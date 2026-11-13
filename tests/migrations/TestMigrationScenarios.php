<?php

namespace tests\migrations;

use CaboLabs\Debbie\DebbieTestCase;
use CaboLabs\Phersistence\migrations\PhMigrator;
use CaboLabs\Phersistence\migrations\PhMigrationException;

/**
 * Realistic migration flows written the way an application (or an AI agent) would write them,
 * using only the driver-neutral API.
 */
class TestMigrationScenarios extends DebbieTestCase {

  private $dir;
  private $tables = ['sc_customer', 'sc_customer_tmp', 'sc_address', 'sc_order', 'sc_old', 'sc_renamed', 'ph_migrations'];

  private function start()
  {
    global $d;
    $this->dir = sys_get_temp_dir() .'/ph_scenarios_'. uniqid();
    $this->prefix = 'p'. substr(uniqid(), -7) .'_';
    $this->seq = 0;
    mkdir($this->dir);
    $this->drop_tables($d);
    return new PhMigrator($d, $this->dir);
  }

  private function finish()
  {
    global $d;
    foreach (glob($this->dir .'/*') as $f) unlink($f);
    rmdir($this->dir);
    $this->drop_tables($d);
  }

  private function drop_tables($d)
  {
    // children first so FKs don't block
    foreach (['sc_order', 'sc_address', 'sc_customer_tmp', 'sc_customer', 'sc_old', 'sc_renamed', 'ph_migrations'] as $t)
    {
      if ($d->table_exists($t)) $d->drop_table($t);
    }
  }

  // each file gets a growing timestamp so they run in the order they are written
  // class names derive from the slug and classes can't be redeclared in one process, so slugs get a per-test prefix
  private $seq = 0;
  private $prefix = '';
  private function s($slug)
  {
    return $this->prefix . $slug;
  }

  private function migration($slug, $body)
  {
    $slug = $this->s($slug);
    $this->seq++;
    $ts = sprintf('2090%010d', $this->seq);
    $class = PhMigrator::class_name($slug);
    file_put_contents($this->dir ."/{$ts}_{$slug}.php",
      "<?php\nuse CaboLabs\\Phersistence\\migrations\\PhMigration;\nclass $class extends PhMigration {\n$body\n}\n");
  }

  private function column_info($table, $column)
  {
    global $d;
    $r = $d->executeRaw('SELECT DATA_TYPE AS t, IS_NULLABLE AS n, COLUMN_DEFAULT AS dflt, CHARACTER_MAXIMUM_LENGTH AS len FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$table, $column]);
    return $r[0] ?? null;
  }

  private function fails_with($fn, $text)
  {
    try { $fn(); } catch (\Exception $e) { return strpos($e->getMessage(), $text) !== false; }
    return false;
  }

  // add a table, add a nullable column, add a mandatory column with a default
  public function test_add_table_and_columns()
  {
    global $d;
    $m = $this->start();

    $this->migration('sc_create_customer', '
      function schema() {
        $this->driver->create_table("sc_customer");
        $this->driver->add_column("sc_customer", "name", "string(100)", false);
      }');
    $m->migrate();
    $this->assert($d->table_exists('sc_customer'), 'table added');
    $this->assert($this->column_info('sc_customer', 'name')['n'] === 'NO', 'name is mandatory');

    $d->insert('sc_customer', ['name' => 'Ann']);

    $this->migration('sc_customer_add_email', '
      function schema() { $this->driver->add_column("sc_customer", "email", "string(255)", true); }');
    $this->migration('sc_customer_add_active', '
      function schema() { $this->driver->add_column("sc_customer", "active", "boolean", false, 1); }');
    $m->migrate();

    $row = $d->select('sc_customer', ['name' => 'Ann'])[0];
    $this->assert($row['email'] === null, 'nullable column is null for old rows');
    $this->assert((int)$row['active'] === 1, 'mandatory column with default filled old rows');

    $this->finish();
  }

  // add a mandatory column without a static default: add nullable, backfill, then make it mandatory
  public function test_seed_mandatory_column()
  {
    global $d;
    $m = $this->start();

    $this->migration('sc_create_customer', '
      function schema() {
        $this->driver->create_table("sc_customer");
        $this->driver->add_column("sc_customer", "name", "string(100)", false);
      }
      function data() {
        $this->driver->insert("sc_customer", ["name" => "Ann"]);
        $this->driver->insert("sc_customer", ["name" => "Bob"]);
      }');
    $m->migrate();

    $this->migration('sc_customer_add_code', '
      function schema() { $this->driver->add_column("sc_customer", "code", "string(20)", true); }
      function data() {
        foreach ($this->driver->select("sc_customer") as $row) {
          $this->driver->update("sc_customer", ["code" => "C". str_pad($row["id"], 4, "0", STR_PAD_LEFT)], ["id" => $row["id"]]);
        }
      }
      function cleanup() { $this->driver->modify_column("sc_customer", "code", "string(20)", false); }');
    $m->migrate();

    $codes = array_column($d->select('sc_customer', [], 'id'), 'code');
    $this->assert($codes === ['C0001', 'C0002'], 'existing rows were seeded');
    $this->assert($this->column_info('sc_customer', 'code')['n'] === 'NO', 'column is mandatory after the migration');

    $this->finish();
  }

  // change the type of a column keeping the data
  public function test_change_column_type()
  {
    global $d;
    $m = $this->start();

    $this->migration('sc_create_customer', '
      function schema() {
        $this->driver->create_table("sc_customer");
        $this->driver->add_column("sc_customer", "name", "string(10)", false);
        $this->driver->add_column("sc_customer", "visits", "int", false, 0);
      }
      function data() { $this->driver->insert("sc_customer", ["name" => "Ann", "visits" => 5]); }');
    $m->migrate();
    $this->assert($this->column_info('sc_customer', 'name')['len'] == 10, 'starts as string(10)');

    $this->migration('sc_customer_widen_types', '
      function schema() {
        $this->driver->modify_column("sc_customer", "name", "string(200)", false);
        $this->driver->modify_column("sc_customer", "visits", "long", false, 0);
      }');
    $m->migrate();

    $this->assert($this->column_info('sc_customer', 'name')['len'] == 200, 'string widened');
    $this->assert($this->column_info('sc_customer', 'visits')['t'] === 'bigint', 'int became long');

    $row = $d->select('sc_customer')[0];
    $this->assert($row['name'] === 'Ann' && (int)$row['visits'] === 5, 'data kept');

    $this->migration('sc_customer_name_to_text', 'function schema() { $this->driver->modify_column("sc_customer", "name", "text", false); }');
    $m->migrate();
    $this->assert($this->column_info('sc_customer', 'name')['t'] === 'text', 'string became text');

    $this->finish();
  }

  // change nullability in both directions, fixing existing nulls first
  public function test_change_nullability()
  {
    global $d;
    $m = $this->start();

    $this->migration('sc_create_customer', '
      function schema() {
        $this->driver->create_table("sc_customer");
        $this->driver->add_column("sc_customer", "name", "string(100)", true);
        $this->driver->add_column("sc_customer", "nick", "string(100)", false, "\'none\'");
      }
      function data() {
        $this->driver->insert("sc_customer", ["name" => null, "nick" => "a"]);
        $this->driver->insert("sc_customer", ["name" => "Bob", "nick" => "b"]);
      }');
    $m->migrate();

    // null -> not null: nulls must be fixed first or the migration fails
    $this->migration('sc_customer_name_mandatory', '
      function schema() { $this->driver->modify_column("sc_customer", "name", "string(100)", false); }');
    $this->assert($this->fails_with(function() use ($m) { $m->migrate(); }, 'failed'), 'making a column with nulls mandatory fails');
    $m->clear_failed($this->s('sc_customer_name_mandatory'));
    unlink(glob($this->dir .'/*_'. $this->s('sc_customer_name_mandatory') .'.php')[0]);

    $this->migration('sc_customer_name_fill_then_mandatory', '
      function schema() {}
      function data() { $this->driver->update("sc_customer", ["name" => "unknown"], ["name" => null]); }
      function cleanup() { $this->driver->modify_column("sc_customer", "name", "string(100)", false); }');
    $m->migrate();
    $this->assert($this->column_info('sc_customer', 'name')['n'] === 'NO', 'name is mandatory');
    $this->assert($d->select('sc_customer', ['name' => 'unknown'])[0]['nick'] === 'a', 'null row was filled');

    // not null -> null
    $this->migration('sc_customer_nick_optional', 'function schema() { $this->driver->modify_column("sc_customer", "nick", "string(100)", true); }');
    $m->migrate();
    $this->assert($this->column_info('sc_customer', 'nick')['n'] === 'YES', 'nick is optional');

    $this->finish();
  }

  // drop a column and rename a column keeping the data, drop and rename a table
  public function test_drop_and_rename()
  {
    global $d;
    $m = $this->start();

    $this->migration('sc_create_customer', '
      function schema() {
        $this->driver->create_table("sc_customer");
        $this->driver->add_column("sc_customer", "name", "string(100)", false);
        $this->driver->add_column("sc_customer", "legacy", "text", true);
      }
      function data() { $this->driver->insert("sc_customer", ["name" => "Ann", "legacy" => "x"]); }');
    $this->migration('sc_create_old', 'function schema() { $this->driver->create_table("sc_old"); }');
    $m->migrate();

    $this->migration('sc_customer_drop_legacy', 'function schema() { $this->driver->drop_column("sc_customer", "legacy"); }');
    $this->migration('sc_customer_rename_name', 'function schema() { $this->driver->rename_column("sc_customer", "name", "full_name"); }');
    $this->migration('sc_drop_old', 'function schema() { $this->driver->drop_table("sc_old"); }');
    $this->migration('sc_rename_customer', 'function schema() { $this->driver->rename_table("sc_customer", "sc_renamed"); }');
    $m->migrate();

    $this->assert(!$d->table_exists('sc_old'), 'table dropped');
    $this->assert(!$d->table_exists('sc_customer') && $d->table_exists('sc_renamed'), 'table renamed');
    $this->assert(!$d->column_exists('sc_renamed', 'legacy'), 'column dropped');
    $this->assert($d->select('sc_renamed')[0]['full_name'] === 'Ann', 'renamed column kept its data');

    $this->finish();
  }

  // seed default data, then delete part of it
  public function test_seed_and_delete_data()
  {
    global $d;
    $m = $this->start();

    $this->migration('sc_create_customer', '
      function schema() {
        $this->driver->create_table("sc_customer");
        $this->driver->add_column("sc_customer", "name", "string(100)", false);
        $this->driver->add_column("sc_customer", "kind", "string(20)", false);
      }
      function data() {
        foreach (["Ann" => "vip", "Bob" => "test", "Cid" => "test"] as $name => $kind) {
          $this->driver->insert("sc_customer", ["name" => $name, "kind" => $kind]);
        }
      }');
    $m->migrate();
    $this->assert(count($d->select('sc_customer')) === 3, 'seeded');

    $this->migration('sc_customer_remove_test_rows', '
      function data() { $this->driver->delete("sc_customer", ["kind" => "test"]); }');
    $m->migrate();
    $rows = $d->select('sc_customer');
    $this->assert(count($rows) === 1 && $rows[0]['name'] === 'Ann', 'only the matching rows were deleted');

    $this->migration('sc_customer_remove_all', 'function data() { $this->driver->delete_all("sc_customer"); }');
    $m->migrate();
    $this->assert(count($d->select('sc_customer')) === 0, 'delete_all emptied the table');

    $this->finish();
  }

  // indexes, unique constraints and foreign keys, and removing them
  public function test_indexes_and_foreign_keys()
  {
    global $d;
    $m = $this->start();

    $this->migration('sc_create_tables', '
      function schema() {
        $this->driver->create_table("sc_customer");
        $this->driver->add_column("sc_customer", "email", "string(100)", false);
        $this->driver->add_unique("sc_customer", ["email"], "sc_customer_email_uq");
        $this->driver->create_table("sc_order");
        $this->driver->add_column("sc_order", "customer_id", "int", true);
        $this->driver->add_index("sc_order", ["customer_id"], "sc_order_customer_idx");
        $this->driver->add_fk("sc_order", "customer_id", "fk_sc_order_customer", "sc_customer", "id");
      }');
    $m->migrate();

    $c = $d->insert('sc_customer', ['email' => 'a@x.com']);
    $d->insert('sc_order', ['customer_id' => $c]);
    $this->assert($this->fails_with(function() use ($d) { $d->insert('sc_customer', ['email' => 'a@x.com']); }, 'Duplicate'), 'unique enforced');
    $this->assert($this->fails_with(function() use ($d) { $d->insert('sc_order', ['customer_id' => 999]); }, 'foreign key'), 'fk enforced');

    $this->migration('sc_remove_constraints', '
      function schema() {
        $this->driver->drop_fk("sc_order", "fk_sc_order_customer");
        $this->driver->drop_index("sc_order", "sc_order_customer_idx");
        $this->driver->drop_index("sc_customer", "sc_customer_email_uq");
      }');
    $m->migrate();

    $d->insert('sc_customer', ['email' => 'a@x.com']);
    $d->insert('sc_order', ['customer_id' => 999]);
    $this->assert(true, 'constraints are gone');

    $this->finish();
  }

  // move data to a new table through a temporary structure (all four phases)
  public function test_split_table_with_intermediate_structure()
  {
    global $d;
    $m = $this->start();

    $this->migration('sc_create_customer', '
      function schema() {
        $this->driver->create_table("sc_customer");
        $this->driver->add_column("sc_customer", "name", "string(100)", false);
        $this->driver->add_column("sc_customer", "street", "string(100)", true);
      }
      function data() {
        $this->driver->insert("sc_customer", ["name" => "Ann", "street" => "Main 1"]);
        $this->driver->insert("sc_customer", ["name" => "Bob", "street" => null]);
      }');
    $m->migrate();

    $this->migration('sc_customer_split_address', '
      function setup() {
        $this->driver->create_table("sc_customer_tmp");
        $this->driver->add_column("sc_customer_tmp", "customer_id", "int", false);
        $this->driver->add_column("sc_customer_tmp", "street", "string(100)", false);
      }
      function schema() {
        $this->driver->create_table("sc_address");
        $this->driver->add_column("sc_address", "customer_id", "int", false);
        $this->driver->add_column("sc_address", "street", "string(100)", false);
        $this->driver->add_fk("sc_address", "customer_id", "fk_sc_address_customer", "sc_customer", "id");
      }
      function data() {
        foreach ($this->driver->select("sc_customer") as $c) {
          if ($c["street"] !== null) $this->driver->insert("sc_customer_tmp", ["customer_id" => $c["id"], "street" => $c["street"]]);
        }
        foreach ($this->driver->select("sc_customer_tmp") as $t) {
          $this->driver->insert("sc_address", ["customer_id" => $t["customer_id"], "street" => $t["street"]]);
        }
      }
      function cleanup() {
        $this->driver->drop_table("sc_customer_tmp");
        $this->driver->drop_column("sc_customer", "street");
      }');
    $m->migrate();

    $addresses = $d->select('sc_address');
    $this->assert(count($addresses) === 1 && $addresses[0]['street'] === 'Main 1', 'data moved');
    $this->assert(!$d->table_exists('sc_customer_tmp'), 'intermediate table removed');
    $this->assert(!$d->column_exists('sc_customer', 'street'), 'legacy column removed');
    $this->assert(count($d->select('sc_customer')) === 2, 'customers kept');

    $this->finish();
  }

  // a data failure rolls back the data of that migration but keeps what earlier migrations did
  public function test_data_rollback_keeps_previous_migrations()
  {
    global $d;
    $m = $this->start();

    $this->migration('sc_create_customer', '
      function schema() {
        $this->driver->create_table("sc_customer");
        $this->driver->add_column("sc_customer", "name", "string(100)", false);
      }
      function data() { $this->driver->insert("sc_customer", ["name" => "Ann"]); }');
    $this->migration('sc_bad_data', '
      function data() {
        $this->driver->insert("sc_customer", ["name" => "Bob"]);
        $this->driver->insert("sc_customer", ["name" => null]);
      }');

    $this->assert($this->fails_with(function() use ($m) { $m->migrate(); }, $this->s('sc_bad_data')), 'second migration fails');
    $names = array_column($d->select('sc_customer'), 'name');
    $this->assert($names === ['Ann'], 'first migration stayed, failed data rolled back');
    $states = array_column($m->status(), 'state', 'slug');
    $this->assert($states[$this->s('sc_create_customer')] === 'applied' && $states[$this->s('sc_bad_data')] === 'failed', 'history reflects it');

    $this->finish();
  }

  // data helpers: bool, null conditions, guards
  public function test_data_helpers()
  {
    global $d;
    $m = $this->start();

    $d->create_table('sc_customer');
    $d->add_column('sc_customer', 'name', 'string(50)', true);
    $d->add_column('sc_customer', 'vip', 'boolean', false, 0);

    $id = $d->insert('sc_customer', ['name' => 'Ann', 'vip' => true]);
    $d->insert('sc_customer', ['name' => null, 'vip' => false]);
    $this->assert($id > 0, 'insert returns the id');
    $this->assert((int)$d->select('sc_customer', ['id' => $id])[0]['vip'] === 1, 'boolean true stored');
    $this->assert(count($d->select('sc_customer', ['name' => null])) === 1, 'null condition');
    $this->assert($d->update('sc_customer', ['vip' => false], ['name' => 'Ann']) === 1, 'update returns affected rows');
    $this->assert($d->delete('sc_customer', ['name' => null]) === 1, 'delete returns affected rows');

    $this->assert($this->fails_with(function() use ($d) { $d->delete('sc_customer', []); }, 'condition'), 'delete without condition refused');
    $this->assert($this->fails_with(function() use ($d) { $d->update('sc_customer', ['vip' => 1], []); }, 'condition'), 'update without condition refused');
    $this->assert($this->fails_with(function() use ($d) { $d->select('sc_customer', ['name; DROP TABLE x' => 1]); }, 'Invalid identifier'), 'bad identifier refused');
    $this->assert($this->fails_with(function() use ($d) { $d->insert('sc_missing', ['a' => 1]); }, "doesn't exist"), 'missing table refused');

    $this->finish();
  }
}

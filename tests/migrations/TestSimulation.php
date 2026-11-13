<?php

namespace tests\migrations;

use CaboLabs\Debbie\DebbieTestCase;
use CaboLabs\Phersistence\migrations\PhMigrator;
use CaboLabs\Phersistence\migrations\PhMigrationSimulator;
use CaboLabs\Phersistence\migrations\PhMigrationCli;
use CaboLabs\Phersistence\migrations\PhMigrationException;

/**
 * Tests the simulation of migrations on a disposable copy of the database.
 */
class TestSimulation extends DebbieTestCase {

  private $dir;
  private $seq = 0;
  private $prefix = '';

  private function start()
  {
    global $d;
    $this->dir = sys_get_temp_dir() .'/ph_sim_'. uniqid();
    mkdir($this->dir);
    $this->prefix = 'p'. substr(uniqid(), -7) .'_';
    $this->seq = 0;

    $this->drop_tables($d);
    (new PhMigrationSimulator($d, $this->dir))->clean();

    // real data the simulation must copy: a parent and a child table with FK, unique and rows
    $d->create_table('sim_parent');
    $d->add_column('sim_parent', 'name', 'string(50)', false);
    $d->add_unique('sim_parent', ['name'], 'sim_parent_name_uq');
    $d->create_table('sim_child');
    $d->add_column('sim_child', 'parent_id', 'int', true);
    $d->add_fk('sim_child', 'parent_id', 'fk_sim_child_parent', 'sim_parent', 'id');
    $p = $d->insert('sim_parent', ['name' => 'Ann']);
    $d->insert('sim_parent', ['name' => 'Bob']);
    $d->insert('sim_child', ['parent_id' => $p]);
  }

  private function finish()
  {
    global $d;
    (new PhMigrationSimulator($d, $this->dir))->clean();
    foreach (glob($this->dir .'/*') as $f) unlink($f);
    rmdir($this->dir);
    $this->drop_tables($d);
  }

  private function drop_tables($d)
  {
    foreach (['sim_child', 'sim_parent', 'sim_new', 'ph_migrations'] as $t) if ($d->table_exists($t)) $d->drop_table($t);
    $r = $d->executeRaw('SELECT TABLE_NAME AS n FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', ['sim_view']);
    if (count($r)) $d->execute('DROP VIEW sim_view');
  }

  private function migration($slug, $body)
  {
    $slug = $this->prefix . $slug;
    $this->seq++;
    $ts = sprintf('2090%010d', $this->seq);
    file_put_contents($this->dir ."/{$ts}_{$slug}.php",
      "<?php\nuse CaboLabs\\Phersistence\\migrations\\PhMigration;\nclass ". PhMigrator::class_name($slug) ." extends PhMigration {\n$body\n}\n");
  }

  private function sim_dbs()
  {
    global $d;
    return (new PhMigrationSimulator($d, $this->dir))->list_simulations();
  }

  private function throws($fn, $text)
  {
    try { $fn(); } catch (\Exception $e) { return strpos($e->getMessage(), $text) !== false; }
    return false;
  }

  // successful simulation: reports the changes, drops the copy, the real database is untouched
  public function test_success()
  {
    global $d;
    $this->start();

    $this->migration('sim_add_stuff', '
      function schema() {
        $this->driver->create_table("sim_new");
        $this->driver->add_column("sim_new", "label", "string(20)", false, "\'x\'");
        $this->driver->add_column("sim_parent", "email", "string(100)", true);
      }
      function data() { $this->driver->insert("sim_new", ["label" => "seed"]); $this->driver->delete("sim_parent", ["name" => "Bob"]); }');

    $r = (new PhMigrationSimulator($d, $this->dir))->simulate();

    $this->assert($r['ok'] === true && $r['ran'] === [$this->prefix .'sim_add_stuff'], 'simulation ok');
    $this->assert($r['kept'] === false && $this->sim_dbs() === [], 'copy dropped');
    $diff = implode("\n", $r['diff']);
    $this->assert(strpos($diff, 'table added: sim_new') !== false, 'diff: table added');
    $this->assert(strpos($diff, 'column added: sim_parent.email') !== false, 'diff: column added');
    $this->assert(strpos($diff, 'rows changed: sim_parent 2 -> 1') !== false, 'diff: rows changed');

    // real database untouched, including no history table
    $this->assert(!$d->table_exists('sim_new'), 'real: no new table');
    $this->assert(!$d->column_exists('sim_parent', 'email'), 'real: no new column');
    $this->assert($d->count_rows('sim_parent') === 2, 'real: rows intact');
    $this->assert(!$d->table_exists('ph_migrations'), 'real: no history table created');

    // the real migration still runs normally afterwards
    $m = new PhMigrator($d, $this->dir);
    $this->assert(count($m->migrate()) === 1 && $d->table_exists('sim_new'), 'real migrate works after simulating');

    $this->finish();
  }

  // failed simulation keeps the copy by default with the partial state, real database untouched
  public function test_failure_keeps_copy()
  {
    global $d;
    $this->start();

    $this->migration('sim_fail', '
      function schema() { $this->driver->create_table("sim_new"); }
      function data() { throw new \Exception("data boom"); }');

    $r = (new PhMigrationSimulator($d, $this->dir))->simulate();

    $this->assert($r['ok'] === false, 'simulation failed');
    $this->assert(strpos($r['error'], 'failed in phase data: data boom') !== false && strpos($r['error'], 'What to do next') !== false, 'has the failure report');
    $this->assert($r['kept'] === true && count($this->sim_dbs()) === 1 && $this->sim_dbs()[0] === $r['database'], 'copy kept');
    $this->assert(strpos(implode("\n", $r['diff']), 'table added: sim_new') !== false, 'diff shows the schema that stayed');

    // the copy can be used to rehearse the fix
    $sim = $d->connection_for($r['database']);
    $this->assert($sim->table_exists('sim_new') && $sim->table_exists('sim_parent'), 'copy has partial schema and data');
    $this->assert($sim->count_rows('sim_parent') === 2, 'copy has the data');
    $sim->close();

    $this->assert(!$d->table_exists('sim_new') && !$d->table_exists('ph_migrations'), 'real database untouched');

    // clean removes it
    $dropped = (new PhMigrationSimulator($d, $this->dir))->clean();
    $this->assert($dropped === [$r['database']] && $this->sim_dbs() === [], 'clean dropped the copy');

    $this->finish();
  }

  // cleanup_on_failure and keep options
  public function test_options()
  {
    global $d;
    $this->start();

    $this->migration('sim_fail_opts', 'function data() { throw new \Exception("boom"); }');
    $r = (new PhMigrationSimulator($d, $this->dir))->simulate(['cleanup_on_failure' => true]);
    $this->assert($r['ok'] === false && $r['kept'] === false && $this->sim_dbs() === [], 'cleanup_on_failure drops the copy');

    // replace the failing migration with a good one
    foreach (glob($this->dir .'/*') as $f) unlink($f);
    $this->migration('sim_ok_opts', 'function schema() { $this->driver->create_table("sim_new"); }');
    $r = (new PhMigrationSimulator($d, $this->dir))->simulate(['keep' => true]);
    $this->assert($r['ok'] === true && $r['kept'] === true && count($this->sim_dbs()) === 1, 'keep keeps the copy after success');

    $this->finish();
  }

  // checks happen first: nothing is copied when migrating can't work or there is nothing to do
  public function test_checks_before_copy()
  {
    global $d;
    $this->start();
    $sim = new PhMigrationSimulator($d, $this->dir);

    // nothing pending
    $r = $sim->simulate();
    $this->assert($r['pending'] === [] && $r['database'] === null && $this->sim_dbs() === [], 'nothing pending, nothing copied');

    // out of order
    $this->migration('sim_late', '');
    (new PhMigrator($d, $this->dir))->migrate();
    file_put_contents($this->dir .'/20800101000000_'. $this->prefix .'sim_early.php',
      "<?php\nuse CaboLabs\\Phersistence\\migrations\\PhMigration;\nclass ". PhMigrator::class_name($this->prefix .'sim_early') ." extends PhMigration {}\n");
    $this->assert($this->throws(function() use ($sim) { $sim->simulate(); }, 'Out-of-order'), 'out of order stops the simulation');
    $this->assert($this->sim_dbs() === [], 'no copy was made');

    // modified applied migration
    unlink($this->dir .'/20800101000000_'. $this->prefix .'sim_early.php');
    file_put_contents(glob($this->dir .'/*_'. $this->prefix .'sim_late.php')[0], "\n// edited", FILE_APPEND);
    $this->assert($this->throws(function() use ($sim) { $sim->simulate(); }, 'modified'), 'modified migration stops the simulation');
    $this->assert($this->sim_dbs() === [], 'no copy was made either');

    $this->finish();
  }

  // the copy keeps structure, constraints, rows and ids
  public function test_copy_fidelity()
  {
    global $d;
    $this->start();

    $name = $d->current_database() .'_sim_20000101000000';
    $d->clone_database($name);
    $c = $d->connection_for($name);

    $this->assert($c->count_rows('sim_parent') === 2 && $c->count_rows('sim_child') === 1, 'rows copied');
    $this->assert($c->fk_exists('sim_child', 'fk_sim_child_parent'), 'foreign key copied');
    $this->assert($c->index_exists('sim_parent', 'sim_parent_name_uq'), 'unique copied');
    $this->assert($this->throws(function() use ($c) { $c->insert('sim_parent', ['name' => 'Ann']); }, 'Duplicate'), 'unique enforced on the copy');

    $next = $c->insert('sim_parent', ['name' => 'Cid']);
    $this->assert($next > 2, 'auto increment continues after the copied ids');
    $this->assert(count($d->select('sim_parent')) === 2, 'writes on the copy do not reach the real database');

    $c->close();
    $this->assert($this->throws(function() use ($d, $name) { $d->clone_database($name); }, 'already exists'), 'existing target refused');
    $this->assert($this->throws(function() use ($d) { $d->clone_database($d->current_database()); }, 'current database'), 'cloning onto itself refused');
    $this->assert($this->throws(function() use ($d) { $d->drop_database($d->current_database()); }, 'Refusing'), 'dropping the current database refused');
    $d->drop_database($name);

    $this->finish();
  }

  // objects that are not copied are reported
  public function test_unsupported_objects_reported()
  {
    global $d;
    $this->start();

    $d->execute('CREATE VIEW sim_view AS SELECT id FROM sim_parent');
    $this->migration('sim_with_view', 'function schema() { $this->driver->create_table("sim_new"); }');

    $r = (new PhMigrationSimulator($d, $this->dir))->simulate();
    $this->assert($r['ok'] === true && $r['unsupported'] === ['view sim_view'], 'view reported as not copied');

    $this->finish();
  }

  // simulation lock does not block the real database
  public function test_locks_are_per_database()
  {
    global $d;
    $this->start();

    $this->migration('sim_lock', 'function schema() { $this->driver->create_table("sim_new"); }');
    $r = (new PhMigrationSimulator($d, $this->dir))->simulate(['keep' => true]);

    $this->assert($r['ok'] === true, 'simulated while keeping the copy');
    $this->assert(count((new PhMigrator($d, $this->dir))->migrate()) === 1, 'real migrate not blocked by the simulation lock');

    $this->finish();
  }

  // CLI commands
  public function test_cli()
  {
    global $d;
    $this->start();

    $lines = [];
    $cli = new PhMigrationCli($d, $this->dir, function($l) use (&$lines) { $lines[] = $l; });

    $this->assert($cli->run(['simulate']) === 0 && strpos(implode("\n", $lines), 'Nothing to simulate') !== false, 'nothing to simulate');

    $this->migration('sim_cli_ok', 'function schema() { $this->driver->create_table("sim_new"); }');
    $lines = [];
    $this->assert($cli->run(['simulate']) === 0, 'simulate ok exits 0');
    $out = implode("\n", $lines);
    $this->assert(strpos($out, 'SIMULATION OK') !== false && strpos($out, 'table added: sim_new') !== false && strpos($out, 'the real database was not modified') !== false, 'report printed');
    $this->assert($this->sim_dbs() === [], 'copy dropped');

    // failing migration
    $this->migration('sim_cli_fail', 'function data() { throw new \Exception("cli boom"); }');
    $lines = [];
    $this->assert($cli->run(['simulate']) === 1, 'failed simulation exits 1');
    $out = implode("\n", $lines);
    $this->assert(strpos($out, 'SIMULATION FAILED') !== false && strpos($out, 'cli boom') !== false && strpos($out, 'was kept') !== false, 'failure report printed, copy kept');

    $lines = [];
    $cli->run(['simulations']);
    $this->assert(count($lines) === 1 && strpos($lines[0], '_sim_') !== false, 'simulations lists the copy');

    $lines = [];
    $cli->run(['clean-simulations']);
    $this->assert(strpos(implode("\n", $lines), 'Dropped') !== false && $this->sim_dbs() === [], 'clean-simulations drops it');

    $lines = [];
    $cli->run(['simulate', '--cleanup-on-failure']);
    $this->assert($this->sim_dbs() === [] && strpos(implode("\n", $lines), 'The copy was dropped') !== false, '--cleanup-on-failure drops the failed copy');

    $this->finish();
  }
}

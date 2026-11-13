<?php

namespace tests\migrations;

use CaboLabs\Debbie\DebbieTestCase;
use CaboLabs\Phersistence\migrations\PhMigrator;
use CaboLabs\Phersistence\migrations\PhMigrationException;

/**
 * Tests the migration runner: ordering, history, phases, out-of-order detection, failures, make/rebase.
 */
class TestMigrator extends DebbieTestCase {

  private $dir;

  private function setup_dir()
  {
    global $d;
    $this->dir = sys_get_temp_dir() .'/ph_migrations_'. uniqid();
    mkdir($this->dir);
    $GLOBALS['mig_log'] = [];
    foreach (['mig_t1', 'mig_t2', 'ph_migrations'] as $t) if ($d->table_exists($t)) $d->drop_table($t);
  }

  private function teardown_dir()
  {
    global $d;
    foreach (glob($this->dir .'/*') as $f) unlink($f);
    rmdir($this->dir);
    foreach (['mig_t1', 'mig_t2', 'ph_migrations'] as $t) if ($d->table_exists($t)) $d->drop_table($t);
  }

  private function write($ts, $slug, $body = '')
  {
    $class = PhMigrator::class_name($slug);
    file_put_contents($this->dir ."/{$ts}_{$slug}.php",
      "<?php\nuse CaboLabs\\Phersistence\\migrations\\PhMigration;\nclass $class extends PhMigration {\n$body\n}\n");
  }

  private function throws($fn, $contains = '')
  {
    try { $fn(); } catch (PhMigrationException $e) { return $contains === '' || strpos($e->getMessage(), $contains) !== false; }
    return false;
  }

  // phases run in order, history is recorded, second run does nothing
  public function test_1()
  {
    global $d;
    $this->setup_dir();

    $this->write('20260101000000', 'mt_phases_one', '
      function setup()   { $GLOBALS["mig_log"][] = "setup"; }
      function schema()  { $GLOBALS["mig_log"][] = "schema"; $this->driver->create_table("mig_t1"); }
      function data()    { $GLOBALS["mig_log"][] = "data"; }
      function cleanup() { $GLOBALS["mig_log"][] = "cleanup"; }');
    $this->write('20260102000000', 'mt_phases_two', 'function schema() { $GLOBALS["mig_log"][] = "two"; $this->driver->create_table("mig_t2"); }');

    $m = new PhMigrator($d, $this->dir);
    $ran = $m->migrate();

    $this->assert($ran === ['mt_phases_one', 'mt_phases_two'], 'both ran in order');
    $this->assert($GLOBALS['mig_log'] === ['setup', 'schema', 'data', 'cleanup', 'two'], 'phases in order');
    $this->assert($d->table_exists('mig_t1') && $d->table_exists('mig_t2'), 'schema applied');
    $this->assert($m->migrate() === [], 'second run does nothing');

    $states = array_column($m->status(), 'state');
    $this->assert($states === ['applied', 'applied'], 'status applied');

    $this->teardown_dir();
  }

  // out-of-order pending migration is a hard error, rebase fixes it, flag overrides
  public function test_2()
  {
    global $d;
    $this->setup_dir();

    $this->write('20260102000000', 'mt_ooo_late', 'function schema() { $this->driver->create_table("mig_t1"); }');
    $m = new PhMigrator($d, $this->dir);
    $m->migrate();

    $this->write('20260101000000', 'mt_ooo_early', 'function schema() { $this->driver->create_table("mig_t2"); }');
    $states = array_column($m->status(), 'state', 'slug');
    $this->assert($states['mt_ooo_early'] === 'out_of_order', 'detected out of order');
    $this->assert($this->throws(function() use ($m) { $m->migrate(); }, 'Out-of-order'), 'migrate fails');
    $this->assert(!$d->table_exists('mig_t2'), 'nothing ran');

    // rebase gives a new timestamp and the migration runs
    $new = $m->rebase('mt_ooo_early');
    $this->assert(basename($new) > '20260102000000_', 'new timestamp is newer');
    $this->assert($m->migrate() === ['mt_ooo_early'], 'runs after rebase');

    // flag override
    $d->drop_table('mig_t2');
    $this->write('20991231000000', 'mt_ooo_flag_a', '');
    $m->migrate();
    $this->write('20991230000000', 'mt_ooo_flag_b', 'function schema() { $this->driver->create_table("mig_t2"); }');
    $ran = $m->migrate(true);
    $this->assert($ran === ['mt_ooo_flag_b'], 'flag runs out of order migration');

    $this->teardown_dir();
  }

  // modified applied migration and missing file are errors
  public function test_3()
  {
    global $d;
    $this->setup_dir();

    $this->write('20260101000000', 'mt_checksum', 'function schema() { $this->driver->create_table("mig_t1"); }');
    $m = new PhMigrator($d, $this->dir);
    $m->migrate();

    file_put_contents($this->dir .'/20260101000000_mt_checksum.php', "\n// edited", FILE_APPEND);
    $this->assert($this->throws(function() use ($m) { $m->migrate(); }, 'modified'), 'modified detected');

    unlink($this->dir .'/20260101000000_mt_checksum.php');
    $this->assert($this->throws(function() use ($m) { $m->migrate(); }, 'no file'), 'missing detected');

    $this->teardown_dir();
  }

  // failure in data rolls the data back, blocks later runs until cleared
  public function test_4()
  {
    global $d;
    $this->setup_dir();

    $this->write('20260101000000', 'mt_fail', '
      function schema() { $this->driver->create_table("mig_t1"); $this->driver->add_column("mig_t1", "v", "int", true); }
      function data() {
        $this->driver->execute("INSERT INTO mig_t1 (v) VALUES (1)");
        throw new \Exception("boom");
      }');
    $m = new PhMigrator($d, $this->dir);

    $this->assert($this->throws(function() use ($m) { $m->migrate(); }, 'boom'), 'failure reported');
    $this->assert($d->table_exists('mig_t1'), 'schema stays (DDL is not transactional)');
    $this->assert(count($d->executeRaw('SELECT * FROM mig_t1', [])) === 0, 'data rolled back');
    $this->assert(array_column($m->status(), 'state') === ['failed'], 'status failed');
    $this->assert($this->throws(function() use ($m) { $m->migrate(); }, 'clear_failed'), 'blocked while failed');

    $m->clear_failed('mt_fail');
    $this->assert(array_column($m->status(), 'state') === ['pending'], 'pending after clear');

    $this->teardown_dir();
  }

  // make, duplicate slug, rebase of applied, baseline, invalid names
  public function test_5()
  {
    global $d;
    $this->setup_dir();
    $m = new PhMigrator($d, $this->dir);

    $file = $m->make('mt_made');
    $this->assert(file_exists($file) && preg_match('/\d{14}_mt_made\.php$/', $file), 'file created');
    $this->assert(strpos(file_get_contents($file), 'class MtMade extends PhMigration') !== false, 'class generated');
    $this->assert($this->throws(function() use ($m) { $m->make('mt_made'); }, 'already exists'), 'duplicate slug refused');
    $this->assert($this->throws(function() use ($m) { $m->make('Bad-Slug'); }, 'Invalid slug'), 'invalid slug refused');

    $m->migrate();
    $this->assert($this->throws(function() use ($m) { $m->rebase('mt_made'); }, 'only pending'), 'rebase of applied refused');

    $this->write('20260101000000', 'mt_baselined', 'function schema() { $this->driver->create_table("mig_t1"); }');
    $m->baseline('mt_baselined');
    $this->assert(!$d->table_exists('mig_t1'), 'baseline does not run');
    $this->assert(array_column($m->status(), 'state', 'slug')['mt_baselined'] === 'applied', 'baseline recorded');

    file_put_contents($this->dir .'/bad name.php', '<?php');
    $this->assert($this->throws(function() use ($m) { $m->status(); }, 'Invalid migration file name'), 'invalid file name refused');
    unlink($this->dir .'/bad name.php');

    $this->teardown_dir();
  }
}

<?php

namespace tests\migrations;

use CaboLabs\Debbie\DebbieTestCase;
use CaboLabs\Phersistence\migrations\PhMigrationCli;

/**
 * Tests the command line front end with an injected driver and output.
 */
class TestMigrationCli extends DebbieTestCase {

  private $dir;
  private $lines;

  private function start()
  {
    global $d;
    $this->dir = sys_get_temp_dir() .'/ph_cli_'. uniqid();
    mkdir($this->dir);
    $this->lines = [];
    foreach (['cli_t1', 'ph_migrations'] as $t) if ($d->table_exists($t)) $d->drop_table($t);

    $lines =& $this->lines;
    return new PhMigrationCli($d, $this->dir, function($l) use (&$lines) { $lines[] = $l; });
  }

  private function finish()
  {
    global $d;
    foreach (glob($this->dir .'/*') as $f) unlink($f);
    rmdir($this->dir);
    foreach (['cli_t1', 'ph_migrations'] as $t) if ($d->table_exists($t)) $d->drop_table($t);
  }

  private function output()
  {
    return implode("\n", $this->lines);
  }

  // make, status, migrate through the CLI
  public function test_1()
  {
    global $d;
    $cli = $this->start();

    $this->assert($cli->run(['make', 'cli_first']) === 0, 'make ok');
    $file = glob($this->dir .'/*_cli_first.php')[0];
    $this->assert(file_exists($file), 'file created');

    // fill the generated template
    $code = file_get_contents($file);
    $code = str_replace('// public function schema() {}', 'public function schema() { $this->driver->create_table("cli_t1"); }', $code);
    file_put_contents($file, $code);

    $this->lines = [];
    $cli->run(['status']);
    $this->assert(strpos($this->output(), 'pending') !== false && strpos($this->output(), 'cli_first') !== false, 'status shows pending');

    $this->lines = [];
    $this->assert($cli->run(['migrate']) === 0, 'migrate ok');
    $this->assert($d->table_exists('cli_t1'), 'migration ran');
    $this->assert(strpos($this->output(), '1 migration(s) applied') !== false, 'summary printed');

    $this->lines = [];
    $cli->run(['migrate']);
    $this->assert(strpos($this->output(), 'Nothing to migrate') !== false, 'nothing to migrate');

    $this->finish();
  }

  // errors give a non zero exit code and a message
  public function test_2()
  {
    $cli = $this->start();

    $this->assert($cli->run([]) === 1, 'no command is an error');
    $this->assert($cli->run(['help']) === 0, 'help ok');
    $this->assert($cli->run(['nope']) === 1, 'unknown command');
    $this->assert($cli->run(['make']) === 1, 'missing slug');
    $this->assert($cli->run(['make', 'Bad Slug']) === 1, 'invalid slug');
    $this->assert($cli->run(['rebase', 'missing_slug']) === 1, 'rebase of unknown slug');

    $this->finish();
  }

  // out-of-order is an error through the CLI, rebase and the override flag resolve it
  public function test_3()
  {
    global $d;
    $cli = $this->start();

    file_put_contents($this->dir .'/20991231000000_cli_late.php',
      "<?php\nuse CaboLabs\\Phersistence\\migrations\\PhMigration;\nclass CliLate extends PhMigration {}\n");
    $cli->run(['migrate']);

    file_put_contents($this->dir .'/20991230000000_cli_early.php',
      "<?php\nuse CaboLabs\\Phersistence\\migrations\\PhMigration;\nclass CliEarly extends PhMigration {}\n");

    $this->lines = [];
    $this->assert($cli->run(['migrate']) === 1, 'out of order fails');
    $this->assert(strpos($this->output(), 'rebase cli_early') !== false, 'message names the fix');

    $this->lines = [];
    $this->assert($cli->run(['migrate', '--allow-out-of-order']) === 0, 'flag allows it');
    $this->assert(strpos($this->output(), 'WARNING') !== false, 'flag warns');

    $this->finish();
  }

  // config file loading
  public function test_4()
  {
    $lines = [];
    $cli = new PhMigrationCli(null, null, function($l) use (&$lines) { $lines[] = $l; });

    $this->assert($cli->run(['status', '--config=/nonexistent/phersistence.php']) === 1, 'missing config fails');
    $this->assert(strpos(implode("\n", $lines), 'not found') !== false, 'message explains it');
  }
}

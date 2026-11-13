<?php

namespace CaboLabs\Phersistence\migrations;

use CaboLabs\Phersistence\drivers\Driver;
use CaboLabs\Phersistence\drivers\MySQL;

/**
 * Command line front end of the migrator. Used by bin/phersistence, exposed as vendor/bin/phersistence.
 *
 * The consuming application provides a phersistence.php config file in its root that returns an array:
 *   ['host' => 'localhost', 'user' => '...', 'pass' => '...', 'db' => '...', 'migrations' => 'migrations']
 * or ['driver' => $driverInstance, 'migrations' => 'migrations']. 'migrations' is optional, default 'migrations'.
 */
class PhMigrationCli {

  const USAGE = <<<'TXT'
Usage: phersistence <command> [options]

Commands:
  make <slug>              Create a migration file from the template
  status                   List migrations and their state
  migrate                  Run pending migrations in order
  simulate                 Run pending migrations on a disposable copy of the database and report the result
  simulations              List the simulation databases
  clean-simulations        Drop every simulation database
  rebase <slug>            Give a pending migration a fresh timestamp
  baseline <slug>          Mark a migration as applied without running it
  clear-failed <slug>      Remove the failed record of a migration so it can run again
  help                     Show this help

Options:
  --config=<file>          Config file (default ./phersistence.php)
  --path=<folder>          Migrations folder (overrides the config, default ./migrations)
  --keep                   With simulate: keep the copy after a successful run (it is dropped by default)
  --cleanup-on-failure     With simulate: drop the copy when the run fails (it is kept by default, to investigate and rehearse the fix)
  --verbose                Log every SQL statement
  --allow-out-of-order     With migrate or simulate: run pending migrations older than the latest applied one
TXT;

  private $driver;
  private $path;
  private $out;

  /**
   * @param Driver|null $driver if null, it is built from the config file
   * @param string|null $path migrations folder, if null it comes from the config file or defaults to 'migrations'
   * @param callable|null $out function(string $line), defaults to printing to stdout
   */
  public function __construct(Driver $driver = null, $path = null, $out = null)
  {
    $this->driver = $driver;
    $this->path   = $path;
    $this->out    = $out ?: function($line) { echo $line . PHP_EOL; };
  }

  /**
   * @param array $args arguments without the script name
   * @return int exit code
   */
  public function run(array $args)
  {
    list($command, $positional, $options) = $this->parse($args);

    if ($command === null || $command == 'help')
    {
      $this->say(self::USAGE);
      return $command === null ? 1 : 0;
    }

    $known = ['make', 'status', 'migrate', 'simulate', 'simulations', 'clean-simulations', 'rebase', 'baseline', 'clear-failed'];
    if (!in_array($command, $known))
    {
      $this->say("Unknown command '$command'");
      $this->say(self::USAGE);
      return 1;
    }

    try
    {
      $this->setup($options);
      $migrator = new PhMigrator($this->driver, $this->path, $this->out);

      switch ($command)
      {
        case 'make':
          $file = $migrator->make($this->arg($positional, 'slug'));
          $this->say("Created $file");
        break;
        case 'status':
          $this->status($migrator);
        break;
        case 'migrate':
          $ran = $migrator->migrate(isset($options['allow-out-of-order']));
          $this->say(count($ran) ? 'Done: '. count($ran) .' migration(s) applied' : 'Nothing to migrate');
        break;
        case 'simulate':
          $ok = $this->simulate($options);
          if (!$ok) return 1;
        break;
        case 'simulations':
          $sims = (new PhMigrationSimulator($this->driver, $this->path, $this->out))->list_simulations();
          foreach ($sims as $db) $this->say($db);
          if (count($sims) == 0) $this->say('No simulation databases');
        break;
        case 'clean-simulations':
          $dropped = (new PhMigrationSimulator($this->driver, $this->path, $this->out))->clean();
          foreach ($dropped as $db) $this->say("Dropped $db");
          if (count($dropped) == 0) $this->say('No simulation databases');
        break;
        case 'rebase':
          $migrator->rebase($this->arg($positional, 'slug'));
        break;
        case 'baseline':
          $slug = $this->arg($positional, 'slug');
          $migrator->baseline($slug);
          $this->say("Marked $slug as applied");
        break;
        case 'clear-failed':
          $slug = $this->arg($positional, 'slug');
          $migrator->clear_failed($slug);
          $this->say("Cleared $slug");
        break;
      }
    }
    catch (\Exception $e)
    {
      $this->say('Error: '. $e->getMessage());
      return 1;
    }

    return 0;
  }

  /**
   * @return boolean false if the simulation failed
   */
  private function simulate(array $options)
  {
    $sim = new PhMigrationSimulator($this->driver, $this->path, $this->out);
    $r = $sim->simulate([
      'keep'               => isset($options['keep']),
      'cleanup_on_failure' => isset($options['cleanup-on-failure']),
      'allow_out_of_order' => isset($options['allow-out-of-order'])
    ]);

    if (count($r['pending']) == 0)
    {
      $this->say('Nothing to simulate: no pending migrations');
      return true;
    }

    $this->say('');
    $this->say('Simulation on '. $r['database'] .' (the real database was not modified)');
    $this->say('Pending: '. implode(', ', $r['pending']));
    foreach ($r['unsupported'] as $o) $this->say('WARNING: not copied, the simulation does not cover: '. $o);

    foreach ($r['ran'] as $slug) $this->say("  applied  $slug");
    if (!$r['ok'])
    {
      $this->say('');
      $this->say('SIMULATION FAILED');
      $this->say($r['error']);
    }

    $this->say('');
    $this->say(count($r['diff']) ? 'Changes on the copy:' : 'No schema or row count changes on the copy');
    foreach ($r['diff'] as $l) $this->say('  '. $l);

    $this->say('');
    if ($r['kept'])
    {
      $this->say("The copy '{$r['database']}' was kept. Use it to investigate or to rehearse the fix, then run 'clean-simulations' to drop it.");
    }
    else
    {
      $this->say('The copy was dropped.');
    }

    $this->say($r['ok'] ? 'SIMULATION OK: the pending migrations can be run on the real database' : 'The real database is unchanged.');
    return $r['ok'];
  }

  private function status(PhMigrator $migrator)
  {
    $rows = $migrator->status();
    if (count($rows) == 0)
    {
      $this->say('No migrations');
      return;
    }

    foreach ($rows as $r)
    {
      $this->say(sprintf('%-13s %s_%s%s', $r['state'], $r['ts'], $r['slug'], $r['error'] ? '  ('. $r['error'] .')' : ''));
    }
  }

  private function arg($positional, $name)
  {
    if (empty($positional)) throw new PhMigrationException("Missing argument <$name>");
    return $positional[0];
  }

  private function parse(array $args)
  {
    $command = null;
    $positional = [];
    $options = [];

    foreach ($args as $a)
    {
      if (substr($a, 0, 2) == '--')
      {
        $kv = explode('=', substr($a, 2), 2);
        $options[$kv[0]] = $kv[1] ?? true;
      }
      else if ($command === null) $command = $a;
      else $positional[] = $a;
    }
    return [$command, $positional, $options];
  }

  private function setup(array $options)
  {
    if (isset($options['path'])) $this->path = $options['path'];

    if (!is_null($this->driver))
    {
      if (is_null($this->path)) $this->path = 'migrations';
      return;
    }

    $file = $options['config'] ?? 'phersistence.php';
    if (!is_file($file))
    {
      throw new PhMigrationException("Config file '$file' not found. Create it in your project root, see the documentation.");
    }

    $config = require $file;
    if (!is_array($config)) throw new PhMigrationException("Config file '$file' must return an array");

    if (isset($config['driver']) && $config['driver'] instanceof Driver)
    {
      $this->driver = $config['driver'];
    }
    else
    {
      foreach (['host', 'user', 'pass', 'db'] as $k)
      {
        if (!array_key_exists($k, $config)) throw new PhMigrationException("Config file '$file' is missing '$k'");
      }
      $this->driver = new MySQL();
      $this->driver->connect($config['host'], $config['user'], $config['pass'], $config['db']);
    }

    if (is_null($this->path)) $this->path = $config['migrations'] ?? 'migrations';
  }

  private function say($line)
  {
    call_user_func($this->out, $line);
  }
}

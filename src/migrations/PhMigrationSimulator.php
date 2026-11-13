<?php

namespace CaboLabs\Phersistence\migrations;

use CaboLabs\Phersistence\drivers\Driver;

/**
 * Runs the pending migrations on a disposable copy of the database, so the result can be checked
 * before touching the real one. The real database is only read.
 *
 * Copies are named <database>_sim_<YYYYMMDDHHMMSS>.
 */
class PhMigrationSimulator {

  private $driver;
  private $path;
  private $logger;

  public function __construct(Driver $driver, $path = 'migrations', $logger = null)
  {
    $this->driver = $driver;
    $this->path   = $path;
    $this->logger = $logger;
  }

  /**
   * @param array $options keep (keep the copy after a successful run), cleanup_on_failure (drop the copy when
   *                       the run fails), allow_out_of_order
   * @return array [
   *   'pending'    => slugs that were to run,
   *   'ok'         => bool, false if a migration failed,
   *   'database'   => name of the copy, or null if nothing was simulated,
   *   'kept'       => bool, the copy still exists,
   *   'ran'        => slugs that ran on the copy,
   *   'error'      => failure report or null,
   *   'diff'       => schema and row count differences,
   *   'unsupported'=> objects that were not copied
   * ]
   * @throws PhMigrationException if the migrations can't run at all (same checks as migrate), nothing is copied then
   */
  public function simulate(array $options = [])
  {
    $keep               = !empty($options['keep']);
    $cleanup_on_failure = !empty($options['cleanup_on_failure']);
    $allow_ooo          = !empty($options['allow_out_of_order']);

    // checks first, on the real database and read only, so a large copy isn't wasted on a run that can't work
    $real_migrator = new PhMigrator($this->driver, $this->path, $this->logger);
    $pending = array_column($real_migrator->check_pending($allow_ooo), 'slug');

    $result = ['pending' => $pending, 'ok' => true, 'database' => null, 'kept' => false, 'ran' => [],
               'error' => null, 'diff' => [], 'unsupported' => []];

    if (count($pending) == 0) return $result;

    $result['unsupported'] = $this->driver->unsupported_objects();

    $name = $this->new_name();
    $this->log("Copying database '". $this->driver->current_database() ."' to '$name'");
    $this->driver->clone_database($name);
    $result['database'] = $name;
    $result['kept'] = true;

    $sim = $this->driver->connection_for($name);
    $before = self::snapshot($sim);

    try
    {
      $migrator = new PhMigrator($sim, $this->path, $this->logger);
      try
      {
        $result['ran'] = $migrator->migrate($allow_ooo);
      }
      catch (PhMigrationException $e)
      {
        $result['ok'] = false;
        $result['error'] = $e->getMessage();
        foreach ($migrator->status() as $s) if ($s['state'] == 'applied' && in_array($s['slug'], $pending)) $result['ran'][] = $s['slug'];
      }

      $result['diff'] = self::diff($before, self::snapshot($sim));
    }
    finally
    {
      $sim->close();
    }

    if (($result['ok'] && !$keep) || (!$result['ok'] && $cleanup_on_failure))
    {
      $this->driver->drop_database($name);
      $result['kept'] = false;
    }

    return $result;
  }

  /**
   * Existing simulation databases of the current database.
   */
  public function list_simulations()
  {
    $prefix = $this->driver->current_database() .'_sim_';
    $found = [];
    foreach ($this->driver->list_databases() as $db)
    {
      if (strpos($db, $prefix) === 0 && preg_match('/^\d{14}$/', substr($db, strlen($prefix)))) $found[] = $db;
    }
    return $found;
  }

  /**
   * Drops every simulation database of the current database. Returns the dropped names.
   */
  public function clean()
  {
    $dropped = [];
    foreach ($this->list_simulations() as $db)
    {
      $this->driver->drop_database($db);
      $dropped[] = $db;
    }
    return $dropped;
  }

  private function new_name()
  {
    $existing = $this->driver->list_databases();
    $ts = time();
    do
    {
      $name = $this->driver->current_database() .'_sim_'. date('YmdHis', $ts++);
    }
    while (in_array($name, $existing));
    return $name;
  }

  /**
   * Tables with their columns and row count, without the migrations history.
   */
  public static function snapshot(Driver $driver)
  {
    $snap = [];
    foreach ($driver->get_tables() as $t)
    {
      if ($t == PhMigrator::HISTORY_TABLE) continue;

      $cols = [];
      foreach ($driver->describe_table($t) as $c)
      {
        $cols[$c['name']] = $c['type'] .' '. ($c['nullable'] ? 'NULL' : 'NOT NULL') . (is_null($c['default']) ? '' : ' DEFAULT '. $c['default']);
      }
      $snap[$t] = ['columns' => $cols, 'rows' => $driver->count_rows($t)];
    }
    return $snap;
  }

  /**
   * Human readable differences between two snapshots.
   */
  public static function diff(array $before, array $after)
  {
    $lines = [];

    foreach ($after as $t => $info)
    {
      if (!isset($before[$t]))
      {
        $lines[] = "table added: $t (". count($info['columns']) ." columns, {$info['rows']} rows)";
        continue;
      }

      foreach ($info['columns'] as $c => $def)
      {
        if (!isset($before[$t]['columns'][$c]))           $lines[] = "column added: $t.$c $def";
        else if ($before[$t]['columns'][$c] !== $def)     $lines[] = "column changed: $t.$c {$before[$t]['columns'][$c]} -> $def";
      }
      foreach ($before[$t]['columns'] as $c => $def)
      {
        if (!isset($info['columns'][$c])) $lines[] = "column dropped: $t.$c";
      }

      if ($before[$t]['rows'] != $info['rows']) $lines[] = "rows changed: $t {$before[$t]['rows']} -> {$info['rows']}";
    }

    foreach ($before as $t => $info)
    {
      if (!isset($after[$t])) $lines[] = "table dropped: $t ({$info['rows']} rows)";
    }

    return $lines;
  }

  private function log($msg)
  {
    if (is_callable($this->logger)) call_user_func($this->logger, $msg);
  }
}

<?php

namespace CaboLabs\Phersistence\migrations;

use CaboLabs\Phersistence\drivers\Driver;

/**
 * Discovers, orders and runs migrations, and keeps the history in the ph_migrations table.
 * See docs/MIGRATIONS_AI_RULES.md for the rules.
 */
class PhMigrator {

  const HISTORY_TABLE = 'ph_migrations';
  const LOCK_PREFIX   = 'phersistence_migrations_';

  const FILE_PATTERN = '/^(\d{14})_([a-z][a-z0-9_]*)\.php$/';

  // status values stored in the history
  const OK      = 'ok';
  const FAILED  = 'failed';
  const RUNNING = 'running';

  private $driver;
  private $path;
  private $logger;

  /**
   * @param Driver $driver
   * @param string $path folder with the migration files
   * @param callable|null $logger function(string $message)
   */
  public function __construct(Driver $driver, $path = 'migrations', $logger = null)
  {
    $this->driver = $driver;
    $this->path   = rtrim($path, '/');
    $this->logger = $logger;
  }

  /** ******************************
   * History
   */

  public function ensure_history()
  {
    if ($this->driver->table_exists(self::HISTORY_TABLE)) return;

    $t = self::HISTORY_TABLE;
    $this->driver->create_table($t);
    $this->driver->add_column($t, 'slug',        'string(255)', false);
    $this->driver->add_column($t, 'ts',          'string(14)',  false);
    $this->driver->add_column($t, 'checksum',    'string(64)',  false);
    $this->driver->add_column($t, 'status',      'string(10)',  false);
    $this->driver->add_column($t, 'batch',       'int',         false);
    $this->driver->add_column($t, 'executed_at', 'datetime',    false);
    $this->driver->add_column($t, 'error',       'text',        true);
    $this->driver->add_unique($t, ['slug'], 'ph_migrations_slug_unique');
  }

  /**
   * Reads the history. Read only: if the history table doesn't exist yet the history is empty and nothing is created.
   */
  private function history()
  {
    if (!$this->driver->table_exists(self::HISTORY_TABLE)) return [];
    $rows = $this->driver->executeRaw('SELECT slug, ts, checksum, status, batch, executed_at, error FROM '. self::HISTORY_TABLE .' ORDER BY ts, slug', []);
    $h = [];
    foreach ($rows as $r) $h[$r['slug']] = $r;
    return $h;
  }

  private function record($slug, $ts, $checksum, $status, $batch, $error = null)
  {
    $this->ensure_history();
    $t = self::HISTORY_TABLE;
    $now = date('Y-m-d H:i:s');
    $exists = $this->driver->executeRaw("SELECT slug FROM $t WHERE slug = ?", [$slug]);
    if (count($exists) > 0)
    {
      $this->driver->executeRaw("UPDATE $t SET ts = ?, checksum = ?, status = ?, batch = ?, executed_at = ?, error = ? WHERE slug = ?",
                                [$ts, $checksum, $status, $batch, $now, $error, $slug]);
    }
    else
    {
      $this->driver->executeRaw("INSERT INTO $t (slug, ts, checksum, status, batch, executed_at, error) VALUES (?, ?, ?, ?, ?, ?, ?)",
                                [$slug, $ts, $checksum, $status, $batch, $now, $error]);
    }
  }

  private function next_batch()
  {
    $this->ensure_history();
    $r = $this->driver->executeRaw('SELECT MAX(batch) AS b FROM '. self::HISTORY_TABLE, []);
    return ((int)($r[0]['b'] ?? 0)) + 1;
  }

  /** ******************************
   * Files
   */

  /**
   * Returns the migration files sorted by timestamp then slug: [['ts', 'slug', 'file', 'path', 'checksum', 'class'], ...]
   */
  public function discover()
  {
    $found = [];
    $slugs = [];

    foreach (glob($this->path .'/*.php') ?: [] as $file)
    {
      $name = basename($file);
      if (!preg_match(self::FILE_PATTERN, $name, $m))
      {
        throw new PhMigrationException("Invalid migration file name '$name', expected <YYYYMMDDHHMMSS>_<slug>.php with a snake_case slug");
      }

      if (isset($slugs[$m[2]]))
      {
        throw new PhMigrationException("Duplicated migration slug '{$m[2]}' in '{$slugs[$m[2]]}' and '$name'");
      }
      $slugs[$m[2]] = $name;

      $found[] = [
        'ts'       => $m[1],
        'slug'     => $m[2],
        'file'     => $name,
        'path'     => $file,
        'checksum' => hash_file('sha256', $file),
        'class'    => self::class_name($m[2])
      ];
    }

    usort($found, function($a, $b) { return [$a['ts'], $a['slug']] <=> [$b['ts'], $b['slug']]; });
    return $found;
  }

  public static function class_name($slug)
  {
    return str_replace(' ', '', ucwords(str_replace('_', ' ', $slug)));
  }

  /** ******************************
   * Status
   */

  /**
   * Returns one entry per migration (files and history) with a 'state':
   * applied, pending, out_of_order (pending but older than the latest applied), failed, running,
   * modified (applied but the file changed), missing (applied but the file is gone).
   */
  public function status()
  {
    $history = $this->history();
    $files   = $this->discover();

    $latest_applied = '';
    foreach ($history as $h)
    {
      if ($h['status'] == self::OK && $h['ts'] > $latest_applied) $latest_applied = $h['ts'];
    }

    $result = [];
    $seen = [];
    foreach ($files as $f)
    {
      $seen[$f['slug']] = true;
      $h = $history[$f['slug']] ?? null;

      if (is_null($h))
        $state = ($f['ts'] < $latest_applied) ? 'out_of_order' : 'pending';
      else if ($h['status'] == self::FAILED)
        $state = 'failed';
      else if ($h['status'] == self::RUNNING)
        $state = 'running';
      else if ($h['checksum'] !== $f['checksum'])
        $state = 'modified';
      else
        $state = 'applied';

      $result[] = $f + ['state' => $state, 'error' => $h['error'] ?? null];
    }

    foreach ($history as $slug => $h)
    {
      if (isset($seen[$slug])) continue;
      $result[] = ['ts' => $h['ts'], 'slug' => $slug, 'file' => null, 'path' => null, 'checksum' => $h['checksum'],
                   'class' => self::class_name($slug), 'state' => 'missing', 'error' => null];
    }

    return $result;
  }

  /** ******************************
   * Run
   */

  /**
   * Runs the pending migrations in timestamp order. Returns the slugs that were run.
   *
   * @param boolean $allow_out_of_order run pending migrations older than the latest applied one (warns)
   */
  public function migrate($allow_out_of_order = false)
  {
    // advisory locks are server wide, so the name includes the database to not block other databases
    $lock = self::LOCK_PREFIX . substr(md5($this->driver->current_database()), 0, 20);

    if (!$this->driver->acquire_lock($lock, 0))
    {
      throw new PhMigrationException('Another migration run is in progress');
    }

    try
    {
      $this->ensure_history();
      return $this->run_pending($allow_out_of_order);
    }
    finally
    {
      $this->driver->release_lock($lock);
    }
  }

  /**
   * Validates that migrating is possible and returns the pending migrations in order, without changing anything.
   * Throws if anything unresolved (failed, interrupted, modified, missing, out-of-order) is found.
   */
  public function check_pending($allow_out_of_order = false)
  {
    $status = $this->status();

    // refuse to continue over anything unresolved
    foreach ($status as $s)
    {
      switch ($s['state'])
      {
        case 'failed':
        case 'running':
          throw new PhMigrationException("Migration '{$s['slug']}' is {$s['state']}". ($s['error'] ? " ({$s['error']})" : '') .". Its schema changes may remain in the database. Inspect the database, leave it in a state where the migration can run, fix the migration if needed, then run 'clear-failed {$s['slug']}' before migrating again.");
        case 'modified':
          throw new PhMigrationException("Applied migration '{$s['slug']}' was modified (checksum mismatch). Never edit an applied migration; write a new one.");
        case 'missing':
          throw new PhMigrationException("Applied migration '{$s['slug']}' has no file in '{$this->path}'");
      }
    }

    $out_of_order = array_values(array_filter($status, function($s) { return $s['state'] == 'out_of_order'; }));
    if (count($out_of_order) > 0)
    {
      $latest = '';
      foreach ($status as $s) if ($s['state'] == 'applied' && $s['ts'] > $latest) $latest = $s['ts'];

      foreach ($out_of_order as $o)
      {
        $msg = "Out-of-order migration: {$o['ts']}_{$o['slug']} is pending but a migration with timestamp $latest already ran.";
        if (!$allow_out_of_order)
        {
          throw new PhMigrationException($msg ." Run: vendor/bin/phersistence rebase {$o['slug']}");
        }
        $this->log("WARNING: $msg Running anyway (--allow-out-of-order).");
      }
    }

    return array_values(array_filter($status, function($s) { return $s['state'] == 'pending' || $s['state'] == 'out_of_order'; }));
  }

  private function run_pending($allow_out_of_order)
  {
    $pending = $this->check_pending($allow_out_of_order);
    if (count($pending) == 0) return [];

    $batch = $this->next_batch();
    $ran = [];
    foreach ($pending as $m)
    {
      $this->run_one($m, $batch);
      $ran[] = $m['slug'];
    }
    return $ran;
  }

  private function run_one($m, $batch)
  {
    $this->log("Migrating {$m['slug']}");

    require_once $m['path'];
    $class = $m['class'];
    if (!class_exists($class, false) || !is_subclass_of($class, PhMigration::class))
    {
      throw new PhMigrationException("File '{$m['file']}' must define class '$class' extending ". PhMigration::class);
    }

    // history row first, so a crash leaves a visible 'running' record
    $this->record($m['slug'], $m['ts'], $m['checksum'], self::RUNNING, $batch);

    $migration = new $class($this->driver);
    $in_transaction = false;
    $phase = 'setup';

    try
    {
      $migration->setup();

      $phase = 'schema';
      $migration->schema();

      $phase = 'data';
      $this->driver->transaction_start();
      $in_transaction = true;
      $migration->data();
      $this->driver->transaction_commit();
      $in_transaction = false;

      $phase = 'cleanup';
      $migration->cleanup();
    }
    catch (\Throwable $e)
    {
      if ($in_transaction) $this->driver->transaction_rollback();

      $this->record($m['slug'], $m['ts'], $m['checksum'], self::FAILED, $batch, "phase $phase: ". $e->getMessage());
      throw new PhMigrationException(self::failure_report($m['slug'], $phase, $e->getMessage()), 0, $e);
    }

    $this->record($m['slug'], $m['ts'], $m['checksum'], self::OK, $batch);
  }

  /**
   * Explains what state a failed migration left the database in and what to do next.
   * Database DDL is not transactional, so schema changes made before the error are kept.
   */
  public static function failure_report($slug, $phase, $error)
  {
    $state = [
      'setup'   => ['setup() changes made before the error were NOT rolled back (they may be partially applied).',
                    'schema(), data() and cleanup() did not run.'],
      'schema'  => ['setup() completed and its changes remain.',
                    'schema() changes made before the error were NOT rolled back (they may be partially applied).',
                    'data() and cleanup() did not run.'],
      'data'    => ['setup() and schema() completed and their changes remain (database DDL is not transactional).',
                    'data() changes were rolled back.',
                    'cleanup() did not run.'],
      'cleanup' => ['setup(), schema() and data() completed and their changes remain.',
                    'cleanup() changes made before the error were NOT rolled back (they may be partially applied).']
    ][$phase];

    $lines = ["Migration '$slug' failed in phase $phase: $error", '', 'State of the database:'];
    foreach ($state as $l) $lines[] = '  - '. $l;

    array_push($lines, '',
      'What to do next:',
      "  1. Run 'status' and inspect the database to see which schema changes were applied.",
      '  2. Leave the database in a state where the migration can run again: undo the schema changes it made by hand,',
      '     or keep them and adjust the migration so it does not repeat them.',
      "  3. Fix the cause of the error in the migration file (it never completed, so editing it is allowed).",
      "  4. Run 'clear-failed $slug' to remove the failed record, then run 'migrate' again.",
      "Migrations after '$slug' were not run."
    );
    return implode("\n", $lines);
  }

  /**
   * Removes the failed/running record of a migration so it can run again.
   * The developer must first leave the database in a state where the migration can run (schema changes are not rolled back).
   */
  public function clear_failed($slug)
  {
    $h = $this->history();
    if (!isset($h[$slug]) || $h[$slug]['status'] == self::OK)
    {
      throw new PhMigrationException("Migration '$slug' is not failed");
    }
    $this->driver->executeRaw('DELETE FROM '. self::HISTORY_TABLE .' WHERE slug = ?', [$slug]);
  }

  /**
   * Marks a migration as applied without running it, for databases that already have its changes.
   */
  public function baseline($slug)
  {
    $h = $this->history();
    if (isset($h[$slug])) throw new PhMigrationException("Migration '$slug' is already in the history");

    foreach ($this->discover() as $f)
    {
      if ($f['slug'] == $slug)
      {
        $this->record($slug, $f['ts'], $f['checksum'], self::OK, $this->next_batch());
        return;
      }
    }
    throw new PhMigrationException("No migration file for slug '$slug'");
  }

  /** ******************************
   * File management
   */

  /**
   * Creates a migration file from the template. Returns the file path.
   */
  public function make($slug)
  {
    if (!preg_match('/^[a-z][a-z0-9_]*$/', $slug))
    {
      throw new PhMigrationException("Invalid slug '$slug', use snake_case (letters, digits, underscores)");
    }

    foreach ($this->discover() as $f)
    {
      if ($f['slug'] == $slug) throw new PhMigrationException("A migration with slug '$slug' already exists ({$f['file']})");
    }

    if (!is_dir($this->path) && !mkdir($this->path, 0775, true))
    {
      throw new PhMigrationException("Can't create folder '{$this->path}'");
    }

    $file = $this->path .'/'. $this->next_timestamp() .'_'. $slug .'.php';
    file_put_contents($file, str_replace('{{class}}', self::class_name($slug), self::TEMPLATE));
    return $file;
  }

  /**
   * Gives a pending migration a fresh timestamp. Only the file name changes. Returns the new file path.
   */
  public function rebase($slug)
  {
    $history = $this->history();
    if (isset($history[$slug]))
    {
      throw new PhMigrationException("Migration '$slug' is already in the history, only pending migrations can be rebased");
    }

    foreach ($this->discover() as $f)
    {
      if ($f['slug'] != $slug) continue;

      $new = $this->path .'/'. $this->next_timestamp() .'_'. $slug .'.php';
      rename($f['path'], $new);
      $this->log("Renamed {$f['file']} to ". basename($new) .". Review the migrations that now run before it for overlapping changes.");
      return $new;
    }
    throw new PhMigrationException("No migration file for slug '$slug'");
  }

  /**
   * Current time, bumped to be greater than every existing file and applied timestamp.
   */
  private function next_timestamp()
  {
    $max = date('YmdHis');
    $latest = '';
    foreach ($this->discover() as $f) if ($f['ts'] > $latest) $latest = $f['ts'];
    foreach ($this->history() as $h)  if ($h['ts'] > $latest) $latest = $h['ts'];

    if ($latest >= $max)
    {
      $max = date('YmdHis', strtotime($latest) + 1);
    }
    return $max;
  }

  private function log($msg)
  {
    if (is_callable($this->logger)) call_user_func($this->logger, $msg);
  }

  const TEMPLATE = <<<'TPL'
<?php

use CaboLabs\Phersistence\migrations\PhMigration;

/**
 * Rules (full version in docs/MIGRATIONS_AI_RULES.md):
 * - Implement only the phases you need. Order: setup, schema, data (transactional), cleanup.
 * - Use the driver-neutral API ($this->driver) with portable types: int, long, float, double, decimal,
 *   boolean, date, time, datetime, text, string(N). No raw SQL unless there is no other way.
 * - Forward-only: there is no down(). Fix mistakes with a new migration.
 * - Never edit or rename this file after it has been applied.
 * - One concern per migration. Ask the developer before deleting data.
 */
class {{class}} extends PhMigration
{
   // public function setup() {}

   // public function schema() {}

   // public function data() {}

   // public function cleanup() {}
}
TPL;
}

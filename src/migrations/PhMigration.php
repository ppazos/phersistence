<?php

namespace CaboLabs\Phersistence\migrations;

use CaboLabs\Phersistence\drivers\Driver;

/**
 * Base class for migrations. Implement only the phases you need; they always run in this order:
 * setup(), schema(), data() (inside a transaction), cleanup().
 *
 * Migrations are forward-only. See docs/MIGRATIONS_AI_RULES.md.
 */
abstract class PhMigration {

  protected $driver;

  public function __construct(Driver $driver)
  {
    $this->driver = $driver;
  }

  /** Create intermediate or temporary structures. */
  public function setup() {}

  /** Create or alter tables, columns, indexes and constraints. */
  public function schema() {}

  /** Seed, move, change or delete data. Runs inside a transaction. */
  public function data() {}

  /** Drop intermediate structures and legacy columns or tables. */
  public function cleanup() {}
}

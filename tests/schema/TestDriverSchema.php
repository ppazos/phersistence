<?php

namespace tests\schema;

use CaboLabs\Debbie\DebbieTestCase;

/**
 * Tests the driver-neutral schema operations used by migrations.
 */
class TestDriverSchema extends DebbieTestCase {

  private function fails(callable $fn)
  {
    try { $fn(); } catch (\Exception $e) { return true; }
    return false;
  }

  private function cleanup($d)
  {
    if ($d->table_exists('mig_b')) $d->drop_table('mig_b');
    if ($d->table_exists('mig_a')) $d->drop_table('mig_a');
    if ($d->table_exists('mig_renamed')) $d->drop_table('mig_renamed');
  }

  // portable types, add/modify/rename/drop column
  public function test_1()
  {
    global $d;
    $this->cleanup($d);

    $d->create_table('mig_a');
    $d->add_column('mig_a', 'name', 'string(50)', false);
    $d->add_column('mig_a', 'age', 'int', true);
    $d->add_column('mig_a', 'active', 'boolean', false, 1);
    $this->assert($d->column_exists('mig_a', 'name'), 'column added');

    $this->assert($this->fails(function() use ($d) { $d->add_column('mig_a', 'name', 'text', true); }), 'add existing column fails');

    $d->modify_column('mig_a', 'name', 'string(100)', true);
    $r = $d->executeRaw("SELECT CHARACTER_MAXIMUM_LENGTH AS l FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?", ['mig_a', 'name']);
    $this->assert((int)$r[0]['l'] === 100, 'column modified');

    $d->rename_column('mig_a', 'age', 'years');
    $this->assert(!$d->column_exists('mig_a', 'age') && $d->column_exists('mig_a', 'years'), 'column renamed');

    $d->drop_column('mig_a', 'years');
    $this->assert(!$d->column_exists('mig_a', 'years'), 'column dropped');
    $this->assert($this->fails(function() use ($d) { $d->drop_column('mig_a', 'years'); }), 'drop missing column fails');

    $this->cleanup($d);
  }

  // indexes, unique, fk
  public function test_2()
  {
    global $d;
    $this->cleanup($d);

    $d->create_table('mig_a');
    $d->add_column('mig_a', 'code', 'string(10)', false);
    $d->add_index('mig_a', ['code'], 'mig_a_code_idx');
    $d->add_unique('mig_a', ['code'], 'mig_a_code_uq');
    $this->assert($d->index_exists('mig_a', 'mig_a_code_idx'), 'index added');

    $d->drop_index('mig_a', 'mig_a_code_idx');
    $d->drop_index('mig_a', 'mig_a_code_uq');
    $this->assert(!$d->index_exists('mig_a', 'mig_a_code_idx'), 'index dropped');
    $this->assert($this->fails(function() use ($d) { $d->drop_index('mig_a', 'mig_a_code_idx'); }), 'drop missing index fails');

    $d->create_table('mig_b');
    $d->add_column('mig_b', 'a_id', 'int', true);
    $d->add_fk('mig_b', 'a_id', 'fk_mig_b_a', 'mig_a', 'id');
    $this->assert($d->fk_exists('mig_b', 'fk_mig_b_a'), 'fk added');
    $d->drop_fk('mig_b', 'fk_mig_b_a');
    $this->assert(!$d->fk_exists('mig_b', 'fk_mig_b_a'), 'fk dropped');

    $this->cleanup($d);
  }

  // tables and locks
  public function test_3()
  {
    global $d;
    $this->cleanup($d);

    $d->create_table('mig_a');
    $d->rename_table('mig_a', 'mig_renamed');
    $this->assert(!$d->table_exists('mig_a') && $d->table_exists('mig_renamed'), 'table renamed');
    $d->drop_table('mig_renamed');
    $this->assert(!$d->table_exists('mig_renamed'), 'table dropped');
    $this->assert($this->fails(function() use ($d) { $d->drop_table('mig_renamed'); }), 'drop missing table fails');

    $this->assert($d->acquire_lock('ph_test_lock', 0), 'lock acquired');
    $this->assert($d->release_lock('ph_test_lock'), 'lock released');
  }
}

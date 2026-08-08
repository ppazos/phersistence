<?php

namespace tests\query;

use CaboLabs\Debbie\DebbieTestCase;

/**
 * Tests for executeRaw() — secure parameterized SQL execution.
 */
class TestExecuteRaw extends DebbieTestCase {

  // SELECT with no params returns empty array when table is empty
  public function test_select_no_params()
  {
    global $Payor;

    $rows = $Payor->executeRaw("SELECT * FROM payor");

    $this->assert(is_array($rows), 'Returns array');
    $this->assert(count($rows) === 0, 'Empty table returns empty array');
  }

  // SELECT with string param returns matching row
  public function test_select_string_param()
  {
    global $Payor;

    $p = $Payor->create(['company' => 'Acme Corp', 'ein' => '12-3456789']);
    $p->save();

    $rows = $Payor->executeRaw("SELECT * FROM payor WHERE company = ?", ['Acme Corp']);

    $this->assert(count($rows) === 1, 'Finds row by string param');
    $this->assert($rows[0]['company'] === 'Acme Corp', 'Correct company returned');
  }

  // SELECT with int param (id) returns matching row
  public function test_select_int_param()
  {
    global $Payor;

    $p = $Payor->create(['company' => 'Beta Inc', 'ein' => '98-7654321']);
    $p->save();

    $rows = $Payor->executeRaw("SELECT * FROM payor WHERE id = ?", [$p->id]);

    $this->assert(count($rows) === 1, 'Finds row by int param');
    $this->assert((int)$rows[0]['id'] === $p->id, 'Correct id returned');
  }

  // SELECT with multiple params returns matching row
  public function test_select_multiple_params()
  {
    global $Payor;

    $p = $Payor->create(['company' => 'Gamma LLC', 'ein' => '55-5555555']);
    $p->save();

    $rows = $Payor->executeRaw(
      "SELECT * FROM payor WHERE company = ? AND ein = ?",
      ['Gamma LLC', '55-5555555']
    );

    $this->assert(count($rows) === 1, 'Finds row with multiple params');
    $this->assert($rows[0]['ein'] === '55-5555555', 'Correct ein returned');
  }

  // String that looks like SQL injection is treated as literal value
  public function test_sql_injection_prevented()
  {
    global $Payor;

    $p = $Payor->create(['company' => 'Safe Co', 'ein' => '11-1111111']);
    $p->save();

    $malicious = "' OR '1'='1";
    $rows = $Payor->executeRaw("SELECT * FROM payor WHERE company = ?", [$malicious]);

    $this->assert(count($rows) === 0, 'Injection attempt returns no rows');
  }

  // DML (UPDATE) returns int affected rows
  public function test_dml_returns_affected_rows()
  {
    global $Payor;

    $p = $Payor->create(['company' => 'Delta Ltd', 'ein' => '44-4444444']);
    $p->save();

    $affected = $Payor->executeRaw(
      "UPDATE payor SET company = ? WHERE id = ?",
      ['Delta Ltd Updated', $p->id]
    );

    $this->assert(is_int($affected), 'DML returns int');
    $this->assert($affected === 1, 'One row affected');

    $rows = $Payor->executeRaw("SELECT * FROM payor WHERE id = ?", [$p->id]);
    $this->assert($rows[0]['company'] === 'Delta Ltd Updated', 'Update persisted');
  }

  // No-params fallback behaves identically to runRaw
  public function test_no_params_fallback_matches_run_raw()
  {
    global $Payor;

    $p = $Payor->create(['company' => 'Echo Corp', 'ein' => '33-3333333']);
    $p->save();

    $via_run_raw    = $Payor->runRaw("SELECT * FROM payor");
    $via_execute_raw = $Payor->executeRaw("SELECT * FROM payor");

    $this->assert(count($via_run_raw) === count($via_execute_raw), 'Same row count');
    $this->assert($via_run_raw[0]['company'] === $via_execute_raw[0]['company'], 'Same data');
  }
}

?>

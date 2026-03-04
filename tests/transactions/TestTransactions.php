<?php

namespace tests\transactions;

use CaboLabs\Debbie\DebbieTestCase;

/**
 * Tests for transaction support: beginTransaction/commitTransaction/rollbackTransaction
 * and the closure-based transaction() method.
 */
class TestTransactions extends DebbieTestCase {

  // Closure-based transaction commits both saves on success
  public function test_1()
  {
    global $Payor;

    $Payor->transaction(function() use ($Payor) {
      $p1 = $Payor->create(['company' => 'Company A', 'ein' => '1111']);
      $p1->save();

      $p2 = $Payor->create(['company' => 'Company B', 'ein' => '2222']);
      $p2->save();
    });

    $this->assert($Payor->count() === 2, 'Both payors committed');
  }

  // Closure-based transaction rolls back all saves when exception is thrown
  public function test_2()
  {
    global $Payor;

    $exception_caught = false;

    try
    {
      $Payor->transaction(function() use ($Payor) {
        $p = $Payor->create(['company' => 'Company A', 'ein' => '1111']);
        $p->save();

        throw new \Exception('Simulated failure');

        $p2 = $Payor->create(['company' => 'Company B', 'ein' => '2222']);
        $p2->save();
      });
    }
    catch (\Exception $e)
    {
      $exception_caught = true;
    }

    $this->assert($exception_caught, 'Exception was re-thrown after rollback');
    $this->assert($Payor->count() === 0, 'Rollback: no payors persisted');
  }

  // Manual beginTransaction + commitTransaction persists changes
  public function test_3()
  {
    global $Payor;

    $Payor->beginTransaction();

    $p = $Payor->create(['company' => 'Company A', 'ein' => '1111']);
    $p->save();

    $Payor->commitTransaction();

    $this->assert($Payor->count() === 1, 'Payor committed');
    $fetched = $Payor->get($p->id);
    $this->assert($fetched->company === 'Company A', 'Correct data persisted');
  }

  // Manual beginTransaction + rollbackTransaction discards changes
  public function test_4()
  {
    global $Payor;

    $Payor->beginTransaction();

    $p = $Payor->create(['company' => 'Company A', 'ein' => '1111']);
    $p->save();

    $Payor->rollbackTransaction();

    $this->assert($Payor->count() === 0, 'Rollback: payor not persisted');
  }

  // Transaction started on one model covers operations on a different model class
  public function test_5()
  {
    global $Payor, $Person;

    $Payor->beginTransaction();

    $p = $Payor->create(['company' => 'Company A', 'ein' => '1111']);
    $p->save();

    $person = $Person->create(['firstname' => 'Test', 'lastname' => 'User']);
    $person->save();

    $Payor->rollbackTransaction();

    $this->assert($Payor->count() === 0, 'Rollback: payor not persisted');
    $this->assert($Person->count() === 0, 'Rollback covers cross-model operations');
  }
}

?>

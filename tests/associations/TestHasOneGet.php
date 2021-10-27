<?php

namespace tests\associations;

use \phtest\PhTestCase;
use \basic\BasicDateTime as d;

/**
 * The goal of these tests is to verify the functionality of creating and saving
 * two objects associated with a hasone, and check the attribute values after a
 * get is executed.
 */
class TestHasOneGet extends PhTestCase {

  public function test_1()
  {
    global $Employer;

    $out = SetupTestData::hasone_save_cascade_1();
    $e = $Employer->get($out['e']->id);


    // tests
    $this->assert($e->id === $out['e']->id, 'Consistent id in get');

    $this->assert($e->payor_id === $out['e']->payor->id, 'Consistent FK id in get');

    $this->assert($e->payor === $Employer::NOT_LOADED_ASSOC, 'Has one object is not loaded');
  }


  public function test_2()
  {
    global $Employer;

    // setup
    $out = SetupTestData::hasone_save_cascade_2();
    $e = $Employer->get($out['e']->id);


    // tests
    $this->assert($e->id === $out['e']->id, 'Consistent id in get');

    $this->assert($e->payor_id === $out['e']->payor->id, 'Consistent FK id in get');

    $this->assert($e->payor === $Employer::NOT_LOADED_ASSOC, 'Has one object is not loaded');
  }


  public function test_3()
  {
    global $Employer;

    // setup
    $out = SetupTestData::hasone_save_cascade_3();
    $e = $Employer->get($out['e']->id);


    // tests
    $this->assert($e->id === $out['e']->id, 'Consistent id in get');

    $this->assert($e->payor_id === $out['e']->payor->id, 'Consistent FK id in get');

    $this->assert($e->payor === $Employer::NOT_LOADED_ASSOC, 'Has one object is not loaded');
  }


  public function test_4()
  {
    global $Employer;

    // setup
    $out = SetupTestData::hasone_save_cascade_4();
    $e = $Employer->get($out['e']->id);


    // tests
    $this->assert($e->id === $out['e']->id, 'Consistent id in get');

    $this->assert($e->payor_id === $out['e']->payor->id, 'Consistent FK id in get');

    $this->assert($e->payor === $Employer::NOT_LOADED_ASSOC, 'Has one object is not loaded');
  }


  public function test_5()
  {
    global $Employer;

    // setup
    $out = SetupTestData::hasone_save_cascade_5();
    $e = $Employer->get($out['e']->id);

    // tests
    $this->assert($e->id === $out['e']->id, 'Consistent id in get');

    $this->assert($e->payor_id === NULL, 'Has one FK id in get is NULL');

    $this->assert($e->payor === NULL, 'Has one object is NULL');
  }
}

?>
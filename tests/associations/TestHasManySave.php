<?php

namespace tests\associations;

use CaboLabs\PhTest\PhTestCase;

/**
 * The goal of these tests is to verify the functionality of creating and saving
 * two objects associated with a hasmany, and check the attribute values after a
 * save.
 */
class TestHasManySave extends PhTestCase {

  public function test_1()
  {
    $out = SetupTestData::hasmany_save_1();


    // tests
    $this->assert($out['result'] !== NULL, 'Save successful');

    $this->assert($out['person']->id === $out['result'], 'Saved object id consistency');

    $this->assert($out['person']->firstname === 'Test', 'Person name correct');

    $this->assert($out['person']->size_addresses() === 3, 'Has many has 3 objects');

    foreach ($out['person']->get_addresses() as $i => $address)
    {
      $this->assert($address->zipcode === str_repeat(($i+1).'', 5), 'Address zipcode correct');
    }
  }


  public function test_2()
  {
    // setup
    $out = SetupTestData::hasmany_save_2();


    // tests
    $this->assert($out['result'] !== NULL, 'Save successful');

    $this->assert($out['person']->id === $out['result'], 'Saved object id consistency');

    $this->assert($out['person']->firstname === 'Test', 'Person name correct');

    $this->assert($out['person']->size_addresses() === 3, 'Has many has 3 objects');

    foreach ($out['person']->get_addresses() as $i => $address)
    {
      $this->assert($address->zipcode === str_repeat(($i+1).'', 5), 'Address zipcode correct');
    }
  }


  public function test_3()
  {
    // setup
    $out = SetupTestData::hasmany_save_3();


    // tests
    $this->assert($out['result'] !== NULL, 'Save successful');

    $this->assert($out['person']->id === $out['result'], 'Saved object id consistency');

    $this->assert($out['person']->firstname === 'Test', 'Person name correct');

    $this->assert($out['person']->size_addresses() === 3, 'Has many has 3 objects');

    foreach ($out['person']->get_addresses() as $i => $address)
    {
      $this->assert($address->zipcode === str_repeat(($i+1).'', 5), 'Address zipcode correct');
    }
  }


  public function test_4()
  {
    // setup
    $out = SetupTestData::hasmany_save_4();


    // tests
    $this->assert($out['result'] !== NULL, 'Save successful');

    $this->assert($out['person']->id === $out['result'], 'Saved object id consistency');

    $this->assert($out['person']->firstname === 'Test', 'Person name correct');

    // should always use size_addresses() because when the collection is not loaded,
    // we can't do ->addresses->size() because ->addresses is NOT_LOADED_ASSOC
    $this->assert($out['person']->size_addresses() === 0, 'Has many has 0 objects');
  }
}

?>
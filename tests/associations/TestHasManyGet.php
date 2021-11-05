<?php

namespace tests\associations;

use CaboLabs\PhTest\PhTestCase;

/**
 * The goal of these tests is to verify the functionality of creating and saving
 * two objects associated with a hasmany, and check the attribute values after a
 * get is executed.
 */
class TestHasManyGet extends PhTestCase {

  // there is an issue the first test doesn't have a log
  public function test_dummy()
  {

  }

  // save 1 + get
  public function test_1_1()
  {
    global $Person;

    $out = SetupTestData::hasmany_save_1();

    $person = $Person->get($out['person']->id);


    // tests
    $this->assert($person !== NULL, 'Container object loaded OK');

    $this->assert($out['person']->id === $person->id, 'Container object id consistency');

    $this->assert($person->addresses === $Person::NOT_LOADED_ASSOC, 'Has many not loaded');

    // loads addresses
    $addresses = $person->get_addresses();

    $this->assert($addresses->size() === 3, 'Has many has 3 objects');

    foreach ($addresses as $i => $address)
    {
      $this->assert($address->zipcode === str_repeat(($i+1).'', 5), 'Address zipcode correct');
    }
  }


  // save 2 + get
  public function test_2_1()
  {
    global $Person;

    // setup
    $out = SetupTestData::hasmany_save_2();

    $person = $Person->get($out['person']->id);


    // tests
    $this->assert($person !== NULL, 'Container object loaded OK');

    $this->assert($out['person']->id === $person->id, 'Container object id consistency');

    $this->assert($person->addresses === $Person::NOT_LOADED_ASSOC, 'Has many not loaded');

    // loads addresses
    $addresses = $person->get_addresses();

    $this->assert($addresses->size() === 3, 'Has many has 3 objects');

    foreach ($addresses as $i => $address)
    {
      $this->assert($address->zipcode === str_repeat(($i+1).'', 5), 'Address zipcode correct');
    }
  }

  // save 3 + get
  public function test_3_1()
  {
    global $Person;

    // setup
    $out = SetupTestData::hasmany_save_3();

    $person = $Person->get($out['person']->id);


    // tests
    $this->assert($person !== NULL, 'Container object loaded OK');

    $this->assert($out['person']->id === $person->id, 'Container object id consistency');

    $this->assert($person->addresses === $Person::NOT_LOADED_ASSOC, 'Has many not loaded');

    // loads addresses
    $addresses = $person->get_addresses();

    $this->assert($addresses->size() === 3, 'Has many has 3 objects');

    foreach ($addresses as $i => $address)
    {
      $this->assert($address->zipcode === str_repeat(($i+1).'', 5), 'Address zipcode correct');
    }
  }


  // save 4 + get
  public function test_4_1()
  {
    global $Person;

    // setup
    $out = SetupTestData::hasmany_save_4();

    $person = $Person->get($out['person']->id);


    // tests
    $this->assert($person !== NULL, 'Container object loaded OK');

    $this->assert($out['person']->id === $person->id, 'Container object id consistency');

    $this->assert($person->addresses === $Person::NOT_LOADED_ASSOC, 'Has many not loaded');

    // loads addresses
    $addresses = $person->get_addresses();

    $this->assert($addresses->size() === 0, 'Has many has 0 objects');
  }
}

?>
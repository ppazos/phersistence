<?php

namespace tests\associations;

use CaboLabs\Debbie\DebbieTestCase;
use tests\associations\SetupTestData;

/**
 * The goal of these tests is to verify the functionality of creating and saving
 * two objects associated with a hasmany, and check the attribute values after an
 * update.
 */
class TestHasManyUpdate extends DebbieTestCase {

  // FIXME: there is a problem in the first test the log is not printed
  public function test_empty()
  {

  }

  // save 1 + add to
  public function test_1_1()
  {
    global $Address;

    $out = SetupTestData::hasmany_save_1();

    $address = $Address->create([
      'line1'   => 'line1_4',
      'zipcode' => '44444',
      'state'   => 'MN'
    ]);

    $out['person']->add_to_addresses($address);

    $out['person']->save();


    // tests
    $this->assert($address->id !== NULL, 'New object id is set');

    $this->assert($out['person']->size_addresses() === 4, 'Has many has 4 objects');

    foreach ($out['person']->get_addresses() as $i => $address)
    {
      $this->assert($address->zipcode === str_repeat(($i+1).'', 5), 'Address zipcode correct');
    }
  }

  // save 1 + remove from
  public function test_1_2()
  {
    global $Address;

    $out = SetupTestData::hasmany_save_1();

    $address = $out['person']->get_addresses()[1];

    $is_removed = $out['person']->remove_from_addresses($address);

    $out['person']->save();


    // tests
    $this->assert($is_removed, 'Removed OK');

    $this->assert($out['person']->size_addresses() === 2, 'Has many has 2 objects');

    $this->assert($address->person_addresses_back === NULL, 'Backlink of removed object is NULL');
  }

  // save 1 + clean
  public function test_1_3()
  {
    $out = SetupTestData::hasmany_save_1();

    // get all items to avoid losing them when the clean() is executed over the collection
    $addresses = $out['person']->get_addresses()->all();

    $out['person']->clean_addresses();

    $out['person']->save();


    // tests
    $this->assert($out['person']->size_addresses() === 0, 'Has many has 0 objects');

    foreach ($addresses as $i => $address)
    {
      $this->assert($address->person_addresses_back === NULL, "Backlink of item $i object is NULL");
    }
  }


  // save 2 + add to
  public function test_2_1()
  {
    global $Address;

    // setup
    $out = SetupTestData::hasmany_save_2();

    $address = $Address->create([
      'line1'   => 'line1_4',
      'zipcode' => '44444',
      'state'   => 'MN'
    ]);

    $out['person']->add_to_addresses($address);

    $out['person']->save();


    // tests
    $this->assert($address->id !== NULL, 'New object id is set');

    $this->assert($out['person']->size_addresses() === 4, 'Has many has 4 objects');

    foreach ($out['person']->get_addresses() as $i => $address)
    {
      $this->assert($address->zipcode === str_repeat(($i+1).'', 5), 'Address zipcode correct');
    }
  }

  // save 2 + remove from
  public function test_2_2()
  {
    global $Address;

    // setup
    $out = SetupTestData::hasmany_save_2();

    $address = $out['person']->get_addresses()[1];

    $is_removed = $out['person']->remove_from_addresses($address);

    $out['person']->save();


    // tests
    $this->assert($is_removed, 'Removed OK');

    $this->assert($out['person']->size_addresses() === 2, 'Has many has 2 objects');

    $this->assert($address->person_addresses_back === NULL, 'Backlink of removed object is NULL');
  }

  // save 2 + clean
  public function test_2_3()
  {
    $out = SetupTestData::hasmany_save_2();

    // get all items to avoid losing them when the clean() is executed over the collection
    $addresses = $out['person']->get_addresses()->all();

    $out['person']->clean_addresses();

    $out['person']->save();


    // tests
    $this->assert($out['person']->size_addresses() === 0, 'Has many has 0 objects');

    foreach ($addresses as $i => $address)
    {
      $this->assert($address->person_addresses_back === NULL, "Backlink of item $i object is NULL");
    }
  }


  // save 3 + add to
  public function test_3_1()
  {
    global $Address;

    // setup
    $out = SetupTestData::hasmany_save_3();

    $address = $Address->create([
      'line1'   => 'line1_4',
      'zipcode' => '44444',
      'state'   => 'MN'
    ]);

    $out['person']->add_to_addresses($address);

    $out['person']->save();


    // tests
    $this->assert($address->id !== NULL, 'New object id is set');

    $this->assert($out['person']->size_addresses() === 4, 'Has many has 4 objects');

    foreach ($out['person']->get_addresses() as $i => $address)
    {
      $this->assert($address->zipcode === str_repeat(($i+1).'', 5), 'Address zipcode correct');
    }
  }

  // save 3 + remove from
  public function test_3_2()
  {
    global $Address;

    // setup
    $out = SetupTestData::hasmany_save_3();

    $address = $out['person']->get_addresses()[1];

    $is_removed = $out['person']->remove_from_addresses($address);

    $out['person']->save();


    // tests
    $this->assert($is_removed, 'Removed OK');

    $this->assert($out['person']->size_addresses() === 2, 'Has many has 2 objects');

    $this->assert($address->person_addresses_back === NULL, 'Backlink of removed object is NULL');
  }

  // save 3 + clean
  public function test_3_3()
  {
    $out = SetupTestData::hasmany_save_3();

    // get all items to avoid losing them when the clean() is executed over the collection
    $addresses = $out['person']->get_addresses()->all();

    $out['person']->clean_addresses();

    $out['person']->save();


    // tests
    $this->assert($out['person']->size_addresses() === 0, 'Has many has 0 objects');

    foreach ($addresses as $i => $address)
    {
      $this->assert($address->person_addresses_back === NULL, "Backlink of item $i object is NULL");
    }
  }


  // save 4 + add to
  public function test_4_1()
  {
    global $Address;

    // setup
    $out = SetupTestData::hasmany_save_4();

    $address = $Address->create([
      'line1'   => 'line1_1',
      'zipcode' => '11111',
      'state'   => 'MN'
    ]);

    $out['person']->add_to_addresses($address);

    $out['person']->save();


    // tests
    $this->assert($out['result'] !== NULL, 'Save successful');

    $this->assert($address->id !== NULL, 'New object id is set');

    $this->assert($out['person']->size_addresses() === 1, 'Has many has 1 objects');

    foreach ($out['person']->get_addresses() as $i => $address)
    {
      $this->assert($address->zipcode === str_repeat(($i+1).'', 5), 'Address zipcode correct');
    }
  }

  // save 4 + remove from
  public function test_4_2()
  {
    global $Address;

    // setup
    $out = SetupTestData::hasmany_save_4();

    // The person doesn't have this address, that is why the remove gives false
    $address = $Address->create([
      'line1'   => 'line1_1',
      'zipcode' => '11111',
      'state'   => 'MN',
      'id'      => 666 // this is needed so the remove don't throw an exception
    ]);

    $is_removed = $out['person']->remove_from_addresses($address);

    $out['person']->save();


    // tests
    $this->assert(!$is_removed, 'Not removed');

    $this->assert($out['person']->size_addresses() === 0, 'Has many has 0 objects');
  }


  public function test_has_many_set_properties()
  {
    $out = SetupTestData::hasmany_save_1();

    $out['person']->setProperties(
      [
        'firstname' => 'Changed',
        'addresses' => [
          [
            'line1'   => 'aaaa',
            'zipcode' => 77777,
            'state'   => 'AK'
          ]
        ]
      ],
      ['addresses' => 'DO_NOT_UPDATE'] // It shouldn't update the addresses because of this flag
    );

    $out['person']->save();

    $this->assert($out['person']->size_addresses() === 3, 'Has many has 3 objects');

    // To avoid orphan addresses, get and delete before setProperties
    $orphan_addresses = $out['person']->get_addresses()->all();
    foreach ($orphan_addresses as $address)
    {
      $address->delete();
    }

    // This will empty the addresses and create a new one, but doesn't
    // delete the current addresses!
    $out['person']->setProperties(
      [
        'firstname' => 'Changed',
        'addresses' => [
          [
            'line1'   => 'aaaa',
            'zipcode' => 77777,
            'state'   => 'AK'
          ]
        ]
      ]
    );

    $out['person']->save();

    $this->assert($out['person']->size_addresses() === 1, 'Has many has 1 objects');
  }

   // save 4 + clean
   public function test_4_3()
   {
     $out = SetupTestData::hasmany_save_4();

     // get all items to avoid losing them when the clean() is executed over the collection
     $addresses = $out['person']->get_addresses()->all();

     $out['person']->clean_addresses(); // shouldn't turn on the is_dirty because there are no objects

     $out['person']->save(); // shouldn't update because is not dirty


     // tests
     $this->assert($out['person']->size_addresses() === 0, 'Has many has 0 objects');

     $this->assert(count($addresses) === 0, 'Has many has 0 objects');
   }
}

?>
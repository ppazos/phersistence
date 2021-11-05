<?php

namespace tests\associations;

use CaboLabs\PhTest\PhTestCase;

/**
 * The goal of these tests is to verify the functionality of updating
 * two objects associated with a hasone, and check the values of the hasone 
 * attribute and the hasone foreign key attribute.
 */
class TestHasOneUpdateCascade extends PhTestCase {

  // save has one 1 + update setting null has one
  public function test_1_1()
  {
    $out = SetupTestData::hasone_save_cascade_1();

    $out['e']->set_payor(NULL);
    $result = $out['e']->save();


    // tests
    $this->assert($result !== NULL, 'Update successful');

    $this->assert($out['e']->payor_id === NULL, 'FK attribute is NULL');

    $this->assert($out['e']->payor === NULL, 'Hasone attribute is NULL');
  }

  // save has one 1 + update setting null has one id
  public function test_1_2()
  {
    $out = SetupTestData::hasone_save_cascade_1();

    $out['e']->set_payor_id(NULL);
    $result = $out['e']->save();


    // tests
    $this->assert($result !== NULL, 'Update successful');

    $this->assert($out['e']->payor_id === NULL, 'FK attribute is NULL');

    //echo $out['e']->payor_id; // = 1

    $this->assert($out['e']->payor === NULL, 'Hasone attribute is NULL');
  }

  // save has one 1 + update setting another object in hasone attribute
  public function test_1_3()
  {
    global $Payor;

    $out = SetupTestData::hasone_save_cascade_1();

    $p = $Payor->create([
      'company' => 'test payor 2',
      'ein'     => '5555'
    ]);

    $out['e']->set_payor($p);

    $result = $out['e']->save();


    // tests
    $this->assert($result !== NULL, 'Update successful');

    $this->assert($out['e']->payor_id === $p->id, 'FK attribute is equal to the updated object id');

    $this->assert($out['e']->payor !== NULL, 'Hasone attribute is not NULL');
  }


  // save has one 1 + no change: shouldn't trigger updates in the database (should check for dirty)
  public function test_1_4()
  {
    global $Payor;

    $out = SetupTestData::hasone_save_cascade_1();

    $this->assert($out['e']->is_dirty === false, 'Parent is not dirty');

    $this->assert($out['e']->payor->is_dirty === false, 'Hasone object is not dirty');

    // log should output only inserts, no updates here because this is not dirty
    $result = $out['e']->save();


    // tests
    $this->assert($result !== NULL, 'Update successful');
  }


  // save has one 2 + update setting null has one
  public function test_2_1()
  {
    $out = SetupTestData::hasone_save_cascade_2();

    $out['e']->set_payor(NULL);
    $result = $out['e']->save();


    // tests
    $this->assert($result !== NULL, 'Update successful');

    $this->assert($out['e']->payor_id === NULL, 'FK attribute is NULL');

    $this->assert($out['e']->payor === NULL, 'Hasone attribute is NULL');
  }

  // save has one 2 + update setting null has one id
  public function test_2_2()
  {
    $out = SetupTestData::hasone_save_cascade_2();

    $out['e']->set_payor_id(NULL);
    $result = $out['e']->save();


    // tests
    $this->assert($result !== NULL, 'Update successful');

    $this->assert($out['e']->payor_id === NULL, 'FK attribute is NULL');

    //echo $out['e']->payor_id; // = 1

    $this->assert($out['e']->payor === NULL, 'Hasone attribute is NULL');
  }

  // save has one 2 + update setting another object in hasone attribute
  public function test_2_3()
  {
    global $Payor;

    $out = SetupTestData::hasone_save_cascade_2();

    $p = $Payor->create([
      'company' => 'test payor 2',
      'ein'     => '5555'
    ]);

    $out['e']->set_payor($p);

    $result = $out['e']->save();


    // tests
    $this->assert($result !== NULL, 'Update successful');

    $this->assert($out['e']->payor_id === $p->id, 'FK attribute is equal to the updated object id');

    $this->assert($out['e']->payor !== NULL, 'Hasone attribute is not NULL');
  }


  // save has one 2 + no change: shouldn't trigger updates in the database (should check for dirty)
  public function test_2_4()
  {
    global $Payor;

    $out = SetupTestData::hasone_save_cascade_2();

    $this->assert($out['e']->is_dirty === false, 'Parent is not dirty');

    $this->assert($out['e']->payor->is_dirty === false, 'Hasone object is not dirty');

    // log should output only inserts, no updates here because this is not dirty
    $result = $out['e']->save();


    // tests
    $this->assert($result !== NULL, 'Update successful');
  }


  // save has one 3 + update setting null has one
  public function test_3_1()
  {
    $out = SetupTestData::hasone_save_cascade_3();

    $out['e']->set_payor(NULL);
    $result = $out['e']->save();


    // tests
    $this->assert($result !== NULL, 'Update successful');

    $this->assert($out['e']->payor_id === NULL, 'FK attribute is NULL');

    $this->assert($out['e']->payor === NULL, 'Hasone attribute is NULL');
  }

  // save has one 3 + update setting null has one id
  public function test_3_2()
  {
    $out = SetupTestData::hasone_save_cascade_3();

    $out['e']->set_payor_id(NULL);
    $result = $out['e']->save();


    // tests
    $this->assert($result !== NULL, 'Update successful');

    $this->assert($out['e']->payor_id === NULL, 'FK attribute is NULL');

    //echo $out['e']->payor_id; // = 1

    $this->assert($out['e']->payor === NULL, 'Hasone attribute is NULL');
  }

  // save has one 3 + update setting another object in hasone attribute
  public function test_3_3()
  {
    global $Payor;

    $out = SetupTestData::hasone_save_cascade_3();

    $p = $Payor->create([
      'company' => 'test payor 2',
      'ein'     => '5555'
    ]);

    $out['e']->set_payor($p);

    $result = $out['e']->save();


    // tests
    $this->assert($result !== NULL, 'Update successful');

    $this->assert($out['e']->payor_id === $p->id, 'FK attribute is equal to the updated object id');

    $this->assert($out['e']->payor !== NULL, 'Hasone attribute is not NULL');
  }


  // save has one 3 + no change: shouldn't trigger updates in the database (should check for dirty)
  public function test_3_4()
  {
    global $Payor;

    $out = SetupTestData::hasone_save_cascade_3();

    $this->assert($out['e']->is_dirty === false, 'Parent is not dirty');

    $this->assert($out['e']->payor->is_dirty === false, 'Hasone object is not dirty');

    // log should output only inserts, no updates here because this is not dirty
    $result = $out['e']->save();


    // tests
    $this->assert($result !== NULL, 'Update successful');
  }


  // save has one 4 + update setting null has one
  public function test_4_1()
  {
    $out = SetupTestData::hasone_save_cascade_4();

    $out['e']->set_payor(NULL);
    $result = $out['e']->save();


    // tests
    $this->assert($result !== NULL, 'Update successful');

    $this->assert($out['e']->payor_id === NULL, 'FK attribute is NULL');

    $this->assert($out['e']->payor === NULL, 'Hasone attribute is NULL');
  }

  // save has one 4 + update setting null has one id
  public function test_4_2()
  {
    $out = SetupTestData::hasone_save_cascade_4();

    $out['e']->set_payor_id(NULL);
    $result = $out['e']->save();


    // tests
    $this->assert($result !== NULL, 'Update successful');

    $this->assert($out['e']->payor_id === NULL, 'FK attribute is NULL');

    //echo $out['e']->payor_id; // = 1

    $this->assert($out['e']->payor === NULL, 'Hasone attribute is NULL');
  }

  // save has one 3 + update setting another object in hasone attribute
  public function test_4_3()
  {
    global $Payor;

    $out = SetupTestData::hasone_save_cascade_3();

    $p = $Payor->create([
      'company' => 'test payor 2',
      'ein'     => '5555'
    ]);

    $out['e']->set_payor($p);

    $result = $out['e']->save();


    // tests
    $this->assert($result !== NULL, 'Update successful');

    $this->assert($out['e']->payor_id === $p->id, 'FK attribute is equal to the updated object id');

    $this->assert($out['e']->payor !== NULL, 'Hasone attribute is not NULL');
  }


  // save has one 3 + no change: shouldn't trigger updates in the database (should check for dirty)
  public function test_4_4()
  {
    global $Payor;

    $out = SetupTestData::hasone_save_cascade_3();

    $this->assert($out['e']->is_dirty === false, 'Parent is not dirty');

    $this->assert($out['e']->payor->is_dirty === false, 'Hasone object is not dirty');

    // log should output only inserts, no updates here because this is not dirty
    $result = $out['e']->save();


    // tests
    $this->assert($result !== NULL, 'Update successful');
  }
}

?>
<?php

namespace tests\associations;

use CaboLabs\PhTest\PhTestCase;

/**
 * The goal of these tests is to verify the functionality of creating and saving
 * two objects associated with a hasone, and check the values of the hasone 
 * attribute and the hasone foreign key attribute.
 */
class TestHasOneSaveCascade extends PhTestCase {

  public function test_1()
  {
    $out = SetupTestData::hasone_save_cascade_1();


    // tests
    $this->assert($out['result'] !== NULL, 'Save successful');

    $this->assert($out['e']->payor_id !== NULL, 'FK attribute is not NULL');

    $this->assert($out['e']->payor !== NULL, 'Hasone attribute is not NULL');

    $this->assert($out['e']->payor->id == $out['e']->payor_id, 'Hasone attribute id is equals to FK attribute value');

    $this->assert($out['e']->payor->ein === '1234', 'Hasone correct field value');

    $this->assert($out['e']->ein === '4444', 'Parent class correct field value');
  }


  public function test_2()
  {
    // setup
    $out = SetupTestData::hasone_save_cascade_2();


    // tests
    $this->assert($out['result'] !== NULL, 'Save successful');

    $this->assert($out['e']->payor_id !== NULL, 'FK attribute is not NULL');

    $this->assert($out['e']->payor !== NULL, 'Hasone attribute is not NULL');

    $this->assert($out['e']->payor->id == $out['e']->payor_id, 'Hasone attribute id is equals to FK attribute value');

    $this->assert($out['e']->payor->ein === '1234', 'Hasone correct field value');

    $this->assert($out['e']->ein === '4444', 'Parent class correct field value');
  }


  public function test_3()
  {
    // setup
    $out = SetupTestData::hasone_save_cascade_3();


    // tests
    $this->assert($out['result'] !== NULL, 'Save successful');

    $this->assert($out['e']->payor_id !== NULL, 'FK attribute is not NULL');

    $this->assert($out['e']->payor !== NULL, 'Hasone attribute is not NULL');

    $this->assert($out['e']->payor->id == $out['e']->payor_id, 'Hasone attribute id is equals to FK attribute value');

    $this->assert($out['e']->payor->ein === '1234', 'Hasone correct field value');

    $this->assert($out['e']->ein === '4444', 'Parent class correct field value');
  }


  public function test_4()
  {
    // setup
    $out = SetupTestData::hasone_save_cascade_4();


    // tests
    $this->assert($out['result'] !== NULL, 'Save successful');

    $this->assert($out['e']->payor_id !== NULL, 'FK attribute is not NULL');

    $this->assert($out['e']->payor !== NULL, 'Hasone attribute is not NULL');

    $this->assert($out['e']->payor->id == $out['e']->payor_id, 'Hasone attribute id is equals to FK attribute value');

    $this->assert($out['e']->payor->ein === '1234', 'Hasone correct field value');

    $this->assert($out['e']->ein === '4444', 'Parent class correct field value');
  }


  public function test_5()
  {
    // setup
    $out = SetupTestData::hasone_save_cascade_5();

    // tests
    $this->assert($out['result'] !== NULL, 'Save successful');

    $this->assert($out['e']->payor_id === NULL, 'FK attribute is NULL');

    $this->assert($out['e']->payor === NULL, 'Hasone attribute is NULL');

    $this->assert($out['e']->ein === '4444', 'Parent class correct field value');
  }
}

?>
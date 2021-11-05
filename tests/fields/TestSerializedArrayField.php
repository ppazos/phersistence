<?php

namespace tests\fields;

use CaboLabs\PhTest\PhTestCase;

/**
 * The goal of these tests is to verify the functionality of creating and saving
 * objects that contain a serialized array field, and check the attribute values
 * after a get is executed.
 */
class TestSerializedArrayField extends PhTestCase {

  // there is an issue the first test doesn't have a log
  public function test_dummy()
  {

  }

  public function test_save_full()
  {
    global $PriceWithCodes;

    $o = $PriceWithCodes->create([
      'price' => 17.5,
      'codes' => ["A01", "B02", "C03"],
      'not_null_codes' => [] // empty array is valid for not null
    ]);

    $id = $o->save();


    // tests
    $this->assert($id !== NULL, 'Object is saved');

    $this->assert($o->id !== NULL, 'Object is saved');

    $this->assert($o->id === $id, 'Object id consistency');

    $this->assert(round($o->get_price(), 2) === round(17.5, 2), 'Object price OK');

    $this->assert(count($o->get_codes()) === 3, 'Object codes OK');
  }

  public function test_save_minimal()
  {
    global $PriceWithCodes;

    $o = $PriceWithCodes->create();
    $o->set_not_null_codes([]); // empty array is valid for not null

    $id = $o->save();


    // tests
    $this->assert($id !== NULL, 'Object is saved');

    $this->assert($o->id !== NULL, 'Object is saved');

    $this->assert($o->id === $id, 'Object id consistency');

    $this->assert($o->get_price() === NULL, 'Object price OK');

    $this->assert($o->get_codes() === NULL, 'Object codes OK');
  }

  public function test_update_minimal()
  {
    global $PriceWithCodes;

    // save
    $o = $PriceWithCodes->create();
    $o->set_not_null_codes([]); // empty array is valid for not null
    $id = $o->save();

    $this->assert($id !== NULL, 'Object is saved');

    // update
    $o->push_to_codes('A01');
    $id = $o->save();

    $this->assert($id !== NULL, 'Object is updated');
    
    // update
    $o->del_from_codes('A01');
    $id = $o->save();

    $this->assert($id !== NULL, 'Object is updated');
  }

  public function test_save_get_full()
  {
    global $PriceWithCodes;

    // save
    $o = $PriceWithCodes->create([
      'price' => 17.5,
      'codes' => ["A01", "B02", "C03"],
      'not_null_codes' => [] // empty array is valid for not null
    ]);

    $id = $o->save();

    // get
    $p = $PriceWithCodes->get($id);

    // tests
    $this->assert($p->get_codes() === ["A01", "B02", "C03"], 'Object codes OK');

    $this->assert($p->get_not_null_codes() === [], 'Object not null codes OK');
  }

  public function test_save_get_minimal()
  {
    global $PriceWithCodes;

    // save
    $o = $PriceWithCodes->create([
      'not_null_codes' => [] // empty array is valid for not null
    ]);

    $id = $o->save();

    // get
    $p = $PriceWithCodes->get($id);

    // tests
    $this->assert($p->get_codes() === NULL, 'Object codes OK');

    $this->assert($p->get_not_null_codes() === [], 'Object not null codes OK');
  }

  public function test_update_get()
  {
    global $PriceWithCodes;

    // TODO
  }

  public function test_save_list_all()
  {
    global $PriceWithCodes;

    // TODO
  }

  public function test_update_list_all()
  {
    global $PriceWithCodes;

    // TODO
  }
}

?>
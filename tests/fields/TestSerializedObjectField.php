<?php

namespace tests\fields;

use CaboLabs\Debbie\DebbieTestCase;

class TestSerializedObjectField extends DebbieTestCase {

  public function test_dummy()
  {

  }

  public function test_null_sobject_save()
  {
    global $TestSObject;

    $o = $TestSObject->create();

    $this->assert($o->save() !== false, 'Save with null sobject succeeds');
  }

  public function test_null_sobject_update()
  {
    global $TestSObject;

    $o = $TestSObject->create();
    $o->save();

    $loaded = $TestSObject->get($o->get_id());
    $loaded->setProperties(['num' => 2]);

    $this->assert($loaded->save() !== false, 'Update with null sobject succeeds');
  }

  public function test_create_with_sobject()
  {
    global $TestSObject;

    $o = $TestSObject->create([
      'sobject' => [
        'nombre' => 'Pablo',
        'edad' => 37,
        'concepto' => ['nombre' => 'Persona']
      ]
    ]);

    $sobject = $o->get_sobject();

    $this->assert($sobject['nombre'] === 'Pablo', 'sobject nombre is correct');
    $this->assert($sobject['edad'] === 37, 'sobject edad is correct');
  }

  public function test_set_sobject()
  {
    global $TestSObject;

    $o = $TestSObject->create();

    $this->assert($o->get_sobject() === null, 'Default sobject is null');

    $o->set_sobject([
      'nombre' => 'Pablo',
      'edad' => 37,
      'concepto' => ['nombre' => 'Persona']
    ]);

    $sobject = $o->get_sobject();

    $this->assert($sobject['nombre'] === 'Pablo', 'sobject nombre is correct');
    $this->assert($sobject['edad'] === 37, 'sobject edad is correct');

    $this->assert($o->save() !== false, 'Save with sobject succeeds');
  }

  public function test_sobject_persistence()
  {
    global $TestSObject;

    $o = $TestSObject->create([
      'sobject' => [
        'nombre' => 'Miguel',
        'edad' => 25,
        'concepto' => ['nombre' => 'Persona']
      ]
    ]);

    $o->save();

    $loaded = $TestSObject->get($o->get_id());

    $this->assert($loaded !== null, 'Object loaded');

    $sobject = $loaded->get_sobject();

    $this->assert($sobject['nombre'] === 'Miguel', 'Loaded sobject nombre is correct');
    $this->assert($sobject['edad'] === 25, 'Loaded sobject edad is correct');
  }

  public function test_set_properties_sobject()
  {
    global $TestSObject;

    $o = $TestSObject->create();
    $o->setProperties([
      'sobject' => [
        'nombre' => 'Xina',
        'edad' => 66,
        'concepto' => ['nombre' => 'Persona']
      ]
    ]);

    $this->assert($o->get_sobject() !== null, 'sobject is set');

    $sobject = $o->get_sobject();

    $this->assert($sobject['nombre'] === 'Xina', 'sobject nombre is correct');
    $this->assert($sobject['edad'] === 66, 'sobject edad is correct');

    $o->save();

    $loaded = $TestSObject->get($o->get_id());

    $sobject = $loaded->get_sobject();

    $this->assert($sobject['nombre'] === 'Xina', 'Loaded sobject nombre is correct');
    $this->assert($sobject['edad'] === 66, 'Loaded sobject edad is correct');
  }
}

?>

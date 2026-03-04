<?php

namespace tests\fields;

use CaboLabs\Debbie\DebbieTestCase;

class TestZeroNull extends DebbieTestCase {

  public function test_dummy()
  {

  }

  public function test_zero_values_persist()
  {
    global $NumberTest;

    $o = $NumberTest->create([
      'number1' => 0,
      'number2' => 0,
      'number3' => 0.0,
      'number4' => 0.0
    ]);

    $o->save();

    $loaded = $NumberTest->get($o->get_id());

    $this->assert($loaded->get_number1() === 0, 'INT zero persists');
    $this->assert($loaded->get_number2() === 0, 'LONG zero persists');
    $this->assert($loaded->get_number3() === 0.0, 'FLOAT zero persists');
    $this->assert($loaded->get_number4() === 0.0, 'DOUBLE zero persists');
  }
}

?>

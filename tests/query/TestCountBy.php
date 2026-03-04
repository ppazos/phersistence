<?php

namespace tests\query;

use CaboLabs\Debbie\DebbieTestCase;

class TestCountBy extends DebbieTestCase {

  public function test_dummy()
  {

  }

  public function test_count_by_returns_int()
  {
    global $NumberTest;

    $numbers = [
      $NumberTest->create(['number1' => 0, 'number2' => 0, 'number3' => 0.0, 'number4' => 0.0]),
      $NumberTest->create(['number1' => 1, 'number2' => 1, 'number3' => 1.0, 'number4' => 1.0]),
      $NumberTest->create(['number1' => 2, 'number2' => 2, 'number3' => 2.0, 'number4' => 2.0])
    ];

    foreach ($numbers as $number) {
      $number->save();
    }

    $count = $NumberTest->countBy([
      ['number1', '>', 0]
    ]);

    $this->assert(is_int($count), 'countBy returns int');
    $this->assert($count === 2, 'countBy returns correct count');
  }
}

?>

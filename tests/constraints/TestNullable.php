<?php

namespace tests\constraints;

use CaboLabs\PhTest\PhTestCase;

/**
 * The goal of these tests is to verify nullability checks in some fields depending on the defined constraints.
 */
class TestNullable extends PhTestCase {

  // there is an issue the first test doesn't have a log
  public function test_dummy()
  {

  }

  public function test_nullable_true()
  {
    global $Person;

    $o = $Person->create();

    // explicity defined nullable
    $this->assert($o->phclass->is_nullable('phone_number'), 'Correct nullable');

    // not defined, default nullable
    $this->assert($o->phclass->is_nullable('lastname'), 'Correct nullable');

    // explicitly not nullable
    $this->assert(!$o->phclass->is_nullable('firstname'), 'Correct not nullable');
  }

}

?>
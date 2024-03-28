<?php

namespace tests\query;

use CaboLabs\Debbie\DebbieTestCase;

/**
 * The goal of these tests is to verify the functionality of creating and saving
 * objects that contain a serialized array field, and check the attribute values
 * after a get is executed.
 */
class TestQueries extends DebbieTestCase {

  // there is an issue the first test doesn't have a log
  public function test_dummy()
  {

  }

  public function test_in()
  {
    global $Person;

    $res = $Person->findBy([
      ['firstname', 'IN', ['Pablo', 'Maria', 'Barbara']]
    ], 20, 0);

    $this->assert($res !== NULL, 'Result not null');
  }

}

?>
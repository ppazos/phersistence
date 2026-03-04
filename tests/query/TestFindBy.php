<?php

namespace tests\query;

use CaboLabs\Debbie\DebbieTestCase;

class TestFindBy extends DebbieTestCase {

  public function test_dummy()
  {

  }

  public function test_find_by_simple()
  {
    global $Person;

    $where = [
      ['firstname', 'LIKE', '%p%']
    ];

    $res = $Person->findBy($where, 20, 0);

    $this->assert(is_array($res), 'findBy returns array');
  }

  public function test_find_by_and()
  {
    global $Person;

    $where = [
      "AND" => [
        ['firstname', 'LIKE', '%p%'],
        ['lastname', 'LIKE', '%p%']
      ]
    ];

    $res = $Person->findBy($where, 20, 0);

    $this->assert(is_array($res), 'findBy AND returns array');
  }

  public function test_find_by_or()
  {
    global $Person;

    $where = [
      "OR" => [
        ['firstname', 'LIKE', '%p%'],
        ['lastname', 'LIKE', '%p%']
      ]
    ];

    $res = $Person->findBy($where, 20, 0);

    $this->assert(is_array($res), 'findBy OR returns array');
  }

  public function test_find_by_or_three()
  {
    global $Person;

    $where = [
      "OR" => [
        ['firstname', 'LIKE', '%p%'],
        ['lastname', 'LIKE', '%p%'],
        ['phone_number', '=', '555-123-1234']
      ]
    ];

    $res = $Person->findBy($where, 20, 0);

    $this->assert(is_array($res), 'findBy OR with 3 conditions returns array');
  }

  public function test_find_by_and_or()
  {
    global $Person;

    $where = [
      "AND" => [
        "OR" => [
          ['firstname', 'LIKE', '%p%'],
          ['lastname', 'LIKE', '%p%']
        ],
        ['phone_number', '=', '555-123-1234']
      ]
    ];

    $res = $Person->findBy($where, 20, 0);

    $this->assert(is_array($res), 'findBy AND OR returns array');
  }

  public function test_find_by_not_and()
  {
    global $Person;

    $where = [
      "NOT" => [
        "AND" => [
          ['firstname', 'LIKE', '%p%'],
          ['lastname', 'LIKE', '%p%']
        ]
      ]
    ];

    $res = $Person->findBy($where, 20, 0);

    $this->assert(is_array($res), 'findBy NOT AND returns array');
  }

  public function test_find_by_not_simple()
  {
    global $Person;

    $where = [
      "NOT" => ['phone_number', '=', '555-123-1234']
    ];

    $res = $Person->findBy($where, 20, 0);

    $this->assert(is_array($res), 'findBy NOT simple returns array');
  }
}

?>

<?php

namespace tests\query;

use CaboLabs\Debbie\DebbieTestCase;
use CaboLabs\Phersistence\phersistent\query\PhQuery as q;

/**
 * The goal of these tests is to verify the functionality of creating and saving
 * objects that contain a serialized array field, and check the attribute values
 * after a get is executed.
 */
class TestQueries2 extends DebbieTestCase {

  private function bootstrap()
  {
    global $Person;

    $persons = [
      $Person->create([
        'firstname' => 'Pablo',
        'lastname' => 'gonzales'
      ]),
      $Person->create([
        'firstname' => 'Pablo',
        'lastname' => 'suarez',
      ]),
      $Person->create([
        'firstname' => 'Maria',
        'lastname' => 'perez'
      ]),
      $Person->create([
        'firstname' => 'Maria',
        'lastname' => 'gonzales'
      ]),
      $Person->create([
        'firstname' => 'Maria',
        'lastname' => 'perez',
        'phone_number' => '090909'
      ]),
      $Person->create([
        'firstname' => 'Barbara',
        'lastname' => 'perez',
        'phone_number' => '090909'
      ]),
      $Person->create([
        'firstname' => 'Paula',
        'lastname' => 'torres'
      ]),
      $Person->create([
        'firstname' => 'Maria',
        'lastname' => 'torres',
        'phone_number' => '717171'
      ]),
      $Person->create([
        'firstname' => 'Maria',
        'lastname' => 'torres',
        'phone_number' => '7171718'
      ]),
      $Person->create([
        'firstname' => 'Maria',
        'lastname' => 'Hernandez',
        'phone_number' => '343434'
      ]),
      $Person->create([
        'firstname' => 'Barbara',
        'lastname' => 'perez',
        'phone_number' => '717171'
      ]),
      $Person->create([
        'firstname' => 'Paula',
        'lastname' => 'perez',
        'phone_number' => 'suarez'
      ])
    ];

    foreach ($persons as $person)
    {
      $person->save();
    }
  }

  public function test_in()
  {
    global $Person;
    $this->bootstrap();

    //$res = $Person->findBy2(['firstname', 'IN', ['Pablo', 'Maria', 'Barbara']], 20, 0);
    $res = $Person->findBy2(q::in('firstname', ['Pablo', 'Maria', 'Barbara']), 20, 0);

    //should be 10
    $this->assert(count($res) == 10, count($res) . ' results found');
  }

  public function test_and_or_0()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->findBy2(
      q::and([
        q::eq('firstname', 'maria'),
        q::eq('lastname', 'gonzales'),
        q::or([
          q::eq('lastname', 'perez'),
          q::eq('phone_number', '090909')
        ])
      ]),
      20, 0
    );

    $this->assert(count($res) == 0, '0 results found');
  }

  public function test_and_or_1()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->findBy2(
      q::and([
        q::eq('firstname', 'maria'),
        q::or([
          q::and([
            q::eq('lastname', 'gonzales'),
            q::isNull('phone_number')
          ]),
          q::or([
            q::eq('lastname', 'perez'),
            q::eq('phone_number', '090909')
          ])
        ])
      ]),
      20, 0
    );

    $this->assert(count($res) == 3, '3 results found');
  }

  public function test_and_or_11()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->findBy2(
      q::and([
        q::eq('firstname', 'maria'),
        q::or([
          q::and([
            q::eq('lastname', 'gonzales'),
            q::isNull('phone_number')
          ]),
          q::and([
            q::eq('lastname', 'perez'),
            q::eq('phone_number', '090909')
          ])
        ])
      ]),
      20, 0
    );

    $this->assert(count($res) == 2, '2 results found');
  }

  public function test_and_or_2()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->findBy2(
      q::or([
        q::eq('firstname', 'maria'),
        q::or([
          q::and([
            q::eq('lastname', 'gonzales'),
            q::isNull('phone_number')
          ]),
          q::and([
            q::eq('lastname', 'perez'),
            q::eq('phone_number', '090909')
          ]),
          q::and([
            q::eq('lastname', 'torres'),
            q::notEq('phone_number', '717171')
          ])
        ]),
        q::and([
          q::eq('lastname', 'hernandez'),
          q::eq('phone_number', '343434')
        ])
      ]),
      20, 0
    );

    $this->assert(count($res) == 8, '8 results found');
  }

  public function test_and_or_3()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->findBy2(
      q::and([
        q::eq('firstname', 'maria'),
        q::or([
          q::eq('lastname', 'gonzales'),
          q::isNull('phone_number'),
          q::and([
            q::eq('lastname', 'perez'),
            q::eq('phone_number', '090909'),
            q::and([
              q::eq('lastname', 'torres'),
              q::gt('phone_number', '717171'),
              q::eq('phone_number', '676767')
            ])
          ])
        ])
      ]),
      20, 0
    );

    $this->assert(count($res) == 2, '2 results found');
  }

  public function test_and_or_not_simple()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->findBy2(
      q::and([
        q::eq('firstname', 'maria'),
        q::eq('lastname', 'gonzales'),
        q::not(
          q::eq('lastname', 'perez')
        )
      ]),
      20, 0
    );

    $this->assert(count($res) == 1, '1 results found');
  }

  public function test_not_1()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->findBy2(
      q::and([
        q::eq('firstname', 'maria'),
        q::eq('firstname', 'pablo'),
        q::not(
          q::and([
            q::eq('lastname', 'gonzales'),
            q::isNull('phone_number'),
            q::or([
              q::eq('lastname', 'perez'),
              q::eq('phone_number', '090909'),
              q::and([
                q::eq('lastname', 'torres'),
                q::eq('phone_number', '717171')
              ]),
              q::and([
                q::eq('firstname', 'Paula'),
                q::not(
                  q::eq('lastname', 'suarez')
                )
              ])
            ])
          ])
        )
      ]),
      20, 0
    );

    //should be 0
    $this->assert(count($res) == 0, '0 results found');
  }

  public function test_not_2()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->findBy2(
      q::and([
        q::eq('firstname', 'pablo'),
        q::not(
          q::eq('lastname', 'gonzales')
        )
      ]),
      20, 0
    );

    $this->assert(count($res) == 1, '1 results found');
  }

  public function test_not_simple()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->findBy2(
      q::not(
        q::eq('firstname', 'pablo')
      ),
      20, 0
    );

    $this->assert(count($res) == 10, count($res) . ' results found');
  }

  public function test_count_by_1()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->countBy2(
      q::in('firstname', ['Pablo', 'Maria', 'Barbara'])
    );

    $this->assert($res == 10, 'count_by results are 10');
  }

  public function test_count_by_2()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->countBy2(
      q::and([
        q::eq('firstname', 'maria'),
        q::eq('firstname', 'pablo'),
        q::not(
          q::and([
            q::eq('lastname', 'gonzales'),
            q::isNull('phone_number'),
            q::or([
              q::eq('lastname', 'perez'),
              q::eq('phone_number', '090909'),
              q::and([
                q::eq('lastname', 'torres'),
                q::eq('phone_number', '717171'),
              ]),
              q::and([
                q::eq('firstname', 'Paula'),
                q::not(
                  q::eq('lastname', 'suarez'),
                )
              ])
            ])
          ])
        )
      ])
    );

    $this->assert($res == 0, 'count_by results are 0');
  }

  public function test_count_by_3()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->countBy2(
      q::not(
        q::eq('firstname', 'pablo'),
      )
    );

    $this->assert($res == 10, 'count_by results are 10');
  }

  public function test_with_accents()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->findBy2(
      q::and([
        q::eq('firstname', 'maría'),
        q::eq('lastname', 'perez'),
        q::eq('phone_number', '090909')
      ]),
      20, 0
    );

    $this->assert(count($res) == 0, '0 results found');
  }

  public function test_without_where()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->findBy2([], 20, 0);

    // should be 12 because should list all
    $this->assert(count($res) == 12, '12 results found');
  }

  public function test_count_without_where()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->countBy2([]);

    $this->assert($res == 12, '12 results found');
  }
}

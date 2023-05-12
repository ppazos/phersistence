<?php

namespace tests\query;

use CaboLabs\PhTest\PhTestCase;
use CaboLabs\Phersistence\phersistent\PhQuery as q;

/**
 * The goal of these tests is to verify the functionality of creating and saving
 * objects that contain a serialized array field, and check the attribute values
 * after a get is executed.
 */
class TestQueries extends PhTestCase {

  // there is an issue the first test doesn't have a log
  public function test_dummy()
  {

  }

  public function test_in()
  {
    global $Person;

    $res = $Person->findBy([
      ['firstname', 'IN', ['("Pablo"', '"Maria"', '"Barbara")']]
    ], 20, 0);

    $this->assert($res !== NULL, 'Result not null');
  }

  public function test_and_or_1()
  {
    global $Person;

    $res = $Person->findBy([
      q::_And([
        ['firstname', '=', '"maría"'],[
          q::_Or([
            [q::_And([
                ['lastname', '>', '"gonzales"'],
                ['phone_number', 'IS NULL']
            ])
           ],
           [
            q::_Or([
                ['lastname', '>', '"perez"'],
                ['phone_number', '=', '"090909"']
            ])
           ]
          ])
        ]
      ])
    ], 20, 0);

    $this->assert($res !== NULL, 'Result not null');
  }

  public function test_and_or_2()
  {
    global $Person;

    $res = $Person->findBy([
      q::_And([
        ['firstname', '=', '"maría"'],[
          q::_Or([
            [q::_And([
              ['lastname', '>', '"gonzales"'],
              ['phone_number', 'IS NULL']
            ])],[
            q::_And([
              ['lastname', '>', '"perez"'],
              ['phone_number', '=', '"090909"']
            ])],[
            q::_And([
              ['lastname', '>', '"torres"'],
              ['phone_number', '=', '"717171"']
            ])]
          ])
        ],[
          q::_And([
            ['lastname', '>', '"firstname"'],[
            q::_Or([
              ['phone_number', '=', '"343434"']
            ])
            ]
          ])
        ]
      ])
    ], 20, 0);

    $this->assert($res !== NULL, 'Result not null');
  }

  public function test_and_or_3()
  {
    global $Person;

    $res = $Person->findBy([
      q::_And([
        ['firstname', '=', '"maría"'],[
          q::_And([
            [q::_Or([
              ['lastname', '>', '"gonzales"'],
              ['phone_number', 'IS NULL'],[
                  q::_And([
                    ['lastname', '>', '"perez"'],
                    ['phone_number', '=', '"090909"'],[
                        q::_And([
                          ['lastname', '>', '"torres"'],
                          ['phone_number', '=', '"717171"'],
                          ['phone_number', '=', '"676767"']
                        ])
                    ]
                  ])
                ]
              ])
            ]
          ])
        ]
      ])
    ], 20, 0);

    $this->assert($res !== NULL, 'Result not null');
  }

  public function test_not()
  {
    global $Person;

    $res = $Person->findBy([
      q::_And([
        ['firstname', '=', '"maría"'],
        ['firstname', '=', '"pablo"'],[
          q::_Not('AND', [
            [q::_And([
              ['lastname', '>', '"gonzales"'],
              ['phone_number', 'IS NULL'],[
                    q::_Or([
                    ['lastname', '>', '"perez"'],
                    ['phone_number', '=', '"090909"'],[
                      q::_And([
                        ['lastname', '>', '"torres"'],
                        ['phone_number', '=', '"717171"']
                      ]),
                      q::_Not('OR', [
                        ['lastname', '>', '"smith"'],
                        ['phone_number', '=', '"616161"']
                        ])
                      ]
                  ])
                ]
              ])
            ]
          ])
        ]
      ])
    ], 20, 0);

    $this->assert($res !== NULL, 'Result not null');
  }

}

?>
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
      ['firstname', 'IN', '("Pablo"', '"Maria"', '"Barbara")']
    ], 20, 0);

    $this->assert($res !== NULL, 'Result not null');
  }

  public function test_and_or_1()
  {
    global $Person;

    $res = $Person->findBy([
      q::And([
        ['firstname', '=', '"maría"'],[
          q::Or([
            [q::And([
              ['lastname', '=', '"gonzales"'],
              ['phone_number', 'IS NULL']
            ])
            ],
            [
              q::Or([
                ['lastname', '=', '"perez"'],
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
      q::And([
        ['firstname', '=', '"maría"'],[
          q::Or([
            [q::And([
              ['lastname', '=', '"gonzales"'],
              ['phone_number', 'IS NULL']
            ])],[
            q::And([
              ['lastname', '=', '"perez"'],
              ['phone_number', '=', '"090909"']
            ])],[
            q::And([
              ['lastname', '=', '"torres"'],
              ['phone_number', '<>', '"717171"']
            ])]
          ])
        ],[
          q::And([
            ['lastname', '=', '"Hernandez"'],[
            q::Or([
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
      q::And([
        ['firstname', '=', '"maría"'],[
          q::And([
            [q::Or([
              ['lastname', '=', '"gonzales"'],
              ['phone_number', 'IS NULL'],[
                  q::And([
                    ['lastname', '=', '"perez"'],
                    ['phone_number', '=', '"090909"'],[
                      q::And([
                        ['lastname', '=', '"torres"'],
                        ['phone_number', '>', '"717171"'],
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

  public function test_and_or_not_simple()
  {
    global $Person;

    $res = $Person->findBy([
      q::And([
        ['firstname', '=', '"maría"'],
        [q::And([['lastname', '=', '"gonzales"']])],
        [q::Not([['lastname', '=', '"perez"']])]
      ])
    ], 20, 0);

    $this->assert($res !== NULL, 'Result not null');
  }

  public function test_not_1()
  {
    global $Person;

    $res = $Person->findBy([
      q::And([
        ['firstname', '=', '"maría"'],
        ['firstname', '=', '"pablo"'],[
          q::Not([
            [q::And([
              ['lastname', '=', '"gonzales"'],
              ['phone_number', 'IS NULL'],[
                q::Or([
                  ['lastname', '=', '"perez"'],
                  ['phone_number', '=', '"090909"'],[
                    q::And([
                      ['lastname', '=', '"torres"'],
                      ['phone_number', '=', '"717171"']
                    ])
                  ],[
                    q::And([
                      ['firstname', '=', '"Paula"'],
                      [q::Not([['lastname', '=', '"smith"']])]
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

  public function test_not_2()
  {
    global $Person;

    $res = $Person->findBy([
      q::And([
        ['firstname', '=', '"pablo"'],[
          q::Not([
            [q::And([
              ['lastname', '=', '"gonzales"']
              ])
            ]
          ])
        ]
      ])
    ], 20, 0);

    $this->assert($res !== NULL, 'Result not null');
  }

  public function test_not_simple()
  {
    global $Person;

    $res = $Person->findBy([
      q::Not([
        ['firstname', '=', '"pablo"']
      ])
    ], 20, 0);

    $this->assert($res !== NULL, 'Result not null');
  }

}

?>
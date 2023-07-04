<?php

namespace tests\query;

use CaboLabs\PhTest\PhTestCase;
use CaboLabs\Phersistence\phersistent\PhQuery as q;

/**
 * The goal of these tests is to verify the functionality of creating and saving
 * objects that contain a serialized array field, and check the attribute values
 * after a get is executed.
 */
class TestQueries2 extends PhTestCase {

  // there is an issue the first test doesn't have a log
  public function test_dummy()
  {

  }

  private function bootstrap()
  {
    global $Person;
        
    $persons = [
      $Person->create([
        'firstname' => 'Pablo',
        'lastname' => 'gonzales',
        'phone_number' => null
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

    $res = $Person->findByTest([
      ['firstname', 'IN', '("Pablo", "Maria", "Barbara")']
    ], 20, 0);

    $this->assert(count($res) == 3, 'number of results :' . count($res));
    $this->assert($res[0]->firstname == 'Pablo', $res[0]->firstname);
  }

  public function test_and_or_1()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->findByTest([
      q::and([
        ['firstname', '=', '"maría"'],[
          q::or([
            [q::and([
              ['lastname', '=', '"gonzales"'],
              ['phone_number', 'IS NULL']
            ])
            ],
            [
              q::or([
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
    $this->bootstrap();

    $res = $Person->findByTest([
      q::and([
        ['firstname', '=', '"maría"'],[
          q::or([
            [q::and([
              ['lastname', '=', '"gonzales"'],
              ['phone_number', 'IS NULL']
            ])],[
            q::and([
              ['lastname', '=', '"perez"'],
              ['phone_number', '=', '"090909"']
            ])],[
            q::and([
              ['lastname', '=', '"torres"'],
              ['phone_number', '<>', '"717171"']
            ])]
          ])
        ],[
          q::and([
            ['lastname', '=', '"Hernandez"'],[
            q::or([
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
    $this->bootstrap();

    $res = $Person->findByTest([
      q::and([
        ['firstname', '=', '"maría"'],[
          q::and([
            [q::or([
              ['lastname', '=', '"gonzales"'],
              ['phone_number', 'IS NULL'],[
                  q::and([
                    ['lastname', '=', '"perez"'],
                    ['phone_number', '=', '"090909"'],[
                      q::and([
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
    $this->bootstrap();

    $res = $Person->findByTest([
      q::and([
        ['firstname', '=', '"maría"'],
        [q::and([['lastname', '=', '"gonzales"']])],
        [q::not([['lastname', '=', '"perez"']])]
      ])
    ], 20, 0);

    $this->assert($res !== NULL, 'Result not null');
  }

  public function test_not_1()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->findByTest([
      q::and([
        ['firstname', '=', '"maría"'],
        ['firstname', '=', '"pablo"'],[
          q::not([
            [q::and([
              ['lastname', '=', '"gonzales"'],
              ['phone_number', 'IS NULL'],[
                q::or([
                  ['lastname', '=', '"perez"'],
                  ['phone_number', '=', '"090909"'],[
                    q::and([
                      ['lastname', '=', '"torres"'],
                      ['phone_number', '=', '"717171"']
                    ])
                  ],[
                    q::and([
                      ['firstname', '=', '"Paula"'],
                      [q::not([['lastname', '=', '"smith"']])]
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
    $this->bootstrap();

    $res = $Person->findByTest([
      q::and([
        ['firstname', '=', '"pablo"'],[
          q::not([
            [q::and([
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
    $this->bootstrap();

    $res = $Person->findByTest([
      q::not([
        ['firstname', '=', '"pablo"']
      ])
    ], 20, 0);

    $this->assert($res !== NULL, 'Result not null');
  }

  public function test_count_by_1()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->countByTest([
      ['firstname', 'IN', '("Pablo"', '"Maria"', '"Barbara")']
    ]);

    $this->assert($res !== NULL, 'Result not null');
  }

  public function test_count_by_2()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->countByTest([
      q::and([
        ['firstname', '=', '"maría"'],
        ['firstname', '=', '"pablo"'],[
          q::not([
            [q::and([
              ['lastname', '=', '"gonzales"'],
              ['phone_number', 'IS NULL'],[
                q::or([
                  ['lastname', '=', '"perez"'],
                  ['phone_number', '=', '"090909"'],[
                    q::and([
                      ['lastname', '=', '"torres"'],
                      ['phone_number', '=', '"717171"']
                    ])
                  ],[
                    q::and([
                      ['firstname', '=', '"Paula"'],
                      [q::not([['lastname', '=', '"smith"']])]
                    ])
                    ]
                  ])
                ]
              ])
            ]
          ])
        ]
      ])
    ]);

    $this->assert($res !== NULL, 'Result not null');
  }

  public function test_count_by_3()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->countByTest([
      q::not([
        ['firstname', '=', '"pablo"']
      ])
    ]);

    $this->assert($res !== NULL, 'Result not null');
  }

}

?>
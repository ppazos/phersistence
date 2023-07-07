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

    $res = $Person->findBy2([
      ['firstname', 'IN', '("Pablo", "Maria", "Barbara")']
    ], 20, 0);

    //should be 10
    $this->assert(count($res) == 10, count($res) . ' results found');
   /* echo('<pre>');
    print_r($res[0]->phclass);
    echo('<pre>');*/
  }

  public function test_and_or_1()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->findBy2([
      q::and([
        ['firstname', '=', '"maría"'],
        [
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

    //should be 3
    $this->assert(count($res) == 3, count($res) . ' results found');
    // print_r($res);
  }

  public function test_and_or_2()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->findBy2([
      q::or([
        ['firstname', '=', '"maría"'],
        [
          q::or([
            [q::and([
              ['lastname', '=', '"gonzales"'],
              ['phone_number', 'IS NULL']
            ])],
            [
              q::and([
                ['lastname', '=', '"perez"'],
                ['phone_number', '=', '"090909"']
              ])
            ],
            [
              q::and([
                ['lastname', '=', '"torres"'],
                ['phone_number', '<>', '"717171"']
              ])
            ]
          ])
        ],
        [
          q::and([
            ['lastname', '=', '"Hernandez"'],
            [
              q::or([
                ['phone_number', '=', '"343434"']
              ])
            ]
          ])
        ]
      ])
    ], 20, 0);

    //should be 8
    $this->assert(count($res) == 8, count($res) . ' results found');
    // print_r($res);
  }

  public function test_and_or_3()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->findBy2([
      q::and([
        ['firstname', '=', '"maría"'],
        [
          q::and([
            [q::or([
              ['lastname', '=', '"gonzales"'],
              ['phone_number', 'IS NULL'],
              [
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

    //should be 2
    $this->assert(count($res) == 2, count($res) . ' results found');
    // print_r($res);
  }

  public function test_and_or_not_simple()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->findBy2([
      q::and([
        ['firstname', '=', '"maría"'],
        [q::and([['lastname', '=', '"gonzales"']])],
        [q::not([['lastname', '=', '"perez"']])]
      ])
    ], 20, 0);

    //should be 1
    $this->assert(count($res) == 1, count($res) . ' results found');
    // print_r($res);
  }

  public function test_not_1()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->findBy2([
      q::and([
        ['firstname', '=', '"maría"'],
        ['firstname', '=', '"pablo"'],
        [
          q::not([
            [q::and([
              ['lastname', '=', '"gonzales"'],
              ['phone_number', 'IS NULL'],
              [
                q::or([
                  ['lastname', '=', '"perez"'],
                  ['phone_number', '=', '"090909"'],
                  [
                    q::and([
                      ['lastname', '=', '"torres"'],
                      ['phone_number', '=', '"717171"']
                    ])
                  ],
                  [
                    q::and([
                      ['firstname', '=', '"Paula"'],
                      [q::not([['lastname', '=', '"suarez"']])]
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

    //should be 0
    $this->assert(count($res) == 0, count($res) . ' results found');
    // print_r($res);
  }

  public function test_not_2()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->findBy2([
      q::and([
        ['firstname', '=', '"pablo"'],
        [
          q::not([
            [q::and([
              ['lastname', '=', '"gonzales"']
              ])
            ]
          ])
        ]
      ])
    ], 20, 0);

    //should be 1
    $this->assert(count($res) == 1, count($res) . ' results found');
    // print_r($res);
  }

  public function test_not_simple()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->findBy2([
      q::not([
        ['firstname', '=', '"pablo"']
      ])
    ], 20, 0);

    //should be 10
    $this->assert(count($res) == 10, count($res) . ' results found');
    // print_r($res);
  }

  public function test_count_by_1()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->countBy2([
      ['firstname', 'IN', '("Pablo", "Maria", "Barbara")']
    ]);

    //should be 10
    $this->assert($res == 10, 'count results: ' . $res);
    // print_r($res);
  }

  public function test_count_by_2()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->countBy2([
      q::and([
        ['firstname', '=', '"maría"'],
        ['firstname', '=', '"pablo"'],
        [
          q::not([
            [q::and([
              ['lastname', '=', '"gonzales"'],
              ['phone_number', 'IS NULL'],
              [
                q::or([
                  ['lastname', '=', '"perez"'],
                  ['phone_number', '=', '"090909"'],
                  [
                    q::and([
                      ['lastname', '=', '"torres"'],
                      ['phone_number', '=', '"717171"']
                    ])
                  ],
                  [
                    q::and([
                      ['firstname', '=', '"Paula"'],
                      [q::not([['lastname', '=', '"suarez"']])]
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

    //should be 0
    $this->assert($res == 0, 'count results: ' . $res);
    // print_r($res);
  }

  public function test_count_by_3()
  {
    global $Person;
    $this->bootstrap();

    $res = $Person->countBy2([
      q::not([
        ['firstname', '=', '"pablo"']
      ])
    ]);

    //should be 10
    $this->assert($res == 10, 'count results: ' . $res);
    // print_r($res);
  }

}

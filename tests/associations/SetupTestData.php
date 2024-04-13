<?php

namespace tests\associations;

class SetupTestData {

  static function hasone_save_cascade_1()
  {
    global $Employer;

    $e = $Employer->create([
      'payor' => [
        'company' => 'test payor',
        'ein'     => '1234'
      ],
      'company'   => 'test employer',
      'ein'       => '4444'
    ]);

    $result = $e->save();

    return ['e' => $e, 'result' => $result];
  }

  static function hasone_save_cascade_2()
  {
    global $Employer;

    $e = $Employer->create([
      'company'   => 'test employer',
      'ein'       => '4444'
    ]);

    $e->setProperties([
      'payor' => [
        'company' => 'test payor',
        'ein'     => '1234'
      ]
    ]);

    $result = $e->save();

    return ['e' => $e, 'result' => $result];
  }

  static function hasone_save_cascade_3()
  {
    global $Employer, $Payor;

    $e = $Employer->create([
      'company'   => 'test employer',
      'ein'       => '4444'
    ]);

    $p = $Payor->create([
      'company' => 'test payor',
      'ein'     => '1234'
    ]);

    $e->setProperties(['payor' => $p]);

    $result = $e->save();

    return ['e' => $e, 'result' => $result];
  }

  static function hasone_save_cascade_4()
  {
    global $Employer, $Payor;

    // setup
    $e = $Employer->create([
      'company'   => 'test employer',
      'ein'       => '4444'
    ]);

    $p = $Payor->create([
      'company' => 'test payor',
      'ein'     => '1234'
    ]);

    $e->set_payor($p);

    $result = $e->save();

    return ['e' => $e, 'result' => $result];
  }

  static function hasone_save_cascade_5()
  {
    global $Employer, $Payor;

    // setup
    $e = $Employer->create([
      'company'   => 'test employer',
      'ein'       => '4444'
    ]);

    $result = $e->save();

    return ['e' => $e, 'result' => $result];
  }


  // person with addresses set by create()
  static function hasmany_save_1()
  {
    global $Address, $Person;

    $addresses = [
      $Address->create([
        'line1'   => 'line1_1',
        'zipcode' => '11111',
        'state'   => 'MN'
      ]),
      $Address->create([
        'line1'   => 'line1_2',
        'zipcode' => '22222',
        'state'   => 'MN'
      ]),
      $Address->create([
        'line1'   => 'line1_3',
        'zipcode' => '33333',
        'state'   => 'MN'
      ])
    ];

    $person = $Person->create([
      'firstname'    => 'Test',
      'lastname'     => 'One',
      'phone_number' => '5551234',
      'addresses'    => $addresses
    ]);

    $result = $person->save();

    return ['person' => $person, 'result' => $result];
  }

  // person with addresses set by setProperties()
  static function hasmany_save_2()
  {
    global $Address, $Person;

    $addresses = [
      $Address->create([
        'line1'   => 'line1_1',
        'zipcode' => '11111',
        'state'   => 'MN'
      ]),
      $Address->create([
        'line1'   => 'line1_2',
        'zipcode' => '22222',
        'state'   => 'MN'
      ]),
      $Address->create([
        'line1'   => 'line1_3',
        'zipcode' => '33333',
        'state'   => 'MN'
      ])
    ];

    $person = $Person->create([
      'firstname'    => 'Test',
      'lastname'     => 'One',
      'phone_number' => '5551234'
    ]);

    $person->setProperties([
      'addresses' => $addresses
    ]);

    $result = $person->save();

    return ['person' => $person, 'result' => $result];
  }

  // personb with addresses set with add_to_xxx
  static function hasmany_save_3()
  {
    global $Address, $Person;

    $addresses = [
      $Address->create([
        'line1'   => 'line1_1',
        'zipcode' => '11111',
        'state'   => 'MN'
      ]),
      $Address->create([
        'line1'   => 'line1_2',
        'zipcode' => '22222',
        'state'   => 'MN'
      ]),
      $Address->create([
        'line1'   => 'line1_3',
        'zipcode' => '33333',
        'state'   => 'MN'
      ])
    ];

    $person = $Person->create([
      'firstname'    => 'Test',
      'lastname'     => 'One',
      'phone_number' => '5551234'
    ]);

    foreach ($addresses as $address)
    {
      $person->add_to_addresses($address);
    }

    $result = $person->save();

    return ['person' => $person, 'result' => $result];
  }

  // person with no addresses
  static function hasmany_save_4()
  {
    global $Person;

    $person = $Person->create([
      'firstname'    => 'Test',
      'lastname'     => 'One',
      'phone_number' => '5551234'
    ]);

    $result = $person->save();

    return ['person' => $person, 'result' => $result];
  }
}

?>
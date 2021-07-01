<?php

namespace tests\associations;

class SetupTestData {


  static function hasone_save_cascade_1()
  {
    global $Employer, $Payor;

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
    global $Employer, $Payor;

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
}

?>
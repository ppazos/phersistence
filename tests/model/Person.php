<?php

namespace CaboLabs\Phersistence\tests\model;

use \CaboLabs\Phersistence\phersistent\PhConstraint as constraints;
use CaboLabs\Phersistence\phersistent\PhCollection;

class Person extends \CaboLabs\Phersistence\phersistent\Phersistent {

  public $firstname = self::TEXT;
  public $lastname = self::TEXT;
  public $phone_number = self::TEXT;

  public $addresses = [PhCollection::class, Address::class];

  function constraints()
  {
    return [
      'phone_number' => [
        constraints::nullable(true)
      ],
      'firstname' => [
        constraints::nullable(false)
      ]
    ];
  }

  // instance functions
  function functions()
  {
    return array(
      'full_name' => function ($ins) {
        return $ins->firstname .' '. $ins->lastname;
      }
    );
  }
}

?>

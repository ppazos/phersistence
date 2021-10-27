<?php

namespace model;

use \phersistent\PhConstraint as constraints;

class Person extends \phersistent\Phersistent {

  public $firstname = self::TEXT;
  public $lastname = self::TEXT;
  public $phone_number = self::TEXT;

  public $addresses = array(\phersistent\PhCollection::class, Address::class);

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

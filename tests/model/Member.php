<?php

namespace CaboLabs\Phersistence\tests\model;

use \CaboLabs\Phersistence\phersistent\PhConstraint as constraints;
use CaboLabs\Phersistence\phersistent\PhCollection;

class Member extends \CaboLabs\Phersistence\phersistent\Phersistent {

  public $name = self::TEXT;
  public $employer = Employer::class;
  public $phones = [PhCollection::class, PhoneNumber::class];

  function constraints()
  {
    return [
      'name' => [
        constraints::maxLength(10)
      ]
    ];
  }
}

?>

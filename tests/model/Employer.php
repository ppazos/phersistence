<?php

namespace CaboLabs\Phersistence\tests\model;

class Employer extends \CaboLabs\Phersistence\phersistent\Phersistent {

  public $company = self::TEXT;
  public $ein = self::TEXT;
  public $payor = Payor::class;
  public $address = Address::class;
  public $contact = Person::class;

}

?>

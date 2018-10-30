<?php

namespace model;

class Employer extends \phersistent\Phersistent {

  public $company = self::TEXT;
  public $ein = self::TEXT;
  public $payor = Payor::class;
  public $addres = Address::class;
  public $contact = Person::class;

}

?>

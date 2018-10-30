<?php

namespace model;

class Person extends \phersistent\Phersistent {

  public $firstname = self::TEXT;
  public $lastname = self::TEXT;
  public $phone_number = self::TEXT;

}

?>

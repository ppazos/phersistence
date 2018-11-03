<?php

namespace model;

class Person extends \phersistent\Phersistent {

  public $firstname = self::TEXT;
  public $lastname = self::TEXT;
  public $phone_number = self::TEXT;

  function __construct()
  {
    // functions to be injected on instances
    // $ins is the instance in the context of the method call
    $this->__functions['full_name'] = function ($ins) {
      return $ins->firstname .' '. $ins->lastname;
    };
  }
}

?>

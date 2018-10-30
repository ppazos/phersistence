<?php

namespace model;

class Address extends \phersistent\Phersistent {

  public $line1 = self::TEXT;
  public $line2 = self::TEXT;
  public $zipcode = self::TEXT;
  public $state = self::TEXT;

}

?>

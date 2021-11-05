<?php

namespace CaboLabs\Phersistence\tests\model;

class Address extends \CaboLabs\Phersistence\phersistent\Phersistent {

  public $line1 = self::TEXT;
  public $line2 = self::TEXT;
  public $zipcode = self::TEXT;
  public $state = self::TEXT;

}

?>

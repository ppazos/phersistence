<?php

namespace CaboLabs\Phersistence\tests\model;

class TestSObject extends \CaboLabs\Phersistence\phersistent\Phersistent {

  public $num = self::INT;
  public $sobject = self::SOBJECT;

  public $table = 'test_sobject';
}

?>

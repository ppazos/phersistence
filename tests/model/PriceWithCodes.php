<?php

namespace CaboLabs\Phersistence\tests\model;

use \CaboLabs\Phersistence\phersistent\PhConstraint as constraints;

class PriceWithCodes extends \CaboLabs\Phersistence\phersistent\Phersistent {

  public $price = self::FLOAT;
  public $codes = self::SARRAY;
  public $not_null_codes = self::SARRAY;

  function constraints()
  {
    return [
      'not_null_codes' => [
        constraints::nullable(false)
      ]
    ];
  }
  
}

?>

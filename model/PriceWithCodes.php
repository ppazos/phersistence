<?php

namespace model;

use \phersistent\PhConstraint as constraints;

class PriceWithCodes extends \phersistent\Phersistent {

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

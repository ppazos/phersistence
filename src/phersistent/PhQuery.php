<?php

namespace CaboLabs\Phersistence\phersistent;

use CaboLabs\Phersistence\phersistent\PhAndCond as andCond;
use CaboLabs\Phersistence\phersistent\PhNotCond as notCond;
use CaboLabs\Phersistence\phersistent\PhOrCond as orCond;

class PhQuery {
  static function And($condAnd = [])
  {
    return andCond::evaluate_and($condAnd);
  }
  
  static function Or($condOr = [])
  {
    return orCond::evaluate_or($condOr);
  }
  
  static function Not($condNot = [])
  {
    return notCond::evaluate_not($condNot);
  }
}
?>
<?php

namespace CaboLabs\Phersistence\phersistent;

use CaboLabs\Phersistence\phersistent\PhAndCond as andCond;
use CaboLabs\Phersistence\phersistent\PhNotCond as notCond;
use CaboLabs\Phersistence\phersistent\PhOrCond as orCond;

class PhQuery {  
  static function and($condAnd)
  {
    $ph = new Phersistent;
    $alias = '';
    return andCond::evaluate_and($alias, $condAnd);
  }
  
  static function or($condOr)
  {
    $alias = '';
    return orCond::evaluate_or($alias, $condOr);
  }
  
  static function not($condNot = [])
  {
    $alias = '';
    return notCond::evaluate_not($alias, $condNot);
  }
}
?>
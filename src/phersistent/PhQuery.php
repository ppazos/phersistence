<?php

namespace CaboLabs\Phersistence\phersistent;

use CaboLabs\Phersistence\phersistent\PhAndCond as andCond;
use CaboLabs\Phersistence\phersistent\PhNotCond as notCond;
use CaboLabs\Phersistence\phersistent\PhOrCond as orCond;

class PhQuery {
  static function and($conds)
  {
    // $ph = new Phersistent;
    // $alias = '';
    // return andCond::evaluate_and($alias, $conds);
    return new PhAndCond($conds);
  }

  static function or($conds)
  {
    //$alias = '';
    //return orCond::evaluate_or($alias, $condOr);
    return new PhOrCond($conds);
  }

  static function not($cond)
  {
    // $alias = '';
    // return notCond::evaluate_not($alias, $condNot);
    return new PhNotCond($cond);
  }
}
?>
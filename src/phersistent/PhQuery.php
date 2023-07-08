<?php

namespace CaboLabs\Phersistence\phersistent;

use CaboLabs\Phersistence\phersistent\PhAndCond as andCond;
use CaboLabs\Phersistence\phersistent\PhNotCond as notCond;
use CaboLabs\Phersistence\phersistent\PhOrCond as orCond;

class PhQuery {  
  public $table_alias;

  public function __construct($class_name= 'x')
  {
    $this->table_alias = $class_name;
  }

  static function and($condAnd = [])
  {
    return andCond::evaluate_and($condAnd, 'p');
  }
  
  static function or($condOr = [])
  {
    return orCond::evaluate_or($condOr,'p');
  }
  
  static function not($condNot = [])
  {
    return notCond::evaluate_not($condNot, 'p');
  }
}
?>
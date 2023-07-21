<?php

namespace CaboLabs\Phersistence\phersistent;

use CaboLabs\Phersistence\phersistent\PhersistentMySQL as e;

class PhNotCond {

  private $conds = [];

  public function __construct($conds = [])
  {
    // conds no sea vacio
    $this->conds = $conds;
  }

  public function eval($alias)
  {
    $gob_query_not = '';
    $gob_query_not .= 'NOT (';

    foreach ($this->conds as $value)
    {
      $gob_query_not .= (!is_array($value)) ? $value->eval($alias) : e::get_single_expression($alias, $value);
    }
    $gob_query_not .= ')';
    return $gob_query_not;
  }
}
?>
<?php

namespace CaboLabs\Phersistence\phersistent;

use CaboLabs\Phersistence\phersistent\PhersistentMySQL as e;

class PhOrCond {

  private $conds = [];

  public function __construct($conds = [])
  {
    // conds no sea vacio
    $this->conds = $conds;
  }

  public function eval($alias)
  {
    $last = end($this->conds);
    $gob_query_or = '';
    $gob_query_or .= '(';

    foreach ($this->conds as $value)
    {
      $gob_query_or .= (!is_array($value)) ? $value->eval($alias) : e::get_single_expression($alias, $value);
      
      $gob_query_or .= ($last !== $value) ? " OR " : "";
    }
    $gob_query_or .= ')';
    return $gob_query_or;
  }
}
?>
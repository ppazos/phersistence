<?php

namespace CaboLabs\Phersistence\phersistent;
use CaboLabs\Phersistence\phersistent\PhersistentMySQL as e;

class PhAndCond {

  private $conds = [];

  public function __construct($conds = [])
  {
    $this->conds = $conds;
  }

  public function eval($alias)
  {
    $last = end($this->conds);
    $gob_query_and = '';
    $gob_query_and .= '(';

    foreach ($this->conds as $value)
    {
      $gob_query_and .= (!is_array($value)) ? $value->eval($alias) : e::get_single_expression($alias, $value);

      $gob_query_and .= ($last !== $value) ? " AND " : "";
    }
    $gob_query_and .= ')';

    return $gob_query_and;
  }
}
?>
<?php

namespace CaboLabs\Phersistence\phersistent\query;

use CaboLabs\Phersistence\phersistent\PhersistentMySQL as e;

class PhAndCond {

  private $conds = [];

  public function __construct($conds = [])
  {
    if (count($conds) < 2) throw new \Exception("Conditions should be at least 2 and there are ". count($conds));
    $this->conds = $conds;
  }

  public function eval($alias)
  {
    $gob_query_and = '(';

    $count = count($this->conds);

    foreach ($this->conds as $i => $cond)
    {
      $gob_query_and .= (!is_array($cond)) ? $cond->eval($alias) : e::get_single_expression($alias, $cond);

      $gob_query_and .= ($i+1 < $count) ? " AND " : "";
    }

    $gob_query_and .= ')';

    return $gob_query_and;
  }
}
?>
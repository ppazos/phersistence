<?php

namespace CaboLabs\Phersistence\phersistent\query;

use CaboLabs\Phersistence\phersistent\PhersistentMySQL as e;

class PhNotCond {

  private $cond;

  public function __construct($cond)
  {
    // conds no sea vacio
    $this->cond = $cond;
  }

  public function eval($alias)
  {
    $gob_query_not = 'NOT (';

    if (is_array($this->cond))
    {
      $gob_query_not .= e::get_single_expression($alias, $this->cond);
    }
    else
    {
      $gob_query_not .= $this->cond->eval($alias);
    }

    $gob_query_not .= ')';
    return $gob_query_not;
  }
}
?>
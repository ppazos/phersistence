<?php

namespace CaboLabs\Phersistence\phersistent;

use CaboLabs\Phersistence\phersistent\PhersistentMySQL as c;

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
      if (!is_array($value))
      {
        $gob_query_not .= $value->eval($alias);
      }
      else
      {
        $gob_query_not .= c::get_single_expression($alias, $value);
      }
    }
    $gob_query_not .= ')';
    return $gob_query_not;
  }
}
?>
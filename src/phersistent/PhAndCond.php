<?php

namespace CaboLabs\Phersistence\phersistent;
use CaboLabs\Phersistence\phersistent\PhersistentMySQL as c;

class PhAndCond {

  private $conds = [];

  public function __construct($conds = [])
  {
    // conds no sea vacio
    $this->conds = $conds;
  }

  public function eval($alias)
  {
    $i = count($this->conds);
    $x = 1;
    $gob_query_and = '';
    $gob_query_and .= '(';

    foreach ($this->conds as $value)
    {
      if ($x < $i)
      {
        if (!is_array($value))
        {
          $gob_query_and .= $value->eval($alias). " AND ";
        }
        else
        {
         $gob_query_and .= c::get_single_expression($alias, $value). " AND ";
        }
      }
      else
      {
        if (!is_array($value))
        {
          $gob_query_and .= $value->eval($alias);
        }
        else
        {
          $gob_query_and .= c::get_single_expression($alias, $value);
        }
      }
      $x++;
    }
    $gob_query_and .= ')';

    return $gob_query_and;
  }
}
?>
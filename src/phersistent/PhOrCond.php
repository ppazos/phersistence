<?php

namespace CaboLabs\Phersistence\phersistent;

use CaboLabs\Phersistence\phersistent\PhersistentMySQL as c;

class PhOrCond {

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
    $gob_query_or = '';
    $gob_query_or .= '(';

    foreach ($this->conds as $value)
    {
      if ($x < $i)
      {
        if (!is_array($value))
        {
          $gob_query_or .= $value->eval($alias) . " OR ";
        }
        else
        {
          $gob_query_or .= c::get_single_expression($alias, $value) . " OR ";
        }
      }
      else
      {
        if (!is_array($value))
        {
          $gob_query_or .= $value->eval($alias);
        }
        else
        {
        $gob_query_or .= c::get_single_expression($alias, $value);
        }
      }
      $x++;
    }
    $gob_query_or .= ')';
    return $gob_query_or;
  }
}
?>
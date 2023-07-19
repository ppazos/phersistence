<?php

namespace CaboLabs\Phersistence\phersistent;

use CaboLabs\Phersistence\phersistent\PhersistentMySQL as c;

class PhOrCond {

  public static function evaluate_or($alias, $conds)
  {
    $i = count($conds);
    $x = 1;
    $gob_query_or = '';
    $gob_query_or .= '(';
    
    foreach ($conds as $value)
    {
      if ($x < $i)
      {
        if (!is_array($value))
        {
          $gob_query_or .= $value . " OR ";
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
          $gob_query_or .= $value;
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
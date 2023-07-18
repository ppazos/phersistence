<?php

namespace CaboLabs\Phersistence\phersistent;
use CaboLabs\Phersistence\phersistent\PhersistentMySQL as c;

class PhAndCond {
  
  public static function evaluate_and($conds)
  {
    $i = count($conds);
    $x = 1;
    $gob_query_and = '';
    $gob_query_and .= '(';

    foreach ($conds as $value)
    {
      if ($x < $i)
      {
        if (!is_array($value))
        {
          $gob_query_and .= $value. " AND ";
        }
        else
        {
         $gob_query_and .= c::get_single_expression2($value). " AND ";
        }
      }
      else
      {
        if (!is_array($value))
        {
          $gob_query_and .= $value;
        }
        else
        {
          $gob_query_and .= c::get_single_expression2($value);
        }
      }
      $x++;
    }
    $gob_query_and .= ')';

    return $gob_query_and;
  }
}
?>
<?php

namespace CaboLabs\Phersistence\phersistent;

class PhAndCond {
  
  public static function evaluate_and($conds)
  {
    $gob_query_and = "";
    if (is_array($conds))
    {
      $i = count($conds);
      $x = 1;
      $gob_query_and = '(';
      foreach ($conds as $value)
      {
        $value_2 = isset($value[2]) ? " ". $value[2] : null;
        $value_1 = isset($value[1]) ? $value[1] : null;
        
        if ($x < $i)
        {
          $gob_query_and .= $value[0] ." ". $value_1 . $value_2 . " AND ";
        }
        else
        {
          $gob_query_and .= $value[0] ." ". $value_1 . $value_2;
        }
        $x++;
      }
      $gob_query_and .= ')';
    }
    else 
    {
      $gob_query_and = $conds;
    }
    return $gob_query_and;
  }
}
?>
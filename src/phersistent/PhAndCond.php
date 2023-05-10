<?php

namespace CaboLabs\Phersistence\phersistent;

class PhAndCond {
  
  public static function evaluate_and($conds = [])
  {
    $i = count($conds);
    $x = 1;
    $gob_query_and = '(';
    foreach ($conds as $value)
    {
      $value[2] ?? " ";
      
      if ($x < $i)
      {
        $gob_query_and .= $value[0] ." ". $value[1] ." '". $value[2] . "' AND ";
      }
      else
      {
        $gob_query_and .= $value[0] ." ". $value[1] ." '". $value[2] ."'";
      }
      $x++;
    }
    $gob_query_and .= ')';
    return $gob_query_and;
  }
}
?>
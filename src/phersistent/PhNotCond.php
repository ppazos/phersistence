<?php

namespace CaboLabs\Phersistence\phersistent;

class PhNotCond {
 
  public static function evaluate_not($conds)
  {
    $gob_query_not = 'NOT (';
    foreach ($conds as $value)
    {
      $value_2 = isset($value[2]) ? " ". $value[2] : null;
      $value_1 = isset($value[1]) ? $value[1] : null;
  
      $gob_query_not .= $value[0] ." ". $value_1 . $value_2;
    }
    $gob_query_not .= ')';
    return $gob_query_not;
  }
}
?>
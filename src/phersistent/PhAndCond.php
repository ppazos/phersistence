<?php

namespace CaboLabs\Phersistence\phersistent;

class PhAndCond {
  
  public static function evaluate_and($conds = [])
  {
    $gob_query_and = '(';
    foreach ($conds as $value)
    {
      if (!end($conds))
      {
        $gob_query_and .= $value[0] ." ". $value[1] ." ". $value[2] ?? "" . " AND ";
      }
      else
      {
        $gob_query_and .= $value[0] ." ". $value[1] ." ". $value[2] ?? "" ." )";
      }
    }
    return $gob_query_and;
  }
}
?>
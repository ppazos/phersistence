<?php

namespace CaboLabs\Phersistence\phersistent;

class PhNotCond {
 
  public static function evaluate_not($operator, $conds)
  {
    $gob_query_not = $operator ?? 'AND' . ' NOT (';
    foreach ($conds as $value)
    {
      if (!end($conds))
      {
        $gob_query_not .= $value[0] ." ". $value[1] ." ". $value[2] ?? "" . " AND ";
      }
      else
      {
        $gob_query_not .= $value[0] ." ". $value[1] ." ". $value[2] ?? "" ." )";
      }
    }
    return $gob_query_not;
  }
}
?>
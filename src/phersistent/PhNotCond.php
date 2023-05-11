<?php

namespace CaboLabs\Phersistence\phersistent;

class PhNotCond {
 
  public static function evaluate_not($operator, $conds)
  {
    $i = count($conds);
    $x = 1;
    $gob_query_not = $operator ?? 'AND' . ' NOT (';
    foreach ($conds as $value)
    {
      $value_2 = $value[2] ? " '". $value[2]. "'" : $value[2] = null;
      
      if ($x < $i)
      {
        $gob_query_not .= $value[0] ." ". $value[1] . $value_2 . " AND ";
      }
      else
      {
        $gob_query_not .= $value[0] ." ". $value[1] . $value_2;
      }
      $x++;
    }
    $gob_query_not .= ')';
    return $gob_query_not;
  }
}
?>
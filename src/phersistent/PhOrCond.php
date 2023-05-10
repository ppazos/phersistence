<?php

namespace CaboLabs\Phersistence\phersistent;

class PhOrCond {

  public static function evaluate_or($conds)
  {
    $i = count($conds);
    $x = 1;
    $gob_query_or = '(';
    foreach ($conds as $value)
    {
      $value[2] ?? " ";
      
      if ($x < $i)
      {
        $gob_query_or .= $value[0] ." ". $value[1] ." ". $value[2] . " OR ";
      }
      else
      {
        $gob_query_or .= $value[0] ." ". $value[1] ." ". $value[2] ." )";
      }
      $x++;
    }
    $gob_query_or .= ')';
    return $gob_query_or;
  }
}
?>
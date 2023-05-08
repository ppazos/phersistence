<?php

namespace CaboLabs\Phersistence\phersistent;

class PhOrCond {

  public static function evaluate_or($conds)
  {
    $gob_query_or = '(';
    foreach ($conds as $value)
    {
      if (!end($conds))
      {
        $gob_query_or .= $value[0] ." ". $value[1] ." ". $value[2] ?? "" . " OR ";
      }
      else
      {
        $gob_query_or .= $value[0] ." ". $value[1] ." ". $value[2] ?? "" ." )";
      }
    }
    return $gob_query_or;
  }
}
?>
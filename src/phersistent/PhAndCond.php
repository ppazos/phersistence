<?php

namespace CaboLabs\Phersistence\phersistent;
use CaboLabs\Phersistence\phersistent\PhersistentMySQL as c;

class PhAndCond {
  
  public static function evaluate_and($conds, $table_alias)
  {
    $i = count($conds);
    $x = 1;
    $gob_query_and = '(';

    foreach ($conds as $value)
    {
      if ($x < $i)
      {
        $gob_query_and .= c::get_single_expression($table_alias, $value). " AND ";
      }
      else
      {
        $gob_query_and .= c::get_single_expression($table_alias, $value);
      }
      $x++;
    }
    $gob_query_and .= ')';

    return $gob_query_and;
  }
}
?>
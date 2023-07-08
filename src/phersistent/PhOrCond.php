<?php

namespace CaboLabs\Phersistence\phersistent;

use CaboLabs\Phersistence\phersistent\PhersistentMySQL as c;

class PhOrCond {

  public static function evaluate_or($conds, $table_alias)
  {
    $i = count($conds);
    $x = 1;
    $gob_query_or = '(';
    foreach ($conds as $value)
    {      
      if ($x < $i)
      {
        $gob_query_or .= c::get_single_expression($table_alias, $value) . " OR ";
      }
      else
      {
        $gob_query_or .= c::get_single_expression($table_alias, $value);
      }
      $x++;
    }
    $gob_query_or .= ')';
    return $gob_query_or;
  }
}
?>
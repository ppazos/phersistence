<?php

namespace CaboLabs\Phersistence\phersistent;
use CaboLabs\Phersistence\phersistent\PhersistentMySQL as c;

class PhAndCond {
  
  public static function evaluate_and($conds, $table_alias)
  {
    $i = count($conds);
    $x = 1;
    $gob_query_and = '';
    $gob_query_and .= '(';

    foreach ($conds as $key => $value)
    {
      if (!is_array($value))
      {
        throw new \Exception("This must be an array");
      }

      if ($x < $i)
      {
        $gob_query_and .= c::get_single_expression2($table_alias, $value). " AND ";
      }
      else
      {
        $gob_query_and .= c::get_single_expression2($table_alias, $value);
      }
      $x++;
    }
    $gob_query_and .= ')';

    return $gob_query_and;
  }
}
?>
<?php

namespace CaboLabs\Phersistence\phersistent;

use CaboLabs\Phersistence\phersistent\PhersistentMySQL as c;

class PhNotCond {
 
  public static function evaluate_not($conds, $table_alias)
  {
    $gob_query_not = '';
    $gob_query_not = 'NOT (';
    
    foreach ($conds as $value)
    { 
      if (!is_array($value))
      {
        throw new \Exception("This must be an array");
      }
      
      $gob_query_not .= c::get_single_expression2($table_alias, $value);
    }
    $gob_query_not = ')';
    return $gob_query_not;
  }
}
?>
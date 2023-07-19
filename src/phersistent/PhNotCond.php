<?php

namespace CaboLabs\Phersistence\phersistent;

use CaboLabs\Phersistence\phersistent\PhersistentMySQL as c;

class PhNotCond {
 
  public static function evaluate_not($alias, $conds)
  {
    $gob_query_not = '';
    $gob_query_not .= 'NOT (';
    
    foreach ($conds as $value)
    { 
      if (!is_array($value))
        {
          $gob_query_not .= $value;
        }
        else
        {      
          $gob_query_not .= c::get_single_expression($alias, $value);
        }
    }
    $gob_query_not .= ')';
    return $gob_query_not;
  }
}
?>
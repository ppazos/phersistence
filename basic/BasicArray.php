<?php

namespace basic;

class BasicArray {

  /**
   * removes empty values on multidimensional arrays
   */
  // static function removeEmptyValues($arr)
  // {
  //   return is_array($arr) ? array_filter($arr,
  //       function (& $value) { return $value = BasicArray::removeEmptyValues($value); }
  //   ) : $arr;
  // }

  static function removeEmptyValues($arr)
  {
    if (is_array($arr))
    {
      echo "is_array\n";
      $filtered = array_filter($arr, function (& $value) { $value = BasicArray::removeEmptyValues($value); return true; });
      var_dump($filtered);
      return $filtered;
    }
    else
    {
      echo "is not array ${arr}\n";
      return $arr;
    }
  }

  static function sort_file_paths_by_date($arr)
  { 
    //Given an array containing file paths retuns the array in order by date.
    usort($arr, function($a, $b) {
      return filemtime($a) < filemtime($b);
    });
    
    return $arr;
  }

  static function equals($arr1, $arr2)
  {
    if (is_null($arr1) && !is_null($arr2)) return false;
    if (!is_null($arr1) && is_null($arr2)) return false;
    if (is_null($arr1) && is_null($arr2)) return true;

    // both are not null

    if (count($arr1) != count($arr2)) return false;

    sort($arr1);
    sort($arr2);
    
    return $arr1 == $arr2;
  }

  //flatten two dimensional array > one dimensional array
  static function flatten($array) 
  {
    $return = array();
    array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
    return $return;
  }
}

?>

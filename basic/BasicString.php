<?php

namespace basic;

class BasicString {

  static function startsWith($haystack, $needle)
  {
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
  }

  static function endsWith($haystack, $needle)
  {
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }
    return (substr($haystack, -$length) === $needle);
  }

  static function removePrefix($str, $prefix)
  {
    if (substr($str, 0, strlen($prefix)) == $prefix) // if it is prefix
    {
      $str = substr($str, strlen($prefix));
    }

    return $str;
  }

  static function removeSuffix($str, $suffix)
  {
    if (self::endsWith($str, $suffix))
    {
      return substr($str, 0, strlen($str)-strlen($suffix));
    }
    return $str;
  }
}

?>

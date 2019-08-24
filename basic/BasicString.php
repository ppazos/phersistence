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

  static function generate_uuid()
  {
  	return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
  		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
  		mt_rand( 0, 0xffff ),
  		mt_rand( 0, 0x0fff ) | 0x4000,
  		mt_rand( 0, 0x3fff ) | 0x8000,
  		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
  	);
  }

  // Random string of desired length from full alphabet
  function random($length = 10)
  {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++)
    {
      $randomString .= $characters[mt_rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }

  // Random string of desired length, max 32, composed of HEX digits only
  static function random_hex($length = 10)
  {
    if ($length > 32) $length = 32;
    return substr(str_shuffle(md5(microtime())), 0, $length);
  }

  // Transforms ABc into a_bc
  static function camel_to_snake($input)
  {
    preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
    $ret = $matches[0];
    foreach ($ret as &$match) {
      $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
    }
    return implode('_', $ret);
  }

  static function snake_to_camel($input)
  {
    // TBD
  }
}



?>

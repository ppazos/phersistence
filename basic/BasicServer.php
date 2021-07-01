<?php

namespace basic;

class BasicServer
{

  static function is_localhost()
  {
    if(isset($_SERVER['SERVER_ADDR']))
    {
      if(in_array($_SERVER['SERVER_ADDR'], array('127.0.0.1', '::1', 'localhost')) || \basic\BasicString::startsWith($_SERVER['SERVER_ADDR'], '192.168.'))
      {
        $local_host = true;
      }
      else
      {
        $local_host = false;
      }
    }
    else
    {
      $local_host = true;
    }

    return $local_host;
  }
}
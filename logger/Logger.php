<?php

namespace logger;

class Logger {

  static $on = true;
  static $force = false;

  /*
  TODO: configure log output (file,screen,out)
  TODO: alloow including stack trace
  TODO: make timestamp optional
  */
  static function log($message)
  {
    if (!self::$on) return;

    if (php_sapi_name() === 'cli')
      echo date("Y-M-d H:i:sP") .' '. $message . PHP_EOL;
    else
    {
      // avoid output logs if request is ajax, this breaks the json respose
      $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
      if (self::$force || !$isAjax)
      {
        echo '<!-- '. date("Y-M-d H:i:sP") .' '. $message .' -->'. PHP_EOL;
      }
    }
  }
}
?>

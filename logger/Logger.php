<?php

namespace logger;

class Logger {
  /*
  TODO: configure log output (file,screen,out)
  TODO: alloow including stack trace
  TODO: make timestamp optional
  */
  static function log($message)
  {
    if (php_sapi_name() === 'cli')
      echo date("Y-M-d H:i:sP") .' '. $message . PHP_EOL;
    else
      echo '<!-- '. date("Y-M-d H:i:sP") .' '. $message .' -->'. PHP_EOL;
  }
}

?>

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
    echo date("Y-M-d H:i:sP") .' '. $message . PHP_EOL;
  }
}
?>

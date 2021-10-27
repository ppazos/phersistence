<?php

namespace phtest;

abstract class PhTestCase {

   // path to the test case
   private $path;

   // PhTestSuite
   private $suite;

   // test being executed
   private $current_test;

   function __construct($suite, $path)
   {
      $this->suite = $suite;
      $this->path = $path;
   }

   // invoked before one test is executed in this test case
   public function before_test($test_name)
   {
      $this->current_test = $test_name;
   }

   // invoked after one test is executed in this test case, passing the total output
   public function after_test($test_name, $output)
   {
      $this->current_test = NULL;
      $this->suite->report_output(get_class($this), $test_name, $output);
   }

   public function assert($cond, $msg = '', $params = array())
   {
      // TODO: obtener un mensaje que diga mas, linea, clase y
      //       metodo donde se intenta verificar la condicion
      //if (!$cond) $this->suite->report('error');
      if (!$cond)
      {
         // http://php.net/manual/en/function.debug-backtrace.php
         $trace = debug_backtrace(0);
         array_shift($trace); // removes the call to assert()
         array_pop($trace); // removes the call to cli.run_cases()

         $this->suite->report_assert(get_class($this), $this->current_test, 'ERROR', $msg, $trace, $params);
      }
      else
      {
         // tengo que mostrar los tests correctos
         $this->suite->report_assert(get_class($this), $this->current_test, 'OK', $msg);
      }
   }

   public function assert_equals($value1, $value2, $msg = '')
   {
      if ($value1 != $value2)
      {
         $trace = debug_backtrace(0);
         array_shift($trace); // removes the call to assert()
         array_pop($trace); // removes the call to cli.run_cases()

         $msg = 'Value 1 ('. $value1 .') is different to value 2 ('. $value2 .') '. $msg;

         $this->suite->report_assert(get_class($this), $this->current_test, 'ERROR', $msg, $trace, array());
      }
      else
      {
         // tengo que mostrar los tests correctos
         $this->suite->report_assert(get_class($this), $this->current_test, 'OK', $msg);
      }
   }

   public function assert_equals_float($value1, $value2, $msg = '')
   {
      if (round($value1, 3) != round($value2, 3))
      {
         $trace = debug_backtrace(0);
         array_shift($trace); // removes the call to assert()
         array_pop($trace); // removes the call to cli.run_cases()

         $msg = 'Value 1 ('. $value1 .') is different to value 2 ('. $value2 .') '. $msg;

         $this->suite->report_assert(get_class($this), $this->current_test, 'ERROR', $msg, $trace, array());
      }
      else
      {
         // tengo que mostrar los tests correctos
         $this->suite->report_assert(get_class($this), $this->current_test, 'OK', $msg);
      }
   }
}

?>

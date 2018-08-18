<?php
abstract class PhTestCase {

   // TestSuite
   private $suite;

   function __construct($suite)
   {
      $this->suite = $suite;
   }

   public function assert($cond, $msg = 'Error', $params = array())
   {
      // TODO: obtener un mensaje que diga mas, linea, clase y
      //       metodo donde se intenta verificar la condicion
      //if (!$cond) $this->suite->report('error');
      if (!$cond)
      {
         // http://php.net/manual/en/function.debug-backtrace.php

         ob_start();
         debug_print_backtrace(); // Stack de llamadas que resultaron en un test que falla
         $trace = ob_get_contents(); // Trace es lo mismo que moreInfo pero abajo se procesa para mostrar solo el trace que importa.
         $moreInfo = ob_get_contents(); // Todos los echos y prints que se pudieron hacer
         ob_end_clean();
         // Se quita la llamada a este metodo de el stack (assert)
         $pos = strpos($trace, "\n#1  ");
         if ($pos !== false)
         {
            $trace = substr($trace, $pos);
         }

         // TODO: hay que remover las ultimas lineas que son llamadas del framework
         /*
          * #4  CoreController->testAppAction(Array ()) called at [C:\wamp\www\YuppPHPFramework\core\mvc\core.mvc.YuppController.class.php:59]
#5  YuppController->__call(testApp, Array ())
#6  CoreController->testApp() called at [C:\wamp\www\YuppPHPFramework\core\routing\core.routing.Executer.class.php:163]
#7  Executer->execute() called at [C:\wamp\www\YuppPHPFramework\core\web\core.web.RequestManager.class.php:158]
#8  RequestManager::doRequest() called at [C:\wamp\www\YuppPHPFramework\index.php:94]
          */

         $this->suite->report(get_class($this), 'ERROR', $msg, $trace, $moreInfo, $params);
      }
      else
      {
         // tengo que mostrar los tests correctos
         $this->suite->report(get_class($this), 'OK', $msg);
      }
   }

   // A implementar por las subclases
   public abstract function run();
}
?>

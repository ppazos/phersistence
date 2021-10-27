<?php

namespace phtest;

use \basic\BasicString as basicString;

class PhTestRun {

   // root folder where all the test suites have their own folder
   private $test_suite_root = './tests';

   private $reports = array();

   private $after_each_test_function;

   public function init($test_suite_root = './tests')
   {
      if (!is_dir($test_suite_root))
      {
         echo "Folder $test_suite_root doesn't exist";
         exit;
      }

      $this->test_suite_root = $test_suite_root;
   }

   public function run_all()
   {
      $root_dir = dir($this->test_suite_root);

      while (false !== ($test_suite = $root_dir->read()))
      {
         if (!is_dir($this->test_suite_root . DIRECTORY_SEPARATOR . $test_suite) ||
             in_array($test_suite, array('.', '..', 'data'))) continue;

         $path = $this->test_suite_root . DIRECTORY_SEPARATOR . $test_suite;

         $suite_dir = dir($path);

         $test_cases = array();

         // $test_case is a class name
         while (false !== ($test_case = $suite_dir->read()))
         {
            // only php files are valid test cases
            if (preg_match('/\.php$/', $test_case))
            {
               $test_case_path = $path.'/'.$test_case;

               $namespaced_class = substr(str_replace('/', '\\', $test_case_path), 0, -4);
               
               if (is_file($test_case_path))
               {
                  $test_cases[$namespaced_class] = $test_case_path;
               }
            }
         }

         $suite_dir->close();

         $phsuite = new PhTestSuite($test_suite, $test_cases);
         $phsuite->run($this->after_each_test_function);
         $this->reports[] = $phsuite->get_reports();
      }

      // TODO: should close all directory handles!
      $root_dir->close();
   }

   public function run_suite($suite)
   {
      // runs all cases in the suite
      $this->run_cases($suite);
   }

   public function run_suites(...$suites)
   {
      // TODO
   }

   public function run_case($suite, $case)
   {
      // TODO

      $path = $this->test_suite_root . DIRECTORY_SEPARATOR . $suite;

      $test_case_path = $path.'/'. $case . '.php';

      $namespaced_class = substr(str_replace('/', '\\', $test_case_path), 0, -4);

      $test_cases = array();
      if (is_file($test_case_path))
      {
         $test_cases[$namespaced_class] = $test_case_path;
      }

      $phsuite = new PhTestSuite($suite, $test_cases);
      $phsuite->run($this->after_each_test_function);
      $this->reports[] = $phsuite->get_reports();
   }

   public function run_cases($suite, ...$cases)
   {
      $path = $this->test_suite_root . DIRECTORY_SEPARATOR . $suite;

      $suite_dir = dir($path);
      
      $test_cases = array();

      // $test_case is a class name
      while (false !== ($test_case = $suite_dir->read()))
      {
         // only php files are valid test cases
         if (preg_match('/\.php$/', $test_case))
         {
            $test_case_path = $path .'/'. $test_case;

            $namespaced_class = substr(str_replace('/', '\\', $test_case_path), 0, -4);
            
            if (is_file($test_case_path) && (empty($cases) || in_array($test_case, $cases)))
            {
               $test_cases[$namespaced_class] = $test_case_path;
            }
         }
      }

      $phsuite = new PhTestSuite($suite, $test_cases);
      $phsuite->run($this->after_each_test_function);
      $this->reports[] = $phsuite->get_reports();
   }

   public function render_reports()
   {
      echo 'Test report: '. PHP_EOL . PHP_EOL;
      
      foreach ($this->reports as $i => $test_suite_reports)
      {
         foreach ($test_suite_reports as $test_case => $reports)
         {
            echo '├── Test case: '. $test_case . PHP_EOL;
            echo '|   |'. PHP_EOL;

            foreach ($reports as $test_function => $report)
            {
               echo '|   ├── Test: '. $test_function . PHP_EOL;

               //print_r($report['asserts']);

               if (isset($report['asserts']))
               {
                  foreach ($report['asserts'] as $assert_report)
                  {
                     if ($assert_report['type'] == 'ERROR')
                     {
                        echo '|   |   |'. PHP_EOL;
                        echo "|   |   └── \033[91mERROR: ". $assert_report['msg'] ."\033[0m". PHP_EOL;
                     }
                     else if ($assert_report['type'] == 'OK')
                     {
                        echo '|   |   |'. PHP_EOL;
                        echo "|   |   └── \033[92mOK: ". $assert_report['msg'] ."\033[0m". PHP_EOL;
                     }
                     else if ($assert_report['type'] == 'EXCEPTION')
                     {
                        echo '|   |   |'. PHP_EOL;
                        echo "|   |   └── \033[94mEXCEPTION: ". $assert_report['msg'] ."\033[0m". PHP_EOL;
                     }
                  }
               }

               if (!empty($report['output']))
               {
                  echo '|   |   |'. PHP_EOL;
                  echo '|   |   └── OUTPUT: '. $report['output'] . PHP_EOL;
               }

               echo '|   |'. PHP_EOL;
            }
         }
      }

      echo PHP_EOL;
   }


   public function get_reports()
   {
      return $this->reports;
   }

   public function after_each_test($callback)
   {
      $this->after_each_test_function = $callback;
   }
}

?>
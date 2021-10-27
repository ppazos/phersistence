<?php

$_BASE = __DIR__ . '/'; // .'/../';

spl_autoload_register(function ($class) {
  global $_BASE;
  //echo $_BASE.str_replace('\\', '/', $class).'.php' . PHP_EOL;
  if (file_exists($_BASE.str_replace('\\', '/', $class).'.php'))
  {
    require_once($_BASE.str_replace('\\', '/', $class).'.php');
  }
});

\logger\Logger::$on = false;
\logger\Logger::$force = false;

$test_db = 'phersistent';

$d = new \drivers\MySQL();
$d->connect('localhost', 'user', 'user1234');
$d->execute('DROP DATABASE IF EXISTS '. $test_db);
$d->execute('CREATE DATABASE '. $test_db);

$ph_db = new \phersistent\PhersistentMySQL('localhost', 'user', 'user1234', $test_db);
$man = new \phersistent\PhersistentDefManager('model', $ph_db);
$d = $ph_db->get_driver();

// generates schema
require_once('db/schema.php');


//print_r($argv);
//print_r($argc);

/*
 * argv[0] -> cli.php
 * argv[1] -> suite (optional)
 * argv[2] -> case (optional)
 * */

if ($argc < 2)
{
   echo 'Missing test_root and test_suite'. PHP_EOL;
   exit;
}


$run = new \phtest\PhTestRun();
$run->init('tests');

// clean the database after each test
$run->after_each_test(function() use ($d) {
   \logger\Logger::$on = false;
   $d->truncate_all_tables();
   \logger\Logger::$on = true;
});




// case or cases specific
if ($argc == 3)
{
   $run->run_case($argv[1], $argv[2]);
}
// suite specified
else if ($argc == 2)
{
   $run->run_suite($argv[1]);
}
// run all
else
{
   $run->run_all();
}

$run->render_reports();

?>
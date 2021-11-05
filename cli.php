<?php

$_BASE = __DIR__ . '/'; // .'/../';


// composer includes
require __DIR__ . '/vendor/autoload.php';


\CaboLabs\PhLogger\PhLogger::$on = false;
\CaboLabs\PhLogger\PhLogger::$force = false;

$test_db = 'phersistent';

$d = new \CaboLabs\Phersistence\drivers\MySQL();
$d->connect('localhost', 'user', 'user1234');
$d->execute('DROP DATABASE IF EXISTS '. $test_db);
$d->execute('CREATE DATABASE '. $test_db);

$ph_db = new \CaboLabs\Phersistence\phersistent\PhersistentMySQL('localhost', 'user', 'user1234', $test_db);
//$man = new \CaboLabs\Phersistence\phersistent\PhersistentDefManager('CaboLabs\\Phersistence\\tests\\model', $ph_db);
$man = new \CaboLabs\Phersistence\phersistent\PhersistentDefManager('tests/model', $ph_db);
$d = $ph_db->get_driver();

// generates schema
require_once('src/db/schema.php');


//print_r($argv);
//print_r($argc);

/*
 * argv[0] -> cli.php
 * argv[1] -> suite (optional)
 * argv[2] -> case (optional)
 * */

 /*
if ($argc < 2)
{
   echo 'Missing test_root and test_suite'. PHP_EOL;
   exit;
}
*/

$run = new \CaboLabs\PhTest\PhTestRun();
$run->init('./tests');

// clean the database after each test
$run->after_each_test(function() use ($d) {
   \CaboLabs\PhLogger\PhLogger::$on = false;
   $d->truncate_all_tables();
   \CaboLabs\PhLogger\PhLogger::$on = true;
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
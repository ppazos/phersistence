<?php

$_BASE = __DIR__ .'/';

//print_r($argv);

$command = $argv[1];

switch ($command)
{
  case "test":
    test();
  break;
  case "schema":
    require_once('tests/schema.php');
  break;
  case "test_ph11":
    require_once('phersistent/test_ph11.php');
  break;
  case "test_ph12":
    require_once('phersistent/test_ph12.php');
  break;
  case "test_amplify":
    require_once('tests/test_amplify.php');
  break;
  default:
    echo "No command";
}

function test()
{
  require_once('tests/test.php');
}

?>

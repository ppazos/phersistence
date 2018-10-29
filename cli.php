<?php

$_BASE = __DIR__ .'/';

//print_r($argv);

$command = $argv[1];

switch ($command)
{
  case "test":
    test();
  break;
  default:
    echo "No command";
}

function test()
{
  require_once('tests/test.php');
}

?>

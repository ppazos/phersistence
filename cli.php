<?php

$_BASE = __DIR__ .'/';

//print_r($argv);

/*
$a = '2342456456456314723';
var_dump(PHP_INT_MAX);
var_dump((int)$a);
$a = $a + 0;
var_dump($a);
*/

if (count($argv) < 2)
{
  echo 'No command'. PHP_EOL;
  exit;
}

$command = $argv[1];

switch ($command)
{
  case "find_by":
    require_once('tests/test_find_by.php');
  break;
  case "test":
    require_once('tests/test.php');
  break;
  case "test_dirty":
    require_once('tests/test_dirty.php');
  break;
  case "sarray":
    require_once('tests/test_serialized_array.php');
  break;
  case "sobject":
    require_once('tests/test_serialized_object.php');
  break;
  case "validations":
    require_once('tests/test_validations.php');
  break;
  case "schema":
    require_once('tests/schema.php');
  break;
  case "test_ph11":
    require_once('tests/test_ph11.php');
  break;
  case "test_ph12":
    require_once('tests/test_ph12.php');
  break;
  case "test_amplify":
    require_once('tests/test_amplify.php');
  break;
  case "test_note_hasmany_note":
    require_once('tests/test_note_hasmany_note.php');
  break;
  default:
    echo "No command";
}

?>

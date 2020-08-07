<?php

spl_autoload_register(function ($class) {
  global $_BASE;
  //echo 'spl_autoload_register '. $class . PHP_EOL;
  //echo $_BASE.str_replace('\\', '/', $class).'.php' . PHP_EOL;

  if (file_exists($_BASE.str_replace('\\', '/', $class).'.php'))
    require_once($_BASE.str_replace('\\', '/', $class).'.php');
});

// SCHEMA

$d = new drivers\MySQL();
$d->connect('localhost', 'user', 'user1234');
$d->select_db('phersistent');

if (!$d->table_exists('number_test'))
{
  $d->create_table('number_test');
  $d->add_column('number_test', 'class', 'varchar(255)', false);
  $d->add_column('number_test', 'deleted', 'boolean', false);
  $d->add_column('number_test', 'number1', 'int', true);
  $d->add_column('number_test', 'number2', 'bigint', true);
  $d->add_column('number_test', 'number3', 'float', true);
  $d->add_column('number_test', 'number4', 'double', true);
}

// MODEL

class NumberTest extends \phersistent\Phersistent {

  public $number1 = self::INT;
  public $number2 = self::LONG;
  public $number3 = self::FLOAT;
  public $number4 = self::DOUBLE;
}

// SETUP

$ph_db = new \phersistent\PhersistentMySQL('localhost', 'user', 'user1234', 'phersistent');
$man = new \phersistent\PhersistentDefManager('', $ph_db);

// TEST

$number_test = $NumberTest->create(array(
  'number1' => 0,
  'number2' => 0,
  'number3' => 0.0,
  'number4' => 0.0
));

$number_test->save();

// get is loading null not zero
$number_test->delete(true);

assert($number_test->get_deleted() == true);

$number_test_get = $NumberTest->get($number_test->get_id());

assert($number_test_get->get_deleted() == true);

//var_dump($number_test_get);
//var_dump($number_test_get->get_number1());

?>

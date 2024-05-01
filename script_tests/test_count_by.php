<?php

$_BASE = __DIR__ . '/';


// composer includes
require __DIR__ . '/../vendor/autoload.php';


// SCHEMA

$d = new \CaboLabs\Phersistence\drivers\MySQL();
$d->connect('localhost', 'user', 'user1234');
$d->select_db('phersistent');

if (!$d->table_exists('number_test'))
{
  $d->create_table('number_test');
  $d->add_column('number_test', 'class',   'varchar(255)', false);
  $d->add_column('number_test', 'deleted', 'boolean', false);
  $d->add_column('number_test', 'number1', 'int', true);
  $d->add_column('number_test', 'number2', 'bigint', true);
  $d->add_column('number_test', 'number3', 'float', true);
  $d->add_column('number_test', 'number4', 'double', true);
  $d->add_column('number_test', 'number5', 'decimal(7,4)', true); // 0.0000 .. 999.9999
}

// MODEL

class NumberTest extends \CaboLabs\Phersistence\phersistent\Phersistent {

  public $number1 = self::INT;
  public $number2 = self::LONG;
  public $number3 = self::FLOAT;
  public $number4 = self::DOUBLE;
  public $number5 = self::DECIMAL;
}

// SETUP

$ph_db = new \CaboLabs\Phersistence\phersistent\PhersistentMySQL('localhost', 'user', 'user1234', 'phersistent');
$man = new \CaboLabs\Phersistence\phersistent\PhersistentDefManager('', $ph_db);

// TEST
$numbers = [
  $NumberTest->create([
    'number1' => 0,
    'number2' => 0,
    'number3' => 0.0,
    'number4' => 0.0,
    'number5' => 0.0
  ]),
  $NumberTest->create([
    'number1' => 1,
    'number2' => 1,
    'number3' => 1.0,
    'number4' => 1.0,
    'number5' => 1.0
  ]),
  $NumberTest->create([
    'number1' => 2,
    'number2' => 2,
    'number3' => 2.0,
    'number4' => 2.0,
    'number5' => 2.0
  ]),
  $NumberTest->create([
    'number1' => 2,
    'number2' => 2,
    'number3' => 2.0,
    'number4' => 2.0,
    'number5' => 18.1818
  ]),
  $NumberTest->create([
    'number1' => 2,
    'number2' => 2,
    'number3' => 2.0,
    'number4' => 2.0,
    'number5' => 999.9999 // NOTE: if the value is greater than this or has more decimal points, MySQL will return an our of range error.
  ])
];

foreach ($numbers as $number)
{
  $number->save();
}

$count = $NumberTest->countBy([
  ['number1', '>', 0]
]);

var_dump($count);
if (!is_int($count))
{
  throw new Exception("count is not int");
}

//assert(is_int($count), "error"); // this only runs if the php assert is enabled!

// cleanup
foreach ($NumberTest->listAll($NumberTest->count()) as $n)
{
  $n->delete();
}

//var_dump($number_test_get);
//var_dump($number_test_get->get_number1());

?>

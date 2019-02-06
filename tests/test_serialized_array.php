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
$d->connect('localhost', 'root', 'toor');
$d->select_db('phersistent');

if (!$d->table_exists('test_sarray'))
{
  $d->create_table('test_sarray'); // adds the id column
  $d->add_column('test_sarray', 'class', 'varchar(255)', false);
  $d->add_column('test_sarray', 'deleted', 'boolean', false);
  $d->add_column('test_sarray', 'num', 'int', true);
  $d->add_column('test_sarray', 'sarray', 'varchar(255)', true);
}

// MODEL

class TestSarray extends \phersistent\Phersistent {

  public $num = self::INT;
  public $sarray = self::SARRAY;

  public $table = 'test_sarray';
}


// SETUP
$ph_db = new \phersistent\PhersistentMySQL('localhost', 'root', 'toor', 'phersistent');
$man = new \phersistent\PhersistentDefManager('model', $ph_db);

// SARRAY TEST
/*
$ins = $TestSarray->create(array('num'=>123, 'sarray'=>array(123, true, 'hola')));
$ins->save();
$sarray = $ins->getSarray();

echo PHP_EOL;
print_r($sarray);
echo PHP_EOL;
*/

$insl = $TestSarray->get(1);
$sarray = $insl->getSarray();

$insl->pushToSarray('newval');
$insl->save();
$sarray = $insl->getSarray();

echo PHP_EOL;
print_r($sarray);
echo PHP_EOL;

$insl->delFromSarray('newval');
$insl->save();
$sarray = $insl->getSarray();

echo PHP_EOL;
print_r($sarray);
echo PHP_EOL;

if ($insl->hasValueInSarray('123')) echo 'Has 123!'. PHP_EOL;

//$insl->delete();

exit;

?>

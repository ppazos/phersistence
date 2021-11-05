<?php

spl_autoload_register(function ($class) {
  global $_BASE;
  //echo 'spl_autoload_register '. $class . PHP_EOL;
  //echo $_BASE.str_replace('\\', '/', $class).'.php' . PHP_EOL;

  if (file_exists($_BASE.str_replace('\\', '/', $class).'.php'))
    require_once($_BASE.str_replace('\\', '/', $class).'.php');
});

// SCHEMA

$d = new \CaboLabs\Phersistence\drivers\MySQL();
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

class TestSarray extends \CaboLabs\Phersistence\phersistent\Phersistent {

  public $num = self::INT;
  public $sarray = self::SARRAY;

  public $table = 'test_sarray';
}


// SETUP
$ph_db = new \CaboLabs\Phersistence\phersistent\PhersistentMySQL('localhost', 'root', 'toor', 'phersistent');
$man = new \CaboLabs\Phersistence\phersistent\PhersistentDefManager('model', $ph_db);

// SARRAY TEST


$ins = $TestSarray->create(array('num'=>123, 'sarray'=>array(123, true, 'hola')));
$ins->save();
$sarray = $ins->get_sarray();

echo PHP_EOL;
print_r($sarray);
echo PHP_EOL;

$ins1 = $TestSarray->get($ins->get_id());
$sarray = $ins1->get_sarray();

echo PHP_EOL;
print_r($sarray);
var_dump($sarray);
echo PHP_EOL;

$insl = $TestSarray->get($ins->get_id());
$sarray = $insl->get_sarray();

$insl->push_to_sarray('newval');
$insl->save();
assert ($insl->has_value_in_sarray('newval'));

$sarray = $insl->get_sarray();

echo PHP_EOL;
print_r($sarray);
echo PHP_EOL;


$insl->del_from_sarray('newval');
$insl->save();
assert (!$insl->has_value_in_sarray('newval'));

$sarray = $insl->get_sarray();

echo PHP_EOL;
print_r($sarray);
echo PHP_EOL;

assert ($insl->has_value_in_sarray('123'));

$insl->delete();


// CHECK DEFAULT VALUE FOR JSON ARRAY
$ins = $TestSarray->create();
//$ins->save();
$sarray = $ins->get_sarray();

assert ($sarray == null);
print_r($sarray);

$ins->push_to_sarray('value');
$sarray = $ins->get_sarray();

assert ($sarray != null);
print_r($sarray);

$ins->get_pepe();


echo '+++-------'. PHP_EOL;

// TEST JSON ENCODE/DECODE OPTIONS
$arr = array('1','3','5');
echo json_encode($arr) . PHP_EOL;
print_r (json_decode(json_encode($arr))); // ARRAY SINGLE

$marr = array('1','3', array('5'));
echo json_encode($marr) . PHP_EOL;
print_r (json_decode(json_encode($marr))); // ARRAY MULTIDIM

$msarr = array('1','key'=>'3', array('key'=>'5'));
echo json_encode($msarr) . PHP_EOL;
print_r (json_decode(json_encode($msarr))); // OBJECT

$msarr = array('1','key'=>'3', array('key'=>'5'));
echo json_encode($msarr) . PHP_EOL;
print_r (json_decode(json_encode($msarr), true)); // ARRAY MULTIDIM ASSOC


echo '+++-------'. PHP_EOL;

// PHP initializes the array if its null
$null_array = null;
$null_array[] = 'item';
print_r($null_array);

exit;

?>

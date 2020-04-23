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
$d->connect('localhost', 'user', 'user123!');
$d->select_db('phersistent');

if (!$d->table_exists('test_sobject'))
{
  $d->create_table('test_sobject'); // adds the id column
  $d->add_column('test_sobject', 'class', 'varchar(255)', false);
  $d->add_column('test_sobject', 'deleted', 'boolean', false);
  $d->add_column('test_sobject', 'num', 'int', true);
  //$d->add_column('test_sobject', 'sobject', 'JSON', false); // JSON doesnt work in mysql 5.5
  $d->add_column('test_sobject', 'sobject', 'text', true); // JSON doesnt work in mysql 5.5
}

// MODEL
class TestSObject extends \phersistent\Phersistent {

  public $num = self::INT;
  public $sobject = self::SOBJECT;

  public $table = 'test_sobject';
}


// SETUP
$ph_db = new \phersistent\PhersistentMySQL('localhost', 'user', 'user123!', 'phersistent');
$man = new \phersistent\PhersistentDefManager('model', $ph_db);



// TEST NULL SOBJECT
// https://github.com/ppazos/phersistence/issues/65

//$ins = $TestSObject->create(array('sobject'=>array('a'=>'b')));
$ins = $TestSObject->create();

//var_dump($ins);

assert ($ins->save() !== false); // save with sobject null (is valid)


$get = $TestSObject->get($ins->get_id());
$get->setProperties(array('num'=>2));
//var_dump($get);
assert ($get->save() !== false); // save with sobject null (is valid)



// TEST CREATE WITH SOBJECT
$ins = $TestSObject->create(array('sobject'=>array('nombre'=>'Pablo', 'edad'=>37, 'concepto'=>array('nombre'=>'Persona'))));
//var_dump($ins); // OK!



// SOBJECT TEST (set_object)

$ins = $TestSObject->create();

// default value is null
assert ($ins->get_sobject() == null);

// set works
$ins->set_sobject(array('nombre'=>'Pablo', 'edad'=>37, 'concepto'=>array('nombre'=>'Persona')));

// get works
$sobject = $ins->get_sobject();

assert($sobject['nombre'] == 'Pablo');
assert($sobject['edad'] == 37);

// save works
assert ($ins->save() !== false);



// get works
$get = $TestSObject->get($ins->get_id());

assert ($get != null);

// get sobject works
$sobject = $get->get_sobject();

assert($sobject['nombre'] == 'Pablo');
assert($sobject['edad'] == 37);

var_dump($sobject);


// SOBJECT TEST (create)

$ins = $TestSObject->create(array('sobject'=>array('nombre'=>'Miguel', 'edad'=>25, 'concepto'=>array('nombre'=>'Persona'))));

// sobject should be set
assert ($ins->get_sobject() != null);

// get works
$sobject = $ins->get_sobject();

assert($sobject['nombre'] == 'Miguel');
assert($sobject['edad'] == 25);

// save works
assert ($ins->save() !== false);

// get works
$get = $TestSObject->get($ins->get_id());

assert ($get != null);

// get sobject works
$sobject = $get->get_sobject();

assert($sobject['nombre'] == 'Miguel');
assert($sobject['edad'] == 25);

var_dump($sobject);


// SOBJECT TEST (setProperties)

$ins = $TestSObject->create();
$ins->setProperties(array('sobject'=>array('nombre'=>'Xina', 'edad'=>66, 'concepto'=>array('nombre'=>'Persona'))));

// sobject should be set
assert ($ins->get_sobject() != null);

// get works
$sobject = $ins->get_sobject();

assert($sobject['nombre'] == 'Xina');
assert($sobject['edad'] == 66);

// save works
assert ($ins->save() !== false);

// get works
$get = $TestSObject->get($ins->get_id());

assert ($get != null);

// get sobject works
$sobject = $get->get_sobject();

assert($sobject['nombre'] == 'Xina');
assert($sobject['edad'] == 66);

var_dump($sobject);


exit;

?>

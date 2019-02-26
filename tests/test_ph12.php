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

if (!$d->table_exists('d'))
{
  $d->create_table('d'); // adds the id column
  $d->add_column('d', 'class', 'varchar(255)', false);
  $d->add_column('d', 'deleted', 'boolean', false);
  $d->add_column('d', 'd_field_1', 'int', true);
  $d->add_column('d', 'd_field_2', 'varchar(255)', true);
}

if (!$d->table_exists('e'))
{
  $d->create_table('e'); // adds the id column
  $d->add_column('e', 'class', 'varchar(255)', false);
  $d->add_column('e', 'deleted', 'boolean', false);
  $d->add_column('e', 'e_field_1', 'int', true);
  $d->add_column('e', 'e_field_2', 'varchar(255)', true);
  $d->add_column('e', 'd_hm_e_back', 'int', true); // backlink hasmany one to many d->e

  $d->add_fk('e', 'd_hm_e_back', 'fk_d_hm_e_back', 'd', 'id');
}

if (!$d->table_exists('f'))
{
  $d->create_table('f'); // adds the id column
  $d->add_column('f', 'class', 'varchar(255)', false);
  $d->add_column('f', 'deleted', 'boolean', false);
  $d->add_column('f', 'f_field_1', 'int', true);
  $d->add_column('f', 'f_field_2', 'varchar(255)', true);
}

if (!$d->table_exists('a'))
{
  $d->create_table('a'); // adds the id column
  $d->add_column('a', 'class', 'varchar(255)', false);
  $d->add_column('a', 'deleted', 'boolean', false);
  $d->add_column('a', 'a_field_1', 'int', true);
  $d->add_column('a', 'a_field_2', 'varchar(255)', true);
  $d->add_column('a', 'a_ho_d_id', 'int', true);

  // STI
  $d->add_column('a', 'b_field_1', 'int', true);
  $d->add_column('a', 'b_field_2', 'varchar(255)', true);
  $d->add_column('a', 'b_ho_e_id', 'int', true);

  // STI
  $d->add_column('a', 'c_field_1', 'int', true);
  $d->add_column('a', 'c_field_2', 'varchar(255)', true);
  $d->add_column('a', 'c_ho_f_id', 'int', true);

  // Needs tables d, e, f created first
  $d->add_fk('a', 'a_ho_d_id', 'fk_a_ho_d', 'd', 'id');
  $d->add_fk('a', 'b_ho_e_id', 'fk_b_ho_e', 'e', 'id');
  $d->add_fk('a', 'c_ho_f_id', 'fk_c_ho_f', 'f', 'id');
}


// join table many to many
// FIXME: supposed e is the owner of the has many, that should be stated in the model
// FIXME: join table PK should be the two FKs
if (!$d->table_exists('e_hm_f_f'))
{
  $d->create_table('e_hm_f_f'); // adds the id column
  $d->add_column('e_hm_f_f', 'class', 'varchar(255)', false);
  $d->add_column('e_hm_f_f', 'deleted', 'boolean', false);
  $d->add_column('e_hm_f_f', 'e_id', 'int', true);
  $d->add_column('e_hm_f_f', 'f_id', 'int', true);

  $d->add_fk('e_hm_f_f', 'e_id', 'fk_e_id', 'e', 'id');
  $d->add_fk('e_hm_f_f', 'f_id', 'fk_f_id', 'f', 'id');
}


// MODEL

class A extends \phersistent\Phersistent {

  public $a_field_1 = self::INT;
  public $a_field_2 = self::TEXT;
  public $a_ho_d = D::class;

  function constraints()
  {
    return array(
      'a_field_1' => array(
        \phersistent\PhConstraint::max(10)
      )
    );
  }

  function functions()
  {
    return array(
      'uppercase' => function ($a) {
        return strtoupper($a->getA_field_2());
      }
    );
  }
}

class B extends A {

  public $b_field_1 = self::INT;
  public $b_field_2 = self::TEXT;
  public $b_ho_e = E::class;
}

class C extends B {

  public $c_field_1 = self::INT;
  public $c_field_2 = self::TEXT;
  public $c_ho_f = F::class;
}

class D extends \phersistent\Phersistent {

  public $d_field_1 = self::INT;
  public $d_field_2 = self::TEXT;
  public $hm_e = array(\phersistent\PhCollection::class, E::class);
}

class E extends \phersistent\Phersistent {

  public $e_field_1 = self::INT;
  public $e_field_2 = self::TEXT;
  public $hm_f = array(PhCollection::class, F::class); // many to many
}

class F extends \phersistent\Phersistent {

  public $f_field_1 = self::INT;
  public $f_field_2 = self::TEXT;
  public $hm_e = array(PhCollection::class, E::class);
}


// setup
$ph_db = new \phersistent\PhersistentMySQL('localhost', 'root', 'toor', 'phersistent');
$man = new \phersistent\PhersistentDefManager('model', $ph_db);

/*
echo "Class Definitions\n";
print_r($man->getDefinitions()); // Ph, A, B, C
echo "\n";
*/

// idem ^
//print_r(get_object_vars($C));

//print_r($E);

// VALIDATE TEST

$aa = $A->create(array('a_field_1'=>0, 'a_field_2'=>"Hello world!"));

// test instance function definition
assert($aa->uppercase() == 'HELLO WORLD!');
echo $aa->uppercase();


$es = $aa->validate();

assert($es === true);

$aa->setA_field_1(10);
$es = $aa->validate();

assert($es === true);

$aa->setA_field_1(11);
$es = $aa->validate();

assert($es !== true);
assert(is_array($es));
echo $es['a_field_1'][0]->getMessage() . PHP_EOL;


// FIND BY TEST

$es = $E->findBy(array(array('e_field_1', '>=', 333)));

echo count($es) .PHP_EOL;

foreach ($es as $e)
{
  assert($e->getE_field_1() >= 333);
}

echo $C->get_parent() . PHP_EOL; // B OK!
echo $A->get_parent() . PHP_EOL; // \phersistent\Phersistent OK!

$a = $A->create();

//print_r($D);
$d = $D->create();
$e = $E->create();

$e2 = $E->create();
$e3 = $E->create();


assert($d->sizeHm_e() == 0);
$d->addToHm_e($e);
assert($d->sizeHm_e() == 1);

$d->addToHm_e($e2);
$d->addToHm_e($e3);

assert($d->sizeHm_e() == 3);

$a->setA_ho_d($d);
$a->save();

//print_r($a);


// TEST DELETE
echo "A count ". $A->count() . PHP_EOL;

$a->delete();

echo "A count ". $A->count() . PHP_EOL;
// /TEST DELETE


exit;

// should try to update
//$a->setA_field_1(123);
$e->setE_field_1(666); // updates the e in a->d->es
$d->addToHm_e($E->create(array('e_field_1'=>333))); // mix of updats with this save
$a->save();

//print_r($A);

// test get hasmany lazy
$dloaded = $D->get(1);
assert($dloaded->getHm_e()->size() == 4); // returns PhCollection
assert($dloaded->getHm_e()[0]->isInstanceOf(get_class($E)));
//print_r($dloaded);

//print_r($a);

/*
$d->removeFromHm_e($e);
assert($d->sizeHm_e() == 2);

assert( $D->has_many_exists(E::class) );
assert( !$D->has_many_exists(A::class) );
assert( $D->is_one_to_many('hm_e') );
assert( !$E->is_one_to_many('hm_f') );
assert( $E->is_many_to_many('hm_f') );


assert( !$A->is_boolean('a_field_1') );
assert( $A->is_number('a_field_1') );
try
{
  $A->is_boolean('sdfsdsdfs'); // attr doesnt exists
  assert(false); // never reaches this point
}
catch (Exception $e)
{
  echo $e->getMessage() . PHP_EOL;
}
*/

//print_r($C->get_all_fields());
//print_r($C->get_declared_fields());

/*
$cins = $C->create(array(
  'c_field_1' => 123,
  'c_ho_f' => $F->create(array(
    'f_field_2'=>'hola'
  ))
));
*/
// this is interpreted as above but doesnt required an explicit create on F
/*
$cins = $C->create(array(
  'c_field_1' => 123,
  'c_ho_f' => array(
    'f_field_2'=>'hola'
  )
));
$cins->setC_field_2('pepe');

assert($cins->getC_field_1() == 123);
assert($cins->getC_field_2() == 'pepe');
assert($cins->getC_ho_f()->getF_field_2() == 'hola');

//print_r($cins);

print_r($ph_db->phi_to_data($cins));
*/

/*
echo \phersistent\PhersistentMySQL::get_table_name($cins) .PHP_EOL;
echo \phersistent\PhersistentMySQL::get_table_name($A->create()) .PHP_EOL;
echo \phersistent\PhersistentMySQL::get_table_name($B->create()) .PHP_EOL;
*/

?>

<?php
namespace test;

spl_autoload_register(function ($class) {
  global $_BASE;
  //echo 'spl_autoload_register '. $class . PHP_EOL;
  //echo $_BASE.str_replace('\\', '/', $class).'.php' . PHP_EOL;

  if (file_exists($_BASE.str_replace('\\', '/', $class).'.php'))
    require_once($_BASE.str_replace('\\', '/', $class).'.php');
});

// SCHEMA

$d = new \drivers\MySQL();
$d->connect('localhost', 'user', 'user1234');
$d->select_db('phersistent');


if (!$d->table_exists('a'))
{
  $d->create_table('a'); // adds the id column
  $d->add_column('a', 'class', 'varchar(255)', false);
  $d->add_column('a', 'deleted', 'boolean', false);
  $d->add_column('a', 'date_created', 'datetime', false);
  $d->add_column('a', 'is_closed', 'boolean', false);
}
if (!$d->table_exists('b'))
{
  $d->create_table('b'); // adds the id column
  $d->add_column('b', 'class', 'varchar(255)', false);
  $d->add_column('b', 'deleted', 'boolean', false);
  $d->add_column('b', 'date_created', 'datetime', false);
  $d->add_column('b', 'note', 'text(2048)', false);
  $d->add_column('b', 'a_bs_back', 'int', true);
  $d->add_fk('b', 'a_bs_back', 'fk_a_bs_back', 'a', 'id');
}


// TEST COLLECTIONS
if (is_subclass_of(\phersistent\PhSet::class, \phersistent\PhCollection::class))
{
  echo \phersistent\PhSet::class .' is subclass of collection '. PHP_EOL;
}
else {
  echo \phersistent\PhSet::class .' is NOT subclass of collection '. PHP_EOL;
}

if (is_subclass_of(\phersistent\PhList::class, '\phersistent\PhCollection'))
{
  echo \phersistent\PhList::class .' is subclass of collection '. PHP_EOL;
}
else {
  echo \phersistent\PhList::class .' is NOT subclass of collection '. PHP_EOL;
}


// MODEL
class A extends \phersistent\Phersistent {

  public $date_created = self::DATETIME;
  public $is_closed = self::BOOLEAN;

  public $table = 'a';

  public $bs = array(\phersistent\PhSet::class, B::class);

  function init()
  {
    return array(
      'date_created' => date('Y-m-d H:i:s')
    );
  }

  // for the $notes has many association defined as a set, it is optional to define
  // a custom equality function to avoid duplicated items in the set, the default
  // function checks by id.
  function bs_equality($b1, $b2) {
    return $b1->get_note() == $b2->get_note();
  }
}
class B extends \phersistent\Phersistent {

  public $date_created = self::DATETIME;
  public $note = self::TEXT;

  public $table = 'b';

  function init()
  {
    return array(
      'date_created' => date('Y-m-d H:i:s')
    );
  }
}


// setup
$ph_db = new \phersistent\PhersistentMySQL('localhost', 'user', 'user1234', 'phersistent');
$man = new \phersistent\PhersistentDefManager('', $ph_db);


$a1 = $A->create(array(
  'bs' => array(
    $B->create(array(
      'note' => 'Hi, I called them but didnt got an answer, trying tomorrow'
    )),
    $B->create(array(
      'note' => 'I just called and was able to reach someone'
    )),
    $B->create(array(
      'note' => 'I just called and was able to reach someone' // this is duplicated, wont be saved
    ))
  )
));

//print_r($case->getDefinition()->get_all_fields());

// this should include the backlinks to case and the same note
//print_r($case->get_notes()[0]->getDefinition()->get_all_fields());

//print_r($case); // sets the patient_case_note_notes_back OK but is not saved

$a1->save();


$a2 = $A->get($a1->get_id());

assert($a2->size_bs() == 2);

$bs2 = $a2->get_bs();

foreach ($bs2 as $b)
{
  echo $b->get_note() . PHP_EOL;
}

// TODO: add new and remove


exit;


?>

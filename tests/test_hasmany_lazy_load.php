<?php
namespace test;

spl_autoload_register(function ($class) {
  global $_BASE;
  //echo 'spl_autoload_register '. $class . PHP_EOL;
  //echo $_BASE.str_replace('\\', '/', $class).'.php' . PHP_EOL;

  if (file_exists($_BASE.str_replace('\\', '/', $class).'.php'))
    require_once($_BASE.str_replace('\\', '/', $class).'.php');
});

function passert($cond, $msg = NULL)
{
  if (!$cond)
  {
    if ($msg == NULL) $msg = 'Assertion failed!';
    //throw new \Exception($msg);
    $trace = debug_backtrace();
    $line = $trace[1]['file'] .'::'. $trace[1]['line'];
    echo 'Assertion failed on '. $line .' >>> '. $msg .PHP_EOL;
  }
}

// SCHEMA

$d = new \drivers\MySQL();
$d->connect('localhost', 'user', 'user123!');
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
  function bs_equality(\phersistent\PhInstance $b1, \phersistent\PhInstance $b2) {
    if ($b1->note == NULL || $b2->note == NULL)
    {
      throw new \Exception("Can't compare B with NULL note");
    }
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
$ph_db = new \phersistent\PhersistentMySQL('localhost', 'user', 'user123!', 'phersistent');
$man = new \phersistent\PhersistentDefManager('', $ph_db);

echo "== TEST SAVE".PHP_EOL;

$b1 = $B->create(array(
  'note' => 'Hi, I called them but didnt got an answer, trying tomorrow'
));
$b2 = $B->create(array(
  'note' => 'I just called and was able to reach someone'
));
$b3 = $B->create(array(
  'note' => 'I just called and was able to reach someone' // this is duplicated, wont be saved
));

$a1 = $A->create(array(
  'bs' => array($b1, $b2, $b3)
));

//print_r($case->getDefinition()->get_all_fields());

// this should include the backlinks to case and the same note
//print_r($case->get_notes()[0]->getDefinition()->get_all_fields());

//print_r($case); // sets the patient_case_note_notes_back OK but is not saved

$a1->save();

echo "== TEST LOAD".PHP_EOL;
$a2 = $A->get($a1->get_id());

assert($a2->size_bs() == 2);

$bs2 = $a2->get_bs();

foreach ($bs2 as $b)
{
  echo $b->get_note() . PHP_EOL;
}

// TODO: add new and remove

// =============================================
// test remove from

echo "== TEST REMOVE FROM".PHP_EOL;
$a3 = $A->get($a1->get_id());

// uses the equality function by note
// if ($a3->remove_from_bs($b1))
// {
//   $b1->delete(); // avoids
// }

$a3->remove_from_bs_and_delete($b1); // does the same as the above lines

// size in memory is 1
passert($a3->size_bs() == 1, 'items are not 1 are '. $a3->size_bs());

$a3->save(); // b2 and b2->a_back = a is still in the database since this doesnt update that!

$a4 = $A->get($a1->get_id());

//echo '----------------- '. $a4->size_bs() . PHP_EOL;

// size of saved is 1
passert($a4->size_bs() == 1, 'items in DB are not 1 are '. $a4->size_bs());

//echo '----------------- '. $a4->size_bs() . PHP_EOL;

// =============================================
// test add to
echo "== TEST ADD TO".PHP_EOL;
$a4->add_to_bs(
  $B->create(array(
    'note' => 'new note'
  ))
);
// size in memory is 2
passert($a4->size_bs() == 2, 'items in mem are not 2 are '. $a4->size_bs());

$a4->save();

$a5 = $A->get($a1->get_id());
// size of saved is 1
passert($a5->size_bs() == 2, 'items in DB are not 2 are '. $a5->size_bs());



// substring test
$method = 'remove_from_bs_and_delete';
if (substr($method,0,12) == "remove_from_")
{
  $attr = lcfirst(substr($method, 12));

  echo $attr .PHP_EOL;

  if (\basic\BasicString::endsWith($attr, '_and_delete'))
  {
    $attr = substr($attr, 0, -11);

    echo $attr .PHP_EOL;
  }
}

exit;

?>

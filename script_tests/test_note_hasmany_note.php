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

$d = new \CaboLabs\Phersistence\drivers\MySQL();
$d->connect('localhost', 'user', 'user123!');
$d->select_db('phersistent');


if (!$d->table_exists('patient_case'))
{
  $d->create_table('patient_case'); // adds the id column
  $d->add_column('patient_case', 'class', 'varchar(255)', false);
  $d->add_column('patient_case', 'deleted', 'boolean', false);
  $d->add_column('patient_case', 'date_created', 'datetime', false);
  $d->add_column('patient_case', 'is_closed', 'boolean', false);
}
if (!$d->table_exists('patient_case_note'))
{
  $d->create_table('patient_case_note'); // adds the id column
  $d->add_column('patient_case_note', 'class', 'varchar(255)', false);
  $d->add_column('patient_case_note', 'deleted', 'boolean', false);
  $d->add_column('patient_case_note', 'date_created', 'datetime', false);
  $d->add_column('patient_case_note', 'note', 'text(2048)', false);
  $d->add_column('patient_case_note', 'patient_case_notes_back', 'int', true); // will be null for the answers
  $d->add_column('patient_case_note', 'patient_case_note_notes_back', 'int', true); // note has_many note (answers)
  $d->add_fk('patient_case_note', 'patient_case_notes_back', 'fk_patient_case_notes_back', 'patient_case', 'id');
  $d->add_fk('patient_case_note', 'patient_case_note_notes_back', 'fk_patient_case_note_notes_back', 'patient_case_note', 'id');
}


// MODEL



class PatientCase extends \CaboLabs\Phersistence\phersistent\Phersistent {

  public $date_created = self::DATETIME;
  public $is_closed = self::BOOLEAN;

  public $table = 'patient_case';

  public $notes = array(CaboLabs\Phersistence\phersistent\PhCollection::class, PatientCaseNote::class);

  // TODO: check if the schedule will be recorded in the case
  //public $schedules = array(\phersistent\PhCollection::class, Schedule::class);

  function init()
  {
    return array(
      'date_created' => date('Y-m-d H:i:s')
    );
  }
}
class PatientCaseNote extends \CaboLabs\Phersistence\phersistent\Phersistent {

  public $date_created = self::DATETIME;
  public $note = self::TEXT;

  // answers to this note, could be empty
  public $notes = array(\phersistent\PhSet::class, PatientCaseNote::class);

  public $table = 'patient_case_note';

  function init()
  {
    return array(
      'date_created' => date('Y-m-d H:i:s')
    );
  }

  // for the $notes has many association defined as a set, it is optional to define
  // a custom equality function to avoid duplicated items in the set, the default
  // function checks by id.
  function notes_equality($a, $b) {
    return $a->get_note() == $b->get_note();
  }
}


// setup
$ph_db = new \CaboLabs\Phersistence\phersistent\PhersistentMySQL('localhost', 'user', 'user123!', 'phersistent');
$man = new \CaboLabs\Phersistence\phersistent\PhersistentDefManager('', $ph_db);


$case = $PatientCase->create(array(
  'notes' => array(
    $PatientCaseNote->create(array(
      'note' => 'Hi, I called them but didnt got an answer, trying tomorrow',
      'notes' => array(
        $PatientCaseNote->create(array(
          'note' => 'I just called and was able to reach someone'
        )),
        $PatientCaseNote->create(array(
          'note' => 'I just called and was able to reach someone'
        ))
      )
    ))
  )
));

//print_r($case->getDefinition()->get_all_fields());

// this should include the backlinks to case and the same note
print_r($case->get_notes()[0]->getDefinition()->get_all_fields());

//print_r($case); // sets the patient_case_note_notes_back OK but is not saved

$case->save();

exit;


?>

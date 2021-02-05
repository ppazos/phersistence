<?php

spl_autoload_register(function ($class) {
  global $_BASE;
  //echo 'spl_autoload_register '. $class . PHP_EOL;
  //echo $_BASE.str_replace('\\', '/', $class).'.php' . PHP_EOL;

  if (file_exists($_BASE.str_replace('\\', '/', $class).'.php'))
    require_once($_BASE.str_replace('\\', '/', $class).'.php');
});

// SCHEMA

$ph_db = new \phersistent\PhersistentMySQL('localhost', 'user', 'user1234', 'phersistent');
$d = $ph_db->get_driver();

/*
$d = new drivers\MySQL();
$d->connect('localhost', 'user', 'user1234');
$d->select_db('phersistent');
*/

// TODO: test has many 

if (!$d->table_exists('employer'))
{
  $d->create_table('employer'); // adds the id column
  $d->add_column('employer', 'class', 'varchar(255)', false);
  $d->add_column('employer', 'deleted', 'boolean', false);
  $d->add_column('employer', 'name', 'varchar(255)', true);
}

if (!$d->table_exists('member'))
{
  $d->create_table('member'); // adds the id column
  $d->add_column('member', 'class', 'varchar(255)', false);
  $d->add_column('member', 'deleted', 'boolean', false);
  $d->add_column('member', 'name', 'varchar(255)', true);
  $d->add_column('member', 'employer_id', 'int', true); // has one

  $d->add_fk('member', 'employer_id', 'fk_member_employer', 'employer', 'id');
}


if (!$d->table_exists('phone_number'))
{
  $d->create_table('phone_number'); // adds the id column
  $d->add_column('phone_number', 'class', 'varchar(255)', false);
  $d->add_column('phone_number', 'deleted', 'boolean', false);
  $d->add_column('phone_number', 'number', 'varchar(255)', true);
  $d->add_column('phone_number', 'member_phones_back', 'int', true); // backlink hasmany

  $d->add_fk('phone_number', 'member_phones_back', 'fk_phone_member', 'member', 'id');
}


// MODEL

class Employer extends \phersistent\Phersistent {

  public $name = self::TEXT;

  function constraints()
  {
    return array(
      'name' => array(
        \phersistent\PhConstraint::maxLength(10)
      )
    );
  }
}

class PhoneNumber extends \phersistent\Phersistent {

  public $number = self::TEXT;

}

class Member extends \phersistent\Phersistent {

  public $name = self::TEXT;
  public $employer = Employer::class;
  public $phones = array(\phersistent\PhCollection::class, PhoneNumber::class);

  function constraints()
  {
    return array(
      'name' => array(
        \phersistent\PhConstraint::maxLength(10)
      )
    );
  }
}


// SETUP

$man = new \phersistent\PhersistentDefManager(NULL, $ph_db);


// TEST is_dirty on create

$employer = $Employer->create(array('name'=>'CaboLabs'));

assert($employer->get_is_dirty() === true);

$val = $employer->validate();

if (!$employer->save())
{
  print_r($employer->getErrors());
}

assert($employer->get_is_dirty() === false);


$member = $Member->create(array(
  'name' => 'Pablo',
  'employer' => $employer,
  'phones' => array(
    $PhoneNumber->create(array(
      'number' => '555 456 4563'
    ))
  )
));

assert($member->get_is_dirty() === true);
assert($employer->get_is_dirty() === false);

// this save shouldn't update the associated employer, since the employer is not dirty
if (!$member->save())
{
  print_r($member->getErrors());
}

assert($member->get_is_dirty() === false);
assert($employer->get_is_dirty() === false);


$employer = $member->get_employer();

//var_dump($employer);

assert($employer->get_is_dirty() === false);

$member->setProperties(array('employer_id'=>$employer->get_id()));

assert($member->get_is_dirty() === true);

// this save shouldn't update the associated employer, since the employer is not dirty
if (!$member->save()) // update
{
  print_r($member->getErrors());
}

//var_dump($member->get_employer());




// TEST is_dirty loading form DB

$employer = $Employer->get($employer->get_id());

// employer should be clean on load
assert($employer->get_is_dirty() === false);

$member = $Member->get($member->get_id());

assert($member->get_is_dirty() === false);

$phones = $member->get_phones();

foreach ($phones as $phone)
{
  assert($phone->get_is_dirty() === false);
}


//var_dump($member);


?>

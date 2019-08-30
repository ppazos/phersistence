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
  $d->add_column('member', 'employer_id', 'int', true); // backlink hasmany one to many d->e

  $d->add_fk('member', 'employer_id', 'fk_member_employer', 'employer', 'id');
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

class Member extends \phersistent\Phersistent {

  public $name = self::TEXT;
  public $employer = Employer::class;

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

$ph_db = new \phersistent\PhersistentMySQL('localhost', 'root', 'toor', 'phersistent');
$man = new \phersistent\PhersistentDefManager(NULL, $ph_db);

// TEST

// increase the size of the name to make the validate fail
$employer = $Employer->create(array('name'=>'CaboLabs'));

assert($employer->getIsDirty());

$val = $employer->validate();

if (!$employer->save())
{
  print_r($employer->getErrors());
}

assert(!$employer->getIsDirty());

// increase the size of the name to make the validate fail
$member = $Member->create(array('name'=>'Pablo', 'employer'=>$employer));

assert($member->getIsDirty());

if (!$member->save())
{
  print_r($member->getErrors());
}


assert(!$member->getIsDirty());

$employer = $member->getEmployer();

//var_dump($employer);

assert(!$employer->getIsDirty());

$member->setProperties(array('employer_id'=>$employer->get_id()));
if (!$member->save()) // update
{
  print_r($membe->getErrors());
}

var_dump($member->getEmployer());

?>

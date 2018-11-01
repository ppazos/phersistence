<?php

spl_autoload_register(function ($class) {
  global $_BASE;
  echo 'spl_autoload_register '. $class . PHP_EOL;
  echo $_BASE.str_replace('\\', '/', $class).'.php' . PHP_EOL;

  if (file_exists($_BASE.str_replace('\\', '/', $class).'.php'))
    require_once($_BASE.str_replace('\\', '/', $class).'.php');
});

$d = new drivers\MySQL();
$d->connect('localhost', 'user', 'user1234');
$d->select_db('amplify');

if (!$d->table_exists('payor'))
{
  $d->create_table('payor'); // adds the id column
  $d->add_column('payor', 'class', 'varchar(255)', false);
  $d->add_column('payor', 'deleted', 'boolean', false);
  $d->add_column('payor', 'company', 'varchar(255)', false);
  $d->add_column('payor', 'ein', 'varchar(255)', false);
  $d->add_unique('payor', array('ein'), 'ein_unique');
}

if (!$d->table_exists('address'))
{
  $d->create_table('address'); // adds the id column
  $d->add_column('address', 'class', 'varchar(255)', false);
  $d->add_column('address', 'deleted', 'boolean', false);
  $d->add_column('address', 'line1', 'varchar(255)', false);
  $d->add_column('address', 'line2', 'varchar(255)', true);
  $d->add_column('address', 'zipcode', 'varchar(10)', true);
  $d->add_column('address', 'state', 'varchar(2)', false); // state code
}

if (!$d->table_exists('person'))
{
  $d->create_table('person'); // adds the id column
  $d->add_column('person', 'class', 'varchar(255)', false);
  $d->add_column('person', 'deleted', 'boolean', false);
  $d->add_column('person', 'firstname', 'varchar(255)', false);
  $d->add_column('person', 'lastname', 'varchar(255)', false);
  $d->add_column('person', 'phone_number', 'varchar(20)', true);
}

if (!$d->table_exists('employer'))
{
  $d->create_table('employer'); // adds the id column
  $d->add_column('employer', 'class', 'varchar(255)', false);
  $d->add_column('employer', 'deleted', 'boolean', false);
  $d->add_column('employer', 'company', 'varchar(255)', false);
  $d->add_column('employer', 'ein', 'varchar(255)', false);
  $d->add_column('employer', 'payor_id', 'int', true);
  $d->add_column('employer', 'address_id', 'int', true);
  $d->add_column('employer', 'contact_id', 'int', true);
  $d->add_fk('employer', 'payor_id', 'fk_payor', 'payor', 'id');
  $d->add_fk('employer', 'address_id', 'fk_address', 'address', 'id');
  $d->add_fk('employer', 'contact_id', 'fk_contact', 'person', 'id');
  $d->add_unique('employer', array('ein'), 'ein_unique');
}

if (!$d->table_exists('member'))
{
  $d->create_table('member'); // adds the id column
  $d->add_column('member', 'class', 'varchar(255)', false);
  $d->add_column('member', 'deleted', 'boolean', false);
  $d->add_column('member', 'member_id', 'varchar(255)', false); // member number
  $d->add_column('member', 'person_id', 'int', false); // member details
  $d->add_column('member', 'employer_id', 'int', false);
}

if (!$d->table_exists('hcpcs'))
{
  $d->create_table('hcpcs'); // adds the id column
  $d->add_column('hcpcs', 'class', 'varchar(255)', false);
  $d->add_column('hcpcs', 'deleted', 'boolean', false);
  $d->add_column('hcpcs', 'name', 'varchar(255)', false);
  $d->add_column('hcpcs', 'code', 'varchar(5)', false);
}

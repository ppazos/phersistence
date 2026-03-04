<?php

/*
spl_autoload_register(function ($class) {
  global $_BASE;
  echo 'spl_autoload_register '. $class . PHP_EOL;
  echo $_BASE.str_replace('\\', '/', $class).'.php' . PHP_EOL;

  if (file_exists($_BASE.str_replace('\\', '/', $class).'.php'))
    require_once($_BASE.str_replace('\\', '/', $class).'.php');
});

$d = new drivers\MySQL();
$d->connect('localhost', 'user', 'user123!');
$d->select_db('phersistent');
*/

if (!$d->table_exists('payor'))
{
  $d->create_table('payor'); // adds the id column
  $d->add_column('payor', 'class', 'varchar(255)', false);
  $d->add_column('payor', 'deleted', 'boolean', false);
  $d->add_column('payor', 'company', 'varchar(255)', false);
  $d->add_column('payor', 'ein', 'varchar(255)', false);
  $d->add_unique('payor', array('ein'), 'ein_unique');
}

if (!$d->table_exists('person'))
{
  $d->create_table('person'); // adds the id column
  $d->add_column('person', 'class',     'varchar(255)', false);
  $d->add_column('person', 'deleted',   'boolean', false);
  $d->add_column('person', 'firstname', 'varchar(255)', false);
  $d->add_column('person', 'lastname',  'varchar(255)', false);
  //$d->add_column('person', 'email', 'varchar(255)', false);
  $d->add_column('person', 'phone_number', 'varchar(20)', true);
  //$d->add_unique('person', array('email'), 'person_email_unique');
}

if (!$d->table_exists('address'))
{
  $d->create_table('address'); // adds the id column
  $d->add_column('address', 'class',   'varchar(255)', false);
  $d->add_column('address', 'deleted', 'boolean', false);
  $d->add_column('address', 'line1',   'varchar(255)', false);
  $d->add_column('address', 'line2',   'varchar(255)', true);
  $d->add_column('address', 'zipcode', 'varchar(10)', true);
  $d->add_column('address', 'state',   'varchar(2)', false); // state code

  $d->add_column('address', 'person_addresses_back', 'int', true);

  $d->add_fk('address', 'person_addresses_back', 'fk_person_addresses_back', 'person', 'id');
}


if (!$d->table_exists('employer'))
{
  $d->create_table('employer'); // adds the id column
  $d->add_column('employer', 'class',    'varchar(255)', false);
  $d->add_column('employer', 'deleted',  'boolean', false);
  $d->add_column('employer', 'company',  'varchar(255)', true);
  $d->add_column('employer', 'name',     'varchar(255)', true);
  $d->add_column('employer', 'ein',      'varchar(255)', true);
  $d->add_column('employer', 'payor_id', 'int', true);
  $d->add_column('employer', 'address_id', 'int', true);
  $d->add_column('employer', 'contact_id', 'int', true);

  $d->add_fk('employer', 'payor_id',   'fk_payor', 'payor', 'id');
  $d->add_fk('employer', 'address_id', 'fk_employer_address', 'address', 'id');
  $d->add_fk('employer', 'contact_id', 'fk_employer_contact', 'person', 'id');

  $d->add_unique('employer', array('ein'), 'ein_unique');
}

if (!$d->table_exists('member'))
{
  $d->create_table('member'); // adds the id column
  $d->add_column('member', 'class',       'varchar(255)', false);
  $d->add_column('member', 'deleted',     'boolean', false);
  $d->add_column('member', 'name',        'varchar(255)', true);
  $d->add_column('member', 'number',      'varchar(255)', true); // member number
  $d->add_column('member', 'person_id',   'int', true); // member details
  $d->add_column('member', 'employer_id', 'int', true);

  $d->add_fk('member', 'person_id', 'fk_member_person', 'person', 'id');
  $d->add_fk('member', 'employer_id', 'fk_member_employer', 'employer', 'id');
}

if (!$d->table_exists('hcpcs'))
{
  $d->create_table('hcpcs'); // adds the id column
  $d->add_column('hcpcs', 'class',   'varchar(255)', false);
  $d->add_column('hcpcs', 'deleted', 'boolean', false);
  $d->add_column('hcpcs', 'name',    'varchar(255)', false);
  $d->add_column('hcpcs', 'code',    'varchar(5)', false);
}

if (!$d->table_exists('provider'))
{
  $d->create_table('provider'); // adds the id column
  $d->add_column('provider', 'class',   'varchar(255)', false);
  $d->add_column('provider', 'deleted', 'boolean', false);

  $d->add_column('provider', 'is_amplify_provider', 'boolean', false);
  $d->add_column('provider', 'is_rendering_provider', 'boolean', false);

  $d->add_column('provider', 'name', 'varchar(255)', false);
  $d->add_column('provider', 'contact_id', 'int', false);
  $d->add_column('provider', 'address_id', 'int', false);
  $d->add_fk('provider', 'contact_id', 'fk_provider_contact', 'person', 'id');
  $d->add_fk('provider', 'address_id', 'fk_provider_address', 'address', 'id');
}

if (!$d->table_exists('price_with_codes'))
{
  $d->create_table('price_with_codes'); // adds the id column
  $d->add_column('price_with_codes', 'class',   'varchar(255)', false);
  $d->add_column('price_with_codes', 'deleted', 'boolean', false);

  $d->add_column('price_with_codes', 'price', 'float', true);
  $d->add_column('price_with_codes', 'codes', 'text', true);
  $d->add_column('price_with_codes', 'not_null_codes', 'text', false);
}

if (!$d->table_exists('number_test'))
{
  $d->create_table('number_test');
  $d->add_column('number_test', 'class',   'varchar(255)', false);
  $d->add_column('number_test', 'deleted', 'boolean', false);
  $d->add_column('number_test', 'number1', 'int', true);
  $d->add_column('number_test', 'number2', 'bigint', true);
  $d->add_column('number_test', 'number3', 'float', true);
  $d->add_column('number_test', 'number4', 'double', true);
}

if (!$d->table_exists('phone_number'))
{
  $d->create_table('phone_number');
  $d->add_column('phone_number', 'class', 'varchar(255)', false);
  $d->add_column('phone_number', 'deleted', 'boolean', false);
  $d->add_column('phone_number', 'number', 'varchar(255)', true);
  $d->add_column('phone_number', 'member_phones_back', 'int', true);

  $d->add_fk('phone_number', 'member_phones_back', 'fk_phone_member', 'member', 'id');
}

if (!$d->table_exists('test_sobject'))
{
  $d->create_table('test_sobject');
  $d->add_column('test_sobject', 'class', 'varchar(255)', false);
  $d->add_column('test_sobject', 'deleted', 'boolean', false);
  $d->add_column('test_sobject', 'num', 'int', true);
  $d->add_column('test_sobject', 'sobject', 'text', true);
}

if (!$d->table_exists('a'))
{
  $d->create_table('a');
  $d->add_column('a', 'class', 'varchar(255)', false);
  $d->add_column('a', 'deleted', 'boolean', false);
  $d->add_column('a', 'date_created', 'datetime', false);
  $d->add_column('a', 'is_closed', 'boolean', false);
}

if (!$d->table_exists('b'))
{
  $d->create_table('b');
  $d->add_column('b', 'class', 'varchar(255)', false);
  $d->add_column('b', 'deleted', 'boolean', false);
  $d->add_column('b', 'date_created', 'datetime', false);
  $d->add_column('b', 'note', 'text(2048)', false);
  $d->add_column('b', 'a_bs_back', 'int', true);
  $d->add_fk('b', 'a_bs_back', 'fk_a_bs_back', 'a', 'id');
}

<?php

use CaboLabs\Phersistence\migrations\PhMigration;

/**
 * Initial schema of the test models. Equivalent of what the former src/db/schema.php generated,
 * now expressed as the baseline migration of the test database.
 */
class InitialSchema extends PhMigration
{
  public function schema()
  {
    $this->driver->create_table('payor'); // adds the id column
    $this->driver->add_column('payor', 'class', 'string(255)', false);
    $this->driver->add_column('payor', 'deleted', 'boolean', false);
    $this->driver->add_column('payor', 'company', 'string(255)', false);
    $this->driver->add_column('payor', 'ein', 'string(255)', false);
    $this->driver->add_unique('payor', ['ein'], 'ein_unique');

    $this->driver->create_table('person'); // adds the id column
    $this->driver->add_column('person', 'class',     'string(255)', false);
    $this->driver->add_column('person', 'deleted',   'boolean', false);
    $this->driver->add_column('person', 'firstname', 'string(255)', false);
    $this->driver->add_column('person', 'lastname',  'string(255)', false);
    //$this->driver->add_column('person', 'email', 'string(255)', false);
    $this->driver->add_column('person', 'phone_number', 'string(20)', true);
    //$this->driver->add_unique('person', array('email'), 'person_email_unique');

    $this->driver->create_table('address'); // adds the id column
    $this->driver->add_column('address', 'class',   'string(255)', false);
    $this->driver->add_column('address', 'deleted', 'boolean', false);
    $this->driver->add_column('address', 'line1',   'string(255)', false);
    $this->driver->add_column('address', 'line2',   'string(255)', true);
    $this->driver->add_column('address', 'zipcode', 'string(10)', true);
    $this->driver->add_column('address', 'state',   'string(2)', false); // state code

    $this->driver->add_column('address', 'person_addresses_back', 'int', true);

    $this->driver->add_fk('address', 'person_addresses_back', 'fk_person_addresses_back', 'person', 'id');

    $this->driver->create_table('employer'); // adds the id column
    $this->driver->add_column('employer', 'class',    'string(255)', false);
    $this->driver->add_column('employer', 'deleted',  'boolean', false);
    $this->driver->add_column('employer', 'company',  'string(255)', true);
    $this->driver->add_column('employer', 'name',     'string(255)', true);
    $this->driver->add_column('employer', 'ein',      'string(255)', true);
    $this->driver->add_column('employer', 'payor_id', 'int', true);
    $this->driver->add_column('employer', 'address_id', 'int', true);
    $this->driver->add_column('employer', 'contact_id', 'int', true);

    $this->driver->add_fk('employer', 'payor_id',   'fk_payor', 'payor', 'id');
    $this->driver->add_fk('employer', 'address_id', 'fk_employer_address', 'address', 'id');
    $this->driver->add_fk('employer', 'contact_id', 'fk_employer_contact', 'person', 'id');

    $this->driver->add_unique('employer', ['ein'], 'ein_unique');

    $this->driver->create_table('member'); // adds the id column
    $this->driver->add_column('member', 'class',       'string(255)', false);
    $this->driver->add_column('member', 'deleted',     'boolean', false);
    $this->driver->add_column('member', 'name',        'string(255)', true);
    $this->driver->add_column('member', 'number',      'string(255)', true); // member number
    $this->driver->add_column('member', 'person_id',   'int', true); // member details
    $this->driver->add_column('member', 'employer_id', 'int', true);

    $this->driver->add_fk('member', 'person_id', 'fk_member_person', 'person', 'id');
    $this->driver->add_fk('member', 'employer_id', 'fk_member_employer', 'employer', 'id');

    $this->driver->create_table('hcpcs'); // adds the id column
    $this->driver->add_column('hcpcs', 'class',   'string(255)', false);
    $this->driver->add_column('hcpcs', 'deleted', 'boolean', false);
    $this->driver->add_column('hcpcs', 'name',    'string(255)', false);
    $this->driver->add_column('hcpcs', 'code',    'string(5)', false);

    $this->driver->create_table('provider'); // adds the id column
    $this->driver->add_column('provider', 'class',   'string(255)', false);
    $this->driver->add_column('provider', 'deleted', 'boolean', false);

    $this->driver->add_column('provider', 'is_amplify_provider', 'boolean', false);
    $this->driver->add_column('provider', 'is_rendering_provider', 'boolean', false);

    $this->driver->add_column('provider', 'name', 'string(255)', false);
    $this->driver->add_column('provider', 'contact_id', 'int', false);
    $this->driver->add_column('provider', 'address_id', 'int', false);
    $this->driver->add_fk('provider', 'contact_id', 'fk_provider_contact', 'person', 'id');
    $this->driver->add_fk('provider', 'address_id', 'fk_provider_address', 'address', 'id');

    $this->driver->create_table('price_with_codes'); // adds the id column
    $this->driver->add_column('price_with_codes', 'class',   'string(255)', false);
    $this->driver->add_column('price_with_codes', 'deleted', 'boolean', false);

    $this->driver->add_column('price_with_codes', 'price', 'float', true);
    $this->driver->add_column('price_with_codes', 'codes', 'text', true);
    $this->driver->add_column('price_with_codes', 'not_null_codes', 'text', false);

    $this->driver->create_table('number_test');
    $this->driver->add_column('number_test', 'class',   'string(255)', false);
    $this->driver->add_column('number_test', 'deleted', 'boolean', false);
    $this->driver->add_column('number_test', 'number1', 'int', true);
    $this->driver->add_column('number_test', 'number2', 'long', true);
    $this->driver->add_column('number_test', 'number3', 'float', true);
    $this->driver->add_column('number_test', 'number4', 'double', true);

    $this->driver->create_table('phone_number');
    $this->driver->add_column('phone_number', 'class', 'string(255)', false);
    $this->driver->add_column('phone_number', 'deleted', 'boolean', false);
    $this->driver->add_column('phone_number', 'number', 'string(255)', true);
    $this->driver->add_column('phone_number', 'member_phones_back', 'int', true);

    $this->driver->add_fk('phone_number', 'member_phones_back', 'fk_phone_member', 'member', 'id');

    $this->driver->create_table('test_sobject');
    $this->driver->add_column('test_sobject', 'class', 'string(255)', false);
    $this->driver->add_column('test_sobject', 'deleted', 'boolean', false);
    $this->driver->add_column('test_sobject', 'num', 'int', true);
    $this->driver->add_column('test_sobject', 'sobject', 'text', true);

    $this->driver->create_table('a');
    $this->driver->add_column('a', 'class', 'string(255)', false);
    $this->driver->add_column('a', 'deleted', 'boolean', false);
    $this->driver->add_column('a', 'date_created', 'datetime', false);
    $this->driver->add_column('a', 'is_closed', 'boolean', false);

    $this->driver->create_table('b');
    $this->driver->add_column('b', 'class', 'string(255)', false);
    $this->driver->add_column('b', 'deleted', 'boolean', false);
    $this->driver->add_column('b', 'date_created', 'datetime', false);
    $this->driver->add_column('b', 'note', 'text', false);
    $this->driver->add_column('b', 'a_bs_back', 'int', true);
    $this->driver->add_fk('b', 'a_bs_back', 'fk_a_bs_back', 'a', 'id');
  }
}

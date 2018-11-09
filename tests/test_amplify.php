<?php

spl_autoload_register(function ($class) {
  global $_BASE;
  //echo 'spl_autoload_register '. $class . PHP_EOL;
  //echo $_BASE.str_replace('\\', '/', $class).'.php' . PHP_EOL;

  if (file_exists($_BASE.str_replace('\\', '/', $class).'.php'))
  {
    require_once($_BASE.str_replace('\\', '/', $class).'.php');
  }
});

// setup
$ph_db = new \phersistent\PhersistentMySQL('localhost', 'root', 'toor', 'amplify');
$man = new \phersistent\PhersistentDefManager('model', $ph_db);
//print_r( $man->getDefinitions() );

$e = $Employer->create(array(
  'company' => 'CaboLabs',
  'ein'     => '12-322278',
  'address' => array(
    'line1'   => 'Juan Paullier 995 apt 703',
    'zipcode' => '11200',
    'state'   => 'MN'
  ),
  'contact' => array(
    'firstname' => 'Pablo',
    'lastname'  => 'Pazos',
    'phone_number' => '00598 99 043 145'
  ),
  'payor' => array(
    'company' => 'BPA',
    'ein'     => '14-122672'
  )
));

//print_r($e->getDefinition());

$e2 = $Employer->create(array(
  'company' => 'CaboLabs2',
  'ein'     => '12-3435629',
  'contact' => array(
    'firstname' => 'Pablo',
    'lastname'  => 'Pazos',
    'phone_number' => '00598 99 043 145'
  ),
  'payor' => array(
    'company' => 'BPA2',
    'ein'     => '14-1226762'
  )
));
$e2->setProperties(array(
  'address' => array(
    'line1'   => 'Juan Paullier 995 apt 703 2',
    'zipcode' => '11200',
    'state'   => 'MN'
  )
));


//print_r($e->save());
//$ph_db ->save_instance($e2);

//print_r($e2);

echo 'Hay '. $Employer->count() .' employers'. PHP_EOL;

//print_r($Employer->listAll());


$edb = $Employer->get(8);
print_r($edb);
if ($edb)
{
  $a = $edb->getAddress(); // ;azy load
  print_r($a);
  print_r($edb);
}




//print_r($ph_db->phi_to_data($e));

//$ph_db ->save_instance($e);

/*
$t = $ph_db->get_row('employer', 1);
print_r($t);

$e = $ph_db->get('\model\Employer', 1);
print_r($e);
*/

//\phersistent\PhersistentMySQL::data_to_phi(\phersistent\PhersistentMySQL::phi_to_data($e));


/*
echo \phersistent\PhersistentMySQL::get_table_name($Payor->create()) . PHP_EOL;

print_r($Payor->create()->getDefinition(true));
print_r($Employer->create()->getDefinition(true));
print_r($Payor->get_all_fields());
print_r($Employer->get_all_fields());
echo $Employer->is_simple_field('ein') .PHP_EOL;

echo $Employer->get_parent();
*/





?>

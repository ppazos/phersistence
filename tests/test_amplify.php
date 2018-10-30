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
$man = new \phersistent\PhersistentDefManager('model');
//print_r( $man->getDefinitions() );

//print_r($GLOBALS);

echo \phersistent\PhersistentMySQL::get_table_name($Payor) . PHP_EOL;
/*
echo $C->get_parent() . PHP_EOL; // B OK!
echo $A->get_parent() . PHP_EOL; // \phersistent\Phersistent OK!

print_r($C->get_all_fields());
print_r($C->get_declared_fields());

$cins = $C->create(array(
  'c_field_1' => 123,
  'c_ho_f' => $F->create(array(
    'f_field_2'=>'hola'
  ))
));
$cins->setC_field_2('pepe');

assert($cins->getC_field_1() == 123);
assert($cins->getC_field_2() == 'pepe');
assert($cins->getC_ho_f()->getF_field_2() == 'hola');
*/

?>

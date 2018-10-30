<?php

spl_autoload_register(function ($class) {
  global $_BASE;
  echo 'spl_autoload_register '. $class . PHP_EOL;
  echo $_BASE.str_replace('\\', '/', $class).'.php' . PHP_EOL;

  if (file_exists($_BASE.str_replace('\\', '/', $class).'.php'))
    require_once($_BASE.str_replace('\\', '/', $class).'.php');
});


class A extends \phersistent\Phersistent {

  public $a_field_1 = self::INT;
  public $a_field_2 = self::TEXT;
  public $a_ho_d = D::class;
}

class B extends A {

  public $b_field_1 = self::INT;
  public $b_field_2 = self::TEXT;
  public $b_ho_e = E::class;
}

class C extends B {

  public $c_field_1 = self::INT;
  public $c_field_2 = self::TEXT;
  public $c_ho_f = F::class;
}

class D extends \phersistent\Phersistent {

  public $d_field_1 = self::INT;
  public $d_field_2 = self::TEXT;
}

class E extends \phersistent\Phersistent {

  public $e_field_1 = self::INT;
  public $e_field_2 = self::TEXT;
}

class F extends \phersistent\Phersistent {

  public $f_field_1 = self::INT;
  public $f_field_2 = self::TEXT;
}


// setup
$man = new \phersistent\PhersistentDefManager();

/*
echo "Class Definitions\n";
print_r($man->getDefinitions()); // Ph, A, B, C
echo "\n";
*/

// idem ^
//print_r(get_object_vars($C));

echo $C->get_parent() . PHP_EOL; // B OK!
echo $A->get_parent() . PHP_EOL; // \phersistent\Phersistent OK!

print_r($C->get_all_fields());
print_r($C->get_declared_fields());
/*
$cins = $C->create(array(
  'c_field_1' => 123,
  'c_ho_f' => $F->create(array(
    'f_field_2'=>'hola'
  ))
));
*/
// this is interpreted as above but doesnt required an explicit create on F
$cins = $C->create(array(
  'c_field_1' => 123,
  'c_ho_f' => array(
    'f_field_2'=>'hola'
  )
));
$cins->setC_field_2('pepe');

assert($cins->getC_field_1() == 123);
assert($cins->getC_field_2() == 'pepe');
assert($cins->getC_ho_f()->getF_field_2() == 'hola');

print_r($cins);

?>

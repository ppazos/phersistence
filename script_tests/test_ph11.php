<?php

spl_autoload_register(function ($class) {
  global $_BASE;
  echo 'spl_autoload_register '. $class . PHP_EOL;
  echo $_BASE.str_replace('\\', '/', $class).'.php' . PHP_EOL;

  if (file_exists($_BASE.str_replace('\\', '/', $class).'.php'))
    require_once($_BASE.str_replace('\\', '/', $class).'.php');
});

class A extends \CaboLabs\Phersistence\phersistent\Phersistent {

  public $a_field_1 = self::INT;
  public $a_field_2 = self::TEXT;
}

class B extends A {

  public $b_field_1 = self::INT;
  public $b_field_2 = self::TEXT;
}

class C extends B {

  public $c_field_1 = self::INT;
  public $c_field_2 = self::TEXT;
}

// setup
$man = new \CaboLabs\Phersistence\phersistent\PhersistentDefManager();

/*
echo "Class Definitions\n";
print_r($man->getDefinitions()); // Ph, A, B, C
echo "\n";
*/

echo "Class Definition A Fields\n";
foreach ($A as $attr=>$type)
   echo "$attr = $type\n";

echo "\n";

echo "Class Definition B Fields\n";
foreach ($B as $attr=>$type)
   echo "$attr = $type\n";

echo "\n";

echo "Class Definition C Fields\n";
foreach ($C as $attr=>$type)
   echo "$attr = $type\n";

echo "\n";

?>

<?php

spl_autoload_register(function ($class) {
  global $_BASE;
  //echo 'spl_autoload_register '. $class . PHP_EOL;
  //echo $_BASE.str_replace('\\', '/', $class).'.php' . PHP_EOL;

  if (file_exists($_BASE.str_replace('\\', '/', $class).'.php'))
    require_once($_BASE.str_replace('\\', '/', $class).'.php');
});

use \phersistent\PhConstraint;

// Test min

$min = PhConstraint::min(10);

assert( $min->validate('Class', 'attr', 0) !== true );
assert( $min->validate('Class', 'attr', 10) === true );
assert( $min->validate('Class', 'attr', 100) === true );

$error = $min->validate('Class', 'attr', 0);

// On Class->attr, the assigned value 0 should be higher or equal than 10
echo $error->getMessage() . PHP_EOL;


// Test max

$max = PhConstraint::max(10);

assert( $max->validate('Class', 'attr', 0) === true );
assert( $max->validate('Class', 'attr', 10) === true );
assert( $max->validate('Class', 'attr', 100) !== true );

$error = $max->validate('Class', 'attr', 100);

// On Class->attr, the assigned value 100 should be lower or equal than 10
echo $error->getMessage() . PHP_EOL;

?>

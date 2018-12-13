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


// Test between

$bet = PhConstraint::between(10, 20);

assert( $bet->validate('Class', 'attr', -10) !== true );
assert( $bet->validate('Class', 'attr', 0) !== true );
assert( $bet->validate('Class', 'attr', 10) === true );
assert( $bet->validate('Class', 'attr', 15) === true );
assert( $bet->validate('Class', 'attr', 20) === true );
assert( $bet->validate('Class', 'attr', 100) !== true );

$error = $bet->validate('Class', 'attr', -10);

// On Class->attr, the length of the assigned value 'abcd' should higher or equal than 10
echo $error->getMessage() . PHP_EOL;


// Test min length

$minl = PhConstraint::minLength(10);

assert( $minl->validate('Class', 'attr', '') !== true );
assert( $minl->validate('Class', 'attr', 'abcd') !== true );
assert( $minl->validate('Class', 'attr', 'abcdefghij') === true );
assert( $minl->validate('Class', 'attr', 'abcdefghijxxxxx') === true );

$error = $minl->validate('Class', 'attr', 'abcd');

// On Class->attr, the length of the assigned value 'abcd' should higher or equal than 10
echo $error->getMessage() . PHP_EOL;


// Test max length

$maxl = PhConstraint::maxLength(10);

assert( $maxl->validate('Class', 'attr', '') === true );
assert( $maxl->validate('Class', 'attr', 'abcd') === true );
assert( $maxl->validate('Class', 'attr', 'abcdefghij') === true );
assert( $maxl->validate('Class', 'attr', 'abcdefghijxxxxx') !== true );

$error = $maxl->validate('Class', 'attr', 'abcdefghijxxxxx');

// On Class->attr, the length of the assigned value 'abcdefghijxxxxx' should lower or equal than 10
echo $error->getMessage() . PHP_EOL;


// Test email

$eml = PhConstraint::email();

assert( $eml->validate('Class', 'attr', '') !== true );
assert( $eml->validate('Class', 'attr', 'abcd') !== true );
assert( $eml->validate('Class', 'attr', 'abcde@') !== true );
assert( $eml->validate('Class', 'attr', 'abcde@gmail') !== true );
assert( $eml->validate('Class', 'attr', 'abcde@gmail.com') === true );
assert( $eml->validate('Class', 'attr', 'ab.cd.e@gmail.com') === true );
assert( $eml->validate('Class', 'attr', 'abc_de@gmail.com.uy') === true );
assert( $eml->validate('Class', 'attr', 'abcde@gmail.com') === true );
assert( $eml->validate('Class', 'attr', 'abcde@gmail.us') === true );
assert( $eml->validate('Class', 'attr', 'abcde+aaabbb@gmail.com') === true );
assert( $eml->validate('Class', 'attr', 'abcde+aaabbb@gmail.us') === true );

$error = $eml->validate('Class', 'attr', 'abcdxx');

// On Class->attr, the assigned value 'abcdxx' is not a valid email address
echo $error->getMessage() . PHP_EOL;


// Test date

$dt = PhConstraint::date();

assert( $dt->validate('Class', 'attr', '') !== true );
assert( $dt->validate('Class', 'attr', 'abcd') !== true );
assert( $dt->validate('Class', 'attr', 'asdf-sd-sd') !== true );
assert( $dt->validate('Class', 'attr', '1234') !== true );
assert( $dt->validate('Class', 'attr', '1234-12') !== true );
assert( $dt->validate('Class', 'attr', '1234-44-01') !== true );
assert( $dt->validate('Class', 'attr', '1234-12-44') !== true );
assert( $dt->validate('Class', 'attr', '1234-12-21') === true );
assert( $dt->validate('Class', 'attr', '1999-01-01') === true );

$error = $dt->validate('Class', 'attr', 'abcdxx');

// On Class->attr, the assigned value 'abcdxx' has not a valid date format YYYY-MM-DD
echo $error->getMessage() . PHP_EOL;


// Test datetine

$dtt = PhConstraint::datetime();

assert( $dtt->validate('Class', 'attr', '') !== true );
assert( $dtt->validate('Class', 'attr', 'abcd') !== true );
assert( $dtt->validate('Class', 'attr', 'asdf-sd-sd') !== true );
assert( $dtt->validate('Class', 'attr', '1234') !== true );
assert( $dtt->validate('Class', 'attr', '1234-12') !== true );
assert( $dtt->validate('Class', 'attr', '1234-44-01') !== true );
assert( $dtt->validate('Class', 'attr', '1234-12-44') !== true );
assert( $dtt->validate('Class', 'attr', '1234-12-21') !== true );
assert( $dtt->validate('Class', 'attr', '1999-01-01') !== true );
assert( $dtt->validate('Class', 'attr', '1234-12-44 12') !== true );
assert( $dtt->validate('Class', 'attr', '1234-12-44 12:12') !== true );
assert( $dtt->validate('Class', 'attr', '1234-12-44 12:12:12') !== true );

assert( $dtt->validate('Class', 'attr', '1234-12-21 44:12:12') !== true );
assert( $dtt->validate('Class', 'attr', '1234-12-21 12:66:12') !== true );
assert( $dtt->validate('Class', 'attr', '1234-12-21 12:12:66') !== true );

assert( $dtt->validate('Class', 'attr', '1234-12-21 12') !== true );
assert( $dtt->validate('Class', 'attr', '1234-12-21 12:12') !== true );
assert( $dtt->validate('Class', 'attr', '1234-12-21 12:12:12') === true );
assert( $dtt->validate('Class', 'attr', '1999-01-01 12:12:12') === true );

$error = $dtt->validate('Class', 'attr', 'abcdxx');

// On Class->attr, the assigned value 'abcdxx' has not a valid datetime format YYYY-MM-DD hh:mm:ss
echo $error->getMessage() . PHP_EOL;


?>

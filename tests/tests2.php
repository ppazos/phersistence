<?php

include "Phersistent2.php";

// Coleccion global de definiciones de clases
$classDefinitions = array();

class BaseUser extends Phersistent {

   public function __construct()
   {
      $this->addField('age', self::INT);
      $this->hasOne('role', Role::class);
   }

   public function __toString()
   {
      return __CLASS__;
   }
}
class SpecificUser extends BaseUser {

   public function __construct()
   {
      $this->addField('date', self::DATE);
      $this->hasMany('associatedUsers', BaseUser::class);
   }

   public function __toString()
   {
      return __CLASS__;
   }
}
class Role extends Phersistent {

   public function __construct()
   {
      $this->addField('name', self::TEXT);
   }

   public function __toString()
   {
      return __CLASS__;
   }
}


// setup
$man = new PhersistentDefManager();
$classDefinitions = $man->getDefinitions();

echo "Class Definitions\n";
print_r($man->getDefinitions());
echo "\n";


echo "Ph Declared Classes\n";
foreach (get_declared_classes() as $k=>$v)
{
   if (is_subclass_of($v, 'Phersistent'))
      echo " + $v\n";
}
echo "\n";


echo "Class Definition BaseUser Fields\n";
// This is possible because of PhersistentDefManager->__construct->add()->$GLOBALS[$def] = $defins;
print_r($BaseUser);
print_r($SpecificUser);
print_r($Role);

// Do not show fields because are private
//foreach ($BaseUser as $attr=>$type)
//   echo "$attr = $type\n";

echo "\n";


/* this and the code below are equivalent
$instance = $man->create(MyPhersistent::class, array('age'=>5));
$instance2 = $man->create(MyPhersistent::class, array('age'=>6));
$instance->addToUsers($instance2);
$instance3 = $man->create(SubclassPhersistent::class, array('date'=>'2013-10-24'));
*/

echo "Class Instances Creation\n";
// This works because the definitions are declared as globals with the same name of the class added to the manager
$user1 = $BaseUser->create(array('age'=>5));
$user2 = $BaseUser->create(array('age'=>6));
$user3 = $SpecificUser->create(array('date'=>'2013-10-24'));
$user3->setAge(7);

$role = $Role->create(array('name'=>'admin'));

$user1->setRole($role);
$user2->setRole($role);
$user3->setRole($role);

$user3->addToAssociatedUsers($user1);
$user3->addToAssociatedUsers($user2);

echo "\n";

echo "-----------------------\n";
print_r( $BaseUser->getManager()->getParent(BaseUser::class) );
print_r( $man->getParent(BaseUser::class) );


/*
echo "Instance Inheritance Structure and Attr Declarations\n";
$c = $user3->getDefinition();
$i = 0;
while ($c != null)
{
   for ($j=0;$j<$i;$j++) echo " ";
   if ($i >0) echo "|=>";

   echo get_class($c). "\n";

   foreach ($c->getFields() as $attr=>$phfield)
   {
      for ($j=0;$j<$i;$j++) echo " ";
      echo " + ". $attr ." (". $phfield->type .")\n";
   }
   foreach ($c->getHasOne() as $attr=>$phone)
   {
      for ($j=0;$j<$i;$j++) echo " ";
      echo " + ". $attr ." (". $phone->class .")\n";
   }
   foreach ($c->getHasMany() as $attr=>$phmany)
   {
      for ($j=0;$j<$i;$j++) echo " ";
      echo " + ". $attr ." (". $phmany->class .")\n";
   }
   echo "\n";

   $c = $c->getManager()->getParent($c);
   $i++;
}

echo "\n";
*/

echo "-----------------------\n";

echo "Instance Definition\n";
print_r($user3->getDefinition());
echo "\n";


//print_r($SpecificUser->getClass()); // undefined function
print_r(SpecificUser::class);
echo SpecificUser::class;


echo "Instance Full Structure\n";
//print_r($instance);
print_r($user3);

// TESTS / ASSERTS

if (!$user3->isInstanceOf('BaseUser')) throw new \Exception("error isInstanceOf 1");
if (!$user3->isInstanceOf('SpecificUser')) throw new \Exception("error isInstanceOf 2");

// user1 is BaseUser
if ( $user1->isInstanceOf('SpecificUser')) throw new \Exception("error isInstanceOf 2 (falso positivo)");
if ( $user3->getClass() != 'SpecificUser' ) throw new \Exception("error getClass");
if ( $user3->getAge() != 7 ) throw new \Exception("error getXXX");
if ( $user3->getAssociatedUsers()->size() != 2 ) throw new \Exception("error getHasMany size");


// The PhCollection is iterable
echo "collection iteration\n";
foreach ($user3->getAssociatedUsers() as  $ins)
{
   print_r($ins);
}

/* Pedir un atributo que no existe tira error de PHP: mejor asi no tengo que chequearlo!

<b>Notice</b>:  Undefined property: PhInstance::$fake in
<b>C:\Documents and Settings\Administrator\My Documents\www\Phersistence\Phersistent.php</b>
 on line <b>14</b><br />

$instance->getFake();
/*

/*
foreach ($instance as $attr=>$value)
{
   //echo gettype($value);
   if (is_scalar($value))
      echo "$attr = ". $value ."\n";
   else if ($value instanceof Closure) // Inyected method
      echo "$attr = Closure\n";
   else if (is_null($value))
      echo "$attr = NULL\n";
   else if (is_object($value))
      echo "$attr = ". print_r($value, true) ."\n";
   else
      echo "$attr = otra cosa\n";
}
*/


/*
echo "--------------------------------\n";
// Valid from PHP 5.4 (array literal)
// http://php.net/manual/es/language.types.array.php
$a = ['a'=>234];
print_r($a);
*/

// TEST UUID

function guidv4($data)
{
    assert(strlen($data) == 16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

if (function_exists("openssl_random_pseudo_bytes"))
{
   echo guidv4(openssl_random_pseudo_bytes(16)) ."\n";
   echo guidv4(openssl_random_pseudo_bytes(16)) ."\n";
   echo guidv4(openssl_random_pseudo_bytes(16)) ."\n";
   echo guidv4(openssl_random_pseudo_bytes(16)) ."\n";
   echo guidv4(openssl_random_pseudo_bytes(16)) ."\n";
 }
if (function_exists("random_bytes")) echo guidv4(random_bytes(16));

?>

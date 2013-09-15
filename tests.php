<?php

include "Phersistent.php";

/*
class PhersistentAttribute {
   private $name;
   private $type;
   public function __construct($name, $type)
   {
      $this->name = $name;
      $this->type = $type;
   }
}
*/

class MyPhersistent extends Phersistent {

   public $age = 'integer'; //new PhersistentAttribute('age', 'integer'); // solo puede inicialzar con valores constantes
   //public $clax = __CLASS__;
   /*
   static private $instance = null;

   private function __contruct() {}

   public static function getInstance()
   {
      if (self::$instance == null)
      {
         self::$instance = new MyPhersistent();
      }
      return self::$instance;
   }
   */
   
   public function __construct()
   {
      //$this->clax = __CLASS__;
      $this->hasMany('users', 'User');
      $this->hasOne('aaa', 'Opa');
   }
   
   public function __toString()
   {
      return __CLASS__;
   }
}
class SubclassPhersistent extends MyPhersistent {

   public $date = 'date';

   public function __construct()
   {
   }
   
   public function __toString()
   {
      return __CLASS__;
   }
}
class AnotherPhersistent extends Phersistent {

   public $name = 'string';

   public function __construct()
   {
   }
   
   public function __toString()
   {
      return __CLASS__;
   }
}

//$MyPhersistent = MyPhersistent::getInstance();
$MyPhersistent = new MyPhersistent();
$SubclassPhersistent = new SubclassPhersistent();
//var_dump($MyPhersistent);

echo "Class Definition Fields\n";
foreach ($MyPhersistent as $attr=>$type) echo "$attr = $type\n";

$instance = $MyPhersistent->create(array('age'=>5));
$instance2 = $MyPhersistent->create(array('age'=>6));
$instance->addToUsers($instance2);

$instance3 = $SubclassPhersistent->create(array('age'=>7, 'date'=>'2013-10-24'));

echo "Instances Fields\n";
//print_r($instance);
print_r($instance3);

// TESTS / ASSERTS
if (!$instance->isInstanceOf('MyPhersistent')) throw new Exception("error isInstanceOf 1");
if ($instance->isInstanceOf('AnotherPhersistent')) throw new Exception("error isInstanceOf 2 (falso positivo)");
if ( $instance->getClass() != 'MyPhersistent' ) throw new Exception("error getClass");
if ( $instance->getAge() != 5 ) throw new Exception("error getXXX");
if ( $instance->getUsers()->size() != 1 ) throw new Exception("error getHasMany size");

if (!$instance3->isInstanceOf('MyPhersistent')) throw new Exception("error isInstanceOf 3");
if (!$instance3->isInstanceOf('SubclassPhersistent')) throw new Exception("error isInstanceOf 4");



// FIXME: poder iterar directamente por la coleccion
foreach ($instance->getUsers()->all() as $ins)
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

?>
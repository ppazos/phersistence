<?php

include "Phersistent.php";

// Coleccion global de definiciones de clases
$classDefinitions = array();


class MyPhersistent extends Phersistent {

   public $age = self::INT; //'integer'; //new PhersistentAttribute('age', 'integer'); // solo puede inicialzar con valores constantes

   // TESTS has one as normal declared fields
   public $another = AnotherPhersistent::class; // without rel name
   public $another2 = array(AnotherPhersistent::class, 'myrelname');

   public function __construct()
   {
      //$this->hasMany('users', SubclassPhersistent::class);
      //$this->hasOne('another', AnotherPhersistent::class);
   }

   public function __toString()
   {
      return __CLASS__;
   }
}
class SubclassPhersistent extends MyPhersistent {

   public $date = self::DATE;

   public function __construct()
   {
     $this->hasMany('composite', MyPhersistent::class);
   }

   public function __toString()
   {
      return __CLASS__;
   }
}
class AnotherPhersistent extends Phersistent {

   public $name = self::TEXT;

   public function __construct()
   {
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

// Our declared classes
//print_r(get_declared_classes());
/*
foreach (get_declared_classes() as $k=>$v)
{
   if (is_subclass_of($v, 'Phersistent'))
      echo "$v\n";
}
*/

// Array( [date] => date  [age] => integer)
//print_r( get_object_vars($SubclassPhersistent) );


echo "Class Definition MyPhersistent Fields\n";
foreach ($MyPhersistent as $attr=>$type)
   echo "$attr = $type\n";

echo "\n";

echo "Class Definition SubclassPhersistent Fields\n";
foreach ($SubclassPhersistent as $attr=>$type)
   echo "$attr = $type\n";

echo "\n";

/* this and the code below are equivalent
$instance = $man->create(MyPhersistent::class, array('age'=>5));
$instance2 = $man->create(MyPhersistent::class, array('age'=>6));
$instance->addToUsers($instance2);
$instance3 = $man->create(SubclassPhersistent::class, array('date'=>'2013-10-24'));
*/

echo "Class Instances Creation\n";
// This works because the definitions are declared as globals with the same name of the class added to the manager
$instance = $MyPhersistent->create(array('age'=>5));
$instance2 = $MyPhersistent->create(array('age'=>6));

$another = $AnotherPhersistent->create(array('name'=>'Carlos'));
$instance->setAnother($another);

$instance3 = $SubclassPhersistent->create(array('date'=>'2013-10-24'));
$instance3->addToComposite($instance);
$instance3->addToComposite($instance2);
// 'age'=>7,
$instance3->setAge(7);

echo "\n";

// *** Recorrer ancestros usando instancias (dinamico)
echo "Instance Inheritance Structure and Attr Declarations\n";
$c = $instance3->getClass();
$i = 0;
while ($c != null)
{
   for ($j=0;$j<$i;$j++) echo " ";
   if ($i >0) echo "|=>";

   echo $c. "\n";

   // TODO; para saber que campos fueron declarados en cada
   //       superclase, es necesario tener la instancia de esa
   //       superclase. Las instancias de superclase no estan
   //       asociadas al objeto instancia. Pero las instancias
   //       de definiciones de clases, deberian estar en un
   //       contenedor global de definiciones. Esto es para
   //       guardar estructuras de herencia en tablas separadas.
   //
   // Las subclases tienen todos los atributos, las superclases
   // tienen menos. Para saber los atributos que se declaran en
   // la clase es necesario restarles los atributos de su padre.
   //print_r( get_object_vars( $classDefinitions[$c] ) );

   $thisAttrs = get_object_vars( $classDefinitions[$c] );
   $c = get_parent_class($c);

   if ($c != null)
      $parentAttrs = get_object_vars( $classDefinitions[$c] );
   else
      $parentAttrs = array();

   $declaredAttrs = array_diff($thisAttrs, $parentAttrs);
   foreach ($declaredAttrs as $attr=>$type)
   {
      for ($j=0;$j<$i;$j++) echo " ";
      echo " + ". $attr ." (". $type ."), ";
   }
   echo "\n";

   $i++;
}


echo "Class Declared Fields\n";
echo "Shows fields declared on the class and inherited from parent\n";

print_r(get_object_vars($SubclassPhersistent));
print_r(get_class_vars($SubclassPhersistent));
print_r(get_object_vars($instance3->getClass())); // empty
print_r(get_class_vars($instance3->getClass()));

echo "\n";

echo "Instances Definition Merged\n";
print_r($instance3->getDefinition());
echo "\n";

echo "Instances Definition Full\n";
print_r($instance3->getDefinition(true));
echo "\n";


print_r(AnotherPhersistent::class);


echo "Instance Full Structure\n";
//print_r($instance);
print_r($instance3);

// TESTS / ASSERTS
if (!$instance->isInstanceOf('MyPhersistent')) throw new Exception("error isInstanceOf 1");
if ($instance->isInstanceOf('AnotherPhersistent')) throw new Exception("error isInstanceOf 2 (falso positivo)");
if ( $instance->getClass() != 'MyPhersistent' ) throw new Exception("error getClass");
if ( $instance->getAge() != 5 ) throw new Exception("error getXXX");
if ( $instance3->getComposite()->size() != 2 ) throw new Exception("error getHasMany size");

if (!$instance3->isInstanceOf('MyPhersistent')) throw new Exception("error isInstanceOf 3");
if (!$instance3->isInstanceOf('SubclassPhersistent')) throw new Exception("error isInstanceOf 4");



// The PhCollection is iterable
echo "collection iteration\n";
foreach ($instance3->getComposite() as  $ins)
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

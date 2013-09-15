<?php

include "Phersistent.php";

// Coleccion global de definiciones de clases
$classDefinitions = array();


class MyPhersistent extends Phersistent {

   public $age = 'integer'; //new PhersistentAttribute('age', 'integer'); // solo puede inicialzar con valores constantes
   
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
$AnotherPhersistent = new AnotherPhersistent();
//var_dump($MyPhersistent);


// Coleccion de definiciones de clases, necesario para MTI
$classDefinitions[ 'Phersistent' ] = new Phersistent(); // base
$classDefinitions[ get_class($MyPhersistent) ] = $MyPhersistent;
$classDefinitions[ get_class($SubclassPhersistent) ] = $SubclassPhersistent;
$classDefinitions[ get_class($AnotherPhersistent) ] = $AnotherPhersistent;


// Array( [date] => date  [age] => integer)
//print_r( get_object_vars($SubclassPhersistent) );


echo "Class Definition Fields\n";
//foreach ($MyPhersistent as $attr=>$type) echo "$attr = $type\n";

$instance = $MyPhersistent->create(array('age'=>5));
$instance2 = $MyPhersistent->create(array('age'=>6));
$instance->addToUsers($instance2);

$instance3 = $SubclassPhersistent->create(array('age'=>7, 'date'=>'2013-10-24'));


// *** Recorrer ancestros usando instancias (dinamico)
$c = $instance3->getClass();
$i = 0;
while ($c != null)
{
   for ($j=0;$j<$i;$j++) echo " ";
   if ($i >0) echo "|_";

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
      echo " ";
      for ($j=0;$j<$i;$j++) echo " ";
      echo $attr ." (". $type ."), ";
   }
   echo "\n";
   
   $i++;
}



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
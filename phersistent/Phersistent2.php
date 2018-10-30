<?php

// Phersistent test with declarations as functions like Yupp.
namespace phersistent;

//include_once "PhCollection.php";

class PhInstance {

   private function addTo($hasManyName, PhInstance $ins)
   {
      $this->{$hasManyName}->add( $ins );
   }

   private function get($attr)
   {
      return $this->{$attr};
   }

   private function set($attr, $value)
   {
      return $this->{$attr} = $value;
   }

   public function __call($method, $args)
   {
      // addToXYX
      if ( substr($method,0,5) == "addTo" )
      {
         $attr = lcfirst( substr($method, 5) ); // xyx
         if (!property_exists($this, $attr))
         {
            throw new Exception("Object of type ". $this->getClass() ." doesn't have a declaration for a hasMany named '$attr'");
         }
         $this->addTo($attr, $args[0]);
         return;
      }

      // getXYZ
      if ( substr($method,0,3) == "get" )
      {
         $attr = lcfirst( substr($method, 3) ); // xyz
         if (!property_exists($this, $attr))
         {
            throw new Exception("Object of type ". $this->getClass() ." doesn't have a property named '$attr'");
         }
         return $this->get( $attr );
      }

      // setXXX
      // The value should be converted to the right type e.g. string dates -> DateTime
      if ( substr($method,0,3) == "set" )
      {
         //echo $method;
         //print_r($args);

         $attr = lcfirst( substr($method, 3) ); // xxx
         if (!property_exists($this, $attr))
         {
            throw new Exception("Object of type ". $this->getClass() ." doesn't have a property named '$attr'");
         }

         // TODO
         // 1. check if the class contains a definition of the attribute
         // 2. check if the value has the same type as the declared
         // 3. if the declared is date and the value is string, try to parse and convert to date, internally use string UTC time to store, since that is the one compatible with most DBs
         // 4. check if the declared is has many, the given value should be an array, of items of the same type as the declared

         $this->set($attr, $args[0]);

         // TODO
         //$attr = lcfirst( substr($method, 3) ); // xYZ
         //return $this->get( $attr );
         return;
      }

      // method not found
   }


   public function isInstanceOf($phersistent)
   {
      //echo get_class($this->phclass).' '. $phersistent;
      return (is_a($this->phclass, $phersistent));
   }

   public function getClass()
   {
      return get_class($this->phclass);
   }

   /**
    * Returns the class declaration. With $full = false, returns the mergede declaration,
    * with $full = true, returns the inheritance declaration with own fields for each class.
    */
   public function getDefinition()
   {
      $definition = array();
      $c = $this->phclass;
      $def = &$definition;
      while ($c != null)
      {
         $def['__class'] = get_class($c);

         foreach ($c->getFields() as $attr=>$phfield)
         {
            //echo " + ". $attr ." (". $phfield->type .")\n";
            $def[$attr] = $phfield;
         }
         foreach ($c->getHasOne() as $attr=>$phone)
         {
            //echo " + ". $attr ." (". $phone->class .")\n";
            $def[$attr] = $phone;
         }
         foreach ($c->getHasMany() as $attr=>$phmany)
         {
            //echo " + ". $attr ." (". $phmany->class .")\n";
            $def[$attr] = $phmany;
         }

         $c = $c->getManager()->getParent($c);
         if ($c != null)
         {
            $def['__parent'] = array();
            $def = &$def['__parent'];
         }
      }

      return $definition;
   }
}

class PhField {
   public $name;
   public $type;
   public function __construct($name, $type)
   {
      $this->name = $name;
      $this->type = $type;
   }
}
class PhHasOne {
   public $name;
   public $class;
   public $role;
   public function __construct($name, $class, $role = '')
   {
      $this->name = $name;
      $this->class = $class;
      $this->role = $role;
   }
}
class PhHasMany {
   public $name;
   public $class;
   public $type; // PhCollection type
   public $role;
   public function __construct($name, $class, $type, $role = '')
   {
      $this->name = $name;
      $this->class = $class;
      $this->type = $type;
      $this->role = $role;
   }
}

class Phersistent {

   // Basic attribute types
   const INT      = 'int';
   const LONG     = 'long';
   const FLOAT    = 'float';
   const DOUBLE   = 'double';
   const BOOLEAN  = 'boolean';
   const DATE     = 'date';
   const TIME     = 'time';
   const DATETIME = 'datetime';
   const TEXT     = 'text';

   private $__fields = array();
   private $__many = array();
   private $__one = array();

   private $manager; // set by IoC

   public function getAllFields()
   {
      return array_merge($this->__fields, $this->__one, $this->__many);
   }

   public function getFields()
   {
      return $this->__fields;
   }

   public function setManager($manager)
   {
      $this->manager = $manager;
   }

   public function getManager()
   {
      return $this->manager;
   }

   protected function addField($name, $type)
   {
      $this->__fields[$name] = new PhField($name, $type);
   }

   /**
    * $name UML relationship target name
    * $class target class
    * $relName UML relationship name
    */
   protected function hasOne($name, $class, $relName = null)
   {
      $this->__one[$name] = new PhHasOne($name, $class, $relName);
   }

   public function getHasOne()
   {
      return $this->__one;
   }

   /**
    * $name UML relationship target name
    * $class target class
    * $collectionType collection, list, set, orderedSet
    * $relName UML relationship name
    */
   protected function hasMany($name, $class, $collectionType = 'collection', $relName = null)
   {
      $this->__many[$name] = new PhHasMany($name, $class, $collectionType, $relName);
   }

   public function getHasMany()
   {
      return $this->__many;
   }

   /*
    * New instance of this class.
    */
   public function create($attrs = array())
   {
      $ins = new PhInstance();

      // Inject attributes declared on this declaration and parent classes
      $class = $this;
      while ($class != null)
      {
         foreach ($class->getFields() as $attr=>$phfield)
         {
            $value = null; // Default value
            if (isset($attrs[$attr])) $value = $attrs[$attr];

            // Injects the attribute and sets the value
            $ins->{$attr} = $value;
         }

         foreach ($class->getHasOne() as $attr=>$phone)
         {
            $value = null; // Default value
            if (isset($attrs[$attr])) $value = $attrs[$attr];

            // Injects the attribute and sets the value
            $ins->{$attr} = $value;
         }

         foreach ($class->getHasMany() as $attr=>$phmany)
         {
            //$value = array(); // Default value
            //if (isset($attrs[$attr])) $value = $attrs[$attr];

            if ($phmany->type == 'collection') $ins->{$attr} = new PhCollection();
            if ($phmany->type == 'list') $ins->{$attr} = new PhList();
            if ($phmany->type == 'set') $ins->{$attr} = new PhSet();

            // TODO: add default values
         }

         $class = $this->manager->getParent($class);
      }

      // phclass merges all the inherited attr declarations from parent classes
      // it doesn't allow to reconstruct the separate definitions for multiple
      // table inheritance mapping
      $ins->phclass = $this;
      $ins->id = null;       // Default value
      $ins->deleted = false; // Default value

      return $ins;
   }

   public function get($id)
   {
   }

   public function listAll()
   {
   }

   public function isValidDef($otherClassName)
   {
      return is_subclass_of($otherClassName, 'Phersistent');
   }
}


class PhersistentDefManager {

   // Always contains base class
   private $classDefinitions; // = array('Phersistent'=>new Phersistent());

   public function __construct()
   {
      $ph = new Phersistent();
      $ph->setManager($this);
      $this->classDefinitions = array('Phersistent'=>$ph);

     // all declared phersistent classes before creating the manager
     foreach (get_declared_classes() as $aClassName)
     {
        if ($this->classDefinitions['Phersistent']->isValidDef($aClassName))
           $this->add($aClassName);
     }
   }

   public function add($def)
   {
      $defins = new $def();
      $defins->setManager($this); // adds manager IoC
      if (!$defins instanceof Phersistent)
      {
         throw new Exception($def ." is not a valid Phersistent definition");
      }

      // TODO: avoid adding the same def twice
      $this->classDefinitions[$def] = $defins;

      // declares the definitions as globals so can be used to create instances without using the manager
      $GLOBALS[$def] = $defins;
   }

   public function getDefinitions()
   {
     return $this->classDefinitions;
   }

   public function getParent($class)
   {
      $parent = get_parent_class($class);
      if ($parent == null) return null;

      return $this->classDefinitions[$parent];
   }

   public function create($def, $attrs = array())
   {
      // TODO: check $def exists
      return $this->classDefinitions[$def]->create($attrs);
   }
}

?>

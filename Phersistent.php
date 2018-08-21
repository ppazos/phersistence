<?php

include_once "PhCollection.php";

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
   public function getDefinition($full = false)
   {
      if (!$full)
        return $this->phclass;

      // TODO: missing hasMany, the issue is hasMany is a call to a function, not a declaration yet.

      $definition = array();
      $c = $this->getClass();
      $def = &$definition;
      while ($c != null)
      {
         $def['__class'] = $c;

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

         $thisAttrs = get_class_vars($c); // $classDefinitions[$c]

         $c = get_parent_class($c);

         if ($c != null)
            $parentAttrs = get_class_vars($c);
         else
            $parentAttrs = array();

         $declaredAttrs = array_diff($thisAttrs, $parentAttrs);

         foreach ($declaredAttrs as $attr=>$type)
         {
            $def[$attr] = $type;

            if (is_subclass_of($type, 'Phersistent'))
            {
               echo "$attr is has one\n";
            }
            if (is_array($type))
            {
               if (is_subclass_of($type[0], 'Phersistent'))
               {
                  echo "$attr is has one with relname $type[1]\n";
               }
            }
         }

         if ($c != null)
         {
            $def['__parent'] = array();
            $def = &$def['__parent'];
         }
      }

      return $definition;
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
   const DURATION = 'duration'; // ISO8601 Duration 'P1M', in PHP is DateInterval
   const TEXT     = 'text';


   private $__many = array();
   private $__one = array();

   public function __construct()
   {
      // set hasOne from declared associations
      $fields = get_class_vars($this);
      foreach ($fields as $attr => $value)
      {
         if (is_subclass_of($type, 'Phersistent'))
         {
            $this->hasOne($attr, $value);
         }
         if (is_array($type))
         {
            if (is_subclass_of($type[0], 'Phersistent'))
            {
               $this->hasOne($attr, $type[0], $type[1]);
            }
         }
      }
   }

   /**
    * $name UML relationship target name
    * $class target class
    * $relName UML relationship name
    */
   private function hasOne($name, $class, $relName = null)
   {
      $this->__one[$name] = new StdClass();
      $this->__one[$name]->class = $class; // A subclass of Phersistent
      $this->__one[$name]->relName = $relName;
   }

   /**
    * $name UML relationship target name
    * $class target class
    * $collectionType collection, list, set, orderedSet
    * $relName UML relationship name
    */
   protected function hasMany($name, $class, $collectionType = 'collection', $relName = null)
   {
      $this->__many[$name] = new StdClass();
      $this->__many[$name]->class = $class; // A subclass of Phersistent
      $this->__many[$name]->collectionType = $collectionType;
      $this->__many[$name]->relName = $relName;
   }

   /*
    * New instance of this class.
    */
   public function create($attrs = array())
   {
      //echo 'create '. $this->clax ."\n";

      $ins = new PhInstance();

      // Inject attributes declared on concrete subclass on new instance
      // This reads the own and inherited attributes of the custom Phersistent class
      foreach ($this as $attr=>$type)
      {
         // dont inject internal hasMany and hasOne definitions
         if ($attr == '__one' || $attr == '__many') continue;

         //echo "create $attr = $type\n";

         // Set values
         // TODO: implementar Phinstance en lugar de usar SdtClass, y ponerle set y get que castee a los tipos declarados.
         $value = null; // Default value
         if (isset($attrs[$attr])) $value = $attrs[$attr];

         // Injects the attribute and sets the value
         //$ins->{$attr} = $value; // this injects and sets the value but doesnt verifies it is a valid value
         $ins->{$attr} = NULL; // injects the attribute
         $setMethod = 'set'.$attr;
         $ins->$setMethod($value); // sets the value and verifies it's validity (type, etc)
      }

      // Inject many
      foreach ($this->__many as $attr=>$rel)
      {
         //print_r($rel);
         // TODO: podria usar $rel->class para restringir el contenido de las coleccions a esa clase
         if ($rel->collectionType == 'collection') $ins->{$attr} = new PhCollection();
         if ($rel->collectionType == 'list') $ins->{$attr} = new PhList();
         if ($rel->collectionType == 'set') $ins->{$attr} = new PhSet();
      }

      // Inject one
      foreach ($this->__one as $attr=>$rel)
      {
         $ins->{$attr} = NULL;
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

   /*
   public function __call($method, $args)
   {
      echo "call $method\n";
      if ( $this->{$method} instanceof Closure ) {
         return call_user_func_array($this->{$method},$args);
      } else {
         return parent::__call($method, $args);
      }
   }
   */
}


class PhersistentDefManager {

   // Always contains base class
   private $classDefinitions; // = array('Phersistent'=>new Phersistent());

   public function __construct()
   {
     $this->classDefinitions = array('Phersistent'=>new Phersistent());

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

   public function create($def, $attrs = array())
   {
      // TODO: check $def exists
      return $this->classDefinitions[$def]->create($attrs);
   }
}

?>

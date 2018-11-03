<?php

namespace phersistent;

use \StdClass;

//include_once "PhCollection.php";

class PhInstance {

  const NOT_LOADED_ASSOC = -1;

  // injected functions from class definition
  public $__functions = array();

  private function addTo($hasManyName, PhInstance $ins)
  {
    $this->{$hasManyName}->add( $ins );
  }

  public function get($attr)
  {
    // is has one, and is null but the FK is not ull, lazy load!
    if ($this->phclass->is_has_one($attr) && $this->{$attr} == null && $this->{$attr.'_id'} != null)
    {
      $has_one_class = $this->phclass->{$attr}; //get_has_one($attr)->class;
      $parts = explode('\\', $has_one_class);
      $class = $parts[count($parts)-1];
      $this->{$attr} = $GLOBALS[$class]->get($this->{$attr.'_id'});
    }
    return $this->{$attr};
  }

  public function set($attr, $value)
  {
    // TODO: type check against definition
    $this->{$attr} = $value;
  }

  public function setProperties($props = array())
  {
    // loops over the declared fields and get the values from props.
    // any other values not declared are ignored from props.

    $fields = $this->getDefinition()->get_all_fields();

    // fields doesnt have the FK fields, need to check for those
    // to set the property from props when FKs come.

    foreach ($fields as $attr => $type)
    {
      // Default value, need to detect if null is set explicitly
      $value = self::NOT_LOADED_ASSOC;
      if (array_key_exists($attr, $props)) $value = $props[$attr]; // can be null

      // the user wants to create an object from the array of values
      if ($this->phclass->is_has_one($attr) && is_array($value))
      {
        // creates an instance of the class declared in the HO attr with the value array
        $parts = explode('\\', $type);
        $class = $parts[count($parts)-1];
        $value = $GLOBALS[$class]->create($value);
      }

      // check FK fields to set
      if ($this->phclass->is_has_one($attr) && array_key_exists($attr.'_id', $props))
      {
        $setMethod = 'set'.$attr.'_id';
        $this->$setMethod($props[$attr.'_id']);
      }

      // sets the value and verifies it's validity (type, etc)
      if ($value !== self::NOT_LOADED_ASSOC)
      {
        $setMethod = 'set'.$attr;
        $this->$setMethod($value);
      }
    }
  }

  public function __call($method, $args)
  {
    if (array_key_exists($method, $this->__functions))
    {
      // custom injected methods from declaration need the instance to be passed
      return $this->__functions[$method]($this, $args);
    }

      // addToXYX
    if ( substr($method,0,5) == "addTo" )
    {
       $attr = lcfirst( substr($method, 5) ); // xyx
       if (!property_exists($this, $attr))
       {
          throw new \Exception("Object of type ". $this->getClass() ." doesn't have a declaration for a hasMany named '$attr'");
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
          throw new \Exception("Object of type ". $this->getClass() ." doesn't have a property named '$attr'");
       }

       return $this->get( $attr );
    }

    // setXXX
    // The value should be converted to the right type e.g. string dates -> DateTime
    if ( substr($method,0,3) == "set" )
    {
       //echo $method . PHP_EOL;
       //print_r($args);

       $attr = lcfirst(substr($method, 3)); // xxx
       if (!property_exists($this, $attr))
       {
          throw new \Exception("Object of type ". $this->getClass() ." doesn't have a property named '$attr'");
       }

       // TODO
       // 1. check if the class contains a definition of the attribute
       // 2. check if the value has the same type as the declared
       // 3. if the declared is date and the value is string, try to parse and convert to date, internally use string UTC time to store, since that is the one compatible with most DBs
       // 4. check if the declared is has many, the given value should be an array, of items of the same type as the declared

       $this->set($attr, $args[0]);
       return;
    }

    // TODO: remove from

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

  public function getParentClass()
  {
    return $this->phclass->get_parent();
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

          if (is_subclass_of($type, '\phersistent\Phersistent'))
          {
             echo "$attr is has one\n";
          }
          if (is_array($type))
          {
             if (is_subclass_of($type[0], '\phersistent\Phersistent'))
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

  // DB interaction functions
  public function save()
  {
    // 0. validate objects against constraints (TBD)
    // 1. transform object instance into database elements
    // 2. provide elements to DAL
    // 3. DAL will load the driver and do the ORM
    // 4. if object is saved for the first time, the id should be retrieved from the DB and assigned to the instance
    return $this->phclass->save($this);
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

  // This code is used for has_one to mark the link as not loaded when lazy loading
  // and to differentiate from the NULL value that is valid for has one.
  const NOT_LOADED_ASSOC = -1;

  private $__many = array();
  private $__one = array();
  private $__manager;
  protected $__functions = array();

  /**
   * PhersistentDefManager creates instances of the Phersistent to expose as
   * global class variables to simplify creating PhInstances from Phersistent.
   */
  public function __construct()
  {
    // set hasOne from declared associations
    //echo get_class($this) . PHP_EOL; // A, B, C
    //echo __CLASS__ . PHP_EOL; // Phersistent, Phersistent, Phersistent

    //$fields = get_class_vars($this);
    $fields = get_object_vars($this); //get_class_vars(get_class($this));

    foreach ($fields as $attr => $type)
    {
      //echo $attr .PHP_EOL;

      // avoid Phersistent fields
      if ($attr == '__one' || $attr == '__many' || $attr == '__functions') continue;

      if (is_subclass_of($type, '\phersistent\Phersistent'))
      {
        $this->hasOne($attr, $type);
      }
      if (is_array($type))
      {
        if (is_subclass_of($type[0], '\phersistent\Phersistent'))
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
      //foreach ($this as $attr=>$type)
      $fields = $this->get_all_fields();
      foreach ($fields as $attr => $type)
      {
         // dont inject internal hasMany and hasOne definitions
         //if ($attr == '__one' || $attr == '__many' || $attr == '__manager') continue;

         //echo "create $attr = $type\n";
         /*
         if (array_key_exists($attr, $this->__one))
         {
           echo $attr .' es HO '. PHP_EOL;
         }
         */

         // has many is injected below
         if (array_key_exists($attr, $this->__many)) continue;

         // Set values
         // TODO: implementar Phinstance en lugar de usar SdtClass, y ponerle set y get que castee a los tipos declarados.
         $value = null; // Default value
         if (isset($attrs[$attr])) $value = $attrs[$attr];

         // Injects the attribute and sets the value

         /* TODO: The has_one should be marked as not loaded if the FK attribute 'xxx_id'
            is not null on the database and this link is lazy loaded, so the ROM
            should set this, not this constructor.
         if (array_key_exists($attr, $this->__one))
         {
           $ins->{$attr} = self::NOT_LOADED_ASSOC;
         }
         else
         {
            $ins->{$attr} = NULL; // injects the attribute
         }
         */

         // injects the FK attribute,
         // TODO: this attribute should be set when the associated object is saved
         if (array_key_exists($attr, $this->__one))
         {
           $ins->{$attr.'_id'} = NULL;

           // if FK attribute comes, set it
           if (array_key_exists($attr.'_id', $attrs))
           {
             $ins->{$attr.'_id'} = $attrs[$attr.'_id'];
           }
         }
         $ins->{$attr} = NULL; // injects the attribute


         // if value comes in properties, set that value
         if (array_key_exists($attr, $attrs))
         {
           $setMethod = 'set'.$attr;

           // the user wants to create an object from the array of values
           if (array_key_exists($attr, $this->__one) && is_array($value))
           {
             // creates an instance of the class declared in the HO attr with the value array
             $value = $this->__manager->create($this->{$attr}, $value);
           }

           $ins->$setMethod($value); // sets the value and verifies it's validity (type, etc)
         }
      }

      // Inject many
      foreach ($this->__many as $attr=>$rel)
      {
         //print_r($rel);
         // TODO: podria usar $rel->class para restringir el contenido de las coleccions a esa clase
         if ($rel->collectionType == 'collection') $ins->{$attr} = new PhCollection();
         if ($rel->collectionType == 'list') $ins->{$attr} = new PhList();
         if ($rel->collectionType == 'set') $ins->{$attr} = new PhSet();

         // TODO: initialize properties for has many if values are passed
      }

      // Inject one
      /* has one is injected as a normal attribute
      foreach ($this->__one as $attr=>$rel)
      {
         $ins->{$attr} = NULL;
      }
      */


      // phclass merges all the inherited attr declarations from parent classes
      // it doesn't allow to reconstruct the separate definitions for multiple
      // table inheritance mapping
      $ins->phclass = $this;
      $ins->id = null;       // Default value
      $ins->deleted = false; // Default value
      $ins->class = $ins->getClass();


      // custom class functions injected to instances
      $ins->__functions = $this->__functions;


      return $ins;
   }

   public function get($id)
   {
     return $this->__manager->getInstance(get_class($this), $id);
   }

   public function listAll($max = 10, $offset = 0)
   {
     return $this->__manager->listInstances(get_class($this), $max, $offset);
   }

   public function save($phi)
   {
     return $this->__manager->saveInstance($phi);
   }

   public function isValidDef($otherClassName)
   {
      return is_subclass_of($otherClassName, '\phersistent\Phersistent');
   }

   public function get_parent()
   {
     return get_parent_class($this);
   }

   public function get_parent_phersistent()
   {
     return $this->__manager->getDefinition($this->get_parent());
   }

   /**
    * Returns all declared fields, on own class and inherited from parent.
    */
   public function get_all_fields()
   {
     $raw_fields = get_object_vars($this);
     return array_diff_key($raw_fields, array('__one'=>'', '__many'=>'', '__manager'=>'', '__functions'=>''));
   }

   /**
    * Retuns only declared fields on the specific class and do not includes
    * fields declared on parent classes. It is usedul to calculate multiple
    * table inheritance structures.
    */
   public function get_declared_fields()
   {
     $parent_class = $this->get_parent();
     $parent = $this->__manager->getDefinition($parent_class);

     $mine = $this->get_all_fields();
     $parents = $parent->get_all_fields();

     return array_diff_key($mine, $parents);
   }

   public function is_has_one($field)
   {
     return array_key_exists($field, $this->__one);
   }

   public function is_has_many($field)
   {
     return array_key_exists($field, $this->__many);
   }

   public function is_simple_field($field)
   {
     $fields = $this->get_all_fields();
     return array_key_exists($field, $fields) && !$this->is_has_one($field) && !$this->is_has_many($field);
   }

   public function get_has_one($field)
   {
     return $this->__one[$field];
   }
   public function get_has_many($field)
   {
     return $this->__many[$field];
   }


   /**
    * Configured from PhersistentDefManager.
    */
   public function set_manager($man)
   {
     $this->__manager = $man;
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

?>

<?php

namespace phersistent;

use \StdClass;

//include_once "PhCollection.php";

class PhInstance {

   private function addTo($hasManyName, PhInstance $ins)
   {
      $this->{$hasManyName}->add( $ins );
   }

   public function get($attr)
   {
      return $this->{$attr};
   }

   public function set($attr, $value)
   {
     $this->{$attr} = $value;
   }

   public function __call($method, $args)
   {
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

  public function __construct()
  {
    // set hasOne from declared associations
    //echo get_class($this) . PHP_EOL; // A, B, C
    //echo __CLASS__ . PHP_EOL; // Phersistent, Phersistent, Phersistent

    //$fields = get_class_vars($this);
    $fields = get_object_vars($this); //get_class_vars(get_class($this));

    //print_r(get_object_vars($this));
    //print_r($fields);

    foreach ($fields as $attr => $type)
    {
      //echo $attr .PHP_EOL;

      // avoid Phersistent fields
      if ($attr == '__one' || $attr == '__many') continue;

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
     return array_diff_key($raw_fields, array('__one'=>'', '__many'=>'', '__manager'=>''));
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

class PhTable {


}

class PhersistentMySQL {

  static public function save_instance($phi)
  {

  }

  /**
   * For now inheritance ORM is all STI.
   */
  static public function phi_to_data($phi)
  {
    // TODO: the returned item should be an array of tables
    // will contain the amy table, the associated via has one
    // and the join tables referencing the main and the assoc table
    // saving order will be always main + associated, saving associated first
    // to copy keys to owners, then join tables, always checking for loops.
    $table = array();

    if ($phi == null) return $table;

    $fields = $phi->getDefinition()->get_all_fields();
    foreach ($fields as $field => $type)
    {
      if ($phi->getDefinition()->is_simple_field($field)) // field
      {
        $table[$field] = $phi->get($field);
      }
      else if ($phi->getDefinition()->is_has_one($field)) // has one
      {
        // FK field
        $has_one_field = $field . '_id';

        // if has one is not saved, the id will be null
        // internally PhInstance will set the xxx_id field
        $table[$has_one_field] = $phi->get($has_one_field);

        // creates related table with the has_one value
        // the associated element can be null
        $table[$field] = self::phi_to_data($phi->get($field));
      }
      else // has many
      {
        // TBD: should create the join table
      }
    }

    // columns injected on instances
    $table['id'] = $phi->getId();
    $table['deleted'] = $phi->getDeleted();
    $table['class'] = $phi->getClass();

    return $table;
  }

  static function get_table_name(PhInstance $phi)
  {
    // TODO: should check STI and MTI (if part of STI, should return the name of the table where the STI is saved)
    // TODO: consider table name override declared on class

    // ***************************************************
    // For now inheritance ORM is all STI.
    // So need to check for parent = Phersistent, and that class will be the table name
    $ph = $phi->getDefinition();

    // go up in the inheritance until finding Phersistent
    while ($ph->get_parent() != 'phersistent\Phersistent')
    {
      $ph = $ph->get_parent_phersistent();
    }

    $class_name = get_class($ph);

    return self::class_to_table_name($class_name);
  }

  private static function class_to_table_name($class_name)
  {
    // removes class namespace
    $parts = explode('\\', $class_name);

    return strtr($parts[count($parts)-1],
                 "ABCDEFGHIJKLMNOPQRSTUVWXYZ ",
                 "abcdefghijklmnopqrstuvwxyz_");
  }

  /**
   * Maps each phersistent data type to a MySQL data type.
   */
  static function get_db_type($phersistent_type)
  {
    // TODO: consider constraints like max_length for texts
    switch ($phersistent_type)
    {
      case Phersistent::INT:
        return 'INT';
      break;
      case Phersistent::LONG:
        return 'BIGINT';
      break;
      case Phersistent::FLOAT:
        return 'FLOAT';
      break;
      case Phersistent::DOUBLE:
        return 'DOUBLE';
      break;
      case Phersistent::BOOLEAN:
        return 'BOOLEAN'; // synonym of TINYINT(1)
      break;
      case Phersistent::DATE:
        return 'DATE';
      break;
      case Phersistent::TIME:
        return 'TIME';
      break;
      case Phersistent::DATETIME:
        return 'DATETIME';
      break;
      case Phersistent::DURATION:
        return 'INT'; // durations will be stored in seconds and converted back to the duration expression
        // check https://stackoverflow.com/questions/13301142/php-how-to-convert-string-duration-to-iso-8601-duration-format-ie-30-minute
        // check https://gist.github.com/w0rldart/9e10aedd1ee55fc4bc74

      break;
      case Phersistent::TEXT:
        return 'TEXT';
      break;
      default:
        throw new \Exception('Data type '. $phersistent_type .' not supported');
    }
  }
}

?>

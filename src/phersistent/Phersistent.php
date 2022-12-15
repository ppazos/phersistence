<?php

namespace CaboLabs\Phersistence\phersistent;

use \StdClass;
use CaboLabs\PhBasic\BasicString;

class Phersistent extends stdClass { // extends to avoid dynamic property deprecated https://php.watch/versions/8.2/dynamic-properties-deprecated

  // injected attrs
  public $id = self::INT;
  public $deleted = self::BOOLEAN;
  public $class = self::TEXT;
  public $is_dirty = self::BOOLEAN; // this one shouldn't be saved (transient)

  public static $transients = ['is_dirty'];

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
  const SARRAY   = 'serialized_string_array'; // JSON encoded array of strings
  const SOBJECT  = 'serialized_object'; // JSON encoded object that is not Phersistent

  // This code is used for has_one to mark the link as not loaded when lazy loading
  // and to differentiate from the NULL value that is valid for has one.
  const NOT_LOADED_ASSOC = -1;

  private $__many = array();
  private $__one = array();
  private $__manager;
  protected $__constraints;
  protected $__functions; // = array();

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
      if ($attr == '__one' || $attr == '__many' || $attr == '__functions' || $attr == '__constraints') continue;

      if (is_subclass_of($type, '\CaboLabs\Phersistence\phersistent\Phersistent'))
      {
        $this->hasOne($attr, $type);
      }
      if (is_array($type))
      {
        // for has many declaration
        // public $locations = array(\phersistent\PhCollection::class, OrganizationProviderLocation::class);
        // $attr = $locations
        // $type[0] = collection class
        // $type[1] = associated class
        // $type[2] = options (optional array with specific keys)

        // for has one declaration
        // $attr = name of attribute
        // $type[0] = associated class
        // $type[1] = relname (optional)

        /*
        echo $type[0] .PHP_EOL;
        echo gettype($type[0]) .PHP_EOL; // string
        echo is_a($type[0], '\phersistent\PhCollection') .PHP_EOL;
        echo ($type[0] == '\phersistent\PhCollection') .PHP_EOL;
        echo ($type[0] == 'PhCollection') .PHP_EOL;
        echo (is_subclass_of($type[0], '\phersistent\PhCollection')) .PHP_EOL;
        echo array_slice(explode('\\', $type[0]), -1)[0] .PHP_EOL;
        */

        // class without namespace
        //$class = array_slice(explode('\\', $type[0]), -1)[0];

        
        // the trailig / is needed for the second comparison below
        //echo "1. ". \CaboLabs\Phersistence\phersistent\PhCollection::class ."\n"; // CaboLabs\Phersistence\phersistent\PhCollection
        //echo "2. ". CaboLabs\Phersistence\phersistent\PhCollection::class ."\n"; // CaboLabs\Phersistence\phersistent\CaboLabs\Phersistence\phersistent\PhCollection


        //if ($class == 'Phersistent')
        if (is_subclass_of($type[0], '\CaboLabs\Phersistence\phersistent\Phersistent'))
        {
          $this->hasOne($attr, $type[0], $type[1]);
        }
        else if (is_subclass_of($type[0], '\CaboLabs\Phersistence\phersistent\PhCollection') || $type[0] == \CaboLabs\Phersistence\phersistent\PhCollection::class)
        {
          //echo get_class($this) .' hasMany '. $attr .' <'. $type[1] .'>'. PHP_EOL;
          $this->hasMany($attr, $type[1], $type[0]);
        }
      }
    }

    // TODO: merge constraints from parent classes and override parent with children constraints
    $this->__constraints = $this->constraints();

    $this->__functions = $this->functions();
  }

  // This will be overriden by class declarations and return constraints for fields
  // owned or inherited.
  public function constraints()
  {
    return array();
  }

  public function functions()
  {
    return array();
  }

  // default values for fields
  public function init()
  {
    return array();
  }

  public function functionExists($func)
  {
    return array_key_exists($func, $this->__functions);
  }

  public function functionCall($func, $ins, $args)
  {
    if (!$this->functionExists($func))
    {
      throw new \Exception('Function '. $func .' doesnt exists');
    }
    array_unshift($args, $ins);
    //return $this->__functions[$func]($args);
    return call_user_func_array($this->__functions[$func], $args);
  }

  public function getHasOneDeclarations()
  {
    return $this->__one;
  }

  public function getHasManyDeclarations()
  {
    return $this->__many;
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
  protected function hasMany($name, $class, $collectionType, $relName = null)
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
    $ins = new PhInstance();

    // inject properties
    $ins->phclass = $this;

    /*
    $ins->id = null;     // Default value
    $ins->deleted = false; // Default value
    $ins->class = $ins->get_class();
    */

    // For setting default values if defined and no value was given in attrs
    $default_values = $this->init();
    $attrs = array_merge($default_values, $attrs); // attrs overwrite default


    // Inject attributes declared on concrete subclass on new instance
    // This reads the own and inherited attributes of the custom Phersistent class
    $fields = $this->get_all_fields();
    // TODO we could avoid iterating through all the class attrs, we just need default_values
    //      and the attrs params merged together to use as the iteration list
    foreach ($fields as $attr => $type)
    {
      // has many is injected below
      if (array_key_exists($attr, $this->__many)) continue;

       // injects the attribute
      $ins->{$attr} = NULL;

      $value = $attrs[$attr] ?? NULL;

      // will be used below to set the value
      $setMethod = 'set_'.$attr;


      // has one attributs process

      // injects the FK attribute,
      // TODO: this attribute should be set when the associated object is saved
      if (array_key_exists($attr, $this->__one))
      {
        $ins->{$attr.'_id'} = NULL; // if the attr_id is NULL, attr should be NULL

        // if FK attribute comes, set it
        if (array_key_exists($attr.'_id', $attrs))
        {
          $ins->{$attr.'_id'} = $attrs[$attr.'_id'];  // if the attr_id is not NULL, the attr should be NOT_LOADED
          $ins->$attr = self::NOT_LOADED_ASSOC; // marking the hasone as not loaded
        }

        // want to create a HO object from the array of values
        if (is_array($value))
        {
          // creates an instance of the class declared in the HO attr with the value array
          // this->attr is the declared class for the has one attribute
          $value = $this->__manager->create($this->{$attr}, $value);
        }

        if ($value != NULL)
        {
          $ins->$setMethod($value);
        }

        continue; // has one processed
      }


      // common attributes process

      // for serialized arrays
      if ($this->is_serialized_array($attr))
      {
        // the value comes as a string, then decode
        if (is_string($value) && $value !== '')
        {
          $value = json_decode($value);
        }
        else if (is_array($value))
        {
          // ensure every value in the array is string
          array_walk($value, function(&$value, $key) {
            $value = (string)$value;
          });
        }
      }
      
      // sets normal attributes and serialized arrays/objects
      $ins->$setMethod($value);
    }

    // inject has many
    foreach ($this->__many as $attr=>$rel)
    {
      // This declared the field in the instance, which is needed to check if the field exists in the instance
      //$ins->{$attr} = null;
      $ins->$attr = self::NOT_LOADED_ASSOC; // marking the hasmany as not loaded

      if (isset($attrs[$attr]) && is_array($attrs[$attr]))
      {
        // Only instantiate the collection if there are values for it, so if the collection
        // is NULL we know the collection was not loaded and we can use the NULL value
        // to lazy load the items when required.
        //
        $ins->initialize_has_many($attr);

        // items in the array should already by PhInstances
        $hm = $attrs[$attr];
        foreach ($hm as $phi)
        {
          $addToHMMethod = 'add_to_'. $attr;
          //if (!($phi instanceof PhInstance)) throw new \Exception("Object intitialization with has many requires all objects to be instances of Phersistent");
          //else
          // add to already checks the value is PhInstance
          $ins->{$addToHMMethod}($phi);
        }
      }
    }

    // TODO: check if the declarations of these fields could be in the Phinstance.
    // Default values
    $ins->deleted = false;
    $ins->class = $ins->getClass(); // asks the definition class

    // for new instances, it should be dirty, for loading from the DB should be
    // clean, the manager or db should set it as false after creation, and will
    // be dirty again if any modifier method is called
    $ins->is_dirty = true;

    return $ins;
  }

  public function get($id)
  {
    return $this->__manager->getInstance(get_class($this), $id);
  }

  public function count()
  {
    return $this->__manager->count(get_class($this));
  }

  public function listAll($max = 10, $offset = 0, $sort = 'id', $order = 'ASC')
  {
    return $this->__manager->listInstances(get_class($this), $max, $offset, $sort, $order);
  }

  public function list_has_many($owner, $hm_attr, $hm_class)
  {
    return $this->__manager->listHasManyInstances($owner, $hm_attr, $hm_class);
  }

  public function findBy($where = array(), $max = 10, $offset = 0, $sort = 'id', $order = 'ASC')
  {
    if (count($where) == 0) return $this->listAll($max, $offset, $sort, $order);
    return $this->__manager->findBy(get_class($this), $where, $max, $offset, $sort, $order);
  }

  public function countBy($where = array())
  {
    if (count($where) == 0) return $this->count();
    return $this->__manager->countBy(get_class($this), $where);
  }

  public function save($phi)
  {
    return $this->__manager->saveInstance($phi);
  }

  public function delete($phi, $logical)
  {
    $this->__manager->deleteInstance($phi, $logical);
  }

  public function isValidDef($otherClassName)
  {
    return is_subclass_of($otherClassName, 'CaboLabs\Phersistence\phersistent\Phersistent');
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
    return array_diff_key($raw_fields, array('__one'=>'', '__many'=>'', '__manager'=>'', '__functions'=>'', '__constraints'=>''));
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
    return array_key_exists($field, $fields) &&
           !$this->is_has_one($field) &&
           !$this->is_has_many($field) &&
           $fields[$field] != self::SARRAY &&
           $fields[$field] != self::SOBJECT;
  }

  public function is_serialized_array($field)
  {
    $fields = $this->get_all_fields();
    return array_key_exists($field, $fields) && $fields[$field] == self::SARRAY;
  }

  public function is_serialized_object($field)
  {
    $fields = $this->get_all_fields();
    return array_key_exists($field, $fields) && $fields[$field] == self::SOBJECT;
  }

  public function get_has_one($field)
  {
    if (!$this->is_has_one($field))
    {
      throw new \Exception('Has one '. $field .' doesnt exists');
    }
    return $this->__one[$field];
  }
  public function get_has_many($field)
  {
    if (!$this->is_has_many($field))
    {
      throw new \Exception('Has many '. $field .' doesnt exists');
    }
    return $this->__many[$field];
  }

  public function has_many_exists($class)
  {
    foreach ($this->__many as $name => $rel)
    {
      if ($rel->class == $class) return true;
    }
    return false;
  }

  public function is_one_to_many($hmattr)
  {
    if (!$this->is_has_many($hmattr))
    {
      return false; // it is not even a has many
    }

    $hmrel = $this->get_has_many($hmattr); // class, collectionType, relName

    // if A has_many A, then that is one_to_many, we don't support many_to_many yet,
    // so A many_to_many A is not possible to declare directly, could be possible
    // declaring the JOIN table as an extra model.

    // if the has many was declared to the same class
    // hmrel->class has the namespace, also the get_class
    if ($hmrel->class == get_class($this))
    {
      return true;
    }

    // if the has_many was declared to another class
    $assoc_ph = $this->__manager->getDefinition($hmrel->class);

    // if assoc has many class, has many of self, is many to many
    if ($assoc_ph->has_many_exists(get_class($this)))
    {
      return false;
    }

    // if assoc has many, has one of self or doesn't have any other explicit
    // association with self, then this is one to many from self
    // no need to check the cases since the only exception is the many to many
    // checked above

    return true;
  }

  public function is_many_to_many($hmattr)
  {
    return !$this->is_one_to_many($hmattr);
  }


  // type checks
  public function is_boolean($attr)
  {
    if (!property_exists($this, $attr))
    {
      // check if attr is an injected FK, if it is injected, is not declared on the PH!
      // xxx_id ends with id and xxx is a has_one
      if (BasicString::endsWith($attr, '_id') && $this->is_has_one(BasicString::removeSuffix($attr, '_id')))
      {
        return false; // FK attr is INT
      }
      else
      {
        throw new \Exception('Attribute "'. $attr .'" is not declared on class '. get_class($this));
      }
    }

    return $this->{$attr} == self::BOOLEAN;
  }

  public function is_number($attr)
  {
    if (!property_exists($this, $attr))
    {
      // check if attr is an injected FK, if it is injected, is not declared on the PH!
      // xxx_id ends with id and xxx is a has_one
      if (BasicString::endsWith($attr, '_id') && $this->is_has_one(BasicString::removeSuffix($attr, '_id')))
      {
        return true; // FK attr is INT
      }
      else
      {
        throw new \Exception('Attribute '. $attr .' is not declared on class '. get_class($this));
      }
    }

    return in_array($this->{$attr}, array(self::INT, self::LONG, self::FLOAT, self::DOUBLE));
  }

  public function is_int($attr)
  {
    if (!property_exists($this, $attr))
    {
      // check if attr is an injected FK, if it is injected, is not declared on the PH!
      // xxx_id ends with id and xxx is a has_one
      if (BasicString::endsWith($attr, '_id') && $this->is_has_one(BasicString::removeSuffix($attr, '_id')))
      {
        return true; // FK attr is INT
      }
      else
      {
        throw new \Exception('Attribute '. $attr .' is not declared on class '. get_class($this));
      }
    }

    return in_array($this->{$attr}, array(self::INT, self::LONG));
  }

  public function is_real($attr)
  {
    return $this->is_number($attr) && !$this->is_int($attr);
  }


  /**
   * Configured from PhersistentDefManager.
   */
  public function set_manager($man)
  {
    $this->__manager = $man;
  }

  public function get_manager()
  {
    return $this->__manager;
  }

  public function get_all_constraints()
  {
    return $this->__constraints;
  }

  public function get_constraints($attr)
  {
    if (array_key_exists($attr, $this->__constraints))
    {
      return $this->__constraints[$attr];
    }

    return array();
  }

  // true if the attribute has a nullable(true) constraint or no nullable constraint at all (default is nullable),
  // false if there is a nullable(false) constraints
  public function is_nullable($attr)
  {
    $constraints = $this->get_constraints($attr);

    $attr_is_nullable = true;

    foreach ($constraints as $c)
    {
      if ($c instanceof Nullable && $c->getValue() === false)
      {
        $attr_is_nullable = false;
        break;
      }
    }

    return $attr_is_nullable;
  }

  public function runRaw($sql)
  {
    return $this->__manager->runRaw($sql);
  }

  // Returns the Phersistent subclasses of this Phersistent class
  public function getSubclasses()
  {
    $parent = get_class($this);

    //echo "---------- CLASS ". $parent . PHP_EOL;

    $subclasses = array();
    foreach (get_declared_classes() as $class)
    {
      if (is_subclass_of($class, $parent)) $subclasses[] = $class;
    }

    return $subclasses;
  }

  public function backlink_name(PhInstance $phi, $hm_field)
  {
    // table name declared in the class
    if (property_exists($this, 'table'))
    {
      $prefix = $this->table;
    }
    else
    {
      // if CURRENT_CLASS(hasmany(assoc,OTHER_CLASS))
      // then $backlink_name = current_class_assoc_id
      // and that column should exist on the OTHER_CLASS table
      $prefix = $this->__manager->get_db()->class_to_table_name($phi->getClass());
    }

    return strtolower($prefix .'_'. $hm_field .'_back');
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

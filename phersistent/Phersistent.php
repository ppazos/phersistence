<?php

namespace phersistent;

use \StdClass;

class Phersistent {

  // injected attrs
  public $id = self::INT;
  public $deleted = self::BOOLEAN;
  public $class = self::TEXT;

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
  const SARRAY   = 'serialized_string_array';

  // This code is used for has_one to mark the link as not loaded when lazy loading
  // and to differentiate from the NULL value that is valid for has one.
  const NOT_LOADED_ASSOC = -1;

  private $__many = array();
  private $__one = array();
  private $__manager;
  protected $__constraints;
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
      if ($attr == '__one' || $attr == '__many' || $attr == '__functions' || $attr == '__constraints') continue;

      if (is_subclass_of($type, '\phersistent\Phersistent'))
      {
        $this->hasOne($attr, $type);
      }
      if (is_array($type))
      {
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
        $class = array_slice(explode('\\', $type[0]), -1)[0];

        //if (is_subclass_of($type[0], '\phersistent\Phersistent'))
        if ($class == 'Phersistent')
        {
          $this->hasOne($attr, $type[0], $type[1]);
        }
        else if ($class == 'PhCollection')
        {
          //echo get_class($this) .' hasMany '. $attr .' <'. $type[1] .'>'. PHP_EOL;
          $this->hasMany($attr, $type[1]);
        }
      }
    }

    // TODO: merge constraints from parent classes and override parent with children constraints
    $this->__constraints = $this->constraints();
  }

  // This will be overriden by class declarations and return constraints for fields
  // owned or inherited.
  public function constraints()
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
    return $this->__functions[$func]($ins, $args);
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

    // inject properties
    $ins->phclass = $this;

    /*
    $ins->id = null;     // Default value
    $ins->deleted = false; // Default value
    $ins->class = $ins->getClass();
    */

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

        // for serialized arrays
        if ($this->is_serialized_array($attr))
        {
          // the value comes as a string, then decode
          if (is_string($value))
          {
            $value = json_decode($value);
          }
          else if (is_array($value))
          {
            // ensure every value in the array is string
            array_walk($value, function(&$value, &$key) {
              $value = (string)$value;
            });
          }
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

    // Default values
    $ins->deleted = false;
    $ins->class = $ins->getClass();

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

  public function listAll($max = 10, $offset = 0)
  {
    return $this->__manager->listInstances(get_class($this), $max, $offset);
  }

  public function list_has_many($owner, $hm_attr, $hm_class)
  {
    return $this->__manager->listHasManyInstances($owner, $hm_attr, $hm_class);
  }

  public function findBy($where = array(), $max = 10, $offset = 0)
  {
    if (count($where) == 0) return $this->listAll($max, $offset);
    return $this->__manager->findBy(get_class($this), $where, $max, $offset);
  }

  public function save($phi)
  {
    return $this->__manager->saveInstance($phi);
  }

  public function delete($phi)
  {
    $this->__manager->deleteInstance($phi);
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
    return array_key_exists($field, $fields) && !$this->is_has_one($field) && !$this->is_has_many($field) && $fields[$field] != self::SARRAY;
  }

  public function is_serialized_array($field)
  {
    $fields = $this->get_all_fields();
    return array_key_exists($field, $fields) && $fields[$field] == self::SARRAY;
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

    $hmrel = $this->get_has_many($hmattr);

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
      if (\basic\BasicString::endsWith($attr, '_id') && $this->is_has_one(\basic\BasicString::removeSuffix($attr, '_id')))
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
      if (\basic\BasicString::endsWith($attr, '_id') && $this->is_has_one(\basic\BasicString::removeSuffix($attr, '_id')))
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


  /**
   * Configured from PhersistentDefManager.
   */
  public function set_manager($man)
  {
    $this->__manager = $man;
  }


  public function get_constraints($attr)
  {
    if (array_key_exists($attr, $this->__constraints))
    {
      return $this->__constraints[$attr];
    }

    return array();
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

<?php

namespace phersistent;

class PhInstance {

  const NOT_LOADED_ASSOC = -1;

  // Validation errors from last validation of this instance
  private $errors;

  public function hasErrors()
  {
    // errors is set and at least one field has errors
    return (isset($this->errors) && count($this->errors) > 0);
  }

  public function getErrors()
  {
    return $this->errors;
  }

  private function addTo($hasManyName, PhInstance $ins)
  {
    $this->{$hasManyName}->add( $ins );
  }

  private function cleanFrom($hasManyName)
  {
    $this->{$hasManyName}->clean();
  }

  private function removeFrom($hasManyName, PhInstance $ins)
  {
    // to remove an instance, it should be saved since the id is used to find a match
    if ($ins->get_id() == null)
    {
      throw new \Exception("Not saved object of type ". $this->getClass() ." can't be removed from '$hasManyName'");
    }

    // collection not loaded? => load to execute the removeFrom
    /* FIXME: need a lazy load lookup on a different object than the
    hasmany to avoid setting values of different types to the same
    field, also the collection will be intialized even if the items
    are not loaded.
    if ($this->{$hasManyName} == self::NOT_LOADED_ASSOC)
    {
      // TBD
    }
    */

    $this->{$hasManyName}->remove($ins);
  }

  private function size($hasManyName)
  {
    return $this->{$hasManyName}->size();
  }

  /** JSON Array Field Operations */
  /**
   * add a string to the string array attribute
   */
  private function pushTo($sarrayAttr, $value)
  {
    if (!is_string($value)) throw new \Exception('Value should be a string');
    $this->{$sarrayAttr}[] = $value; // if the array is null, this also initializes it
  }
  private function delFrom($sarrayAttr, $value)
  {
    if (!is_string($value)) throw new \Exception('Value should be a string');
    $this->{$sarrayAttr} = array_diff($this->{$sarrayAttr}, array($value));
  }
  private function hasValue($sarrayAttr, $value)
  {
    if (!is_string($value)) throw new \Exception('Value should be a string');
    return in_array($value, $this->{$sarrayAttr});
  }


  public function get($attr)
  {
    // is has one, and is null but the FK is not null, lazy load!
    if ($this->phclass->is_has_one($attr) && $this->{$attr} == null && $this->{$attr.'_id'} != null)
    {
      $has_one_class = $this->phclass->{$attr}; //same as $this->phclass->get_has_one($attr)->class;
      $parts = explode('\\', $has_one_class);
      $class = $parts[count($parts)-1];
      $this->{$attr} = $GLOBALS[$class]->get($this->{$attr.'_id'});
    }
    else if ($this->phclass->is_one_to_many($attr) && $this->{$attr}->size() == 0 && $this->get_id() != null) // can lazy load only if current instance has id
    {
      //$hm_class = $this->phclass->get_has_many($attr)->class;
      //$parts = explode('\\', $has_one_class);
      //$class = $parts[count($parts)-1];
      // lazy load has many for one to many
      $hm_class = $this->phclass->get_has_many($attr)->class;
      $instances = $this->phclass->list_has_many($this, $attr, $hm_class);
      $this->{$attr}->add_all($instances);
    }

    return $this->{$attr};
  }

  public function set($attr, $value)
  {
    // TODO: type check against attr definition

    if ($this->phclass->is_serialized_object($attr))
    {
      if (!(is_null($value) || is_array($value)))
      {
        // ERROR: SOBJECT values should be null or array
        throw new \Exception('Serialized object field '. $attr .' cant be set to value of type '. gettype($value) .', it should be null or an array');
      }
    }

    if ($this->phclass->is_boolean($attr) && !is_bool($value))
    {
      $value = boolval($value);
    }
    else if ($this->phclass->is_number($attr) && is_string($value))
    {
      // TODO: check for PHP_INT_MAX since the value can be truncated
      $value = $value + 0; // converts the value to a number
    }
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

      if ($this->phclass->is_serialized_array($attr))
      {
        // the value comes as a string, then decode
        if (is_string($value))
        {
          $value = json_decode($value);

          if ($value === NULL)
          {
            $error = '';
            switch (json_last_error()) {
              case JSON_ERROR_NONE:
                $error = ' - No errors';
              break;
              case JSON_ERROR_DEPTH:
                $error = ' - Maximum stack depth exceeded';
              break;
              case JSON_ERROR_STATE_MISMATCH:
                $error = ' - Underflow or the modes mismatch';
              break;
              case JSON_ERROR_CTRL_CHAR:
                $error = ' - Unexpected control character found';
              break;
              case JSON_ERROR_SYNTAX:
                $error = ' - Syntax error, malformed JSON';
              break;
              case JSON_ERROR_UTF8:
                $error = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
              break;
              default:
                $error = ' - Unknown error';
              break;
            }

            throw new \Exception('Error decoding json array '. $error);
          }
        }
        else if (is_array($value))
        {
          // ensure every value in the array is string
          array_walk($value, function(&$value, &$key) {
            $value = (string)$value;
          });
        }
        else
        {
          throw new \Exception('Serialized array field '. $attr .' can only be initialized with an array, non array passed');
        }
      }
      else if ($this->phclass->is_serialized_object($attr))
      {
        // the value comes as a string, then decode
        if (is_string($value))
        {
          $value = json_decode($value, true); // decode as assoc array, not object

          if ($value === NULL)
          {
            $error = '';
            switch (json_last_error()) {
              case JSON_ERROR_NONE:
                $error = ' - No errors';
              break;
              case JSON_ERROR_DEPTH:
                $error = ' - Maximum stack depth exceeded';
              break;
              case JSON_ERROR_STATE_MISMATCH:
                $error = ' - Underflow or the modes mismatch';
              break;
              case JSON_ERROR_CTRL_CHAR:
                $error = ' - Unexpected control character found';
              break;
              case JSON_ERROR_SYNTAX:
                $error = ' - Syntax error, malformed JSON';
              break;
              case JSON_ERROR_UTF8:
                $error = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
              break;
              default:
                $error = ' - Unknown error';
              break;
            }

            throw new \Exception('Error decoding json object '. $error);
          }
        }
        else if (is_array($value))
        {
          // do nothing: array values are all valid for serialized objects
        }
        else
        {
          throw new \Exception('Serialized array field '. $attr .' can only be initialized with an array, non array passed');
        }
      }
      // the user wants to create/update an object from the array of values
      else if ($this->phclass->is_has_one($attr) && is_array($value))
      {
        $has_one_values = $value;

        $current_value = $this->get($attr);

        // $value is set bellow
        if ($current_value == null)
        {
          // creates an instance of the class declared in the HO attr with the value array
          $parts = explode('\\', $type);
          $class = $parts[count($parts)-1];
          $value = $GLOBALS[$class]->create();
          $value->setProperties($has_one_values); // could be recursive
        }
        else // updates current value
        {
          $current_value->setProperties($has_one_values); // could be recursive
          $value = $current_value;
        }
      }
      // check FK fields to set
      else if ($this->phclass->is_has_one($attr) && array_key_exists($attr.'_id', $props))
      {
        $setMethod = 'set_'.$attr.'_id';
        $this->$setMethod($props[$attr.'_id']);
      }
      else if ($this->phclass->is_has_many($attr) && is_array($value))
      {
        // if properties want to set has many, the has many is cleaned up
        // *** all the items are removed ***
        $cleanMethod = 'clean_'. $attr;
        $this->{$cleanMethod}();

        $addToHMMethod = 'add_to_'. $attr;
        $hm = $value;
        foreach ($hm as $phi)
        {
          $this->{$addToHMMethod}($phi); // verifies phi instanceof PhInstance
        }
      }

      // sets the value and verifies it's validity (type, etc)
      if ($value !== self::NOT_LOADED_ASSOC)
      {
        $setMethod = 'set_'.$attr;
        $this->$setMethod($value);
      }
    }
  }

  public function __call($method, $args)
  {
    if ($this->phclass->functionExists($method))
    {
      return $this->phclass->functionCall($method, $this, $args);
    }

    // get_XYZ
    if (substr($method,0,4) == "get_")
    {
      $attr = lcfirst( substr($method, 4) ); // xyz
      if (!property_exists($this, $attr))
      {
        throw new \Exception("Object of type ". $this->getClass() ." doesn't have a property named '$attr'");
      }

      return $this->get($attr);
    }

    // set_XXX
    // The value should be converted to the right type e.g. string dates -> DateTime
    if (substr($method,0,4) == "set_")
    {
      $attr = lcfirst(substr($method, 4)); // xxx
      if (!property_exists($this, $attr))
      {
        throw new \Exception("Object of type ". $this->getClass() ." doesn't have a property named '$attr'");
      }

      // TODO
      // 1. check if the class contains a definition of the attribute
      // 2. check if the value has the same type as the declared
      // 3. if the declared is date and the value is string, try to parse and convert to date, internally use string UTC time to store, since that is the one compatible with most DBs
      // 4. check if the declared is has many, the given value should be an array, of items of the same type as the declared

      $this->is_dirty = true;

      // this might set is_dirty to false, that is why we set dirty=true before
      // this line, this order is important!
      $this->set($attr, $args[0]);

      return;
    }

    // add_to_XYX
    if (substr($method,0,7) == "add_to_")
    {
      $attr = lcfirst(substr($method, 7)); // xyx
      if (!property_exists($this, $attr))
      {
        throw new \Exception("Object of type ". $this->getClass() ." doesn't have a declaration for a hasMany named '$attr'");
      }
      $this->addTo($attr, $args[0]);
      $this->is_dirty = true;
      return;
    }

    // remove_from_XXX
    if (substr($method,0,12) == "remove_from_")
    {
      $attr = lcfirst(substr($method, 12));
      if (!property_exists($this, $attr))
      {
        throw new \Exception("Object of type ". $this->getClass() ." doesn't have a declaration for a hasMany named '$attr'");
      }
      $this->removeFrom($attr, $args[0]);
      $this->is_dirty = true;
      return;
    }

    // clean_xxx
    if (substr($method,0,6) == "clean_")
    {
      $attr = lcfirst(substr($method, 6));
      if (!property_exists($this, $attr))
      {
        throw new \Exception("Object of type ". $this->getClass() ." doesn't have a declaration for a hasMany named '$attr'");
      }
      $this->cleanFrom($attr);
      $this->is_dirty = true;
      return;
    }

    if (substr($method,0,8) == "push_to_")
    {
      $attr = lcfirst(substr($method, 8));
      if (!property_exists($this, $attr))
      {
        throw new \Exception("Object of type ". $this->getClass() ." doesn't have a declaration named '$attr'");
      }
      $this->pushTo($attr, $args[0]);
      $this->is_dirty = true;
      return;
    }

    if (substr($method,0,9) == "del_from_")
    {
      $attr = lcfirst(substr($method, 9));
      if (!property_exists($this, $attr))
      {
        throw new \Exception("Object of type ". $this->getClass() ." doesn't have a declaration named '$attr'");
      }
      $this->delFrom($attr, $args[0]);
      $this->is_dirty = true;
      return;
    }

    if (substr($method,0,13) == "has_value_in_")
    {
      $attr = lcfirst(substr($method, 13));
      if (!property_exists($this, $attr))
      {
        throw new \Exception("Object of type ". $this->getClass() ." doesn't have a declaration named '$attr'");
      }
      return $this->hasValue($attr, $args[0]);
    }

    // size_XXX
    if (substr($method,0,5) == "size_")
    {
      $attr = lcfirst(substr($method, 5));
      if (!property_exists($this, $attr))
      {
        throw new \Exception("Object of type ". $this->getClass() ." doesn't have a declaration for a hasMany named '$attr'");
      }
      return $this->size($attr);
    }

    // TODO: method not found exception
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

    if (($validation = $this->validate()) !== true)
    {
      $this->errors = $validation;
      return false; // validation = false
    }

    $res = $this->phclass->save($this);
    $this->is_dirty = false; // clean after save
    return $res; // returns the id of the saved object, > 0
  }

  // TODO: support logical delete
  public function delete()
  {
    $this->phclass->delete($this);
  }

  /**
   * return the current values for all has one attributes, used on save.
   */
  public function getAllHasOne()
  {
    $hasone = array();
    foreach ($this->phclass->getHasOneDeclarations() as $attr=>$rel)
    {
      $hasone[$attr] = $this->get($attr);
    }
    return $hasone;
  }

  /**
   * return the current values for all has many attributes, used on save.
   */
  public function getAllHasMany()
  {
    $hasmany = array();
    foreach ($this->phclass->getHasManyDeclarations() as $attr=>$rel)
    {
      $hasmany[$attr] = $this->get($attr); // value is a collection
    }
    return $hasmany;
  }

  /**
   * Used on save of has many cascade for one to many where the backlink is set
   * on the many side.
   */
  public function setBacklinkId($class, $field, $toclass, $backlinkName, $id)
  {
    //echo 'setBacklinkId on '. $toclass .' '. $backlinkName . PHP_EOL;
    // TODO: use the class and field to check if there is a declaration of
    // a has many on the class to the toclass.
    $this->{$backlinkName} = $id;
  }

  // FIXME: get constraints from the parent, since we also process attrs inherited
  // TODO: if a constraint of the same type for the same attr is defined on parent
  //       and child class, the constraint on the child overrides the parent constraint
  public function validate($cascade = true)
  {
    $errors = array();

    /* this validation was iterating though all the attrs even if those didnt
       have a constraint, below is a better solution, iterating though the constraints

    $simple_fields = $this->phclass->get_all_fields();
    foreach ($simple_fields as $attr=>$type)
    {
      //if (in_array($attr, array('id', 'class', 'deleted'))) continue;

      $validation_res = FieldValidator::validate($this, $attr);
      // if errors are returned already have the name of the attr on it
      if ($validation_res !== true) $errors[$attr] = $validation_res;

      // $cs = $this->phclass->get_constraints($attr);
      // foreach ($cs as $c)
      // {
      //   if (($e = $c->validate($this->getClass(), $attr, $this->get($attr), $this)) !== true)
      //   {
      //     if (!isset($errors[$attr])) $errors[$attr] = array();
      //     $errors[$attr][] = $e;
      //   }
      // }
    }
    */

    // validates only based on existing constraints
    // there is no need to iterate through all the fields
    $constraints = $this->phclass->get_all_constraints();
    foreach ($constraints as $attr => $attr_constraints)
    {
      $validation_res = FieldValidator::validate($this, $attr, $attr_constraints);
      // if errors are returned already have the name of the attr on it
      if ($validation_res !== true) $errors[$attr] = $validation_res;
    }


    if ($cascade)
    {
      $hos = $this->getAllHasOne();

      foreach ($hos as $attr => $hoi)
      {
        // cant call to validate if the hoi is null
        if ($hoi == null) continue;

        $ho_errors = $hoi->validate(true);
        // merge of HO errors into the instance errors,
        // that might also contain other cascade errors.
        if ($ho_errors !== true) $errors[$attr] = $ho_errors;
      }

      $hms = $this->getAllHasMany();
      foreach ($hms as $attr => $hmc) // collection
      {
        foreach ($hmc as $i => $hmi) // instance
        {
          // cant call to validate if the hmi is null
          if ($hmi == null) continue;

          $hm_errors = $hmi->validate();
          if ($hm_errors !== true)
          {
            if (!isset($errors[$attr])) $errors[$attr] = array();
             // the index is important to know which instance violated the constriants
            $errors[$attr][$i] = $hm_errors;
          }
        }
      }
    }

    if (count($errors) == 0) return true;
    return new ObjectValidationErrors($errors);
  }
}

?>

<?php

namespace CaboLabs\Phersistence\phersistent;

use CaboLabs\Phersistence\phersistent\PhSet;

use CaboLabs\PhBasic\BasicString;
use stdClass;

class PhInstance extends stdClass { // extends to avoid dynamic property deprecated https://php.watch/versions/8.2/dynamic-properties-deprecated

  const NOT_LOADED_ASSOC = -1;
  const VALUE_NOT_PROVIDED = "\x20";

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
    if ($this->{$hasManyName} === self::NOT_LOADED_ASSOC) // not loaded
    {
      // NOTE: this will load all the hasmany items, if the amount is big, this will cause problems
      $this->get($hasManyName); // does the lazy load
    }

    return $this->{$hasManyName}->add($ins);
  }

  private function cleanFrom($hasManyName, $options = [])
  {
    if ($this->{$hasManyName} === self::NOT_LOADED_ASSOC) // not loaded
    {
      // NOTE: this will load all the hasmany items, if the amount is big, this will cause problems
      $this->get($hasManyName); // does the lazy load
    }

    // the collection is empty, there is no clean to do
    if ($this->{$hasManyName}->size() == 0) return false;


    $items = $this->{$hasManyName}->all();

    // NOTE: doing a clean will leave orphan items in the database, because are removed from the parent here.
    // removes all objects from the collection without any checks
    $this->{$hasManyName}->clean();

    // try to delete orphans, if we can't, just nullify the backlink
    if (in_array('DELETE_ORPHANS', $options))
    {
      foreach ($items as $ins)
      {
        try
        {
          $ins->delete();
        }
        catch (\Exception $e)
        {
          // nullify backlink
          $backlink_name = $this->phclass->backlink_name($this, $hasManyName);
          $ins->{'set_'. $backlink_name}(NULL);
          $ins->save();
        }
      }
    }
    // nullify backlinks
    else
    {
      // NOTE: this is innefficient when hasmany has thousands of object, it should be documented.
      foreach ($items as $ins)
      {
        // nullify backlink
        $backlink_name = $this->phclass->backlink_name($this, $hasManyName);
        $ins->{'set_'. $backlink_name}(NULL);
        $ins->save();
      }
    }

    return true;
  }

  private function removeFrom($hasManyName, PhInstance $ins)
  {
    // to remove an instance, it should be saved since the id is used to find a match,
    // because if the remove is done, even if the equality is checking other attributes than
    // id, when the remove is done from the collmection, the DB should be updated removing the
    // backlink and maybe the $ins if the remove cascades the remove to be a delete (cascade is not currently supported).
    if ($ins->id == null)
    {
      throw new \Exception("Not saved object of type ". $this->getClass() ." can't be removed from '$hasManyName'");
    }

    if ($this->{$hasManyName} === self::NOT_LOADED_ASSOC) // not loaded
    {
      // NOTE: this will load all the hasmany items, if the amount is big, this will cause problems
      $this->get($hasManyName); // does the lazy load
    }

    $removed = $this->{$hasManyName}->remove($ins);
    if ($removed)
    {
      // nullify backlink

      // this is only for one-to-many for now
      $backlink_name = $this->phclass->backlink_name($this, $hasManyName);
      $ins->{'set_'. $backlink_name}(NULL);
      $ins->save();
    }

    return $removed;
  }

  /* this is implemented in the same dynamic call in __call for 'remove_from'
  // Similar to removeFrom but if the $ins was removed, also deletes it from the DB
  private function removeFromAndDelete($hasManyName, PhInstance $ins)
  {
    if ($ins->id == null)
    {
      throw new \Exception("Not saved object of type ". $this->getClass() ." can't be removed from '$hasManyName'");
    }

    if ($this->{$hasManyName} === self::NOT_LOADED_ASSOC)
    {
      // NOTE: this will load all the hasmany items, if the amount is big, this will cause problems
      $this->get($hasManyName); // does the lazy load
    }

    $removed = $this->{$hasManyName}->remove($ins);
    if ($removed)
    {
      // delete $ins
      $ins->delete();
    }

    return $removed;
  }
  */

  // counts elements in the hasmany collection, loads them from the DB is not loaded
  // TODO: this method can be optimized by using a countBy, since we don't really
  // need to load the collection, just count the items in the association
  private function size($hasManyName)
  {
    if ($this->{$hasManyName} === self::NOT_LOADED_ASSOC)
    {
      // NOTE: this will load all the hasmany items, if the amount is big, this will cause problems
      $this->get($hasManyName); // does the lazy load
    }
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

    // TODO: this will mark the phi as dirty even if a value wasn't removed, need to check for the remove to mark as dirty

    if (empty($this->{$sarrayAttr})) return; // nothing to do

    // array_diff generates non sequential indexes
    // doing json_encode over non sequential indexes generates a JSON object not an array
    // array_values, reindexes from 0
    $this->{$sarrayAttr} = array_values(array_diff($this->{$sarrayAttr}, [$value]));
  }

  private function hasValue($sarrayAttr, $value)
  {
    if (!is_string($value)) throw new \Exception('Value should be a string');
    return in_array($value, $this->{$sarrayAttr});
  }

  // Creates the hasmany instance with the right type of collection, used for lazy
  // loading and class initialization on create
  public function initialize_has_many($attr)
  {
    // hasmany declaration
    $rel = $this->phclass->get_has_many($attr); // checks this is has many

    //echo 'initialize_has_many ======================'.PHP_EOL;
    //print_r($rel);

    // checks for custom equality function for sets
    if ($rel->collectionType == PhSet::class && method_exists($this->phclass, $attr.'_equality'))
    {
      // get reference to callable function like:
      // $v = Array($this,"checkDemo");
      // $v("hello");
      $equality_function = [$this->phclass, $attr.'_equality']; // this is a reference to the method!
      $this->{$attr} = new $rel->collectionType($equality_function);
    }
    else
    {
      $this->{$attr} = new $rel->collectionType();
    }

    return $this->{$attr};
  }

  public function get($attr)
  {
    // is has one, and is null but the FK is not null, lazy load!
    if ($this->phclass->is_has_one($attr) &&
        $this->{$attr} === self::NOT_LOADED_ASSOC &&
        $this->{$attr.'_id'} != NULL)
    {
      $has_one_class = $this->phclass->{$attr}; //same as $this->phclass->get_has_one($attr)->class;
      $parts = explode('\\', $has_one_class);
      $class = $parts[count($parts)-1];
      $this->{$attr} = $GLOBALS[$class]->get($this->{$attr.'_id'});
    }
    else if ($this->phclass->is_one_to_many($attr) &&
             $this->{$attr} === self::NOT_LOADED_ASSOC)
    {
      //$hm_class = $this->phclass->get_has_many($attr)->class;
      //$parts = explode('\\', $has_one_class);
      //$class = $parts[count($parts)-1];

      // the collection is null, this initializes it
      $this->initialize_has_many($attr);

      // lazy loads the has many collection, if the instance has id (is saved)
      if ($this->id != NULL)
      {
        $hm_class = $this->phclass->get_has_many($attr)->class;
        $instances = $this->phclass->list_has_many($this, $attr, $hm_class);
        $this->{$attr}->add_all($instances);
      }
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
      if ($value === '') $value = NULL;
      else
      {
        // TODO: check for PHP_INT_MAX since the value can be truncated
        if ($this->phclass->is_int($attr))
          $value = $value + 0; // converts the value to a number
        else
          $value = $value + 0.0;
      }
    }

    $this->{$attr} = $value;
  }

  // options for now is to tell the hasmany to avoid updating the items when the collection is cleaned
  public function setProperties($props = [], $options = [])
  {
    // loops over the declared fields and get the values from props.
    // any other values not declared are ignored from props.

    $fields = $this->getDefinition()->get_all_fields(); // doesn't include attr_id for hasones

    // fields doesnt have the FK fields, need to check for those
    // to set the property from props when FKs come.

    foreach ($fields as $attr => $type)
    {
      // Default value, need to detect if null is set explicitly
      // This is the value when no value was passed in the props, so should keep the current value!
      $value = self::VALUE_NOT_PROVIDED;
      if (array_key_exists($attr, $props)) $value = $props[$attr]; // NULL allowed!

      if ($this->phclass->is_serialized_array($attr))
      {
        if (empty($value)) // handles null and empty string
        {
          if ($this->phclass->is_nullable($attr))
          {
            $value = NULL;
          }
          else
          {
            $value = [];
          }
        }
        else if ($value == self::VALUE_NOT_PROVIDED) continue; // if no value provided, keep current value


        if (is_null($value))
        {
          // do nothing, accept NULL value as valid
        }
        // the value comes as a string, then decode
        else if (is_string($value) && $value !== '')
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
          array_walk($value, function(&$value, $key) {
            $value = (string)$value;
          });
        }
        else
        {
          ob_start();
          var_dump($value);
          $value_dump = ob_get_contents();
          ob_end_clean();
          throw new \Exception('Serialized array field '. $attr .' can only be initialized with an array, non array passed: '. $value_dump);
        }
      }
      else if ($this->phclass->is_serialized_object($attr))
      {
        // echo 'initialize '. $attr . PHP_EOL;
        // var_dump($value);

        // nullable values from DB cant be decoded, null will be set in the instance
        if (is_null($value)) $value = NULL;
        else if ($value == self::VALUE_NOT_PROVIDED) continue; // if no value provided, keep current value

        // the value comes as a string, then decode
        if (is_string($value) && $value !== '')
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
        else if (is_array($value) || is_null($value))
        {
          // do nothing: array and NULL values are all valid for serialized objects
        }
        else
        {
          throw new \Exception('Serialized object field '. $attr .' can only be initialized with a JSON string or an array');
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
        $hasone_attr_id_value = $props[$attr.'_id'];
        $this->{'set_'. $attr. '_id'}($hasone_attr_id_value);

        if ($hasone_attr_id_value != NULL)
        {
          $this->{'set_'. $attr}(self::NOT_LOADED_ASSOC); // marking the hasone as not loaded
        }
      }
      else if ($this->phclass->is_has_many($attr) && is_array($value))
      {
        // NOTE: the options[attr] could be a single string or an array of labels
        // $attr_options will always be an array
        $attr_options = $options[$attr] ?? []; // ['addresses' => 'DO_NOT_UPDATE']
        if (is_string($attr_options)) $attr_options = [$attr_options];


        // Do not touch this has many collection on setProperties, even if values are given
        if (in_array('DO_NOT_UPDATE', $attr_options)) continue;

        // =====
        // This code does:
        // 1. load the has many collection of it's NOT_LOADED
        // 2. removes all items from it
        // 3. deletes or nullifies the backlink from each item to the container item
        // 4. saves the items with the nullified backlink

        // DELETE_ORPHANS will try to delete from the DB the items in the has many,
        // but only if they are not referenced by other items. For the non-orphan items,
        // only the backlink to the parent will be nullified.
        // $filtered = array_filter($attr_options, function($item) {
        //   return str_starts_with($item, 'DELETE');
        // });

        // if (count($filtered) == 0)
        // {
        //   $attr_options[] = 'DELETE_ORPHANS';
        // }

        // delete orphans by default if the keep orphans option is not set
        if (!in_array('KEEP_ORPHANS', $attr_options))
        {
          $attr_options[] = 'DELETE_ORPHANS';
        }

        // Remove and delete items
        $cleanMethod = 'clean_'. $attr;
        $this->{$cleanMethod}($attr_options);
        // =====


        // NOTE: because of the code above, all previous items could be left orphans,
        // one option would be to have an option to delete existing items, another would
        // be to leave the deletion of existing items to the user, so they can get the
        // existing items before the setProperties, then after the setProperties, delete
        // each item on the list.

        $addToHMMethod = 'add_to_'. $attr;

        // FIXME: note this expect $value to be a list of PhInstance not a list of arrays, if arrays of values are given, the add_to will fail!
        $hm = $value;
        foreach ($hm as $phi)
        {
          // in case an array with values is passed instead of a phi
          if (is_array($phi))
          {
            // creates an instance of the class declared in the HO attr with the value array
            $parts = explode('\\', $type[1]); // NOTE: for has many the type is an array, item 0 is the actual class of the items
            $class = $parts[count($parts)-1];
            $real_phi = $GLOBALS[$class]->create();
            $real_phi->setProperties($phi); // could be recursive
          }
          else
          {
            $real_phi = $phi;
          }

          $this->{$addToHMMethod}($real_phi); // verifies phi instanceof PhInstance
        }

        // the set is done by the add_to_xxx
        // without the continue, the collection was overwritten as an array!
        continue;
      }

      // sets the value and verifies it's validity (type, etc)
      if ($value !== self::VALUE_NOT_PROVIDED)
      {
        $setMethod = 'set_'.$attr;
        $this->$setMethod($value);
      }
    }
  }

  public function is_assoc_loaded($attr)
  {
    if (!$this->phclass->is_has_one($attr) && !$this->phclass->is_has_many($attr))
    {
      throw new \Exception("Object of type ". $this->getClass() ." doesn't have a relationship named '$attr'");
    }

    return $this->{$attr} !== self::NOT_LOADED_ASSOC;
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

      // transients (attributes that are not saved) don't make this dirty
      if (!in_array($attr, $this->phclass::$transients))
      {
        $this->is_dirty = true;
      }

      // this might set is_dirty to false, that is why we set dirty=true before
      // this line, this order is important!
      $this->set($attr, $args[0]);

      return;
    }

    // add_to_XYX
    if (substr($method,0,7) == "add_to_")
    {
      //print_r($this->phclass->getHasManyDeclarations());
      $attr = lcfirst(substr($method, 7)); // xyx
      if (!property_exists($this, $attr))
      {
        throw new \Exception("Object of type ". $this->getClass() ." doesn't have a declaration for a hasMany named '$attr'");
      }

      // is_dirty only if item was added because
      if ($this->addTo($attr, $args[0]))
      {
        $this->is_dirty = true;
      }
      return;
    }

    // remove_from_XXX
    if (substr($method,0,12) == "remove_from_")
    {
      $attr = lcfirst(substr($method, 12));

      // check for remove_from_xxx_and_delete
      $delete = false;
      if (BasicString::endsWith($attr, '_and_delete'))
      {
        $attr = substr($attr, 0, -11); // removes _and_delete
        $delete = true;
      }

      if (!property_exists($this, $attr))
      {
        throw new \Exception("Object of type ". $this->getClass() ." doesn't have a declaration for a hasMany named '$attr'");
      }

      $removed = $this->removeFrom($attr, $args[0]);

      // is_dirty if the object was removed
      if ($removed)
      {
        $this->is_dirty = true;

        if ($delete) $args[0]->delete(); // deletes the instance
      }
      return $removed; // true if removed, false if not
    }

    // clean_xxx
    if (substr($method,0,6) == "clean_")
    {
      $attr = lcfirst(substr($method, 6));
      if (!property_exists($this, $attr))
      {
        throw new \Exception("Object of type ". $this->getClass() ." doesn't have a declaration for a hasMany named '$attr'");
      }

      // only is_dirty if the clean was done, it's not done if the collection is empty
      if ($this->cleanFrom($attr, $args[0] ?? [])) // if clean_xxx is called directly, it might not have $args[0]
      {
        $this->is_dirty = true;
      }
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

  // returns the full class name with namespaces
  public function getClass()
  {
    return get_class($this->phclass);
  }

  // returns the class name without the namespaces
  public function getSimpleClass()
  {
    $class = get_class($this->phclass);
    $parts = explode('\\', $class);
    return $parts[count($parts)-1];
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

    $definition = [];
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
          $parentAttrs = [];

       $declaredAttrs = array_diff($thisAttrs, $parentAttrs);

       foreach ($declaredAttrs as $attr=>$type)
       {
          $def[$attr] = $type;

          if (is_subclass_of($type, 'CaboLabs\Phersistence\phersistent\Phersistent'))
          {
             echo "$attr is has one\n";
          }
          if (is_array($type))
          {
             if (is_subclass_of($type[0], 'CaboLabs\Phersistence\phersistent\Phersistent'))
             {
                echo "$attr is has one with relname $type[1]\n";
             }
          }
       }

       if ($c != null)
       {
          $def['__parent'] = [];
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

    return $res; // returns the id of the saved object, > 0
  }

  public function delete($logical = false)
  {
    $this->phclass->delete($this, $logical);
  }

  /**
   * return the current values for all has one attributes, used on save.
   */
  public function getAllHasOne()
  {
    $hasone = [];
    foreach ($this->phclass->getHasOneDeclarations() as $attr=>$rel)
    {
      // TEST
      // TEST: temporal
      // TEST
      if ($this->$attr !== self::NOT_LOADED_ASSOC) // do not force the loading if the assoc was not loaded
      {
        $hasone[$attr] = $this->get($attr); // this forces loading from the db
      }
    }
    return $hasone;
  }

  /**
   * return the current values for all has many attributes, used on save.
   */
  public function getAllHasMany()
  {
    $hasmany = [];
    foreach ($this->phclass->getHasManyDeclarations() as $attr=>$rel)
    {
      // FIXME: this loads from DB and the validate uses it, so everytime
      // the validation is called, if the collections were not loaded, this
      // loads them, adding an overhead on all validates and saves, it should
      // not validate if the collections are not loaded.

      // TEST
      // TEST: temporal
      // TEST
      if ($this->$attr !== self::NOT_LOADED_ASSOC) // do not force the loading if the assoc was not loaded
      {
        $hasmany[$attr] = $this->get($attr); // value is a collection, this forces loading from the db
      }
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
  public function validate($cascade = true, $set = true)
  {
    //echo 'INSTANCE validate '. $this->getClass() . PHP_EOL;
    $errors = [];

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
        // and dont validate if the instance is not dirty
        if ($hoi == null) continue;
        else if (!$hoi->get_is_dirty()) continue;

        $ho_errors = $hoi->validate();
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
          // and avoid validation if the instance is not dirty
          if ($hmi == null) continue;
          else if (!$hmi->get_is_dirty()) continue;

          $hm_errors = $hmi->validate();
          if ($hm_errors !== true)
          {
            if (!isset($errors[$attr])) $errors[$attr] = [];
             // the index is important to know which instance violated the constriants
            $errors[$attr][$i] = $hm_errors;
          }
        }
      }
    }

    if (count($errors) == 0) return true;

    // assigns the errors to the correspondent object when validating in cascade
    if ($set) $this->errors = new ObjectValidationErrors($errors);

    return new ObjectValidationErrors($errors);
  }
}

?>

<?php

namespace phersistent;

class PhInstance {

  const NOT_LOADED_ASSOC = -1;

  private function addTo($hasManyName, PhInstance $ins)
  {
    $this->{$hasManyName}->add( $ins );
  }

  private function removeFrom($hasManyName, PhInstance $ins)
  {
    // to remove an instance, it should be saved since the id is used to find a match
    if ($ins->getId() == null)
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
    if ($this->phclass->functionExists($method))
    {
      return $this->phclass->functionCall($method, $this, $args);
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

    // removeFromXXX
    if ( substr($method,0,10) == "removeFrom" )
    {
      $attr = lcfirst( substr($method, 10) );
      if (!property_exists($this, $attr))
      {
        throw new \Exception("Object of type ". $this->getClass() ." doesn't have a declaration for a hasMany named '$attr'");
      }
      $this->removeFrom($attr, $args[0]);
      return;
    }

    // sizeXXX
    if ( substr($method,0,4) == "size" )
    {
      $attr = lcfirst( substr($method, 4) );
      if (!property_exists($this, $attr))
      {
        throw new \Exception("Object of type ". $this->getClass() ." doesn't have a declaration for a hasMany named '$attr'");
      }
      return $this->size($attr);
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
    // TODO: use the class and field to check if there is a declaration of
    // a has many on the class to the toclass.
    $this->{$backlinkName} = $id;
  }
}

?>

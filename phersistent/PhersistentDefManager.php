<?php

namespace phersistent;

class PhersistentDefManager {

  // Database driver wrapper e.g. PhersistentMySQL
  private $__ph_db;

  // Always contains base class
  private $classDefinitions; // = array('Phersistent'=>new Phersistent());

  public function __construct($modelns, $phersistent_db)
  {
    global $_BASE;

    $this->__ph_db = $phersistent_db;

    // this checks the model folder and triggers the autoload by creating dummy
    // instances of those classes

    $dir = dir($_BASE.$modelns);
    while (false !== ($entry = $dir->read()))
    {
      if ($entry == '.' || $entry == '..') continue;

      //echo (basename($entry, '.php')) . PHP_EOL;
      //echo '\\'. $modelns .'\\'. basename($entry, '.php') . PHP_EOL;

      // triggers auto load of class
      eval('new \\'. $modelns .'\\'. basename($entry, '.php') .'();');
    }
    $dir->close();

    $this->classDefinitions = array('Phersistent'=>new Phersistent());

    // all declared phersistent classes before creating the manager
    foreach (get_declared_classes() as $aClassName)
    {
      //if ($this->classDefinitions['Phersistent']->isValidDef($aClassName))
      if (is_subclass_of($aClassName, '\phersistent\Phersistent'))
      {
        $this->add($aClassName);
      }
    }

    $this->declareBackLinks();
  }

  // Declare has many back links for one_to_many on the many side.
  //
  // The issue is when processing an instance on the many side of a
  // one to many association, the phi_to_data of the instance
  // doesn't know it is part of a one to many assoc, so the resulting
  // table doesn't have the backlink needed to save the has many.
  // Here we are checking the ony to many's to add metadata to the
  // many side so when  doing phi_to_data, it knows that a backlink
  // should be added.
  //
  // This should be executed after all the model is loaded.
  private function declareBackLinks()
  {
    foreach ($this->classDefinitions as $class => $defins)
    {
      $hmd = $defins->getHasManyDeclarations();
      foreach ($hmd as $attr => $hmdec)
      {
        //echo $attr . PHP_EOL;
        //print_r($hmdec);
        /*
        stdClass Object
        (
        [class] => E
        [collectionType] => collection
        [relName] =>
        )
        */

        if ($defins->is_one_to_many($attr))
        {
          $backlink_name = $this->__ph_db->backlink_name_def($defins, $attr);

          // declare one to many on the many side
          // backlink FK is INT
          // if D has_many E, E.backlink_to_d = INT is declared
          $this->classDefinitions[$hmdec->class]->{$backlink_name} = Phersistent::INT;
        }
        else
        {
          // Many to many might need the backlinks to be declared in the join table class
          //echo get_class($defins) .'.'. $attr. ' many to many '. PHP_EOL;
        }
      }
    }
  }

  public function add($def)
  {
    $defins = new $def();
    if (!$defins instanceof Phersistent)
    {
      throw new Exception($def ." is not a valid Phersistent definition");
    }

    $defins->set_manager($this);

    // TODO: avoid adding the same def twice
    $this->classDefinitions[$def] = $defins;

    // $def = \model\Class
    // global should only be Class

    // declares the definitions as globals so can be used to create instances without using the manager
    $parts = explode('\\', $def);
    $GLOBALS[end($parts)] = $defins;
  }

  public function getDefinitions()
  {
    return $this->classDefinitions;
  }

  public function getDefinition($def)
  {
    return $this->classDefinitions[$def];
  }

  public function create($def, $attrs = array())
  {
    // TODO: check $def exists
    return $this->classDefinitions[$def]->create($attrs);
  }

  public function getInstance($class_name, $id)
  {
    return $this->__ph_db->get_instance($class_name, $id);
  }

  public function count($class_name)
  {
    return $this->__ph_db->count($class_name);
  }

  /**
   * saves or updates
   */
  public function saveInstance($phi)
  {
    return $this->__ph_db->save_instance($phi);
  }

  public function listInstances($class_name, $max, $offset)
  {
    return $this->__ph_db->list_instances($class_name, $max, $offset);
  }

  public function listHasManyInstances($owner, $hm_attr, $hm_class)
  {
    $backlink_name = $this->__ph_db->backlink_name($owner, $hm_attr);

    return $this->__ph_db->list_hasmany_instances($owner->getId(), $hm_class, $backlink_name);
  }

  public function findBy($class_name, $where, $max, $offset)
  {
    return $this->__ph_db->find_by($class_name, $where, $max, $offset);
  }

  public function deleteInstance($phi)
  {
    $this->__ph_db->delete_instance($phi);
  }
}

?>

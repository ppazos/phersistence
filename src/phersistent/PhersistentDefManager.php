<?php

namespace CaboLabs\Phersistence\phersistent;

use CaboLabs\PhBasic\BasicString;

class PhersistentDefManager {

  // Database driver wrapper e.g. PhersistentMySQL
  private $__ph_db;

  // Always contains base class
  private $classDefinitions; // = array('Phersistent'=>new Phersistent());

  public function __construct($model_path, $phersistent_db)
  {
    global $_BASE;

    $this->__ph_db = $phersistent_db;

    // this checks the model folder and triggers the autoload by creating dummy
    // instances of those classes

    if ($model_path !== NULl && $model_path !== '')
    {
      //$path = str_replace('\\', '/', $modelns); // changes namespace for a linux path
      $dir = dir($_BASE.$model_path);

      if ($dir === FALSE)
      {
        echo "Can't open ". $_BASE.$model_path .", please check the folder exists\n";
        exit();
      }

      while (false !== ($entry = $dir->read()))
      {
        if ($entry == '.' || $entry == '..') continue;

        // triggers auto load of class
        //eval('new \\'. $modelns .'\\'. basename($entry, '.php') .'();');

        if (!BasicString::endsWith($model_path, DIRECTORY_SEPARATOR))
        {
          $model_path .= DIRECTORY_SEPARATOR;
        }

        require_once($model_path.$entry);
      }
      $dir->close();
    }
    

    $this->classDefinitions = array('Phersistent'=>new Phersistent());

    // all declared phersistent classes before creating the manager
    foreach (get_declared_classes() as $aClassName)
    {
      //if ($this->classDefinitions['Phersistent']->isValidDef($aClassName))
      if (is_subclass_of($aClassName, 'CaboLabs\Phersistence\phersistent\Phersistent'))
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

          // if the hmdec class has subclasses, also those should have the backlink injected
          // https://github.com/ppazos/phersistence/issues/58
          $subclasses = $this->classDefinitions[$hmdec->class]->getSubclasses();
          foreach ($subclasses as $subclass)
          {
            $this->classDefinitions[$subclass]->{$backlink_name} = Phersistent::INT;
          }
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
    if (!($defins instanceof Phersistent))
    {
      throw new \Exception($def ." is not a valid Phersistent definition");
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

  public function listInstances($class_name, $max, $offset, $sort, $order)
  {
    return $this->__ph_db->list_instances($class_name, $max, $offset, $sort, $order);
  }

  public function listHasManyInstances($owner, $hm_attr, $hm_class)
  {
    $backlink_name = $owner->getDefinition()->backlink_name($owner, $hm_attr);

    return $this->__ph_db->list_hasmany_instances($owner->get_id(), $hm_class, $backlink_name);
  }

  public function findBy($class_name, $where, $max, $offset, $sort, $order)
  {
    return $this->__ph_db->find_by($class_name, $where, $max, $offset, $sort, $order);
  }

  public function countBy($class_name, $where)
  {
    return $this->__ph_db->count_by($class_name, $where);
  }

  public function deleteInstance($phi, $logical)
  {
    $this->__ph_db->delete_instance($phi, $logical);
  }

  public function runRaw($sql)
  {
    return $this->__ph_db->runRaw($sql);
  }

  public function get_db()
  {
    return $this->__ph_db;
  }

  public function findByTest($class_name, $where, $max, $offset, $sort, $order)
  {
    return $this->__ph_db->find_by_test($class_name, $where, $max, $offset, $sort, $order);
  }

  public function countByTest($class_name, $where)
  {
    return $this->__ph_db->count_byTest($class_name, $where);
  }
}

?>

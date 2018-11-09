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

  public function saveInstance($phi)
  {
    return $this->__ph_db->save_instance($phi);
  }

  public function listInstances($class_name, $max, $offset)
  {
    return $this->__ph_db->list_instances($class_name, $max, $offset);
  }
}

?>

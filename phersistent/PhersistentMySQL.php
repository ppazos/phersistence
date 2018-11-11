<?php

namespace phersistent;

class PhersistentMySQL {

  private $driver;

  public function __construct($dbhost, $dbuser, $dbpass, $dbname)
  {
    $d = new \drivers\MySQL();
    $d->connect($dbhost, $dbuser, $dbpass);
    $d->select_db($dbname);
    $this->driver = $d;
  }

  public function save_instance($phi)
  {
    // TODO: transactional since it does cascade save of has one.
    // TODO: will try to do the loop detection on phi_to_data.
    //$loop = time()."_". rand()."_". rand();
    //$table = $this->phi_to_data($phi);
    //print_r($table);
    $id = $this->save_instance_recursive($phi);

    return $id;
  }

  private function save_instance_recursive($phi)
  {
    // can be an empty array derived from a null has one
    // this wont save anything so wont return an id
    //if (count($table) == 0) return null;

    // TODO: check if id is null, if not should except since this is save not update

    $hones = $phi->getAllHasOne();
    foreach ($hones as $attr=>$value)
    {
      if ($value == null)
      {
        $phi->set($attr.'_id', null); // FK should be emptied
      }
      else
      {
        if ($value->getId() == null) // insert
        {
          $idho = $this->save_instance_recursive($value);
          $value->setId($idho);                   // id set on associated instance
          $phi->set($attr .'_id', $idho);
          //$table['columns'][$attr .'_id'] = $idho; // FK set on owner
        }
        else
        {
          // TBD: update if dirty, avoid if not dirty
        }
      }
    }

    $table = $this->phi_to_data($phi);

    /*
    // save foreigns first to get their fk ids and set them to the table before save
    if (count($table['foreigns']) > 0)
    {
      // $col is equals to the name of the field declared in the phi
      foreach ($table['foreigns'] as $col => $ft)
      {
        // if the has one already exists, do not insert
        if (!isset($table['columns'][$col .'_id']))
        {
          $id = $this->save_instance_recursive($ft);
          $ft->setId($id);
          $table['columns'][$col .'_id'] = $id; // FK set
        }
      }
    }
    */

    // insert with column values
    $r = $this->driver->execute($this->table_to_insert($table));
    if($r === 1)
    {
      $id = $this->driver->last_insert_id();

      $hmanies = $phi->getAllHasMany();
      foreach ($hmanies as $attr=>$collection)
      {
        foreach ($collection as $item)
        {
          $backlink = $this->backlink_name($phi->getClass(), $attr);
          $item->setBacklinkId($phi->getClass(), $attr, $item->getClass(), $backlink, $id);
          $hmid = $this->save_instance_recursive($item);
          $item->setId($hmid);
        }
      }

      /*
      // now that I have the id, save the hasmany in one to many relationships,
      // injecting the id in the backlink.
      if (count($table['many_back']) > 0)
      {
        foreach ($table['many_back'] as $backlink_name => $manyhmtable)
        {
          foreach ($manyhmtable as $i=>$hmtable)
          {
            $hmtable['columns'][$backlink_name] = $id;
            $hmid = $this->save_instance_recursive($hmtable);

            //$ft->setId($hmid);
          }
        }
      }
      */

      // TODO: save many to many in join table, might need to detect the owner side

      $phi->setId($id);

      return $id;
    }

    return null;
  }

  /**
   * Retrieves the phersistent instance from the database.
   * @param string $class_name class name with namespace
   * @param int @id id of the instance in the database
   */
  public function get_instance($class_name, $id)
  {
    $parts = explode('\\', $class_name);
    $class = $parts[count($parts)-1];
    $phi = $GLOBALS[$class]->create();

    $table_name = $this->get_table_name($phi);

    try
    {
      $table = $this->get_row($table_name, $id);

      $phi->setProperties($table['columns']);

      $phi->setId($table['columns']['id']);
      $phi->setClass($table['columns']['class']);
      $phi->setDeleted($table['columns']['deleted']);
    }
    catch (\Exception $e)
    {
      return null; // row doesnt exists, null phi is returned
    }

    return $phi;
  }

  public function count($class_name)
  {
    $parts = explode('\\', $class_name);
    $class = $parts[count($parts)-1];
    $phi = $GLOBALS[$class]->create();

    $table_name = $this->get_table_name($phi);

    $r = $this->driver->query('SELECT COUNT(id) as count FROM '. $table_name);
    $row = $r->fetch_assoc();
    $r->close();

    return $row['count'];
  }

  public function list_instances($class_name, $max, $offset)
  {
    $parts = explode('\\', $class_name);
    $class = $parts[count($parts)-1];
    $phi = $GLOBALS[$class]->create();

    $table_name = $this->get_table_name($phi);

    $records = array();
    $r = $this->driver->query('SELECT * FROM '. $table_name .' LIMIT '. $offset .', '. $max);
    while ($row = $r->fetch_assoc())
    {
      // FIXME: table is really row or record
      $table = array('table_name' => $table_name, 'columns' => array(), 'foreigns' => array());
      $table['columns'] = $row;
      $records[] = $table;
    }
    $r->close();

    $instances = array();
    foreach($records as $table)
    {
      $phi = $GLOBALS[$class]->create();
      $phi->setProperties($table['columns']);

      $phi->setId($table['columns']['id']);
      $phi->setClass($table['columns']['class']);
      $phi->setDeleted($table['columns']['deleted']);

      $instances[] = $phi;
    }
    return $instances;
  }

  /**
   * Returns a table structure if the id exists on the table.
   */
  public function get_row($table_name, $id)
  {
    // FIXME: table is really a recor or row
    // Does lazy loading, so foreigns are not loaded.
    $table = array('table_name' => $table_name, 'columns' => array(), 'foreigns' => array());

    $r = $this->driver->query('SELECT * FROM '. $table_name .' WHERE id='. $id);

    if(mysqli_num_rows($r) == 1)
    {
      $row = $r->fetch_assoc();
      $table['columns'] = $row;
      $r->close();
    }
    else
    {
      throw new \Exception('Record with id '. $id .' on table '. $table_name .' does not exist');
    }

    //print_r($table);

    return $table;
  }

  /**
   * Takes a table derived from phi and returns an insert query with the column
   * values to insert on one single table.
   */
  private function table_to_insert($table)
  {
    $columns_string = '';
    $values_string = '';

    foreach ($table['columns'] as $col => $val)
    {
      // id will be set from the auto increment on the database
      if ($col == 'id') continue;

      // if class has namepsace, needs to be escaped for mysql
      if ($col == 'class') $val = str_replace('\\', '\\\\', $val);

      // Check if FK column is null, do not include on the column list
      if (\basic\BasicString::endsWith($col, '_id') && $val == null)
      {
        continue;
      }

      $columns_string .= $col .', ';

      // FIXME: need to check the type of value and add or not quotes depending
      // on the type, e.g. numeric wont have quotes

      if (is_bool($val))
      {
        $values_string .= ($val ? 'true' : 'false');
      }
      else if (!is_string($val) && is_numeric($val)) // numbers wont come as strings, but is_numeric returns true for numeric strings also
      {
        $values_string .= $val;
      }
      else
      {
        $values_string .= '"'. $val .'"';
      }

      $values_string .= ', ';
    }

    $columns_string = substr($columns_string, 0, -2);
    $values_string = substr($values_string, 0, -2);

    $q = 'INSERT INTO '. $table['table_name'] .'('. $columns_string .') VALUES ('. $values_string .')';

    return $q;
  }


  /**
   * ORM
   * For now inheritance ORM is all STI.
   * FIXME: should detect recursion loops, need to add a model with a loop to test.
   */
  public function phi_to_data($phi)
  {
    // TODO: the returned item should be an array of tables
    // will contain the amy table, the associated via has one
    // and the join tables referencing the main and the assoc table
    // saving order will be always main + associated, saving associated first
    // to copy keys to owners, then join tables, always checking for loops.
    $table = array();

    if ($phi == null) return $table;

    $table['table_name'] = $this->get_table_name($phi);
    $table['columns'] = array(); // simple column values
    $table['foreigns'] = array(); // associated objects referenced by FKs
    $table['many_back'] = array();
    $table['many_join'] = array();

    $fields = $phi->getDefinition()->get_all_fields();
    foreach ($fields as $field => $type)
    {
      if ($phi->getDefinition()->is_has_many($field)) // has many
      {
        // one to many uses back links from the many side
        if ($phi->getDefinition()->is_one_to_many($field))
        {
          $backlink_name = $this->backlink_name($phi->getClass(), $field);

          $table['many_back'][$backlink_name] = array();

          foreach ($phi->get($field) as $i=>$hmphi)
          {
            $table['many_back'][$backlink_name][] = $this->phi_to_data($hmphi);

            // inject backlink name on columns
            // will be empty until we save the current phi and get an id
            $table['many_back'][$backlink_name][$i]['columns'][$backlink_name] = null;
          }

          // TODO: to set the backlink value, this instance should be saved first
        }
        else // many to many uses join table
        {
          // TBD
        }
      }
      else if ($phi->getDefinition()->is_has_one($field)) // has one
      {
        // FK field
        $has_one_field = $field . '_id';

        // if has one is not saved, the id will be null
        // internally PhInstance will set the xxx_id field
        $table['columns'][$has_one_field] = $phi->get($has_one_field);

        // creates related table with the has_one value
        // the associated element can be null
        $table['foreigns'][$field] = $this->phi_to_data($phi->get($field));
      }
      else // simple field
      {
        $table['columns'][$field] = $phi->get($field);
      }
    }

    // columns injected on instances
    $table['columns']['id'] = $phi->getId();
    $table['columns']['deleted'] = $phi->getDeleted();
    $table['columns']['class'] = $phi->getClass();

    return $table;
  }


  /**
   * ORM
   * Map table data to phersistent instance
   */
  public function data_to_phi($table)
  {
    //echo $table['columns']['class'] . PHP_EOL;

    $parts = explode('\\', $table['columns']['class']);
    $class = $parts[count($parts)-1];

    //print_r($GLOBALS[$class]);
    //print_r($table['columns']);

    $phi = $GLOBALS[$class]->create($table['columns']);

    //global ${$class};
    //$phi = ${$class}->create();
    /*
    foreach ($table['columns'] as $col => $value)
    {

    }
    */

    return $phi;
  }

  public function get_table_name(PhInstance $phi)
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

    return $this->class_to_table_name($class_name);
  }

  private function class_to_table_name($class_name)
  {
    // removes class namespace
    $parts = explode('\\', $class_name);

    return strtr($parts[count($parts)-1],
                 "ABCDEFGHIJKLMNOPQRSTUVWXYZ ",
                 "abcdefghijklmnopqrstuvwxyz_");
  }

  /**
   * name of the column for the backlink FK for one to many relationships.
   */
  private function backlink_name($class, $field)
  {
    // if CURRENT_CLASS(hasmany(assoc,OTHER_CLASS))
    // then $backlink_name = current_class_assoc_id
    // and that column should exist on the OTHER_CLASS table
    return strtolower($class .'_'. $field .'_back');
  }

  /**
   * Maps each phersistent data type to a MySQL data type.
   */
  function get_db_type($phersistent_type)
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

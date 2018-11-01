<?php

namespace phersistent;

class PhersistentMySQL {

  private $driver;

  public function __construct($driver)
  {
    $this->driver = $driver;
  }

  public function save_instance($phi)
  {
    // TODO: transactional since it does cascade save of has one.
    // TODO: will try to do the loop detection on phi_to_data.
    //$loop = time()."_". rand()."_". rand();
    $table = $this->phi_to_data($phi);
    return $this->save_instance_recursive($table);
  }

  private function save_instance_recursive($table)
  {
    // can be an empty array derived from a null has one
    // this wont save anything so wont return an id
    if (count($table) == 0) return null;

    // TODO: check if id is null, if not should except since this is save not update

    // save foreigns first to get their fk ids and set them to the table before save
    if (count($table['foreigns']) > 0)
    {
      // $col is equals to the name of the field declared in the phi
      foreach ($table['foreigns'] as $col => $ft)
      {
        $id = $this->save_instance_recursive($ft);
        $table['columns'][$col .'_id'] = $id; // FK set
      }
    }

    // insert with column values
    $r = $this->driver->execute($this->table_to_insert($table));
    if($r === 1)
    {
      return $this->driver->last_insert_id();
    }

    return null;
  }

  /**
   * Retrieves the phersistent instance from the database.
   * @param string $class_name class name with namespace
   * @param int @id id of the instance in the database
   */
  public function get($class_name, $id)
  {
    $parts = explode('\\', $class_name);
    $class = $parts[count($parts)-1];
    $phi = $GLOBALS[$class]->create();

    $table_name = $this->get_table_name($phi);
    $table = $this->get_row($table_name, $id);

    $phi->setProperties($table['columns']);

    return $phi;
  }

  public function list($class_name, $max, $offset)
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
      $phi = $GLOBALS[$class]->create($table['columns']);
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

    $fields = $phi->getDefinition()->get_all_fields();
    foreach ($fields as $field => $type)
    {
      if ($phi->getDefinition()->is_has_many($field)) // has many
      {
        // TBD: should create the join table
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

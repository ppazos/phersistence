<?php

namespace drivers;

class MySQL {

  private $connection = NULL;
  private $using_dbname;
  private $last_query;
  //private $last_result;
  private $query_count;
  private $transaction_on = false;


  // https://docs.oracle.com/cd/E19078-01/mysql/mysql-refman-5.0/error-handling.html
  // https://dev.mysql.com/doc/refman/5.5/en/server-error-reference.html

  // insert duplicated value for unique key
  const ERR_DUP_KEY = 1062;

  // unknown column
  const ERR_BAD_FIELD = 1054;

  // unknown table
  const ERR_BAD_TABLE = 1051;

  // incorrect query syntax
  const ERR_PARSE = 1065;
  

  function __construct()
  {
    // exception on construct breaks autoloader
    //if (!function_exists('mysqli_connect')) throw new Exception("MySQLi not loaded");

    $this->query_count = 0;
  }

  /** ******************************
   * Connection
   */

  function connect($dbhost, $dbuser, $dbpass, $dbname = null)
  {
    if (!function_exists('mysqli_connect')) throw new \Exception("MySQLi not loaded");

    $this->connection = mysqli_connect($dbhost, $dbuser, $dbpass);

    \logger\Logger::log("MySQL::connect ". $this->connection->host_info);

    if (!$this->connection)
    {
      throw new \Exception("Can't connect to MySQL: " . mysqli_connect_error() );
    }

    if (!is_null($dbname))
    {
      $this->selectDB($dbname);
    }
  }

  function select_db ($dbname)
  {
    if (!$this->connection)
    {
      throw new \Exception("No connection to MySQL");
    }

    if (!mysqli_select_db($this->connection, $dbname)) // Por si estoy trabajando con muchas conecciones
    {
      throw new \Exception("Error selecting db '$dbname', please check it exists.", 666);
    }

    $this->using_dbname = $dbname;
  }

  function close()
  {
     \logger\Logger::log("MySQL::close ". $this->connection->host_info);
     if ($this->connection !== NULL)
     {
        mysqli_close($this->connection);
        $this->connection = NULL;
     }
  }

  /** ******************************
   * Query
   */

  /**
   * Executes am insert update or delete (queries that do not return any data).
   */
  function execute($q)
  {
    \logger\Logger::log("MySQL::execute ". $q);

    if (!$this->connection)
    {
      throw new \Exception("No connection to MySQL");
    }

    $this->last_query = $q;

    if (!$r = mysqli_query($this->connection, $q))
      throw new \Exception('Query error: ' . mysqli_error($this->connection), mysqli_errno($this->connection));

    $this->query_count++;
    return mysqli_affected_rows($this->connection);
  }

  /**
   * Returns the last insert id on the current connection.
   */
  function last_insert_id()
  {
    return mysqli_insert_id($this->connection);
  }

  /**
   * Executes a query that returns a result
   */
  function query($q)
  {
    \logger\Logger::log("MySQL::execute ". $q);

    if (!$this->connection)
    {
      throw new \Exception("No connection to MySQL");
    }

    $this->last_query = $q;

    if (!$result = mysqli_query($this->connection, $q))
      throw new \Exception('Query error: ' . mysqli_error($this->connection), mysqli_errno($this->connection));

    $this->query_count++;

    // if we save the last result and close the result, then print this, will give a warning
    // if we need the result stored, we might need to cpy it via fetch
    //$this->last_result = $result;

    return $result;
  }


  /** ******************************
   * Inspection
   */

  function table_exists($table_name)
  {
    $f = $this->query('SHOW TABLES FROM '. $this->using_dbname .' WHERE tables_in_'. $this->using_dbname .'="'. $table_name .'"');
    return $f->num_rows > 0;
  }

  function get_tables()
  {
    $tables = array();
    $ts = $this->query('SHOW TABLES');
    while ($row = $ts->fetch_assoc())
    {
      $tables[] = $row['Tables_in_'.$this->using_dbname];
    }
    $ts->close();
    return $tables;
  }

  function get_create_tables()
  {
    $create_tables = array();
    $tables = $this->get_tables();
    foreach ($tables as $table)
    {
      $res = $this->query('SHOW CREATE TABLE '. $table);
      $create_tables[] = $res->fetch_assoc();
    }

    return $create_tables;
  }

  function column_exists($table_name, $column_name)
  {
    /*
    Array
    (
        [Field] => id
        [Type] => int(11)
        [Null] => NO
        [Key] => PRI
        [Default] =>
        [Extra] => auto_increment
    )
    */
    $found = false;
    $t = $this->query('DESCRIBE '.$table_name);
    while ($row = $t->fetch_assoc())
    {
      if ($row['Field'] == $column_name)
      {
        $found = true;
        break;
      }
    }
    $t->close();
    return $found;
  }

  function index_exists($table_name, $index_name)
  {
    $found = false;
    $idx = $this->get_indexes($table_name);
    while ($row = $idx->fetch_assoc())
    {
      if ($row['Key_name'] == $index_name)
      {
        $found = true;
        break;
      }
    }
    $idx->close();
    return $found;
  }

  function get_indexes($table_name)
  {
    $i = $this->query('SHOW INDEX FROM '. $table_name);
    /*
    row = Array
    (
        [Table] => employer
        [Non_unique] => 1
        [Key_name] => fk_payor
        [Seq_in_index] => 1
        [Column_name] => payor_id
        [Collation] => A
        [Cardinality] => 0
        [Sub_part] =>
        [Packed] =>
        [Null] => YES
        [Index_type] => BTREE
        [Comment] =>
        [Index_comment] =>
    )
    */
    return $i;
  }


  /** ******************************
   * Schema manipulation
   */

  /**
   * Creates a table with id column that is PK. MySQL doesn't support the creation of a table without columns.
   */
  function create_table($table_name)
  {
    $this->execute('CREATE TABLE '.$table_name .' (id INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id))');
  }

  /**
   * Adds a column to a table.
   *
   * @param string $table_name name of the table that will be modified
   * @param string $column_name name of the column to be added
   * @param string $data_type type of the column, should be a valid MySQL data type
   * @param boolean $nullable true if the column accepts null values, false otherwise
   * @param string $default if not null, will set the default value for the column
   */
  function add_column($table_name, $column_name, $data_type, $nullable, $default = null)
  {
    // ALTER TABLE table ADD COLUMN column_name column_definition;
    // column definition: data_type NULL|NOT NULL [AUTO_INCREMENT] DEFAULT def

    // Auto increment is not used because MySQL only allows that on PKs and the PK is added automatically on create_table.
    /*
    // auto increment only allowed on integer values
    $int_types = array('INTEGER', 'INT', 'BIGINT', 'MEDIUMINT', 'SMALLINT', 'TINYINT');
    if ($auto_increment && !in_array(strtoupper($data_type), $int_types))
    {
      $auto_increment = false;
    }
    */

    $default_string = '';
    if (!is_null($default)) $default_string = 'DEFAULT '. $default;

    $this->execute('ALTER TABLE '.$table_name .' ADD COLUMN '. $column_name .' '. $data_type .' '. ($nullable ? 'NULL' : 'NOT NULL') .' '. $default_string);
  }

  /**
   * Sets a column as primary key of the table. Only allows one column PKs.
   */
  /* Removed because PK is added automatically on create_table
  function set_pk($table_name, $column_name)
  {
    // ALTER TABLE t1 ADD PRIMARY KEY(id);
    $this->execute('ALTER TABLE '. $table_name .' ADD PRIMARY KEY('. $column_name .')');
  }
  */

  /**
   * Adds a unique constraint over a set of columns of a table.
   *
   * @param string $table_name
   * @param array $column_names
   * @param string $constraint_name
   */
  function add_unique($table_name, $column_names, $constraint_name)
  {
    // ALTER TABLE t ADD CONSTRAINT constraint_name UNIQUE(column_name_1,column_name_2)
    $columns_string = '';
    foreach ($column_names as $col)
    {
      $columns_string .= $col .', ';
    }
    $columns_string = substr($columns_string, 0, -2);

    $this->execute('ALTER TABLE '. $table_name .' ADD CONSTRAINT '. $constraint_name .' UNIQUE KEY ('. $columns_string .')');
  }

  /**
   * Adds an index over a set of columns of a table.
   *
   * @param string $table_name
   * @param array $column_names
   * @param string $index_name
   */
  function add_index($table_name, $column_names, $index_name)
  {
    $columns_string = '';
    foreach ($column_names as $col)
    {
      $columns_string .= $col .', ';
    }
    $columns_string = substr($columns_string, 0, -2);

    $this->execute('CREATE INDEX '. $index_name .' ON '. $table_name .'('. $columns_string .')');
  }

  function add_fk($table_name, $column_name, $fk_name, $ref_table_name, $ref_column_name)
  {
    $this->execute('ALTER TABLE '. $table_name .' ADD CONSTRAINT '. $fk_name .' FOREIGN KEY ('. $column_name .') REFERENCES '. $ref_table_name .'('. $ref_column_name .')');
  }

  function remove_column()
  {
    // TDB
  }

   /** ******************************
    * Worksing with transactions
    */
  function transaction_start($is_read_only = false)
  {
    if ($is_read_only)
      mysqli_begin_transaction($this->connection, MYSQLI_TRANS_START_READ_ONLY);
    else
      mysqli_begin_transaction($this->connection, MYSQLI_TRANS_START_READ_WRITE);

    $this->transaction_on = true;
  }

  function transaction_commit()
  {
    if (!$this->transaction_on)
    {
      throw new \Exception('No active transaction to commit');
    }
    mysqli_commit($this->connection);
    $this->transaction_on = false;
  }

  function transaction_rollback()
  {
    if (!$this->transaction_on)
    {
      throw new \Exception('No active transaction to rollback');
    }
    mysqli_rollback($this->connection);
    $this->transaction_on = false;
  }
}
?>

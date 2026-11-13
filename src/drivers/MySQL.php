<?php

namespace CaboLabs\Phersistence\drivers;

use \CaboLabs\PhLogger\PhLogger;

class MySQL implements Driver {

  private $connection = NULL;
  private $using_dbname;
  private $last_query;
  //private $last_result;
  private $query_count;
  private $transaction_on = false;
  private $credentials = null;


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

    $this->credentials = [$dbhost, $dbuser, $dbpass];
    $this->connection = mysqli_connect($dbhost, $dbuser, $dbpass);

    PhLogger::log("MySQL::connect ". $this->connection->host_info);

    if (!$this->connection)
    {
      throw new \Exception("Can't connect to MySQL: " . mysqli_connect_error() );
    }

    if (!is_null($dbname))
    {
      $this->select_db($dbname);
    }
  }

  function select_db($dbname)
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
    PhLogger::log("MySQL::close ". $this->connection->host_info);
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
    PhLogger::log("MySQL::execute ". $q);

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
    PhLogger::log("MySQL::query ". $q);

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

  function executeRaw(string $sql, array $params = [])
  {
    PhLogger::log("MySQL::executeRaw ". $sql);

    if (!$this->connection)
    {
      throw new \Exception("No connection to MySQL");
    }

    if (empty($params))
    {
      $result = $this->query($sql);

      // statements that don't return a result set (INSERT, UPDATE, DELETE, ...) return the affected rows
      if ($result === true)
      {
        return mysqli_affected_rows($this->connection);
      }

      $rows = [];
      while ($row = $result->fetch_assoc())
      {
        $rows[] = $row;
      }
      $result->close();
      return $rows;
    }

    $stmt = $this->connection->prepare($sql);
    if (!$stmt)
    {
      throw new \Exception("Prepare failed: " . $this->connection->error);
    }

    $types = '';
    foreach ($params as $param)
    {
      if (is_int($param))       $types .= 'i';
      elseif (is_float($param)) $types .= 'd';
      else                      $types .= 's';
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    $result = $stmt->get_result();
    $this->query_count++;

    if ($result === false)
    {
      $affected = $stmt->affected_rows;
      $stmt->close();
      return $affected;
    }

    $rows = [];
    while ($row = $result->fetch_assoc())
    {
      $rows[] = $row;
    }
    $result->close();
    $stmt->close();
    return $rows;
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
    $tables = [];
    $ts = $this->query('SHOW TABLES');
    while ($row = $ts->fetch_assoc())
    {
      $tables[] = $row['Tables_in_'.$this->using_dbname];
    }
    $ts->close();
    return $tables;
  }

  function truncate_all_tables()
  {
    $tables = $this->get_tables();
    $this->execute("SET FOREIGN_KEY_CHECKS=0;");
    foreach ($tables as $table)
    {
      $this->execute("TRUNCATE TABLE $table");
    }
    $this->execute("SET FOREIGN_KEY_CHECKS=1;");
  }

  function get_create_tables()
  {
    $create_tables = [];
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
    // in mysql >= 5.6 innodb supports fulltext indexes
    // in lower versions that is supported only by MyISAM
    // in 5.5 the default table type is inoodb and that breaks the fulltext index creation
    if ($this->server_version() >= 50600)
      $this->execute('CREATE TABLE '.$table_name .' (id INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id))');
    else
      $this->execute('CREATE TABLE '.$table_name .' (id INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)) ENGINE=MyISAM');
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

    $this->require_table($table_name);
    if ($this->column_exists($table_name, $column_name))
    {
      throw new \Exception("Column '$column_name' already exists in table '$table_name'");
    }

    $this->execute('ALTER TABLE '.$table_name .' ADD COLUMN '. $column_name .' '. $this->column_definition($data_type, $nullable, $default));
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

  function add_fulltext_index($table_name, $column_names, $index_name)
  {
    $columns_string = '';
    foreach ($column_names as $col)
    {
      $columns_string .= $col .', ';
    }
    $columns_string = substr($columns_string, 0, -2);

    $this->execute('CREATE FULLTEXT INDEX '. $index_name .' ON '. $table_name .'('. $columns_string .')');
  }

  function add_fk($table_name, $column_name, $fk_name, $ref_table_name, $ref_column_name)
  {
    $this->execute('ALTER TABLE '. $table_name .' ADD CONSTRAINT '. $fk_name .' FOREIGN KEY ('. $column_name .') REFERENCES '. $ref_table_name .'('. $ref_column_name .')');
  }

  function fk_exists($table_name, $fk_name)
  {
    $rows = $this->executeRaw(
      "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
      [$this->using_dbname, $table_name, $fk_name]
    );
    return count($rows) > 0;
  }

  function drop_table($table_name)
  {
    $this->require_table($table_name);
    $this->execute('DROP TABLE '. $table_name);
  }

  function rename_table($table_name, $new_table_name)
  {
    $this->require_table($table_name);
    if ($this->table_exists($new_table_name))
    {
      throw new \Exception("Table '$new_table_name' already exists");
    }
    $this->execute('RENAME TABLE '. $table_name .' TO '. $new_table_name);
  }

  /**
   * Changes type, nullability and default of an existing column. The column definition is replaced as a whole.
   */
  function modify_column($table_name, $column_name, $data_type, $nullable, $default = null)
  {
    $this->require_column($table_name, $column_name);
    $this->execute('ALTER TABLE '. $table_name .' MODIFY COLUMN '. $column_name .' '. $this->column_definition($data_type, $nullable, $default));
  }

  function rename_column($table_name, $column_name, $new_column_name)
  {
    $this->require_column($table_name, $column_name);
    if ($this->column_exists($table_name, $new_column_name))
    {
      throw new \Exception("Column '$new_column_name' already exists in table '$table_name'");
    }
    $this->execute('ALTER TABLE '. $table_name .' RENAME COLUMN '. $column_name .' TO '. $new_column_name);
  }

  function drop_column($table_name, $column_name)
  {
    $this->require_column($table_name, $column_name);
    $this->execute('ALTER TABLE '. $table_name .' DROP COLUMN '. $column_name);
  }

  /**
   * Drops an index or a unique constraint (in MySQL both are indexes).
   */
  function drop_index($table_name, $index_name)
  {
    $this->require_table($table_name);
    if (!$this->index_exists($table_name, $index_name))
    {
      throw new \Exception("Index '$index_name' doesn't exist in table '$table_name'");
    }
    $this->execute('DROP INDEX '. $index_name .' ON '. $table_name);
  }

  function drop_fk($table_name, $fk_name)
  {
    $this->require_table($table_name);
    if (!$this->fk_exists($table_name, $fk_name))
    {
      throw new \Exception("Foreign key '$fk_name' doesn't exist in table '$table_name'");
    }
    $this->execute('ALTER TABLE '. $table_name .' DROP FOREIGN KEY '. $fk_name);
  }

  /** *******************************
   * Locking
   */
  function acquire_lock($name, $timeout_seconds = 0)
  {
    $rows = $this->executeRaw('SELECT GET_LOCK(?, ?) AS l', [$name, $timeout_seconds]);
    return (int)$rows[0]['l'] === 1;
  }

  function release_lock($name)
  {
    $rows = $this->executeRaw('SELECT RELEASE_LOCK(?) AS l', [$name]);
    return (int)$rows[0]['l'] === 1;
  }

  /** *******************************
   * Schema helpers
   */

  private function require_table($table_name)
  {
    if (!$this->table_exists($table_name))
    {
      throw new \Exception("Table '$table_name' doesn't exist");
    }
  }

  private function require_column($table_name, $column_name)
  {
    $this->require_table($table_name);
    if (!$this->column_exists($table_name, $column_name))
    {
      throw new \Exception("Column '$column_name' doesn't exist in table '$table_name'");
    }
  }

  private function column_definition($data_type, $nullable, $default)
  {
    $def = $this->map_type($data_type) .' '. ($nullable ? 'NULL' : 'NOT NULL');
    if (!is_null($default)) $def .= ' DEFAULT '. $default;
    return $def;
  }

  /**
   * Maps a portable type (see Driver) to a MySQL type. Unknown types are passed through unchanged (legacy).
   */
  function map_type($type)
  {
    if (preg_match('/^string\((\d+)\)$/i', $type, $m)) return 'VARCHAR('. $m[1] .')';

    switch (strtolower($type))
    {
      case 'int':      return 'INT';
      case 'long':     return 'BIGINT';
      case 'float':    return 'FLOAT';
      case 'double':   return 'DOUBLE';
      case 'decimal':  return 'DECIMAL';
      case 'boolean':  return 'BOOLEAN';
      case 'date':     return 'DATE';
      case 'time':     return 'TIME';
      case 'datetime': return 'DATETIME';
      case 'text':     return 'TEXT';
    }
    return $type;
  }

  /** ******************************
   * Data manipulation
   */

  /**
   * Inserts a row ($row is a column => value map). Returns the new id.
   */
  function insert($table_name, array $row)
  {
    if (empty($row)) throw new \Exception('insert needs at least one column');
    $this->require_table($table_name);

    $cols = array_map([$this, 'ident'], array_keys($row));
    $this->executeRaw(
      'INSERT INTO '. $this->ident($table_name) .' ('. implode(', ', $cols) .') VALUES ('. implode(', ', array_fill(0, count($row), '?')) .')',
      $this->bindable(array_values($row))
    );
    return $this->last_insert_id();
  }

  /**
   * Returns the rows matching $where (all rows if empty), optionally sorted by a column name.
   */
  function select($table_name, array $where = [], $order_by = null)
  {
    $this->require_table($table_name);
    list($cond, $params) = $this->where_clause($where);

    $sql = 'SELECT * FROM '. $this->ident($table_name) . $cond;
    if (!is_null($order_by)) $sql .= ' ORDER BY '. $this->ident($order_by);

    return $this->executeRaw($sql, $params);
  }

  /**
   * Updates the rows matching $where. $where can't be empty, to update every row use an explicit condition.
   * Returns the number of affected rows.
   */
  function update($table_name, array $values, array $where)
  {
    if (empty($values)) throw new \Exception('update needs at least one column');
    if (empty($where))  throw new \Exception('update needs a condition');
    $this->require_table($table_name);

    $sets = [];
    foreach (array_keys($values) as $col) $sets[] = $this->ident($col) .' = ?';

    list($cond, $params) = $this->where_clause($where);
    return $this->executeRaw(
      'UPDATE '. $this->ident($table_name) .' SET '. implode(', ', $sets) . $cond,
      array_merge($this->bindable(array_values($values)), $params)
    );
  }

  /**
   * Deletes the rows matching $where. $where can't be empty, use delete_all() to delete every row.
   * Returns the number of affected rows.
   */
  function delete($table_name, array $where)
  {
    if (empty($where)) throw new \Exception('delete needs a condition, use delete_all to delete every row');
    $this->require_table($table_name);

    list($cond, $params) = $this->where_clause($where);
    return $this->executeRaw('DELETE FROM '. $this->ident($table_name) . $cond, $params);
  }

  function delete_all($table_name)
  {
    $this->require_table($table_name);
    return $this->execute('DELETE FROM '. $this->ident($table_name));
  }

  private function ident($name)
  {
    if (!is_string($name) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name))
    {
      throw new \Exception("Invalid identifier '$name'");
    }
    return $name;
  }

  private function bindable(array $values)
  {
    return array_map(function($v) { return is_bool($v) ? (int)$v : $v; }, $values);
  }

  private function where_clause(array $where)
  {
    if (empty($where)) return ['', []];

    $parts = [];
    $params = [];
    foreach ($where as $col => $val)
    {
      if (is_null($val))
      {
        $parts[] = $this->ident($col) .' IS NULL';
      }
      else
      {
        $parts[] = $this->ident($col) .' = ?';
        $params[] = $val;
      }
    }
    return [' WHERE '. implode(' AND ', $parts), $this->bindable($params)];
  }

  /** ******************************
   * Columns and rows
   */

  function describe_table($table_name)
  {
    $this->require_table($table_name);
    $cols = [];
    $r = $this->query('DESCRIBE '. $this->ident($table_name));
    while ($row = $r->fetch_assoc())
    {
      $cols[] = ['name' => $row['Field'], 'type' => $row['Type'], 'nullable' => $row['Null'] == 'YES', 'default' => $row['Default']];
    }
    $r->close();
    return $cols;
  }

  function count_rows($table_name)
  {
    $this->require_table($table_name);
    $r = $this->executeRaw('SELECT COUNT(*) AS c FROM '. $this->ident($table_name), []);
    return (int)$r[0]['c'];
  }

  /** ******************************
   * Databases
   */

  function current_database()
  {
    return $this->using_dbname;
  }

  function connection_for($database_name)
  {
    if (is_null($this->credentials)) throw new \Exception('No connection to MySQL');

    $d = new MySQL();
    $d->connect($this->credentials[0], $this->credentials[1], $this->credentials[2], $database_name);
    return $d;
  }

  function list_databases()
  {
    $rows = $this->executeRaw('SELECT SCHEMA_NAME AS n FROM information_schema.SCHEMATA ORDER BY SCHEMA_NAME', []);
    return array_column($rows, 'n');
  }

  /**
   * Creates $target_name and copies every table of the current database: structure, indexes, foreign keys and rows.
   * Views, triggers, routines and events are not copied (see unsupported_objects()).
   * If anything fails the partial copy is dropped.
   */
  function clone_database($target_name)
  {
    $this->ident($target_name);
    if ($target_name == $this->using_dbname) throw new \Exception('The target database is the current database');
    if (in_array($target_name, $this->list_databases())) throw new \Exception("Database '$target_name' already exists");

    $source = $this->using_dbname;
    $this->execute('CREATE DATABASE '. $target_name);

    try
    {
      $t = $this->connection_for($target_name);
      $t->execute('SET FOREIGN_KEY_CHECKS=0');

      foreach ($this->get_tables() as $table)
      {
        // base tables only, views are reported as unsupported
        $res = $this->query('SHOW CREATE TABLE '. $this->ident($table));
        $row = $res->fetch_assoc();
        $res->close();
        if (!isset($row['Create Table'])) continue;

        $t->execute($row['Create Table']);
        $t->execute('INSERT INTO '. $table .' SELECT * FROM '. $source .'.'. $table);
      }

      $t->execute('SET FOREIGN_KEY_CHECKS=1');
      $t->close();
    }
    catch (\Throwable $e)
    {
      $this->execute('DROP DATABASE '. $target_name);
      throw $e;
    }
  }

  function drop_database($database_name)
  {
    $this->ident($database_name);
    if ($database_name == $this->using_dbname) throw new \Exception('Refusing to drop the current database');
    $this->execute('DROP DATABASE '. $database_name);
  }

  function unsupported_objects()
  {
    $db = $this->using_dbname;
    $objects = [];

    $queries = [
      'view'    => 'SELECT TABLE_NAME AS n FROM information_schema.VIEWS WHERE TABLE_SCHEMA = ?',
      'trigger' => 'SELECT TRIGGER_NAME AS n FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = ?',
      'routine' => 'SELECT ROUTINE_NAME AS n FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = ?',
      'event'   => 'SELECT EVENT_NAME AS n FROM information_schema.EVENTS WHERE EVENT_SCHEMA = ?'
    ];
    foreach ($queries as $type => $sql)
    {
      foreach ($this->executeRaw($sql, [$db]) as $row) $objects[] = $type .' '. $row['n'];
    }
    return $objects;
  }

  /** ******************************
   * Working with transactions
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

  /** *******************************
   * Utils
   */
  function server_version()
  {
    if (!$this->connection)
    {
      throw new \Exception("Not connect to MySQL");
    }

    return mysqli_get_server_version($this->connection);
  }
}
?>

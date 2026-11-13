<?php

namespace CaboLabs\Phersistence\drivers;

/**
 * Database driver contract. Migrations and the ORM depend on this interface, not on a specific database.
 *
 * Portable column types accepted by add_column() and modify_column():
 *   int, long, float, double, decimal, boolean, date, time, datetime, text, string(N)
 * These match the Phersistent data type names (string(N) is a bounded text, e.g. string(255)).
 * Drivers may pass through any other type string unchanged for backward compatibility,
 * but migrations must only use the portable types.
 *
 * Schema operations fail loudly: creating something that exists, or dropping/altering
 * something that doesn't, throws an exception.
 */
interface Driver {

  /* Connection */
  function connect($dbhost, $dbuser, $dbpass, $dbname = null);
  function select_db($dbname);
  function close();

  /* Query */
  function execute($q);
  function query($q);
  function executeRaw(string $sql, array $params = []);
  function last_insert_id();

  /* Inspection */
  function table_exists($table_name);
  function get_tables();
  function column_exists($table_name, $column_name);
  function index_exists($table_name, $index_name);
  function fk_exists($table_name, $fk_name);

  /* Schema manipulation */
  function create_table($table_name);
  function drop_table($table_name);
  function rename_table($table_name, $new_table_name);
  function add_column($table_name, $column_name, $data_type, $nullable, $default = null);
  function modify_column($table_name, $column_name, $data_type, $nullable, $default = null);
  function rename_column($table_name, $column_name, $new_column_name);
  function drop_column($table_name, $column_name);
  function add_unique($table_name, $column_names, $constraint_name);
  function add_index($table_name, $column_names, $index_name);
  function add_fulltext_index($table_name, $column_names, $index_name);
  function drop_index($table_name, $index_name);
  function add_fk($table_name, $column_name, $fk_name, $ref_table_name, $ref_column_name);
  function drop_fk($table_name, $fk_name);

  /* Data manipulation (prepared statements). $where is a column => value map joined with AND; null means IS NULL. */
  function insert($table_name, array $row);
  function select($table_name, array $where = [], $order_by = null);
  function update($table_name, array $values, array $where);
  function delete($table_name, array $where);
  function delete_all($table_name);

  /* Inspection of columns and rows */
  function describe_table($table_name); // [['name' => , 'type' => , 'nullable' => bool, 'default' => ], ...]
  function count_rows($table_name);

  /* Databases: used to simulate migrations on a disposable copy */
  function current_database();
  function connection_for($database_name); // new driver instance connected to another database with the same credentials
  function list_databases();
  function clone_database($target_name);   // creates the target and copies the tables (structure, constraints and data) of the current database
  function drop_database($database_name);  // refuses to drop the current database
  function unsupported_objects();          // objects clone_database does not copy (views, triggers, routines, events), as 'type name' strings

  /* Transactions */
  function transaction_start($is_read_only = false);
  function transaction_commit();
  function transaction_rollback();

  /* Locking: used to prevent concurrent migration runs. Returns true if the lock was acquired. */
  function acquire_lock($name, $timeout_seconds = 0);
  function release_lock($name);
}

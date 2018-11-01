<?php

spl_autoload_register(function ($class) {
  global $_BASE;
  echo 'spl_autoload_register '. $class . PHP_EOL;
  echo $_BASE.str_replace('\\', '/', $class).'.php' . PHP_EOL;

  if (file_exists($_BASE.str_replace('\\', '/', $class).'.php'))
    require_once($_BASE.str_replace('\\', '/', $class).'.php');
});

\logger\Logger::log(memory_get_usage());

$d = new \drivers\MySQL();
\logger\Logger::log('Start CLI');

$d->connect('localhost', 'user', 'user1234');
$d->select_db('amplify');


// Inspection
assert($d->table_exists('payor'), 'Payor doesnt exists');
assert($d->index_exists('payor', 'PRIMARY'), 'Primary key doesnt exists on payor');
assert($d->index_exists('employer', 'fk_payor'), 'FK fk_payor doesnt exists on employer');

/*
$idx = $d->get_indexes('payor');
while ($row = $idx->fetch_assoc())
{
  print_r($row);
}
$idx->close();

$idx = $d->get_indexes('employer');
while ($row = $idx->fetch_assoc())
{
  print_r($row);
}
$idx->close();

$ts = $d->get_tables();
foreach ($ts as $t) echo $t . PHP_EOL;

assert($d->column_exists('payor', 'ein'), 'Table payor does not contain column ein');
*/

$r = $d->execute('INSERT INTO payor(company, ein) VALUES ("Insurance A", "01-1234567")');
if($r === 1)
{
  echo $d->last_insert_id() .PHP_EOL;
}


$r = $d->query('SELECT * FROM payor');

//print_r($r);

if($r)
{
  // TODO: make a wrapper on query results to call the same API always
  while ($row = $r->fetch_object()) // returns stdClass instances, fetch_assoc() will return arrays
  {
    print_r($row);
  }
  // free results
  $r->close();
}


// DB manipulation
/*
$d->create_table('test'); // adds the id column
$d->add_column('test', 'age', 'int', true);
$d->add_column('test', 'name', 'varchar(255)', false);
//$d->set_pk('test', 'id');
//$d->set_pk('test', 'name');
$d->add_index('test', array('age', 'name'), 'an');
$d->add_fk('test', 'age', 'fk_payor', 'payor', 'id');

$idx = $d->get_indexes('test');
while ($row = $idx->fetch_assoc())
{
  print_r($row);
}
$idx->close();
*/

$cts = $d->get_create_tables();
foreach ($cts as $ct)
{
  print_r($ct);
  /*
  Array
  (
    [Table] => test
    [Create Table] => CREATE TABLE `test` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `age` int(11) DEFAULT NULL,
      `name` varchar(255) NOT NULL,
      PRIMARY KEY (`id`),
      KEY `an` (`age`,`name`),
      CONSTRAINT `fk_payor` FOREIGN KEY (`age`) REFERENCES `payor` (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1
  )
  */
}

$d->close();

\logger\Logger::log(memory_get_usage());

?>

<?php

spl_autoload_register(function ($class) {
  global $_BASE;
  echo 'spl_autoload_register '. $class . PHP_EOL;
  echo $_BASE.str_replace('\\', '/', $class).'.php' . PHP_EOL;

  if (file_exists($_BASE.str_replace('\\', '/', $class).'.php'))
    require_once($_BASE.str_replace('\\', '/', $class).'.php');
});

\logger\Logger::log(memory_get_usage());

$d = new drivers\MySQL();
\logger\Logger::log('Start CLI');

$d->connect('localhost', 'root', 'toor');
$d->select_db('amplify');


// Inspection
assert($d->table_exists('payor'), 'Payor doesnt exists');
assert($d->index_exists('payor', 'PRIMARY'), 'Primary key doesnt exists on payor');
assert($d->index_exists('employer', 'fk_payor'), 'FK fk_payor doesnt exists on employer');

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


//$d->execute('INSERT INTO payor(company, ein) VALUES ("Insurance A", "01-1234567")');
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


$d->close();

\logger\Logger::log(memory_get_usage());

?>

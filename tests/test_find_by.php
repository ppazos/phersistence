<?php

spl_autoload_register(function ($class) {
  global $_BASE;
  //echo 'spl_autoload_register '. $class . PHP_EOL;
  //echo $_BASE.str_replace('\\', '/', $class).'.php' . PHP_EOL;

  if (file_exists($_BASE.str_replace('\\', '/', $class).'.php'))
    require_once($_BASE.str_replace('\\', '/', $class).'.php');
});


$ph_db = new \phersistent\PhersistentMySQL('localhost', 'user', 'user123!', 'phersistent');
$man = new \phersistent\PhersistentDefManager('model', $ph_db);

global $Person;


//$cond = array('code', 'LIKE', "%$q%");

// AND 2
$where = array(
  "AND" => array(
    array('firstname', 'LIKE', '%p%'),
    array('lastname',  'LIKE', '%p%')
  )
);

$res = $Person->findBy($where, 20, 0);

// OR 2
$where = array(
  "OR" => array(
    array('firstname', 'LIKE', '%p%'),
    array('lastname',  'LIKE', '%p%')
  )
);

$res = $Person->findBy($where, 20, 0);

// OR 3
$where = array(
  "OR" => array(
    array('firstname', 'LIKE', '%p%'),
    array('lastname',  'LIKE', '%p%'),
    array('phone_number', '=', '555-123-1234')
  )
);

$res = $Person->findBy($where, 20, 0);

// SIMPLE
$where = array(
  array('firstname', 'LIKE', '%p%')
);

$res = $Person->findBy($where, 20, 0);

// AND OR
$where = array(
  "AND" => array(
    "OR" => array(
      array('firstname', 'LIKE', '%p%'),
      array('lastname',  'LIKE', '%p%')
    ),
    array('phone_number', '=', '555-123-1234')
  )
);

$res = $Person->findBy($where, 20, 0);

// NOT AND
$where = array(
  "NOT" => array(
    "AND" => array(
      array('firstname', 'LIKE', '%p%'),
      array('lastname',  'LIKE', '%p%')
    )
  )
);

$res = $Person->findBy($where, 20, 0);

// NOT SIMPLE
$where = array(
  "NOT" => array('phone_number', '=', '555-123-1234')
);

$res = $Person->findBy($where, 20, 0);


?>

<?php

namespace CaboLabs\Phersistence\tests\model;

class B extends \CaboLabs\Phersistence\phersistent\Phersistent {

  public $date_created = self::DATETIME;
  public $note = self::TEXT;

  public $table = 'b';

  function init()
  {
    return [
      'date_created' => date('Y-m-d H:i:s')
    ];
  }
}

?>

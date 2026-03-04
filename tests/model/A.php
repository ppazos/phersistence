<?php

namespace CaboLabs\Phersistence\tests\model;

use CaboLabs\Phersistence\phersistent\PhSet;

class A extends \CaboLabs\Phersistence\phersistent\Phersistent {

  public $date_created = self::DATETIME;
  public $is_closed = self::BOOLEAN;

  public $table = 'a';

  public $bs = [PhSet::class, B::class];

  function init()
  {
    return [
      'date_created' => date('Y-m-d H:i:s')
    ];
  }

  function bs_equality(\CaboLabs\Phersistence\phersistent\PhInstance $b1, \CaboLabs\Phersistence\phersistent\PhInstance $b2) {
    if ($b1->note === NULL || $b2->note === NULL) {
      throw new \Exception("Can't compare B with NULL note");
    }
    return $b1->get_note() === $b2->get_note();
  }
}

?>

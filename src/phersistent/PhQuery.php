<?php

namespace CaboLabs\Phersistence\phersistent;

class PhQuery {
  static function and($conds)
  {
    return new PhAndCond($conds);
  }

  static function or($conds)
  {
    return new PhOrCond($conds);
  }

  static function not($cond)
  {
    return new PhNotCond($cond);
  }
}
?>
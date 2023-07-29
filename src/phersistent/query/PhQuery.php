<?php

namespace CaboLabs\Phersistence\phersistent\query;

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

  static function eq($column, $value)
  {
    return [$column, '=', $value];
  }

  static function notEq($column, $value)
  {
    return [$column, '!=', $value];
  }

  static function isNull($column)
  {
    return [$column, 'IS NULL'];
  }

  static function isNotNull($column)
  {
    return [$column, 'IS NOT NULL'];
  }

  static function in($column, array $values = [])
  {
    return [$column, 'IN', $values];
  }

  static function lt($column, $value)
  {
    return [$column, '<', $value];
  }

  static function gt($column, $value)
  {
    return [$column, '>', $value];
  }

  static function le($column, $value)
  {
    return [$column, '<=', $value];
  }

  static function ge($column, $value)
  {
    return [$column, '>=', $value];
  }

  // $value should have the % already
  static function like($column, $value)
  {
    return [$column, 'LIKE', $value];
  }
}
?>
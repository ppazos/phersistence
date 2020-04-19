<?php

namespace phersistent;

class PhSet extends PhCollection {

  private $equality_function;

  public function __construct($equality_function = NULL)
  {
    if ($equality_function == NULL)
    {
      $this->equality_function = function($a, $b) {
        return $a->get_id() != NULL && $b->get_id() != NULL && $a->get_id() == $b->get_id();
      };
    }
    else
    {
      $this->equality_function = $equality_function;
    }
  }

  public function add($instance)
  {
    // can't call directly, need the variable
    $eq = $this->equality_function;
    foreach ($this->items as $i=>$ins)
    {
      if ($eq($ins, $instance))
      {
        return; // Dont add the instance
      }
    }
    parent::add($instance);
  }
}

?>

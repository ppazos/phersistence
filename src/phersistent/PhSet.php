<?php

namespace CaboLabs\Phersistence\phersistent;

class PhSet extends PhCollection {

  private $equality_function;

  public function __construct($equality_function = NULL)
  {
    if ($equality_function == NULL)
    {
      $this->equality_function = function(PhInstance $a, PhInstance $b) {
        if ($a->id == NULL)
        {
          throw new \Exception("Can't compare instance of ". $a->getClass() ." if id is NULL");
        }
        if ($b->id == NULL)
        {
          throw new \Exception("Can't compare instance of ". $b->getClass() ." if id is NULL");
        }

        // TODO: check a and b are in the same hierarchical structure (class or subclass)
        return $a->id == $b->id;
      };
    }
    else
    {
      $this->equality_function = $equality_function;
    }
  }

  public function add(PhInstance $instance)
  {
    // can't call directly, need the variable
    $eq = $this->equality_function;
    foreach ($this->items as $i=>$ins)
    {
      if ($eq($ins, $instance))
      {
        return false; // Dont add the instance
      }
    }
    return parent::add($instance);
  }

  public function add_all($instances = array())
  {
    foreach ($instances as $ins)
    {
      $this->add($ins); // uses equality functon
    }
  }

  // FIXME: check this, because remove from might be done with the equality function too
  // remove is implemented in PhCOllection since it has to be done using the id not
  // the equality_function, because the DB could be inconsistent using the
  // equality_function without checking the ids, for instance if a1 hasmany b1, b2
  // and a2 hasmany b3, b4, if b4 and b2 have the same value used in the equality_function,
  // then the a1->remove(b4) remove will return true but the item b4 wasn't really in a1,
  // so we need to ids, and if the id is not set, it throws an exception.
}

?>

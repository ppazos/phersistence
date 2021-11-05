<?php

namespace CaboLabs\Phersistence\phersistent;

class PhList extends PhCollection {

  public function put($idx, $instance)
  {
    $this->items[$idx] = $instance;
    return true;
  }
}

?>

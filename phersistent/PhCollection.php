<?php

namespace phersistent;

class PhCollection implements \Iterator, \ArrayAccess { // implements Traversable {

  private $position = 0;
  private $items = array();

  public function add($instance)
  {
    $this->items[] = $instance;
  }
  public function add_all($instances = array())
  {
    foreach ($instances as $ins)
    {
      $this->items[] = $ins;
    }
  }

  public function remove($instance)
  {
    foreach ($this->items as $i=>$ins)
    {
      if ($ins->getId() == $instance->getId())
      {
        array_splice($this->items, $i, 1);
      }
    }
  }

  public function all()
  {
    return $this->items;
  }

  public function size()
  {
    return count($this->items);
  }

  // iterator
  public function rewind()
  {
    $this->position = 0;
  }

  public function current()
  {
    return $this->items[$this->position];
  }

  public function key()
  {
    return $this->position;
  }

  public function next()
  {
    ++$this->position;
  }

  public function valid()
  {
    return isset($this->items[$this->position]);
  }

  // ArrayAccess
  public function offsetSet($offset, $value)
  {
    if (is_null($offset))
    {
      $this->items[] = $value;
    }
    else
    {
      $this->items[$offset] = $value;
    }
  }

  public function offsetExists($offset)
  {
    return isset($this->items[$offset]);
  }

  public function offsetUnset($offset)
  {
    unset($this->items[$offset]);
  }

  public function offsetGet($offset)
  {
    return isset($this->items[$offset]) ? $this->items[$offset] : null;
  }
}

class PhList extends PhCollection {

  public function put($idx, $instance)
  {
    $this->items[$idx] = $instance;
  }
}

class PhSet extends PhCollection {

  public function add($instance)
  {
    foreach ($this->items as $i=>$ins)
    {
      if ($ins->id == $instance->id) // id should be injected into PhInstances
      {
        return; // Dont add the instance
      }
    }
    parent::add($instance);
  }
}

?>

<?php

namespace CaboLabs\Phersistence\phersistent;

class PhCollection implements \Iterator, \ArrayAccess, \Countable { // implements Traversable {

  protected $position = 0;
  protected $items = array();

  public function add(PhInstance $instance)
  {
    $this->items[] = $instance;
    return true;
  }

  public function add_all($instances = array())
  {
    foreach ($instances as $ins)
    {
      $this->items[] = $ins;
    }
  }

  public function clean()
  {
    $this->items = array();
    $this->rewind();
  }

  public function remove(PhInstance $instance)
  {
    $removed = false;

    if ($instance->id == NULL)
    {
      throw new \Exception("Not saved instance of type ". $instance->getClass() ." can't be removed from hasmany");
    }

    foreach ($this->items as $i=>$ins)
    {
      if ($ins->get_id() == $instance->get_id())
      {
        array_splice($this->items, $i, 1);
        $removed = true;
      }
    }

    return $removed;
  }

  public function all()
  {
    return $this->items;
  }

  public function size()
  {
    return count($this->items);
  }

  // countable
  public function count(): int
  {
    return count($this->items);
  } 

  // iterator
  public function rewind(): void
  {
    $this->position = 0;
  }

  #[\ReturnTypeWillChange]
  public function current()
  {
    return $this->items[$this->position];
  }

  #[\ReturnTypeWillChange]
  public function key()
  {
    return $this->position;
  }

  public function next(): void
  {
    ++$this->position;
  }

  public function valid(): bool
  {
    return isset($this->items[$this->position]);
  }

  // ArrayAccess
  public function offsetSet($offset, $value): void
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

  public function offsetExists($offset): bool
  {
    return isset($this->items[$offset]);
  }

  public function offsetUnset($offset): void
  {
    unset($this->items[$offset]);
  }

  #[\ReturnTypeWillChange]
  public function offsetGet($offset)
  {
    return isset($this->items[$offset]) ? $this->items[$offset] : null;
  }
}

?>

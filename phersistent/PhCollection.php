<?php

namespace phersistent;

class PhCollection implements Iterator { // implements Traversable {

   private $position = 0;
   private $items = array();

   public function add($instance)
   {
      $this->items[] = $instance;
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

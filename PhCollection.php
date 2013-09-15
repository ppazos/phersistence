<?php

class PhCollection { // implements Traversable {

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
<?php

include_once "PhCollection.php";

class PhInstance {

   private function addTo($hasManyName, PhInstance $ins)
   {
      $this->{$hasManyName}->add( $ins );
   }
   
   private function get($attr)
   {
      return $this->{$attr};
   }
   
   public function __call($method, $args)
   {
      // addToXYX
      if ( substr($method,0,5) == "addTo" )
      {
         $attr = lcfirst( substr($method, 5) ); // xYZ
         $this->addTo($attr, $args[0]);
      }
      
      // getXYZ
      if ( substr($method,0,3) == "get" )
      {
         $attr = lcfirst( substr($method, 3) ); // xYZ
         return $this->get( $attr );
      }
   }

   
   public function isInstanceOf($phersistent)
   {
      //echo get_class($this->phclass).' '. $phersistent;
      return (is_a($this->phclass, $phersistent));
   }
   
   public function getClass()
   {
      return get_class($this->phclass);
   }
}

class Phersistent {

   private $__many = array();
   private $__one = array();

   /**
    * $name UML relationship target name
    * $class target class
    * $relName UML relationship name
    */
   protected function hasOne($name, $class, $relName = null)
   {
      $this->__one[$name] = new StdClass();
      $this->__one[$name]->class = $class; // A subclass of Phersistent
      $this->__one[$name]->relName = $relName;
   }
   
   /**
    * $name UML relationship target name
    * $class target class
    * $collectionType collection, list, set, orderedSet
    * $relName UML relationship name
    */
   protected function hasMany($name, $class, $collectionType = 'collection', $relName = null)
   {
      $this->__many[$name] = new StdClass();
      $this->__many[$name]->class = $class; // A subclass of Phersistent
      $this->__many[$name]->collectionType = $collectionType;
      $this->__many[$name]->relName = $relName;
   }

   /*
    * New instance of this class.
    */
   public function create($attrs = array())
   {
      //echo 'create '. $this->clax ."\n";
      
      $ins = new PhInstance();
      
      // Inject attributes declared on concrete subclass on new instance
      foreach ($this as $attr=>$type)
      {
         // dont inject internal hasMany and hasOne definitions
         if ($attr == '__one' || $attr == '__many') continue;
         
         //echo "create $attr = $type\n";
         
         // Set values
         // TODO: implementar Phinstance en lugar de usar SdtClass, y ponerle set y get que castee a los tipos declarados.
         $value = null; // Default value
         if (isset($attrs[$attr])) $value = $attrs[$attr];
         
         // Injects the attribute and sets the value
         $ins->{$attr} = $value; 
      }
      
      // Inject many
      foreach ($this->__many as $attr=>$rel)
      {
         print_r($rel);
         // TODO: podria usar $rel->class para restringir el contenido de las coleccions a esa clase
         if ($rel->collectionType == 'collection') $ins->{$attr} = new PhCollection();
         if ($rel->collectionType == 'list') $ins->{$attr} = new PhList();
         if ($rel->collectionType == 'set') $ins->{$attr} = new PhSet();
      }
      
      // Inject one
      
      
      
      $ins->phclass = $this;
      $ins->id = null;       // Default value
      $ins->deleted = false; // Default value
      
      return $ins;
   }

   public function get($id)
   {
   }
   
   public function listAll()
   {
   }
   
   /*
   public function __call($method, $args)
   {
      echo "call $method\n";
      if ( $this->{$method} instanceof Closure ) {
         return call_user_func_array($this->{$method},$args);
      } else {
         return parent::__call($method, $args);
      }
   }
   */
}

?>
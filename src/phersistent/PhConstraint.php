<?php

namespace CaboLabs\Phersistence\phersistent;

abstract class PhConstraint {

  public abstract function validate($class, $attr, $value, $object);

  // number constraints
  public static function min    ($min) { return new MinConstraint($min); }
  public static function max    ($max) { return new MaxConstraint($max); }
  public static function lower  ($max) { return new LowerConstraint($max); }
  public static function greater($min) { return new GreaterConstraint($min); }
  public static function between($min, $max) { return new Between($min, $max); }

  // string constraints
  public static function email    () { return new EmailConstraint(); }
  public static function matches  ($regex) { return new Matches($regex); }
  public static function date     () { return new DateConstraint(); }
  public static function datetime () { return new DateTimeConstraint(); }
  public static function maxLength($max) { return new MaxLengthConstraint($max); }
  public static function minLength($min) { return new MinLengthConstraint($min); }

  // general
  public static function nullable($nullable) { return new Nullable($nullable); }
  public static function blank   ($blank) { return new BlankConstraint($blank); }
  public static function inList  ($array) { return new InList($array); }
  public static function unique  () { return new Unique(); }
}

class FieldValidator {

  public static function validate($phi, $attr, $cs)
  {
    $errors = array();

    /* the default value for nullable was changed from false to true, so if not
       constraint is defined, we consider it is nullable, like it happens in the database.
    */
    $attr_is_nullable = $phi->phclass->is_nullable($attr);

    $value = $phi->get($attr);

    // if is nullable and value is null, there is no need of testing the rest of
    // the constraints
    if ($attr_is_nullable && $value == null) return true;


    // if attr is blankable and the value is blank, passes the validation
    // the issue is if the attr is also unique, then the empty string will
    // fail the unique constraint
    $attr_is_blankable = false;
    foreach ($cs as $c)
    {
      if ($c instanceof BlankConstraint && $c->getValue() === true)
      {
        //echo $attr .' is blankable'. PHP_EOL;
        $attr_is_blankable = true;
        break;
      }
    }

    if ($attr_is_blankable && $value == '') return true;


    foreach ($cs as $c)
    {
      if (($e = $c->validate($phi->getClass(), $attr, $value, $phi)) !== true)
      {
        $errors[] = $e;
      }
    }

    if (count($errors) == 0) return true;
    return $errors;
  }
}

class ObjectValidationErrors implements \Iterator, \ArrayAccess, \Countable {

  private $field_errors;
  private $position = 0;

  public function __construct($field_errors = array())
  {
    $this->field_errors = $field_errors;
  }

  public function getFieldErrors()
  {
    return $this->field_errors;
  }

  public function getAllErrorMessages()
  {
    $errs = array();
    foreach ($this->field_errors as $field => $validation_errors)
    {
      // if field is has one, validation errors is object validation errors
      if ($validation_errors instanceof ObjectValidationErrors)
      {
        $errs = array_merge($errs, $validation_errors->getAllErrorMessages());
      }
      else
      {
        foreach ($validation_errors as $validation_error)
        {
          // errors for has many there is a list of object validaiton errors
          if ($validation_error instanceof ObjectValidationErrors)
          {
            $errs = array_merge($errs, $validation_error->getAllErrorMessages());
          }
          else
          {
            $errs[] = $validation_error->getMessage();
          }
        }
      }
    }
    return $errs;
  }

  // returns the tree of errors but instead of ObjectValidationErrors returns just the name of the attribute
  // something like person => address => line1 => [ValidationError]
  public function getSimplifiedFieldErrors()
  {
    $errors = array();
    $this->collect_errors($this, $errors);
    return $errors;
  }

  private function collect_errors($validation_errors, &$collected)
  {
    foreach ($validation_errors->getFieldErrors() as $field => $error)
    {
      // list of single errors
      if (is_array($error))
      {
        $collected[$field] = array();

        foreach ($error as $error_i)
        {
          $collected[$field][] = $error_i;
        }
      }
      // is another validation_errors object
      else
      {
        $collected[$field] = array();

        // recursion
        $this->collect_errors($error, $collected[$field]);
      }
    }
  }

  // similar to getSimplifiedFieldErrors but returns the names of the attributes in a flat form
  // person[address][line1] => [ValidationError]
  public function getSimplifiedFlatFieldErrors()
  {
    $errors = $this->collect_errors_flat($this);
    $ret = array();

    // fix attribute names for the final flat array
    foreach ($errors as $attr_keys_errors)
    {
      $attr_flat = '';
      foreach ($attr_keys_errors['keys'] as $i => $attr)
      {
        if ($i == 0)
          $attr_flat .= $attr .'[';
        else if ($i == count($attr_keys_errors['keys']) -1)
          $attr_flat .= $attr .']';
        else
          $attr_flat .= $attr .'][';
      }
      $ret[$attr_flat] = $attr_keys_errors['errors'];
    }
    

    return $ret;
  }

  private function collect_errors_flat($validation_errors)
  {
    $attr_errors = array();
    foreach ($validation_errors->getFieldErrors() as $field => $error)
    {
      // list of single errors or array of has many errors for each item
      // for has many errors, the ObjectValidationErrors can have an array of ObjectValidationErrors, one per Object in a
      // has many relationship, and inside each OVE could have ValidationError for each attribute (one or many per attribute)
      if (is_array($error))
      {
        $is_single = false;
        foreach ($error as $i => $err)
        {
          //echo "error $i ". PHP_EOL;
          // If it's a single error...
          if (is_a($err, 'CaboLabs\Phersistence\phersistent\ValidationError'))
          {
            //echo "is ValidationError". PHP_EOL;
            $is_single = true;
            break;
          }

          // recursion to check for single errors or for array of ObjectValidationErrors for has many object attributes
          $attr_errors_rec = $this->collect_errors_flat($err);

          foreach ($attr_errors_rec as $attr => $errmany)
          {
            array_unshift($errmany['keys'], $field, $i); // solution for the not supported ... below
            $attr_errors[] = [
              'errors' => $errmany['errors'],
              'keys' => $errmany['keys'] //[$field, $i, ...$errmany['keys']] -- this only works on php 7.4, server has 7.2
            ];
          }
        }

        if ($is_single)
        {
          // if it's a single error
          $attr_errors[] = [
            'errors' => $error,
            'keys' => array($field)
          ];
        }
      }
      // is another validation_errors object
      else
      {
        // recursion
        $attr_errors_rec = $this->collect_errors_flat($error);

        foreach ($attr_errors_rec as $attr => $err)
        {
          //print_r($err);
          //$attr_errors[$field.'['.$attr] = $err;
          array_unshift($err['keys'], $field); // solution for the not supported ... below
          $attr_errors[] = [
            'errors' => $err['errors'],
            'keys' => $err['keys'] // [$field, ...$err['keys']] -- this only works on php 7.4, server has 7.2
          ];
        }
      }
    }

    return $attr_errors;
  }

  // for merging two or more ObjevtValidationErrors
  public function addAll($errors = array())
  {
    $this->field_errors = array_merge($this->field_errors, $errors);
  }

  // countable
  public function count(): int
  {
    return count($this->field_errors);
  }

  // iterator
  public function rewind(): void
  {
    $this->position = 0;
  }

  public function current(): mixed
  {
    return $this->field_errors[$this->position];
  }

  public function key(): mixed
  {
    return $this->position;
  }

  public function next(): void
  {
    ++$this->position;
  }

  public function valid(): bool
  {
    return isset($this->field_errors[$this->position]);
  }

  // ArrayAccess
  public function offsetSet($offset, $value): void
  {
    if (is_null($offset))
    {
      $this->field_errors[] = $value;
    }
    else
    {
      $this->field_errors[$offset] = $value;
    }
  }

  public function offsetExists($offset): bool
  {
    return isset($this->field_errors[$offset]);
  }

  public function offsetUnset($offset): void
  {
    unset($this->field_errors[$offset]);
  }

  public function offsetGet($offset): mixed
  {
    return isset($this->field_errors[$offset]) ? $this->field_errors[$offset] : null;
  }
}

class ValidationError {

  private $class;
  private $attr;
  private $value;      // violating value
  private $constraint; // violated constraint

  public function __construct($class, $attr, $value, $constraint)
  {
    $this->class = $class;
    $this->attr = $attr;
    $this->value = $value;
    $this->constraint = $constraint;
  }

  public function getMessage()
  {
    return "On ". $this->class .".". $this->attr .", ". $this->constraint->getErrorMessage($this->value);
  }
}

class MaxLengthConstraint extends PhConstraint {

  protected $max;

  public function __construct( $max )
  {
    $this->max = $max;
  }

  public function validate($class, $attr, $value, $object)
  {
    // null values comply with this constraint
    if ($value === NULL) return true;

    if (!is_string($value)) throw new \Exception("La restriccion MaxLength no se aplica al valor: " . $value);

    if (strlen($value) <= $this->max) return true;
    else
    {
      return new ValidationError($class, $attr, $value, $this);
    }
  }

  // Necesito el valor para poder generar el esquema.
  public function getValue()
  {
    return $this->max;
  }

  public function __toString()
  {
    return "" . $this->max;
  }

  public function getErrorMessage($value)
  {
    return "the length of the assigned value '". $value ." (". strlen($value) .") should lower or equal than ". $this->max;
  }
}

class MinLengthConstraint extends PhConstraint {

  protected $min;

  public function __construct( $min )
  {
    $this->min = $min;
  }

  public function validate($class, $attr, $value, $object)
  {
    // null values never comply with this constraint at least the min is 0
    // strlen(null) == 0 in PHP
    if ($value === NULL && $this->min > 0) return new ValidationError($class, $attr, $value, $this);

    if (!is_string($value)) throw new \Exception("La restriccion MinLength no se aplica al valor: " . $value);

    if (strlen($value) >= $this->min) return true;
    else
    {
      return new ValidationError($class, $attr, $value, $this);
    }
  }

  public function __toString()
  {
    return "" . $this->min;
  }

  public function getValue()
  {
    return $this->min;
  }

  public function getErrorMessage($value)
  {
    return "the length of the assigned value '". $value ."' (". strlen($value) .") should greater or equal than ". $this->min;
  }
}

// lower or equal to
class MaxConstraint extends PhConstraint {

  protected $max;

  public function __construct( $max )
  {
    $this->max = $max;
  }

  public function validate($class, $attr, $value, $object)
  {
    if (!is_numeric($value)) throw new \Exception("The constraint max does not apply to the value " . $value);

    if ((float)$value <= $this->max) return true;
    else
    {
      return new ValidationError($class, $attr, $value, $this);
    }
  }

  public function getValue()
  {
    return $this->max;
  }

  public function __toString()
  {
    return "" . $this->max;
  }

  public function getErrorMessage($value)
  {
    return "the assigned value ". $value ." should be lower or equal than ". $this->max;
  }
}

// greater or equal to
class MinConstraint extends PhConstraint {

  protected $min;

  public function __construct( $min )
  {
    $this->min = $min;
  }

  public function validate($class, $attr, $value, $object)
  {
    if (!is_numeric($value)) throw new \Exception("The constraint min does not apply to the value " . $value);

    if ((float)$value >= $this->min) return true;
    else
    {
      return new ValidationError($class, $attr, $value, $this);
    }
  }

  public function getValue()
  {
    return $this->min;
  }

  public function __toString()
  {
    return "" . $this->min;
  }

  public function getErrorMessage($value)
  {
    return "the assigned value ". $value ." should be greater or equal than ". $this->min;
  }
}

// strict lower than
class LowerConstraint extends PhConstraint {

  protected $max;

  public function __construct($max)
  {
    $this->max = $max;
  }

  public function validate($class, $attr, $value, $object)
  {
    if (!is_numeric($value)) throw new \Exception("The constraint lower does not apply to the value " . $value);

    if ((float)$value < $this->max) return true;
    else
    {
      return new ValidationError($class, $attr, $value, $this);
    }
  }

  public function getValue()
  {
    return $this->max;
  }

  public function __toString()
  {
    return "" . $this->max;
  }

  public function getErrorMessage($value)
  {
    return "the assigned value ". $value ." should be lower than ". $this->min;
  }
}

// strict greather than
class GreaterConstraint extends PhConstraint {

  protected $min;

  public function __construct($min)
  {
    $this->min = $min;
  }

  public function validate($class, $attr, $value, $object)
  {
    if (!is_numeric($value)) throw new \Exception("The constraint greater does not apply to the value " . $value);

    if ((float)$value > $this->min) return true;
    else
    {
      return new ValidationError($class, $attr, $value, $this);
    }
  }

  public function getValue()
  {
    return $this->min;
  }

  public function __toString()
  {
    return "" . $this->min;
  }

  public function getErrorMessage($value)
  {
    return "the assigned value ". $value ." should be greater than ". $this->min;
  }
}

class Between extends PhConstraint {

  protected $min;
  protected $max;

  public function __construct( $min, $max )
  {
    $this->min = new MinConstraint($min);
    $this->max = new MaxConstraint($max);
  }

  public function validate($class, $attr, $value, $object)
  {
    if ($this->min->validate($class, $attr, $value, $object) === true &&
        $this->max->validate($class, $attr, $value, $object) === true)
    {
      return true;
    }
    else
    {
      return new ValidationError($class, $attr, $value, $this);
    }
  }

  public function __toString()
  {
    return $this->min->__toString() . ".." . $this->max->__toString();
  }

  public function getMin() { return $this->min->getValue(); }
  public function getMax() { return $this->max->getValue(); }

  public function getErrorMessage($value)
  {
    return "the assigned value ". $value ." should be in ". $this->min ."..". $this->max;
  }
}

class EmailConstraint extends Matches {

  // https://regex101.com/r/9kAwoY/1
  const email_pattern = '/^[a-z]+[a-z0-9_\.\-\+]*@((?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.))+([a-z0-9])((?:[a-z0-9-]*[a-z0-9])?)$/';

  public function __construct()
  {
    parent::__construct(self::email_pattern);
  }

  public function getErrorMessage($value)
  {
    return "the assigned value '". $value ."' is not a valid email address";
  }
}

class DateConstraint extends Matches {

  // On Class->attr, the assigned value 'abcdxx' has not a valid datetime format YYYY-MM-DD hh:mm:ss
  const date_pattern = '/^(-?(?:[1-9][0-9]*)?[0-9]{4})-(1[0-2]|0[1-9])-(3[01]|0[1-9]|[12][0-9])$/';

  public function __construct()
  {
    parent::__construct(self::date_pattern);
  }

  public function __toString()
  {
    return 'YYYY-MM-DD';
  }

  public function getErrorMessage($value)
  {
    return "the assigned value '". $value ."' has not a valid date format YYYY-MM-DD";
  }
}

class DateTimeConstraint extends Matches {

  const datetime_pattern = '/^(-?(?:[1-9][0-9]*)?[0-9]{4})-(1[0-2]|0[1-9])-(3[01]|0[1-9]|[12][0-9]) (2[0-3]|[01][0-9]):([0-5][0-9]):([0-5][0-9])(\\.[0-9]+)?(Z)?$/';

  public function __construct()
  {
    parent::__construct(self::datetime_pattern);
  }

  public function __toString()
  {
    return 'YYYY-MM-DD hh:mm:ss';
  }

  public function getErrorMessage($value)
  {
    return "the assigned value '". $value ."' has not a valid datetime format YYYY-MM-DD hh:mm:ss";
  }
}

/*
  "^The": matches any string that starts with "The";
  "of despair$": matches a string that ends in the substring "of despair";
  "^abc$": a string that starts and ends with "abc" -- that could only be "abc" itself!
  "notice": a string that has the text "notice" in it.
  You can see that if you don't use either of the two characters we mentioned, as in the last example,
  you're saying that the pattern may occur anywhere inside the string -- you're not "hooking" it to any of the edges.
  "ab*": matches a string that has an a followed by zero or more b's ("a", "ab", "abbb", etc.);
  "ab+": same, but there's at least one b ("ab", "abbb", etc.);
  "ab?": there might be a b or not;
  "a?b+$": a possible a followed by one or more b's ending a string.
  You can also use bounds, which come inside braces and indicate ranges in the number of occurences:
  "ab{2}": matches a string that has an a followed by exactly two b's ("abb");
  "ab{2,}": there are at least two b's ("abb", "abbbb", etc.);
  "ab{3,5}": from three to five b's ("abbb", "abbbb", or "abbbbb").
  Note that you must always specify the first number of a range (i.e, "{0,2}", not "{,2}"). Also, as you might
  have noticed, the symbols '*', '+', and '?' have the same effect as using the bounds "{0,}", "{1,}", and "{0,1}",
  respectively.
  "a(bc)*": matches a string that has an a followed by zero or more copies of the sequence "bc";
  "a(bc){1,5}": one through five copies of "bc."
  There's also the '|' symbol, which works as an OR operator:
  "hi|hello": matches a string that has either "hi" or "hello" in it;
  "(b|cd)ef": a string that has either "bef" or "cdef";
  "(a|b)*c": a string that has a sequence of alternating a's and b's ending in a c;
  A period ('.') stands for any single character:
  "a.[0-9]": matches a string that has an a followed by one character and a digit;
  "^.{3}$": a string with exactly 3 characters.
  Bracket expressions specify which characters are allowed in a single position of a string:
  "[ab]": matches a string that has either an a or a b (that's the same as "a|b");
  "[a-d]": a string that has lowercase letters 'a' through 'd' (that's equal to "a|b|c|d" and even "[abcd]");
  "^[a-zA-Z]": a string that starts with a letter;
  "[0-9]%": a string that has a single digit before a percent sign;
  ",[a-zA-Z0-9]$": a string that ends in a comma followed by an alphanumeric character.
  You can also list which characters you DON'T want -- just use a '^' as the first symbol in a bracket expression
  (i.e., "%[^a-zA-Z]%" matches a string with a character that is not a letter between two percent signs).
  In order to be taken literally, you must escape the characters "^.[$()|*+?{\" with a backslash ('\'), as
  they have special meaning. On top of that, you must escape the backslash character itself in PHP3 strings, so,
  for instance, the regular expression "(\$|�)[0-9]+" would have the function call: ereg("(\\$|�)[0-9]+", $str)
  (what string does that validate?)
  Example 1. Examples of valid patterns
    * /<\/\w+>/
    * |(\d{3})-\d+|Sm
    * /^(?i)php[34]/
    * {^\s+(\s+)?$}
  Example 2. Examples of invalid patterns
	* /href='(.*)' - missing ending delimiter
	* /\w+\s*\w+/J - unknown modifier 'J'
	* 1-\d3-\d3-\d4| - missing starting delimiter
*/
class Matches extends PhConstraint {

  private $regex;

  public function __construct( $regex )
  {
    $this->regex = $regex;
  }

  public function validate($class, $attr, $value, $object)
  {
    if ($value == NULL) return new ValidationError($class, $attr, $value, $this);

    if (!is_string($value)) throw new \Exception("The constraint matches does not apply to the value: " . $value);

    if (preg_match($this->regex, $value)) return true;
    else
    {
      return new ValidationError($class, $attr, $value, $this);
    }
  }

  public function getValue()
  {
    return $this->regex;
  }

  public function getErrorMessage($value)
  {
    return "the assigned value '". $value ."' doesn't match the regex ". $this->regex;
  }
}

class Nullable extends PhConstraint {

  private $nullable;

  public function __construct( $nullable )
  {
    $this->nullable = $nullable;
  }

  public function setValue( $nullable )
  {
   	$this->nullable = $nullable;
  }

  // Needed because this affects the schema generation
  public function getValue()
  {
    return $this->nullable;
  }

  public function validate($class, $attr, $value, $object)
  {
    if ($this->nullable || $value !== NULL) return true;
    else
    {
      return new ValidationError($class, $attr, $value, $this);
    }
  }

  public function getErrorMessage($value)
  {
    return "the assigned value can't be null";
  }
}

class BlankConstraint extends PhConstraint {

  private $blank;

  public function __construct( $blank )
  {
    $this->blank = $blank;
  }

  public function getValue()
  {
    return $this->blank;
  }

  public function validate($class, $attr, $value, $object)
  {
    if ($value === NULL) return true; // blank o no blank no dice nada de si es null o no null, ese chekeo se debe hacer en otro lado.

    if (!is_string($value)) throw new \Exception("The constraint blank does not apply to the value: $value");

    // The string is not null, if blank => any string passes
    if ($this->blank) return true;

    // Not blank, all but the empty string
    if (strcmp($value, "") != 0) return true;
    else
    {
      return new ValidationError($class, $attr, $value, $this);
    }
  }

  public function getErrorMessage($value)
  {
    return "the assigned value can't be empty";
  }
}

class InList extends PhConstraint {

  private $array;

  public function __construct( $array )
  {
    $this->array = $array;
  }

  public function validate($class, $attr, $value, $object)
  {
    if (in_array($value, $this->array)) return true;
    else
    {
      return new ValidationError($class, $attr, $value, $this);
    }
  }

  public function getList()
  {
    return $this->array;
  }

  public function getErrorMessage($value)
  {
    return "the assigned value ". $value ." is not on the list ". print_r($this->array, true);
  }
}

class Unique extends PhConstraint {

  public function __construct()
  {
  }

  public function validate($class, $attr, $value, $object)
  {
    $parts = explode('\\', $class);
    $simpleclass = $parts[count($parts)-1];
    global ${$simpleclass};

    $where = array(
      array($attr, '=', $value)
    );
    $res = ${$simpleclass}->findBy($where, 1, 0);
    if (count($res) == 0) return true; // there is no stored instance with the same value
    else if ($res[0]->id ==$object->id) return true; // if the stored instance is the same, there is no error

    return new ValidationError($class, $attr, $value, $this);
  }

  public function getErrorMessage($value)
  {
    return "the assigned value '$value' already exists";
  }
}

?>

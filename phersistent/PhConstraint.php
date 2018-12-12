<?php

namespace phersistent;

abstract class PhConstraint {

  public abstract function validate($class, $attr, $value);

  // number constraints
  public static function min( $min ) { return new MinConstraint($min); }
  public static function max( $max ) { return new MaxConstraint($max); }
  public static function lower( $max ) { return new MaxConstraint($max-1); }
  public static function greater( $min ) { return new MinConstraint($min+1); }
  public static function between( $min, $max ) { return new Between($min, $max); }

  // string constraints
  public static function email() { return new EmailConstraint(); }
  public static function matches($regexp) { return new Matches($regexp); }
  public static function date() { return new DateConstraint(); }
  public static function datetime() { return new DateTimeConstraint(); }
  public static function maxLength( $max ) { return new MaxLengthConstraint($max); }
  public static function minLength( $min ) { return new MinLengthConstraint($min); }

  // general
  public static function nullable( $nullable ) { return new Nullable($nullable); }
  public static function blank( $blank ) { return new BlankConstraint($blank); }
  public static function inList( $array ) { return new InList($array); }
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
    // TODO
  }
}

class MaxLengthConstraint extends PhConstraint {

  protected $max;

  public function __construct( $max )
  {
    $this->max = $max;
  }

  public function validate( $value )
  {
    if ($value === NULL) return true; // Si es null no supera el valor maximo.
    if (!is_string($value)) throw new Exception("La restriccion MaxLength no se aplica al valor: " . $value);
    return (strlen($value) <= $this->max);
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
}

class MinLengthConstraint extends PhConstraint {

  protected $min;

  public function __construct( $min )
  {
    $this->min = $min;
  }

  public function validate( $value )
  {
    if ($value === NULL ) return false; // FIXME: si es null, y min es 0, no deberia tirar true?
    if (!is_string($value)) throw new Exception("La restriccion MinLength no se aplica al valor: " . $value);
    return (strlen($value) >= $this->min);
  }

  public function __toString()
  {
    return "" . $this->min;
  }

  public function getValue()
  {
    return $this->min;
  }
}

class MaxConstraint extends PhConstraint {

  protected $max;

  public function __construct( $max )
  {
    $this->max = $max;
  }

  public function validate( $value )
  {
    if (!is_numeric($value)) return false; // throw new Exception("La restriccion Max no se aplica al valor: " . $value);
    return ((float)$value <= $this->max);
  }

  public function getValue()
  {
    return $this->max;
  }

  public function __toString()
  {
    return "" . $this->max;
  }
}

class MinConstraint extends PhConstraint {

   protected $min;

   public function __construct( $min )
   {
      $this->min = $min;
   }
   public function validate( $value )
   {
      if (!is_numeric($value)) return false; //throw new Exception("La restriccion Min no se aplica al valor: " . $value);

      // Se que es numeric entonces los transformo a la clase mas generica de numner que es double para poder comparar.

      // FIXME: PROBLEMAS SI EL DATO QUE VIENE ES UN STRING QUE REPRESENTA UN NUEMRO!!
      //if (!is_int($value)) throw new Exception("La restriccion Min no se aplica al valor: " . $value);
      return ((float)$value >= $this->min);
   }

   public function getValue()
   {
      return $this->min;
   }

   public function __toString()
   {
      return "" . $this->min;
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

  public function validate( $value )
  {
    return ($this->min->validate($value) && $this->max->validate($value));
  }

  public function __toString()
  {
    return $this->min->__toString() . ".." . $this->max->__toString();
  }

  public function getMin() { return $this->min->getValue(); }
  public function getMax() { return $this->max->getValue(); }
}

class EmailConstraint extends Matches {

  const email_pattern = '/^[a-z]+[a-z0-9\.|\-|_]*@([a-z]+[a-z0-9\.|\-|_]*){1,4}\.[a-z]{2,4}$/';

  public function __construct()
  {
    parent::__construct( self::email_pattern );
  }
}

class DateConstraint extends Matches {

  const date_pattern = '/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/';

  public function __construct()
  {
    parent::__construct( self::email_pattern );
  }

  public function __toString()
  {
    return 'YYYY-MM-DD';
  }
}

class DateTimeConstraint extends Matches {

  const datetime_pattern = '/^(-?(?:[1-9][0-9]*)?[0-9]{4})-(1[0-2]|0[1-9])-(3[01]|0[1-9]|[12][0-9]) (2[0-3]|[01][0-9]):([0-5][0-9]):([0-5][0-9])(\\.[0-9]+)?(Z)?$/';

  public function __construct()
  {
    parent::__construct( self::email_pattern );
  }

  public function __toString()
  {
    return 'YYYY-MM-DD hh:mm:ss';
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

  private $regexp;

  public function __construct( $regexp )
  {
    $this->regexp = $regexp;
  }

  public function validate( $value )
  {
    if ($value == NULL) return false; // Si es NULL ni siquiera le puedo aplicar la restriccion porque es para strings

    if (!is_string($value)) throw new Exception("La restriccion ". get_class($this) ." no se aplica al valor: " . $value);
    return preg_match ( $this->regexp, $value );
  }

  public function getValue()
  {
    return $this->regexp;
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

  public function validate( $value )
  {
    if ( !$this->nullable ) return ($value != NULL);
    return true;
  }
}

class BlankConstraint extends PhConstraint {

  private $blank;

  public function __construct( $blank )
  {
    $this->blank = $blank;
  }
  public function validate( $value )
  {
    if ($value === NULL) return true; // blank o no blank no dice nada de si es null o no null, ese chekeo se debe hacer en otro lado.
    if (!is_string($value)) throw new Exception("BlankConstraint.validate: el tipo de ($value) debe ser string");
    if ($this->blank) return true; // Si lleog aca es que es string y no es null, asi que cualquier string debe cumplir, hasta el vacio.
    return ( strcmp($value, "") != 0 ); // Not blank, cumplen todos menos el vacio....
  }
}

class InList extends PhConstraint {

  private $array;

  public function __construct( $array )
  {
    $this->array = $array;
  }
  public function validate( $value )
  {
    return in_array($value, $this->array);
  }

  public function getList()
  {
    return $this->array;
  }
}

?>

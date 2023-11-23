<?php
/* OOHForms: password
 *
 * Copyright (c) 1998 by Jay Bloodworth
 *
 * $Id: of_password.inc,v 1.1.1.1 2000/04/17 16:40:15 kk Exp $
 */

class of_password extends of_element {

  var $maxlength;
  var $minlength;
  var $length_e;
  var $valid_regex;
  var $valid_icase;
  var $valid_e;
  var $pass;
  var $size;

  // Constructor
  function __construct($a) {
    $this->setup_element($a);
    if ($a["type"]=="password")
      $this->pass=1;
  }

  function self_get($val,$which, &$count) {
    $str = "";
    
    if (is_array($this->value))
      $v = htmlspecialchars(array_key_exists($which,$this->value) ? $this->value[$which] : "");
    else 
      $v = htmlspecialchars($this->value);
    $n = $this->name . ($this->multiple ? "[]" : "");
    $str .= "<input name='$n' value=\"$v\"";
    $str .= ($this->pass)? " type='password'" : " type='password'";
    if ($this->maxlength)
      $str .= " maxlength='$this->maxlength'";
    if ($this->size>50) $this->size=50;
    switch ($this->class) {
	case 'ipi':
	case 'ipe':
	   if ($this->size>15) {
		$size = floor($this->size / 1.8);
	   	break;
	   }
	default:
	   $size = $this->size;
    }
    if (!$this->class) $this->class="textField";
    $str .= " class='$this->class'";
    if ($size) 
      $str .= " size='$size'";
    if ($this->extrahtml) 
      $str .= " $this->extrahtml";
    else
      $str .= " onfocus='this.select()'";
    $str .= ">";
    
    $count = 1;
    return $str;
  }

  function self_get_frozen($val,$which, &$count) {
    $str = "";
    
    if (is_array($this->value))
      $v = $this->value[$which];
    else 
      $v = $this->value;
    $n = $this->name . ($this->multiple ? "[]" : "");
    $str .= "<input type='hidden' name='$n' value='$v'>\n";
    if (strncmp(strtoupper($v),"HTTP://",7)==0) $v="<a href=$v target=_blank>$v</a>";
    // $str .= "<table border=0><tr><td>$v</td></tr></table>\n";
    $str .= "$v\n";
    
    $count = 1;
    return $str;
  }

  function self_get_js($ndx_array) {
   $str = "";
   if (is_array($ndx_array)) { //Added by DaveB
    reset($ndx_array);
    while (list($k,$n) = each($ndx_array)) {
      if ($this->length_e) {
        $str .= "if (f.elements[\"${n}\"].value.length < $this->minlength) {\n";
        $str .= "  alert(\"$this->length_e\");\n";
        $str .= "  f.elements[\"${n}\"].focus();\n";
        $str .= "  return(false);\n}\n";
      }
      if ($this->valid_e) {
        $flags = ($this->icase ? "gi" : "g");
	$AllowNull = "";
        $str .= "if (window.RegExp) {\n";
        $str .= "  var reg = new RegExp(\"$this->valid_regex\",\"$flags\");\n";
        $str .= "  if (!reg.test(f.elements[\"${n}\"].value)) {\n";
        $str .= "    alert(\"$this->valid_e\");\n";
        $str .= "    f.elements[\"${n}\"].focus();\n";
        $str .= "    return(false);\n";
        $str .= "  }\n}\n";
      }
    }
   } 
   return $str;
  }

  function self_validate($val) {
    if (!is_array($val)) $val = array($val);
    reset($val);
    while (list($k,$v) = each($val)) {
      if ($this->length_e && (strlen($v) < $this->minlength))
        return $this->length_e;
      if ($this->valid_e && (($this->icase && 
            !eregi($this->valid_regex,$v)) ||
           (!$this->icase &&
            !ereg($this->valid_regex,$v))))
        return $this->valid_e;
    }
    return false;
  } 

} // end PASSWORD

?>

<?php
/* OOHForms: date
 *
 * Copyright (c) 2009 by David Beveridge
 *
 */

class of_date extends of_text {

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

  function date_format($format,$v) {
	switch ($v) {
    		case 'CURRENT_TIMESTAMP':  
    		case '0000-00-00 00:00:00':
			$v = date($format); 
			break;
		default:
			$v = date($format,strtotime($v));
	}
	return $v;
  }

  function format_value($v) {
    switch ($this->valid_regex) {
            case 'nulltimestamp':
            case 'nulldatetime':
    		if ($v=='0000-00-00 00:00:00') $v='';
		if ($v) $v = str_replace("12:00:00 AM","",$this->date_format("D, d M Y h:i:s A",$v)); break;
	    case 'timestamp':
	    case 'datetime':
		if ($v) $v = str_replace("12:00:00 AM","",$this->date_format("D, d M Y h:i:s A",$v)); break;
            case 'nulldate':
    		if ($v=='0000-00-00') $v='';
		if ($v) $v = $this->date_format("D, d M Y",$v); break;
	    case 'date':
		$v = $this->date_format("D, d M Y",$v); break;
            case 'nulltime':
		if ($v) $v = $this->date_format("h:i:s A",$v); break;
	    case 'time':
		$v = $this->date_format("h:i:s A",$v); break;
	    default:
    }
    return $v;
  }

  function self_get($val,$which, &$count) {
    $str = "";
    
    if (is_array($this->value))
      $v = htmlspecialchars($this->value[$which]);
    else 
      $v = htmlspecialchars($this->value);
    if ($this->class <> "ips") $v = $this->format_value($v);
    $n = $this->name . ($this->multiple ? "[]" : "");
    $str .= "<input id='$n' name='$n' value=\"$v\"";
    $str .= ($this->pass)? " type='password'" : " type='text'";
    if ($this->maxlength)
      $str .= " maxlength='$this->maxlength'";
    if ($this->size>40) $this->size=40;
    switch ($this->class) {
	case 'ips':			// in place insert 
	case 'ipi':			// in place insert 
	   $size = 8;
	   break;
	case 'ipe':			// in place edit
	   $size = 20;
	   break;
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
    $v = $this->format_value($v);
    $n = $this->name . ($this->multiple ? "[]" : "");
    $str .= "<input type='hidden' name='$n' value='$v'>\n";
    $str .= "$v\n";
    
    $count = 1;
    return $str;
  }

  function self_get_js($ndx_array) {
   $str = "";
   if (is_array($ndx_array)) { //Added by DaveB
    foreach ($ndx_array as $k=>$n) {
      if ($this->length_e) {
        $str .= "if (f.elements[\"${n}\"].value.length < $this->minlength) {\n";
        $str .= "  alert(\"$this->length_e\");\n";
        $str .= "  f.elements[\"${n}\"].focus();\n";
        $str .= "  return(false);\n}\n";
      }
      if ($this->valid_e) {
        $flags = (isset($this->icase) ? "gi" : "g");
	$AllowNull = "";
	switch ($this->valid_regex) {
            case 'nulldate':
            case 'nulltime':
            case 'nulltimestamp':
            case 'nulldatetime':
		$AllowNull = "YES";
	    case 'date':
	    case 'time':
	    case 'timestamp':
	    case 'datetime':
		$str .= "if (!dateValidator(\"$this->valid_regex\",f.elements[\"${n}\"],f.elements[\"${n}\"].value,'".$AllowNull."')) {\n";
	        $str .= "    alert(ErrorStr);\n";
	        $str .= "    f.elements[\"${n}\"].focus();\n";
		$str .= "    return(false);\n";
	        $str .= "}\n";
		break;
	    default:
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
   } 
   return $str;
  }

  function self_validate($val) {
    if (!is_array($val)) $val = array($val);
    foreach ($val as $k=>$v) {
        switch ($this->valid_regex) {
            case 'nulldate':
            case 'nulltime':
            case 'nulltimestamp':
            case 'nulldatetime':
            case 'date':
            case 'time':
            case 'timestamp':
            case 'datetime':
                break;
            default:

      if ($this->length_e && (strlen($v) < $this->minlength)) {
        return $this->length_e;
	}//if
      if ($this->valid_e && (($this->icase && 
            !eregi($this->valid_regex,$v)) ||
           (!$this->icase &&
            !ereg($this->valid_regex,$v)))) {
        return $this->valid_e;
		}//if
	}//switch
    }
    return false;
  } 

} // end TEXT

?>

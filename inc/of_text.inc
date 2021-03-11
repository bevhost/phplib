<?php
/* OOHForms: text
 *
 * Copyright (c) 1998 by Jay Bloodworth
 *
 * $Id: of_text.inc,v 1.1.1.1 2000/04/17 16:40:15 kk Exp $
 */

class of_text extends of_element {

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

    $db = new $_ENV["DatabaseClass"];
    
    if (is_array($this->value))
      $v = htmlspecialchars($this->value[$which],ENT_COMPAT, "UTF-8");
    else 
      $v = htmlspecialchars($this->value,ENT_COMPAT, "UTF-8");
    $n = $this->name . ($this->multiple ? "[]" : "");
    $str .= "<input id='$n' name='$n' value=\"$v\"";
    $extra = "";
    if (@$this->LinkedTable) {
	$linkdesc = ""; if ($this->LinkDesc) $linkdesc = ", ".$db->qi($this->LinkDesc);
    	$str .= " type='hidden'><input autocomplete='off' name='".$n."_Selector'";
	$sql="select ".$db->qi($this->LinkField).$linkdesc." from ".$db->qi($this->LinkedTable)." where ".$db->qi($this->LinkField)."=".$db->quote($v);
	#echo "<!-- $sql -->";
	if ($v) {
	    $db->query($sql);
	    $v = "";
	    if ($db->next_record()) {
		if ($this->LinkDesc) $v=$db->f(1);
	    }
	}
	$str .= " onkeyup=ShowSelector(this,'".$this->LinkDesc."','".$this->action."') value=\"$v\"";;
    }
    $str .= ($this->pass)? " type='password'" : " type='text'";
    if ($this->maxlength)
      $str .= " maxlength='$this->maxlength'";
    if ($this->size>40) {
      if (empty($GLOBALS["widemode"])) $this->size=40;
      else $this->size=80;
    }
    $size = $this->size;
    switch ($this->action) {
	case 'ips':			// in place search
	case 'ipi':			// in place insert 
	case 'ipe':			// in place edit
           if ($this->size>15) {
              if (empty($GLOBALS["widemode"])) $size = floor($this->size / 1.8);
              else $size = floor($this->size / 1.2);
	   }
	   $size=false;
	   break;
	default:
	   $size = $this->maxlength;
    	   if ($size>40) $size=40;
    }
    if (!$this->class) $this->class="textField";
    $str .= " class='$this->class'";
    if ($size) 
      $str .= " size='$size'";
    else
      $str .= " style='width:80%'";
    if ($this->extrahtml) 
      $str .= " $this->extrahtml";
    else
      $str .= " onfocus='this.select()'";
    $str .= " />";
    
    $count = 1;
    return $str;
  }

  function self_get_frozen($val,$which, &$count) {
    $str = "";
    
    if (is_array($this->value))
      $v = $this->value[$which];
    else 
      $v = $this->value;
    $v = htmlentities($v,ENT_IGNORE,"UTF-8");
    $n = $this->name . ($this->multiple ? "[]" : "");
    $str .= "<input type='hidden' name='$n' id='$n' value='$v'>\n";
    if (strncmp(strtoupper($v),"HTTP://",7)==0) $v="<a href=$v target=_blank>$v</a>";
    // $str .= "<table border=0><tr><td>$v</td></tr></table>\n";
    if ($this->format) $v=money_fmat($this->format,$v);
    $str .= "$v\n";
    
    $count = 1;
    if (isset($this->link)) return $this->link.$str."</a>";
    else return $str;
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
        $flags = (!empty($this->icase) ? "gi" : "g");
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
    foreach ($val as $k=>$v) {
      if ($this->length_e && (strlen($v) < $this->minlength)) {
        return $this->length_e;
      }//if
      $regex = $flags = "";
      if (!empty($this->icase)) $flags = "i";
      if (!empty($this->valid_regex)) {
	$regex = "/" . $this->valid_regex . "/" . $flags;
	if (!preg_match($regex,$v)) return $this->valid_e;
      }
    }
    return false;
  } 

} // end TEXT

?>

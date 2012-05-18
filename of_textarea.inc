<?php
/* OOHForms: textarea
 *
 * Copyright (c) 1998 by Jay Bloodworth
 *
 * $Id: of_textarea.inc,v 1.2 2001/08/09 01:07:13 richardarcher Exp $
 */

class of_textarea extends of_element {

  var $rows;
  var $maxlength;
  var $minlength;
  var $length_e;
  var $cols;
  var $wrap;

  // Constructor
  function of_textarea($a) {
    $this->setup_element($a);
  }

  function self_get($val,$which, &$count) {
    $str  = "";
    $str .= "<textarea name='$this->name'";
    $str .= " rows='$this->rows' cols='$this->cols'";
    if ($this->class) 
      $str .= " class='$this->class'";
    if ($this->wrap) 
      $str .= " wrap='$this->wrap'";
    if ($this->extrahtml) 
      $str .= " $this->extrahtml";
    $str .= ">" . htmlspecialchars($this->value) ."</textarea>";
    
    $count = 1;
    return $str;
  }

  function self_get_frozen($val,$which, &$count) {
    $str  = "";
    $str .= "<input type='hidden' name='$this->name'";
    $str .= " value=\"";
    $str .= htmlspecialchars($this->value);
    $str .= "\">\n";
    #$str .= "<table border=0><tr><td>\n";
    $str .=  nl2br($this->value);
    #$str .= "\n</td></tr></table>\n";
    
    $count = 1;
    return $str;
  }

  function self_get_js($ndx_array) {
   $str="";
   if (is_array($ndx_array)) { //Added by DaveB
    $str = "/* textarea */\n";
    reset($ndx_array);
    while (list($k,$n) = each($ndx_array)) {
      if ($this->length_e) {
        $str .= "if (f.elements[\"${n}\"].value.length < $this->minlength) {\n";
        $str .= "  alert(\"$this->length_e\");\n";
        $str .= "  f.elements[\"${n}\"].focus();\n";
        $str .= "  return(false);\n}\n";
      }
      if (!empty($this->valid_e)) {
        $flags = ($this->icase ? "gi" : "g");
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

} // end TEXTAREA

?>

<?php
/* OOHForms: checkbox
 *
 * Copyright (c) 1998 by Jay Bloodworth
 *
 * $Id: of_checkbox.inc,v 1.1.1.1 2000/04/17 16:40:15 kk Exp $
 */

class of_checkbox extends of_element {

  var $checked;

  // Constructor
  function __construct($a) {
    $this->setup_element($a);
  }

  function self_get($val, $which, &$count) {
    $str = "";
    
    if ($this->multiple) {
      $n = $this->name . "[]";
      $str .= "<input type='checkbox' name='$n' value='$val'";
      if (is_array($this->value)) {
        reset($this->value);
        while (list($k,$v) = each($this->value)) {
          if ($v==$val) {
            $str .= " checked"; 
            break; 
          }
        }
      }
    } else {
      $str .= "<input type='checkbox' name='$this->name'";
      $str .= " value='$this->value'";
      if ($this->checked) 
        $str .= " checked";
    }
    if ($this->extrahtml) 
      $str .= " $this->extrahtml";
    if ($this->class) 
      $str .= " class='$this->class'";
    $str .= ">\n";
    
    $count = 1;
    return $str;
  }

  function self_get_frozen($val, $which, &$count) {
    $str = "";
   
    if ($this->multiple) {
      $n = $this->name . "[]";
      $str .= "<input type='checkbox' name='$n' value='$val'";
      if (is_array($this->value)) {
        reset($this->value);
        while (list($k,$v) = each($this->value)) {
          if ($v==$val) {
            $str .= " checked";
            break;
          }
        }
      }
    } else {
      $str .= "<input type='checkbox' name='$this->name'";
      $str .= " value='$this->value'";
      if ($this->checked)
        $str .= " checked";
    }
    if ($this->class) {
      $str .= " class='$this->class'";
    }
    if ($this->extrahtml)
      $str .= str_replace("class=checkBoxes","class=FrozenCheckBoxes"," $this->extrahtml");
    $str .= " disabled>\n";
   
    $count = 1;
    return $str;
  }

  function self_load_defaults($val) {
    if ($this->multiple)
      $this->value = $val;
    elseif (isset($val) && (!$this->value || $val==$this->value)) 
      $this->checked=1;
    else 
      $this->checked=0;
  }

} // end CHECKBOX

?>

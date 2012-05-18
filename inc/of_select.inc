<?php
/* OOHForms: select
 *
 * Copyright (c) 1998 by Jay Bloodworth
 *
 * $Id: of_select.inc,v 1.1.1.1 2000/04/17 16:40:15 kk Exp $
 */

class of_select extends of_element {

  var $options;
  var $size;
  var $valid_e;

  // Constructor
  function of_select($a) {
    $this->setup_element($a);
    if ($a["type"]=="select multiple") $this->multiple=1;
  }
  
  function self_get($val,$which, &$count) {
    $found = false;
    $str = "";

    if ($this->multiple) {
      $n = $this->name . "[]";
      $t = "select multiple";
    } else {
      $n = $this->name;
      $t = "select";
    }
    if ($this->class=='ips') {
	if (array_key_exists("oper_$n",$_POST)) $sel=$_POST["oper_$n"]; else $sel=false;
	$str .= "<select name='oper_$n' id='oper_$n'>";
	$ops = array("="=>"exactly equals","<"=>"less than",">"=>"greater than","!="=>"not equal to");
	foreach($ops as $k=>$v) { 
		$str .= "<option ";
		if ($sel) if ($k==$sel) $str .= "selected ";
		$str .= "value='$k' title='$v'>$k</option>";
	}
	$str .= "</select>\n";
    }
    
    $str .= "<$t name='$n' id='$n'";
    if ($this->size) 
      $str .= " size='$this->size'";
    if ($this->extrahtml) 
      $str .= " $this->extrahtml";
    $str .= ">\n";

    if ($this->class=='ips') $str .= "<option value=''>Any</option>\n";
    else if ($this->valid_e) $str .= "<option value='0'>select...\n";

    reset($this->options);
    while (list($k,$o) = each($this->options)) {
      $str .= "<option";
      if (is_array($o)) 
        $str .= " value='" .  ($o["value"] ? $o["value"] : $o["label"] ). "'";
      else
        $str .= " value='$o'";
      if (!$this->multiple && ($this->value==$o["value"] || $this->value==$o)) {
        $str .= " selected='selected'";
	$found = true;
      }
      elseif ($this->multiple && is_array($this->value)) {
        reset($this->value);
        while (list($tk,$v) = each($this->value)) {
          if ($v==$o["value"] || $v==$o) { 
            $str .= " selected='selected'"; 
	    $found = true;
	    break; 
          }
        }
      }
      $str .= ">" . (is_array($o) ? $o["label"] : $o) . "</option>\n";
    }
    if (!$found) {
	if (!empty($val)) $str .= "<option selected value='$val'>$val\n";
    }
    $str .= "</select>";
    global $sess;
    if (!empty($this->edit)) {
      if (($this->class<>"ipe") and ($this->class<>"ipi") and ($this->class<>"ips")) {
    	if ($val) {
	    $str .= " <a href=javascript:gotoWin('".$sess->url("/$this->edit.php");
	    $str .= $sess->add_query(array("cmd"=>"Edit","id"=>$val))."')>Edit</a>\n";
    	}
    	$str .= " or <a href=javascript:gotoWin('".$sess->url("/$this->edit.php");
    	$str .= $sess->add_query(array("cmd"=>"Add","Field"=>$n))."')>Add new $this->edit</a>\n";
      }
    }
    
    $count = 1;
    return $str;
  }

  function old_self_get_frozen($val,$which, &$count) {

    return "<input type='hidden' name='$this->name' value='$this->value'>$this->value\n";
  }
    

  function self_get_frozen($val,$which, &$count) {
    $str = "";
    
    $x = 0;
    $n = $this->name . ($this->multiple ? "[]" : "");
    $v_array = (is_array($this->value) ? $this->value : array($this->value));
    reset($v_array);
    while (list($tk,$tv) = each($v_array)) {
      reset($this->options);
      while (list($k,$v) = each($this->options)) {
        if ((is_array($v) && 
           (($tmp=$v["value"])==$tv || $v["label"]==$tv))
         || ($tmp=$v)==$tv) {
          $x++;
	  $label = is_array($v) ? $v["label"] : $tmp;
          $str .= "<input type='hidden' name='$n' value='$tmp'>\n $label \n";
        }
      }
    }
    
    $count = $x;
    return $str;
  }

  function self_get_js($ndx_array) {
    $str = "";
    
    if (!$this->multiple && $this->valid_e) {
      $str .= "if (f.$this->name.selectedIndex == 0) {\n";
      $str .= "  alert(\"$this->valid_e\");\n";
      $str .= "  f.$this->name.focus();\n";
      $str .= "  return(false);\n";
      $str .= "}\n";
    }
    
    return $str;
  }

  function self_validate($val) {
    if (empty($val) &&  $this->valid_e) return $this->valid_e; 
    if (!$this->multiple && $this->valid_e) {
      foreach($this->options as $k=>$v) {
	if ($val==$v["value"] || $val==$v) return false;
      }
    }
    return $this->valid_e;
  }

} // end SELECT

?>

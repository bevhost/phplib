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
  function __construct($a) {
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
    if ($this->action=='ips') {
	if (array_key_exists("oper_$n",$_POST)) $sel=$_POST["oper_$n"]; else $sel=false;
	if (isset($this->show_oper)) $xtra=""; else $xtra=" style='display:none'";
	$str .= "<select name='oper_$n' id='oper_$n'$xtra>";
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

    if ($this->action=='ips') $str .= "<option value=''>Any</option>\n";
    else if ($this->valid_e) $str .= "<option value='0'>select...\n";

#echo "<!--:$val:$this->value:"; var_dump($this->options); echo "-->";
    foreach ($this->options as $k=>$o) {
      $str .= "<option";
      if (is_array($o)) $value = isset($o["value"]) ? $o["value"] : $o["label"]; else $value = $o;
      $str .= " value='$value'";
      if (!$this->multiple && ($this->value==$value)) {
        $str .= " selected='selected'";
	$found = true;
      }
      elseif ($this->multiple && is_array($this->value)) {
        foreach ($this->value as $tk=>$v) {
      	  if (is_array($o)) $value = isset($o["value"]) ? $o["value"] : $o["label"]; else $value = $o;
          if ($v==$value) { 
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
	else if (!empty($this->value)) $str .= "<option selected value='$this->value'>$this->value\n";
	else $str .= "<option selected value=''>Any\n";
    }
    $str .= "</select>";
    global $sess;
    if (!empty($this->show_popup_links)) {
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
    foreach($v_array as $tk=>$tv) {
      foreach ($this->options as $k=>$v) {
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
	#if ($val==$v["value"] || $val==$v) return false;  #old
	if (isset($v["value"])) {
		if ($val==$v["value"]) return false;
	} else {
		if ($val==$v) return false;
	}
      }
    }
    return $this->valid_e;
  }

} // end SELECT

?>

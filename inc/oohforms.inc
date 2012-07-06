<?php
/* OOH! Forms!
 * 
 * Object Oriented HTML Forms
 *
 * Copyright (c) 1998 by Jay Bloodworth
 * 
 * $Id: oohforms.inc,v 1.17 1999/10/24 14:39:31 kk Exp $
 */

class of_element {

  var $name;
  var $value;
  var $class;
  var $multiple;
  var $extrahtml;
  var $is_file=false;
  var $is_hidden=false;
  var $is_checkbox=false;
  var $LinkedTable=false;  
  var $format=false;		// see php money_format()

  function marshal_dispatch($m,$func) {   
    $v = array_key_exists($this->name,$GLOBALS) ? $GLOBALS[$this->name] : "";
    $value = array_key_exists($this->name,$_POST) ? $_POST[$this->name] : $v;
//    if ($this->type <> "of_file") echo "<!-- MD type:$this->type name:$this->name method:$m func:$func v:$value -->\n";
    return $this->$func($value);
  }
  
  function self_get($val, $which, &$count) {
  }

  function self_show($val, $which) {
    $count = 0;
    print $this->self_get($val, $which, $count);
    return $count;
  }

  function self_get_frozen($val, $which, &$count) {
    return $this->self_get($val, $which, $count);
  }

  function self_show_frozen($val, $which) {
    $count = 0;
    print $this->self_get_frozen($val, $which, $count);
    return $count;
  }

  function self_validate($val) {
    return false;
  }

  function self_get_js($ndx_array) {
  }
  
  function self_print_js($ndx_array) {
    print $this->self_get_js($ndx_array);
  }

  // Note that this function is generally quite simple since
  // most of the work of dealing with different types of values
  // is now done in show_self.  It still needs to be overidable,
  // however, for elements like checkbox that deal with state
  // differently
  function self_load_defaults($val) {
    $this->value = $val;
  }

  // Helper function for compatibility
  function setup_element($a) {
    $cv_tab = array("type"=>"ignore",
        "min_l"=>"minlength",
        "max_l"=>"maxlength",
        "extra_html"=>"extrahtml");
    reset($a);
    while (list($k,$v) = each($a)) {
      #if ($cv_tab[$k]=="ignore") continue;
      #else $k = (array_key_exists($k,$cv_tab) ? $cv_tab[$k] : $k);
      #$this->$k = $v;
        if (array_key_exists($k,$cv_tab)) { $k = $cv_tab[$k]; }
        if ($k != "ignore") $this->$k = $v;
    }
  }

} // end ELEMENT

class of_hidden extends of_element {

  var $hidden=1;

  function of_hidden($a) {
    $this->setup_element($a);
  }

  function self_get($val,$which, &$count) {
    $str = "";

    $v = (is_array($this->value) ? $this->value : array($this->value));
    $n = $this->name . ($this->multiple ? "[]" : "");
    reset($v);
    while (list($k,$tv) = each($v)) {
      $str .= "<input type='hidden' name='$n' value='$tv'";
      if ($this->extrahtml)
        $str .=" $this->extrahtml";
      $str .= ">";
    }
    
    return $str;
  }
} // end HIDDEN

class of_reset extends of_element {

  var $src;

  function of_reset($a) {
    $this->setup_element($a);
  }

  function self_get($val, $which, &$count) {
    $str = "<input name='$this->name' type=reset value='$val'";
    if ($this->extrahtml) 
      $str .= " $this->extrahtml";
    $str .= ">";
    
    return $str;
  }
} // end RESET

class of_submit extends of_element {

  var $src;

  function of_submit($a) {    
    $this->setup_element($a);
  }

  function self_get($val, $which, &$count) {
    $str = "";
    
    $sv = empty($val) ? $this->value : $val;
    $str .= "<input name='$this->name' value='$sv'";
    if ($this->src) 
      $str .= " type='image' src='$this->src'";
    else 
      $str .= " type='submit'";
    if ($this->extrahtml) 
      $str .= " $this->extrahtml";
    $str .= ">";
    
    return $str;
  }
  
  function self_load_defaults($val) {
    // SUBMIT will not change its value
  }
} // end SUBMIT

class form {
  var $elements;
  var $hidden;
  var $jvs_name;
  var $isfile;
  var $n;
  var $in_table=false;   /* form is in a table row, has height restrictions */
  var $save_class=false; /* cells to be save immediately using this ajax function prefix */  
  var $calendar=false;   /* form has date field, requires javascript calendar popup */
  var $editor=false;	 /* form has htmlarea field, requires wysiwyg editor */

  function get_start($jvs_name="",$method="",$action="",$target="",$form_name="") {
    
    $str = "";
    
    $this->jvs_name = "";
    $this->n = 0;
    if (!$method) $method = "post";
    if (!$action) $action = $_SERVER["PHP_SELF"];
    if (!$target) $target = "_self";

    $str .= "<form name='$jvs_name' ";
    if ($this->isfile) {
      $str .= " enctype='multipart/form-data'";
      $method = "post";
    }
    $str .= " method='$method'";
    $str .= " action='$action'";
    $str .= " target='$target'";
    global $cmd;
    if ($cmd=="Close") $jvs_name="";  //DaveB  I don't want to validate a record I'm going to close.
    if ($cmd=="Delete") $jvs_name="";  //DaveB  I don't want to validate a record I'm going to delete.
    if ($cmd=="View") $jvs_name="";  //DaveB  I don't want to validate a record I'm going to view.
    if ($cmd=="Process") $jvs_name="";  //DaveB  I don't want to validate a record I'm going to process.
    if ($jvs_name) {
      $this->jvs_name = $jvs_name;
	$str .= " onsubmit=\"return ${jvs_name}_Validator(this)\"";
    }
    
    $str .= " accept-charset='UTF-8'>";
    
    return $str;
  }

  function start($jvs_name="",$method="",$action="",$target="",$form_name="") {
    print $this->get_start($jvs_name,$method,$action,$target,$form_name);
  }

  function get_finish($after="",$before="") {
    global $sess;
    $str = "";
    
    if ($this->hidden) {
      reset($this->hidden);
      while (list($k,$elname) = each($this->hidden)) 
        $str .= $this->get_element($elname);
    }
    if (is_object($sess) && (!isset($_COOKIE[$sess->name]))) {
      $str .= sprintf("<input type=\"hidden\" name=\"%s\" value=\"%s\" />\n", $sess->name, $sess->id);
    }
    if ($this->key<>"id" and array_key_exists($this->key,$GLOBALS)) {
      $str .= sprintf("<input type=\"hidden\" name=\"id\" value=\"%s\" key=\"%s\" />\n", $GLOBALS[$this->key],$this->key);
    }
    $referer = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : $_SERVER["PHP_SELF"];
    $str .= sprintf("<input type=\"hidden\" name=\"_http_referer\" value=\"%s\" />\n", $referer);

    if (true)   // serialize compress and encode the whole record so that you can tell which fields have been changed on submit.
    if ($GLOBALS["cmd"]=="Save") {
      reset($this->elements);
      while (list($k,$elrec) = each($this->elements)) {
	$el = $elrec["ob"];
	$old[$el->name] = $el->value;
      }
      $str .= sprintf("<input type=\"hidden\" name=\"_old_record_values\" value=\"%s\" />\n", 
		base64_encode(gzcompress(serialize($old))));
    }

    $str .= "\n</form>\n";

    if ($this->jvs_name) {
      $jvs_name = $this->jvs_name;
      $str .= "<script language='javascript'>\n<!--\n";
      $str .= "function ${jvs_name}_Validator(f) {\n";

      if (isset($before)) $str .= "$before\n";
      if (isset($this->before)) $str .= "$this->before\n";
      reset($this->elements);
      while (list($k,$elrec) = each($this->elements)) {
        $el = $elrec["ob"];
	$str .= "/* $k */\n";
        if (array_key_exists("ndx_array",$elrec)) $str .= $el->self_get_js($elrec["ndx_array"]);
      }
      if (isset($after)) $str .= "$after\n";
      if (isset($this->after)) $str .= "$this->after\n";
      $str .= "return true;\n}\n//-->\n</script>";
    }
    
    return $str;
  }
  
  function finish($after="",$before="") {
    print $this->get_finish($after, $before);
  }
  
  function add_element($el) {
    global $enum_sets;

    if (!is_array($el)) 
      return false;
    $n = $el["name"];
    if (empty($existing_enum_sets)) $js_sent=false; else
    if (array_key_exists($n,$existing_enum_sets)) { $js_sent=true; $enum_sets=$existing_enum_sets[$n]; } else $js_sent=false;
    $cv_tab = array("select multiple"=>"select", "image"=>"submit");
    if (array_key_exists($el["type"],$cv_tab))
        $t = "of_".$cv_tab[$el["type"]];
    else
        $t = "of_".$el["type"];

    if ($t=="of_date") $this->calendar = true;
    if ($t=="of_htmlarea") $this->editor = true;
    if ($t=="of_checkbox") $checkbox=true; else $checkbox=false;
    if ($this->in_table) {
	  if ($this->save_class) {  	
		/* Save each Cell */	
		$save = $this->save_class."SaveCell(this,\$index);";
		$valid = " onKeyUp='ValidatorOn=true' onclick='ValidatorOn=true'";
		$valid="";
	  } else {			
		/* Enable Save Row Button */
		$save = "enableSave(this);";
		$valid = "";
	  }
	  if (!array_key_exists('extrahtml',$el)) $el['extrahtml'] = '';
    	  if ($t=="of_checkbox") {
		$t = "of_text";
   		if (!isset($enum_sets)) {
			$enum_sets = 0;
			echo "<script>var enum_set = new Array();</script>";
    		}
		$el["extrahtml"].=" onkeyup='enum_set_chooser(this,$enum_sets);' onfocus='enum_set_chooser(this,$enum_sets);' onblur='$save'";
		if (!$js_sent) {
			echo "<script>enum_set[$enum_sets]=['".implode("','",$el["options"])."'];</script>\n";
			$existing_enum_sets[$n] = $enum_sets;
			$enum_sets++;
		}
    	  } else {
	        $el["extrahtml"] .= " onblur='$save' onfocus='this.select();'$valid";
	  }
    }
    // translate names like $foo[int] to $foo{int} so that they can cause no
    // harm in $this->elements
    # Original match
    # if (preg_match("/(\w+)\[(d+)\]/i", $el[name], $regs)) { 
    if (preg_match("/([a-zA-Z_]+)\[([0-9]+)\]/", $el["name"], $regs)) {
       $el["name"] = sprintf("%s{%s}", $regs[1], $regs[2]);
       $el["multiple"] = true;
    }
    $el = new $t($el);
    $el->type = $t; # as suggested by Michael Graham (magog@the-wire.com)
    if (isset($el->isfile)) 
      $this->isfile = true;
    $this->elements[$el->name]["ob"] = $el;
    if (isset($el->hidden))
      $this->hidden[] = $el->name;
    $el->is_checkbox = $checkbox;
  }

  function get_element($name,$value=false) {
    $str = "";
    $x   = 0;
    $flag_nametranslation = false;
    
    // see add_element: translate $foo[int] to $foo{int}
#   Original pattern
#   if (preg_match("/(w+)\[(\d+)\]/i", $name, $regs) {
    if (preg_match("/([a-zA-Z_]+)\[([0-9]+)\]/", $name, $regs)) {
       $org_name = $name;
       $name = sprintf("%s{%s}", $regs[1], $regs[2]);
       $flag_nametranslation = true;
    }

    $name = str_replace(" ","_",$name);
    $name = str_replace("(","_",$name);
    $name = str_replace(")","_",$name);
    $name = str_replace("/","_",$name);
    $name = str_replace(",","_",$name);
    $name = str_replace("#","_",$name);
    $name = str_replace("`","_",$name);
    $name = str_replace("'","_",$name);
    $name = str_replace('"',"_",$name);
    $name = str_replace("-","_",$name);
    
    if (!isset($this->elements[$name])) 
      if ($name=='reset') return "";
      else return "<p class=error>$name field not found in SQL record.</p>"; 

    if (!isset($this->elements[$name]["which"]))
      $this->elements[$name]["which"] = 0;
   
    $el = $this->elements[$name]["ob"];
    if (true == $flag_nametranslation)
      $el->name = $org_name; 

    if (false == $value) 
       $value = $el->value; 

    if (isset($this->elements[$name]["frozen"]))
      $str .= $el->self_get_frozen($value,$this->elements[$name]["which"]++, $x);
    else
      $str .= $el->self_get($value,$this->elements[$name]["which"]++, $x);

    //Orig code
    //$this->elements[$name]["ndx_array"][] = $this->n;
    //$this->n += $x;

    //Changed by DaveB
    $this->elements[$name]["ndx_array"][] = $name;
    //$this->n += $x;
    
    return $str;
  }

  function show_element($name, $value=false) {
    print $this->get_element($name, $value);
  }

  function ge($name, $value="") {
    return $this->get_element($name, $value);
  }

  function se($name, $value="") {
    $this->show_element($name, $value);
  }

  function ae($el) {
    $this->add_element($el);
  }

  function validate($default=false,$vallist="") {
    $errors = array();
    if ($vallist) {
      reset($vallist);
      $elrec = $this->elements[current($vallist)];
    } else {
      reset($this->elements);
      $elrec = current($this->elements);
    }
    while ($elrec) {
      $el = $elrec["ob"];
      if (isset($this->method)) $method=$this->method; else $method=false;
      if ($res = $el->marshal_dispatch($method,"self_validate")) {
	$errors[] = $res ;
      	if ($res) $this->errorfield = $el->name;
	//return $res; 
      }
    if ($vallist) {
        next($vallist);
        $elrec = $this->elements[current($vallist)];
      } else {
        next($this->elements);
        $elrec = current($this->elements);
      }
    }
    if (isset($this->errorfield)) return $errors;
    return $default;
  }

  function load_defaults($deflist="") {
    if ($deflist) {
      reset($deflist);
      $elrec = $this->elements[current($deflist)];
    } else {
      reset($this->elements);
      $elrec = current($this->elements);
    }
    while ($elrec) {
      $el = $elrec["ob"];
      #$el->marshal_dispatch(@$this->method,"self_load_defaults");
      $el->marshal_dispatch(false,"self_load_defaults");
      $this->elements[$el->name]["ob"] = $el;  // no refs -> must copy back
      if ($deflist) {
        next($deflist);
        $elrec = $this->elements[current($deflist)];
      } else {
        next($this->elements);
        $elrec = current($this->elements);
      }
    }
  }

  function unfreeze($flist="") {
    if ($flist) {
      reset($flist);
      $elrec = $this->elements[current($flist)];
    } else {
      reset($this->elements);
      $elrec = current($this->elements);
    }
    while ($elrec) {
      $el = $elrec["ob"];
      $this->elements[$el->name]["frozen"]=0;
      if ($flist) {
        next($flist);
        $elrec = $this->elements[current($flist)];
      } else {
        next($this->elements);
        $elrec = current($this->elements);
      }
    }
  }

  function freeze($flist="") {
    if ($flist) {
      reset($flist);
      $elrec = $this->elements[current($flist)];
    } else {
      reset($this->elements);
      $elrec = current($this->elements);
    }
    while ($elrec) {
      $el = $elrec["ob"];
      $this->elements[$el->name]["frozen"]=1;
      if ($flist) {
        next($flist);
        $elrec = current($flist) ? $this->elements[current($flist)] : false;
      } else {
        next($this->elements);
        $elrec = current($this->elements);
      }
    }
  }

  function getblob() {
        return true;
  }
  function show_image($fieldname,$id) {
        return true;
  }
  function show_image_href($fieldname,$id,$tablename) {
        return true;
  }

  function link($Source,$Target,$Table) {
	return true;
  }


} /* end FORM */

include($_ENV["libdir"] . "of_text.inc");
include($_ENV["libdir"] . "of_password.inc");
include($_ENV["libdir"] . "of_date.inc");
include($_ENV["libdir"] . "of_select.inc");
include($_ENV["libdir"] . "of_radio.inc");
include($_ENV["libdir"] . "of_checkbox.inc");
include($_ENV["libdir"] . "of_textarea.inc");
include($_ENV["libdir"] . "of_htmlarea.inc");
include($_ENV["libdir"] . "of_file.inc");

?>

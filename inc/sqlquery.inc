<?php
/*
 * PHP Base Library
 *
 * Copyright (c) 1998,1999 SH Online Dienst GmbH
 *                    Boris Erdmann, Kristian Koehntopp
 *
 * $Id: sqlquery.inc,v 1.7 1999/07/14 10:46:59 kk Exp $
 *
 */ 


class Sql_Query {
  var $classname = "Sql_Query";  ## Persistence Support
  var $persistent_slots = array(
    "conditions", "input_size", "input_max", "method", "lang", "translate", "container", "variable"
  );
  
  var $conditions = 1;      ## Allow for that many Query Conditions
  var $input_size = 20;     ## Used in text input field creation
  var $input_max  = 80;
  var $start_row  = 0;
  var $row_count  = 100;
  var $max_chooser = 20;			// (DaveB) Maximum drop down box entries.
  var $default_query = "id!='0'";		// (DaveB)
  var $default_sortorder = "id desc";		// (DaveB)
  var $extra_cond = "";				// (DaveB)

  var $method     = "post"; ## Generate get or post form...
  var $lang       = "en";   ## HTML Widget language

  var $translate = "on";    ## If set, translate column names
  var $container = "on";    ## If set, create a container table
  var $variable  = "on";    ## if set, create variable size buttons
  var $printview = "off";   // (daveB)
  var $base      = "x";     // (daveB)
  var $joins = array(); 
  var $tables = array();
  var $search = array();
  
  ## HTML Widget dictionary
  var $dict = array(
    "de" => array(
      "searchfor" => "Suchen nach:",
      "and"       => "und",
      "or"        => "oder",
      "like"      => "enthält",
      "not like"  => "not enthält",
      "reset"     => "Neu",
      "submit"    => "Ausführen",
      "less"      => "Weniger",
      "more"      => "Mehr",
      "begin"     => "Anfangen &lt;&lt;",
      "previous"  => "Vorhergehend &lt;",
      "shownext"  => "&gt; Zunächst",
      "rowsfrom"  => "Reihen Beginnen von",
      "end"       => "&gt;&gt; Ende",
      "orderby"	  => "ordered by"
    ),
    
    "en" => array(
      "searchfor" => "Enter your search conditions<br />",
      "and"       => "and",
      "or"        => "or",
      "like"      => "contains",
      "not like"  => "does not contain",
      "reset"     => "Reset Query",
      "submit"    => "Submit Query",
      "less"      => "Fewer Conditions",
      "more"      => "More Conditions",
      "begin"	  => "&lt;&lt;",
      "previous"  => "&lt;",
      "shownext"  => "&gt;",
      "rowsfrom"  => "rows from",
      "end"       => "&gt;&gt;",
      "orderby"	  => "ordered by"
    )
  );

  ## SQL comparision dictionary
  var $compare = array(
        "like" => "like",
        "not like" => "not like",
        "&gt;"    => "&gt;",
        "&lt;"    => "&lt;",
        "&gt;="   => "&gt;=",
        "&lt;="   => "&lt;=",
        "="    => "=",
        "&lt;&gt;"   => "&lt;&gt;"
      );


  function Sql_Query() {
	if (empty($this->database_class)) $this->database_class=$_ENV["DatabaseClass"];
	$db = new $this->database_class;
	if (!isset($this->table)) {
		if (isset($this->classname)) {
			$this->table = substr($this->classname,0,-10);
		}
	}
  }

  function start($class = "MyForm") {
  }

  # deprecated use the same function in tpl_form
  function search($form_elements) {
	$db = new $this->database_class;
        $query = "";
        foreach ($form_elements as $k=>$v) {
		if (($f = @$v["ob"]->field)   		// has an sql field
		  and (!empty($_POST[$k]))) {		// and was POSTed.
                        if ($query) $query .= " AND ";
                        $query .= $db->qi($f)." LIKE '%".$_POST[$k]."%'";
                }
        }
	return $query;
  }


  ## selection:
  ##
  ## Create a <select> tag of the class $class with the name $name.
  ## The tag contains the options named in array $option. If $trans
  ## is true, $option is exspected to be a hash of 
  ## "long name " => "sqlname" pairs. The option matching $old
  ## is created with the attribute "selected".
  ##
  function selection($name, $option, $old = "", $trans = "", $class = "MyForm") {

    $db = new $this->database_class;
    $base = $this->base;
    $res  = "";
    if ( $class == "query" ) $class="query_cell"; 		// Added by David Beveridge
    if (strpos($name,"sel")) $chooser = "onchange='UpdateChooser(this);'"; else $chooser="";
    $res .= sprintf("<select%s name=\"%s\" %s>\n",
              ($class)?" class='$class'":"",
              $name, $chooser);
    reset($option);
    while(list($k, $v) = each($option)) {
      if (($trans == "" && $old == $v)
       || ($trans != "" && $old == $db->qi($k)." desc")
       || ($trans != "" && $old == $db->qi($k)) 
       || ($trans != "" && $old == htmlspecialchars_decode($k))
       || ($trans != "" && $old == $k)) {
        $selected = " selected";
      } else {
        $selected = "";
      }
      
    if ($name==$base."[ord_1]") $extra = " &lt;"; else $extra="";

      $res .= sprintf("<option value=\"%s\"%s%s>%s%s</option>\n",
                ($trans)?$k:$v,
                ($class)?" class='$class'":"",
                ($selected)?" selected":"",
                $v,$extra);
   if ($name==$base."[ord_1]") 
      $res .= sprintf("<option value=\"%s desc\"%s%s>%s &gt;</option>\n",
                ($trans)?$k:$v,
                ($class)?" class='$class'":"",
                ($selected)?" selected":"",
                $v);
    }
    $res .= sprintf("      </select>");
    
    return $res;
  }

  ## fieldname:
  ##
  ## Given a basename $base, and attribute name $att and an attribute
  ## number $num, this functions returns an input field name
  ## $base[$name_$num].
  ##
  ## This construct can be imported into a function namespace with a
  ## single global instruction and the field name can be easily
  ## exploded into component names and numbers.


  function makename($base, $att, $num) {
    return sprintf("%s[%s_%d]", $base, $att, $num);
  }
  function makeid($base, $att, $num) {
    return sprintf("%s%s_%d", $base, $att, $num);
  }

  function param_nq($base,$index) {
    return array_key_exists($index,$GLOBALS[$base]) ? str_replace("`","",$GLOBALS[$base][$index]) : "";
  }
  function param($base,$index) {
    return array_key_exists($index,$GLOBALS[$base]) ? $GLOBALS[$base][$index] : "";
  }

  ## form:
  ##
  ## Draw SQL Query selection form.
  ##
  function form($base, $option, $class = "MyForm", $target = "") {
    global $user_id;
    if (empty($this->database_class)) {
	$this->database_class = $_ENV["DatabaseClass"];
    }
    $db = new $this->database_class;
   # $db2 = new $this->database_class;

    global $sess, $sortorder, $QUERY_STRING;

    if (!array_key_exists($base,$GLOBALS)) {
	$GLOBALS[$base] = array();
    }

    $QUERY_STRING="";
    ##
    ## load the HTML results of this function into $res.
    ##
    $res  = "";

    
    if ( $class == "query" ) $cell_class="query_cell"; 		// Added by David Beveridge
    else $cell_class=$class;			 		// Added by David Beveridge
								// References to $cell_class below changed from $class by DaveB.

    ## A hack. We cannot do language dependent initialisation of
    ## static values.
    if (isset($this->compare["not like"])) {
      $this->compare["not like"] = $this->dict[$this->lang]["not like"];
    }
    if (isset($this->compare["like"])) {
      $this->compare["like"] = $this->dict[$this->lang]["like"];
    }
    
    $res .= sprintf("<form class='$class' method=\"%s\" action=\"%s\">\n",
      $this->method, 
      ($target)?$target:$sess->self_url());
    

    ## Build $this->conditions many selection elements    
    for ($i=1; $i<= $this->conditions; $i++) {

      ## Build conjunction (first row does not have a conjunction)
      if ($i == 1) {
        $res .= sprintf("  %s\n", 
          $this->dict[$this->lang]["searchfor"]);
      } else {
        $res .= sprintf("  %s<br />\n", 
          $this->selection($this->makename($base, "conj", $i),
                           array("and" => $this->dict[$this->lang]["and"], "or" => $this->dict[$this->lang]["or"],") or ("=>") ".$this->dict[$this->lang]["or"]." ("),
                           $this->param($base,"conj_".$i), 
                           "on",
                           $class));
      }
      
      ## Build field selection
      $res .= sprintf("  %s\n",
        $this->selection(
          $this->makename($base, "sel", $i), 
          $option, 
          $this->param($base,"sel_".$i), 
          $this->translate, 
          $class));
        
      ## Build comparison selection
      $res .= sprintf("  %s\n",
        $this->selection(
          $this->makename($base, "comp", $i), 
          $this->compare, 
          $this->param($base,"comp_".$i), 
          "on", 
          $class));
      $res .= sprintf("<input type=\"text\" name=\"%s\" value=\"%s\" size=\"%d\" maxlength=\"%d\"%s />\n",
      //  ($class)?" class=$class":"",
        $this->makename($base, "input", $i),
        $this->param($base,"input_".$i),
        $this->input_size,
        $this->input_max,
        ($cell_class)?" class=\"$cell_class\"":"");
      $res .= "</span> ";
      $res .= sprintf(" \n");
    }

    ## Create variable size buttons

    $res .= sprintf("<br /><input type=\"submit\" name=\"%s\" value=\"%s\" />\n",
      $this->makename($base, "submit", 0),
      $this->dict[$this->lang]["submit"]);
    
    if ($this->variable) {
      $res .= sprintf("  <input type=\"submit\" name=\"%s\" value=\"%s\" />&nbsp;",
        $this->makename($base, "more", 0),
        $this->dict[$this->lang]["more"]);
      $res .= sprintf("<input type=\"submit\" name=\"%s\" value=\"%s\" />\n",
        $this->makename($base, "less", 0),
        $this->dict[$this->lang]["less"]);
    }
    
    $res .= sprintf("<input type=\"submit\" name=\"%s\" value=\"%s\" />\n",
      $this->makename($base, "submit", 0),
      $this->dict[$this->lang]["submit"]);
    
    $res .= sprintf(' <br /><input type="text" name="rowcount" value="%s" size="3" />', $this->row_count);
    $res .= sprintf(' %s',$this->dict[$this->lang]["rowsfrom"]);
    $res .= sprintf(' &nbsp;<input type="text" name="startingwith" value="%s" size="3" />', $this->start_row);
    $res .= sprintf(' <input class="small" type="submit" name="%s" value="%s" />', $this->makename($base,"begin",0),$this->dict[$this->lang]["begin"]);
    $res .= sprintf(' &nbsp;<input class="small" type="submit" name="%s" value="%s" />', $this->makename($base,"previous",0),$this->dict[$this->lang]["previous"]);
    $res .= sprintf(' <input class="small" type="submit" name="%s" value="%s" />', $this->makename($base,"shownext",0),$this->dict[$this->lang]["shownext"]);
    $res .= sprintf(' &nbsp;<input class="small" type="submit" name="%s" value="%s" />', $this->makename($base,"end",0),$this->dict[$this->lang]["end"]);

      if ($sortorder) $GLOBALS[$base]["ord_1"] = $sortorder;
      $res .= sprintf("  order by  %s\n",
        $this->selection( $this->makename($base, "ord", 1),
          $option, $GLOBALS[$base]["ord_1"], $this->translate, $class));


    if ($this->printview=="on") $res .= "<input type='hidden' name='cmd' value='Print' />";
    
    $res .= sprintf("</form>\n");
    $res .= sprintf("<!-- End %s generated query form -->\n", $this->classname);

    return $res;
  }
  
  ## plain_where:
  ##
  ## Given a base variable name, creates a condition suitable for
  ## the where clause of a SQL query.
  ##
  function plain_where($base) {
    $tt = "";
    $qq = "";
    $tc = 0;
    $used_joins = array(); 
    if (!isset($db)) {
      if (!$this->database_class) {
	$this->database_class = $_ENV["DatabaseClass"];
      }
      $db = new $this->database_class;
      #$db2 = new $this->database_class;
    }
    if ($this->table) $tt = $db->qi($this->table);

	if (empty($this->tpl_form_classname)) $this->tpl_form_classname = $this->table."form";
	$sql = "SELECT  FieldName, LinkTable, LinkField, LinkDesc, LinkCondition FROM LinkedTables WHERE FormName='$this->tpl_form_classname' ";
	$db->query($sql);
	while ($db->next_record()) {
		extract($db->Record);
		$this->tables[$FieldName] = $LinkTable;
		$this->joins[$FieldName] = $db->qi($this->table).".".$db->qi($FieldName)."=%s.".$db->qi($LinkField);
		$this->search[$FieldName] = $LinkDesc;
	}

    for($i=1; $i<=$this->conditions; $i++) {
      ## Only create conditions for used input fields
      if ($this->param($base,"input_".$i) == "")
        continue;

      if ($field = @$GLOBALS[$base]["sel_".$i]) {
	  if (empty($this->tables[$field])) $table=$tt;
	  else {
		$tc++;
		$table = "_q$tc";
		$TN = $this->tables[$field];
		$used_joins["$TN $table"] = sprintf($this->joins[$field],$db->qi($table));
		if (isset($this->search[$field])) $field=$this->search[$field];
	  }
      }      
      ## If necessary, add conjunction
      if (!empty($qq))
        $qq .= sprintf(" %s ", $this->param_nq($base,"conj_".$i));
      
      ## Handle "like"
#echo "<!-- ".$GLOBALS[$base]["comp_".$i]." -->";
      if (($this->param_nq($base,"comp_".$i) == "like") || ($this->param_nq($base,"comp_".$i) == "not like"))
        $v = "%".$this->param($base,"input_".$i)."%";
      else {
        $v = $this->param($base,"input_".$i);
        if (strpos($field,"Date")) {
          $c = count(explode("/",$v));
          if ($c==2) {
            list($d,$m) = explode("/",$v);
            if ($d>0 and $m>0 and $m<13 and $d<32) $v = "$m/$d";
          }
          if ($c==3) {
            list($d,$m,$y) = explode("/",$v);
            if ($d>0 and $m>0 and $y>0 and $y<100 and $m<13 and $d<32) $v = "$m/$d/$y";
          }
          $v = date("Y-m-d H:i:s",strtotime($v));
        }
      }

      $field = $db->qi($field);  
      ## Create subcondition
      $qq .= sprintf("%s %s '%s'",
              "$table.$field",
	      $this->param_nq($base,"comp_".$i),
              $v);
    }
    if (empty($qq)) {
	$qq = $this->extra_cond ? $this->extra_cond : "1";
    } else if ($this->extra_cond) $qq = "$this->extra_cond AND ($qq)"; 

    $this->join = "";
    foreach($used_joins as $lt=>$lc) $this->join .= "\nLEFT OUTER JOIN $lt ON $lc ";

    if ($order_field = @$GLOBALS[$base]["ord_1"]) {
		if (substr(strtolower($order_field),-5) == " desc") {
			$of = substr($order_field,0,-5); 
			$order = " DESC";
		} else {
			$of=$order_field;
			$order = "";
		}
      		$field = $db->qi($of);  
		if (!$table = @$this->tables[$of]) $table = $tt;
		return "(( $qq )) order by $table.$field$order";
    } 
    
    return "(( $qq ))";
  }

  ## translated_plain_where:
  ##
  ## Given a base variable name, creates a translated version of
  ## the where clause of a SQL query.
  ##
  function translated_plain_where($base, $field) {
    for($i=1; $i<=$this->conditions; $i++) {
      ## Only create conditions for used input fields
      if ($GLOBALS[$base]["input_".$i] == "")
        continue;

      ## If necessary, add conjunction
      if ($qq != "")
        $qq .= sprintf(" %s ", $this->dict[$this->lang][$GLOBALS[$base]["conj_".$i]]);
      
      ## Handle "like"
      if (($GLOBALS[$base]["comp_".$i] == "like") || ($GLOBALS[$base]["comp_".$i] == "not like"))
        $c = $this->dict[$this->lang][$GLOBALS[$base]["comp_".$i]];
      else
        $c = $this->compare[$GLOBALS[$base]["comp_".$i]];

      ## Create subcondition
      $qq .= sprintf("%s %s '%s'",
              $field[$GLOBALS[$base]["sel_".$i]],
              $c,
              $GLOBALS[$base]["input_".$i]);
    }
    
    if (empty($qq)) {
	$qq = $this->extra_cond ? $this->extra_cond : "1";
    } else $qq = "$this->extra_cond AND ($qq)";
    
    return "( $qq ) order by " . $GLOBALS[$base]["ord_1"];
    //return "( $qq )";
  }
  
  ## where:
  ##
  ## Same as plain_where(), but also inspects the submit button
  ## used to submit the query. Changes $this->conditions appropriately.
  function where($base, $incr = 1) {

    if (isset($GLOBALS[$base]["begin_0"]))
      $this->start_row = 0;
    
    if (isset($GLOBALS[$base]["previous_0"]))
      $this->start_row -= $this->row_count;
    
    if (isset($GLOBALS[$base]["shownext_0"]))
      $this->start_row += $this->row_count;

    if ($this->start_row < 0)
      $this->start_row = 0;

    if (isset($GLOBALS[$base]["end_0"]))
      $this->start_row = 999999;

    if (isset($GLOBALS[$base]["less_0"]))
      $this->conditions -= $incr;
    
    if (isset($GLOBALS[$base]["more_0"]))
      $this->conditions += $incr;
    
    if ($this->conditions < 1)
      $this->conditions = 1;
    
    return $this->plain_where($base);
  }
}

?>

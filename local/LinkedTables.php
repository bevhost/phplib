<?php
include('phplib/prepend.php');

page_open(array("sess"=>$_ENV["SessionClass"],"auth"=>$_ENV["AuthClass"],"perm"=>$_ENV["PermClass"]));

get_request_values("table,FieldName,LinkTable,LinkField,LinkDesc,DefaultValue");

echo "<h2>Linked tables</h2>";

$f = new LinkedTablesform;

if ($submit) {
  switch ($submit) {

   case "Save":
    if ($id) $submit = "Edit";
    else $submit = "Add";
   case "Add":
   case "Edit":
    if (isset($auth)) {
     if (!$f->validate($result)) {
        $cmd = $submit;
        echo "<font class=bigTextBold>$cmd Linked Tables</font>\n";
        $f->display();
        page_close();
        exit;
     }
     else
     {
        echo "Saving....";
        $f->save_values();
        echo "<b>Done!</b><br>\n";
	$QUERY_STRING="";
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url()."\">";
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to LinkedTables.</a><br>\n";
        page_close();
        exit;
     }
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br>\n";
    }
   case "View":
   case "Back":
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"0; URL=".$sess->self_url()."\">";
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to LinkedTables.</a><br>\n";
        page_close();
        exit;

   case "Delete":
    if (isset($auth)) {
        echo "Deleting....";
        $f->save_values();
        echo "<b>Done!</b><br>\n";
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br>\n";
    }
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url()."\">";
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to LinkedTables.</a><br>\n";
        page_close();
        exit;
  }
} else {
    if ($id) {
	$f->find_values($id);
    }
}
switch ($cmd) {
    case "View":
    case "Delete":
	$f->freeze();
    case "Add":

    case "Copy":
    case "Edit":
	echo "<font class=bigTextBold>$cmd Linked Tables</font>\n";
	$f->display();
	break;
    case "Link":
	echo "<font class=bigTextBold>Link Wizard</font>\n";
	echo "<form method=post>\n";
	$FormName = $table."form";
	if ($table) echo "<input type=hidden name=table value='$table'><input type=hidden name=FormName value='$FormName'>\n";
	if ($FieldName) echo "<input type=hidden name=FieldName value='$FieldName'>\n";
	if ($LinkTable) echo "<input type=hidden name=LinkTable value='$LinkTable'>\n";
	if ($LinkField) echo "<input type=hidden name=LinkField value='$LinkField'>\n";
	if ($LinkDesc) echo "<input type=hidden name=LinkDesc value='$LinkDesc'>\n";
	if ($DefaultValue) echo "<input type=hidden name=DefaultValue value='$DefaultValue'>\n";
	else echo "<input type=hidden name=cmd value=Link>\n";
	echo "Setting Relationship for $FieldName on $FormName to get values from $LinkField ($LinkDesc) on $LinkTable \n<br>";
	if (!$table) {
		echo "Form <select method=post name=table onchange=this.form.submit()>\n  <option>select";
		$db->query("SHOW TABLES");
		while ($db->next_record()) {
			echo "\n  <option>".$db->f(0);
		}
		echo "\n</select>\n";
	} else 
	if (!$FieldName) {
		echo "Field <select name=FieldName onchange=this.form.submit()>\n  <option>select";
		$db->query("SHOW COLUMNS FROM $table");
		while ($db->next_record()) {
			echo "\n  <option>".$db->f(0);
		}
		echo "\n</select>\n";
	} else 
	if (!$LinkTable) {
		echo "Table <select name=LinkTable onchange=this.form.submit()>\n  <option>select";
		$db->query("SHOW TABLES");
		while ($db->next_record()) {
			echo "\n  <option>".$db->f(0);
		}
		echo "\n</select>\n";
	} else 
	if (!$LinkField) {
		echo "Key <select name=LinkField onchange=this.form.submit()>\n  <option>select";
		$db->query("SHOW INDEX FROM $LinkTable");
		while ($db->next_record()) {
			echo "\n  <option>".$db->f("Column_name");
		}
		echo "\n</select>\n";
	} else 
	if (!$LinkDesc) {
		echo "Label <select name=LinkDesc onchange=this.form.submit()>\n  <option>select";
		$db->query("SHOW COLUMNS FROM $LinkTable");
		while ($db->next_record()) {
			echo "\n  <option>".$db->f(0);
		}
		echo "\n</select>\n";
	} else 
	if (empty($DefaultValue)) {
		echo "Default Value <select name=DefaultValue onchange=this.form.submit()>\n  <option>select";
		echo "\n  <option value='0'>No Default";
		$db->query("Select $LinkField, $LinkDesc FROM $LinkTable");
		while ($db->next_record()) {
			echo "\n  <option value='".$db->f(0)."'>".$db->f(1);
		}
		echo "\n</select>\n";
	} else {
		echo "<br>Extra Key<input name=NullValue size=10>eg 0<br>";
		echo "<br>Extra Label<input name=NullDesc size=10>eg All, Any, None<br>";
		echo "<BR>WHERE<br><textarea name=LinkCond rows=4 cols=60></textarea><br>";
		if (!$DefaultValue) {
			echo "<br>Use select... and display the following when nothing selected<br>";
			echo "<input name=LinkErrMsg size=60>";
		}
		echo "<input type=hidden name=form_name value=LinkedTablesform>";
		echo "<input name=submit type=submit value=Save>";
	} 
	echo "</form>\n";
	break;
    default:
	$cmd="Query";
	$t = new LinkedTablesTable;
	$t->heading = 'on';
	$t->add_extra = 'on';
	$db = new $_ENV["DatabaseClass"];

        echo "&nbsp<a href=\"".$sess->self_url()
		.$sess->add_query(array("cmd"=>"Link"))."\">Add Linked Tables</a>&nbsp;\n";
        echo "&nbsp<a href=\"".$sess->url("/index.php")."\">Home</a>&nbsp;\n";
	echo "<font class=bigTextBold>$cmd Linked Tables</font>\n";

	// These fields will be searchable and displayed in results.
	// Format is "RealFieldName"=>"Field Name Formatted For Display",
	$t->fields = array(
			"FormName",
			"FieldName",
			"LinkTable",
			"LinkField",
			"LinkDesc",
			"NullValue",
			"NullDesc",
			"LinkCondition",
			"LinkErrorMsg",
			"DefaultValue");
        $t->map_cols = array(
			"FormName"=>"Form Name",
			"FieldName"=>"Field Name",
			"LinkTable"=>"Link Table",
			"LinkField"=>"Link Field",
			"LinkDesc"=>"Link Desc",
			"NullValue"=>"Null Value",
			"NullDesc"=>"Null Desc",
			"LinkCondition"=>"Link Condition",
			"LinkErrorMsg"=>"Link Error Msg",
			"DefaultValue"=>"Default Value");

  // When we hit this page the first time,
  // there is no .
  if (!isset($q_LinkedTables)) {
    $q_LinkedTables = new LinkedTables_Sql_Query;     // We make one
    $q_LinkedTables->conditions = 1;     // ... with a single condition (at first)
    $q_LinkedTables->translate  = "on";  // ... column names are to be translated
    $q_LinkedTables->container  = "on";  // ... with a nice container table
    $q_LinkedTables->variable   = "on";  // ... # of conditions is variable
    $q_LinkedTables->lang       = "en";  // ... in English, please
    $q_LinkedTables->primary_key = "id";  // let Query engine know primary key
    $q_LinkedTables->default_query = "`id`!='0'";  // let Query engine know primary key

    $sess->register("q_LinkedTables");   // and don't forget this!
  }

  if (isset($rowcount)) {
        $q_LinkedTables->start_row = $startingwith;
        $q_LinkedTables->row_count = $rowcount;
  }

  // When we hit that page a second time, the array named
  // by $base will be set and we must generate the $query.
  // Ah, and don\'t set $base to "q" when $q is your Sql_Query
  // object... :-)
  if (isset($x)) {
    $query = $q_LinkedTables->where("x", 1);
  }

  if ($submit=='Search') $query = $q_LinkedTables->search($t->map_cols);

  if (!$sortorder) $sortorder="id";
  if (!$query) { $query="`id`!='0' order by `id`"; }
  $db->query("SELECT COUNT(*) as total from `LinkedTables` where ".$query);
  $db->next_record();
  if ($db->f("total") < ($q_LinkedTables->start_row - $q_LinkedTables->row_count))
      { $q_LinkedTables->start_row = $db->f("total") - $q_LinkedTables->row_count; }

  if ($q_LinkedTables->start_row < 0) { $q_LinkedTables->start_row = 0; }

  $query .= " LIMIT ".$q_LinkedTables->start_row.",".$q_LinkedTables->row_count;

  // In any case we must display that form now. Note that the
  // "x" here and in the call to $q->where must match.
  // Tag everything as a CSS "query" class.
  printf($q_LinkedTables->form("x", $t->map_cols, "query"));
  printf("<hr>");

  // Do we have a valid query string?
  if ($query) {
    // Show that condition
    printf("Query Condition = %s<br>\n", $query);

    // Do that query
    $db->query("select * from `LinkedTables` where ". $query);

    // Dump the results (tagged as CSS class default)
    printf("Query Results = %s<br>\n", $db->num_rows());
    $t->show_result($db, "default");
  }
} // switch $cmd
page_close();
?>

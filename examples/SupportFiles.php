<?php
include('phplib/prepend.php');

page_open(array("sess"=>"hotspot_Session","auth"=>"hotspot_Auth","perm"=>"hotspot_Perm"));

echo "<script language=JavaScript src=js/scripts.js></script>
<script language=JavaScript src=js/datefunc.js>
//Parts taken from ts_picker.js
//Script by Denis Gritcyuk: tspicker@yahoo.com
//Submitted to JavaScript Kit (http://javascriptkit.com)
//Visit http://javascriptkit.com for this script
</script> \n";

$db = new DB_hotspot;
$self = neatstr(substr($_SERVER["PHP_SELF"],1,-4));
echo "<h2>$self</h2>";

check_view_perms();

$f = new SupportFilesform;

if ($submit) {
  switch ($submit) {
   case "Copy": $id="";
   case "Save":
    if ($id) $submit = "Edit";
    else $submit = "Add";
   case "Add":
   case "Edit":
    if (isset($auth)) {
     check_edit_perms();
     if (!$f->validate($result)) {
        $cmd = $submit;
        echo "<font class=bigTextBold>$cmd Support Files</font>\n";
        $f->display();
        page_close();
        exit;
     }
     else
     {
        echo "Saving....";
        $f->save_values();
        echo "<b>Done!</b><br>\n";
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url()."\">";
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to SupportFiles.</a><br>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to SupportFiles.</a><br>\n";
        page_close();
        exit;

   case "Delete":
    if (isset($auth)) {
        check_edit_perms();
        echo "Deleting....";
        $f->save_values();
        echo "<b>Done!</b><br>\n";
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br>\n";
    }
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url()."\">";
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to SupportFiles.</a><br>\n";
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
	if ($cmd=="Copy") $id="";
    case "Edit":
	echo "<font class=bigTextBold>$cmd Support Files</font>\n";
	$f->display();
	break;
    default:
	$cmd="Query";
	$t = new SupportFilesTable;
	$t->heading = 'on';
	$t->add_extra = 'on';
	$db = new DB_hotspot;

        echo "&nbsp<a href=\"".$sess->self_url()
		.$sess->add_query(array("cmd"=>"Add"))."\">Add Support Files</a>&nbsp;\n";
        echo "&nbsp<a href=\"".$sess->url("/index.php")."\">Home</a>&nbsp;\n";
	echo "<font class=bigTextBold>$cmd Support Files</font>\n";

	// These fields will be searchable and displayed in results.
	// Format is "RealFieldName"=>"Field Name Formatted For Display",
	$t->fields = array(
			"TicketNo",
			"FileName");
        $t->map_cols = array(
			"TicketNo"=>"Ticket No",
			"FileName"=>"File Name");

  // When we hit this page the first time,
  // there is no .
  if (!isset($q_SupportFiles)) {
    $q_SupportFiles = new SupportFiles_Sql_Query;     // We make one
    $q_SupportFiles->conditions = 1;     // ... with a single condition (at first)
    $q_SupportFiles->translate  = "on";  // ... column names are to be translated
    $q_SupportFiles->container  = "on";  // ... with a nice container table
    $q_SupportFiles->variable   = "on";  // ... # of conditions is variable
    $q_SupportFiles->lang       = "en";  // ... in English, please
    $q_SupportFiles->primary_key = "id";  // let Query engine know primary key
    $q_SupportFiles->default_query = "`id`!='0'";  // let Query engine know primary key

    $sess->register("q_SupportFiles");   // and don't forget this!
  }

  if ($rowcount) {
        $q_SupportFiles->start_row = $startingwith;
        $q_SupportFiles->row_count = $rowcount;
  }

  // When we hit that page a second time, the array named
  // by $base will be set and we must generate the $query.
  // Ah, and don\'t set $base to "q" when $q is your Sql_Query
  // object... :-)
  if (isset($x)) {
    $query = $q_SupportFiles->where("x", 1);
  }

  if ($submit=='Search') $query = $q_SupportFiles->search($t->map_cols);

  if (!$sortorder) $sortorder="id";
  if (!$query) { $query="`id`!='0' order by `id`"; }
  $db->query("SELECT COUNT(*) as total from `SupportFiles` where ".$query);
  $db->next_record();
  if ($db->f("total") < ($q_SupportFiles->start_row - $q_SupportFiles->row_count))
      { $q_SupportFiles->start_row = $db->f("total") - $q_SupportFiles->row_count; }

  if ($q_SupportFiles->start_row < 0) { $q_SupportFiles->start_row = 0; }

  $query .= " LIMIT ".$q_SupportFiles->start_row.",".$q_SupportFiles->row_count;

  // In any case we must display that form now. Note that the
  // "x" here and in the call to $q->where must match.
  // Tag everything as a CSS "query" class.
  printf($q_SupportFiles->form("x", $t->map_cols, "query"));
  printf("<hr>");

  // Do we have a valid query string?
  if ($query) {
    // Show that condition
    printf("Query Condition = %s<br>\n", $query);

    // Do that query
    $db->query("select * from `SupportFiles` where ". $query);

    // Dump the results (tagged as CSS class default)
    printf("Query Results = %s<br>\n", $db->num_rows());
    $t->show_result($db, "default");
  }
} // switch $cmd
page_close();
?>

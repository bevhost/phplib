<?php
include('phplib/prepend.php');
page_open(array("sess"=>"Hotspot_Session","auth"=>"Hotspot_Auth","perm"=>"Hotspot_Perm"));
include('postfix.inc');

echo "<script language=JavaScript src=currency.js></script>\n";
echo "<script language=JavaScript src=datefunc.js>
//Parts taken from ts_picker.js
//Script by Denis Gritcyuk: tspicker@yahoo.com
//Submitted to JavaScript Kit (http://javascriptkit.com)
//Visit http://javascriptkit.com for this script
</script> \n";

check_view_perms();

$f = new clearancesform;

if ($submit) {
  switch ($submit) {

   case "Save":
    if ($id) $submit = "Edit";
    else $submit = "Add";
   case "Add":
   case "Edit":
    if (isset($auth)) {
     check_edit_perms();
     if (!$f->validate($result)) {
        $cmd = $submit;
        echo "<font class=bigTextBold>$cmd clearances</font>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to clearances.</a><br>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to clearances.</a><br>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to clearances.</a><br>\n";
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

    case "Edit":
	echo "<font class=bigTextBold>$cmd clearances</font>\n";
	$f->display();
	break;
    default:
	$cmd="Query";
	$t = new clearancesTable;
	$t->heading = 'on';
	$t->add_extra = 'on';
	$db = new DB_policyd;

        echo "&nbsp<a href=\"".$sess->self_url()
		.$sess->add_query(array("cmd"=>"Add"))."\">Add clearances</a>&nbsp\n";
        echo "&nbsp<a href=\"".$sess->url("/index.php")."\">Home</a>&nbsp\n";
	echo "<font class=bigTextBold>$cmd clearances</font>\n";

	// These fields will be searchable and displayed in results.
	// Format is "RealFieldName"=>"Field Name Formatted For Display",
	$t->fields = array(
			"FullName",
			"Email",
			"Phone",
			"Warrant",
			"Address",
			"Domain",
			"When");
        $t->map_cols = array(
			"FullName"=>"Full Name",
			"Email"=>"Email",
			"Phone"=>"Phone",
			"Warrant"=>"Warrant",
			"Address"=>"Address",
			"Domain"=>"Domain",
			"When"=>"When");

  // When we hit this page the first time,
  // there is no .
  if (!isset($q_clearances)) {
    $q_clearances = new clearances_Sql_Query;     // We make one
    $q_clearances->conditions = 1;     // ... with a single condition (at first)
    $q_clearances->translate  = "on";  // ... column names are to be translated
    $q_clearances->container  = "on";  // ... with a nice container table
    $q_clearances->variable   = "on";  // ... # of conditions is variable
    $q_clearances->lang       = "en";  // ... in English, please
    $q_clearances->primary_key = "id";  // let Query engine know primary key

    $sess->register("q_clearances");   // and don't forget this!
  }

  if (!empty($rowcount)) {
        $q_clearances->start_row = $startingwith;
        $q_clearances->row_count = $rowcount;
  }

  // When we hit that page a second time, the array named
  // by $base will be set and we must generate the $query.
  // Ah, and don\'t set $base to "q" when $q is your Sql_Query
  // object... :-)
  if (!empty($x)) {
    $query = $q_clearances->where("x", 1);
  }

  if ($submit=='Search') $query = $q_whitelist->search($t->map_cols);

  if (!$sortorder) $sortorder="id";
  if (!$query) { $query="id!='0' order by id"; }
  $db->query("SELECT COUNT(*) as total from clearances where ".$query);
  $db->next_record();
  if ($db->f("total") < ($q_clearances->start_row - $q_clearances->row_count))
      { $q_clearances->start_row = $db->f("total") - $q_clearances->row_count; }

  if ($q_clearances->start_row < 0) { $q_clearances->start_row = 0; }

  $query .= " LIMIT ".$q_clearances->start_row.",".$q_clearances->row_count;

  // In any case we must display that form now. Note that the
  // "x" here and in the call to $q->where must match.
  // Tag everything as a CSS "query" class.
//  printf($q_clearances->form("x", $t->map_cols, "query"));
  printf("<hr>");

  // if (!$query) { $query="id!='0'"; }

  // Do we have a valid query string?
  if ($query) {
    // Show that condition
    printf("Query Condition = %s<br>\n", $query);

    // Do that query
    $db->query("select * from clearances where ". $query);

    // Dump the results (tagged as CSS class default)
    printf("Query Results = %s<br>\n", $db->num_rows());
    $t->show_result($db, "default");
  }
} // switch $cmd
page_close();
?>

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


$f = new spamtrapform;

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
        echo "<font class=bigTextBold>$cmd spamtrap</font>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to spamtrap.</a><br>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to spamtrap.</a><br>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to spamtrap.</a><br>\n";
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
	echo "<font class=bigTextBold>$cmd spamtrap</font>\n";
	$f->display();
	break;
    default:
	$cmd="Query";
	$t = new spamtrapTable;
	$t->heading = 'on';
	$t->add_extra = 'on';
	$t->add_install = 'spamtrapform';
	$db = new DB_policyd;

        echo "&nbsp<a href=\"".$sess->self_url()
		.$sess->add_query(array("cmd"=>"Add"))."\">Add spamtrap</a>&nbsp\n";
        echo "&nbsp<a href=\"".$sess->url("/index.php")."\">Home</a>&nbsp\n";
	echo "<font class=bigTextBold>$cmd spamtrap</font>\n";

	// These fields will be searchable and displayed in results.
	// Format is "RealFieldName"=>"Field Name Formatted For Display",
	$t->fields = array(
			"_rcpt",
			"_active",
			"Comment");
        $t->map_cols = array(
			"_rcpt"=>"rcpt",
			"_active"=>"active",
			"Comment"=>"Comment");

  // When we hit this page the first time,
  // there is no .
  if (!isset($q_spamtrap)) {
    $q_spamtrap = new spamtrap_Sql_Query;     // We make one
    $q_spamtrap->conditions = 1;     // ... with a single condition (at first)
    $q_spamtrap->translate  = "on";  // ... column names are to be translated
    $q_spamtrap->container  = "on";  // ... with a nice container table
    $q_spamtrap->variable   = "on";  // ... # of conditions is variable
    $q_spamtrap->lang       = "en";  // ... in English, please
    $q_spamtrap->primary_key = "id";  // let Query engine know primary key

    $sess->register("q_spamtrap");   // and don't forget this!
  }

  if (!empty($rowcount)) {
        $q_spamtrap->start_row = $startingwith;
        $q_spamtrap->row_count = $rowcount;
  }

  // When we hit that page a second time, the array named
  // by $base will be set and we must generate the $query.
  // Ah, and don\'t set $base to "q" when $q is your Sql_Query
  // object... :-)
  if (!empty($x)) {
    $query = $q_spamtrap->where("x", 1);
  }

  if ($submit=='Search') $query = $q_whitelist->search($t->map_cols);

  if (!$sortorder) $sortorder="id";
  if (!$query) { $query="id!='0' order by id"; }
  $db->query("SELECT COUNT(*) as total from spamtrap where ".$query);
  $db->next_record();
  if ($db->f("total") < ($q_spamtrap->start_row - $q_spamtrap->row_count))
      { $q_spamtrap->start_row = $db->f("total") - $q_spamtrap->row_count; }

  if ($q_spamtrap->start_row < 0) { $q_spamtrap->start_row = 0; }

  $query .= " LIMIT ".$q_spamtrap->start_row.",".$q_spamtrap->row_count;

  // In any case we must display that form now. Note that the
  // "x" here and in the call to $q->where must match.
  // Tag everything as a CSS "query" class.
//  printf($q_spamtrap->form("x", $t->map_cols, "query"));
  printf("<hr>");

  // if (!$query) { $query="id!='0'"; }

  // Do we have a valid query string?
  if ($query) {
    // Show that condition
    printf("Query Condition = %s<br>\n", $query);

    // Do that query
    $db->query("select * from spamtrap where ". $query);

    // Dump the results (tagged as CSS class default)
    printf("Query Results = %s<br>\n", $db->num_rows());
    $t->show_result($db, "default");
  }
} // switch $cmd
page_close();
?>

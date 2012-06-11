<?php
include('phplib/prepend.php');

page_open(array("sess"=>"Hotspot_Session","auth"=>"Hotspot_Auth","perm"=>"Hotspot_Perm"));

include('postfix.inc');

echo "<script type='text/javaScript' src='currency.js'></script>\n";
echo "<script type='text/javaScript' src='datefunc.js'>
//Parts taken from ts_picker.js
//Script by Denis Gritcyuk: tspicker@yahoo.com
//Submitted to JavaScript Kit (http://javascriptkit.com)
//Visit http://javascriptkit.com for this script
</script> \n";

check_view_perms();

$f = new blacklistform;

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
        echo "<font class=bigTextBold>$cmd blacklist</font>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to blacklist.</a><br>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to blacklist.</a><br>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to blacklist.</a><br>\n";
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
	echo "<span class=bigTextBold>$cmd blacklist</span>\n";
	$f->display();
    if ($cmd=='Back') {
        echo "<pre>";
        echo "\n<hr>\n";
        system ("host $_blacklist");
        echo "\n<hr>\n";
        system ("whois $_blacklist");
        echo "\n<hr>\n";
        system ("traceroute $_blacklist");
        echo "\n<hr>\n";



        echo "</pre>";
    }
	break;
    default:
	$cmd="Query";
	$t = new blacklistTable;
	$t->heading = 'on';
	$t->add_extra = 'on';
	$t->add_insert = 'blacklistform';  // use this form to display an insert record
	$t->edit = 'blacklistform';
	$db = new DB_policyd;

        echo "&nbsp;<a href=\"".$sess->self_url()
		.$sess->add_query(array("cmd"=>"Add"))."\">Add blacklist</a>&nbsp;\n";
        echo "&nbsp;<a href=\"".$sess->url("/index.php")."\">Home</a>&nbsp;\n";
	echo "<span class=bigTextBold>$cmd blacklist</span>\n";

	// These fields will be searchable and displayed in results.
	// Format is "RealFieldName"=>"Field Name Formatted For Display",
	$t->fields = array(
			"_blacklist",
			"_description",
			"_expire");
        $t->map_cols = array(
			"id"=>"id",
			"_blacklist"=>"blacklist",
			"_description"=>"description",
			"_expire"=>"expire");

  // When we hit this page the first time,
  // there is no .
  if (!isset($q_blacklist)) {
    $q_blacklist = new blacklist_Sql_Query;     // We make one
    $q_blacklist->conditions = 1;     // ... with a single condition (at first)
    $q_blacklist->translate  = "on";  // ... column names are to be translated
    $q_blacklist->container  = "on";  // ... with a nice container table
    $q_blacklist->variable   = "on";  // ... # of conditions is variable
    $q_blacklist->lang       = "en";  // ... in English, please
    $q_blacklist->primary_key = "id";  // let Query engine know primary key

    $sess->register("q_blacklist");   // and don't forget this!
  }

  if (!empty($rowcount)) {
        $q_blacklist->start_row = $startingwith;
        $q_blacklist->row_count = $rowcount;
  }

  // When we hit that page a second time, the array named
  // by $base will be set and we must generate the $query.
  // Ah, and don\'t set $base to "q" when $q is your Sql_Query
  // object... :-)
  if (!empty($x)) {
    $query = $q_blacklist->where("x", 1);
  }
 
  if ($submit=='Search') $query = $q_blacklist->search($t->map_cols);
 
  if (!$sortorder) $sortorder="id";
  if (!$query) { $query="id!='0' order by id"; }
  $db->query("SELECT COUNT(*) as total from blacklist where ".$query);
  $db->next_record();
  if ($db->f("total") < ($q_blacklist->start_row - $q_blacklist->row_count))
      { $q_blacklist->start_row = $db->f("total") - $q_blacklist->row_count; }

  if ($q_blacklist->start_row < 0) { $q_blacklist->start_row = 0; }

  $query .= " LIMIT ".$q_blacklist->start_row.",".$q_blacklist->row_count;

  // In any case we must display that form now. Note that the
  // "x" here and in the call to $q->where must match.
  // Tag everything as a CSS "query" class.
//  printf($q_blacklist->form("x", $t->map_cols, "query"));
  printf("<hr>");

  // if (!$query) { $query="id!='0'"; }

  // Do we have a valid query string?
  if ($query) {
    // Show that condition
    printf("Query Condition = %s<br>\n", $query);

    // Do that query
    $db->query("select * from blacklist where ". $query);

    // Dump the results (tagged as CSS class default)
    printf("Query Results = %s<br>\n", $db->num_rows());
    $t->show_result($db, "default");
  }
} // switch $cmd
page_close();
?>

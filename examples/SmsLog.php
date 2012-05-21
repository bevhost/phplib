<?php
include($DOCUMENT_ROOT.'/phplib/prepend.php');

page_open(array("sess"=>"whatsup_Session","auth"=>"whatsup_Auth","perm"=>"whatsup_Perm"));

echo "<script language=JavaScript src=currency.js></script>\n";
echo "<script language=JavaScript src=datefunc.js>
//Parts taken from ts_picker.js
//Script by Denis Gritcyuk: tspicker@yahoo.com
//Submitted to JavaScript Kit (http://javascriptkit.com)
//Visit http://javascriptkit.com for this script
</script>
<script language=JavaScript>
function DoCustomChecks(f) {
        return true;
}
</script> \n";


$f = new SmsLogform;
if ($submit=="Login now") $submit="";
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
        echo "<font class=bigTextBold>$cmd Sms Log</font>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to SmsLog.</a><br>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to SmsLog.</a><br>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to SmsLog.</a><br>\n";
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
	echo "<font class=bigTextBold>$cmd Sms Log</font>\n";
	$f->display();
	break;
    default:
	$cmd="Query";
	$t = new SmsLogTable;
	$t->heading = 'on';
	$t->add_extra = 'on';
	$db = new DB_whatsup;

        echo "&nbsp<a href=\"".$sess->self_url()
		.$sess->add_query(array("cmd"=>"Add"))."\">Add Sms Log</a>&nbsp\n";
        echo "&nbsp<a href=\"".$sess->url("/index.php")."\">Home</a>&nbsp\n";
	echo "<font class=bigTextBold>$cmd Sms Log</font>\n";

	// These fields will be searchable and displayed in results.
	// Format is "RealFieldName"=>"Field Name Formatted For Display",
	$t->fields = array(
			"UserName",
			"Result",
			"RecipientName",
			"RecipientNumber",
			"SenderNumber",
			"MsgType",
			"DateTime",
			"Tracking");
        $t->map_cols = array(
			"UserName"=>"User Name",
			"Result"=>"Result",
			"RecipientName"=>"Recipient Name",
			"RecipientNumber"=>"Recipient Number",
			"SenderNumber"=>"Sender Number",
			"MsgType"=>"Msg Type",
			"DateTime"=>"Date Time",
			"Tracking"=>"Tracking");

  // When we hit this page the first time,
  // there is no .
  if (!isset($q)) {
    $q = new SmsLog_Sql_Query;     // We make one
    $q->conditions = 1;     // ... with a single condition (at first)
    $q->translate  = "on";  // ... column names are to be translated
    $q->container  = "on";  // ... with a nice container table
    $q->variable   = "on";  // ... # of conditions is variable
    $q->lang       = "en";  // ... in English, please

    $sess->register("q");   // and don't forget this!
  }

  if (isset($rowcount)) {
        $q->start_row = $startingwith;
        $q->row_count = $rowcount;
  }

  // When we hit that page a second time, the array named
  // by $base will be set and we must generate the $query.
  // Ah, and don\'t set $base to "q" when $q is your Sql_Query
  // object... :-)
  if (isset($x)) {
    $query = $q->where("x", 1);
  }

  if (!$query) { $query="id!='0'"; }
  $db->query("SELECT COUNT(*) as total from SmsLog where ".$query);
  $db->next_record();
  if ($db->f("total") < ($q->start_row - $q->row_count))
      { $q->start_row = $db->f("total") - $q->row_count; }

  if ($q->start_row < 0) { $q->start_row = 0; }

  if (!$sortorder) $sortorder="id";
  $query .= " Order By ".$sortorder." LIMIT ".$q->start_row.",".$q->row_count;

  // In any case we must display that form now. Note that the
  // "x" here and in the call to $q->where must match.
  // Tag everything as a CSS "query" class.
  printf($q->form("x", $t->map_cols, "query"));
  printf("<hr>");

  // if (!$query) { $query="id!='0'"; }

  // Do we have a valid query string?
  if ($query) {
    // Show that condition
    printf("Query Condition = %s<br>\n", $query);

    // Do that query
    $db->query("select * from SmsLog where ". $query);

    // Dump the results (tagged as CSS class default)
    printf("Query Results = %s<br>\n", $db->num_rows());
    $t->show_result($db, "default");
  }
} // switch $cmd
page_close();
?>

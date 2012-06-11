<?php
include('phplib/prepend.php');
page_open(array("sess"=>"hotspot_Session","auth"=>"hotspot_Auth","perm"=>"hotspot_Perm"));
include('SupportEmail.php');

echo "<script language=JavaScript src=/js/currency.js></script>\n";
echo "<script language=JavaScript src=/js/datefunc.js>
//Parts taken from ts_picker.js
//Script by Denis Gritcyuk: tspicker@yahoo.com
//Submitted to JavaScript Kit (http://javascriptkit.com)
//Visit http://javascriptkit.com for this script
</script>
<script language=JavaScript>
function DoCustomChecks(f) {
if (f.elements[\"Details\"].value.length < 1) {
  alert(\"Work Done Details must not be blank.  Required field.\");
  f.elements[\"Details\"].focus();
  return(false);
}
if (f.elements[\"Duration\"].value < 1) {
  alert(\"Duration must not be zero.  Required field.\");
  f.elements[\"Duration\"].focus();
  return(false);
}
        return true;
}
</script> \n";

class my_SupportDetailsform extends SupportDetailsform {
	var $classname = "my_SupportDetailsform";
}

if ((!$TicketNo) && (!id)) {
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->url("/SupportTickets.php")."\">";
        page_close();
        exit;
}
$f = new my_SupportDetailsform;
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
        echo "<font class=bigTextBold>$cmd Support Details</font>\n";
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
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->url("/SupportTickets.php");
        echo $sess->add_query(array("cmd"=>"View","id"=>$TicketNo))."\">";
        echo "&nbsp<a href=\"".$sess->url("/SupportTickets.php");
        echo $sess->add_query(array("cmd"=>"View","id"=>$TicketNo))."\">Back to Ticket.</a><br>\n";page_close();
	SupportEmail($TicketNo,$OldStatus);
        exit;
     }
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br>\n";
    }
   case "View":
   case "Back":
        $QUERY_STRING="";
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->url("/SupportTickets.php");
        echo $sess->add_query(array("cmd"=>"View","id"=>$TicketNo))."\">";
        echo "&nbsp<a href=\"".$sess->url("/SupportTickets.php");
        echo $sess->add_query(array("cmd"=>"View","id"=>$TicketNo))."\">Back to Ticket.</a><br>\n";
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
        $QUERY_STRING="";
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->url("/SupportTickets.php");
        echo $sess->add_query(array("cmd"=>"View","id"=>$TicketNo))."\">";
        echo "&nbsp<a href=\"".$sess->url("/SupportTickets.php");
        echo $sess->add_query(array("cmd"=>"View","id"=>$TicketNo))."\">Back to Ticket.</a><br>\n";
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
        $db->query("select id from userinfo where UserName='".$auth->auth["uname"]."'");
        $db->next_record();
        $ContactID=$db->f(0);
    case "Edit":
	echo "<font class=bigTextBold>$cmd Support Details</font>\n";
	$f->display();
	break;
    default:
	$cmd="Query";
	$t = new SupportDetailsTable;
	$t->heading = 'on';
	$t->add_extra = 'on';
	$db = new DB_admin;

        echo "&nbsp<a href=\"".$sess->self_url()
		.$sess->add_query(array("cmd"=>"Add"))."\">Add Support Details</a>&nbsp\n";
	echo "&nbsp<a href=\"".$sess->url("/SupportTickets.php")."\">Tickets</a>&nbsp\n";
	echo "<font class=bigTextBold>$cmd Support Details</font>\n";

	// These fields will be searchable and displayed in results.
	// Format is "RealFieldName"=>"Field Name Formatted For Display",
	$t->fields = array(
			"TicketNo",
			"Completed",
			"Duration",
			"Charge");
        $t->map_cols = array(
			"TicketNo"=>"Ticket No",
			"Completed"=>"Time Completed",
			"Duration"=>"Duration",
			"Charge"=>"Charge");

  // When we hit this page the first time,
  // there is no .
  if (!isset($q)) {
    $q = new SupportDetails_Sql_Query;     // We make one
    $q->conditions = 1;     // ... with a single condition (at first)
    $q->translate  = "on";  // ... column names are to be translated
    $q->container  = "on";  // ... with a nice container table
    $q->variable   = "on";  // ... # of conditions is variable
    $q->lang       = "en";  // ... in English, please

    $sess->register("q");   // and don't forget this!
  }

  if ($rowcount) {
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
  $db->query("SELECT COUNT(*) as total from SupportDetails where ".$query);
  $db->next_record();
  if ($db->f("total") < ($q->start_row - $q->row_count))
      { $q->start_row = $db->f("total") - $q->row_count; }

  if ($q->start_row < 0) { $q->start_row = 0; }

  if (!$sortorder) $sortorder="id";
  $query .= " LIMIT ".$q->start_row.",".$q->row_count;

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
    $db->query("select * from SupportDetails where ". $query);

    // Dump the results (tagged as CSS class default)
    printf("Query Results = %s<br>\n", $db->num_rows());
    $t->show_result($db, "default");
  }
} // switch $cmd
page_close();
?>

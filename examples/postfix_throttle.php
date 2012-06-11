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

class MYthrottleform extends throttleform {
	var $classname="MYthrottleform";
}

$f = new MYthrottleform;
$db = new DB_policyd;

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
        echo "<font class=bigTextBold>$cmd throttle</font>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to throttle.</a><br>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to throttle.</a><br>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to throttle.</a><br>\n";
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
	echo "<font class=bigTextBold>$cmd throttle</font>\n";
	$f->display();

 

    if ($cmd=='Back') {

$t = new tripletTable;
$t->heading = 'on';
$t->fields = array(
        "_from",
        "_rcpt",
        "_host",
        "_datenew",
        "_datelast",
        "_count");
$t->map_cols = array(
        "_from"=>"from",
        "_rcpt"=>"rcpt",
        "_host"=>"host",
        "_datenew"=>"datenew",
        "_datelast"=>"datelast",
        "_count"=>"count");

$tr_host = substr($_from,0,strrpos($_from,"."));
$sql = "select * from triplet where _host like '$tr_host%' order by _count desc limit 0,20";
$db->query($sql);
echo "<br><big><i><b>$sql</b></i></big>\n";
$t->show_result($db);

	echo "<pre>";
	echo "\n<hr>\n";
	system ("host $_from");
	echo "\n<hr>\n";
	system ("whois $_from");
	echo "\n<hr>\n";
	system ("traceroute $_from");
	echo "\n<hr>\n";



	echo "</pre>";
    }
	break;
    default:
	$cmd="Query";
	$t = new throttleTable;
	$t->heading = 'on';
	$t->add_extra = 'on';
	$db = new DB_policyd;

        echo "&nbsp<a href=\"".$sess->self_url()
		.$sess->add_query(array("cmd"=>"Add"))."\">Add throttle</a>&nbsp\n";
        echo "&nbsp<a href=\"".$sess->url("/index.php")."\">Home</a>&nbsp\n";
	echo "<font class=bigTextBold>$cmd throttle</font>\n";

	// These fields will be searchable and displayed in results.
	// Format is "RealFieldName"=>"Field Name Formatted For Display",
	$t->fields = array(
			"_from",
			"_count_max",
			"_count_cur",
			"_date",
			"_quota_cur",
			"_quota_max",
			"_time_limit",
			"_mail_size",
			"_count_tot",
			"_rcpt_max",
			"_rcpt_cur",
			"_rcpt_tot",
			"_abuse_cur",
			"_abuse_tot",
			"_log_warn",
			"_log_panic",
			"_priority");
        $t->map_cols = array(
			"_from"=>"from",
			"_count_max"=>"count max",
			"_count_cur"=>"count cur",
			"_date"=>"date",
			"_quota_cur"=>"quota cur",
			"_quota_max"=>"quota max",
			"_time_limit"=>"time limit",
			"_mail_size"=>"mail size",
			"_count_tot"=>"count tot",
			"_rcpt_max"=>"rcpt max",
			"_rcpt_cur"=>"rcpt cur",
			"_rcpt_tot"=>"rcpt tot",
			"_abuse_cur"=>"abuse cur",
			"_abuse_tot"=>"abuse tot",
			"_log_warn"=>"log warn",
			"_log_panic"=>"log panic",
			"_priority"=>"priority");

  if (!$sortorder) {
        if (!isset($x)) $sortorder="id desc";
        else $sortorder = $xui[ord_1];
  }
  // When we hit this page the first time,
  // there is no .
  if (!isset($q_throttle)) {
    $q_throttle = new throttle_Sql_Query;     // We make one
    $q_throttle->conditions = 1;     // ... with a single condition (at first)
    $q_throttle->translate  = "on";  // ... column names are to be translated
    $q_throttle->container  = "on";  // ... with a nice container table
    $q_throttle->variable   = "on";  // ... # of conditions is variable
    $q_throttle->lang       = "en";  // ... in English, please
    $q_throttle->primary_key = "id";  // let Query engine know primary key

    $sess->register("q_throttle");   // and don't forget this!
  }

  if (!empty($rowcount)) {
        $q_throttle->start_row = $startingwith;
        $q_throttle->row_count = $rowcount;
  }

  // When we hit that page a second time, the array named
  // by $base will be set and we must generate the $query.
  // Ah, and don\'t set $base to "q" when $q is your Sql_Query
  // object... :-)
  if (!empty($x)) {
    $query = $q_throttle->where("x", 1);
  }

  if ($submit=='Search') $query = $q_whitelist->search($t->map_cols);

  if (!$query) { $query="id!='0' order by id"; }
  $db->query("SELECT COUNT(*) as total from throttle where ".$query);
  $db->next_record();
  if ($db->f("total") < ($q_throttle->start_row - $q_throttle->row_count))
      { $q_throttle->start_row = $db->f("total") - $q_throttle->row_count; }

  if ($q_throttle->start_row < 0) { $q_throttle->start_row = 0; }

  $query .= " LIMIT ".$q_throttle->start_row.",".$q_throttle->row_count;

  // In any case we must display that form now. Note that the
  // "x" here and in the call to $q->where must match.
  // Tag everything as a CSS "query" class.
//  printf($q_throttle->form("x", $t->map_cols, "query"));
  printf("<hr>");

  // if (!$query) { $query="id!='0'"; }

  // Do we have a valid query string?
  if ($query) {
    // Show that condition
    printf("Query Condition = %s<br>\n", $query);

    // Do that query
    $db->query("select * from throttle where ". $query);

    // Dump the results (tagged as CSS class default)
    printf("Query Results = %s<br>\n", $db->num_rows());
    $t->show_result($db, "default");
  }
} // switch $cmd
page_close();
?>

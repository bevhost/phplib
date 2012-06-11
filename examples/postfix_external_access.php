<?php
include('phplib/prepend.php');

page_open(array("sess"=>"hotspot_Session","auth"=>"hotspot_Auth","perm"=>"hotspot_Perm"));

echo "<script language=JavaScript src=/js/currency.js></script>\n";
echo "<script language=JavaScript src=/js/datefunc.js>
//Parts taken from ts_picker.js
//Script by Denis Gritcyuk: tspicker@yahoo.com
//Submitted to JavaScript Kit (http://javascriptkit.com)
//Visit http://javascriptkit.com for this script
</script>
<script language=JavaScript>
function DoCustomChecks(f) {
	if (f.bulk.value) {
		if (!f.access.value) f.access.value=' ';
		if (!f.address.value) f.address.value=' ';
	}
	if (!f.access.value) {
		if (f.acc.selectedIndex) {
			f.access.value=f.acc.options[f.acc.selectedIndex].value;
		} else {
			alert('Please select or enter Access');
			f.access.focus();
			return false;
		}
	}
        return true;
}
</script> \n";

include ("postfix.inc");

class my_postfix_external_accessform extends postfix_external_accessform {
	var $classname="my_postfix_external_accessform";
}

$f = new my_postfix_external_accessform;

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
        echo "<font class=bigTextBold>$cmd postfix _external _access</font>\n";
        $f->display();
        page_close();
        exit;
     }
     else
     {
        echo "Saving....";
	$modified = date("Y-m-d H:m:s");
        if ($bulk) {
		if ($access=' ') $access = $acc;
                $lines = explode("\n",$bulk);
                foreach ($lines as $line) {
                        unset($id);
                        $words = split("[\n\r\t ]+", $line);
                        $address = $words[0];
                        $acc = trim(substr($line,strlen($address)));
			if ($acc) $access=$acc;
                        $f->save_values();
                        $count++;
                }
                echo "$count records...";
        } else {
                $f->save_values();
        }
        echo "<b>Done!</b><br>\n";
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url()."\">";
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to postfix_external_access.</a><br>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to postfix_external_access.</a><br>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to postfix_external_access.</a><br>\n";
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
	echo "<font class=bigTextBold>$cmd postfix _external _access</font>\n";
	$f->display();
	break;
    default:
	$cmd="Query";
	$t = new postfix_external_accessTable;
	$t->heading = 'on';
	$t->add_extra = 'on';
	$t->add_insert = 'my_postfix_external_accessform';
	$t->edit = 'my_postfix_external_accessform';
	$db = new DB_postfix;

        //echo "&nbsp<a href=\"".$sess->self_url()
	//	.$sess->add_query(array("cmd"=>"Add"))."\">Add postfix _external _access</a>&nbsp\n";
        //echo "&nbsp<a href=\"".$sess->url("/index.php")."\">Home</a>&nbsp\n";
	echo "<br><font class=bigTextBold>External Access Controls by IP Address or DNS PTR</font>\n";

	// These fields will be searchable and displayed in results.
	// Format is "RealFieldName"=>"Field Name Formatted For Display",
	$t->fields = array(
			"address",
			"access",
			"notes");
        $t->map_cols = array(
			"address"=>"address",
			"access"=>"access",
			"modified"=>"modified",
			"notes"=>"notes");

  // When we hit this page the first time,
  // there is no .
  if (!isset($q)) {
    $q = new postfix_external_access_Sql_Query;     // We make one
    $q->conditions = 1;     // ... with a single condition (at first)
    $q->translate  = "on";  // ... column names are to be translated
    $q->container  = "on";  // ... with a nice container table
    $q->variable   = "on";  // ... # of conditions is variable
    $q->lang       = "en";  // ... in English, please
    $q->sortorder  = "inet_aton(address),reverse(address)";

    $sess->register("q");   // and don't forget this!
  }

  if (!empty($rowcount)) {
        $q->start_row = $startingwith;
        $q->row_count = $rowcount;
  }

  // When we hit that page a second time, the array named
  // by $base will be set and we must generate the $query.
  // Ah, and don\'t set $base to "q" when $q is your Sql_Query
  // object... :-)
  if (!empty($x)) {
    $query = $q->where("x", 1);
  }

  if (!$query) { $query="id!='0'"; }
  $db->query("SELECT COUNT(*) as total from postfix_external_access where ".$query);
  $db->next_record();
  if ($db->f("total") < ($q->start_row - $q->row_count))
      { $q->start_row = $db->f("total") - $q->row_count; }

  if ($q->start_row < 0) { $q->start_row = 0; }

  $sortorder = str_replace("address","INET_ATON(address),address",$sortorder);
  if (!$sortorder) $sortorder="inet_aton(address),reverse(address)";
  if (!strpos("order by",$query)) $query .= " order by $sortorder";

  $query .= " LIMIT ".$q->start_row.",".$q->row_count;


  $query = "id!='0' order by inet_aton(address),reverse(address)";

  // In any case we must display that form now. Note that the
  // "x" here and in the call to $q->where must match.
  // Tag everything as a CSS "query" class.
//  printf($q->form("x", $t->map_cols, "query"));
//  printf("<hr>");

  // if (!$query) { $query="id!='0'"; }

  // Do we have a valid query string?
  if ($query) {
    // Show that condition
  //  printf("Query Condition = %s<br>\n", $query);

    // Do that query
    $db->query("select * from postfix_external_access where ". $query);

    // Dump the results (tagged as CSS class default)
    //printf("Query Results = %s<br>\n", $db->num_rows());
    $t->show_result($db, "default");
  }
} // switch $cmd
page_close();
?>

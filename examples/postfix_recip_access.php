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

class my_postfix_recip_accessform extends postfix_recip_accessform {
	var $classname="my_postfix_recip_accessform";
}

class my_postfix_recip_accessTable extends Table {
  var $classname = "my_postfix_recip_accessTable";

  function table_row_add_extra($row, $row_key, $data, $class="") {
        global $sess, $auth, $perm;

        echo "<td><a href=\"".$sess->self_url().
                $sess->add_query(array("cmd"=>"View","id"=>$data["id"]))."\">view</a> ";
        echo "<a href=\"".$sess->self_url().
                $sess->add_query(array("cmd"=>"Edit","id"=>$data["id"]))."\">edit</a> ";
        echo "<a href=\"".$sess->self_url().
                $sess->add_query(array("cmd"=>"Delete","id"=>$data["id"]))."\">delete</a></td>";
  }
}

$f = new my_postfix_recip_accessform;

if ($submit) {
  switch ($submit) {

   case "Save":
    if ($id) $submit = "Edit";
    else $submit = "Add";
   case "Add":
   case "Edit":
    if ((!$domain) or (!strpos($address,$domain))) {
      if (!$perm->have_perm("admin")) {
	echo "Error!";
	page_close();
	exit;
      }
    }
    if (isset($auth)) {
     if (!$f->validate($result)) {
        $cmd = $submit;
        echo "<font class=bigTextBold>$cmd postfix _recip _access</font>\n";
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
                        $acc = addslashes(trim(substr($line,strlen($address))));
			if ($acc) $access=$acc;
                        $f->save_values();
                        $count++;
                }
                echo "$count records...";
        } else {
                $f->save_values();
        }
        echo "<b>Done!</b><br>\n";
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url();
        echo $sess->add_query(array("domain"=>$domain))."\">";
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to postfix_recip_access.</a><br>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to postfix_recip_access.</a><br>\n";
        page_close();
        exit;

   case "Delete":
    if (!$perm->have_perm("admin")) {
      if ((!$domain) or (!strpos($address,$domain))) {
	echo "Error!";
	page_close();
	exit;
      }
    }
    if (isset($auth)) {
        echo "Deleting....";
        $f->save_values();
        echo "<b>Done!</b><br>\n";
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br>\n";
    }
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url()."\">";
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to postfix_recip_access.</a><br>\n";
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
	echo "<font class=bigTextBold>$cmd postfix _recip _access</font>\n";
	$f->display();
	break;
    default:
	$cmd="Query";
	$t = new postfix_recip_accessTable;
	$t->heading = 'on';
	$t->add_extra = 'on';
	$t->add_insert = 'postfix_recip_accessform';
	$t->edit = 'postfix_recip_accessform';
	$db = new DB_postfix;

        //echo "<br><a href=\"".$sess->self_url()
//		.$sess->add_query(array("cmd"=>"Add"))."\">Add postfix _recip _access</a>&nbsp\n";
  //      echo "&nbsp<a href=\"".$sess->url("/index.php")."\">Home</a>&nbsp\n";
	echo "<br><font class=bigTextBold>Access Controls by Recipient (To/cc) Email Address</font>\n";

	// These fields will be searchable and displayed in results.
	// Format is "RealFieldName"=>"Field Name Formatted For Display",
	$t->fields = array(
			"address",
			"access");
        $t->map_cols = array(
			"address"=>"address",
			"access"=>"access",
                        "modified"=>"modified",
                        "notes"=>"notes");

  // When we hit this page the first time,
  // there is no .
  if (!isset($q)) {
    $q = new postfix_recip_access_Sql_Query;     // We make one
    $q->conditions = 1;     // ... with a single condition (at first)
    $q->translate  = "on";  // ... column names are to be translated
    $q->container  = "on";  // ... with a nice container table
    $q->variable   = "on";  // ... # of conditions is variable
    $q->lang       = "en";  // ... in English, please

    $sess->register("q");   // and don't forget this!
  }

  if (!empty($rowcount)) {
        $q->start_row = $startingwith;
        $q->row_count = $rowcount;
  }

  get_request_values("x,domain");

  // When we hit that page a second time, the array named
  // by $base will be set and we must generate the $query.
  // Ah, and don\'t set $base to "q" when $q is your Sql_Query
  // object... :-)
  if (!empty($x)) {
    $query = $q->where("x", 1);
  }

  if ($submit=='Search') $query = $q->search($t->map_cols);

  if (!$query) { $query="id!='0'"; }

if (($domain) or (!$perm->have_perm("admin"))) {
	$query .= " and address like '%".$domain."'";
}

  $db->query("SELECT COUNT(*) as total from postfix_recip_access where ".$query);
  $db->next_record();
  if ($db->f("total") < ($q->start_row - $q->row_count))
      { $q->start_row = $db->f("total") - $q->row_count; }

  if ($q->start_row < 0) { $q->start_row = 0; }

  if (!$sortorder) $sortorder="id";
  $query .= " LIMIT ".$q->start_row.",".$q->row_count;

  // In any case we must display that form now. Note that the
  // "x" here and in the call to $q->where must match.
  // Tag everything as a CSS "query" class.
if ($perm->have_perm("admin")) {
//  printf($q->form("x", $t->map_cols, "query"));
  //printf("<hr>");
}

  // if (!$query) { $query="id!='0'"; }

  // Do we have a valid query string?
  if ($query) {
    // Show that condition
 //   printf("Query Condition = %s<br>\n", $query);

    // Do that query
    $db->query("select * from postfix_recip_access where ". $query);

    // Dump the results (tagged as CSS class default)
    //printf("Query Results = %s<br>\n", $db->num_rows());
    $t->show_result($db, "default");
  }
} // switch $cmd
page_close();
?>

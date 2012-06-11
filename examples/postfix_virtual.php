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
                if (f.email.value=='') f.email.value=' ';
		return true;
        }
	m = f.mailbox.value;
	e = f.domain;
	if (e.selectedIndex==0) { 
		alert('Please select domain'); 
		return false; 
	}
	d = e.options[e.selectedIndex].value;
	if (m=='') {
		alert('Please enter a value for mailbox part of the email address.');
		return false;
	} else {
		f.email.value = m + '@' + d;
	}
        return true;
}
</script> \n";

include ("postfix.inc");

class my_postfix_virtualform extends postfix_virtualform {
	var $classname="my_postfix_virtualform";
}
$f = new my_postfix_virtualform;

if ($submit) {
  switch ($submit) {

   case "Save":
    if ($id) $submit = "Edit";
    else $submit = "Add";
   case "Add":
   case "Edit":
    if (isset($auth)) {
     if (!$f->validate()) {
        $cmd = $submit;
        echo "<font class=bigTextBold>$cmd postfix _virtual</font>\n";
        $f->display();
        page_close();
        exit;
     }
     else
     {
        echo "Saving....";
	$modified = date("Y-m-d H:m:s");
	get_request_values("bulk,email,destination");
        if ($bulk) {
                $lines = explode("\n",$bulk);
                foreach ($lines as $line) {
                        unset($id);
                        $words = split("[\n\r\t ]+", $line);
                        $email = $words[0];
                        $destination = trim(substr($line,strlen($email)));
                        $f->save_values();
                        $count++;
                }
                echo "$count records...";
        } else {
                $f->save_values();
        }
	$pos = strpos($email,'@');
	$domain = substr($email,$pos+1);
        echo "<b>Done!</b><br>\n";
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url();
        echo $sess->add_query(array("domain"=>$domain))."\">";
        echo "&nbsp<a href=\"".$sess->self_url();
        echo $sess->add_query(array("domain"=>$domain));
	echo "\">Back to postfix_virtual.</a><br>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to postfix_virtual.</a><br>\n";
        page_close();
        exit;

   case "Delete":
    if (isset($auth)) {
        echo "Deleting....";
        $f->save_values();
	$pos = strpos($email,'@');
	$domain = substr($email,$pos+1);
        echo "<b>Done!</b><br>\n";
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br>\n";
    }
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url();
        echo $sess->add_query(array("domain"=>$domain))."\">";
        echo "&nbsp<a href=\"".$sess->self_url();
        echo $sess->add_query(array("domain"=>$domain));
	echo "\">Back to postfix_virtual.</a><br>\n";
        page_close();
        exit;
  }
} else {
    if ($id) {
	$f->find_values($id);
    }
}
switch ($cmd) {
    case "Delete":
	echo "<h2>Deleting $domain</h2>";
	$db2->query("select * from postfix_mailbox where domain='".$domain."'");
	while ($db2->next_record()) echo $db2->f("username").", ".$db2->f("email")."<br>\n";
    case "View":
	$f->freeze();
    case "Add":

    case "Edit":
	echo "<font class=bigTextBold>$cmd postfix _virtual</font>\n";
	$f->display();
	break;
    default:
	$cmd="Query";

class my_postfix_virtualTable extends Table {
  var $classname = "postfix_virtualTable";

  function table_row_add_extra($row, $row_key, $data, $class="") {
        global $sess, $auth, $perm;
        
        echo "<td><a href=\"".$sess->self_url().
                $sess->add_query(array("cmd"=>"View","id"=>$data["id"]))."\">view</a></td>";
        echo "<td><a href=\"".$sess->self_url().
                $sess->add_query(array("cmd"=>"Edit","id"=>$data["id"]))."\">edit</a></td>";
        echo "<td><a href=\"".$sess->self_url().
                $sess->add_query(array("cmd"=>"Delete","id"=>$data["id"]))."\">delete</a></td>";
  }
}
	$t = new postfix_virtualTable;
	$t->heading = 'on';
	$t->add_extra = 'on';
	$t->add_insert = 'my_postfix_virtualform';
	$t->edit = 'my_postfix_virtualform';
	$db = new DB_postfix;

        //echo "<br><a href=\"".$sess->self_url()
	//	.$sess->add_query(array("cmd"=>"Add"))."\">Add postfix _virtual</a>&nbsp\n";
	echo "<br><font class=bigTextBold>User Alias - email addresses that are forwarded to new destination(s)</font>\n";

	// These fields will be searchable and displayed in results.
	// Format is "RealFieldName"=>"Field Name Formatted For Display",
	$t->fields = array(
			"email",
			"destination"
			);
        $t->map_cols = array(
			"email"=>"email",
			"destination"=>"destination",
                        "modified"=>"modified",
                        "notes"=>"notes");

  // When we hit this page the first time,
  // there is no .
  if (!isset($q)) {
    $q = new postfix_virtual_Sql_Query;     // We make one
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

  get_request_values("x,domain");

  // When we hit that page a second time, the array named
  // by $base will be set and we must generate the $query.
  // Ah, and don\'t set $base to "q" when $q is your Sql_Query
  // object... :-)
  if ($x) {
    $query = $q->where("x", 1);
  }

  if ($submit=='Search') $query = $q->search($t->map_cols);

  if (empty($query)) { $query="email like '%".$domain."'"; }
  $db->query("SELECT COUNT(*) as total from postfix_virtual where ".$query);
  $db->next_record();
  if ($db->f("total") < ($q->start_row - $q->row_count))
      { $q->start_row = $db->f("total") - $q->row_count; }

  if (!$q->start_row) { $q->start_row = 0; }
  if ($q->start_row < 2) { $q->start_row = 0; }
  if (!$q->row_count) { $q->row_count = 50; }

  if (!$sortorder) $sortorder="id";
#  $query .= " LIMIT ".$q->start_row.",".$q->row_count;

  // In any case we must display that form now. Note that the
  // "x" here and in the call to $q->where must match.
  // Tag everything as a CSS "query" class.
if ($perm->have_perm("admin")) {
  if (!$db->f("total")) printf($q->form("x", $t->map_cols, "query"));
  printf("<hr>");
}

  // Do we have a valid query string?
  if ($query) {
    // Show that condition
    printf("Query Condition = %s<br>\n", $query);

    // Do that query
    $db->query("select * from postfix_virtual where ". $query);

    // Dump the results (tagged as CSS class default)
    printf("Query Results = %s<br>\n", $db->num_rows());
    $t->show_result($db, "default");
  }
} // switch $cmd
page_close();
?>

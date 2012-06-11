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
                if (f.domain.value=='') f.domain.value=' ';
                return true;
        }
        return true;
}
</script> \n";

include ("postfix.inc");

class my_postfix_virtual_domainsform extends postfix_virtual_domainsform {
	var $classname="my_postfix_virtual_domainsform";
}	

$f = new my_postfix_virtual_domainsform;

class my_postfix_virtual_domainsTable extends Table {
  var $classname = "postfix_virtual_domainsTable";
                    
  function table_row_add_extra($row, $row_key, $data, $class="") {
        global $sess, $auth, $perm, $Path;
        $db = new DB_postfix; 
                    
        echo "<td class='btable'>";
                    
        echo " <a href=\"".$sess->url('postfix_mailbox.php').
                $sess->add_query(array("domain"=>$data["domain"]))."\">mailboxes";
	$db->query("select count(*) from postfix_mailbox where email like '%@".$data["domain"]."'");
	if ($db->next_record()) echo "(".$db->f(0).")";
	echo "</a>";

        echo " <a href=\"".$sess->url('postfix_virtual.php').
                $sess->add_query(array("domain"=>$data["domain"]))."\">aliases";
	$db->query("select count(*) from postfix_virtual where email like '%@".$data["domain"]."'");
	if ($db->next_record()) echo "(".$db->f(0).")";
	echo "</a>";
                    
  	if ($perm) {      
  	  if ($perm->have_perm("admin")) {
  	      echo " <a href=\"".$sess->url('postfix_virtual_domains.php').
  	              $sess->add_query(array("cmd"=>"Delete","id"=>$data["id"]))."\">delete</a>";
  	  }       
  	}
        if ($this->edit) {
                echo "<input type='submit' value='Save' name='submit' class='ipeh'> ";
                echo "<input type='hidden' value='".$data["id"]."' name='id'> ";
        }
        echo "</td>";
  }     
}

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
        echo "<font class=bigTextBold>$cmd postfix _virtual _domains</font>\n";
        $f->display();
        page_close();
        exit;
     }
     else
     {
        echo "Saving....";
        $modified = date("Y-m-d H:m:s");
        if ($bulk) {
                $lines = explode("\n",$bulk);
                foreach ($lines as $line) {
                        unset($id);
                        $words = split("[\n\r\t ,]+", $line);
                        $domain = $words[0];
                        $f->save_values();
                        $count++;
                }
                echo "$count records...";
        } else {
                $f->save_values();
        }
        $f->save_values();
        echo "<b>Done!</b><br>\n";
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url()."\">";
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to postfix_virtual_domains.</a><br>\n";
	$command = 'mkdir /home/vmail/'.$domain;
	system($command);
        system('chown virtual '.$homedir);
        system('chmod 775 '.$homedir);
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to postfix_virtual_domains.</a><br>\n";
        page_close();
        exit;

   case "Delete":
    if (isset($auth)) {
        echo "Deleting....";
        $f->save_values();
        if ($domain) $db2->query("delete from postfix_mailbox where domain='".$domain."'");
        if ($domain) $db2->query("delete from postfix_virtual where email like '%".$domain."'");
        echo "<b>Done!</b><br>\n";
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br>\n";
    }
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url()."\">";
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to postfix_virtual_domains.</a><br>\n";
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
        $db2->query("select * from postfix_virtual where email like '%".$domain."'");
        while ($db2->next_record()) echo $db2->f("email")." => ".$db2->f("destination")."<br>\n";
        $db2->query("select * from postfix_mailbox where domain='".$domain."'");
        while ($db2->next_record()) echo $db2->f("username").", ".$db2->f("email")."<br>\n";
    case "View":
	$f->freeze();
    case "Add":

    case "Edit":
	echo "<font class=bigTextBold>$cmd postfix _virtual _domains</font>\n";
	$f->display();
	break;
    default:
	$cmd="Query";
	$t = new my_postfix_virtual_domainsTable;
	$t->heading = 'on';
	$t->add_extra = 'on';
	$t->add_insert = 'my_postfix_virtual_domainsform';
	$t->edit = 'my_postfix_virtual_domainsform';
	$db = new DB_postfix;

        //echo "&nbsp<a href=\"".$sess->self_url()
	//	.$sess->add_query(array("cmd"=>"Add"))."\">Add postfix _virtual _domains</a>&nbsp\n";
        //echo "&nbsp<a href=\"".$sess->url("/index.php")."\">Home</a>&nbsp\n";
	echo "<br><font class=bigTextBold>Local Mailbox Domains</font>\n";

	// These fields will be searchable and displayed in results.
	// Format is "RealFieldName"=>"Field Name Formatted For Display",
	$t->fields = array(
			"domain",
			"active",
			"notes");
        $t->map_cols = array(
			"domain"=>"domain",
                        "modified"=>"modified",
                        "notes"=>"notes");

  // When we hit this page the first time,
  // there is no .
  if (!isset($q)) {
    $q = new postfix_virtual_domains_Sql_Query;     // We make one
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

  // When we hit that page a second time, the array named
  // by $base will be set and we must generate the $query.
  // Ah, and don\'t set $base to "q" when $q is your Sql_Query
  // object... :-)
  if (isset($x)) {
    $query = $q->where("x", 1);
  }

  if ($submit=='Search') $query = $q_whitelist->search($t->map_cols);

  if (!$query) { $query="id!='0'"; }
  $db->query("SELECT COUNT(*) as total from postfix_virtual_domains where ".$query);
  $db->next_record();
  if ($db->f("total") < ($q->start_row - $q->row_count))
      { $q->start_row = $db->f("total") - $q->row_count; }

  if ($q->start_row < 0) { $q->start_row = 0; }

  if (!$sortorder) $sortorder="domain";
  $query .= " LIMIT ".$q->start_row.",".$q->row_count;


  $query = "id!='0' order by domain";

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
    $db->query("select * from postfix_virtual_domains where ". $query);

    // Dump the results (tagged as CSS class default)
    //printf("Query Results = %s<br>\n", $db->num_rows());
    $t->show_result($db, "default");
  }
} // switch $cmd
page_close();
?>

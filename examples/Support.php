<?php
include('phplib/prepend.php');
include('phplib/SupportTickets.inc');

page_open(array("sess"=>"hotspot_Session","auth"=>"hotspot_Auth","perm"=>"hotspot_Perm"));

include('SupportEmail.php');

echo "<h1>Support Tickets</h1>";

/*
$db->query("Select Location, `Status`, Department from SupportTickets WHERE `Created` between '2010-05-01' and '2010-05-31' and `Status`<>'Software'");
while ($db->next_record()) {
	extract($db->Record);
	$total[$Location]++;
	$GLOBALS[$Location][$Status]++;
	$GLOBALS[$Location][$Department]++;
}
$Open=0;
echo "<table border=1><tr><th>Location</th><th>Support</th><th>Accounts</th><th>Other</th></tr>\n";
foreach($total as $loc=>$count) {
	$Other  = $GLOBALS[$loc]['Sales'] + $GLOBALS[$loc]['Development'];
	echo "<tr><td>$loc</td><td>$count</td><td>".$GLOBALS[$loc]['Support']."</td><td>".$GLOBALS[$loc]['Billing']."</td></tr>\n";
	$Open += $GLOBALS[$loc]['Open'];
}
echo "</table>\n";
echo $Open;
page_close();
exit;
*/ 


function array_first_chunk($input,$narrow_chunk_size,$wide_chunk_size) {
	$chunk_size = empty($GLOBALS["widemode"]) ? $narrow_chunk_size : $wide_chunk_size;  //get appropriate chunk size for screen width.
	if (count($input)>$chunk_size) {
		$chunks = array_chunk($input,$chunk_size);
		return $chunks[0];
	} else return $input;
}


check_view_perms();

$f = new SupportTicketsform;

if ($submit) {
  switch ($submit) {
   case "Copy": $id="";
   case "Save":
    if ($id) $submit = "Edit";
    else $submit = "Add";
   case "Add":
   case "Edit":
    if (isset($auth)) {
     check_edit_perms();
     if (!$f->validate()) {
        $cmd = $submit;
        echo "<font class='bigTextBold'>$cmd Support Tickets</font>\n";
        $f->display();
        page_close();
        exit;
     }
     else
     {
        echo "Saving....";
        $f->save_values();
        echo "<b>Done!</b><br />\n";
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url()."\">";
        echo "&nbsp;<a href=\"".$sess->self_url()."\">Back to SupportTickets.</a><br />\n";
        page_close();
        exit;
     }
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br />\n";
    }
   case "View":
   case "Back":
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"0; URL=".$sess->self_url()."\">";
        echo "&nbsp;<a href=\"".$sess->self_url()."\">Back to SupportTickets.</a><br />\n";
        page_close();
        exit;
   case "Delete":
    if (isset($auth)) {
        check_edit_perms();
        echo "Deleting....";
        $f->save_values();
        echo "<b>Done!</b><br />\n";
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br />\n";
    }
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url()."\">";
        echo "&nbsp;<a href=\"".$sess->self_url()."\">Back to SupportTickets.</a><br />\n";
        page_close();
        exit;
  }
} else {
    if ($id) {
	$f->find_values($id);
    }
}

$f->javascript();


switch ($cmd) {
    case "View":
    case "Delete":
	$f->freeze();
    case "Add":

    case "Copy":
	if ($cmd=="Copy") $id="";
    case "Edit":
	echo "<font class='bigTextBold'>$cmd Support Tickets</font>\n";
	$f->display();
        $f->showChildRecords();
	break;
    default:
	$cmd="Query";
	$t = new SupportTicketsTable;
	$t->heading = 'on';
	$t->add_extra = 'on';   /* or set to base url of php file to link to, defaults to PHP_SELF */
	$t->add_total = 'on';   /* add a grand total row to the bottom of the table on the numberic columns */
	$t->add_insert = 'SupportTicketsform';  /* Add a blank row ontop of table allowing insert or search */
	$t->add_insert_buttons = 'Search';   /* Control which buttons appear on the add_insert row eg: Add,Search */
	/* See below - EditMode can also be turned on/off by user if section below uncommented */
	#$t->edit = 'SupportTicketsform';   /* Allow rows to be editable with a save button that appears onchange */
	#$t->ipe_table = 'SupportTickets';   /* Make in place editing changes immediate without a save button */

	$db = new DB_hotspot;

        echo "<a href=\"".$sess->self_url().$sess->add_query(array("cmd"=>"Add"))."\">Add Support Tickets</a>\n";
$SupportTickets_fields= array();

        if (array_key_exists("SupportTickets_fields",$_REQUEST)) $SupportTickets_fields = $_REQUEST["SupportTickets_fields"];
        if (empty($SupportTickets_fields)) {
                $SupportTickets_fields = array_first_chunk($t->default,7,11);
                $sess->register("SupportTickets_fields");
        }
        $t->fields = $SupportTickets_fields;
		
	#$t->extra_html = array('fieldname'=>'extrahtml');
	#$t->align      = array('fieldname'=>'right', 'otherfield'=>'center'); 	

        echo "<a href=javascript:show('ColumnSelector')>Column Chooser</a>";
        echo "<form id=ColumnSelector method='post' style=display:none>\n";
        echo "<a href=javascript:hide('ColumnSelector')>Hide</a>";
        echo " Columns: ";
        foreach ($t->all_fields as $field) {
                if (in_array($field,$SupportTickets_fields,TRUE)) $chk = "checked='checked'"; else $chk="";
                echo "\n<input type='checkbox' $chk name=SupportTickets_fields[] value='$field' />$field ";
        }
        echo "\n<input type=submit name=setcols value='Set' />";
        if ($sess->have_edit_perm()) {
            if ($EditMode=='on') {
                $on='checked="checked"'; $off='';
		$t->edit = 'SupportTicketsform';   
		$t->ipe_table = 'SupportTickets';   #uncomment this for immediate table update (no save button)
            } else {
                $off='checked="checked"'; $on='';
            }
            echo "\nEdit Mode <input type='radio' name='EditMode' value='on' $on> On <input type='radio' name='EditMode' value='off' $off /> Off ";
        } else {
            $EditMode='';
        }
        echo "\n</form>\n";

  // When we hit this page the first time,
  // there is no .
  if (!isset($q_SupportTickets)) {
    $q_SupportTickets = new SupportTickets_Sql_Query;     // We make one
    $q_SupportTickets->conditions = 1;     // ... with a single condition (at first)
    $q_SupportTickets->translate  = "on";  // ... column names are to be translated
    $q_SupportTickets->container  = "on";  // ... with a nice container table
    $q_SupportTickets->variable   = "on";  // ... # of conditions is variable
    $q_SupportTickets->lang       = "en";  // ... in English, please
    $q_SupportTickets->primary_key = "id";  // let Query engine know primary key
    $q_SupportTickets->default_query = $db->qi("id")."!='0' desc";  // let Query engine know primary key

    $sess->register("q_SupportTickets");   // and don't forget this!
  }

  if ($rowcount) {
        $q_SupportTickets->start_row = $startingwith;
        $q_SupportTickets->row_count = $rowcount;
  }

  if ($submit=='Search') $query = $q_SupportTickets->search($t->map_cols);

  // When we hit that page a second time, the array named
  // by $base will be set and we must generate the $query.
  // Ah, and don\'t set $base to "q" when $q is your Sql_Query
  // object... :-)
  if (array_key_exists("x",$_POST)) {
    get_request_values("x");
    $query = $q_SupportTickets->where("x", 1);
    $hideQuery = "";
  } else {
    $hideQuery = "style='display:none'";
  }

  if (!$sortorder) $sortorder="id";
  if (empty($query)) { $query="1 order by  ".$db->qi("id")." desc"; }
  $db->query("SELECT COUNT(*) as total from ".$db->qi("SupportTickets")." where ".$query);
  $db->next_record();
  if ($db->f("total") < ($q_SupportTickets->start_row - $q_SupportTickets->row_count))
      { $q_SupportTickets->start_row = $db->f("total") - $q_SupportTickets->row_count; }

  if ($q_SupportTickets->start_row < 0) { $q_SupportTickets->start_row = 0; }

  $query .= " LIMIT ".$q_SupportTickets->start_row.",".$q_SupportTickets->row_count;

  // In any case we must display that form now. Note that the
  // "x" here and in the call to $q->where must match.
  // Tag everything as a CSS "query" class.
  echo "<a href=javascript:show('customQuery')>Custom Query</a>";
  echo "\n<div id=customQuery $hideQuery><a href=javascript:hide('customQuery')>Hide</a>\n";
  printf($q_SupportTickets->form("x", $t->map_cols, "query"));
  echo "\n</div>\n";

  // Do we have a valid query string?
  if ($query) {
    // Show that condition
    echo "<a href=javascript:show('QueryStats')>Query Stats</a><div id=QueryStats style=display:none>";
    echo "<a href=javascript:hide('QueryStats')>Hide</a><br>";
    printf("Query Condition = %s<br />\n", $query);

    // Do that query
	$sql = $t->select($f).$query;
    $db->query($sql);

    // Dump the results (tagged as CSS class default)
    printf("Query Results = %s<br /></div>\n", $db->num_rows());
    echo "<br />";
    $t->show_result($db, "default");
  }
} // switch $cmd
page_close();
?>

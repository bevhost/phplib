<?php
include('phplib/prepend.php');

if ($PHP_SELF=='/Vacation.php')
	page_open(array("sess"=>"hotspot_Session","auth"=>"hotspot_Auth","perm"=>"hotspot_Perm"));
else
	page_open(array("sess"=>"hotspot_Session"));

include("postfix.inc");

if (!$name) $name = "I";

if ($cmd=="Login") {
        $db2->query("select email, name from postfix_mailbox where username='".$username."'");
        if ($db2->next_record()) {
                $email = $db2->f(0);
		$name = $db2->f(1);
		if ($name) {
			$body=$db2->f(1)." is on vacation.";
			$subject = $body;
		} else {
			$subject="I am on vacation.";
			$body = $subject."  I will attend to your email upon my return.";
		}	
                $db2->query("select id from vacation where email='".$email."'");
                if ($db2->next_record()) $id=$db2->f(0);
                else $cmd='Add';
        } else {
                echo 'Mailbox not found.';
                echo "<META HTTP-EQUIV=REFRESH CONTENT=\"12; URL=".$sess->self_url()."\">";
                echo "&nbsp<a href=\"".$sess->self_url()."\">Back to vacation.</a><br>\n";
                page_close();
                exit;
        }
}

echo "<script language=JavaScript src=currency.js></script>\n";
echo "<script language=JavaScript src=datefunc.js>
//Parts taken from ts_picker.js
//Script by Denis Gritcyuk: tspicker@yahoo.com
//Submitted to JavaScript Kit (http://javascriptkit.com)
//Visit http://javascriptkit.com for this script
</script>
<script language=JavaScript>
function DoCustomChecks(f) {
	if (f.elements['LastWorkDay'].value >= f.elements['FirstDayBack'].value) {
		alert('You must come back after you leave.');
		f.elements['FirstDayBack'].focus();
		return false;
	}
        return true;
}
function MonthStr(m) {
	if (m<1900) m += 1900;
	return m; 
}
function SetBodyText(f) {
    if (DoCustomChecks(f)) {
        name = '".$name."';
        vacStart = str2dt(getFieldValue('LastWorkDay'));
        vacEnd = str2dt(getFieldValue('FirstDayBack'));
	strStart = arr_days[vacStart.getDay()] + ' ' + vacStart.getDate() 
		+ ' ' + arr_months[vacStart.getMonth()] + ' ' + MonthStr(vacStart.getYear());
	strEnd = arr_days[vacEnd.getDay()] + ' ' + vacEnd.getDate()
		+ ' ' + arr_months[vacEnd.getMonth()] + ' ' + MonthStr(vacEnd.getYear());
        if (name=='I') {
                body = 'I am';
        } else {
                body = name+' is';
        }
	body += ' on annual leave from '+strStart+' until '+strEnd;
        body += ' and will attend to your email upon returning.';
        setFieldValue('body',body)
    }
}
</script>
</script> \n";

class my_vacationform extends vacationform {
	var $classname="my_vacationform";
}
$f = new my_vacationform;

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
        echo "<font class=bigTextBold>$cmd vacation</font>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to vacation.</a><br>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to vacation.</a><br>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to vacation.</a><br>\n";
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

    case "Login": 
    case "Edit":
	echo "<h3>Change Vacation Settings</h3>\n";
	$fp = fopen('/etc/postfix/main.cf"','r');
	$found='dis';
	if ($fp) while (!feof($fp)) {
		$line = fgets($fp,1000);
		echo $line;
		if ($line=='always_bcc = filter@onvacation.nass.com.au') $found='en';
	}
	fclose($fp);
$found='en';
	echo "<h3>Vacation feature is currently ".$found."abled.</h3>";
	$f->display();
	break;
    default:
	$cmd="Query";
	$t = new vacationTable;
	$t->heading = 'on';
	$t->add_extra = 'on';
	$db = new DB_postfix;

        echo "<br><br>&nbsp<a href=\"".$sess->self_url()
		.$sess->add_query(array("cmd"=>"Add"))."\">Setup Vacation AutoReply</a>&nbsp\n";

	// These fields will be searchable and displayed in results.
	// Format is "RealFieldName"=>"Field Name Formatted For Display",
	$t->fields = array(
			"email",
			"subject",
			"LastWorkDay",
			"FirstDayBack"
			);
        $t->map_cols = array(
			"email"=>"email",
			"subject"=>"subject",
			"LastWorkDay"=>"Start",
			"FirstDayBack"=>"End",
			);

  // When we hit this page the first time,
  // there is no .
  if (!isset($q)) {
    $q = new vacation_Sql_Query;     // We make one
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
  if (!empty($x)) {
    $query = $q->where("x", 1);
  }

  if (!$query) { 
	$query="id='0'"; 
	if ($perm) if ($perm->have_perm("admin")) {
		if ($domain) $query = "email like '%".$domain."'";
		else $query="id!=0";
	}
	if ($auth) if ($domain) if (in_array($domain,$domains)) $query = "email like '%".$domain."'";
  }
  $db->query("SELECT COUNT(*) as total from vacation where ".$query);
  $db->next_record();
  if ($db->f("total") < ($q->start_row - $q->row_count))
      { $q->start_row = $db->f("total") - $q->row_count; }

  if ($q->start_row < 0) { $q->start_row = 0; }

  if (!$sortorder) $sortorder="id";
  $query .= " Order By ".$sortorder." LIMIT ".$q->start_row.",".$q->row_count;

  // In any case we must display that form now. Note that the
  // "x" here and in the call to $q->where must match.
  // Tag everything as a CSS "query" class.
if ($perm) if ($perm->have_perm("admin")) {
  printf($q->form("x", $t->map_cols, "query"));
  printf("<hr>");
}


  // Do we have a valid query string?
  if ($query) {
    // Show that condition
    //printf("Query Condition = %s<br>\n", $query);

    // Do that query
    $db->query("select * from vacation where ". $query);

    // Dump the results (tagged as CSS class default)
    //printf("Query Results = %s<br>\n", $db->num_rows());
    $t->show_result($db, "default");
  }
} // switch $cmd
page_close();
?>

<?php
include('phplib/prepend.php');
page_open(array("sess"=>"hotspot_Session","auth"=>"hotspot_Auth","perm"=>"hotspot_Perm"));
include($DOCUMENT_ROOT.'/SupportEmail.php');

#echo "<script language=JavaScript src=/dojo/dojo.js></script>\n";
echo "<script language=JavaScript src=/js/datefunc.js>
//Parts taken from ts_picker.js
//Script by Denis Gritcyuk: tspicker@yahoo.com
//Submitted to JavaScript Kit (http://javascriptkit.com)
//Visit http://javascriptkit.com for this script
</script>
<script language=JavaScript>
function edit(frm,fld){
 vURL = '/editor.php?frm='+frm+'&fld='+fld;
 newWindow =  window.open(vURL,null,'resizable=yes,location=yes,scrollbars=yes,width=990,height=580');
}
</script> \n";

if (!class_exists("SupportTicketsform")) include("phplib/SupportTickets.inc");
class my_SupportTicketsform extends SupportTicketsform {
        var $classname="my_SupportTicketsform";
}
class my_SupportCommentsform extends SupportCommentsform {
        var $classname="my_SupportCommentsform";
}
if (!class_exists("userinfoform")) include("phplib/userinfo.inc");
class my_userinfoform extends userinfoform {
        var $classname="my_userinfoform";
}

get_request_values("TicketNo,EMAILTXT,SMSTEXT,EMAIL,MOBILE");

if ((!$TicketNo) && (!$id)) {
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->url("/SupportTickets.php")."\">";
        page_close();
        exit;
}

$db = new DB_hotspot;

$f = new my_SupportCommentsform;

if ($submit) {
  switch ($submit) {
   case "Send":
	$hdrs = "From: Help Desk<ticket+".$TicketNo."@accessplus.net.au>";
	$hdrs .= "\r\nContent-type: text/html; charset=utf-8";
	if ($EMAILTXT) {
		mail($EMAIL,"Hotspot Support",$EMAILTXT,$hdrs);
		echo "<P>sending email to $EMAIL</P>";
	}
	if ($SMSTEXT) {
		if (substr($MOBILE,0,2)=="04") $MOBILE="61".substr($MOBILE,1);
		mail($MOBILE."@sms.accessplus.com.au","Hotspot Support",$SMSTXT,$hdrs);
		echo "<P>sending SMS to $MOBILE</P>";
	}
        echo "<font class=bigTextBold>$cmd Support Comments <a href=SupportTickets.php>Back to Support Tickets List</a></font>\n";
        echo "&nbsp<a href=\"".$sess->url("/SupportTickets.php");
	echo $sess->add_query(array("cmd"=>"View","id"=>$TicketNo))."\">Back to Ticket.</a><br>\n";
        page_close();
        exit;

   case "Save":
    if ($id) $submit = "Edit";
    else $submit = "Add";
   case "Add":
   case "Edit":
    if (isset($auth)) {
     if (!$f->validate($result)) {
        $cmd = $submit;
        echo "<font class=bigTextBold>$cmd Support Comments <a href=SupportTickets.php>Back to Support Tickets List</a></font>\n";
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
	echo $sess->add_query(array("cmd"=>"View","id"=>$TicketNo))."\">Back to Ticket.</a><br>\n";
	SupportEmail($TicketNo,$OldStatus);
        page_close();
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

$st = new my_SupportTicketsform;
$st->find_values($TicketNo);


echo "<font class=bigTextBold>Support Ticket $TicketNo</font> ";
$origcmd=$cmd;
switch ($cmd) {
    case "View":
    case "Delete":
	$f->freeze();
    case "Send":
    case "Add":
        $db->query("select id from userinfo where UserName='".$auth->auth["uname"]."'");
        $db->next_record();

        $ContID=$db->f(0);			/* logged in user */
	$ContactID = UserID($UserName);		/* looked up user */

	if (!$perm->have_perm("admin") and !$perm->have_perm("support")) {
		if (($ContID!=$ContactID) and ($EnteredBy!=$auth->auth["uname"])) {
			$str1 = "SupportComments: Access Denied ";
			$str2 = "$auth->auth[uname] $EnteredBy $UserName";
			Eventlog($str1,$str2,"Warning");
			echo $str1.$str2;
			break;	
		} 
	}
    case "Edit":
	echo "<font class=bigTextBold>$cmd Support Comments <a href=SupportTickets.php>Back to Support Tickets List</a></font>\n";
	echo "<table cellspacing=10><tr><td valign=top>";
	$cmd="View";
	if ($ContactID) {
		$mf = new my_userinfoform;
        	$mf->find_values($ContactID);
        	$mf->freeze();
        	$mf->display();
	}
	echo "</td><td>\n";
	$cmd="View";
	$st->freeze();
	$st->display();
        echo "</td></tr></table>";
	$cmd=$origcmd;
        printf("<table>\n");
	$db->query("select * from SupportComments where TicketNo='".$TicketNo."'");
        while ($db->next_record()) {
                $send = " <a href=".$sess->url("/SupportComments.php")
                        .$sess->add_query(array("cmd"=>"Send","id"=>$db->f(0))).">send</a>";
                if (empty($Mobile) and empty($Mail)) $send="";
                printf("<tr><td>%s</td><td>%s:</td><td>%s</td><td>%s</td><td>$send</td>"
			//	."<td><a href=%s>view</a>&nbsp;<a href=%s>edit</a></td>"
				."</tr>\n",
                                                                        $db->f("TimeStamp"),
									$db->f("ByUser"),
                                                                        $db->f("UserName"),
                                                                        stripslashes($db->f("Comment"))
                       // ,$sess->url("/SupportComments.php").$sess->add_query(array("cmd"=>"View","id"=>$db->f(0)))
                       // ,$sess->url("/SupportComments.php").$sess->add_query(array("cmd"=>"Edit","id"=>$db->f(0)))
                                                                        );
        }
	echo "</table>";
	$f->display();
	break;
    default:
	$cmd="Query";
	$t = new SupportCommentsTable;
	$t->heading = 'on';
	$t->add_extra = 'on';
	$db = new DB_hotspot;

        echo "&nbsp<a href=\"".$sess->self_url()
		.$sess->add_query(array("cmd"=>"Add"))."\">Add Support Comments</a>&nbsp\n";
        echo "&nbsp<a href=\"".$sess->url("/SupportTickets.php")."\">Tickets</a>&nbsp\n";
	echo "<font class=bigTextBold>$cmd Support Comments</font>\n";

	// These fields will be searchable and displayed in results.
	// Format is "RealFieldName"=>"Field Name Formatted For Display",
	$t->fields = array(
			"TicketNo",
			"ByUser");
        $t->map_cols = array(
			"TicketNo"=>"Ticket No",
			"ByUser"=>"By User");

  // When we hit this page the first time,
  // there is no .
  if (!isset($q)) {
    $q = new SupportComments_Sql_Query;     // We make one
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
  $db->query("SELECT COUNT(*) as total from SupportComments where ".$query);
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
    $db->query("select * from SupportComments where ". $query);

    // Dump the results (tagged as CSS class default)
    printf("Query Results = %s<br>\n", $db->num_rows());
    $t->show_result($db, "default");
  }
} // switch $cmd
page_close();
?>

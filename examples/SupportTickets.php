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
function edit(frm,fld){
 vURL = '/editor.php?frm='+frm+'&fld='+fld;
 newWindow =  window.open(vURL,null,'resizable=yes,location=yes,scrollbars=yes,width=990,height=580');
}
</script> \n";

class my_SupportTicketsform extends SupportTicketsform {
	var $classname="my_SupportTicketsform";
}
if (!class_exists("userinfoform")) include("phplib/userinfo.inc");
class my_userinfoform extends userinfoform {
        var $classname="my_userinfoform";
}
class MySupportTicketsTable extends SupportTicketsTable {
  var $classname = "MySupportTicketsTable";
  
  function table_row_add_extra($row, $row_key, $data, $class="") {
        global $sess, $auth, $perm, $Path;
        
        echo "<td>";
  
  if ($perm) {
    if (($perm->have_perm("admin")) or ($perm->have_perm("support"))) {
        echo "<a href=\"".$sess->self_url().
                $sess->add_query(array("cmd"=>"Edit",$this->primary_key=>$data[$this->primary_key]))."\" title='Edit'><img src='/images/edit.jpg'></img></a>";
        echo "<a href=\"".$sess->self_url().
                $sess->add_query(array("cmd"=>"Close",$this->primary_key=>$data[$this->primary_key]))."\" title='Close'><img src='/images/close.jpg'></img></a>";

    }
  } else {
	echo "<a href=\"".$sess->self_url().
                $sess->add_query(array("cmd"=>"View",$this->primary_key=>$data[$this->primary_key]))."\" title='View'><img src='/images/view.jpg'></img></a>";
  }     
        echo "</td>";
  }
}


$QUERY_STRING="";

get_request_values("SrchLevel,SrchState,assigned,SrchTicketNo,SrchShortDesc,SrchUserName,SrchEntBy,AssignedTo,TicketNo,Comment,Level,Closed,Status,OldStatus,Mail,Severity");

$f = new my_SupportTicketsform;
$f->setup();
$f->form_data->before = <<< BEFORE
if (f.elements['AssignedTo'].value=='') {
	if (!confirm('Ticket has not been assigned to anyone. OK to accept Cancel to change.')) {
		return false;
	}
}	
if (f.elements['UserName'].value.length<3) {
	f.elements['UserName'].value=prompt('Please enter Username','unknown');
}
if (f.elements['ShortDesc'].value.length<3) {
	f.elements['ShortDesc'].value=prompt('Please enter Short Description of Problem','');
}
if ((f.elements['RoomNo'].value.length<1) and (false)) {
	f.elements['RoomNo'].value=prompt('Please enter Room No','');
}
if ((f.elements['Status'].selectedIndex==0) && (f.elements['Level'].value==0)) {
	alert('Incompatible level and status, Cannot be Open and Solved at the same time. Change problem type or make status closed.');
	return false;	
}
BEFORE;
$f->form_data->after = <<< AFTER
if (f.elements['SrchLocation'].value=='') {
	if (f.elements['Location'].value=='') {
		return confirm('No location has been selected, OK to accept Cancel to change');
	} else {
		f.elements['SrchLocation'].value = f.elements['Location'].value;
	}
}
AFTER;
if ($submit) {
  switch ($submit) {

   case "EmptyCart":
        $cart->reset();
        $QUERY_STRING="";
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"0; URL=".$sess->self_url().$extra."\">";
        echo "&nbsp<a href=\"".$sess->self_url().$extra."\">Back to Tickets.</a><br>\n";
        page_close();
        exit;

   case "Save":
    if ($id) $submit = "Edit";
    else $submit = "Add";
   case "Add":
   case "Edit":
    if (isset($auth)) {
     if (!$f->validate()) {
        $cmd = $submit;
        echo "<h2>$cmd Support Tickets</h2>\n";
        $f->display();
        page_close();
        exit;
     }
     else
     {
	$Location = $_POST["SrchLocation"];
        echo "Saving..$Location..";
	if ($Status=='Closed') $Closed=date("Y-m-d H:i:s");
        $f->save_values();
	if (!$id) {
        	$db->query("SELECT LAST_INSERT_ID();");
        	$db->next_record();
        	$TicketNo = $db->f(0);
	} else $TicketNo=$id;
	if ($Comment) {
		$submit="Add";
		$f = new SupportCommentsform;
		$ByUser = $auth->auth["uname"];
		$TimeStamp=date("Y-m-d H:i:s");
		$id="";
		$_POST["id"]="";
		$f->save_values();
	}
        echo "<b>Done!</b><br>\n";
	if ($Status=='Closed') {
        	echo "<META HTTP-EQUIV=REFRESH CONTENT=\"20; URL=".$sess->url("SupportTickets.php");
		echo $sess->add_query(array("SrchLocation"=>"Any","Assigned"=>"anyone"));
		echo "\">";
        	echo "&nbsp<a href=\"".$sess->url("SupportTickets.php");
		echo $sess->add_query(array("SrchLocation"=>"Any","Assigned"=>"anyone"));
	} else {
        	echo "<META HTTP-EQUIV=REFRESH CONTENT=\"20; URL=".$sess->url("SupportComments.php");
		echo $sess->add_query(array("cmd"=>"Add","TicketNo"=>$TicketNo));
		echo "\">";
        	echo "&nbsp<a href=\"".$sess->url("SupportComments.php");
		echo $sess->add_query(array("cmd"=>"Add","TicketNo"=>$TicketNo));
	}
	echo "\">Back to SupportTickets.</a><br>\n";
	SupportEmail($TicketNo,$OldStatus);
        page_close();
        exit;
     }
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br>\n";
    }
   case "Close":
	$sql = "UPDATE SupportTickets SET Status='Closed', Closed=now() WHERE id='$id'";
	$db->query($sql);
	echo "Closing Ticket $id<br>\n";
   case "View":
   case "Back":
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"0; URL=".$sess->self_url();
	echo $sess->add_query(array("SrchUserName"=>"","SrchLocation"=>"Any","Assigned"=>"anyone"));
	echo "\">";
        echo "&nbsp<a href=\"".$sess->self_url();
	echo $sess->add_query(array("SrchLocation"=>"Any","Assigned"=>"anyone"));
	echo "\">Back to SupportTickets.</a><br>\n";
        page_close();
        exit;
   case "ReOpen":
	$sql = "UPDATE SupportTickets SET Status='Open' WHERE id='$id'";
	$db->query($sql);
	echo "ReOpening Ticket $id<br>\n";
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"0; URL=".$sess->self_url();
	echo $sess->add_query(array("cmd"=>"Edit","id"=>$id));
	echo "\">";
        echo "&nbsp<a href=\"".$sess->self_url();
	echo $sess->add_query(array("cmd"=>"Edit","id"=>$id));
	echo "\">Edit Ticket</a><br>\n";
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
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url();
	echo $sess->add_query(array("SrchUserName"=>"","SrchLocation"=>"Any","Assigned"=>"anyone"));
	echo "\">";
        echo "&nbsp<a href=\"".$sess->self_url();
	echo $sess->add_query(array("SrchLocation"=>"Any","Assigned"=>"anyone"));
	echo "\">Back to SupportTickets.</a><br>\n";
        page_close();
        exit;
   case "Save Changes":
        echo "Saving. . . .";
        $cart->update_all();
        echo "<b>Done!</b><br>\n";
        $QUERY_STRING="";
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"0; URL=".$sess->self_url().$extra."\">";
        echo "&nbsp<a href=\"".$sess->self_url().$extra."\">Back to Products.</a><br>\n";
        page_close();
        exit;
        break;

  }
} else {
    if ($id) {
	$f->find_values($id);
	$TicketNo=$id;
        echo "&nbsp<a href=\"".$sess->url("/SupportComments.php")
		.$sess->add_query(array("cmd"=>"Add","TicketNo"=>$TicketNo))."\">Add Comment</a>&nbsp\n";
	echo "&nbsp;<a href=SupportTickets.php>Back to Support Tickets List</a>";
	$SrchLocation = $Location;
    }
}

include('phplib/locations.inc');

$id = $TicketNo;
echo "<h2>$cmd Support Ticket $id </h2> ";
$origcmd=$cmd;
switch ($cmd) {
    case "View":
	if ($UID=UserID($UserName)) {
		echo "<h3>User Information</h3>\n";
		$mf = new my_userinfoform;
		$mf->find_values($UID);
		$mf->freeze();
		$mf->display();
	}
	echo "</td><td valign=top>";
        echo "<h3>Ticket Details</h3>\n";
	$cmd=$origcmd;
    case "Delete":
    case "Close":
	$f->freeze();
    case "Add":

    case "Edit":
	if (!isset($Location) and isset($SrchLocation)) $Location=$SrchLocation;
	if ($Status=='Closed') $f->freeze();
 	if (($cmd<>"Add") and ($perm->have_perm("sitecontact"))) 
	   if ($EnteredBy<>$auth->auth["uname"]) {
		echo "Not one of your tickets.";
		page_close();
		exit;
	}
	$f->display();
	$TotalCharge = 0;
      if ($perm->have_perm("admin") or $perm->have_perm("support")) {
        $t = new MySupportTicketsTable;
        $t->heading = 'on';
        $t->add_extra = 'on';
        $sortorder="Created";
        $t->fields = array(
                        "ShortDesc",
                        "Status",
                        "Created",
                        "EnteredBy");
        $t->map_cols = array(
                        "EngineerEmail"=>"Engineer Email",
                        "ShortDesc"=>"Description",
                        "Status"=>"Status",
                        "Created"=>"Created",
                        "EnteredBy"=>"By");
	$quser = $db->quote($UserName);
	
/*         $db->query("select * from SupportTickets where UserName<>$quser and Status<>'Closed' order by $sortorder limit 0,8");
        if ($db->num_rows()) echo "<h5>Current Support Tickets for other users</h5>\n";
        if ($t->show_result($db, "default")==8) {
                echo "<a href=";
                echo $sess->url("SupportTickets.php");
                echo $sess->add_query(array("SrchState[1]"=>"Open","SrchState[2]"=>"Flagged","SrchState[3]"=>"OnHold","SrchLocation"=>"Any","assigned"=>"anyone"));
                echo ">Show More Tickets</a>";
        } */

      }
/* 	if ($origcmd=='Edit' and $UID=UserID($UserName)) {
		$cmd="Back";
		echo "<br/>";
		echo "<h3>User Information</h3>\n";
		$mf = new my_userinfoform;
		$mf->find_values($UID);
		$mf->freeze();
		$mf->display();
	} */
   if ($origcmd!='Add') {
	$shown=array();
	$db->query("select FileName from SupportFiles where TicketNo='".$TicketNo."'");
        while ($db->next_record()) {
		if (!isset($fn)) echo "<p>&nbsp;<br /></p><h3>Attached Files</h3>\n";
		$fn = $db->f(0);
		if (!array_key_exists($fn,$shown)) echo "<a href='/files/$TicketNo/".htmlentities($fn,ENT_QUOTES,"UTF-8")."' target='_blank'>$fn</a><br />\n";
		$shown[$fn] = true;
	}
	$db->query("select * from SupportComments where TicketNo='".$TicketNo."'");
	printf("<br /><h3>Comments</h3><table class='comments'>\n\n",
			$sess->url("/SupportComments.php").$sess->add_query(array("cmd"=>"Add","TicketNo"=>$TicketNo))
		);
	while ($db->next_record()) {
		if (strtotime($db->f("TimeStamp"))+300 > mktime()) 
		$edit = "<a href=".$sess->url("/SupportComments.php")
			.$sess->add_query(array("cmd"=>"Edit","id"=>$db->f(0))).">edit</a>";
		else $edit="";
		$send = "<a href=".$sess->url("/SupportComments.php")
			.$sess->add_query(array("cmd"=>"Send","id"=>$db->f(0))).">send</a>";
		if (!isset($Mail)) $Mail="";
		if (!isset($Mobile)) $Mobile="";
		if ($Mobile.$Mail=="") $send="";
		printf("<tr><td width=60 class='com'>%s</td><td class='com'>%s:</td><td class='com'>%s</td><td class='com'>%s %s</td></tr>\n",
									$db->f("TimeStamp"),
									$db->f("ByUser"),
									stripslashes($db->f("Comment")),
									$edit,$send
									);
	}
   }
   
/*    if (isset($Billing)) {
	printf("<tr><td colspan=4><br><b>Billable Work Details <a href=%s>add</a></b></td></tr>\n",
			$sess->url("/SupportDetails.php").$sess->add_query(array("cmd"=>"Add","TicketNo"=>$TicketNo))
		);
	$db->query("select * from SupportDetails where TicketNo='".$TicketNo."'");
	while ($db->next_record()) {
		$Charge = $db->f("Charge");
		if (!$Charge) $Charge = $db->f("Duration") * $Rate/60;
		$TotalCharge += $Charge;
		printf("<tr><td>%s</td><td>%s</td><td>%s</td><td align=right>$%0.2f</td><td><a href=%s>view</a></td></tr>\n",	
									$db->f("StartTime"),
									$db->f("Duration"),
									$db->f("Details"),
									$Charge,
			$sess->url("/SupportDetails.php").$sess->add_query(array("cmd"=>"View","id"=>$db->f("id")))
									);
	}
	printf("<tr><td colspan=4><br><b>Parts Used <a href=%s>add</a> <a href=%s>add from cart</a></b></td></tr>\n",
			$sess->url("/SupportParts.php").$sess->add_query(array("cmd"=>"Add","TicketNo"=>$TicketNo)),
			$sess->url("/SupportParts.php").$sess->add_query(array("cmd"=>"AddFromCart","TicketNo"=>$TicketNo))
		);
	$db->query("select * from SupportParts where TicketNo='".$TicketNo."'");
	while ($db->next_record()) {
		$Charge = $db->f("Price") * $db->f("Quantity");	
		$TotalCharge += $Charge;
		printf("<tr><td>%s</td><td>%s</td><td>%s</td><td align=right>$%0.2f</td><td><a href=%s>view</a> <a href=%s>edit</a> <a href=%s>delete</a></td></tr>\n",
									$db->f("ProductCode"),
									$db->f("PartNo"),
									$db->f("Description"),
									$Charge,
			$sess->url("/SupportParts.php").$sess->add_query(array("cmd"=>"View","id"=>$db->f("id"))),
			$sess->url("/SupportParts.php").$sess->add_query(array("cmd"=>"Edit","id"=>$db->f("id"))),
			$sess->url("/SupportParts.php").$sess->add_query(array("cmd"=>"Delete","id"=>$db->f("id")))
									);
	}
	printf("<tr><td colspan=2></td><td align=right>TOTAL&nbsp;CHARGE&nbsp;</td><td align=right>$%0.2f</td><td></td></tr>\n",$TotalCharge);
    } */
	break;
    default:
	$cmd="Query";
	$t = new MySupportTicketsTable;
	$t->heading = 'on';
	$t->add_extra = 'on';
	$db = new DB_hotspot;

	$db->query("select Mail from userinfo where UserName='".$auth->auth["uname"]."'");
	if ($db->next_record()) {
		$MyEmail = $db->f(0);
	}

    echo "<br><a href=\"".$sess->url("/SupportTickets.php")
		.$sess->add_query(array("cmd"=>"Add"))."\">Create New Support Ticket</a>&nbsp\n";

    echo "<h1>Find Ticket</h1>";


extract($_REQUEST);
	if ($SrchState=="Array") $SrchState=Array();
	if (!$assigned) {
	#	$assigned='me';
	#	if ($auth->auth["uname"]=='cams') 
		$assigned='anyone';
	}
	
	if ($assigned=='me') $AssignedTo=$auth->auth["uname"];
        if (!$SrchState) {
		if ($auth->auth["uname"]=='cams') $SrchState=array("Open");
		else $SrchState=array("Open");
		if ($perm->have_perm("accounts")) $SrchState=array("Accounts");
		if ($auth->auth["uname"]=='admin') $SrchState=array("Software");
	}
        $sess->register("SrchState,SrchShortDesc,SrchEntBy,SrchUserName");
        
// var_dump($_POST);
?>
<script>
function Any() {
	document.SupportQueryForm.SrchLocation.selectedIndex=0;
}
function me() {
	document.SupportQueryForm.SrchEntBy.value='<?=$auth->auth["uname"];?>';
}
function assign(usr) {
	document.SupportQueryForm.AssignedTo.value=usr
}
</script>
<form name=SupportQueryForm method=POST><br>
<table><tr><td>
Ticket No</td><td><input name=SrchTicketNo value='<?=$SrchTicketNo?>'> or</td></tr><tr><td>
Assigned To</td><td>
<input type=radio value=me name=assigned onclick="assign('<?php echo $auth->auth["uname"]; ?>')" 
	<?php if($assigned=='me') echo "checked"; ?>>Me 
<input type=radio value=anyone name=assigned onclick="assign('')" 
	<?php if($assigned=='anyone') echo "checked"; ?>>Anyone
<input type=radio value=specific name=assigned onclick=document.SupportQueryForm.AssignedTo.focus(); 
	<?php if($assigned=='specific') echo "checked"; ?>>Specify
<input type=text value='<?=$AssignedTo?>' name=AssignedTo onkeyup="document.SupportQueryForm.AssignedTo.checked='checked'"></td></tr><tr><td>
Level</td><td><select name=SrchLevel>
    <option>select...</option>
<?php $db->query("select id,Description from SupportLevels");
	while ($db->next_record()) {
		if ($SrchLevel==$db->f("id")) $sel=" selected"; else $sel="";
		echo "    <option$sel value='".$db->f("id")."'>".$db->f("Description")."</option>\n";
	}

?>
</select></td></tr><tr><td>
Location</td><td><select name=SrchLocation>
<option value='Any'>Any
<?=$LocOptions?>
</select>
<a href='javascript:Any()'>Any</a>
<input type=checkbox <?php if (in_array("Open",$SrchState)) echo "checked"; ?> name=SrchState[] value=Open>Open
<input type=checkbox <?php if (in_array("Flagged",$SrchState)) echo "checked"; ?> name=SrchState[] value=Flagged>Flagged
<input type=checkbox <?php if (in_array("OnHold",$SrchState)) echo "checked"; ?> name=SrchState[] value=OnHold>On Hold
<input type=checkbox <?php if (in_array("Accounts",$SrchState)) echo "checked"; ?> name=SrchState[] value=Accounts>Accounts
<input type=checkbox <?php if (in_array("Software",$SrchState)) echo "checked"; ?> name=SrchState[] value=Software>Software
<input type=checkbox <?php if (in_array("Closed",$SrchState)) echo "checked"; ?> name=SrchState[] value=Closed>Closed
</td></tr><tr><td>
Description</td><td><input name=SrchShortDesc value='<?=$SrchShortDesc?>'></td></tr><tr><td>
Entered By</td><td><input name=SrchEntBy value='<?=$SrchEntBy?>'>
<?php
	// Site Contacts can only see tickets that they created themselves.
	if ($perm->have_perm("sitecontact")) {
		$SrchEntBy = $auth->auth["uname"];
	} else {
?>
<a href='javascript:me()'>me</a>
<?php   } ?>
</td></tr><tr><td>
Username</td><td><input name='SrchUserName' value='<?=$SrchUserName?>'>
<input type=submit value=Search>
<?php if (substr($sortorder,-5,5)==" desc") { $sortdesc=1; $sortorder=substr($sortorder,0,-5); } ?>
</td></tr><tr><td>Sorted by
</td><td><select name=sortorder>
<option value='id'>Order Created
<option <?php if ($sortorder=='Updated') echo 'selected'; ?> value='Updated'>Updated
<option <?php if ($sortorder=='UserName') echo 'selected'; ?> value='UserName'>UserName
<option <?php if ($sortorder=='ShortDesc') echo 'selected'; ?> value='ShortDesc'>Description
<option <?php if ($sortorder=='Status') echo 'selected'; ?> value='Status'>Status
<option <?php if ($sortorder=='Created') echo 'selected'; ?> value='Created'>Created
<option <?php if ($sortorder=='EnteredBy') echo 'selected'; ?> value='EnteredBy'>Entered By
<option <?php if ($sortorder=='TicketNo') echo 'selected'; ?> value='id'>Ticket No
<option <?php if ($sortorder=='InvoiceNo') echo 'selected'; ?> value='InvoiceNo'>Invoice No
<option <?php if ($sortorder=='Rate') echo 'selected'; ?> value='Rate'>Rate
</select>
<input type=checkbox name=sortdesc <?php if ($sortdesc) { echo "checked"; $sortorder .= ' desc'; } ?> value='desc'> reversed
</td></tr><tr><td>Display</td><td>
<?php
    if ($rowcount) {
        if ($start) $startingwith = 0;
        if ($prev) $startingwith -= $rowcount;
        if ($next)  $startingwith += $rowcount;
        if ($last) $startingwith = $total-$rowcount;
    } else $rowcount=200;
    if (!$startingwith) $startingwith='0';
?>
<input name=rowcount value='<?=$rowcount?>' size=5>
rows, starting from row
<input name=startingwith value='<?=$startingwith?>' size=5>
<input name=start type=submit value='&lt;&lt;'>
<input name=prev type=submit value='&lt;'>
<input name=next type=submit value='&gt;'>
<input name=last type=submit value='&gt;&gt;'>
</td></tr></table>
<?php

     $sql = "";
     if ($SrchTicketNo) {
	$sql = "SupportTickets.id=$SrchTicketNo ";
     } else {
        if ($SrchLocation) 
		if ($SrchLocation<>'Any') $sql = "Location='$SrchLocation'";
        if ($SrchShortDesc) {
                if ($sql) $sql .= " and ";
                $sql .= "ShortDesc like '%$SrchShortDesc%'";
        }
        if ($AssignedTo) {
                if ($sql) $sql .= " and ";
                $sql .= "AssignedTo='$AssignedTo'";
        }
        if (($SrchLevel) and ($SrchLevel<>"select..." and ($SrchLevel<>"Any"))) {
                if ($sql) $sql .= " and ";
                $sql .= "Level = '$SrchLevel'";
        }
        if ($SrchUserName) {
                if ($sql) $sql .= " and ";
                $sql .= "UserName like '%$SrchUserName%'";
        }
        if ($SrchEntBy) {
                if ($sql) $sql .= " and ";
                $sql .= "EnteredBy like '%$SrchEntBy%'";
        }
        $cj = " and ";
        foreach ($SrchState as $k => $v) {
                if ($sql) { $sql .= $cj; }
                if ($cj==" and ") $sql .= " ( ";
                $cj = " or ";
                $sql .= "Status='$v'";
        }
        if ($cj==" or ") $sql .= " ) ";
     }
     $query = $sql;


	// These fields will be searchable and displayed in results.
	// Format is "RealFieldName"=>"Field Name Formatted For Display",
	$t->fields = array(
			"id",
			"UserName",
			"ShortDesc",
			"Status",
			"Severity",
			"Created",
			"Updated",
			"Location",
			"EnteredBy");
        $t->map_cols = array(
			"id"=>"Ticket No",
			"UserName"=>"UserName",
			"ShortDesc"=>"Description",
			"Status"=>"Status",
			"Severity"=>"Severity",
			"Created"=>"Created",
			"Updated"=>"Updated",
			"EnteredBy"=>"Entered By");

  // When we hit this page the first time,
  // there is no .
  if ((!isset($q)) or ($q->classname<>"SupportTickets_Sql_Query")) {
    $q = new SupportTickets_Sql_Query;     // We make one
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

  if (!$query) { 
	if ($Status) $query="Status='$Status'";
	else $query="Status<>'Closed'";
  }
  if ((!$perm->have_perm('support')) && (!$perm->have_perm("admin"))) {
	if ($perm->have_perm("sitecontact")) $query .= " and Location in ($Locations)";
        else $query .= " and UserName='".$auth->auth["uname"]."'";
  }
  $db->query("SELECT COUNT(*) as total from SupportTickets where ".$query);
  $db->next_record();
  $total = $db->f("total");
  if ($db->f("total") < ($q->start_row - $q->row_count))
      { $q->start_row = $db->f("total") - $q->row_count; }

  if ($q->start_row < 0) { $q->start_row = 0; }

  if (!$sortorder) $sortorder="Created";

  $query .= " group by id order by $sortorder";

  #$query .= " LIMIT ".$q->start_row.",".$q->row_count;
  $query .= " LIMIT 0,100";

  echo "<input name=total value=$total type=hidden>\n</form>\n";

  // In any case we must display that form now. Note that the
  // "x" here and in the call to $q->where must match.
  // Tag everything as a CSS "query" class.
//  printf($q->form("x", $t->map_cols, "query"));
  printf("<hr>");


  // if (!$query) { $query="id!='0'"; }

  // Do we have a valid query string?
  if ($query) {
    // Show that condition
    if ($perm->have_perm("admin")) printf("Query Condition = %s<br>\n", $query);

    // Do that query
    $db->query("select SupportTickets.*, MAX(SupportComments.TimeStamp) as Updated 
		from SupportTickets LEFT JOIN (SupportComments) on (SupportComments.TicketNo=SupportTickets.id)
		where ". $query);

    // Dump the results (tagged as CSS class default)
    printf("Query Results = %s of %s<br>\n", $db->num_rows(),$total);
    $t->show_result($db, "default");
  }
} // switch $cmd
	echo "</table>";
	echo "</td></tr></table>\n";
//  echo "<hr>\n";
//  $cart->show_all();
page_close();
?>

<?php
include('phplib/htmlMail.php');
echo "<script language=JavaScript src=/ckeditor/ckeditor.js></script>\n";
echo "<script language=JavaScript src=/ckfinder/ckfinder.js></script>\n";
$db = new DB_hotspot;
function UserID($UserName) {
	$db = new DB_hotspot;
	$quser = $db->quote($UserName);
	$db->query("select id from userinfo where UserName=$quser");
	if ($db->next_record()) return $db->f(0);
	else return false;
}

if (!class_exists("SupportCommentsform")) include("phplib/SupportComments.inc");

check_view_perms();

$AccessLevel = 0;

if ($perm->have_perm("user")) $AccessLevel = 1;
if ($perm->have_perm("sitecontact")) $AccessLevel = 2;
if ($perm->have_perm("support")) $AccessLevel = 3;
if ($perm->have_perm("admin")) $AccessLevel = 3;
if ($AccessLevel==0) {
	echo "Access Denied";
	page_close();
	exit;
}

echo "<script>
function finduser(){
 var UserName = document.my_SupportTicketsform['UserName'].value;
 if (!UserName) { UserName = prompt('Enter UserName to Search for','');  }
 if (UserName!=null) { 
  var vURL = 'FindUser.php?UserName='+UserName;
  var Location = document.my_SupportTicketsform['SrchLocation'].value;
  if (Location) { vURL += '&Location='+Location; }
  helpWindow = window.open(vURL,null,'resizable=yes,location=yes,scrollbars=yes,width=780,height=480');
 }
}
</script>\n";

function get_email($username) {
	$db = new DB_hotspot;
	if (!$username) return false;
	if ($username=='unassigned') return false;
	$db->query("select Mail from userinfo where UserName='$username'");
	if ($db->next_record()) {
		return $db->f(0);
	}
}

function SupportEmail($TicketNo, $OldStatus="") {
	global $UserName, $Name, $Company, $StreetAddress, $Town, $State, $PostCode, $CO, $OtherDetail;
	global $Quantity, $ProductCode, $PartNo, $Description, $Price, $Duration, $Rate, $Details, $Email, $Comment, $ByContact;
	global $HomePhone, $WorkPhone, $Mobile, $Status, $ShortDesc, $EngineerEmail, $Created, $EnteredBy, $SequenceNo;
	global $AssignedTo, $ByUser;

	if ($TicketNo<1) return false;

	$Level = 0;
	$db = new DB_hotspot;
	$stf = new SupportTicketsform;
	$stf->find_values($TicketNo);
	$mf = new userinfoform;
	$mf->find_values(UserID($UserName));
	$EngineerEmail = get_email($AssignedTo);
	if ($OldStatus) $Status = $OldStatus."->".$Status;
	$subj = "Ticket: $TicketNo, $Status, $ShortDesc";
	$Msg = "Job for: <b>$UserName, $Name</b><br>\n";
	if ($StreetAddress.$Town.$State.$PostCode) {
		$Msg .= "at: <b>";
		if ($StreetAddress) $Msg .= "$StreetAddress, ";
		if ($Town) $Msg .= "$Town, ";
		if ($State) $Msg .= "$State, ";
		if ($PostCode) $Msg .= "$PostCode";
		$Msg .= "</b><br>\n";
	}
	if (isset($HomePhone)) $Msg .= "HomePhone: <b>$HomePhone</b><br>\n";
	if (isset($WorkPhone)) $Msg .= "WorkPhone: <b>$WorkPhone</b><br>\n";
	if (isset($Mobile)) $Msg .= "Mob: <b>$Mobile</b><br>\n";
	if (isset($Mail)) $Msg .= "Email: <b>$Mail</b><br>\n";
	$Msg .= "------------------------------------------------------------<br>\n";
	$Msg .= "Entered By: <b>$EnteredBy</b><br>\n";
	$Msg .= "Date: <b>$Created</b><br>\n";
	if (isset($Location)) $Msg .= "Location: <b>$Location</b><br>\n";
	if (isset($RoomNo)) $Msg .= "Room No: <b>$RoomNo</b><br>\n";
	if (isset($Severity)) $Msg .= "Severity: <b>$Severity</b><br>\n";
	if ($Level>0) $Msg .= "Level: <b>$Level</b><br>\n";
	$Msg .= "Description: <b>$ShortDesc</b> $OtherDetail<br>\n";
	$Msg .= "------------------------------------------------------------<br>\n";
/*
	$db->query("select id from SupportParts where TicketNo='".$TicketNo."'");
	$sp = new SupportPartsform;
	while ($db->next_record()) {
		$sp->find_values($db->f(0));
		$Msg .= "Part: <b>$Quantity x $ProductCode, $PartNo, $Description @ $Price</b><br>\n";
	}
	$db->query("select id from SupportDetails where TicketNo='".$TicketNo."'");
	$sd = new SupportDetailsform;
	while ($db->next_record()) {
		$sd->find_values($db->f(0));
		$Msg .= "Work: <b>$Duration minutes @ $Rate /hour, $Details</b><br>\n";
	}
*/
	$db->query("select distinct FileName from SupportFiles where TicketNo='".$TicketNo."'");
	while ($db->next_record()) {
		$Msg .= "Attachment: http://os.$CO.com.au/files/$TicketNo/".$db->f(0)."<br>\n";
	}
	$db->query("select id from SupportComments where TicketNo='".$TicketNo."'");
	$sc = new SupportCommentsform;
	while ($db->next_record()) {
		$sc->find_values($db->f(0));
		$mf->find_values($ByContact);
		$Msg .= "Comment by <b>$ByUser:</b> ";
		$Msg .= str_replace('" src="/candy/','" src="http://os.'.$CO.'.com.au/candy/',$Comment);
		$Msg .= "<br>\n";
	}
	$url = "https://os.$CO.com.au/SupportTickets.php?cmd=View&id=".$TicketNo;
	$Msg .= "<a href='$url'>$url</a><br>\n";

	$hdrs = "From: ticket+$TicketNo@$CO.net.au";
	if ($EngineerEmail) $hdrs .= "\r\nTo: $EngineerEmail";
	
	htmlMail("info@$CO.com.au", $subj, $Msg, $hdrs); //, $text="", $name="", $type="Application/Octet-Stream", $data="", $name2="", $display2="")
	echo "<pre>";
	echo $hdrs;
	echo $subj;
	echo "\n";
	echo $Msg;
	echo "</pre>";
}
?>

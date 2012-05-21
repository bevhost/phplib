<?php
include($DOCUMENT_ROOT.'/phplib/prepend.php');
include($DOCUMENT_ROOT.'/phplib/smsapi.php');
page_open(array("sess"=>"whatsup_Session","auth"=>"whatsup_Auth","perm"=>"whatsup_Perm"));

echo "<script language=JavaScript src=currency.js></script>\n";
echo "<script language=JavaScript src=datefunc.js>
//Parts taken from ts_picker.js
//Script by Denis Gritcyuk: tspicker@yahoo.com
//Submitted to JavaScript Kit (http://javascriptkit.com)
//Visit http://javascriptkit.com for this script
</script>
<script language=JavaScript>
function DoCustomChecks(form) {
  return true;
}
function SmsFormValidator(form,max) {
 var total = 0;
 for (var idx = 1; idx <= max; idx++) {
   if (eval('document.SmsForm.SendTo' + idx + '.checked') == true) {
    total += 1;
   }
 }
 if (total==0) {
        alert('You have not selected any recipients.');
        return false;
 }
 if (form.Message.value.length==0) {
        alert('You have not typed in your message.');
        form.Message.focus();
        return false;
 }else{
        return true;
 }
}

<!-- Original:  Ronnie T. Moore -->
<!-- Web Site:  The JavaScript Source -->
<!-- Dynamic 'fix' by: Nannette Thacker -->
<!-- Web Site: http://www.shiningstar.net -->
<!-- This script and many more are available free online at -->
<!-- The JavaScript Source!! http://javascript.internet.com -->
<!-- Begin
function textCounter(field, countfield, maxlimit) {
if (field.value.length > maxlimit) // if too long...trim it!
field.value = field.value.substring(0, maxlimit);
// otherwise, update 'characters left' counter
else 
countfield.value = maxlimit - field.value.length;
}
// End -->
</script> \n";

class my_SmsAddressBookform extends SmsAddressBookform {
	var $classname = "my_SmsAddressBookform";
}

$f = new my_SmsAddressBookform;
$db = new DB_whatsup;

if ($submit) {
  switch ($submit) {

   case "Save":
    if ($id) $submit = "Edit";
    else $submit = "Add";
   case "Add":
   case "Edit":
    $Charge = 4;
    if (isset($auth)) {
     if (!$f->validate($result)) {
        $cmd = $submit;
        echo "<font class=bigTextBold>$cmd Sms Address Book</font>\n";
        $f->display();
        page_close();
        exit;
     }
     else
     {
        $mysms = new sms();
        if ($mysms->session) $Charge = $mysms->checkCoverage($CountryCode.$AreaCode.$Number);
        echo "Saving....";
        $f->save_values();  $count=0;
        $db->query("select distinct Country from SmsCoverage where CountryCode='".$CountryCode."'");
        echo "<b>Done!</b><br><table><tr><td><b>Carriers in ";
        while ($db->next_record()) {
                if ($count) echo ", ";
                echo $db->f("Country");
                $count++;
        }
        $MobileNumber = $CountryCode.$AreaCode.$Number;
        echo "&nbsp;&nbsp;</b></td><td><b>Credits Used Per SMS Message</b></td></tr>\n";
        $db->query("select * from SmsCoverage where CountryCode='".$CountryCode."'");
        while ($db->next_record()) {
                printf("<tr><td>%s</td><td>%s</td></tr>\n",$db->f("Carrier"),$db->f("Cost"));
        }
        echo "</table><br><a href=\"".$sess->self_url()."\">Back to SmsAddressBook.</a><br>\n";
        echo "<a href=".$sess->self_url();
        echo $sess->add_query(array("submit"=>"Send VCard","MobileNumber"=>$MobileNumber,"Recip"=>$Name));
        echo ">Send VCard to ".$Name."</a>";
        page_close();
        exit;
     }
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br>\n";
    }
   case "Send VCard":
        $QUERY_STRING="";
        echo "<br><br>";
//        $db->query("select SmsCredits, id from Members where UserName='".$auth->auth["uname"]."'");
//        if ($db->next_record()) {
//                $Credits = $db->f(0);
//                $MemberID = $db->f(1);
//        } else { 
//                $Credits = 0;
//                echo "Can't find how many credits you have left. - Program Error please tell webmaster@nass.com.au";
//                page_close();
//		exit;
//        }
	$Credit=99;
        if (($Credits>$MsgCount) || (!$perm->have_perm("guest"))) {
            $mysms = new sms();
            $startBalance = $mysms->getbalance();
            $db->query("select * from Members where UserName='".$auth->auth["uname"]."'");
            if ($db->next_record()) {
                $CardMsg = sprintf("BEGIN:VCARD\r\nVERSION:2.1\r\nN:%s;%s\r\n",$db->f("LastName"),$db->f("FirstName"));
                if ($db->f("CompanyName")) $CardMsg .= sprintf("ORG:%s\r\n",$db->f("CompanyName"));
                $CardMsg .= sprintf("ADR:%s;%s;%s;;%s;%s;Australia\r\n",$db->f("AddressLine1"),$db->f("AddressLine2"),
                                                                        $db->f("Suburb"),$db->f("State"),$db->f("PostCode"));
                if ($db->f("Fax")) $CardMsg .= sprintf("TEL;FAX:%s\r\n",$db->f("Fax"));
                $CardMsg .= sprintf("TEL;PREF:%s\r\n",$db->f("Telephone"));
                $CardMsg .= sprintf("TEL;CELL:%s\r\n",$db->f("Mobile"));
                $CardMsg .= sprintf("EMAIL:INTERNET:%s\r\n",$db->f("Email"));
                $CardMsg .= "END:VCARD\r\n";
                $mysms = new sms();
                printf ("<br>Sending Nokia VCARD SMS to %s, +%s &nbsp;",$Recip,$MobileNumber);
                $output = $mysms->send($MobileNumber,"VCARD",$CardMsg);
                printf($output);
                $UN = $auth->auth["uname"];
                $trk = $mysms->trackingNo;
                if ($output) {
                    $id = $db->nextid("SmsLog_sequence");
                    $sql = "INSERT INTO SmsLog (";
                    $sql .= "id,UserName,Result,RecipientName,RecipientNumber,SenderNumber,Message,MsgType,DateTime,Tracking";
                    $sql .= ") VALUES (";
                    $sql .= "'$id','$UN','$output','$Recip','$MobileNumber','VCARD','$CardMsg','SMS_NOKIA_VCARD',now(),'$trk'";
                    $sql .= ")";
                    $db->query($sql);
                    $OkCount++;
                }
                sleep(2); $RetryCount=0;
                $endBalance = $mysms->getbalance();
                while ($endBalance==$startBalance and $RetryCount<10) {
                   sleep(1);
                   $RetryCount++;
                   $endBalance = $mysms->getbalance();
                }
                if ($RetryCount==10) 
                 echo "<br>Your message was accepted for delivery, but no charge was confirmed.  It may not have been delivered.\n";
                $Charge = $startBalance - $endBalance;
                $Credits -= $Charge; 
	    	printf("<br>%s credits used. %s left.<br>\n",$Charge,$Credits);
                //$db->query("Update Members set SmsCredits='".$Credits."' where UserName='".$auth->auth["uname"]."'");
            }
        }
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to SmsAddressBook.</a><br>\n";
        page_close();
        exit;
   case "Send Message":
	echo "<br><br>";
        $count = 0;
        foreach ($_POST as $key => $value) {
                if (substr($key,0,6)=="SendTo") {
                        $SendTo[$count++] = $value;
                }
        }
	$MsgCount = array_count_values($SendTo);
	$SenderNum="+61432920108";
//	$db->query("select SmsCredits, id, Mobile from Members where UserName='".$auth->auth["uname"]."'");
//	if ($db->next_record()) {
//		$Credits = $db->f(0);
//		$MemberID = $db->f(1);
//		$SenderNum = $db->f(2);
//        } else {
//                $Credits = 0;
//                echo "Can't find how many credits you have left. - Program Error please tell webmaster@nass.com.au";
//                page_close();
//                exit;
//        }
	$Credits=99;
	$OkCount = 0;
	if ($Flash=='Send as Flash Message') $Flash=true; else $Flash=false;
	if ($Flash) $MsgType = "SMS_FLASH"; else $MsgType="SMS_TEXT";
	if (($Credits>$MsgCount) || (!$perm->have_perm("guest"))) {
	    $mysms = new sms();
	    $startBalance = $mysms->getbalance();
	    foreach ($SendTo as $Recipient) {
		$db->query("select * from SmsAddressBook where id='".$Recipient."'");
		if ($db->next_record()) {
			$name = $db->f("Name");
			$cc = $db->f("CountryCode");
			$ac = $db->f("AreaCode");
			$num = $db->f("Number");
			printf("<br>Sending SMS to %s, +%s %s %s ",$name,$cc,$ac,$num);
			$MobileNumber = $cc.$ac.$num;
	                $output = $mysms->send($MobileNumber,$SenderNum,$Message,$Flash);
	                printf($output);
	                $UN = $auth->auth["uname"];
	                $trk = $mysms->trackingNo;
	                if ($output) {
	                    $id = $db->nextid("SmsLog_sequence");
	                    $sql = "INSERT INTO SmsLog (";
                    $sql .= "id,UserName,Result,RecipientName,RecipientNumber,SenderNumber,Message,MsgType,DateTime,Tracking";
	                    $sql .= ") VALUES (";
                    $sql .= "'$id','$UN','$output','$name','$MobileNumber','$SenderNum','$Message','$MsgType',now(),'$trk'";
	                    $sql .= ")";
	                    $db->query($sql);
	                    $OkCount++;
	                }

		} 
	    } // for each
            sleep(2); $RetryCount=0;
            $endBalance = $mysms->getbalance();
            while ($endBalance==$startBalance and $RetryCount<10) {
                   sleep(1);
                   $RetryCount++;
                   $endBalance = $mysms->getbalance();
            }
            if ($RetryCount==10)
                echo "<br>Your message was accepted for delivery, but no charge was confirmed.  It may not have been delivered.\n";
            $Charge = $startBalance - $endBalance;
            $Credits -= $Charge;
            printf("<br>%s credits used. %s left.<br>\n",$Charge,$Credits);
	//    $db->query("Update Members set SmsCredits='".$Credits."' where UserName='".$auth->auth["uname"]."'");
	} else {
		echo "Insufficient SMS Credits.  You need to buy more SMS Credits.";
		

	}
	echo "<br><br>";
   case "View":
   case "Back":
        // echo "<META HTTP-EQUIV=REFRESH CONTENT=\"0; URL=".$sess->self_url()."\">";
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to SmsAddressBook.</a><br>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to SmsAddressBook.</a><br>\n";
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
	echo "<font class=bigTextBold>$cmd Sms Address Book</font>\n";
	$f->display();
	break;
    case "Query":
	$t = new SmsAddressBookTable;
	$t->heading = 'on';
	$t->add_extra = 'on';

        echo "&nbsp<a href=\"".$sess->self_url()
		.$sess->add_query(array("cmd"=>"Add"))."\">Add Sms Address Book</a>&nbsp\n";
        echo "&nbsp<a href=\"".$sess->url("/index.php")."\">Home</a>&nbsp\n";
	echo "<font class=bigTextBold>$cmd Sms Address Book</font>\n";

	// These fields will be searchable and displayed in results.
	// Format is "RealFieldName"=>"Field Name Formatted For Display",
     if ($perm->have_perm("admin")) {
	$t->fields = array(
			"UserName",
			"Name",
			"CountryCode",
			"AreaCode",
			"Number");
        $t->map_cols = array(
			"UserName"=>"UserName",
			"Name"=>"Name",
			"CountryCode"=>"Country Code",
			"AreaCode"=>"Area Code",
			"Number"=>"Number");
     } else {
        $t->fields = array(
                        "Name",
                        "CountryCode",
                        "AreaCode",
                        "Number");
        $t->map_cols = array(
                        "Name"=>"Name",
                        "CountryCode"=>"Country Code",
                        "AreaCode"=>"Area Code",
                        "Number"=>"Number");
     }
  // When we hit this page the first time,
  // there is no .
  if (!isset($q)) {
    $q = new SmsAddressBook_Sql_Query;     // We make one
    $q->conditions = 1;     // ... with a single condition (at first)
    $q->translate  = "on";  // ... column names are to be translated
    $q->container  = "on";  // ... with a nice container table
    $q->variable   = "on";  // ... # of conditions is variable
    $q->lang       = "en";  // ... in English, please

    $sess->register("q");   // and don't forget this!
  }

  if (isset($rowcount)) {
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
if ($perm->have_perm("admin"))
  $db->query("SELECT COUNT(*) as total from SmsAddressBook where ".$query);
else
  $db->query("SELECT COUNT(*) as total from SmsAddressBook where UserName='".$auth->auth["uname"]."' and ".$query);
  $db->next_record();
  if ($db->f("total") < ($q->start_row - $q->row_count))
      { $q->start_row = $db->f("total") - $q->row_count; }

  if ($q->start_row < 0) { $q->start_row = 0; }

  if (!$sortorder) $sortorder="id";
  $query .= " Order By ".$sortorder." LIMIT ".$q->start_row.",".$q->row_count;

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
if ($perm->have_perm("admin"))
    $db->query("select * from SmsAddressBook where ". $query);
else
    $db->query("select * from SmsAddressBook where UserName='".$auth->auth["uname"]."' and ". $query);

    // Dump the results (tagged as CSS class default)
    printf("Query Results = %s<br>\n", $db->num_rows());
    $t->show_result($db, "default");
  }
  default:
//	$db->query("select SmsCredits, id from Members where UserName='".$auth->auth["uname"]."'");
//        if ($db->next_record()) {
//      	        $Credits = $db->f(0);
//               	$MemberID = $db->f(1);
//       	} else $Credits = 0;
//	printf("You Have %d PrePaid SMS Messages Left",$Credits);
//        echo "&nbsp<a href=\"".$sess->url("items.php")
//                .$sess->add_query(array("category"=>"SMS"))."\">Buy More Now</a>&nbsp\n";	
	$Credits=100;

    echo "<h3>Short Message Service</h3>";
//    $db->query("select Mobile from Members where UserName='".$auth->auth["uname"]."'");
    $From = "";
//    if ($db->next_record()) {

//      $From = $db->f(0); 
	$From = "+61432920108";

      if ($From) {
	echo "<hr><b>Step 1.</b> Add Entries to your Address Book";
	$cmd="Add";
	$f->display();
	echo "<hr>";
	$db->query("SELECT COUNT(*) as total from SmsAddressBook where UserName='".$auth->auth["uname"]."'");
	if ($db->next_record()) {
	    $total = $db->f(0); $count=0;
	    $sql = "select * from SmsAddressBook where UserName='".$auth->auth["uname"]."' Order By Name";
	    $db->query($sql);
	    while ($db->next_record()) {
		$count++;
		$name[$count] = $db->f("Name");
		$nameId[$count] = $db->f("id");
		$no[$count] = sprintf("+%s %s %s",$db->f("CountryCode"),$db->f("AreaCode"),$db->f("Number"));
	    }
	    echo "<b>Step 2.</b> Select your recipients";
            printf("<form action=%s method=post name=SmsForm onsubmit='return SmsFormValidator(this,%d)'>",
                        $sess->self_url(),$count);
            if ($count>35) $k=6; else $k=10;
            if ($count>55) $k=4;
            echo "<table cellpadding=$k>\n";
            if ($count<8) {
                for ($i=1; $i<=$count; $i++) {
                        printf("<tr><td><input name=SendTo%d type=checkbox value='%s'>%s ".
                                "<a href='%s'><img src=/images/edit.jpg alt='%s' border=0></a>".
                                "<a href='%s'><img src=/images/delete.jpg border=0></a></td></tr>\n",$i,$nameId[$i],$name[$i],
                                $sess->self_url().$sess->add_query(array("cmd"=>"Edit","id"=>$nameId[$i])),$no[$i],
                                $sess->self_url().$sess->add_query(array("cmd"=>"Delete","id"=>$nameId[$i])));
                }
            } else {
                if ($count>35) $k=5; else $k=4;
                $j = floor($count / $k)+1;
                for ($i=1; $i<=$j; $i++) {
                        printf("<tr><td><input name=SendTo%d type=checkbox value='%s'>%s ".
                                "<a href='%s'><img src=/images/edit.jpg alt='%s' border=0></a>".
                                "<a href='%s'><img src=/images/delete.jpg border=0></a></td>\n",$i,$nameId[$i],$name[$i],
                                $sess->self_url().$sess->add_query(array("cmd"=>"Edit","id"=>$nameId[$i])),$no[$i],
                                $sess->self_url().$sess->add_query(array("cmd"=>"Delete","id"=>$nameId[$i])));
                    if ($name[$i+$j*1])
                        printf("<td><input name=SendTo%d type=checkbox value='%s'>%s ".
                                "<a href='%s'><img src=/images/edit.jpg alt='%s' border=0></a>".
                        "<a href='%s'><img src=/images/delete.jpg border=0></a></td>\n",$i+$j*1,$nameId[$i+$j*1],$name[$i+$j*1],
                                $sess->self_url().$sess->add_query(array("cmd"=>"Edit","id"=>$nameId[$i+$j*1])),$no[$i+$j*1],
                                $sess->self_url().$sess->add_query(array("cmd"=>"Delete","id"=>$nameId[$i+$j*1])));
                    if ($name[$i+$j*2])
                        printf("<td><input name=SendTo%d type=checkbox value='%s'>%s ".
                                "<a href='%s'><img src=/images/edit.jpg alt='%s' border=0></a>".
                           "<a href='%s'><img src=/images/delete.jpg border=0></a></td>\n",$i+$j*2,$nameId[$i+$j*2],$name[$i+$j*2],
                                $sess->self_url().$sess->add_query(array("cmd"=>"Edit","id"=>$nameId[$i+$j*2])),$no[$i+$j*2],
                                $sess->self_url().$sess->add_query(array("cmd"=>"Delete","id"=>$nameId[$i+$j*2])));
                    if ($name[$i+$j*3])
                        printf("<td><input name=SendTo%d type=checkbox value='%s'>%s ".
                                "<a href='%s'><img src=/images/edit.jpg alt='%s' border=0></a>".
                      "<a href='%s'><img src=/images/delete.jpg border=0></a></td>\n",$i+$j*3,$nameId[$i+$j*3],$name[$i+$j*3],
                                $sess->self_url().$sess->add_query(array("cmd"=>"Edit","id"=>$nameId[$i+$j*3])),$no[$i+$j*3],
                                $sess->self_url().$sess->add_query(array("cmd"=>"Delete","id"=>$nameId[$i+$j*3])));
                    if ($name[$i+$j*4])
                        printf("<td><input name=SendTo%d type=checkbox value='%s'>%s ".
                                "<a href='%s'><img src=/images/edit.jpg alt='%s' border=0></a>".
                      "<a href='%s'><img src=/images/delete.jpg border=0></a></td></tr>\n",$i+$j*4,$nameId[$i+$j*4],$name[$i+$j*4],
                                $sess->self_url().$sess->add_query(array("cmd"=>"Edit","id"=>$nameId[$i+$j*4])),$no[$i+$j*4],
                                $sess->self_url().$sess->add_query(array("cmd"=>"Delete","id"=>$nameId[$i+$j*4])));

                    else
                        printf("</tr>");
                }
            }
                if (!$EventDate) $EventDate = date("Y-m-d H:i:00");
	    echo "</table>\n<br><b>Step 3.</b> Type your message up to 160 characters<br>";
	    echo "<textarea rows=5 cols=60 name=Message onKeyUp='textCounter(this.form.Message,this.form.remLen,160);'>";
	    echo $Message;
	    echo "</textarea>\n<br><input readonly type=text name=remLen size=3 maxlength=3 value=160> characters left";
	    echo "<input type=hidden name=From value='".$From."'>";
	    echo "&nbsp;&nbsp;&nbsp;&nbsp;<input type=submit name=submit value='Send Message'>";
	    echo "<br><input type=checkbox name=Flash value='Send as Flash Message'>";
	    echo "Send as Flash Message <small>(so it doesn't get saved in receivers inbox).</small>";
//	    echo "<br><input type=checkbox name=Debug value='DebugMode'>";
//	    echo "Debug Mode <small>(Show Form Variables instead of Posting to EvansCorp).</small>";
//	    echo "<br><input type=checkbox name=FullMode value='FullHtml'>";
//	    echo "Full HTML <small>(Don't strip HTML Tags from output).</small>";
	    echo "</form>\n";
	} 

	$cmd = "";
      } else $cmd="Edit";
//    } else $cmd="Add";
    if ($cmd) {
	$QUERY_STRING="";
	echo "You mobile number has not been set.&nbsp;Please <a href='";
	echo $sess->url("members.php").$sess->add_query(array("cmd"=>$cmd));
	echo "'>".$cmd."</a> your details.";
    }
} // switch $cmd
page_close();
?>

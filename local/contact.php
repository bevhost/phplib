<?php
include($DOCUMENT_ROOT.'/phplib/prepend.php');

page_open(array("sess"=>$_ENV["SessionClass"]));

include("phplib/subnav.inc");

echo "<script language=JavaScript>
function DoCustomChecks(f) {
        return true;
}
</script> \n";

$db = new $_ENV["DatabaseClass"];
echo "<h2>Contact Us</h2>";

$f = new contactform;

echo "<h4>$submit</h4>";

$domain_name=str_replace("www.","",$SERVER_NAME);

if ($submit) {
  switch ($submit) {

   case "Submit":
   case "Save":
    $submit = "Add";
   case "Add":
     if (!$f->validate($result)) {
        $cmd = $submit;
        echo "<font class=bigTextBold>$cmd contact</font>\n";
        $f->display();
	echo "</div>";
        page_close();
        exit;
     }
     else
     {
        $f->save_values();
        echo "<b>Thank you!</b> someone will contact you soon.<br>\n";
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"10; URL=/contact.html\">";
        echo "&nbsp<a href=\"/contact.html\">Back to contact.</a><br>\n";
	echo "</div>";
        page_close();
$add = "-fwebmaster@$domain_name";
$hdr = "From: webmaster@$domain_name";
$to = "info@$domain_name";

$Comment = "

Name:     $Name
Phone:    $Phone
Email:    $Email

$Message

-------------------------------------------
Browser: $HTTP_USER_AGENT
Forwarded For: $HTTP_X_FORWARDED_FOR
Via Proxy: $HTTP_VIA
Direct IP Address: $REMOTE_ADDR
Referred By: $HTTP_REFERER
";


       	mail ($to,$Subject,$Comment,$hdr,$add);

        exit;
     }
  }
}
echo "<font class=bigTextBold>Send us an email</font>\n";
$f->display();
echo "</div>";
page_close();
?>

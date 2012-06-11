<?php
include($DOCUMENT_ROOT.'/phplib/prepend.php');

page_open(array("sess"=>"shop_Session","auth"=>"shop_Auth","perm"=>"shop_Perm"));
echo "<link rel=stylesheet href=/html4.css>";

include("toplink.inc");

$path = "/var/log/mail";
if ($year) $path .= "/".$year."/".$month."/".$day."-".$month.".maillog";
else $path .= "log";

echo "<pre>\n";
system("tail -n 50000 ".$path." | grep ".$domain." | grep 'vweb postfix' | sed 's/</\&lt;/g' | sed 's/>/\&gt;/g'");
echo "</pre>\n";

page_close();
?>

<?php
include('phplib/prepend.php');

page_open(array("sess"=>"hotspot_Session","auth"=>"hotspot_Auth","perm"=>"hotspot_Perm"));
#echo "<link rel=stylesheet href=/html4.css>";

check_view_perms();

include("postfix.inc");

function ListDomains($db,$sql,$title) {
  global $sess;
  $db2 = new DB_postfix;
  $db->query($sql);
  echo "<tr><td colspan=4><h3>$title</h3></td></tr>\n";
  $count=0;
  while ($db->next_record()) {
   if ($domain=$db->f(0)) {
	$count++;
	$db2->query("select count(*) from postfix_vacation where email like '%".$domain."'");
	$db2->next_record(); $vacount = $db2->f(0);
	$db2->query("select count(*) from postfix_mailbox where domain='".$domain."'");
	$db2->next_record(); $mbcount = $db2->f(0);
	$db2->query("select count(*) from postfix_virtual where email='@".$domain."'");
	$db2->next_record(); $cacount = $db2->f(0);
	$db2->query("select count(*) from postfix_virtual where email like '%".$domain."'");
	$db2->next_record(); $fwcount = $db2->f(0);
	$query = $sess->add_query(array("domain"=>$domain));
	printf("<tr><td>%s</td><td><a href=%s%s>Mailboxes (%d)</a></td>".
		"<td><a href=%s%s>Forwards (%d)</a></td><td><a href=%s%s>Vacation (%d)</a></td>\n",
		$domain,
		$sess->url("postfix_mailbox.php"),$query,$mbcount,
		$sess->url("postfix_virtual.php"),$query,$fwcount,
		$sess->url("Vacation.php"),$query,$vacount
	      );
	printf("<td><a href=%s>Logs</a></td>",
		$sess->url("postfix_logs.php").$sess->add_query(array("domain"=>$domain))
	      );
	if ($cacount) {
		printf("<td>&nbsp;&nbsp;<a href=%s>Recipient Access</a></td>",
			$sess->url("postfix_recip_access.php").$sess->add_query(array("domain"=>$domain))
		      );
	}
	echo "</tr>\n";
    }
  }
}
$sql = "select distinct EmailDomain from ServiceLocation where Location in ($Locations) order by EmailDomain";
# echo $sql;
echo "<table cellspacing=2>\n";
$db = new DB_hotspot;
$pf = new DB_postfix;
if ($perm->have_perm("admin")) {
#	echo "<a href=".$sess->url("mailq.php").">Mail Queue</a><br>";
	$sql = "select distinct EmailDomain from ServiceLocation order by EmailDomain";
	ListDomains($db,$sql,"HotSpot Domains");
	$sql = "select domain from postfix_virtual_domains";
	ListDomains($pf,$sql,"Local Mailbox Domains");
	$sql = "select domain, destination from postfix_transport";
	$pf->query($sql);
	echo "<tr><td colspan=4><h3>Domain Routing</h3></td></tr>\n";
	while ($pf->next_record()) {
		echo "<tr><td>".$pf->f(0)."</td><td colspan=3> is set up to route to ".$pf->f(1)."</td></tr>\n";
	}
}
echo "</table>\n";
if (!$count) echo "<h4>You have no email domains to administer here.</h4>";
page_close(array());
?>

<?php global $perm, $auth;
extract($_REQUEST);
$db = new DB_hotspot;
$db2 = new DB_postfix;
$domains = array();
$found = 0;
if ($perm) { 
    if ($perm) if ($perm->have_perm("admin")) { ?>
    <table><tr><td><br/><b>Postfix Admin:</b><br/></td></tr>
    <tr><td>MailBox:</td><td>
    &nbsp;<a href="<?php $sess->purl("/postfix_virtual_domains.php") ?>" title='Domains we host mailboxes for'>Domains</a>&nbsp;&nbsp;
    &nbsp;<a href="<?php $sess->purl("/postfix_mailbox.php") ?>" title='POP3/IMAP accounts'>MailBox</a>&nbsp;&nbsp;
    &nbsp;<a href="<?php $sess->purl("/postfix_vacation.php") ?>" title='Under Construction'>Vacation</a>&nbsp;&nbsp;</td></tr>
    <tr><td>Routing:</td><td>
    &nbsp;<a href="<?php $sess->purl("/postfix_transport.php") ?>" title='Domains we route to customers'>Domains</a>&nbsp;&nbsp;
    &nbsp;<a href="<?php $sess->purl("/postfix_virtual.php") ?>" title='email address aliases we translate/forward'>Users (aliases)</a>&nbsp;&nbsp;
    <tr><td>Access:</td><td>
    &nbsp;<a href="<?php $sess->purl("/postfix_client_access.php") ?>" title='Postfix Relay Control for Our Internal Customers'>Internal IP Ranges</a>&nbsp;&nbsp;
    &nbsp;<a href="<?php $sess->purl("/postfix_external_access.php") ?>" title='Postfix White / Black List with Custom Errors for general internet users by IP Address or Reverse DNS Name'>External IP or PTR</a>&nbsp;&nbsp;
    &nbsp;<a href="<?php $sess->purl("/postfix_recip_access.php") ?>" title='Local Recip Filtering for Relay Domains'>Recipient</a>&nbsp;&nbsp;
    &nbsp<a href="<?php $sess->purl("/postfix_sender_access.php") ?>" title="Known Bad Senders">Sender</a>&nbsp;&nbsp;</td></tr>
    <tr><td>Policy:</td><td>
&nbsp;<a href="<?php $sess->purl("/postfix_blacklist.php") ?>" title='All Server Blacklist'>blacklist IP</a>&nbsp;&nbsp;
&nbsp;<a href="<?php $sess->purl("/postfix_whitelist.php") ?>" title='Do Not Greylist'>whitelist IP</a>&nbsp;&nbsp;
&nbsp;<a href="<?php $sess->purl("/postfix_blacklist_sender.php") ?>" title='Blacklist single user or domain from IP'>blacklist sender@IP</a>&nbsp;&nbsp;
&nbsp;<a href="<?php $sess->purl("/postfix_whitelist_sender.php") ?>" title='Whitelist single user or domain from IP'>whitelist sender@IP</a>&nbsp;&nbsp;
&nbsp;<a href="<?php $sess->purl("/postfix_spamtrap.php") ?>" title='Bait spammers with old mailboxes'>spamtrap</a>&nbsp;&nbsp;
&nbsp;<a href="<?php $sess->purl("/postfix_throttle.php") ?>" title='See who has been throttled'>throttle</a>&nbsp;&nbsp;
&nbsp;<a href="<?php $sess->purl("/postfix_triplet.php") ?>" title='Current triplet counters'>triplet</a>&nbsp;&nbsp;
&nbsp;<a href="<?php $sess->purl("/postfix_clearances.php") ?>" title='who has cleared their address'>clearances</a>&nbsp;&nbsp;
</td></tr>
</table>
    <?php  
        } else {
                ?>
                        &nbsp<a href="<?php $sess->purl("/postfix.php") ?>">Postfix Admin</a>&nbsp;&nbsp;
                <?php
        }
/*
    $db->query("select DomainName from Domains where MemberID='".$MemID."' or RefBy='".$MemID."'");
    while ($db->next_record()) {
        if (($PHP_SELF != '/postfix.php') and (!$domain)) $domain=$db->f(0);
        if ($domain==$db->f(0)) $found += 1;
        array_push($domains,$db->f(0));
    }
*/
    if (!$perm->have_perm("admin")) {
      if ($domain) {
        if ($found>0) {
                echo $domain;
        } else {
                echo $domain." is not listed as one of your domains.";
                page_close();
                exit;
        }
      }
    }
} else {
        $db2->query("select * from postfix_mailbox where email='".$username."' and clear='".$password."'");
        if ($db2->next_record()) {
                $found = 1;
        } else {
                ?>
                        <p>&nbsp;</p>
                        <h1 align=center>Change Vacation Settings</h2>
                        <p align=center><form method=post><table cellspacing=20  align=center>
                                <tr><td>Mailbox Username </td><td><input name=email></td></tr>
                                <tr><td>Mailbox Password </td><td><input name=password type=password></td></tr>
                                <tr><td></td><td><input type=submit name=cmd value=Login></td></tr>
                        </table></form></p>
                        <p>&nbsp;</p>
                <?php
                        page_close();
                        exit;
        }
}
?>

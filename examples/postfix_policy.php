<?php
include('phplib/prepend.php');

page_open(array("sess"=>"hotspot_Session","auth"=>"hotspot_Auth","perm"=>"hotspot_Perm"));

?>

<table width=90% align=center><tr><td>

<h1>Postfix Local Policies</h1>
<p><b>Grey Listing</b> is generally a way of limiting spam but rejecting emails on the first attempt.</p>
<p><b>Rate Limiting</b> slows down senders who try to send too many emails too fast.</p>
<?php
if ($perm->have_perm("admin")) {
?>
<h4><a href="<?php $sess->purl("/blacklist.php") ?>">Blacklist IP</a> - block a server or client</h4>
<p>Anything coming from this IP address will be blocked</p> 

<h4><a href="<?php $sess->purl("/whitelist.php") ?>">Whitelist IP</a> - permit a server or client</h4>
<p>Anything coming from this IP address will not be subjected to policy controls.</p> 

<h4><a href="<?php $sess->purl("/blacklist_sender.php") ?>" >Blacklist Sender @ IP</a> - block senders from somewhere</h4>
<p></p> 

<h4><a href="<?php $sess->purl("/whitelist_sender.php") ?>" >Whitelist Sender @ IP</a> - permit senders from somewhere</h4>
<p></p> 

<h4><a href="<?php $sess->purl("/spamtrap.php") ?>" >Spam Trap</a> - unused email address bait</h4>
<p></p> 

<h4><a href="<?php $sess->purl("/throttle.php") ?>" >Throttle</a> - counters of who's sent how much</h4>
<p></p> 

<h4><a href="<?php $sess->purl("/triplet.php") ?>" >Triplet</a> - known sender/recip/address patterns</h4>
<p></p> 

<h4><a href="<?php $sess->purl("/clearances.php") ?>" >Clearances</a> - who's cleared ther own blocks</h4>
<p></p> 

<?php } ?>

</table>

<?php
page_close();
?>

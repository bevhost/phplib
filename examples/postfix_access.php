<?php
include('phplib/prepend.php');

page_open(array("sess"=>"hotspot_Session","auth"=>"hotspot_Auth","perm"=>"hotspot_Perm"));

?>

<table width=90% align=center><tr><td>

<h1>Postfix Access Controls</h1>
<?php
if ($perm->have_perm("admin")) {
?>
<h4><a href="<?php $sess->purl("/postfix_client_access.php") ?>">Client Access</a> - permit or block a server or client</h4>
<p></p> 

<h4><a href="<?php $sess->purl("/postfix_recip_access.php") ?>">Recipient Access</a> - permit or block recipients</h4>
<p></p> 

<h4><a href="<?php $sess->purl("/postfix_sender_access.php") ?>" >Sender Access</a> - permit or block senders</h4>
<p></p> 

<?php } ?>

</table>

<?php
page_close();
?>

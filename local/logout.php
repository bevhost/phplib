<?php
include('phplib/prepend.php');

page_open(array("sess"=>$_ENV["SessionClass"],"auth"=>$_ENV["AuthClass"],"perm"=>$_ENV["PermClass"]));

$auth->logout();
if ($cart) $cart->reset();
$sess->unregister("loggedIn");
$back = $_SERVER["HTTP_REFERER"];
if (!$back) $back="index.php";
?>
<p><br /></p>
<p><br /></p>
<p><br /></p>
<p><br /></p>
<p><br /></p>
<a href="javascript:window.close();">Close This Window</a>
<font class=bigTextBold>Logged out</font>
<a href=<?php echo $back; ?>>Log back in</a>
<br><br>
<?php page_close(); ?>

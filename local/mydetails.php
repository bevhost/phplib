<?php
include('phplib/prepend.php');

if ( isset($HTTPS) && $HTTPS == 'on' ) { $PROTOCOL='https'; } else { $PROTOCOL='http'; }
$this_web_site = $PROTOCOL. "://".$_SERVER["HTTP_HOST"];

switch ($_REQUEST["mode"]) {
	case 'register':
    		page_open(array("sess"=>$_ENV["SessionClass"],"silent"=>"silent"));
		echo '<link rel="stylesheet" href="/css/layout.css" type="text/css"/><div id=Delivery>';
		break;
	case 'delivery':
    		page_open(array("sess"=>$_ENV["SessionClass"],"auth"=>$_ENV["AuthClass"],"perm"=>$_ENV["PermClass"]));
		break;
	default:
    		page_open(array("sess"=>$_ENV["SessionClass"],"auth"=>$_ENV["AuthClass"],"perm"=>$_ENV["PermClass"],"silent"=>"silent"));
		echo '<link rel="stylesheet" href="/css/layout.css" type="text/css"/><div id=Delivery>';

}

$formclass = $_ENV["UserDetailsTable"]."form";
$f = new $formclass;

$db = new $_ENV["DatabaseClass"];

if ($auth) {
    if (!isset($UserDetailsId)) {  // Not sure why this is needed now...
	$sql = "select id from ".$_ENV["UserDetailsTable"];
	$sql .= " where ".$_ENV["UserAuthIdField"]."='".$auth->auth["user_id"]."'";
	$db->query($sql);
	if ($db->next_record()) {
		$UserDetailsId=$db->f(0);
		$sess->register("UserDetailsId");
		echo "\n<script>showcart();</script>\n";
	}
    }
}

if ($submit) {
  switch ($submit) {

   case "Save":
        if ($id) $submit = "Edit";
        else {
		$_EmailAddressField = $_ENV["UserEmailAddressField"];
		$EmailAddress = $_REQUEST[$_EmailAddressField];
		$sql = "select id from ".$_ENV["UserDetailsTable"];
		$sql .= " where `$_EmailAddressField`='$EmailAddress'";
		$db->query($sql);
		if ($db->next_record()) {
			echo "<p class=error>Account already exists with email $EmailAddress</p>";
        		echo "&nbsp<a href=\"javascript:history.go(-1)\">Back to re-edit.</a><br>\n";
        		echo "&nbsp<a href=\"/delivery.php\">Goto Login.</a><br>\n";
			page_close(array("silent"=>"silent"));
			exit;
		}
		$submit = "Add";
	}
   case "Add":
   case "Edit":
	switch (strtolower($_ENV["RegisterMode"])) {
		case "auto":	/* Permit instant login or no login and use PayPal IPN to create registration */
        		echo "<META HTTP-EQUIV=REFRESH CONTENT=\"0; URL=$url\">";
			$email_recip = $EmailAddress;
			$url = $sess->url("delivery.php");
			$url .= $sess->add_query(array("username"=>$EmailAddress,"password"=>$Password,"mode"=>"mini"));
			$display = "Continue...";
			break;
		case "approve":		/* Send email to webmaster and have them forward it if approved */
			$email_recip = "webmaster@".$_ENV["Domain"];
			$display = "Webmaster will email you a password upon approval of your registration";
			$url = $sess->url("delivery.php");
			break;
		case "checkemail":	/* Send email with password to verify email address before login */
			$email_recip = $EmailAddress;
			$url = $sess->url("delivery.php");
			$display = "Please check your email to find your password to login.";
			break;


			break;
		default: 
			echo "Site Error: Webmaster has not set the RegisterMode variable";
	} //switch
			if ($submit=='Add') $message = "
Thankyou for creating an account at ".$_SERVER["HTTP_HOST"].".

$this_web_site

Your username is $EmailAddress
Your password is $Password

best regards,
webmaster";
	$subj = "New ".$_ENV["BaseName"]." Account";
	mail ($email_recip,$subj,$message);
        $f->save_values();
        echo "Saving....";
        echo "<b>Done!</b><br>\n";
        echo "&nbsp<a href=\"$url\">$display</a><br>\n";
        page_close(array("silent"=>"silent"));
        exit;
  }
}
if ($UserDetailsId) {
	$f->find_values($UserDetailsId);
}
$f->display();
switch ($mode) {
	case 'delivery':
		page_close(array("silent"=>"silent"));
		echo "\n</div>\n<script>window.top.showcart();</script>\n";
	default:
		page_close();
}
?>

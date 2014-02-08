<?php
include('phplib/prepend.php');
page_open(array("sess" => $_ENV["SessionClass"], "auth" => $_ENV["AuthClass"], "perm" => $_ENV["PermClass"]));

$sess->that->db->Record = false;

foreach($sess->pt as $thing=>$val) {
	switch($thing) {
#		case "cart":	/* some things are important */
		case "auth":
		case "loggedIn":
			break;
		default:
			echo "<br>Unsetting $thing";
			unset($$thing);
			unset($sess->pt[$thing]);
	}
}

page_close();
?>

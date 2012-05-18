<?php
include('phplib/prepend.php');

page_open(array("sess"=>$_ENV["SessionClass"],"auth"=>$_ENV["AuthClass"],"perm"=>$_ENV["PermClass"],"silent"=>"silent"));

get_request_values("val,index,col,table,key");

switch($table) {
	default: $requires = "admin"; break;
	case "menu": $requires = "admin"; break;
}
if (!$perm->have_perm($requires)) {
	echo "Access Denied";
} else {

	$db = new $_ENV["DatabaseClass"];
	$v = urldecode($val);
	$i = urldecode($index);

	// Get Old Value
	$db->query("SELECT $col FROM $table WHERE $key='$i'");
	if ($db->next_record()) $oldval=$db->f(0);

	// Set New Value
	$db->query("UPDATE $table SET $col='$v' WHERE $key='$i'");
	EventLog("Cell Edit by ".$auth->auth["uname"],$sql);

	// Get New Value
	$db->query("SELECT $col FROM $table WHERE $key='$i'");
	if ($db->next_record()) echo $db->f(0);
	else echo $oldval;
}

page_close(array("silent"=>"silent"));
?>

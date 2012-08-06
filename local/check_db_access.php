<?php 

$username = "admin";
$password = "phplib";

require("phplib/prepend.php");

if (!$db->connect()) {
	echo "connect failed\n";
} else
if (!$tables = $db->table_names()) {
	echo "Can't read table names\n";
} else {
	$str = count($tables). " tables found in db $db->Database on $db->Server server $db->Host\n"; 
	echo $str;
	EventLog("Test DB",$str);
	if ($db->query("SELECT count(*) FROM auth_user")) {
		if ($db->next_record()) {
			if ($db->f(0)==0) {
				# no users in auth_user, let's make one.
				$id = md5(uniqid("somestring"));
				$hashpass = hash_auth($username,$password);
				if ($db->query("INSERT INTO auth_user (user_id, username, password, perms) VALUES ('$id','$username','$hashpass','admin')")) {
					echo "Created admin user with password $password\n";
				}				
			}
		}
	}
}


?>

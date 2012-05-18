<?php
include("phplib/prepend.php");

if ($_SERVER["DOCUMENT_ROOT"]) {
	page_open(array("sess" => $_ENV["SessionClass"], "auth" => $_ENV["AuthClass"], "perm" => $_ENV["PermClass"]));
	echo "<h2>Menu Audit</h2>\n";
	echo "<h5>Verifying that php program files have the functions necessary to check for the permissions given in the menus</h5>\n";
	echo "<pre>\n";
}

$db = new $_ENV["DatabaseClass"];

// $verbose = true;

$count=0;
$db->query("select target, view_requires, edit_requires  from menu where target like '%.php' and target not like 'http%'");
while ($db->next_record()) {
    if (file_exists($db->f(0))) {
	if ($fp = fopen($db->f(0),"r")) {
		$count++;
		$view_found=false;
		$edit_found=false;
		while (!feof($fp)) {
			$line = fgets($fp,1000);
			if ($line=="check_view_perms();\n") $view_found=true;
			if (strpos($line,"check_view_perms(")) $view_found=true;
			if (strpos($line,"check_edit_perms(")) $edit_found=true;
		}
		fclose($fp);
		if ($db->f(1)) { 
			if (!$view_found) echo "check_view_perms() Not found in ".$db->f(0)."\n";
		} else {
			if ($view_found) if ($verbose) echo "Found but not required in ".$db->f(0)."\n";
		}
		if ($db->f(2)) {
			if (!$edit_found) echo "####> check_edit_perms() Not found in ".$db->f(0)."\n";
		}
	} else echo "Can't Open ".$db->f(0)."\n";
    } else echo "----------------------------------------------> ".$db->f(0)." not found!\n";
}
echo "$count php files checked.\n";
$count=0;
if ($_SERVER["DOCUMENT_ROOT"]) {
	echo "</pre><h5>Verifying that html files exist</h5><pre>";
}
$db->query("select target, view_requires, edit_requires from menu where target like '%.html' and target not like 'http%'");
while ($db->next_record()) {
    if (!file_exists($db->f(0))) {
	$count++;
	echo "----------------------------------------------> ".$db->f(0)." not found!\n";
    }
}
echo "$count html files checked.\n";

if ($_SERVER["DOCUMENT_ROOT"]) {
	echo "</pre><a href='MenuEditor.php'>Menu Editor</a>";
	page_close();
}

?>

<?php 
include('phplib/prepend.php');

$page = $_REQUEST["page"];

if (!empty($_ENV["view_requires"])) {
    page_open(array("sess"=>$_ENV["SessionClass"], "auth"=>$_ENV["AuthClass"], "perm"=>$_ENV["PermClass"]));
} else {
    page_open(array("sess"=>$_ENV["SessionClass"]));
}
$html = new Template($_ENV["local"]."/templates","comment");

if ($page) {

    switch ($page) {
	// public template pages without subnav menu.
	case "indexxxx":

		break;
	default:
		$self = $page.".html";
		include ("phplib/subnav.inc");

    }

    $html->set_file($page,$page.".html");
    echo $html->subst($page);
}
else echo "incorrect usage:- page not found.";

page_close();
?>


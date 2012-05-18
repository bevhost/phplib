<?php
include('phplib/prepend.php');
page_open(array("sess" => $_ENV["SessionClass"], "auth" => $_ENV["AuthClass"], "perm" => $_ENV["PermClass"]));

MenuPage($MenuId);  

page_close();
?>

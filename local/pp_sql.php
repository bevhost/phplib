<?php

include("phplib/prepend.php");
require($_ENV["libdir"]."paypal.inc");

$ipn = new IPN_Agent();
$ipn->tablename_prefix = "pp_";
$ipn->init();
$ipn->_dump_table_layout();

?>

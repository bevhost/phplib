<?php
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache");
header("Cache-Control: post-check=0, pre-check=0");
header("Pragma: no-cache");

include('phplib/prepend.php');
page_open(array("sess"=>$_ENV["SessionClass"],"silent"=>"silent"));
$db = new $_ENV["DatabaseClass"];

if ($product = $_GET["item_key"]) {
        $qty = $_GET["qty"];
        if ($options = $_GET["options"]) foreach ($options as $option) $OptionList[$option] = $_GET[$option];
        $cart->add_item($product,$qty,$OptionList);
        $cart->show_all();
}
if ($_GET["cmd"]=="ShowCart") {
        $cart->show_all();
}
if ($_GET["cmd"]=="EmptyCart") {
        $cart->item = array();
        $cart->currentItem = 1;
        $cart->show_all();
}
if ($product = $_GET["updatecart"]) {
        $qty = $_GET["qty"];
        if ($options = $_GET["options"]) foreach ($options as $option) $OptionList[$option] = $_GET[$option];
        echo "<script>alert('$product $qty');</script>";
        $cart->set_item($product,$qty,$OptionList);
        $cart->show_all();
}


function strtotime_uk($str)
{
   $str = preg_replace("/^\s*([0-9]{1,2})[\/\. -]+([0-9]{1,2})[\/\. -]*([0-9]{0,4})/", "\\2/\\1/\\3", $str);
   $str = trim($str,'/');
   return strtotime($str);
}
function datestr($dt) {
        date_default_timezone_set("Australia/Brisbane");
        $tm = strtotime_uk($dt);
        if ($tm>0) {
               return date("Y-m-d H:i:s",$tm);
        }
        return "unknown";
}
if ($dt = $_GET["dt"]) {
        echo datestr($dt);
}

page_close(array("silent"=>"silent"));
?>

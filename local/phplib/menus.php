<?php 
global $widthTotal;
$widthTotal = 0;

function menu($parent,$indent) {
	global $widthTotal, $sess;
	$db = new $_ENV["DatabaseClass"];
	$sql = "select * from menu where parent=$parent order by position";
	if (!$db->query($sql)) echo "Error(no rows) $sql";;
        if ($parent) if ($db->num_rows()) {
                $start_str = "\n$indent <ul>";
                $end_str = "\n$indent </ul>\n$indent";
        }
	while ($db->next_record()) {
		extract($db->Record);
                $ok = false;
                if ($view_requires) {
                        foreach(explode(",",$view_requires) as $need) {
                                if ($sess->have_perm($need)) $ok = true;
                        }
                } else $ok = true;
                if ($ok) {
                    if ($start_str) {
			echo $start_str;
			$start_str = "";
                    }
		    if ($target=="menu") $target = "menupage.php?MenuId=$id";
		    if (!strpos($target,'://')) $target = "/$target";
		    if ($target=="/ContentEditor.php") {
			if ($_REQUEST["page"]) { $target .= "?page=".$_REQUEST["page"]; $skip=false; } else $skip=true;
		    } else $skip=false;
		    if (!$skip) {
			echo "\n$indent  <li><a href='$target'";
			if (!$parent) {
				if ($_ENV["MenuMode"]=="horiz") echo " class='menu' style='width:".$width."px'";
				$widthTotal += $width;
			}
			echo ">$title</a>";
			menu($id,$indent."  ");
			echo "</li>";
		    }
		}
	}
	if (!$start_str) echo $end_str;
}

echo "<li><a href='/ContentEditor.php?".$_SERVER["QUERY_STRING"]."' id='NavHome'>&nbsp;</a></li>\n";
menu(0,"");
$width = 919 - $widthTotal;
if ($_ENV["MenuMode"]=="horiz") $style = " style='width:".$width."px'"; else $style="";
echo "<li><a href='/MenuEditor.php' id='MenuEnd'$style>&nbsp;</a></li>\n";

?>


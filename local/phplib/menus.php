<?php 
global $q, $widthTotal, $menu;
$widthTotal = 0;

if (substr($_SERVER["REQUEST_URI"],0,strlen($_SERVER["SCRIPT_NAME"]))==$_SERVER["SCRIPT_NAME"]) 
     $ModReWritten=false;
else $ModReWritten = true;

$db = new $_ENV['DatabaseClass'];
$sql = "SELECT * FROM menu";
$db->query($sql);
while ($db->next_record()) {
        extract($db->Record);
	$ok = false;
        if ($view_requires) {
               if ($perm) {
                        foreach(explode(",",$view_requires) as $need) {
                                if ($perm->have_perm($need)) $ok = true;
                        }
                }
        } else $ok = true;
        if ($ok) {
		if ($target=="menu") $target = "menupage.php?MenuId=$id";
		if (substr($target,0,4)<>"http") $target = "/$target";
		$menu[$id]->target = $target;
		$menu[$id]->title = $title;
		$menu[$id]->width = $width;
		$menu[$parent]->children[$position] = $id;
	}
}

function menu($parent,$indent) {
	global $widthTotal, $menu;
	if (!isset($menu[$parent]->children)) return;
	foreach ($menu[$parent]->children as $sortorder => $id) {
		$items[] = $sortorder;
	}
	sort($items);
	if ($parent) echo "\n$indent <ul>";
	foreach($items as $item) {
		$id = $menu[$parent]->children[$item];
		$target = $menu[$id]->target;
		$title = $menu[$id]->title;
		echo "\n$indent  <li><a href='$target'";
		if (!$parent) {
			echo " class='menu'";
			$widthTotal += $menu[$id]->width;
		}
		echo ">$title</a>";
		menu($id,$indent."  ");
		echo "</li>";
	}
	if ($parent) echo "\n$indent </ul>\n$indent";
}

menu(0,"");
if (empty($GLOBALS["widemode"])) $screen = 919; else $screen=1519;
$width = $screen - $widthTotal;
if ($width>199) {
	$fill = $width - 199;
	echo '<li><a href="#" style="width:'.$fill.'px" class="menufill"></a></li>';
	$width = 199;
}

/*
if (isset($q)) {
  if (empty($q->start_row)) { $q->start_row = 0; }
  if ($q->start_row < 1) { $q->start_row = 0; }
  if (empty($q->row_count)) { $q->row_count = 50; }
}
*/

?>

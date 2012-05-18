<?php
        $db->query("select header, subnavhdr, parent, view_requires from menu 
			where target='/$self' or target='$self' order by id desc");
        if ($db->next_record()) {
                extract($db->Record);
                echo "<div id='TemplateTop'></div>";
                if ($header) {
                        echo "<img src='";
                        echo $db->f("header");
                        echo "' class='Header' width='876' height='33' />\n";
                }
                if ($subnavhdr) {
                        echo " <div id='SubNav'>\n";
                        echo "  <div id='SubNavHeader'><h3>$subnavhdr</h3></div>\n  <div id='SubNavContentBg'>\n";
                        $db->query("select title, target, view_requires from menu where parent=$parent order by position");
	                $ok = false;
        	        if ($view_requires) {
                	        foreach(explode(",",$view_requires) as $need) {
                        	        if ($sess->have_perm($need)) $ok = true;
	                        }
        	        } else $ok = true;
                	if ($ok) {
                        	while ($db->next_record()) {
                                	extract($db->Record);
                                	if ($target=="$page.html") $class="class='SubNavContentLinkOn' "; else $class="";
                                	echo "   <p><a $class href='$target'>$title</a></p>\n";
                        	}
			}
                        echo "  </div>\n  <div id='SubNavFooter'></div>\n </div>\n";
                }
        }
	$id = $_REQUEST["id"];
?>

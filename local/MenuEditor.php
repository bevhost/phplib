<?php
include('phplib/prepend.php');

page_open(array("sess"=>$_ENV["SessionClass"],"auth"=>$_ENV["AuthClass"],"perm"=>$_ENV["PermClass"]));

check_view_perms();

$db = new $_ENV["DatabaseClass"];

class MymenuTable extends Table {
  var $classname = "menuTable";

  function table_row_add_extra($row, $row_key, $data, $class="") {
        global $sess;
        $db = new $_ENV["DatabaseClass"];

        echo "<td class='btable'>";
        echo "<a href=\"".$sess->url('MenuEditor.php').
                $sess->add_query(array("cmd"=>"View","id"=>$data["id"]))."\">view</a>";

  if ($sess->have_edit_perm()) {
        echo " <a href=\"".$sess->url('MenuEditor.php').
                $sess->add_query(array("cmd"=>"Copy","id"=>$data["id"]))."\">copy</a>";
        echo " <a href=\"".$sess->url('MenuEditor.php').
                $sess->add_query(array("cmd"=>"Edit","id"=>$data["id"]))."\">edit</a>";
        echo " <a href=\"".$sess->url('MenuEditor.php').
                $sess->add_query(array("cmd"=>"Delete","id"=>$data["id"]))."\">delete</a>";
  }

        if ($this->edit) {
                echo "<input type='submit' value='Save' name='submit' class='ipeh'> ";
                echo "<input type='hidden' value='".$data["id"]."' name='id'> ";
                echo "<input type='hidden' value='".$data["parent"]."' name='parent'> ";
        }
        echo "</td>";
  }
  function table_insert_row_add_extra($data,$class)
  {
	global $parent, $level;
        echo "<td class='btable'>";
        $this->form->form_data->show_element('submit','Add');
        $this->form->form_data->elements["submit"]["ob"]->extrahtml = "onclick='this.form.onsubmit=\"\"'";
        $this->form->form_data->show_element('submit','Search');
	echo "<input type='hidden' value='".$parent."' name='parent'> ";
        echo "</td>";
  } 
}

echo "<p align=right><a href='AuditPerms.php'>Menu Audit</a></p>";
echo "<h2>Menu Editor</h2>";

get_request_values("id,submit,cmd,parent,menufieldsi,target");

$f = new menuform;

function FixWidth() {
	$sitewidth = 880;
	$db = new $_ENV["DatabaseClass"];
	$total = 0;
        $db->query("select id, title from menu where parent=0");
        while ($db->next_record()) {
                $length[$db->f(0)] = strlen($db->f(1)) + 3;
                $total += $length[$db->f(0)];
		echo "total $total<br>";
        }
        foreach ($length as $id=>$len) {
                $new = $sitewidth / $total * $len;
                $sql = "update menu set width='$new' where id='$id'";
		$db->query($sql);
        }
}

if ($submit) {
  switch ($submit) {

   case "Save":
    if ($id) $submit = "Edit";
    else $submit = "Add";
   case "Add":
   case "Edit":
    if (isset($auth)) {
     check_edit_perms();
     if (!$f->validate()) {
        $cmd = $submit;
        echo "<font class=bigTextBold>$cmd menu</font>\n";
        $f->reload_values();
        $f->display();
        page_close();
        exit;
     }
     else
     {
        echo "Saving....";
        $f->save_values();
	if (substr($target,-5)==".html") {
		$file = $_ENV["local"]."templates/".$target;
		if (!file_exists($file)) {
			if( $fp = fopen($file,"w")) {
				$targ = NeatStr(substr($target,0,-5));
				fwrite($fp,"<h1>$targ</h1><p>Under construction</p>");
				fclose($fp);
				$_http_referer = "/$target";
			}
		}
	}
	if ($parent==0) FixWidth();
        echo "<b>Done!</b><br>\n";
      	if (!$_http_referer) $_http_referer=$sess->self_url().$sess->add_query(array("parent"=>$parent));
        if (!$dev) echo "<META HTTP-EQUIV=REFRESH CONTENT=\"0; URL=".$_http_referer."\">";
	echo "&nbsp<a href=\"$_http_referer\">Back to menu.</a><br>\n";
        page_close();
        exit;
     }
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br>\n";
    }
   case "View":
   case "Back":
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"0; URL=".$sess->self_url();
	echo $sess->add_query(array("parent"=>$parent));
	echo "\">";
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to menu.</a><br>\n";
        page_close();
        exit;

   case "Delete":
    if (isset($auth)) {
	check_edit_perms();
        echo "Deleting....";
        $f->save_values();
	if ($parent==0) FixWidth();
        echo "<b>Done!</b><br>\n";
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br>\n";
    }
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url()."\">";
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to menu.</a><br>\n";
        page_close();
        exit;
  }
} else {
    if ($id) {
	$f->find_values($id);
    }
}
switch ($cmd) {
    case "View":
    case "Delete":
	$f->freeze();
    case "Add":

    case "Copy":
    case "Edit":
	echo "<font class=bigTextBold>$cmd menu</font>\n";
	$f->display();
	break;
    default:
	$cmd="Query";
	$t = new MymenuTable;
	$t->heading = 'on';
	$t->add_extra = 'on';
	$t->add_insert = 'menuform';
	$t->edit = 'menuform';
	$t->ipe_table = "menu";
	$db = new $_ENV["DatabaseClass"];

	echo "<font class=bigTextBold>Select Menu Heading to edit</font><br><br>\n";

	// These fields will be searchable and displayed in results.
	// Format is "RealFieldName"=>"Field Name Formatted For Display",
	$t->all_fields = array(
			"position",
			"title",
			"target",
			"header",
			"subnavhdr",
			"view_requires",
			"edit_requires",
			"HtmlTitle",
			"MetaData"
			);
	$default_fields = array(
                        "position",
                        "title",
                        "target",
			"view_requires",
                        "subnavhdr"
                        );

	
	if (!is_array($menufields)) {
		$menufields = $default_fields;
		$sess->register("menufields");
	}

	echo "<form method=post>\n";

	$t->fields = $menufields;
        $t->map_cols = array(
			"level"=>"level",
			"parent"=>"parent",
			"position"=>"position",
			"title"=>"title",
			"target"=>"target",
			"header"=>"header image",
			"HTML_title"=>"HTML Title",
			"MetaData"=>"META Data",
			"view_requires"=>"viewing permission",
			"edit_requires"=>"add/edit/delete permission",
			"width"=>"width");
	$t->extra_html = array(
			"view_requires"=>"onkeyup='permchooser(this);' onfocus='permchooser(this);'",
			"edit_requires"=>"onkeyup='permchooser(this);' onfocus='permchooser(this);'",
			);


	if (!$parent) {
		$parent='0';
		if ($ref = basename(@$_SERVER["HTTP_REFERER"])) {
			$db->query("select parent from menu where target='$ref'");
			if ($db->next_record()) {
				$parent=$db->f(0);
			}
		}
	}
        echo "<select name='parent' onchange='this.form.submit();'>\n";
	echo "<option value='A'>All\n";
        if ($parent=='0') echo "<option value='0' selected>Top Level\n";
	else {
		$db->query("select parent from menu where id='$parent'");
		$db->next_record();
		$thisparent=$db->f("parent");
		if ($thisparent>0) {
			echo "<option value='0'>Top Level\n";
			$db->query("select parent, title from menu where id='$thisparent'");
                	$db->next_record();
			echo "<option value='$thisparent'>".$db->f("title");
		}
		$db->query("select id, title from menu where parent='$thisparent'");
		echo "<optgroup label='Parent Level'>";
		while ($db->next_record()) {
			if ($db->f("id")==$parent) $sel=" selected"; else $sel="";
			echo "<option value='".$db->f("id")."'$sel>".$db->f("title")."\n";
		}
		echo "</optgroup>";
	}
        $sql = "select id, title from menu where parent='$parent' order by position";
        $db->query($sql);
	echo "<optgroup label='This Level'>";
        while ($db->next_record()) {
                if ($parent==$db->f("parent")) $sel="selected"; else $sel="";
                echo "<option value='".$db->f("id")."' $sel> &nbsp; ".$db->f("title")."\n";
        }
        echo "</optgroup>\n</select>\n";
	echo " Columns: ";
	foreach ($t->all_fields as $field) {
		if (in_array($field,$menufields,TRUE)) $chk = "checked"; else $chk="";
		echo "\n<input type=checkbox $chk name=menufields[] value='$field'>$field ";
	}
	echo "\n<input type=submit name=setcols value='Set'>";
	echo "\n</form>\n";


	if ($parent=='A') $query = "1 order by parent,position,id";
	else $query = "parent='$parent' order by `position`";

  printf("<hr>");

  // Do we have a valid query string?
  if ($query) {
    // Show that condition
  //  printf("Query Condition = %s<br>\n", $query);

    // Do that query
    $db->query("select * from `menu` where ". $query);
    // Dump the results (tagged as CSS class default)
 //   printf("Query Results = %s<br>\n", $db->num_rows());
	if ($db->num_rows()) {
    		$t->show_result($db, "default");
	} else {
		$data["parent"] = $parent;
		$data["submit"] = "Add";
		$data["title"] = "";
		$data["target"] = "";
		$t->table_open();
		$t->table_heading_row($data);
		$t->table_insert_row($data);
		$t->table_close();
	}
  }
} // switch $cmd
?>
<form name=perm_chooser_form>
<div id=perm_popup>
<a href="#" onclick="this.offsetParent.style.display='none';" id="close">x</a>
<UL>
<?php foreach (explode(",",$_ENV["Perms"]) as $p) {
	echo " <LI><input type=checkbox name='perms[]' value='$p' onclick=update(); >$p</li>\n";
} ?>
</UL>
</div>
</form>
<script>
var currentField;
function hide_perm_popup() {
	popup = document.getElementById("perm_popup");
	popup.style.display='none';
}
function move(obj,target) {
        var curleft = 50;
        var curtop = -50;
        if (obj.offsetParent) {
                do {
                        curleft += obj.offsetLeft;
                        curtop += obj.offsetTop;
                } while (obj = obj.offsetParent);
                target.style.top = curtop + "px";
                target.style.left = curleft + "px";
        }
}
function permchooser(elem) {
        if (document.all) {
                // IE
                document.all['perm_popup'].style.display='block';
                document.all['perm_popup'].style.visibility='visible';
        } else {
                // Others
                popup = document.getElementById("perm_popup");
                popup.style.display='block';
                popup.style.visibility='visible';
        }
        perms = elem.value.split(',');
        f = document.forms["perm_chooser_form"];
        for (p in perms) {
                perm = perms[p];
                for (i=0; i<f.elements.length; i++) {
                        e = f.elements[i];
                        if ((e.name=='perms[]') && (e.value==perm)) {
                                e.checked='checked';
                        } else if (p<1) {e.checked=false;}
                }
        }
	move(elem,popup);
	currentField = elem;
	enableSave(elem);
}
function update() {
        var str = "";
        f = document.forms["perm_chooser_form"];
        for (i=0; i<f.elements.length; i++) {
                e = f.elements[i];
                if ((e.name=='perms[]') && (e.checked)) {
                        if (str.length>0) {str += ",";}
                        str += e.value;
                }
        }
        currentField.value = str;
	if (currentField.className=='ipe') {
		enableSave(currentField);
	}
}
hide_perm_popup();
</script>
<style>
div#perm_popup {
        float: left;
        display: none;
        position:absolute;
        background: #ddd;
        height: auto;
	width: 150px;
        z-index: 350;
        border: 1px solid;
        margin: 1px;
}
div#perm_popup ul {
        padding: 2px 20px 2px 10px;
        margin: 0px;
}
div#perm_popup ul li {
        z-index: 12;
        padding: 1px;
        list-style-type: none;
}
div#perm_popup ul li:hover {
</style>
<?php
page_close();
?>

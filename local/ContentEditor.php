<?php
include("phplib/prepend.php");
page_open(array("sess" => $_ENV["SessionClass"], "auth" => $_ENV["AuthClass"], "perm" => $_ENV["PermClass"]));

check_view_perms();

echo "<script language=JavaScript src=/ckeditor/ckeditor.js></script>\n";

$preferred_method = "disk";
$alternate_method = "sql";

$template = $_REQUEST["page"];

echo "	<h5>Editing $css$template; </h5>\n";

if ($css = $_REQUEST['css']) {
	$text = "";
	$filename = $_ENV["local"]."../css/$css.css";
	$outfile = $_ENV["local"]."../css/old/$css.css";
	if (file_exists($filename)) {
		$fp = fopen($filename,'r');
		if ($fp) {
			while (!feof($fp)) {
				$csstext .= fgets($fp,1024);
			}
		} else echo "can not open $filename";
	} else echo "$filename does not exist";
}

function get_template($method,$template) {
	$db = new $_ENV["DatabaseClass"];
	$text = "";
	switch ($method) {
		case "disk":
   			$filename = $_ENV["local"]."templates/$template.html";
   			$outfile = $_ENV["local"]."templates/old/$template.html.".date("YmdHis");
   			if (file_exists($filename)) {
				$fp = fopen($filename,'r');
				if ($fp) {
					while (!feof($fp)) {
						$text .= fgets($fp,1024);
					}
				} 
			} 
			break;
		case "sql":
			$db->query("SHOW TABLES LIKE 'templates'");
			if ($db->next_record()) {
	   			$db->query("SELECT Content FROM templates WHERE Page='$template' ORDER BY id DESC LIMIT 0,1");
   				if ($db->next_record()) {
        				$text = stripslashes($db->f(0));
				}
			}
			break;
	}
	return $text;
}

function save_content($method,$template,$content) {
	global $auth;
	$db = new $_ENV["DatabaseClass"];
	$success = false;
	switch ($method) {
                case "disk":
   			$filename = $_ENV["local"]."templates/$template.html";
   			$outfile = $_ENV["local"]."templates/old/$template.html.".date("YmdHis");
			echo "<br>Saving to disk...";
			$fp = @fopen($outfile,"w");
			if ($fp) {
				fwrite($fp,$text);
				fclose($fp);
				$fp = @fopen($filename,"w");
				if ($fp) {
					fwrite($fp,stripslashes($content));
					fclose($fp);
					echo "<h4>Saved</h4>";
					$success = true;
				} else echo "<br>Can't open $filename for writing. ";
			} else echo "<br>Can't open backup file $outfile for writing. ";
                        break;
                case "sql":
			$db->query("SHOW TABLES LIKE 'templates'");
			if ($db->next_record()) {
				echo "<br>Saving to sql database...";
        			$db->query("INSERT INTO templates SET Content=".$db->quote($content).", Page='$template', UpdatedBy='".$auth->auth["uname"]."'");
				if ($db->rows_affected()) $success=true;
			} else echo "<br>No templates table in database";
                        break;

        }
	return $success;
}

if ($template) {
	if (!$text = get_template($preferred_method,$template))
	if (!$text = get_template($alternate_method,$template)) {
        	echo "Cannot find $template - new page";
        	$text = "<h1>$template</h1>\n<p>under construction</p>";
	}
}

if ($css) {
	if ($content=$_REQUEST['content']) {
		check_edit_perms();
		$filename = $_ENV["local"]."../css/$css.css";
		$outfile = $_ENV["local"]."../css/old/$css.css".date("YmdHis");
		$fp = fopen($outfile,"w");
		if ($fp) {
			fwrite($fp,$csstext);
			fclose($fp);
			$fp = fopen($filename,"w");
			if ($fp) {
				fwrite($fp,stripslashes($content));
				fclose($fp);
				echo "<h4>Saved</h4>";
        			echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=/\">";
			} else echo "unable to write to $filename";
		} else echo "unable to write to $outfile";
        	echo "&nbsp<a href='/'>Back to page.</a><br>\n";
        	page_close();
        	exit;
	} else {
		echo "<form name=EditorForm id=EditorForm method=post>\n";
		echo "<textarea cols=105 rows=30 name=content>";
		echo $csstext;
		echo "</textarea>\n";
		echo "<input type=submit value='Save'>\n";
		echo "</form>\n";
	}
}
else	
if ($content=$_REQUEST['content']) {
	check_edit_perms();
	$ok = true;
	if (!save_content($preferred_method,$template,$content)) 
	if (!save_content($alternate_method,$template,$content)) {
		echo "<p class=error>Save Failed</p>";;
		$ok = false;
	}
	if ($ok) echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$template.".html\">";
        echo "&nbsp<a href=\"".$template.".html\">Back to page.</a><br>\n";
        page_close();
        exit;
}

if ($text) {
	echo "<form name='EditorForm' id='EditorForm' method='post'>\n";

	if ($_ENV["SubFolder"]) $sf = "/".$_ENV["SubFolder"]."/";

	$finder="";
	switch ($_ENV["editor"]) {
	    case "fckeditor":
		$editor = new FCKeditor('content');
		$editor->BasePath = 'fckeditor/';
		$editor->Config['EditorAreaCSS'] = 'css/style.css';
		$editor->Width = '880';
		$editor->Height = '500';
		$editor->Value = stripslashes($text);
		$editor->editor('content',stripslashes($text));
		break;
	    case "ckfinder":
                $finder="CKFinder.SetupCKEditor(editor);";
	    case "ckeditor":
        	echo "<textarea name='$fieldname' rows=200 cols=40></textarea>
		<script>var editor=CKEDITOR.replace(\"$this->name\", {
                toolbarStartupExpanded : true
                });$finder</script>";
		break;
	}

	echo "</form>\n";

	} // if text
	

page_close();

?>

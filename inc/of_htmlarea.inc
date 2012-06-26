<?php
/* OOHForms: htmlarea
 *
 * Copyright (c) 2008 by David Beveridge
 * 
 * Adapted from of_textarea.inc
 * $Id: of_textarea.inc,v 1.2 2001/08/09 01:07:13 richardarcher Exp $
 * use autogen type mediumtext
 */

  /* 	$_ENV["editor"] tells us which WYSISYG editor is installed 
	fckeditor,	is a free editor and file uploader that works well in firefox and ie
	ckeditor,	is a better free editor that works in more browsers but does not have a file uploader build in
	ckfinder,	is a file uploader for ckeditor, but is not free (see www.ckfinder.com for pricing)

	If you set $_ENV["editor"]='ckfinder' then the editor is ckeditor with ckfinder integrated.
  */

class of_htmlarea extends of_element {

  var $height;
  var $width;
  var $wrap;
  var $style = "css/style.css";

  // Constructor
  function of_htmlarea($a) {
    $this->setup_element($a);
  }

  function self_get($val,$which, &$count) {

    if ($_ENV["editor"]=='fckeditor') return $this->fck_self_get($val,$which, $count);

    $str  = "";
	if (!isset($this->rows)) $this->rows=10;
	if (!isset($this->cols)) $this->cols=60;
    $str .= "<textarea name='$this->name' id='$this->name'";
    $str .= " rows='$this->rows' cols='$this->cols'";
    if ($this->class)
      $str .= " class='$this->class'";
    if ($this->wrap)
      $str .= " wrap='$this->wrap'";
    if ($this->extrahtml)
      $str .= " $this->extrahtml";
    $str .= ">" . stripslashes($this->value) ."</textarea>";

    if ($_ENV["editor"]=="ckeditor") {
	$str .= "<script>CKEDITOR.replace(\"$this->name\", {
		toolbarStartupExpanded : false
		});</script>";
    }
    if ($_ENV["editor"]=="ckfinder") {
	$str .= "<script>var editor=CKEDITOR.replace(\"$this->name\", {
		toolbarStartupExpanded : false
		});CKFinder.SetupCKEditor(editor);</script>";
    }

    $count = 1;
    return $str;
  }

  function fck_self_get($val,$which, &$count) {
    $editor = new FCKeditor($this->name);
    $editor->BasePath = 'fckeditor/';
    $editor->Config['EditorAreaCSS'] = $this->style;
    $editor->Height = $this->height;
    $editor->Width = $this->width;
    $editor->Value = stripslashes($this->value);
    return $editor->CreateHtml();
  }

  function self_get_frozen($val,$which, &$count) {
    $str  = "";
    $str .= "<input type='hidden' name='$this->name'";
    $str .= " value=\"";
    $str .= urlencode(stripslashes($this->value));
    $str .= "\">\n";
    $str .= "<table border=0><tr><td>\n";
    $str .=  $this->value;
    $str .= "\n</td></tr></table>\n";
    
    $count = 1;
    return $str;
  }

} // end TEXTAREA

?>

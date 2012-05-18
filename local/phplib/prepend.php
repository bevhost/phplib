<?php
/*
 * Session Management for PHP3
 *
 * Copyright (c) 1998,1999 SH Online Dienst GmbH
 *                    Boris Erdmann, Kristian Koehntopp
 *
 * $Id: prepend.php3,v 1.9 1999/10/24 10:21:24 kk Exp $
 *
 */ 

$QUERY_STRING="";

$_ENV["local"] = getcwd()."/phplib/";
if (!file_exists($_ENV["libdir"] = "/usr/share/phplib/")) $_ENV["libdir"] = $_ENV["local"];

require($_ENV["libdir"] . "db_mysql.inc");  /* Change this to match your database. */
require($_ENV["libdir"] . "ct_sql.inc");    /* Change this to match your data storage container */
require($_ENV["libdir"] . "session.inc");   /* Required for everything below.      */
require($_ENV["libdir"] . "auth.inc");      /* Disable this, if you are not using authentication. */
require($_ENV["libdir"] . "perm.inc");      /* Disable this, if you are not using permission checks. */
require($_ENV["libdir"] . "user.inc");      /* Disable this, if you are not using user variables. */
#require($_ENV["libdir"] . "cart.inc");      /* Disable this, if you are not using the shopping cart. */
class My_Cart { function start() {} };		/* Disable this, if you are using the shopping cart */

/* Additional require statements go below this line */
include($_ENV["libdir"] . 'oohforms.inc');
include($_ENV["libdir"] . 'tpl_form.inc');
include($_ENV["libdir"] . 'table.inc');
include($_ENV["libdir"] . 'sqlquery.inc');
include($_ENV["libdir"] . 'template.inc');

include($_ENV["libdir"] . 'fckeditor/fckeditor.php');

/* Additional require statements go before this line */

#require($_ENV["local"] . "My_Cart.inc");	/* Disable this, if you are not using the shopping cart. */
require($_ENV["local"] . "local.inc");	/* Required, contains your local configuration. */
require($_ENV["local"] . "menu.inc");

if (file_exists($inc="phplib".substr($PHP_SELF,0,-3)."inc")) include($inc);  /* Include SQL & form class to match this php */

require($_ENV["libdir"] . "page.inc");	/* Required, contains the page management functions. */

#require($_ENV['libdir'] . 'htmlMail.php');	/* for sending MIME encoded email messages. */
#require($_ENV['libdir'] . 'web.php');	/* for access web pages with cookies etc. */

function EventLog($Description,$ExtraInfo="",$Level="Info") {
        global $PHP_SELF, $argv, $REMOTE_ADDR, $auth;
        $db = new $_ENV["DatabaseClass"];
        if ($PHP_SELF) $Program=$PHP_SELF; else $Program = $argv[0];
        if ($auth) $UserName = $auth->auth["uname"]; else $UserName="NotLoggedIn";
        $sql = "INSERT DELAYED INTO EventLog SET ";
        $sql .= "Program = '$Program',";
        $sql .= "IPAddress = '$REMOTE_ADDR',";
        $sql .= "UserName = '$UserName',";
        $sql .= "Description = '".addslashes($Description)."',";
        $sql .= "Level = '$Level',";
        $sql .= "ExtraInfo = '".addslashes($ExtraInfo)."'";
        $db->query($sql);
} 

function neatstr($InpStr)
{
        $OutStr = "";
        $pos = 0;
        $done = strlen($InpStr);
        do {
                $ch = substr($InpStr,$pos,1);
                if ($pos>0) if ($ch<'a') $OutStr .= " ";
                $OutStr .= $ch;
                $pos++;
        } while ($pos<$done);
        return str_replace("_","",$OutStr);
}


$db = new $_ENV["DatabaseClass"];
$self = substr($PHP_SELF,1);
switch ($self) {
	case "index.php":
	case "template.php":
		$self = $_REQUEST["page"].".html";
}
$db->query("SELECT HtmlTitle, MetaData, view_requires, edit_requires, subnavhdr from menu WHERE target='$self' OR target='/$self' order by id desc");
if ($db->next_record()) {
	$HTML_title = stripslashes($db->f(0));
	$MetaData = stripslashes($db->f(1));
	$_ENV["view_requires"] = $db->f("view_requires");
	$_ENV["edit_requires"] = $db->f("edit_requires");
	$_ENV["subnavhdr"] = $db->f("subnavhdr");
	// edit perms defaults to the same as view perms when blank.
	if (!$_ENV["edit_requires"]) $_ENV["edit_requires"] = $_ENV["view_requires"];
}
$self = explode(".",$self);
if (!$HTML_title) $HTML_title = $_ENV["BaseName"]." ".$self[0];

function check_view_perms() {
	global $sess, $auth;
	$ok = false;
	if ($_ENV["view_requires"]) {
		foreach(explode(",",$_ENV["view_requires"]) as $need) {
			if ($sess->have_perm($need)) $ok = true;
		}
	} else $ok = true;
	if (!$ok) {
		if ($auth) { $usrnm = $auth->auth["uname"]; $p = $auth->auth["perm"]; }
		echo "<h3>Access Denied</h1>\n";
		echo "<p class=error>User $usrnm does not have sufficient access privileges for this $cmd $submit operation on this page</p>\n";
		echo "<p>$usrnm has $p rights</p>";
		page_close();
		exit;
	}
}

function check_edit_perms() {
	global $sess, $auth;
	$ok = false;
	if ($_ENV["edit_requires"]) {
		foreach(explode(",",$_ENV["edit_requires"]) as $need) {
			if ($sess->have_perm($need)) $ok = true;
		}
	} else $ok = true;
	$_ENV["show_edit"] = $ok;
	if (!$ok) {
		if ($auth) { $usrnm = $auth->auth["uname"]; $p = $auth->auth["perm"]; }
		echo "<h3>Permission Denied</h1>\n";
		echo "<p class=error>User $usrnm does not have sufficient access privileges for this $cmd $submit operation on this page</p>\n";
		echo "<p>$usrnm has $p rights</p>";
		page_close();
		exit;
	} 
}

class MenuPageform extends menuform {
        var $classname="MenuPageform";
}
function MenuPage($page) {
        global $target, $id, $perm, $view_requires, $edit;
        $db = new $_ENV["DatabaseClass"];
        $db->query("Select title from menu where id='$page'");
        if ($db->next_record()) {
                $heading = $db->f(0);
                $db->query("Select * from menu where parent='$page' order by position");
                while ($db->next_record()) {
                    extract($db->Record)
		    $edit = "";
                    $ok = false;
                    if ($perm->have_perm("admin")) {
                        $edit = "<a href=menu.php?cmd=Edit&id=$id><img src=image/edit.jpg /></a>";
                    }
                    if ($view_requires) {
                        if ($perm) {
                                foreach(explode(",",$view_requires) as $need) {
                                        if ($perm->have_perm($need)) $ok = true;
                                }
                        }
                    } else $ok = true;
                    if ($ok) {
                        if ($heading) { echo "<h1>$heading</h1>\n"; $heading=false; }
                        if ($target=="menu") $target = "menupage.php?MenuId=$id";
			echo "<br>\n<h3><a href='$target'>$title</a> - %s%s</h3>%s";
//				$GLOBALS['target'],$GLOBALS['title'],$GLOBALS['LongDescription'],$GLOBALS['edit'],$GLOBALS['HelpText']);

                    }
                }
        }
}

function my_error_handler($errno, $errstr, $errfile, $errline, $errcontext) {
    $errno = $errno & error_reporting();
    if($errno == 0) return;
    if(!defined('E_STRICT'))            define('E_STRICT', 2048);
    if(!defined('E_RECOVERABLE_ERROR')) define('E_RECOVERABLE_ERROR', 4096);
    switch($errno){
        case E_ERROR:               print "Error";                  break;
        case E_WARNING:             print "Warning";                break;
        case E_PARSE:               print "Parse Error";            break;
        case E_NOTICE:              print "Notice";                 break;
        case E_CORE_ERROR:          print "Core Error";             break;
        case E_CORE_WARNING:        print "Core Warning";           break;
        case E_COMPILE_ERROR:       print "Compile Error";          break;
        case E_COMPILE_WARNING:     print "Compile Warning";        break;
        case E_USER_ERROR:          print "User Error";             break;
        case E_USER_WARNING:        print "User Warning";           break;
        case E_USER_NOTICE:         print "User Notice";            break;
        case E_STRICT:              print "Strict Notice";          break;
        case E_RECOVERABLE_ERROR:   print "Recoverable Error";      break;
        default:                    print "Unknown error ($errno)"; break;
    }
    $msg = "<b>PHP Error:</b> <i>$errstr</i> in <b>$errfile</b> on line <b>$errline</b>\n";
    $detail = "";
    if(function_exists('debug_backtrace')){
        //print "backtrace:\n";
        $backtrace = debug_backtrace();
        array_shift($backtrace);
        foreach($backtrace as $i=>$l){
            $detail .= "[$i] in function <b>{$l['class']}{$l['type']}{$l['function']}</b>";
            if($l['file']) $detail .= " in <b>{$l['file']}</b>";
            if($l['line']) $detail .= " on line <b>{$l['line']}</b>";
            $detail .= "<br>\n";
        }
    }
    $error=EventLog($msg,serialize($errcontext),"Error");
    if ($detail) EventLog("PHP BackTrace for $errstr in $errfile on line $errline",$detail,"Error");
    if(isset($GLOBALS['error_fatal'])){
        if($GLOBALS['error_fatal'] & $errno)
                die("<br><br><big><b>Oops, well this is embarrassing! &nbsp; An error has occurred:</b>
                        <br>Please quote $error if calling helpdesk ".$_ENV["HelpDesk"]."</big>");
    }
}

function error_fatal($mask = NULL){
    if(!is_null($mask)){
        $GLOBALS['error_fatal'] = $mask;
    }elseif(!isset($GLOBALS['die_on'])){
        $GLOBALS['error_fatal'] = 0;
    }
    return $GLOBALS['error_fatal'];
}

error_reporting(E_ALL^E_NOTICE);      // will report all errors
set_error_handler('my_error_handler');
error_fatal(E_ALL^E_NOTICE); // will die on any error except E_NOTICE

?>

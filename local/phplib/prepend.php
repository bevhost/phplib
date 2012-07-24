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

//setup php for working with Unicode data
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_http_input('UTF-8');
mb_language('uni');
mb_regex_encoding('UTF-8');
ob_start('mb_output_handler');
ini_set('default_charset', 'UTF-8');
ini_set('magic_quotes_gpc', 0);
ini_set("arg_separator.input",";&");
setlocale(LC_ALL, 'en_AU.UTF-8');
date_default_timezone_set("Australia/Sydney");
$js="";

$testip = "129.168.1.100"; // NPD
$REMOTE_ADDR = @$_SERVER["REMOTE_ADDR"];
if (($REMOTE_ADDR==$testip) or ($REMOTE_ADDR=="124.191.215.55") or (@$_ENV["SiteRoot"] == "/var/www/hsdev/public_html/")) { 
 	$dev=true; 
} else {
 	$dev=false;
}
$dev=true;
if ($dev) {
	ini_set('display_errors', 'On');
	if (array_key_exists("HTTP_HOST",$_SERVER)) ini_set('html_errors', 'On');
	else ini_set('html_errors', 'Off');
	ini_set('docref_root','http://au.php.net/manual/en/');
	error_reporting(E_ALL^E_NOTICE); 	// will report all errors
	set_error_handler('my_error_handler');
} else {
	error_reporting(E_ALL^E_NOTICE);	// will report all errors
	error_fatal(E_ALL^E_NOTICE);		// will die on any error except E_NOTICE
	ini_set('display_errors', 'Off');
	set_error_handler('my_error_handler');
}

$_ENV["local"] = getcwd()."/phplib/";
if (!file_exists($_ENV["libdir"] = "/usr/share/phplib/")) $_ENV["libdir"] = $_ENV["local"];

$time = microtime();
$time = explode(" ", $time);
$time = $time[1] + $time[0];
$_page_start_time = $time;

if (array_key_exists("widemode",$_REQUEST)) $GLOBALS["widemode"]=$_REQUEST["widemode"];

$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];
get_request_values("id,cmd,submit,rowcount,sortorder,sortdesc,startingwith,start,prev,next,last,cond,EditMode,WithSelected,widemode,Field,_http_referer,export_results");
$orig_cmd=$cmd;
$PWD = array_key_exists("PWD",$_SERVER) ? $_SERVER["PWD"] : "";
$PHP_SELF = $_SERVER["PHP_SELF"];

if (substr($_SERVER["PHP_SELF"],0,1)=="/") $SELF = $_SERVER["PHP_SELF"]; else $SELF="$PWD/".$_SERVER["PHP_SELF"];
$docroot = substr($SELF,0,strrpos($SELF,'/'));
if (!$DOCUMENT_ROOT) $DOCUMENT_ROOT = $docroot;

$_ENV["libdir"] = "/usr/share/phplib/";
$_ENV["local"]  = $DOCUMENT_ROOT."/phplib/";

$QUERY_STRING="";


require($_ENV["libdir"] . "db_pdo.inc");  /* Change this to match your database. */
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


/* Additional require statements go before this line */

#require($_ENV["local"] . "My_Cart.inc");	/* Disable this, if you are not using the shopping cart. */
require($_ENV["local"] . "local.inc");	/* Required, contains your local configuration. */
require($_ENV["local"] . "menu.inc");

if (file_exists($inc="phplib".substr($PHP_SELF,0,-3)."inc")) include($inc);  /* Include SQL & form class to match this php */

require($_ENV["libdir"] . "page.inc");	/* Required, contains the page management functions. */

#require($_ENV['libdir'] . 'htmlMail.php');	/* for sending MIME encoded email messages. */
#require($_ENV['libdir'] . 'web.php');	/* for access web pages with cookies etc. */

if ($_ENV["editor"]=="fckeditor") include("/usr/share/phplib/fckeditor/fckeditor.php");
if ($_ENV["editor"]=="ckeditor") include("/usr/share/phplib/ckeditor/ckeditor.php");
if ($_ENV["editor"]=="ckfinder") include("/usr/share/phplib/ckeditor/ckeditor.php");


function get_request_values($varlist) {
        $vars = explode(",",$varlist);
        foreach($vars as $v) {
                if (isset($_REQUEST[$v])) {
			if (is_array($_REQUEST[$v])) $GLOBALS[$v]=$_REQUEST[$v];
			else $GLOBALS[$v]=to_utf8($_REQUEST[$v]);
			if ($v=="submit") {
				/* Pages with $_ENV["AllowPostWithoutReferer"] set will always pass */
				$ok = array_key_exists("AllowPostWithoutReferer",$_ENV) ? true : false;	
				if (array_key_exists("HTTP_REFERER",$_SERVER)) {
					$proto = array_key_exists("HTTPS",$_SERVER) ? "https://" : "http://" ;
					$ref = strtolower($proto.$_SERVER["HTTP_HOST"]);
					if (substr(strtolower($_SERVER["HTTP_REFERER"]),0,strlen($ref))==$ref) { $ok=true; }
				}
				if (!$ok) {
				#	die("Suspected CSRF Attack");
				}
			}
                } else {
                        if (!isset($GLOBALS[$v])) $GLOBALS[$v]=false;
                }
        }
        if (!is_array($GLOBALS[$v])) $GLOBALS["q_".$v]="'".addslashes($GLOBALS[$v])."'";  // can't use database specific yet, not defined.
}

ini_set('unserialize_callback_func', 'mycallback'); // set your callback_function
function mycallback($classname) 
{
	$classname = str_replace("_Sql_Query","",$classname);
	$inc_file = $_ENV["local"].$classname.".inc";
	require_once($inc_file);
}


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

$db = new $_ENV["DatabaseClass"];

function magicquote($str) {   // hangover from addslashes 
	global $db;
	if (!$db->connect()) return 0;
        return substr(substr($db->escape_string($str),1),0,-1);
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
#	case "index.php":
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
} else {
	$_ENV["view_requires"] = "";
	$_ENV["edit_requires"] = "";
	$MetaData = "<meta name='keywords' content='' />\n".
		"<meta name='description' content='' />\n".
		"<meta http-equiv='Content-type' content='text/html;charset=UTF-8' />\n";
}
$self = explode(".",$self);
if (empty($HTML_title)) $HTML_title = $_ENV["BaseName"]." ".$self[0];

function check_view_perms() {
	global $sess, $auth, $cmd, $submit;
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
function array_first_chunk($input,$narrow_chunk_size,$wide_chunk_size) {
        $chunk_size = empty($globals["widemode"]) ? $narrow_chunk_size : $wide_chunk_size;  //get appropriate chunk size for screen width.
        if (count($input)>$chunk_size) {
                $chunks = array_chunk($input,$chunk_size);
                return $chunks[0];
        } else return $input;
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
                    extract($db->Record);
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
    global $dev;
    $errno = $errno & error_reporting();
    if ((!$dev) and ($errno == 0)) return;
    if (error_reporting() === 0) {
        // continue script execution, skipping standard PHP error handler
        return true;
    }
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
            @$detail .= "[$i] in function <b>{$l['class']}{$l['type']}{$l['function']}</b>";
            if($l['file']) $detail .= " in <b>{$l['file']}</b>";
            if($l['line']) $detail .= " on line <b>{$l['line']}</b>";
            $detail .= "<br>\n";
        }
    }
    $self = $_SERVER["PHP_SELF"];
    if ($dev) {
	if (isset($php_errormsg)) echo "<h1>$php_errormsg</h1>";
	echo "<h3>PHP BackTrace for $errstr in $errfile on line $errline</h3>$detail<pre>";
	var_dump($errcontext);
    }
    $error=EventLog($msg,serialize($errcontext),"Error");
    if ($detail) EventLog("PHP BackTrace for $errstr in $errfile on line $errline",$detail,"Error");
    if(isset($GLOBALS['error_fatal'])){
        if($GLOBALS['error_fatal'] & $errno)
		if ($self=="api.php") {
			$xmlstr = "<?xml version='1.0' standalone='yes'?><api><errors></errors></api>";
			$xml = new SimpleXMLElement($xmlstr);
			$xml->errors->addChild("error","Call Support and Quote Event ID $error");
			echo str_replace("><",">\n<",$xml->asXML());
			exit;
		} else
                die("<br><br><big><b>Oops, well this is embarrassing! &nbsp; An error has occurred:</b>
                        <br>Please quote <b>Event ID $error</b> if calling helpdesk on 1300 739 822 from 9am to 8pm</big>");
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


function to_utf8( $string ) { 
// From http://w3.org/International/questions/qa-forms-utf-8.html 
    if ( preg_match('%^(?: 
      [\x09\x0A\x0D\x20-\x7E]            # ASCII 
    | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte 
    | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs 
    | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte 
    | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates 
    | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3 
    | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15 
    | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16 
)*$%xs', $string) ) { 
        return $string; 
    } else { 
        return iconv( 'CP1252', 'UTF-8', $string); 
    } 
} 

function show_audit_trail($id,$table='') {
	echo "<h3>Audit Trail</h3>\n";
	if (!$table) $table = substr($_SERVER["PHP_SELF"],1,-4);
	$db = new $_ENV["DatabaseClass"];
        $t = new Table;
        $t->heading = "on";
        $t->add_extra = array("View"=>array("target"=>$_ENV["LogSqlTo"].".php"));
        $t->fields = array( "UserName", "Table", "SQL", "Was", "At", "IP");
        $t->map_cols = array( "UserName"=>"User Name", "Table"=>"Table", "SQL"=>"Database transaction", 
				"Was"=>"Old Values", "At"=>"Date,TimeStamp", "IP"=>"IP Addr");
        $sql = "SELECT * FROM ".$db->qi($_ENV["LogSqlTo"])." WHERE ".$db->qi('Table')."='$table' AND ".
		$db->qi('Key')."='$id' AND `SQL` LIKE 'UPDATE%' order by id desc limit 0,50";
	$db->query($sql);
        if ($t->show_result($db, "default")==50) {
                echo "<a href=".$sess->url($_ENV["LogSqlTo"].".php").$sess->add_query(array("Table"=>$table,"Key"=>$id)).">Show More Audit Logs</a>\n";
        }
}

?>

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

$testip = "203.26.11.96"; // NPD
$REMOTE_ADDR = @$_SERVER["REMOTE_ADDR"];
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];
if (($REMOTE_ADDR==$testip) or ($REMOTE_ADDR=="58.174.77.127")) { 
 	$dev=true; 
} else {
 	$dev=false;
}
if (array_pop(explode(".",$_SERVER["HTTP_HOST"]))=="local") $dev=true;
if ($DOCUMENT_ROOT == "/var/www/portal/public_html") $dev=false;
if ($dev) {
	ini_set('display_errors', 'On');
	if (array_key_exists("HTTP_HOST",$_SERVER)) ini_set('html_errors', 'On');
	else ini_set('html_errors', 'Off');
	ini_set('docref_root','http://au.php.net/manual/en/');
	error_reporting(E_ALL^(E_STRICT|E_NOTICE));
	error_fatal(E_ALL^(E_STRICT|E_NOTICE));
	set_error_handler('my_error_handler');
} else {
	error_reporting(E_ALL & ~(E_STRICT|E_NOTICE));
	error_fatal(E_ALL & ~(E_STRICT|E_NOTICE));
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
class My_Cart { function start() {}  function reset() {} };		/* Disable this, if you are using the shopping cart */

/* Additional require statements go below this line */
include($_ENV["libdir"] . 'oohforms.inc');
include($_ENV["libdir"] . 'tpl_form.inc');
include($_ENV["libdir"] . 'table.inc');
include($_ENV["libdir"] . 'sqlquery.inc');

/* Additional require statements go before this line */

#require($_ENV["local"] . "My_Cart.inc");	/* Disable this, if you are not using the shopping cart. */
require($_ENV["local"] . "local.inc");	/* Required, contains your local configuration. */

if (file_exists($inc="phplib".substr($PHP_SELF,0,-3)."inc")) include($inc);  /* Include SQL & form class to match this php */

require($_ENV["libdir"] . "page.inc");	/* Required, contains the page management functions. */

#require($_ENV['libdir'] . 'htmlMail.php');	/* for sending MIME encoded email messages. */
#require($_ENV['libdir'] . 'web.php');	/* for access web pages with cookies etc. */

if ($_ENV["editor"]=="fckeditor") include("/usr/share/phplib/fckeditor/fckeditor.php");
if ($_ENV["editor"]=="ckeditor") include("/usr/share/phplib/ckeditor/ckeditor.php");
if ($_ENV["editor"]=="ckfinder") include("/usr/share/phplib/ckeditor/ckeditor.php");

if ($_SERVER['HTTP_COOKIE']) { 
        if (strstr($_SERVER['HTTP_COOKIE'],$_ENV["SessionClass"])===FALSE) unset($modRW);
        else $modRW = "on";
} 

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
			switch($v) {
			    case "rowcount":
			    case "sortorder":
					break;
			    default:
                        	if (!isset($GLOBALS[$v])) $GLOBALS[$v]=false;
			}
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
        if ($Level=="Debug") {
                if (date("d-M-Y")<>"11-May-2011") return;  // Only debug if date matches.
        }
        global $PHP_SELF, $argv, $REMOTE_ADDR, $auth, $action, $dev;
        $db = new $_ENV["DatabaseClass"];
        if ($PHP_SELF) $Program=$PHP_SELF; else $Program = $argv[0];
        if ($Program=='/hello.php') $Program=$action;
        $UserName = "NotLoggedIn";
        if ($auth) if (array_key_exists("uname",$auth->auth)) $UserName = $auth->auth["uname"];
        if ($db->type=="pdo") {
                $stmt = $db->prepare("INSERT INTO EventLog (Program,IPAddress,UserName,Description,Level,ExtraInfo) values (?,?,?,?,?,?)");
                $stmt->execute(array($Program,$REMOTE_ADDR,$UserName,$Description,$Level,$ExtraInfo));
                return $db->lastInsertId();
        } else {
                if ($Level=='Error') $sql = "INSERT INTO EventLog SET ";
                else $sql = "INSERT INTO EventLog SET ";
                $sql .= "Program = '$Program',";
                $sql .= "IPAddress = '$REMOTE_ADDR',";
                $sql .= "UserName = ".$db->quote($UserName).",";
                $sql .= "Description = ".$db->quote($Description).",";
                $sql .= "Level = '$Level',";
                $sql .= "ExtraInfo = ".$db->quote($ExtraInfo);
                switch ($Program) {
                        case "/hello.php":
                        #       break;
                        default:
                                $db->query($sql);
                }
                if ($Level=='Error') {
                    $db->query("SELECT LAST_INSERT_ID()");
                    $db->next_record();
                    return $db->f(0);
                }
        }

}

$db = new $_ENV["DatabaseClass"];

function magicquote($str) {   // hangover from addslashes 
	global $db;
	if (!$db->connect()) return 0;
        return $db->escape_string($str);
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
$_ENV["view_requires"] = "";
$_ENV["edit_requires"] = "";
$MetaData = "<meta name='keywords' content='' />\n".
	"<meta name='description' content='' />\n".
	"<meta http-equiv='Content-type' content='text/html;charset=UTF-8' />\n";
if ($MenuClass = array_key_exists("MenuTable",$_ENV) ? $_ENV["MenuTable"] : false) {
    require($_ENV["local"] . "$MenuClass.inc");
    class MenuPageform extends menuform {
        var $classname="MenuPageform";
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
    static $ErrorCount;
    if (!isset($ErrorCount)) $ErrorCount=0;
    $ErrorCount++;
    $errno = $errno & error_reporting();
    if ($errno == 0) return;
    if (error_reporting() === 0) {
        // continue script execution, skipping standard PHP error handler
        return true;
    }
    if ($ErrorCount==100) die("Too many errors");
    $self = $_SERVER["PHP_SELF"];
    if(!defined('E_STRICT'))            define('E_STRICT', 2048);
    if(!defined('E_RECOVERABLE_ERROR')) define('E_RECOVERABLE_ERROR', 4096);
    if ($dev) {
      switch($errno){
        case E_ERROR:               $ErrType = "Error";                  break;
        case E_WARNING:             if (!$dev) return true;
				    $ErrType = "Warning";                break;
        case E_PARSE:               $ErrType = "Parse Error";            break;
        case E_NOTICE:              $ErrType = "Notice";                 break;
        case E_CORE_ERROR:          $ErrType = "Core Error";             break;
        case E_CORE_WARNING:        $ErrType = "Core Warning";           break;
        case E_COMPILE_ERROR:       $ErrType = "Compile Error";          break;
        case E_COMPILE_WARNING:     $ErrType = "Compile Warning";        break;
        case E_USER_ERROR:          $ErrType = "User Error";             break;
        case E_USER_WARNING:        $ErrType = "User Warning";           break;
        case E_USER_NOTICE:         $ErrType = "User Notice";            break;
        case E_STRICT:              if (!$dev) return true;
				    $ErrType = "Strict Notice";          break;
        case E_RECOVERABLE_ERROR:   $ErrType = "Recoverable Error";      break;
        default:                    $ErrType = "Unknown error ($errno)"; break;
      }
      $msg = "<b>PHP Error:</b> <i>$errstr</i> in <b>$errfile</b> on line <b>$errline</b>\n";
      $detail = "";
      if(function_exists('debug_backtrace')){
        //print "backtrace:\n";
        $backtrace = debug_backtrace();
        array_shift($backtrace);
        foreach($backtrace as $i=>$l){
            @$detail .= "[$i] in function {$l['class']}{$l['type']}{$l['function']}";
            if($l['file']) $detail .= " in {$l['file']}";
            if($l['line']) $detail .= " on line {$l['line']}";
            $detail .= "\n";
        }
      }
      if (isset($php_errormsg)) echo "<h1>$php_errormsg</h1>";
      if ($ErrorCount<10) {
      echo "<h3>PHP $ErrType $errstr in $errfile on line $errline</h3><!--\n$detail";
      ini_set('html_errors', 'Off');
      var_dump($errcontext);
      echo "-->";
      }
    }
    $error=EventLog($msg,$detail,"Error");
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
                if (!$dev) die("<br><br><big><b>Oops, well this is embarrassing! &nbsp; An error has occurred:</b>
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

include($_ENV["local"] . 'application.inc');
?>

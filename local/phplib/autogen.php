<a href="../">Go back to web site</a></br>
<?php 
if (!file_exists($LD="/usr/share/phplib")) $LD="";
if ($LD) $LD.="/";

require($LD."db_mysql.inc");    /* change this to match your database eg mysql, odbc, oci8, pgsql */


$skip=array();

if ($windir = $_SERVER["windir"]) {
	echo "Windows Directory: $windir<br />\n";
} else {
	$ar = posix_getpwuid(posix_getuid());
	$owner = $ar["name"];
	echo "Script running as user $owner for user ".get_current_user()."<br />\n";
}
if (!file_exists(".htauth.local")) {
	extract($_REQUEST);
	if ($db and $usr and $pwd) {
		$database = new DB_SQL;
		echo "Testing Credentials..";
		$database->connect($db,"localhost",$usr,$pwd);  /* Test credientials */
		echo "OK<br />\n";
		$path = explode("/",$_SERVER["DOCUMENT_ROOT"]);
		$l = count($path)-2;
		$BN = $path[$l];
		while ($l>0) {
			$l--;
			$HD = $path[$l]."/".$HD;
		}
$AF ='<?php
$_ENV["HomeDirs"] = "'.$HD.'";
$_ENV["BaseName"] = "'.$BN.'";
$_ENV["SubFolder"] = "'.$idir.'";
$_ENV["DocRoot"] = "'.$_SERVER["DOCUMENT_ROOT"].'";
$_ENV["DatabaseClass"] = "DB_".$_ENV["BaseName"];
$_ENV["SessionClass"] = $_ENV["BaseName"]."_Session";
$_ENV["AuthClass"] = $_ENV["BaseName"]."_Auth";
$_ENV["PermClass"] = $_ENV["BaseName"]."_Perm";
$_ENV["Perms"] = "guest,user,editor,admin";
$_ENV["MenuMode"] = "vert";   /*horiz/vert*/
$_ENV["no_edit"] = array("radacct","pp_transactions","EventLog"); /* tables not to be edited */
$_ENV["HelpDesk"] = "on 1300 739 822 from 9am to 8pm";
class DB_'.$BN.' extends DB_Sql {
  var $Host     = "localhost";
  var $Database = "'.$db.'";
  var $User     = "'.$usr.'";
  var $Password = "'.$pwd.'";
}
?>';
		if ($idir) $SD = "/$idir";
		$local = "$HD$BN/public_html$SD/phplib";

		echo "$local/.htauth.local<pre>\n".htmlentities($AF)."\n</pre>";
		if (($windir) or (posix_getuid()==getmyuid())) {  //using Windows or SuPHP
			if ($fp=fopen(".htauth.local","w")) {
				fwrite($fp,$AF);
				fclose($fp);
				mkdir("$local/autogen",0750);
				mkdir("$local/templates",0750);
				mkdir("$local/templates/old",0750);
			}
		} else {  // hopefully cgi-bin using suexec
			require("$LD/web.php");
			echo "Using cgi-bin perl method<br />";
			$data = array("HD"=>$HD,"BN"=>$BN,"SD"=>$idir,"DB"=>$db,"USR"=>$usr,"PWD"=>$pwd);
			if ($password = $_SERVER["PHP_AUTH_USER"]) $password .= ":" . $_SERVER["PHP_AUTH_PW"];
			web($_SERVER["HTTP_HOST"],"/phplib/setup.pl","POST",$data,$password);
		}
		echo "<a href=autogen.php>back</a>";

	} else {
?>
	<h1>Auth file does not exist.</h1><h3>please provide the following.</h3>
<?php 
		$ldr = strlen($_SERVER["DOCUMENT_ROOT"]);
		$cwd = getcwd();
		$SD = substr($cwd,$ldr+1,strlen($cwd)-$ldr-8);
?>
	<form method='post'>
	<table>
	<tr><td>install dir</td><td><input name='idir' value='<?php echo $SD; ?>' /></td></tr>
	<tr><td>database name</td><td><input name='db' value='<?php echo get_current_user(); ?>_db' /></td></tr>
	<tr><td>database user</td><td><input name='usr' value='<?php echo get_current_user(); ?>_user' /></td></tr>
	<tr><td>database pass</td><td><input name='pwd' /></td></tr>
	<tr><td></td><td><input type='submit' value='Create Auth File' /></td></tr>
	</table>
	</form>

<?php	}
	exit;
}


include(".htauth.local");

$_ENV["SiteRoot"] = $_ENV["DocRoot"] . "/";
if ($_ENV["SubFolder"]) $_ENV["SiteRoot"] .= $_ENV["SubFolder"] . "/";

$db=$_ENV["BaseName"];
#$db = "cdr"; $prefix = "cdr";
#$db = "syslog"; $prefix = "syslog"; $skip=array('LinkedTables','db_sequence');
#$db = "postfix"; $prefix = "postfix"; $skip=array('LinkedTables','db_sequence','virt');
#$db = "policyd"; $prefix = "policy"; $skip=array('EventLog','LinkedTables','auth_user'); 
#$db = "traffic"; $prefix = "traffic";

$sitedomainname=$_SERVER["SERVER_NAME"];

$outdir = $_ENV["SiteRoot"]."phplib/autogen";
$dbname = "DB_".$db;
$database = new $dbname;

$host=$database->Host;
$user=$database->User;
$password=$database->Password;
$dbase=$database->Database;

$cart_table = "stock_master";


$siteurl=$sitedomainname;
$sitemaster="info@".str_replace("www.","",$sitedomainname);

if (!file_exists($outdir)) {
	echo "<h1>Output directory does not exist</h1>";
	echo "<h3>Please create writeable by '$owner'</h3>";
	echo "<p>$outdir</p>";
	exit;
}
if (!is_writeable($outdir)) {
	echo "<h3>Output directory exists but it not writeable by '$owner'</h3>";
	echo "<h4>Please make writeable by '$owner'</h4>";
	echo "<p>$outdir</p>";
	echo "<p>Attempting to execute vi perl CGI...</p>";
	require("$LD/web.php");
	$data = array();
	$path = "/phplib/autogen.pl";
	if ($_ENV["SubFolder"]) $path = "/" . $_ENV["SubFolder"] . $path;
	if ($password = $_SERVER["PHP_AUTH_USER"]) $password .= ":" . $_SERVER["PHP_AUTH_PW"];
	echo web($_SERVER["HTTP_HOST"],$path,"POST",$data,$password);
	exit;
}
echo "Files will be output to $outdir\n";

echo "$sitedomainname<br /><br />\n";
echo "<p>Use this command to move the generated files into production...<br />\n";
echo "cd ".$_ENV["SiteRoot"]."/phplib/autogen && cp -i *.inc *form.ihtml ..";
echo "<br /></p>\n";

ini_set("display_errors","on");

function valid_name($str) {
    $str = str_replace(" ","_",$str);
    $str = str_replace("(","_",$str);
    $str = str_replace(")","_",$str);
    $str = str_replace("/","_",$str);
    $str = str_replace(",","_",$str);
    $str = str_replace("#","_",$str);
    $str = str_replace("`","_",$str);
    $str = str_replace("'","_",$str);
    $str = str_replace('"',"_",$str);
    $str = str_replace("-","_",$str);
    return $str;
}

function neatstr($InpStr)
{
	global $key_names, $i;
	$OutStr = "";
	$pos = 0;
	$done = strlen($InpStr);
	do {
		$ch = substr($InpStr,$pos,1);
		if ($pos>0) if ($ch<'a') $OutStr .= " ";
		$OutStr .= $ch;
		$pos++;
	} while ($pos<$done);
	if ($InpStr==$key_names[$i] and $InpStr=='id') $OutStr="";
	return str_replace("_","",$OutStr);
}

$tables = $database->table_names();
$j=0;
foreach($tables as $i => $table) {
    $tbname = $table["table_name"];
    if (array_search($tbname,$skip)===false) 
    {
    	$tb_names[$j] = $tbname;
    	$tbnames[$j] = str_replace(" ","",$tb_names[$j]);
    	$classnames[$j] = str_replace("-","",$tbnames[$j]);
	$key_names[$j] = $database->primary_key($tbname);
	if (!$key_names[$j]) {
		$key_names[$j]="id";
    		echo $tb_names[$j] . " has no primary key. Assuming 'id' will be created.<BR>";
	} else {
    		echo $tb_names[$j] . " indexed by ".$key_names[$j]. "<BR>";
	}
    	$keynames[$j] = valid_name($key_names[$j]);
	$j++;
#	$md = $database->metadata($tbname);
#	var_dump($md);
    }
    else 
	echo "Skipping $tbname<br />";
}


if ($_POST["files"]) {
    switch ($database->type) {
      case "mysql":
	$cmd = "cat $LD".implode(" $LD",$_POST["files"])." | mysql -u$user -p$password $dbase";
	break;
      case "pgsql":
	$cmd = "cat $LD".implode(" $LD",$_POST["files"])." | psql --password $password $dbase $user";
	break;
    } 
    if ($cmd) {
	echo $cmd;
	system($cmd);
    } else {
	echo "unsupport database server type ".$database->type;
    }
    echo "<a href=autogen.php>back</a>";
    exit;
}


echo "Found $j tables in $dbase";

echo "<form method='post'>Do you want to import? <br />\n";
echo "<input name='files[]' type='checkbox' checked='checked' value='phplib.sql'> phplib.sql<br />\n";
echo "<input name='files[]' type='checkbox' checked='checked' value='iso_country_list.sql'> iso_country_list.sql<br />\n";
echo "<input type='submit' value='Import'>\n</form>";
if (!$j) {
	exit;
}
$tablecount = $j;

echo "<br />\n";

//Process setup.inc for auto_init session management
$fset = fopen("$outdir/setup.inc","w");
fwrite($fset,"<?php global \$HTTP_REFERER, \$REMOTE_ADDR, \$HTTP_USER_AGENT, \$cart;

\$cart = new My_Cart;
\$cart->start();

\$db   = new DB_".$db.";
\$tab  = \"session_stats\";

\$now = date(\"YmdHis\", time());
\$query = sprintf(\"insert into %s ( name,  sid, start_time, referer, addr, user_agent ) values ( '%s', '%s',       '%s',    '%s', '%s',       '%s' )\",
  \$tab,
  \$sess->name,
  \$sess->id,
  \$now,
  \$HTTP_REFERER,
  \$REMOTE_ADDR,
  \$HTTP_USER_AGENT);

\$db->query(\$query);
?>\n");
fclose($fset);

//Process Master local.inc Output File first
$floc = fopen("$outdir/".$prefix."local.inc","w");
if (!$prefix) include("autogen.local");

$fusr = fopen("$outdir/new_user.php","w");
include("autogen.new_user");
fclose($fusr);

$fmenu = fopen("$outdir/$db.php","w");
$ftop = fopen("$outdir/toplink.inc","w");
fwrite($fmenu,"<?php include('phplib/prepend.php');
page_open(array(\"sess\" => \"".$db."_Session\")); ?>
<html><head><title>smeg $db Database</title></head><body>
<font class=bigTextBold align=\"CENTER\">$db Database</font>\n");




//Enumerate Tables in Database
$i = 0;
while ($i < $tablecount) {
switch ($tb_names[$i]) {
    case "active_sessions":
    case "active_sessions_split":
    case "session_stats":
    case "activity_log":
    case "auth_user_md5":
    case "db_sequence":
    case "login":
    case "logout":
        break;
    default:



echo "SHOW FIELDS FROM ".$tb_names[$i]."<br />\n";



// Open Per Table Output Files - this new method prevents is requires for databases with lots of tables.
$finc = fopen("$outdir/$tbnames[$i].inc","w");
// $finc = $floc;  /* old way was have a monolithic local.inc with all tables in it */
$fihtml = fopen("$outdir/$tbnames[$i]form.ihtml","w");
$fphp = fopen("$outdir/$tbnames[$i].php","w");

$tblobfound = "no";

//Create Header for TableName.inc
//Now written to local.inc file not TableName.inc
fwrite($finc,"<?php

class $classnames[$i]form extends tpl_form {
  var \$table = \"$tb_names[$i]\";
  var \$key = \"$keynames[$i]\";
  var \$classname = \"$classnames[$i]form\";
  var \$database_class = \"DB_$db\";

  function setup_fields () {
");


//Create Header for TableNameform.ihtml
fwrite($fihtml,"<?php
  \$this->form_data->start(\"".$tbnames[$i]."form\");
  if (\$this->error) printf(\"<P class=error>Error: %s %s</p>\\n\", \$ErrorFieldName, \$this->error);  ?>\n".$atcjs." <table> ");

if ($tbnames[$i]==$cart_table) { 
	$AddToCart = "\n    case \"AddToCart\":"; 
	$CartExtra='

include($_ENV["libdir"]."currency.inc");     // setup currency data for shopping cart
$CurrencySelector = showCurrencyData(true);     // false = local currency only, true = all known currencies
echo "Currency: $CurrencySelector<br />";         // uncomment this so end users can change the displayed currency
                                                // be sure that update_exchange_rates is set to run in cron jobs

';

} else {
	$AddToCart = $CartExtra = "";
}

//Create TableName.php
fwrite($fphp,"<?php
include('phplib/prepend.php');

page_open(array(\"sess\"=>\"".$db."_Session\",\"auth\"=>\"".$db."_Auth\",\"perm\"=>\"".$db."_Perm\"));

echo \"<h1>".neatstr($classnames[$i])."</h1>\";

check_view_perms();

\$f = new ".$classnames[$i]."form;

if (\$submit) {
  switch (\$submit) {".$AddToCart."
   case \"Copy\": \$id=\"\";
   case \"Save\":
    if (\$id) \$submit = \"Edit\";
    else \$submit = \"Add\";
   case \"Add\":
   case \"Edit\":
    if (isset(\$auth)) {
     check_edit_perms();
     if (!\$f->validate()) {
        \$cmd = \$submit;
        echo \"<font class='bigTextBold'>\$cmd ".neatstr($tbnames[$i])."</font>\\n\";
        \$f->display();
        page_close();
        exit;
     }
     else
     {
        echo \"Saving....\";
        \$f->save_values();
        echo \"<b>Done!</b><br />\\n\";
        echo \"<META HTTP-EQUIV=REFRESH CONTENT=\\\"2; URL=\".\$sess->self_url().\"\\\">\";
        echo \"&nbsp;<a href=\\\"\".\$sess->self_url().\"\\\">Back to ".$tb_names[$i].".</a><br />\\n\";
        page_close();
        exit;
     }
    } else {
        echo \"You are not logged in....\";
        echo \"<b>Aborted!</b><br />\\n\";
    }
   case \"View\":
   case \"Back\":
        echo \"<META HTTP-EQUIV=REFRESH CONTENT=\\\"0; URL=\".\$sess->self_url().\"\\\">\";
        echo \"&nbsp;<a href=\\\"\".\$sess->self_url().\"\\\">Back to ".$tb_names[$i].".</a><br />\\n\";
        page_close();
        exit;
   case \"Delete\":
    if (isset(\$auth)) {
        check_edit_perms();
        echo \"Deleting....\";
        \$f->save_values();
        echo \"<b>Done!</b><br />\\n\";
    } else {
        echo \"You are not logged in....\";
        echo \"<b>Aborted!</b><br />\\n\";
    }
        echo \"<META HTTP-EQUIV=REFRESH CONTENT=\\\"2; URL=\".\$sess->self_url().\"\\\">\";
        echo \"&nbsp;<a href=\\\"\".\$sess->self_url().\"\\\">Back to ".$tb_names[$i].".</a><br />\\n\";
        page_close();
        exit;
  }
} else {
    if (\$".$keynames[$i].") {
	\$f->find_values(\$".$keynames[$i].");
    }
}

\$f->javascript();

".$CartExtra."
switch (\$cmd) {
    case \"View\":
    case \"Delete\":
	\$f->freeze();
    case \"Add\":
".$AddToCart."
    case \"Copy\":
	if (\$cmd==\"Copy\") \$id=\"\";
    case \"Edit\":
	echo \"<font class='bigTextBold'>\$cmd ".neatstr($tbnames[$i])."</font>\\n\";
	\$f->display();
	break;
    default:
	\$cmd=\"Query\";
	\$t = new ".$classnames[$i]."Table;
	\$t->heading = 'on';
	\$t->add_extra = 'on';   /* or set to base url of php file to link to, defaults to PHP_SELF */
	\$t->add_total = 'on';   /* add a grand total row to the bottom of the table on the numberic columns */
	\$t->add_insert = '".$classnames[$i]."form';  /* Add a blank row ontop of table allowing insert or search */
	\$t->add_insert_buttons = 'Search';   /* Control which buttons appear on the add_insert row eg: Add,Search */
	/* See below - EditMode can also be turned on/off by user if section below uncommented */
	#\$t->edit = '".$classnames[$i]."form';   /* Allow rows to be editable with a save button that appears onchange */
	#\$t->ipe_table = '".$tbnames[$i]."';   /* Make in place editing changes immediate without a save button */

	\$db = new DB_".$db.";

        echo \"<a href=\\\"\".\$sess->self_url().\$sess->add_query(array(\"cmd\"=>\"Add\")).\"\\\">Add ".neatstr($tbnames[$i])."</a>\\n\";

");



//Loop through each field to create body of files.
$DataModifierRead = "";
$DataModifierWrite = "";
$fvals = "";
$fcols = "";
$fdisp = "";
$fnams = "";
$fglob = "";
$ptype = "";
$preproc = "";
$j = 0;
$wtype="hidden";
$PrimaryKey = "";
$UniqueKey = "";
$fkey = "";

$metadata=$database->metadata($tb_names[$i]);

foreach($metadata as $j => $md) {
    echo "Information for column $j table $tb_names[$i] with key $key_names[$i]:<BR>\n";

    $fkey = $md["key"]; 
    $ftype = $md["type"];
    $PrevWtype = $wtype;
    $fsize = $md["chars"];
    $wtype = $ftype;
    $fname = $md["name"];
    $fdefault = $md["default"];
    $fnull = $md["null"];
    $fextra = $md["extra"];
    $fcomment = $md["comment"];
    if ($fkey=="PRI") { $PrimaryKey = $fname; }
    if ($fkey=="UNI") { $UniqueKey = $fname; }
    if ( preg_match("/int$/i",$ftype) ) { $wtype = "int"; $fsize=12; }
    if ( preg_match("/text$/i",$ftype) ) { $wtype = "text"; }
    if ( $ftype=="bigint" ) { $fsize = 18; }
    if ( $ftype=="float" ) { $fsize = 15; }
    if ( $ftype=="mediumtext" ) { $wtype = "html"; } 
    if ( preg_match("/BLOB/i",$ftype) ) { $wtype = "nblob"; $fsize="800000"; $sqlbits=16; }	// Pictures to be displayed on detail page as normal
    if ( $ftype=="tinyblob" ) { $wtype = "tblob"; $fsize="200000"; $sqlbits=8;
				$tblobfound =  $fname; }		// Thumbnail Pictures to be displayed on table view
    if ( $ftype=="mediumblob" ) { $wtype = "mblob"; $fsize="2000000"; $sqlbits=24; }  // Extra Large Pictures for full screen view.
    if ( $ftype=="longblob" ) { $wtype = "lblob"; $fsize="2000000"; $sqlbits=32; }	// Extra Large Pictures for full screen view.
    if ( !strcmp($fextra,"auto_increment") ) { $wtype = "hidden"; }
    if ( $fname=='id' and $j==0 ) $wtype = "hidden";
    $sqlsize = $md["len"];

    $fvname = valid_name($fname);
    if (strcmp($PrevWtype,"hidden")) $fvals .= ",";
    if ($j>0) $fglob .= ",";
    if (strcmp($PrevWtype,"hidden")) $fnams .= ",";
    if (strcmp($wtype,"hidden")) $fnams .= $database->quote_identifier($fname);
    $fglob .= "$".$fvname;
    
    
// Output some diags to web page
    echo "<PRE>
Field:        ".$fname."
Type:         ".$ftype."
Size:         ".$fsize."
Key:	      ".$fkey."
Null:         ".$fnull."
Default:      ".$fdefault."
Extra:        ".$fextra."
Comments:     ".$fcomment."
WebType:      ".$wtype."
</PRE>";

      $ValidRegex = "";
      $ExtraHtml = "";
      if ($fnull=="YES") $AllowNull="null";
	else $AllowNull="";
      switch($wtype) {
	case "set"  :
		$multicount = substr_count($fsize,",");
		fwrite($fihtml,"    <tr><td>".neatstr($fname)."</td><td>\n");
		fwrite($fihtml,"<table border=1><tr><td>"); $count=0; $totalcount=0;
		foreach(explode(",",$fsize) as $value) {  $count++; $totalcount++;
		  fwrite($fihtml,"	      <?php \$this->form_data->show_element('".$fvname."',".$value."); ?>");
		  fwrite($fihtml,trim(strtr($value,"'"," "))."\n");
		  if ($totalcount <= $multicount) {
		    fwrite($fihtml,"</td>"); 
		    if ($multicount > 6 ) { 
			if ($count == 4) { fwrite($fihtml,"</tr><tr>"); $count=0; }
		    }
		    fwrite($fihtml,"<td>");
		  }
		}
		fwrite($fihtml,"</td></tr></table>");
		fwrite($fihtml,"	</td></tr>\n");
		break;
	case "tblob":
      		fwrite($fihtml,"<?php if ( \$cmd==\"Edit\" || \$cmd==\"Add\" ) { ?>   
		<tr><td>".neatstr($fname)."</td><td> 
		<?php if ( \$cmd!=\"Add\" ) { \$this->show_image('".$fvname."',\$".$keynames[$i].",'".$tb_names[$i]."','".$key_names[$i]."'); }
		if ( \$cmd==\"Add\" || \$cmd==\"Edit\" ) {
		\$this->form_data->show_element('".$fname."');
		} ?> </td></tr>\n<?php } ?>");
		break;
	case "nblob":
      		fwrite($fihtml,"    <tr><td>".neatstr($fname)."</td><td> 
		<?php if ( \$cmd!=\"Add\" ) { \$this->show_image('".$fvname."',\$".$keynames[$i].",'".$tb_names[$i]."','".$key_names[$i]."'); }
		if ( \$cmd==\"Add\" || \$cmd==\"Edit\" ) {
		\$this->form_data->show_element('".$fvname."');
		} ?> </td></tr>\n");
		break;
	case "lblob":
      		fwrite($fihtml,"    <tr><td>".neatstr($fname)."</td><td> 
		<?php if ( \$cmd!=\"Add\" ) { \$this->show_image_href('".$fvname."',\$".$keynames[$i].",'".$tb_names[$i]."','".$key_names[$i]."'); }
		if ( \$cmd==\"Add\" || \$cmd==\"Edit\" ) {
		\$this->form_data->show_element('".$fvname."');
		} ?> </td></tr>\n");
		break;
	case "time":
		$ValidRegex = ',"valid_e"=>"Invalid Time","valid_regex"=>"'.$wtype.'"';
                fwrite($fihtml,"    <tr><td>".neatstr($fname)."</td><td>
                <?php \$this->form_data->show_element('".$fvname."'); ?>
		<a href=\"javascript:show_help('helptime.php');\">Help</a>
		</td></tr>\n");
		break;
	case "date":
	case "timestamp":
	case "datetime":
		$ExtraHtml = " onBlur=\\\"ajax_update_element(this,'find.php?dt=')\\\"";
		$ValidRegex = ',"valid_e"=>"Invalid Date","valid_regex"=>"'.$AllowNull.$wtype.'"';
		fwrite($fihtml,"    <tr><td>".neatstr($fname)."</td><td>
		<?php \$this->form_data->show_element('".$fvname."'); ?> 
                <?php if (!isset(\$this->form_data->elements['".$fvname."']['frozen'])) { ?>
			<a href=\"javascript:show_cal('".$classnames[$i]."form', '".$fvname."');\">
			<img src=/image/cal.gif width=16 height=16 border=0 
				alt=\"Click here to pick a date from the calendar\"></a>
                <?php } ?>
		</td></tr>\n");
		break;
	default :
		fwrite($fihtml,"    <tr><td>".neatstr($fname)."</td><td>
		<?php \$this->form_data->show_element('".$fvname."'); ?> </td></tr>\n");
      }


    if ($fkey=="PRI" and $fvname!="id") {
	fwrite($fihtml,"\t<input type='hidden' name='id' value='<?php echo \$GLOBALS['".$fvname."']; ?>' />\n");
    }


    fwrite($finc,"    \$this->form_data->add_element(array(");
    $oohtype = "text";
    switch($wtype) {
        case "set":
		$preproc .= "\n\t\t    if (isset(\$".$fvname. 
			")) {\$".$fvname."List = addslashes(implode(\$".$fvname.",\",\"));}";
                $fvals .= "'$".$fvname."List'";
                if (strlen($fdisp)>1) $fdisp .= ","; $fdisp .= "\n\t\t\t\"".$fname.'"=>"'.neatstr($fvname).'"';
                if (strlen($fcols)>1) $fcols .= ","; $fcols .= "\n\t\t\t\"".$fname.'"';
                fwrite($finc,'"type"=>"checkbox","multiple"=>1,"name"=>"'.$fvname.'",
                "field"=>"'.$fname.'","options"=>array('.$fsize.'),
		"extrahtml"=>"class=checkBoxes"');
    		if ( isset($fdefault) ) { fwrite($finc,',"value"=>"'.$fdefault.'"'); }
                break;
	case "enum": 
    		$fvals .= "'$".$fvname."'";
                if (strlen($fdisp)>1) $fdisp .= ","; $fdisp .= "\n\t\t\t\"".$fname.'"=>"'.neatstr($fvname).'"';
                if (strlen($fcols)>1) $fcols .= ","; $fcols .= "\n\t\t\t\"".$fname.'"';
		fwrite($finc,'"type"=>"select","name"=>"'.$fvname.'","field"=>"'.$fname.'",
		"extrahtml"=>"class=\'dropdownMenu\'",
		"options"=>array('.$fsize.')');
    		if ( isset($fdefault) ) { fwrite($finc,',"value"=>"'.$fdefault.'"'); }
		break;
	case "int":
    		if ($j>0) $fvals .= "'$".$fvname."'";
                if (strlen($fdisp)>1) $fdisp .= ","; $fdisp .= "\n\t\t\t\"".$fname.'"=>"'.neatstr($fvname).'"';
                if (strlen($fcols)>1) $fcols .= ","; $fcols .= "\n\t\t\t\"".$fname.'"';
		if ($AllowNull=="null") $ValidRegex = "^[0-9|\-]+$|^$";
		else $ValidRegex = "^[0-9|\-]+$";
		fwrite($finc,'"type"=>"text","name"=>"'.$fvname.'","size"=>"'.$fsize.'",
		"valid_regex"=>"'.$ValidRegex.'","field"=>"'.$fname.'",
		"valid_e"=>"'.neatstr($fname).
			' must be a whole number containing digits 0-9 only. May also start with negative symbol -",
		"extrahtml"=>""');
    		if ( isset($fdefault) ) { fwrite($finc,',"value"=>"'.$fdefault.'"'); }
		break;
	case "year":
    		$fvals .= "'$".$fvname."'";
                if (strlen($fdisp)>1) $fdisp .= ","; $fdisp .= "\n\t\t\t\"".$fname.'"=>"'.neatstr($fvname).'"';
                if (strlen($fcols)>1) $fcols .= ","; $fcols .= "\n\t\t\t\"".$fname.'"';
		fwrite($finc,'"type"=>"text","name"=>"'.$fvname.'","size"=>"'.$fsize.'",
		"valid_regex"=>"^[19|20][0-9]+$","minlength"=>"4","maxlength"=>"4","field"=>"'.$fname.'",
		"valid_e"=>"'.neatstr($fname).' must be a whole number between 1900 and 2099",
		"extrahtml"=>""');
    		if ( isset($fdefault) ) { fwrite($finc,',"value"=>"'.$fdefault.'"'); }
		break;
        case "html":
                $fvals .= "'$".$fvname."'";
                $DataModifierRead .= "\$".$fname." = stripslashes(\$".$fvname.");\n";
                $DataModifierWrite .= "\$".$fname." = addslashes(\$".$fvname.");\n";
                fwrite($finc,'"type"=>"htmlarea","name"=>"'.$fvname.'","height"=>"700px","width"=>"750px",
                "field"=>"'.$fname.'","extrahtml"=>""');
                if ( isset($fdefault) ) { fwrite($finc,',"value"=>"'.$fdefault.'"'); }
                break;
	case "text":
    		$fvals .= "'$".$fvname."'";
		$DataModifierRead .= "\$".$fname." = stripslashes(\$".$fvname.");\n";
		$DataModifierWrite .= "\$".$fname." = addslashes(\$".$fvname.");\n";
		fwrite($finc,'"type"=>"textarea","name"=>"'.$fvname.'","rows"=>"5","cols"=>"50",
		"field"=>"'.$fname.'","extrahtml"=>""');
    		if ( isset($fdefault) ) { fwrite($finc,',"value"=>"'.$fdefault.'"'); }
		break;
	case "lblob":
	case "mblob":
	case "tblob":
	case "nblob":
    		$fvals .= "'\".\$this->getblob(\"".$fvname."\").\"'";
		fwrite($finc,'"type"=>"file","name"=>"'.$fvname.'","size"=>"'.$fsize.'","sqlsize"=>"'.$sqlsize.'",
		"field"=>"'.$fname.'","extrahtml"=>""');
		break;
	case "hidden":
    		if ($j>0) $fvals .= "'$".$fvname."'";
		fwrite($finc,'"type"=>"hidden","name"=>"'.$fvname.'","field"=>"'.$fname.'","size"=>"'.$fsize.'"');
		break;
	case "decimal":
		$ExtraHtml = " onBlur='dollarformat(this)'";
		$ValidRegex = ',"valid_e"=>"Invalid Price","valid_regex"=>"^[$|-]?[0-9|.]+$"';
                $fquer .= "\n\t\"".$fvname."\" => \"".$fname."\"";
                $fvals .= "'$".$fvname."'";
                if (strlen($fdisp)>1) $fdisp .= ","; $fdisp .= "\n\t\t\t\"".$fname.'"=>"'.neatstr($fvname).'"';
                if (strlen($fcols)>1) $fcols .= ","; $fcols .= "\n\t\t\t\"".$fname.'"';
		$DataModifierRead .= "\$".$fvname." = \"$\".\$".$fvname.";\n\t";
		$DataModifierRead .= "\$this->form_data->elements[\"".$fvname."\"][\"ob\"]->value=\$".$fvname.";\n\t";
		$DataModifierWrite .= "\$".$fvname." = str_replace('$','',\$".$fvname.");\n\t";
                fwrite($finc,'"type"=>"text","field"=>"'.$fname.'","name"=>"'.$fvname.'","size"=>"10"');
                if ($fnull!="YES") fwrite($finc,'
                ,"minlength"=>1,"length_e"=>"'.neatstr($fname).' must not be blank.  Required field."');
                fwrite($finc,',"extrahtml"=>"'.$ExtraHtml.'"
                '.$ValidRegex);
                if ( isset($fdefault) ) { fwrite($finc,',"value"=>"'.$fdefault.'"'); }
		break;
	case "date":
	case "datetime":
	case "timestamp":
		$oohtype="date";
	default:
    		$fquer .= "\n\t\"".$fname."\" => \"".$fname."\"";
    		$fvals .= "'$".$fvname."'";
                if (strlen($fdisp)>1) $fdisp .= ","; $fdisp .= "\n\t\t\t\"".$fname.'"=>"'.neatstr($fvname).'"';
                if (strlen($fcols)>1) $fcols .= ","; $fcols .= "\n\t\t\t\"".$fname.'"';
		$fwidth = floor($fsize / 3);
		fwrite($finc,'"type"=>"'.$oohtype.'","name"=>"'.$fvname.'","maxlength"=>"'.$fsize.'","size"=>"'.$fwidth.'"');
		if ($fnull!="YES") fwrite($finc,'
		,"minlength"=>1,"length_e"=>"'.neatstr($fname).' must not be blank.  Required field."');
		fwrite($finc,',
		"field"=>"'.$fname.'","extrahtml"=>"'.$ExtraHtml.'"
		'.$ValidRegex);
    		if ( isset($fdefault) ) { fwrite($finc,',"value"=>"'.$fdefault.'"'); }
    }
    fwrite($finc,"));\n");
    $j++;
} //while fields



//If items table add quantity field
if ( $tbnames[$i] == $cart_table ) {
fwrite($finc,"    \$this->form_data->add_element(array(");
fwrite($finc,'"type"=>"text","name"=>"qty","value"=>"1","extra_html"=>" onChange=\'showTotalPrice(qty.value);\'"');
fwrite($finc,"));\n");
fwrite($fihtml,"    <tr><td>&nbsp;</td><td>
                <?php \$this->form_data->show_element('qty');
                ?> </td></tr>\n
    <tr><td>Total Price</td><td>
        <table><tr><td>
        <table border=1><tr><td>
                <div class=dynamicText id=totalPrice></div>

                <ilayer id=NStotalPrice1>
                        <layer left=8 top=0 width=180 height=300 id=NStotalPrice2></layer>
                </ilayer>
        </td></tr></table></td><td>
        <a href='javascript:showTotalPrice(qty.value)'>recalc</a></td></tr></table>
                        </td></tr>
<?php if (\$cmd=='AddToCart') { ?>
        <script Language=JavaScript>
                showTotalPrice(1);
        </script>
<?php } ?>
");
}

// Add a submit button
fwrite($finc,"    \$this->form_data->add_element(array(");
fwrite($finc,'"type"=>"submit","name"=>"submit","value"=>"Submit"');
fwrite($finc,"));\n");
fwrite($fihtml,"    <tr><td>&nbsp;</td><td> 
		<?php 
                if (\$cmd==\"View\") {
                        \$cmd=\"Back\";
                        echo \"<a href=\".\$sess->url(\"".$tb_names[$i].".php\");
                        echo \$sess->add_query(array(\"cmd\"=>\"Edit\",\"".$key_names[$i]."\"=>\$GLOBALS[\"".$key_names[$i]."\"]));
                        echo \">Edit</a>&nbsp;\";
			echo \"<a href='\".\$sess->url(\"".$tb_names[$i].".php\").\"'>Back</a>\";
                } else {
                        if (\$cmd==\"Add\") \$cmd=\"Save\";
                        if (\$cmd==\"Edit\") \$cmd=\"Save\";
                        \$this->form_data->show_element('submit',\$cmd);
                }
		?> </td></tr>\n");

// Add a reset button
/*
fwrite($finc,"    \$this->form_data->add_element(array(");
fwrite($finc,'"type"=>"reset","name"=>"reset","value"=>"Reset"');
if ( $tbnames[$i] == $cart_table ) fwrite($finc,',"extra_html"=>" onChange=\'showTotalPrice(qty.value);\'"');
fwrite($finc,"));\n");
fwrite($fihtml,"    <tr><td>&nbsp;</td><td> 
		<?php \$this->form_data->show_element('reset');	
		?> </td></tr>\n");
<?
*/


// Write tail end of files.

fwrite($fphp,"
        if (array_key_exists(\"".$classnames[$i]."_fields\",\$_REQUEST)) \$".$classnames[$i]."_fields = \$_REQUEST[\"".$classnames[$i]."_fields\"];
        if (empty(\$".$classnames[$i]."_fields)) {
                \$".$classnames[$i]."_fields = \$t->default;
                \$sess->register(\"".$classnames[$i]."_fields\");
        }
        
        \$t->fields = \$".$classnames[$i]."_fields;
		
	#\$t->extra_html = array('fieldname'=>'extrahtml');
	#\$t->align      = array('fieldname'=>'right', 'otherfield'=>'center'); 	

/* Uncomment this section to allow end user to change which columns are displayed in the table.
        echo \"<a href=javascript:show('ColumnSelector')>Column Chooser</a>\";
        echo \"<form id=ColumnSelector method='post' style=display:none>\\n\";
        echo \"<a href=javascript:hide('ColumnSelector')>Hide</a>\";
        echo \" Columns: \";
        foreach (\$t->all_fields as \$field) {
                if (in_array(\$field,\$".$classnames[$i]."_fields,TRUE)) \$chk = \"checked='checked'\"; else \$chk=\"\";
                echo \"\\n<input type='checkbox' \$chk name=".$classnames[$i]."_fields[] value='\$field' />\$field \";
        }
        echo \"\\n<input type=submit name=setcols value='Set' />\";
        if (\$sess->have_edit_perm()) {
            if (\$EditMode=='on') {
                \$on='checked=\"checked\"'; \$off='';
		\$t->edit = '".$classnames[$i]."form';   
		# \$t->ipe_table = '".$tbnames[$i]."';   #uncomment this for immediate table update (no save button)
            } else {
                \$off='checked=\"checked\"'; \$on='';
            }
            echo \"\\nEdit Mode <input type='radio' name='EditMode' value='on' \$on> On <input type='radio' name='EditMode' value='off' \$off /> Off \";
        } else {
            \$EditMode='';
        }
        echo \"\\n</form>\\n\";
*/

  // When we hit this page the first time,
  // there is no $q.
  if (!isset(\$q_".$classnames[$i].")) {
    \$q_".$classnames[$i]." = new ".$classnames[$i]."_Sql_Query;     // We make one
    \$q_".$classnames[$i]."->conditions = 1;     // ... with a single condition (at first)
    \$q_".$classnames[$i]."->translate  = \"on\";  // ... column names are to be translated
    \$q_".$classnames[$i]."->container  = \"on\";  // ... with a nice container table
    \$q_".$classnames[$i]."->variable   = \"on\";  // ... # of conditions is variable
    \$q_".$classnames[$i]."->lang       = \"en\";  // ... in English, please
    \$q_".$classnames[$i]."->primary_key = \"".$key_names[$i]."\";  // let Query engine know primary key
    \$q_".$classnames[$i]."->default_query = \$db->qi(\"".$key_names[$i]."\").\"!='0' desc\";  // let Query engine know primary key

    \$sess->register(\"q_".$classnames[$i]."\");   // and don't forget this!
  }

  if (\$rowcount) {
        \$q_".$classnames[$i]."->start_row = \$startingwith;
        \$q_".$classnames[$i]."->row_count = \$rowcount;
  }

  if (\$submit=='Search') \$query = \$q_".$classnames[$i]."->search(\$t->map_cols);

  // When we hit that page a second time, the array named
  // by \$base will be set and we must generate the \$query.
  // Ah, and don\'t set \$base to \"q\" when \$q is your Sql_Query
  // object... :-)
  if (array_key_exists(\"x\",\$_POST)) {
    get_request_values(\"x\");
    \$query = \$q_".$classnames[$i]."->where(\"x\", 1);
    \$hideQuery = \"\";
  } else {
    \$hideQuery = \"style='display:none'\";
  }

  if (!\$sortorder) \$sortorder=\"".$key_names[$i]."\";
  if (empty(\$query)) { \$query=\"1 order by  \".\$db->qi(\"".$key_names[$i]."\").\" desc\"; }
  \$db->query(\"SELECT COUNT(*) as total from \".\$db->qi(\"".$tb_names[$i]."\").\" where \".\$query);
  \$db->next_record();
  if (\$db->f(\"total\") < (\$q_".$classnames[$i]."->start_row - \$q_".$classnames[$i]."->row_count))
      { \$q_".$classnames[$i]."->start_row = \$db->f(\"total\") - \$q_".$classnames[$i]."->row_count; }

  if (\$q_".$classnames[$i]."->start_row < 0) { \$q_".$classnames[$i]."->start_row = 0; }

  \$query .= \" LIMIT \".\$q_".$classnames[$i]."->start_row.\",\".\$q_".$classnames[$i]."->row_count;

  // In any case we must display that form now. Note that the
  // \"x\" here and in the call to \$q->where must match.
  // Tag everything as a CSS \"query\" class.
  echo \"<a href=javascript:show('customQuery')>Custom Query</a>\";
  echo \"\\n<div id=customQuery \$hideQuery><a href=javascript:hide('customQuery')>Hide</a>\\n\";
  printf(\$q_".$classnames[$i]."->form(\"x\", \$t->map_cols, \"query\"));
  echo \"\\n</div>\\n\";

  // Do we have a valid query string?
  if (\$query) {
    // Show that condition
    echo \"<a href=javascript:show('QueryStats')>Query Stats</a><div id=QueryStats style=display:none>\";
    echo \"<a href=javascript:hide('QueryStats')>Hide</a><br>\";
    printf(\"Query Condition = %s<br />\\n\", \$query);

    // Do that query
    \$db->query(\"select * from \".\$db->qi(\"".$tb_names[$i]."\").\" where \". \$query);

    // Dump the results (tagged as CSS class default)
    printf(\"Query Results = %s<br /></div>\\n\", \$db->num_rows());
    echo \"<br />\";
    \$t->show_result(\$db, \"default\");
  }
} // switch \$cmd\n");
if ( $tbnames[$i] == $cart_table ) {
   fwrite($fphp,"  echo \"<hr />\\n\";\n  \$cart->show_all();\n");
}
fwrite($fphp,"page_close();\n?>\n");
fclose($fphp);

fwrite($finc,"  }

}
class ".$classnames[$i]."Table extends Table {
  var \$classname = \"".$classnames[$i]."Table\";
  var \$sql_table = \"$tb_names[$i]\";
  var \$primary_key = \"$keynames[$i]\";
  var \$all_fields = array(".$fcols.");

  /* comment out or delete some of these default entries so that the table isn't too wide for the screen */
  var \$default = array(".$fcols.");

  // These fields will be searchable and displayed in results.
  // Format is \"RealFieldName\"=>\"Field Name Formatted For Display\",
  var \$map_cols = array(".$fdisp.");
");
if ( $tbnames[$i] == $cart_table )
  fwrite($finc,"

  function table_cell_open(\$class='', \$align='', \$title='', \$key, \$index)
  {
    printf(\"  <td id='%s%s'%s%s%s>\",\$key,\$index,
      \$class?\" class='\$class'\":\"\",
      \$title?\" title='\$title'\":\"\",
      \$align?\" align='\$align'\":\"\");
  }

");

if ( $tbnames[$i] == $cart_table ) {
  fwrite($finc,"

  function table_row_add_extra(\$row, \$row_key, \$data, \$class=\"\") {
        global \$sess, \$PHP_SELF, \$_ENV;
	\$db = new DB_".$db."; \n");

  fwrite($finc,"
        \$price = \$cart->fields[\"price\"];
        \$key = \$cart->fields[\"key\"];
        \$jsPrices .= \$data[\$key].\":\".\$data[\$price].\", \";
");

if ( $tblobfound!="no" ) {
  fwrite($finc,"        echo \"<td><img src=\\\"/\".\$data[\"".$tblobfound."\"].\"\\\"></td>\";"); 
}
fwrite($finc,"
        echo \"<td class='btable'>\";
	echo \"<a href=\\\"\".\$sess->url('".$tbnames[$i].".php').
                \$sess->add_query(array(\"cmd\"=>\"View\",\"".$keynames[$i]."\"=>\$data[\"".$key_names[$i]."\"])).\"\\\">view</a>\";
");
if (!in_array($tbnames[$i],$_ENV["no_edit"])) fwrite($finc,"

  if (\$sess->have_edit_perm()) {
        echo \" <a href=\\\"\".\$sess->url('$tbnames[$i].php').
                \$sess->add_query(array(\"cmd\"=>\"Copy\",\"".$keynames[$i]."\"=>\$data[\"".$key_names[$i]."\"])).\"\\\">copy</a>\";
        echo \" <a href=\\\"\".\$sess->url('$tbnames[$i].php').
                \$sess->add_query(array(\"cmd\"=>\"Edit\",\"".$keynames[$i]."\"=>\$data[\"".$key_names[$i]."\"])).\"\\\">edit</a>\";
        echo \" <a href=\\\"\".\$sess->url('$tbnames[$i].php').
                \$sess->add_query(array(\"cmd\"=>\"Delete\",\"".$keynames[$i]."\"=>\$data[\"".$key_names[$i]."\"])).\"\\\">delete</a>\";
  }\n");
fwrite($finc,"        \$js = \"<a href=javascript:cart('\".\$data[\"".$key_names[$i]."\"].\"',this)>add to cart</a>\";
        echo \" <script>document.write(\\\"\$js\\\");</script>\\n\";
      echo \" <noscript><a href=\\\"\".\$sess->url('$tbnames[$i].php').
                \$sess->add_query(array(\"cmd\"=>\"AddToCart\",\"".$keynames[$i]."\"=>\$data[\"".$key_names[$i]."\"])).\"\\\">add&nbsp;to&nbsp;cart</a></noscript>\";");
fwrite($finc,"
	if (\$this->edit) {
                echo \"<input type='submit' value='Save' name='submit' class='ipeh'> \";
                echo \"<input type='hidden' value='\".\$data[\"id\"].\"' name='id'> \";
	}
        echo \"</td>\";\n
   }"); 
} // if cart table
fwrite($finc,"}\n");
fwrite($floc,"  
class ".$classnames[$i]."_Sql_Query extends Sql_Query {
  var \$classname = \"".$classnames[$i]."_Sql_Query\";
  var \$primary_key = \"".$key_names[$i]."\";
}
\n");
//fclose($finc);





fwrite($fihtml," </table>\n<?php \$this->form_data->finish();\n?>\n");
fclose($fihtml);

fwrite($fmenu,"<font class='bigTextBold' align='CENTER'><a href=\"<?php \$sess->purl(\"".$tbnames[$i].".php\") ?>\">".neatstr($tbnames[$i])."</a></font>\n");
fwrite($ftop,"&nbsp<a href=\"<?php \$sess->purl(\"/phplib/".$tbnames[$i].".php\") ?>\" class=toplink>".neatstr($tbnames[$i])."</a>&nbsp;&nbsp\n");

} //switch
$i++;
} //while tables
fwrite($fmenu,"<font class='bigTextBold' align='CENTER'><a href=\"<?php \$sess->purl(\"login.php\") ?>\">Login</a></font>\n");
fwrite($fmenu,"<font class='bigTextBold' align='CENTER'><a href=\"<?php \$sess->purl(\"register.php\") ?>\">Register</a></font>\n");
fwrite($fmenu,"<font class='bigTextBold' align='CENTER'><a href=\"<?php \$sess->purl(\"logout.php\") ?>\">Logout</a></font>\n");
fwrite($fmenu,"<?php page_close(); ?> </body></html>\n");
fclose($fmenu);
fclose($ftop);

fclose($floc);
?>
     


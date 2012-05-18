<?php
# vim: tabstop=4 expandtab shiftwidth=4 softtabstop=4

$_PHPLIB["local"] = "./";
include("prepend.php");

$debug=false;

function strtotime_uk($str) {
   $str = preg_replace("/^\s*([0-9]{1,2})[\/\. -]+([0-9]{1,2})[\/\. -]*([0-9]{0,4})/", "\\2/\\1/\\3", $str);
   $str = trim($str,'/');
   return strtotime($str);
}


function date_time_format($str) {
    global $k;
	list($datepart,$timepart) = explode(" ",$str);
	if ($datepart." ".$timepart == $str) {
		$df = date_format($datepart);
		$tf = time_format($timepart);
        if ($GLOBALS["debug"]) echo " _${k}_ $df $tf _ ";
		if (($df) and ($tf)) return "$df $tf";
	} else {
		if (preg_match('/^[0-9]+$/',$str)) { 
			switch(strlen($str)) {
				case 14: return "YmdHis";
				case 12: return "ymdHis";
			}
		}
	}
    return false;
}
	

function date_format($str) {
	global $uk_fmt, $uk_date, $us_fmt, $us_date;
	$format_strings = array(   /* php date formats we accept */
		"Y-m-d", "Y/m/d",
		"j-F-Y", "j-M-Y", "j-M-y", "j-m-Y", "j-m-y", "j-n-Y",
		"d-F-Y", "d-M-Y", "d-M-y", "d-m-Y",
		"m-d-Y", "n-j-Y",
		"j M Y", "j/n/Y", "n/j/Y",
		"d M Y", "d/m/Y",
		"j/m/Y", "m/d/Y",
		"Ymd",   "ymd",
		);	
	if ($dt = strtotime_uk($str))
	foreach($format_strings as $s) {
		if ($str==date($s,$dt)) {
			if (date('d',$dt)>12) { $uk_date++; $uk_fmt = $s; };
			return $s;
		}
	}
	if ($dt = strtotime($str))
	foreach($format_strings as $s) {
		if ($str==date($s,$dt)) {
			if (date('d',$dt)>12) { $us_date++; $us_fmt = $s; };
			return $s;
		}
	}
	return false;
}

function time_format($str) {
	$format_strings = array(   /* php date function formats */
		"h:ia",
		"g:ia",
		"h:iA",
		"g:iA",
		"G:i",
		"g:i",
		"h:i:sA",
		"g:i:sA",
		"h:i:sa",
		"g:i:sa",
		"h:i:sA",
		"g:i:sA",
		"His",
		"Gis",
		"H:i:s",
		"G:i:s",
                );
        $dt = strtotime($str);
        foreach($format_strings as $s) {
                if ($str==date($s,$dt)) return $s;
        }
        return false;
}

function date_format_php2mysql($fmt) {
    $t = array(                         /* translate php date format to mysql */
        "M"=>"%b",
        "n"=>"%c",
        "d"=>"%d",
        "D"=>"%D",
        "j"=>"%e",
        "h"=>"%k",
        "G"=>"%h",
        "H"=>"%H",
        "i"=>"%i",
        "g"=>"%l",
        "F"=>"%M",
        "m"=>"%m",
        "a"=>"%p",
        "A"=>"%p",
        "s"=>"%s",
        "y"=>"%y",
        "Y"=>"%Y",
    );
    foreach($t as $php=>$mysql) {
        $fmt = str_replace($php,$mysql,$fmt);
    }
    return $fmt;
}

function addtoset($str,$fmt) {
    global $SETSQL, $date_format;
    if (is_array($fmt)) {
        if (count($fmt)==1) $fmt=key($fmt); else
        foreach($fmt as $f=>$c) {
           if (substr($f,0,strlen($date_format))==$date_format) { $fmt=$f; break; }
        }
    }
    if (!$SETSQL) $SETSQL = "\nSET \n"; else $SETSQL .= ",\n";
    $SETSQL .= $str."'".date_format_php2mysql($fmt)."')";
}

function getFileName($str) {
    $bn = basename($str);
    if ($arr = explode(".",$bn)) {
        return substr($bn,0,(strlen(end($arr))+1)*-1);
        
    } else {
        return $bn;
    }
    $extlen = strlen(end(explode(".", $str)));
    return substr(basename($str),0,(strlen(end(explode(".", $str))) -1 ) * -1);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr"> 
<head>
<title>MySQL File Import</title> 
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" href="/css/style.css" type="text/css"/>
</head><body>
<?php
if ($_POST["MAX_FILE_SIZE"]) echo "<a href=import.php>restart</a><br>\n";

if ($_REQUEST["submit"]=='Import') {
    if ($_SERVER["PHP_AUTH_USER"]) {
        $db = new $_ENV["DatabaseClass"];
        foreach($_POST["sqlquery"] as $sql_query) {
            echo "<blockquote>$sql_query</blockquote>";;
            $db->query($sql_query);
        }
    } else {
        echo "<h1>This script is not password protected therefore is not safe to run.</h1><h2>click back in your browser</h2><h3>cut and paste the SQL into your database client</h3>";
        exit;
    }
}

$eof=$_POST["eof"];
$pref_date=$_POST["pref_date"];
if (!$enc=$_POST["enc"]) $enc='"';
if (!$esc=$_POST["esc"]) $esc='\\';
if (!$card=$_POST["card"]) $card=10;
if (!$minrows=$_POST["minrows"]) $minrows=50;
if (!$grow_room=$_POST["grow_room"]) $grow_room=25;


/* ok let's see what files were upload and if we can handle them */
$count=0;
$csv_path = "/tmp/csv";
system("mkdir $csv_path");
if ($file = $_FILES['userfile']) {
	$ext = end(explode(".", $file['name']));
    if ($debug) echo "f:".$file['name']." e:$ext<br>\n";
	if ($ext) {
		switch ($ext) {
            case "xlsx":
                echo "Not supported yet!<br>";
                echo "Visit <a href='http://www.phpexcel.net/'>http://www.phpexcel.net/</a> to get information on coding this format";
                exit;
			case "zip": 
                $zip = new ZipArchive();
				if ($res = $zip->open($file['tmp_name'])) {
                    for ($i=0;$i<$zip->numFiles;$i++) {
					    $entry = $zip->statIndex($i);
                        $count++;
                        $filename[$count] = $csv_path."/".$entry['name'];
                        $basename[$count] = getFileName($entry['name']);
                    }
                    $zip->extractTo($csv_path);
                    $zip->close();
                } else echo "no res";
                break;
            case "csv":
                $count++;
                $filename[$count] = $csv_path."/".$file['name'];
                $basename[$count] = getFileName($file['name']);
                move_uploaded_file($file['tmp_name'],$filename[$count]);
                chmod($filename[$count],0644);
                break;
            case "xml":  /* as created by Excel 2003 */
                $count++;
                $rowcount = $cols = 0;
                $dom = DOMDocument::load($file['tmp_name'] );
                $rows = $dom->getElementsByTagName( 'Row' );
                $filename[$count] = $csv_path."/".$file['name'];
                $basename[$count] = getFileName($file['name']);
                if ($fp=fopen($filename[$count],"w")) {
                    foreach ($rows as $row) {
                        $rowcount++;
                        unset($index,$data);
                        $cells = $row->getElementsByTagName( 'Cell' );
                        foreach( $cells as $cell ) { 
                            $ind = $cell->getAttribute( 'Index' );
                            if ( $ind != null ) $index = $ind;
                            $data[$index] = $cell->nodeValue;
                            $index++;
                        }
                        if (!$cols) $cols=$index;
                        for ($i=0; $i<$cols; $i++) {
                            if ($i>0) { $sep=","; } else $sep="";
                            fwrite($fp,$sep.'"'.addslashes(trim($data[$i])).'"');
                        }
                        fwrite($fp,"\n");
                    }
                    fclose($fp);
                }
                break;
            default:
                echo "$ext files are not supported.<br>\n";
		}
	}
}


/* if we found some files to process */
if ($count) {
    echo "<form action='".$GLOBALS["PHP_SELF"]."' method='post'>\n";
    for($files=1;$files<=$count;$files++) {
        unset($lines,$header,$enum,$date_time_format,$date_format,$time_format,$fmt);
        unset($uk_date,$us_date,$uk_fmt,$us_fmt,$date_format,$datecol);
        unset($SQL,$SETSQL,$SQLCOLDEFS,$SQLKEYS,$chars);
        $field_sep=Array(";",",","|","\t");
        foreach($field_sep as $f) $chars[$f]=0;
        if ($fp = fopen($filename[$files],"r")) {
            $str = fgets($fp,1000);
            $qt = false;


            /* lets have a sneak peek at the file */
            for($i=0;$i<strlen($str);$i++) {
                set_time_limit(30);
                $ch = $str[$i];
                switch ($ch) {
                    case "\n":
                        $eol = '\n';
                        break;
                    case "\r":
                        if ($str[$i+1]=="\n") $eol = '\r\n'; else $eol='\n';
                        break; 
                    case '"': 
                        if ($qt) $qt=false; else $qt=true;
                    default:
                        $chars[$ch]++;
                }
                if ($eol) break;
            }
            if (!$eof)
             foreach($field_sep as $f) if ($chars[$f]) {
                if ($eof) {
                    if ($chars[$f]>$chars[$eof]) $eof=$f;
                } else {
                    $eof=$f;
                }
                echo "found ".$chars[$f]." occurences of $f<br>\n";
            }
            echo "assuming field seperator is $eof<br>\n";
            echo "detected end of line is $eol<br>\n";
    

            /* ok, lets read the file for real now */
            rewind($fp);
            while ($data = fgetcsv($fp)) {
                $lines++;
                if ($lines==1) {
                    $header = $data;
                    foreach ($header as $h) if (preg_match('/^[$|-]?[0-9|\.]+$/',$h)) {
                        echo "All numeric value found in row 1, This is probably not a header row<br />\n";
                        echo $str;
                        exit;
                    }
                } else {
                    foreach($data as $k => $v) {
                        if ($lines==2) {
                            $datecol[$k]=true;
                            $key[$k]=true;
                            $integer[$k]=true;
                            $float[$k]=true;
                            $money[$k]=true;
                            $null[$k]=false;
                            $hasdata[$k]=false;
                        }
                        if (!$v) {
                            $null[$k] = true;
                            $key[$k] = false;
                        } else {
                            $hasdata[$k]=true;
                            if ($pref_date=="us") {
                                if (!$dt = strtotime($v)) $dt = strtotime_uk($v);
                            } else {
                                if (!$dt = strtotime_uk($v)) $dt = strtotime($v);
                            }
                            if (($v=="0") or (!$dt)) $datecol[$k]=false;
                            else {
                                if (strlen($v)>11) {
                                    if ($fmt = date_time_format($v)) $date_time_fmt[$k][$fmt]++; 
                                }
                                if ($fmt = date_format($v)) $date_fmt[$k][$fmt]++;
                                if ($fmt = time_format($v)) $time_fmt[$k][$fmt]++;
                            }
                            $len[$k] = max($len[$k],strlen($v));
                            if (preg_match('/\./',$v)>1) { $integer[$k] = false; $float[$k] = false; $money[$k] = false; }
                            if ($integer[$k]) { if (preg_match('/^[-]?[0-9]+$/',$v)==0) $integer[$k] = false; }
                            if ($float[$k]) { if (preg_match('/^[-]?[0-9|e|\.]+$/i',$v)==0) $float[$k] = false; }
                            if ($money[$k]) { if (preg_match('/^[$|-]?[0-9|\.]+$/',$v)==0) $money[$k] = false; }
                            $enum[$k][trim($v)]++;
                        }
                    }
                }
            }
        }


        /* so we looked at the file in detail, lets create a MySQL definition */
        $keys = 0;
        echo "$lines read including header<pre>";
        $lines--; //Header Row is not data.
        if (($uk_date) and ($us_date)) {
            echo "Both US and UK date formats found.  I'm too confused to continue";
            exit;
        }
        if ($us_date) $date_format=$us_fmt; else $date_format=$uk_fmt;
        $TableName = $basename[$files];
        $db->query("SHOW TABLES LIKE '$TableName'");
        if ($db->next_record()) {
            $TableName .= "_".date("YmdHis");
        }
        $SQL = "\nCREATE TABLE `$TableName` (";
        for($i=0;$i<=$k;$i++) {
            if ($debug) echo "<br>\n$i $header[$i] ";
            $ColName = trim($header[$i]);
            $header[$i] = "`".$header[$i]."`";
            if ($float[$i]) $money[$i]=false;
            if ($integer[$i]) $float[$i]=false;
            if ($hasdata[$i]) {
                $j = $i + 1;
                $length = floor($len[$i] * ($grow_room/100+1));
                $datatype = "VARCHAR($length)";
                if ($datecol[$i]) { 
                    if ($dtf = $date_time_fmt[$i]) { $datatype = "DATETIME"; $header[$i]='@col'.$j; addtoset("$ColName = str_to_date(@col$j,",$dtf); } else
                    if ($df = $date_fmt[$i]) { $datatype = "DATE"; $header[$i]='@col'.$j; addtoset("`$ColName` = str_to_date(@col$j,",$df); } else
                    if ($tf = $time_fmt[$i]) { $datatype = "TIME"; } 
                    if ($debug) echo " dtf:$dtf df:$df tf:$tf ";
                }
                if ($money[$i]) { if ($debug) echo "money "; $datatype = 'DECIMAL(9,2)'; }
                if ($float[$i]) { if ($debug) echo "float "; $datatype = 'FLOAT'; }
                if ($integer[$i]) { if ($debug) echo "int "; $datatype = 'INT'; }
                $distinct = count($enum[$i]);
                if ($debug) echo " $distinct ";
                if ($distinct<$lines) $key[$i]=false;
                if (($distinct>1) and ($distinct<$card) and ($lines>100)) {
                    $datatype="ENUM('".implode("','",array_keys($enum[$i]))."')";
                    $header[$i] = '@col'.$j;
                    if (!$SETSQL) $SETSQL = "\nSET \n"; else $SETSQL .= ",\n";
                    $SETSQL .= "`$ColName` = trim(@col$j)";
                }
                if (!$null[$i]) $datatype .= " NOT NULL";
            } else {
                if ($debug) echo "empty ";
                $datatype = "TEXT";
            }
            if ($i) $SQLCOLDEFS.=",";
            $SQLCOLDEFS .= "\n  `$ColName` $datatype";
            if ($key[$i]) { 
                if ($debug) echo "key "; $keys++; 
                if ($keys==1) $SQLKEYS = ",\n  PRIMARY KEY (`$ColName`)";
                else $SQLKEYS = ",\n  UNIQUE `k_".$header[$i]."` (`$ColName`)";
            }
            if ($null[$i]) if ($debug) echo "null ";
        }
        if (!$keys) $SQLCOLDEFS = "\n  `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,".$SQLCOLDEFS;
        $SQL .= $SQLCOLDEFS;
        $SQL .= $SQLKEYS."\n);\n\n";

        $esc = mysql_escape_string($esc);
        $enc = mysql_escape_string($enc);
        echo "<h3>$basename[$files]</h3>";
        echo "<br>\n$keys keys<br />";
        echo "<textarea name='sqlquery[]' rows='15' cols='100'>$SQL</textarea><br />";
        $SQL = "LOAD LOCAL DATA INFILE '".@mysql_escape_string($filename[$files])."' INTO TABLE `".$TableName.  "` FIELDS TERMINATED BY '$eof'".
               " OPTIONALLY ENCLOSED BY '$enc' ESCAPED BY '$esc' LINES TERMINATED BY '$eol' IGNORE 1 LINES (".implode(",", $header).")".$SETSQL.";";
        echo "<textarea name='sqlquery[]' rows='5' cols='100'>$SQL</textarea><br />";
    }
    echo "<input type='submit' name='submit' value='Import'>\n";
    echo "Select and copy this SQL code and paste it into a phpMyAdmin SQL box or paste into MySQL command client";
    echo "</form>\n";
} else {


# Ok, so nothing interesting happened, so we'll present the start form.
?>
<h3>MySQL Import</h3>
<p>Import text files into MySQL from the following formats</p>
<ul>
<li>.csv Comma Seperated Values file</li>
<li>.zip file containing one or more .csv files</li>
<li>.xml file created by Excel 2003</li>
</ul>
<p>The first row of the file will be used for the column names and the table will be named after the file.</p>
<p>Datatypes will be automatically determined from the data</p>
<form enctype="multipart/form-data" action="import.php" method="post" onsubmit='return confirm("Please be patient. This could take a while");'>
 <input type="hidden" name="MAX_FILE_SIZE" value="30000000" />
 <input type="file" name="userfile" size="60" />
 <input type="submit" name="submit" />
<p>This should normally work with the default settings below, but they are here in case you need to tweak something.</p>
<br />
 Default Date Format
 <input type="checkbox" name="pref_date" value="uk" checked="checked"> UK (dd/mm/yy)
 <input type="checkbox" name="pref_date" value="us" > US (mm/dd/yy) <br />
 <small>You cannot mix UK and US date formats in the same .csv file.<br />
 This is only the default, if the data suggests the other format, then so it will be.</small>
<hr />
 End of line:
 <input type="checkbox" name="eol" value="" checked="checked"> Autodetect
<br />
 Column Seperator:
 <input type="checkbox" name="eof" value="," > Comma
 <input type="checkbox" name="eof" value=";" > Semicolon
 <input type="checkbox" name="eof" value="|" > Vertical Bar
 <input type="checkbox" name="eof" value="\t" > Tab
 <input type="text" name="eof" value="" size="1"> Specify Other <small>(or leave blank to auto detect)</small>
<br />
 Optionally Enclosed by 
 <input type="text" name="enc" value='"' size="2">
<br />
 Escaped By
 <input type="text" name="esc" value="\" size="5">
<hr />
 ENUM Cardinality<br />
 <small>Columns storing Australian States, Days of the week, Yes/No, etc are best stored in ENUM format</small><br />
 Columns with <input type="text" name="card" value="<?=$card?>" size="4"> or less distinct values will be stored as ENUM datatype (0 to disable)<br />
 For tables with <input type="text" name="minrows" value="<?=$minrows?>" size="4"> or more rows.
<hr />
 VarChar grow room<br />
 Add <input type="text" name="grow_room" value="<?=$grow_room?>" size="3">% to the length of the longest value found in each column when defining varchar columns
</form>
<?php } ?>
</body>
</html>

<?php
/*
 * PHPLIB class for PHP Data Objects
 *
 * by David Beveridge
 *
 */ 

class DB_Sql {
  
  /* public: connection parameters */
  var $Host     = "";
  var $Database = "";
  var $User     = "";
  var $Password = "";
  var $Server   = "mysql";
  var $charset  = "utf8";

  /* public: configuration parameters */
  var $Auto_Free     = 0;     ## Set to 1 for automatic mysqli_free_result()
  var $Debug         = 0;     ## Set to 1 for debugging messages.
  var $Halt_On_Error = "yes"; ## "yes" (halt with message), "no" (ignore errors quietly), "report" (ignore errror, but spit a warning)
  var $Seq_Table     = "db_sequence";

  /* public: result array and current row number */
  var $Record   = array();
  var $Row;

  /* public: current error number and error text */
  var $ErrCode  = 0;
  var $Errno    = 0;
  var $Error    = "Not Connected";

  /* public: this is an api revision, not a CVS revision. */
  var $type     = "pdo";
  var $revision = "1.0";

  /* private: link and query handles */
  var $dbh  = 0;
  var $sth = 0;
  
  function escape_string($str) {
	return substr(substr($this->quote($str),1),0,-1);
  }
  function quote($str) {
	if (!$this->connect()) return 0; 
	if (get_magic_quotes_gpc()) $str = stripslashes($str);
	return $this->dbh->quote($str);
  }
  function quote_identifier($str) {
	$arr = explode(".",$str);
	return '"'.implode('"."',$arr).'"';
  }
  function qi($str) {
	$arr = explode(".",$str);
	return '"'.implode('"."',$arr).'"';
  }


  /* public: constructor */
  function DB_Sql($query = "") {
      $this->query($query);
  }

  function beginTransaction() {
	if (!$this->connect()) return false;  
	return $this->dbh->beginTransaction();
  }
  function prepare($query = "") {
	if (!$this->connect()) return false;  
	$this->sth = $this->dbh->prepare($query);
	return $this->sth;
  }
  function commit() {
	return $this->dbh->commit();
  }
  function rollback() {
	return $this->dbh->rollback();
  }

  /* public: some trivial reporting */
  function link_id() {
    return $this->dbh;
  }

  function query_id() {
    return $this->sth;
  }

  function lastInsertId() {
    return $this->dbh->lastInsertId();
  }

  /* public: connection management */
  function connect($Database = "", $Host = "", $User = "", $Password = "") {
    /* Handle defaults */
    if ("" == $Database)
      $Database = $this->Database;
    if ("" == $Host)
      $Host     = $this->Host;
    if ("" == $User)
      $User     = $this->User;
    if ("" == $Password)
      $Password = $this->Password;
      
    /* establish connection, select database */
    if ( ! $this->dbh ) {
    
      try{
	switch($this->Server) {
	    case "sqlite":
      		if ($this->Debug) echo "Opening $Database<br>\n";
        	$this->dbh = new PDO("$this->Server:$Database", null, null, array(PDO::ATTR_PERSISTENT => true));
		if (!$this->dbh) $this->halt("open($Database) failed.");
		break;
	    case "pgsql";
        	$this->dbh = new PDO("$this->Server:host=$Host;dbname=$Database;port=5432", $User);
 #       	$this->dbh = new PDO("$this->Server:host=$Host;dbname=$Database;port=5432", $User, $Password);
		break;
	    default:
      		if ($this->Debug) echo "Connecting to $Host as $User<br>\n";
        	$this->dbh = new PDO("$this->Server:host=$Host;dbname=$Database;charset=$this->charset", $User, $Password);
	}
      }
      catch(PDOException $e) {
	$this->halt($e->getMessage());
      }
      if (!$this->dbh) {
	$this->halt("connect($Host, $User, \$Password) failed.");
        return 0;
      }

      switch ($this->Server) {
	case "mysql":
	  $this->dbh->query("SET SESSION sql_mode='ANSI'");
          if (phpversion()<"5.3.6") $this->dbh->exec("SET NAMES $this->charset");
	  break;
	case "sqlite":
	  if ($this->Debug) echo "Creating sqlite functions...";
	  $this->dbh->sqliteCreateFunction('now',function() { return date('Y-m-d H:i:s');},0);
	  $this->dbh->sqliteCreateFunction('year',function() { return date('Y');},0);
	  $this->dbh->sqliteCreateFunction('month',function() { return date('n');},0);
	  $this->dbh->sqliteCreateFunction('day',function() { return date('j');},0);
	  $this->dbh->sqliteCreateFunction('hour',function() { return date('G');},0);
	  $this->dbh->sqliteCreateFunction('minute',function() { return date('i');},0);
	  $this->dbh->sqliteCreateFunction('second',function() { return date('s');},0);
	  $this->dbh->sqliteCreateFunction('unix_timestamp',function($string='') { return strtotime($string);});
	  $this->dbh->sqliteCreateFunction('compress','gzcompress');
	  $this->dbh->sqliteCreateFunction('uncompress','gzuncompress');
	  $this->dbh->sqliteCreateFunction('length', 'strlen', 1);
	  $this->dbh->sqliteCreateFunction('substring', function($string, $from, $length) { 
		return substr($string, $from - 1, $length); },3);
	  $this->dbh->sqliteCreateFunction('md5', 'md5', 1);
	  $this->dbh->sqliteCreateFunction('pow', 'pow', 2);
	  $this->dbh->sqliteCreateFunction('rand', '_sql_rand');
	  $this->dbh->sqliteCreateFunction('if', function($condition, $expr1, $expr2 = NULL) { 
		return $condition ? $expr1 : $expr2; });
	  $this->dbh->sqliteCreateFunction('concat', function() { 
		$args = func_get_args(); return implode('', $args); });
	  $this->dbh->sqliteCreateFunction('concat_ws', function () { 
		$args = func_get_args(); $sep = array_shift($args); return implode($sep, $args); });
	  $this->dbh->sqliteCreateFunction('greatest', '_sql_greatest');
	  $this->dbh->sqliteCreateFunction('least', '_sql_least');
	  $this->dbh->sqliteCreateFunction('regexp', function($pattern, $value) { 
		#mb_internal_encoding('UTF-8'); // multibyte character encoding
		#mb_regex_encoding('UTF-8');	// should be set at the application level
		return (false !== mb_ereg($pattern, $value)) ? 1 : 0;
	  });
	  break;
      }

    }
    return $this->dbh;
  }

  /* public: discard the query result */
  function free() {
      $this->sth->closeCursor();
  }

  /* public: perform a query */
  function query($Query_String) {
    /* No empty queries, please, since PHP4 chokes on them. */
    if ($Query_String == "") {
      /* The empty query string is passed on from the constructor,
       * when calling the class without a query, e.g. in situations
       * like these: '$db = new DB_Sql_Subclass;'
       */
      if ($this->Debug) echo "no query specified.";
      return 0;
    }

    if (!$this->connect()) {
      if ($this->Debug) echo "Complain Again!!";
      return 0; /* we already complained in connect() about that. */
    };

    if ($this->Debug) echo "Still Connected, ";

    if ($this->Debug)
      printf("Debug: query = %s<br>\n", $Query_String);
    try {
      $this->sth = $this->dbh->query($Query_String);
    }
    catch(PDOException $e) {
	$this->halt($e->getMessage());
    }
    #if ($this->sth->rowCount()) { 
    #	$result = $this->sth->setFetchMode(PDO::FETCH_BOTH); /wasn't required, must be default.
    #}
    $this->Row   = 0;
    $this->getErrorInfo();
    if (!$this->sth) {
      $this->halt("Invalid SQL: ".$Query_String);
    }

    # Will return nada if it fails. That's fine.
    return 1;
  }

  /* public: walk result set */
  function next_record() {
    if (!$this->sth) {
      $this->halt("next_record called with no query pending.");
      return 0;
    }

    if ($this->Debug) echo ".";

    $this->Record = $this->sth->fetch();
    $this->Row   += 1;
    $this->getErrorInfo();

    $stat = is_array($this->Record);
    if (!$stat && $this->Auto_Free) {
      $this->free();
    }
    return $stat;
  }

  /* public: position in result set */
  function seek($pos = 0) {
	die ("Seek function not supported");
  }

  /* public: table locking */
  function lock($table, $mode = "write") {
    $query = "lock tables ";
    if (is_array($table)) {
      while (list($key,$value) = each($table)) {
        if (!is_int($key)) {
		  // texts key are "read", "read local", "write", "low priority write"
          $query .= "$value $key, ";
        } else {
          $query .= "$value $mode, ";
        }
      }
      $query = substr($query,0,-2);
    } else {
      $query .= "`$table` $mode";
    }
    $res = $this->query($query);
	if (!$res) {
      $this->halt("lock() failed.");
      return 0;
    }
    return $res;
  }
  
  function unlock() {
    $res = $this->query("unlock tables");
    if (!$res) {
      $this->halt("unlock() failed.");
    }
    return $res;
  }

  /* public: evaluate the result (size, width) */
  function affected_rows() {
    return $this->sth->rowCount();
  }

  function num_rows() {
    return $this->sth->rowCount();
  }

  function num_fields() {
    return $this->sth->columnCount();
  }

  /* public: shorthand notation */
  function nf() {
    return $this->num_rows();
  }

  function np() {
    print $this->num_rows();
  }

  function f($Name) {
    if (isset($this->Record[$Name])) {
      return $this->Record[$Name];
    }
  }

  function p($Name) {
    if (isset($this->Record[$Name])) {
      print $this->Record[$Name];
    }
  }

  /* public: sequence numbers */
  function nextid($seq_name) {
    $this->connect();
    
    if ($this->lock($this->Seq_Table)) {
      /* get sequence number (locked) and increment */
      $q  = sprintf("select nextid from `%s` where seq_name = '%s'",
                $this->Seq_Table,
                $seq_name);
      try {
      	$st = $this->dbh->query($q);
      	$currentid = $st->fetchColumn();
      }
      catch(PDOException $e) {
        $currentid = false;
      }
      
      /* No current value, make one */
      if ($currentid===false) {
        $nextid = 1;
        $q = sprintf("insert into `%s` values('%s', %s)",
                 $this->Seq_Table,
                 $seq_name,
                 $nextid);
        $nr = $this->dbh->exec($q);
      } else {
        $nextid = $currentid + 1;
        $q = sprintf("update `%s` set nextid = '%s' where seq_name = '%s'",
                 $this->Seq_Table,
                 $nextid,
                 $seq_name);
        $nr = $this->dbh->exec($q);
      }
      $this->unlock();
    } else {
      $this->halt("cannot lock ".$this->Seq_Table." - has it been created?");
      return 0;
    }
    return $nextid;
  }

  /* public: return table metadata */
  function metadata($table = "", $full = false) {
    $count   = 0;
    $id      = 0;
    $res     = array();
    $stmt    = false;
    $hasdef  = false;
    $attnums = array();

    // if no $table specified, assume that we are working with a query
    // result
    if (!$table) {
      $stmt = $this->sth; 
      if (!$stmt) {
        $this->halt("No table specified.");
        return false;
      }
##################################################################################
## This section causes it to prefer pdo metadata functions over DESCRIBE table.
## Unfortunately, it's broken, as it can't tell the difference between TEXT & BLOB
## If your database server does not support DESCRIBE, you may turn this back on.
## Also does not return default values or auto_increment data.
## pdo_type retuned was always 2 (PDO::PARAM_STR) in my testing.
## PHP MANUAL STATES "getColumnMeta() Warning: This function is EXPERIMENTAL. 
## The behaviour of this function, its name, and surrounding documentation may 
## change without notice in a future release of PHP. 
## This function should be used at your own risk."
/*
    } else {
      $q = "SELECT * FROM ".$this->qi($table)." LIMIT 1";
      $stmt = $this->dbh->query($q);
*/
##################################################################################
    }
    if ($stmt) {
      if (!@$stmt->columnCount()) {
		$row = $stmt->fetchAll();
      }
      for ($i=0;$i<$stmt->columnCount();$i++) {
        $res[$i] = $stmt->getColumnMeta($i);
	if (false) {
		echo " \n$i:<pre>";
		var_dump($res[$i]);
		echo "</pre>";
	}
	if ((array_key_exists("native_type",$res[$i])) and (!array_key_exists("type",$res[$i]))) {
		$res[$i]["type"] = strtolower($res[$i]["native_type"]);
	}
	if (!array_key_exists("type",$res[$i])) {
		switch (@$res[$i]["pdo_type"]) {
			case PDO::PARAM_INT: $res[$i]["type"] = "int";    break;	# 1
			case PDO::PARAM_STR: $res[$i]["type"] = "string"; break;	# 2
			case PDO::PARAM_LOB: $res[$i]["type"] = "blob";   break;	# 3
		}
	}
	if ((array_search("blob",$res[$i]["flags"])!==false) and ($res[$i]["pdo_type"] == PDO::PARAM_STR)) {
		$res[$i]["type"]="text"; 
	}
	$res[$i]["default"]="";		// pdo metadata does not return default value.
	$res[$i]["extra"]="";		// or auto_increment?
	$res[$i]["comment"]="";
	$res[$i]["key"]="";
	if (array_search("primary_key",$res[$i]["flags"])!==false) $res[$i]["key"]="PRI"; 
	if (array_search("multiple_key",$res[$i]["flags"])!==false) $res[$i]["key"]="MUL"; 
	if (array_search("unique_key",$res[$i]["flags"])!==false) $res[$i]["key"]="UNI"; 
	if (array_search("not_null",$res[$i]["flags"])!==false) $res[$i]["null"]="NO"; 
	else $res[$i]["null"]="YES"; 
	$len = @$res[$i]["len"] ? $res[$i]["len"] : $res[$i]["length"];
	$res[$i]["chars"] = $len / 3;	## this / 3 might only be for utf-8;
	$count++;
      }
    } else {
      $this->connect();
      switch ($this->Server) {
	  case "pgsql":
		$q = "SELECT a.attname AS name, t.typname AS type, a.attlen AS len, 
				a.atttypmod, a.attnotnull AS notnull, a.atthasdef, a.attnum  
        		FROM pg_class c,  pg_attribute a, pg_type t  
        		WHERE relkind in ('r', 'v') AND (c.relname='$table' or c.relname = lower('$table')) 
			AND a.attname not like '....%' AND a.attnum > 0 AND a.atttypid = t.oid AND a.attrelid = c.oid 
			ORDER BY a.attnum"; 
		break;
	  case "sqlite":
		$q = "PRAGMA table_info($table)"; 
		break;
          case "mssql":
		$q = "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, COLLATION_NAME,
			CHARACTER_SET_NAME, IS_NULLABLE, COLUMN_DEFAULT 
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_SCHEMA=".$this->quote($this->Database)." AND TABLE_NAME=".$this->quote($table);
		break;
          case "mysql":
		$q = "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, COLLATION_NAME, 
			EXTRA, COLUMN_COMMENT, COLUMN_KEY, PRIVILEGES, COLUMN_TYPE,
			CHARACTER_SET_NAME, IS_NULLABLE, COLUMN_DEFAULT
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_SCHEMA=".$this->quote($this->Database)." AND TABLE_NAME=".$this->quote($table);
		break;
	  default:
		$q = "DESCRIBE ".$this->qi($table);
      }
      try {
        if (!$stmt = $this->dbh->query($q)) {
          $this->halt("Metadata query failed.");
	  return false;
	}
      }
      catch(PDOException $e) {
	$this->halt($e->getMessage());
      }
    #  $res["num_fields"] = $stmt->rowCount();
      $i = 0;
#echo "<h1>$table</h1>";
      foreach($stmt as $row) {
#var_dump($row);
        $res[$i]["table"] = $table;
	if (array_key_exists('DATA_TYPE',$row))   $type = $row['DATA_TYPE'];	// information_schema sql standard
	if (array_key_exists('IS_NULLABLE',$row)) $null = $row['IS_NULLABLE'];  // information_schema sql standard
	if (array_key_exists('COLUMN_TYPE',$row)) $type = $row['COLUMN_TYPE'];	// information_schema mysql
	if (array_key_exists('type',$row))        $type = $row['type'];		// sqlite & pgsql
	if (array_key_exists('Type',$row))        $type = $row['Type'];		// mysql describe
	$fattr = "";
	$pos = strpos ( $type, "(" );
        if ( $pos > 0 ) {
            $ftype = substr ( $type, 0, $pos );
            $fsize = substr ( $type, $pos +1 );
            $pos = strpos ( $fsize, ") " );
            if ( $pos > 0 ) {
                $fattr = substr ( $fsize, $pos +2, strlen ($fsize) -2 -$pos );
                $fsize = substr ( $fsize, 0, $pos );
            } else {
                $fsize = substr ( $fsize, 0, $pos -1 );
            }
        } else {
            $fsize = "";
            $ftype = $type;
        }
	$res[$i]["chars"] = $fsize;
	if ((array_key_exists("atttypmod",$row)) and ($row["atttypmod"]>0)) $res[$i]["chars"] = $row["atttypmod"]; //pgsql
	if (array_key_exists("attnum",$row)) $attnums[$row["attnum"]] = $i; //pgsql
	$res[$i]["len"] = $fsize;
	$res[$i]["type"] = $ftype;
	$res[$i]["attr"] = $fattr;   /* eg unsigned */
	if (array_key_exists("notnull",$row)) {	
		if ($row["notnull"]) $null="NO"; else $null="YES"; // sqlite & pgsql
	} else 
	if (array_key_exists("Null",$row)) {
		 $null = $row["Null"];		// MySQL DESCRIBE
	}
	$res[$i]["null"] = $null;
	if ((array_key_exists("atthasdef",$row)) and ($row["atthasdef"])) $hasdef = true;
	// mysql
	if (array_key_exists('Field',$row)) $res[$i]['name'] = $row['Field'];
	if (array_key_exists("Default",$row)) $res[$i]["default"] = $row["Default"];
	if (array_key_exists("Key",$row)) $res[$i]["key"] = $row["Key"];
	$res[$i]["extra"] = array_key_exists('Extra',$row) ? $row["Extra"] : "";
	// sqlite
	if (array_key_exists('name',$row)) $res[$i]['name'] = $row['name'];
	if (array_key_exists("pk",$row)) $res[$i]["key"] = $row["pk"];
	if (array_key_exists("dflt_value",$row)) $res[$i]["default"] = $row["dflt_value"];
	// Information_Schema values...
	if (array_key_exists('COLUMN_NAME',$row)) $res[$i]['name'] = $row['COLUMN_NAME'];
	if (array_key_exists('DATA_TYPE',$row)) $res[$i]['type'] = $row['DATA_TYPE'];
	if (array_key_exists('CHARACTER_MAXIMUM_LENGTH',$row)) $res[$i]['len'] = $row['CHARACTER_MAXIMUM_LENGTH'];
	if (array_key_exists('COLUMN_DEFAULT',$row)) $res[$i]['default'] = $row['COLUMN_DEFAULT'];
	if (array_key_exists('COLLATION_NAME',$row)) $res[$i]['collation'] = $row['COLLATION_NAME'];
	if (array_key_exists('CHARACTER_SET_NAME',$row)) $res[$i]['charset'] = $row['CHARACTER_SET_NAME'];
	if (array_key_exists('EXTRA',$row)) $res[$i]['extra'] = $row['EXTRA'];			// mysql only
	if (array_key_exists('COLUMN_KEY',$row)) $res[$i]['key'] = $row['COLUMN_KEY'];	        // mysql only
	if (array_key_exists('PRIVILEGES',$row)) $res[$i]['priv'] = $row['PRIVILEGES'];  	// mysql only
	if (array_key_exists('COLUMN_COMMENT',$row)) $res[$i]['comment'] = $row['COLUMN_COMMENT']; // mysql only
	$i++;
      }
      if ($hasdef) { # postgres
	$stmt = $this->dbh->query("SELECT d.adnum as num,  d.adsrc as def from pg_attrdef d,  pg_class c 
				   WHERE d.adrelid=c.oid and c.relname='$table' order by d.adnum");
	foreach($stmt as $row) {
		$num = $attnums[$row["num"]];
		$def = $row["def"];
		$res[$num]["default"] = $def;
		if (substr($def,0,8)=='nextval(') $res[$num]["extra"] = "auto_increment";
	}
      }
    }
    return $res;
  }

  /* public: find available table names */
  function table_names() {
    $this->connect();
    $i = 0;
    $res = Array();
    switch($this->Server){
	case "sybase":
		$q = "SELECT name FROM sysobjects WHERE type='U'"; break;
	case "sqlite":
		$q = "SELECT name FROM sqlite_master WHERE type = 'table'"; break;
	case "mysql":
		$q = "SHOW TABLES"; break;
        case "pgsql":
		$q = "SELECT c.relname FROM pg_catalog.pg_class c
			LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
			WHERE c.relkind IN ('r','') AND n.nspname NOT IN ('pg_catalog', 'pg_toast')
			AND pg_catalog.pg_table_is_visible(c.oid);"; 
		break;
	case "oracle":
		$q = "SELECT table_name FROM tabs"; break;
	default:
		$q = "SELECT table_name FROM information_schema.tables ".
		     "WHERE table_type = 'BASE TABLE' AND table_schema NOT IN ('pg_catalog', 'information_schema')"; }
    if ($stmt = $this->dbh->query($q))
    foreach ($stmt as $row) {
      $res[$i]["table_name"]      = $row[0];
      $res[$i]["tablespace_name"] = $this->Database;
      $res[$i]["database"]        = $this->Database;
      $i++;
    }
    return $res;
  }

  function primary_key($table) {
    $this->connect();
    switch($this->Server){
        case "pgsql":
	  $q = "SELECT 	a.attname AS column_name, ic.relname AS index_name, 
			i.indisunique AS unique_key, i.indisprimary AS primary_key 
    		FROM pg_class bc,  pg_class ic,  pg_index i,  pg_attribute a 
		WHERE bc.oid = i.indrelid AND ic.oid = i.indexrelid 
		AND i.indisprimary
		AND (i.indkey[0] = a.attnum OR i.indkey[1] = a.attnum OR i.indkey[2] = a.attnum OR i.indkey[3] = a.attnum 
		  OR i.indkey[4] = a.attnum OR i.indkey[5] = a.attnum OR i.indkey[6] = a.attnum OR i.indkey[7] = a.attnum) 
		AND a.attrelid = bc.oid AND bc.relname = ".$this->quote($table);
		break;
	case "sqlite":
		return "rowid";
	default:
		$q = "SELECT column_name FROM information_schema.columns ".
		     "WHERE column_key = 'PRI' AND table_name=".$this->quote($table);
    }
    if ($stmt = $this->dbh->query($q)) {
	return $stmt->fetchColumn();
    } else {
        return false;
    }
  }
    
  function getErrorInfo() {
    if (is_object($this->dbh)) {
      $e = $this->dbh->errorInfo();
      $this->Error = $e[2];
      $this->Errno = $e[1];
      $this->ErrCode = $e[0];
    }
  }

  /* private: error handling */
  function halt($msg) {
    $this->getErrorInfo();
    if ($this->Debug) echo $this->Server."(pdo) error(".$this->ErrCode."):".$this->Errno." ".$this->Error.$msg;
    if (function_exists("EventLog")) {
      EventLog($this->Server."(pdo) error(".$this->ErrCode."):".$this->Errno." ".$this->Error,$msg,"Error");
    }
    if ($this->Halt_On_Error == "no")
      return;

    $this->haltmsg($msg);

    if ($this->Halt_On_Error != "report")
      die("Session halted.");
  }

  function haltmsg($msg) {
    printf("<b>Database error:</b> %s<br>\n", $msg);
    printf("<b>%s PDO Error</b> (%s): %s, %s<br>\n",
      $this->Server,
      $this->ErrCode,
      $this->Errno,
      $this->Error);
  }

}


/*  possible postgresql queries.... 
    from
    http://scripts.ringsworld.com/content-management/cms/adodb/drivers/adodb-pdo.inc.php.html


    // used when schema defined
    var $metaColumnsSQL1 = "SELECT a.attname,  t.typname,  a.attlen,  a.atttypmod,  a.attnotnull,  a.atthasdef,  a.attnum 
		FROM pg_class c,  pg_attribute a,  pg_type t,  pg_namespace n 
		WHERE relkind in ('r', 'v') AND (c.relname='%s' or c.relname = lower('%s'))
			 and c.relnamespace=n.oid and n.nspname='%s' 
			    and a.attname not like '....%%' AND a.attnum > 0 
		AND a.atttypid = t.oid AND a.attrelid = c.oid 
		ORDER BY a.attnum";
    
    // get primary key etc -- from Freek Dijkstra
    
*/

# The SQLITE driver has the ability to call PHP functions inside it's SQL statments
# very handy for creating functions that SQLITE doesn't have
# see above use of $this->dbh->sqliteCreateFunction() to map these functions.
#
function _sql_rand($seed = NULL) {
    if (isset($seed)) { mt_srand($seed); }
    return mt_rand() / mt_getrandmax();
}
function _sql_least() {
    $args = func_get_args();
    foreach ($args as $k => $v) {
      if (!isset($v)) { unset($args); }
    }
    if (count($args)) { return min($args); }
    else { return NULL; }
}
function _sql_greatest() {
    $args = func_get_args();
    foreach ($args as $k => $v) {
      if (!isset($v)) { unset($args); }
    }
    if (count($args)) { return max($args); }
    else { return NULL; }
}
?>

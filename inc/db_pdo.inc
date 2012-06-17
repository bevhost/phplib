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
  var $Errno    = 0;
  var $Error    = "";

  /* public: this is an api revision, not a CVS revision. */
  var $type     = "pdo";
  var $revision = "1.0";

  /* private: link and query handles */
  var $dbh  = 0;
  var $sth = 0;
  
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
	$this->$sth = $this->dbh->prepare($query);
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
    
      if ($this->Debug) echo "Connecting to $Host as $User<br>\n";
      try{
        $this->dbh = new PDO("$this->Server:host=$Host;dbname=$Database;charset=$this->charset", $User, $Password);
      }
      catch(PDOException $e) {
	$this->halt($e->message);
      }
      if (!$this->dbh) {
        $this->halt("connect($Host, $User, \$Password) failed.");
        return 0;
      }

      if (phpversion()<"5.3.6") $this->dbh->exec("SET NAMES $this->charset");
      if ($this->Server=='mysql') $this->dbh->query("SET SESSION sql_mode='ANSI'");
	
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

    if ($this->Debug) echo "Connected";

    if ($this->Debug)
      printf("Debug: query = %s<br>\n", $Query_String);
    try {
      $this->sth = $this->dbh->query($Query_String);
    }
    catch(PDOException $e) {
      $this->halt($e->message);
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
    $count = 0;
    $id    = 0;
    $res   = array();


    // if no $table specified, assume that we are working with a query
    // result
    if (!$table) {
      $stmt = $this->sth; 
      if (!$stmt) {
        $this->halt("No table specified.");
        return false;
      }
      for ($i=0;$i<$stmt->columnCount;$i++) {
        $res[$i] = $stmt->getColumnMeta($i);
      }
    } else {
      $this->connect();
      try {
        if (!$stmt = $this->dbh->query("DESCRIBE ".$this->qi($table))) {
          $this->halt("Metadata query failed.");
	  return false;
	}
      }
      catch(PDOException $e) {
	$this->halt($e->message);
      }
      $res["num_fields"] = $stmt->rowCount();
      $i = 0;
      foreach($stmt as $row) {
        $res[$i]["table"] = $table;
        $res[$i]["name"]  = $row["Field"];
	$fattr = "";
	$pos = strpos ( $row["Type"], "(" );
        if ( $pos > 0 ) {
            $ftype = substr ( $row["Type"], 0, $pos );
            $fsize = substr ( $row["Type"], $pos +1 );
            $pos = strpos ( $fsize, ") " );
            if ( $pos > 0 ) {
                $fattr = substr ( $fsize, $pos +2, strlen ($fsize) -2 -$pos );
                $fsize = substr ( $fsize, 0, $pos );
            } else {
                $fsize = substr ( $fsize, 0, $pos -1 );
            }
        } else {
            $fsize = "";
            $ftype = $row["Type"];
        }
	$res[$i]["key"] = $row["Key"];
	$res[$i]["chars"] = $fsize;
	$res[$i]["type"] = $ftype;
	$res[$i]["attr"] = $fattr;   /* eg unsigned */
	$res[$i]["null"] = $row["Null"];
	$res[$i]["extra"] = $row["Extra"];
	$res[$i]["default"] = $row["Default"];
	$i++;
      }
    }

    return $res;
  }

  /* public: find available table names */
  function table_names() {
    $this->connect();
    $i = 0;
    switch($this->Server){
	case "sqlite":
		$q = 'SELECT name FROM sqlite_master WHERE type = "table"';
	case "mysql":
		$q = "SHOW TABLES"; break;
        case "pgsql":
		$q = "SELECT * FROM pg_catalog.pg_tables"; break;
	case "oracle":
		$q = "SELECT table_name FROM tabs"; break;
	default:
		$q = "SELECT table_name FROM information_schema.tables ".
		     "WHERE table_type = 'BASE TABLE' AND table_schema NOT IN ('pg_catalog', 'information_schema')";
    }
    foreach ($this->dbh->query($q) as $row) {
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
    $e = $this->dbh->errorInfo();
    $this->Error = $e[2];
    $this->Errno = $e[1];
    $this->ErrCode = $e[0];
  }

  /* private: error handling */
  function halt($msg) {
    $this->getErrorInfo();
    if ($this->Debug) echo "MySQL Error(".$this->ErrCode."):".$this->Errno." ".$this->Error.$msg;
    if (function_exists("EventLog")) {
      EventLog("MySQL Error(".$this->ErrCode."):".$this->Errno." ".$this->Error,$msg,"Error");
    }
    if ($this->Halt_On_Error == "no")
      return;

    $this->haltmsg($msg);

    if ($this->Halt_On_Error != "report")
      die("Session halted.");
  }

  function haltmsg($msg) {
    printf("</td></tr></table><b>Database error:</b> %s<br>\n", $msg);
    printf("<b>%s PDO Error</b> (%s): %s, %s<br>\n",
      $this->Server,
      $this->ErrCode,
      $this->Errno,
      $this->Error);
  }

}
?>

<?php
/*
 * Session Management for PHP3
 *
 * Copyright (c) 1998-2000 NetUSE AG
 *                    Boris Erdmann, Kristian Koehntopp
 *
 * $Id: db_mysql.inc,v 1.8 2001/10/12 16:16:16 layne_weathers Exp $
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
  var $type     = "mysqli";
  var $revision = "1.0";

  /* private: link and query handles */
  var $Link_ID  = 0;
  var $Query_ID = 0;
  

  /* Quoting Helper - by Dave Beveridge */
  function quote($str) {
	return "'".$this->escape_string($str)."'";
  }
  function escape_string($str) {
	if (!$this->connect()) return 0; 
	if (get_magic_quotes_gpc()) $str = stripslashes($str);
	return mysqli_real_escape_string($this->Link_ID,$str);
  }
  function quote_identifier($str) {
	$arr = explode(".",$str);
	return "`".implode("`.`",$arr)."`";
  }
  function qi($str) {
	$arr = explode(".",$str);
	return "`".implode("`.`",$arr)."`";
  }

  function lastInsertId() {
	return mysqli_insert_id($this->Link_ID);
  }

  /* public: constructor */
  function DB_Sql($query = "") {
      $this->query($query);
  }

  /* public: some trivial reporting */
  function link_id() {
    return $this->Link_ID;
  }

  function query_id() {
    return $this->Query_ID;
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
    if ( ! $this->Link_ID ) {
    
      if ($this->Debug) echo "Connecting to $Host as $User<br>\n";
      $this->Link_ID=mysqli_connect($Host, $User, $Password);
      if (!$this->Link_ID) {
        $this->halt("connect($Host, $User, \$Password) failed.");
        return 0;
      }

      if (!@mysqli_select_db($this->Link_ID, $Database)) {
        $this->halt("cannot use database ".$Database);
        return 0;
      }
	
      /* change character set to utf8 */
      if (!mysqli_set_charset($this->Link_ID, $this->charset)) {
          $this->halt("Error loading character set ".$this->charset);
      }
    }
    return $this->Link_ID;
  }

  /* public: discard the query result */
  function free() {
      @mysqli_free_result($this->Query_ID);
      $this->Query_ID = 0;
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
    # New query, discard previous result.
    if ($this->Query_ID) {
      $this->free();
    }

    if ($this->Debug)
      printf("Debug: query = %s<br>\n", $Query_String);
    $this->Query_ID = mysqli_query($this->Link_ID,$Query_String);
    $this->Row   = 0;
    $this->Errno = mysqli_errno($this->Link_ID);
    $this->Error = mysqli_error($this->Link_ID);
    if (!$this->Query_ID) {
      $this->halt("Invalid SQL: ".$Query_String);
    }

    # Will return nada if it fails. That's fine.
    return $this->Query_ID;
  }

  /* public: walk result set */
  function next_record() {
    if (!$this->Query_ID) {
      $this->halt("next_record called with no query pending.");
      return 0;
    }

    if ($this->Debug) echo ".";

    $this->Record = @mysqli_fetch_array($this->Query_ID);
    $this->Row   += 1;
    $this->Errno  = mysqli_errno($this->Link_ID);
    $this->Error  = mysqli_error($this->Link_ID);

    $stat = is_array($this->Record);
    if (!$stat && $this->Auto_Free) {
      $this->free();
    }
    return $stat;
  }

  /* public: walk result set */
  function next_row() {
    if (!$this->Query_ID) {
      $this->halt("next_record called with no query pending.");
      return 0;
    }

    $this->Record = @mysqli_fetch_row($this->Query_ID);
    $this->Row   += 1;
    $this->Errno  = mysqli_errno();
    $this->Error  = mysqli_error();

    $stat = is_array($this->Record);
    if (!$stat && $this->Auto_Free) {
      $this->free();
    }
    return $stat;
  }

  /* public: position in result set */
  function seek($pos = 0) {
    $status = @mysqli_data_seek($this->Query_ID, $pos);
    if ($status)
      $this->Row = $pos;
    else {
      $this->halt("seek($pos) failed: result has ".$this->num_rows()." rows.");

      /* half assed attempt to save the day, 
       * but do not consider this documented or even
       * desireable behaviour.
       */
      @mysqli_data_seek($this->Query_ID, $this->num_rows());
      $this->Row = $this->num_rows();
      return 0;
    }

    return 1;
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
    return @mysqli_affected_rows($this->Link_ID);
  }

  function num_rows() {
    return @mysqli_num_rows($this->Query_ID);
  }

  function num_fields() {
    return @mysqli_num_fields($this->Query_ID);
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
      $id  = @mysqli_query($this->Link_ID,$q);
      $res = @mysqli_fetch_array($id);
      
      /* No current value, make one */
      if (!is_array($res)) {
        $currentid = 0;
        $q = sprintf("insert into `%s` values('%s', %s)",
                 $this->Seq_Table,
                 $seq_name,
                 $currentid);
        $id = @mysqli_query($this->Link_ID, $q);
      } else {
        $currentid = $res["nextid"];
      }
      $nextid = $currentid + 1;
      $q = sprintf("update `%s` set nextid = '%s' where seq_name = '%s'",
               $this->Seq_Table,
               $nextid,
               $seq_name);
      $id = @mysqli_query($this->Link_ID, $q);
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

    /*
     * Due to compatibility problems with Table we changed the behavior
     * of metadata();
     * depending on $full, metadata returns the following values:
     *
     * - full is false (default):
     * $result[]:
     *   [0]["table"]  table name
     *   [0]["name"]   field name
     *   [0]["type"]   field type
     *   [0]["len"]    field length
     *   [0]["flags"]  field flags
     *
     * - full is true
     * $result[]:
     *   ["num_fields"] number of metadata records
     *   [0]["table"]  table name
     *   [0]["name"]   field name
     *   [0]["type"]   field type
     *   [0]["len"]    field length
     *   [0]["flags"]  field flags
     *   ["meta"][field name]  index of field named "field name"
     *   This last one could be used if you have a field name, but no index.
     *   Test:  if (isset($result['meta']['myfield'])) { ...
     */

    // if no $table specified, assume that we are working with a query
    // result
    if ($table) {
      $this->connect();
      //$id = @mysqli_list_fields($this->Database, $table);
      $id = @mysqli_query($this->Link_ID,"SHOW FIELDS FROM $table");
      if (!$id) {
        $this->halt("Metadata query failed.");
        return false;
      }
    } else {
      #$id = $this->Query_ID; 
      #if (!$id) {
        $this->halt("No table specified.");
        return false;
      #}
    }
 
    $count = @mysqli_num_rows($id);

    for ($i=0; $i<$count; $i++) {
	$row = @mysqli_fetch_array($id);
        $res[$i]["table"] = $table;
        $res[$i]["name"]  = $row["Field"];
        $res[$i]["type"]  = $row["Type"];
        #$res[$i]["len"]   = @mysql_field_len   ($id, $i);
        #$res[$i]["flags"] = @mysql_field_flags ($id, $i);
    }

    if ($full) {
      $res["num_fields"] = $count;
      for ($i=0; $i<$count; $i++) {
        $res["meta"][$res[$i]["name"]] = $i;
      }
    }
    
    // free the result only if we were called on a table
    if ($table) {
      @mysqli_free_result($id);
    }

    for ($i=0; $i<$count; $i++) {
	$res[$i]["group"] = $res[$i]["type"];
	$h = @mysqli_query($this->Link_ID,"SHOW FIELDS FROM $table WHERE Field='".$res[$i]["name"]."'");
	$row = @mysqli_fetch_array($h);
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
	
    }


    return $res;
  }

  /* public: find available table names */
  function table_names() {
    $this->connect();
    $h = @mysqli_query($this->Link_ID,"show tables");
    $i = 0;
    while ($info = @mysqli_fetch_row($h)) {
      $return[$i]["table_name"]      = $info[0];
      $return[$i]["tablespace_name"] = $this->Database;
      $return[$i]["database"]        = $this->Database;
      $i++;
    }
    
    @mysqli_free_result($h);
    return $return;
  }

  function primary_key($table) {
    $this->connect();
    $h = @mysqli_query($this->Link_ID,"show index from `$table` where Key_name='PRIMARY'");
    if ($info = @mysqli_fetch_array($h)) {
	$return = $info["Column_name"];
    }
    @mysql_free_result($h);
    return $return;
  }
    

  /* private: error handling */
  function halt($msg) {
    $this->Error = @mysqli_error($this->Link_ID);
    $this->Errno = @mysqli_errno($this->Link_ID);
    if ($this->Debug) echo "MySQL Error:".$this->Errno." ".$this->Error.$msg;
    if (function_exists("EventLog")) {
      EventLog("MySQL Error:".$this->Errno." ".$this->Error,$msg,"Error");
    }
    if ($this->Halt_On_Error == "no")
      return;

    $this->haltmsg($msg);

    if ($this->Halt_On_Error != "report")
      die("Session halted.");
  }

  function haltmsg($msg) {
    printf("</td></tr></table><b>Database error:</b> %s<br>\n", $msg);
    printf("<b>MySQL Error</b>: %s (%s)<br>\n",
      $this->Errno,
      $this->Error);
  }

}
?>

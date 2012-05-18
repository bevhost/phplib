<?php
/*
 * Session Management for PHP3
 *
 * Copyright (c) 1998-2000 Cameron Taggart (cameront@wolfenet.com)
 *        Modified by Guarneri carmelo (carmelo@melting-soft.com)
 *					 
 * $Id: db_odbc.inc,v 1.3 2000/07/12 18:22:34 kk Exp $
 */ 

class DB_Sql {
  var $Host     = "";
  var $Database = "";
  var $User     = "";
  var $Password = "";
  var $UseODBCCursor = 0;

  var $Link_ID  = 0;
  var $Query_ID = 0;
  var $Record   = array();
  var $Row      = 0;
  
  var $type	= "odbc";

  var $Errno    = 0;
  var $Error    = "";

  var $Auto_Free = 0;     ## set this to 1 to automatically free results

  /* public: constructor */
  function DB_Sql($query = "") {
      $this->query($query);
  }

  function connect($Database = "", $Host = "", $User = "", $Password = "") {
    /* Handle defaults */
    if ("" == $Database) $Database = $this->Database;
    if ("" == $User)     $User     = $this->User;
    if ("" == $Password) $Password = $this->Password;
    if ( 0 == $this->Link_ID ) {
      #$this->Link_ID=odbc_pconnect($this->Database, $this->User, $this->Password, $this->UseODBCCursor);
      $this->Link_ID=odbc_pconnect($Database, $User, $Password);
      if (!$this->Link_ID) {
        $this->halt("Link-ID == false, odbc_pconnect failed");
      }
    }
  }
  
  function query($Query_String) {

    /* No empty queries, please, since PHP4 chokes on them. */
    if ($Query_String == "")
      /* The empty query string is passed on from the constructor,
       * when calling the class without a query, e.g. in situations
       * like these: '$db = new DB_Sql_Subclass;'
       */
      return 0;

    $this->connect();
    
#   printf("<br>Debug: query = %s<br>\n", $Query_String);

#   rei@netone.com.br suggested that we use this instead of the odbc_exec().
#   He is on NT, connecting to a Unix MySQL server with ODBC. -- KK
#    $this->Query_ID = odbc_prepare($this->Link_ID,$Query_String);
#    $this->Query_Ok = odbc_execute($this->Query_ID);
        
    $this->Query_ID = odbc_exec($this->Link_ID,$Query_String);
    $this->Row = 0;
    odbc_binmode($this->Query_ID, 1);
    odbc_longreadlen($this->Query_ID, 4096);
    
    if (!$this->Query_ID) {
      $this->Errno = 1;
      $this->Error = "General Error (The ODBC interface cannot return detailed error messages).";
      $this->halt("Invalid SQL: ".$Query_String);
    }
    return $this->Query_ID;
  }
  
  function next_record() {
    $this->Record = array();
    $stat      = odbc_fetch_into($this->Query_ID, ++$this->Row, $this->Record);
    if (!$stat) {
      if ($this->Auto_Free) {
	    odbc_free_result($this->Query_ID);
        $this->Query_ID = 0;
	  };
    } else {
      // add to Record[<key>]
      $count = odbc_num_fields($this->Query_ID);
      for ($i=1; $i<=$count; $i++)
        $this->Record[strtolower(odbc_field_name ($this->Query_ID, $i)) ] = $this->Record[ $i - 1 ];
    }
    return $stat;
  }
  
  function seek($pos) {
    $this->Row = $pos;
  }

  function table_names() {
    $this->connect();
    $result = odbc_tables ($this->Link_ID) or die ("Query Failed");
    $i = 0;
    while (odbc_fetch_row($result)){
	if(odbc_result($result,"TABLE_TYPE")=="TABLE") {
		$info[$i]["table_name"]      = odbc_result($result,"TABLE_NAME");
		$info[$i]["tablespace_name"] = $this->Database;
		$i++;
	}
    }
    odbc_free_result($result);
    return $info;
  }
  function primary_key($table) {
    $this->connect();
    $result = odbc_primarykeys($this->Link_ID,"","",$table);
    return odbc_result($result,"COLUMN_NAME");
  }
  function metadata($table) {
    $count = 0;
    $id    = 0;
    $res   = array();

    $this->connect();
    $result = odbc_columns($this->Link_ID, "", "", $table);
    while (odbc_fetch_row($result)) {
      $res[$i]["table"]   = $table;
      $res[$i]["name"]    = odbc_result($result,"COLUMN_NAME");
      $res[$i]["type"]    = odbc_result($result,"TYPE_NAME");
      $res[$i]["len"]     = odbc_result($result,"LENGTH");
      $res[$i]["chars"]   = odbc_result($result,"PRECISION");
      $res[$i]["null"]    = odbc_result($result,"NULLABLE");
      $res[$i]["comment"] = odbc_result($result,"REMARKS");
      $res[$i]["flags"]   = ""; // any optional flags to report?
	  $i++;
	#	echo "<br>SCALE:".odbc_result($result,"SCALE");
	#	echo "<br>RADIX:".odbc_result($result,"RADIX");
    }    
    odbc_free_result($result);
    return $res;

/* original code
    $id = odbc_exec($this->Link_ID, "select * from $table");
    if (!$id) {
      $this->Errno = 1;
      $this->Error = "General Error (The ODBC interface cannot return detailed error messages).";
      $this->halt("Metadata query failed.");
    }
    $count = odbc_num_fields($id);
    
    for ($i=1; $i<=$count; $i++) {
      $res[$i]["table"] = $table;
      $name             = odbc_field_name ($id, $i);
      $res[$i]["name"]  = $name;
      $res[$i]["type"]  = odbc_field_type ($id, $name);
      $res[$i]["len"]   = 0;  // can we determine the width of this column?
      $res[$i]["flags"] = ""; // any optional flags to report?
    }
    odbc_free_result($id);
    return $res;
*/
  }
  
  function affected_rows() {
    return odbc_num_rows($this->Query_ID);
  }
  
  function num_rows() {
    # Many ODBC drivers don't support odbc_num_rows() on SELECT statements.
    $num_rows = odbc_num_rows($this->Query_ID);
	//printf ($num_rows."<br>");

    # This is a workaround. It is intended to be ugly.
    if ($num_rows < 0) {
      $i=10;
      while (odbc_fetch_row($this->Query_ID, $i)) 
        $i*=10;

      $j=0;
      while ($i!=$j) {
        $k= $j+intval(($i-$j)/2);
        if (odbc_fetch_row($this->Query_ID, $k))
          $j=$k;
        else 
          $i=$k;
        if (($i-$j)==1) {
          if (odbc_fetch_row($this->Query_ID, $i)) 
            $j=$i;
          else 
            $i=$j; 
        };
        //printf("$i $j $k <br>");
      };
      $num_rows=$i;
    }

    return $num_rows;
  }
  
  function num_fields() {
    return count($this->Record)/2;
  }

  function nf() {
    return $this->num_rows();
  }
  
  function np() {
    print $this->num_rows();
  }
  
  function f($Field_Name) {
    return $this->Record[strtolower($Field_Name)];
  }
  
  function p($Field_Name) {
    print $this->f($Field_Name);
  }
  
  function halt($msg) {
    printf("</td></tr></table><b>Database error:</b> %s<br>\n", $msg);
    printf("<b>ODBC Error</b>: %s (%s)<br>\n",
      $this->Errno,
      $this->Error);
    die("Session halted.");
  }

  /* ODBC Identifiers eg table and column names are defined as
		 letter[digit|letter|_]...
	 Therefore do not to be quoted inside anything. */
  function quote_identifier($str) {
	  return str;
  }
  function qi($str) {
	  return str;
  }
  function quote($str) {
	$slashed = get_magic_quotes_gpc() ? $str : addslashes($str);
	return "'$slashed'";
  }
}
?>

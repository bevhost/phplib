<?php
/*
 * Session Management for PHP3
 *
 * Copyright (c) 2009 David Beveridge <dave@bevhost.com>
 *					 
 * $Id: db_proxy_client.inc,v 1.3 2009/06/03 18:22:34 db Exp $
 *
 * See also, db_proxy_server.inc
 */ 

class DB_Sql {
  var $Host     = "";
  var $Database = "";
  var $User     = "";
  var $Password = "";

  var $Record   = array();
  var $Row      = 0;
  
  var $Errno    = 0;
  var $Error    = "";
  var $Data	= array();

  /* public: constructor */
  function DB_Sql($query = "") {
      $this->query($query);
  }

  function query($Query_String) {

    /* No empty queries, please, since PHP4 chokes on them. */
    if ($Query_String == "")
      /* The empty query string is passed on from the constructor,
       * when calling the class without a query, e.g. in situations
       * like these: '$db = new DB_Sql_Subclass;'
       */
      return 0;

    $this->Row = 0;
    $reply = "";
    $url = $this->Host."?user=".$this->User."&pass=".$this->Password."&sql=".urlencode($Query_String);
    if ($fp = fopen($url,"r")) {
	while (!feof($fp)) {
		$reply .= fread($fp,4096);
	}
    } else {
	$this->Errno = 1;
	$this->Error = "General Error (The Proxy interface cannot return detailed error messages).";
	$this->halt("Invalid SQL: ".$Query_String);
    }
    $this->Data = unserialize($reply);
    return true;
  }
  
  function next_record() {
    $this->Record = $this->Data[$this->Row++];
    return count($this->Record);
  }
  
  function seek($pos) {
    $this->Row = $pos;
  }

  function metadata($table) {
    return $res;
  }
  
  function affected_rows() {
    return false;
  }
  
  function num_rows() {
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
    printf("<b>Database Proxy Error</b>: %s (%s)<br>\n",
      $this->Errno,
      $this->Error);
    die("Session halted.");
  }
}
?>

<?php
/*
 * Session Management for PHP3
 *
 * Copyright (c) 1998-2000 NetUSE AG
 *                    Boris Erdmann, Kristian Koehntopp
 *
 * $Id: user.inc,v 1.3 2001/08/27 01:07:22 pingus Exp $
 *
 */ 

class User extends Session {
  var $classname  = "User";         ## Needed for object serialization.
  var $that_class = "Session_sql";  ## Name of data storage container

  ##
  ## End of parameters.
  ##

  var $name;                          ## Session name
  var $id;                            ## Unique Session ID

  var $pt = array();                  ## This Array contains the registered things
  var $that;

  ## get_id():
  ##
  ## Propagate the session id according to mode and lifetime.
  ## Will create a new id if necessary. To take over abandoned sessions,
  ## one may provide the new session id as a parameter (not recommended).
  function get_id($id = "") {
    $this->id = $id;
  }
  
  ## put_id():
  ## 
  ## Stop using the current session id (unset cookie, ...) and
  ## abandon a session.
  function put_id() {
    ;
  }

  ##
  ## Initialization
  ##

  function start($sid = "") {
    $this->get_id($sid);
	
    if(!isset($this->cookiename)) { $this->cookiename=""; };
	$this->name = $this->cookiename == "" ? $this->classname : $this->cookiename;
	$name = $this->that_class;
	$this->that = new $name;
	$this->that->ac_start();
 
    $this->thaw();
  }

  function freeze() {
		if ($this->id == 'nobody')
			return;

    $str="";

    $this->serialize("this->in",$str);
    $this->serialize("this->pt",$str);

    reset($this->pt);
    while ( list($thing) = each($this->pt) ) {
      $thing=trim($thing);
      if ( $thing ) {
        $this->serialize("GLOBALS['".$thing."']",$str);
      }
    }

    $r = $this->that->ac_store($this->id, $this->name, $str);
    $this->release_lock();

    if (!$r) $this->that->ac_halt("Session: freeze() failed.");
  }
}
?>

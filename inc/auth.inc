<?php
/*
 * Session Management for PHP3
 *
 * Copyright (c) 1998-2000 NetUSE AG
 *                    Boris Erdmann, Kristian Koehntopp
 * Copyright (c) 1999-2000 Internet Images srl
 *                    Massimiliano Masserelli
 *
 * $Id: auth.inc,v 1.4 2001/08/16 18:49:57 pingus Exp $
 *
 */ 

class Auth {
  var $classname = "Auth";
  var $persistent_slots = array("auth");
  
  var $lifetime = 15;             ## Max allowed idle time before
                                  ## reauthentication is necessary.
                                  ## If set to 0, auth never expires.
  
  var $refresh = 0;               ## Refresh interval in minutes. 
                                  ## When expires auth data is refreshed
                                  ## from db using auth_refreshlogin()
                                  ## method. Set to 0 to disable refresh

  var $mode = "log";              ## "log" for login only systems,
                                  ## "reg" for user self registration

  var $magic = "";                ## Used in uniqid() generation

  var $nobody = false;            ## If true, a default auth is created...

  var $cancel_login = "cancel_login"; ## The name of a button that can be 
                                      ## used to cancel a login form

  ## End of user qualifiable settings.

  var $auth = array();            ## Data array
  var $in   = false;
  var $db;

  ##
  ## Initialization
  ##
  function start() {
    $cl = $this->cancel_login;
    global $sess, $$cl;


    ## This is for performance, I guess but I'm not sure if it could
    ## be safely removed -- negro
    if (! $this->in) {
      $sess->register("auth");
      $this->in = true;
    }
    
    ## back compatibility: if d_c is set, create db object
    if(isset($this->database_class)) {
      $class = $this->database_class;
      $this->db = new $class;
    }

    # Check current auth state. Should be one of
    #  1) Not logged in (no valid auth info or auth expired)
    #  2) Logged in (valid auth info)
    #  3) Login in progress (if $$cl, revert to state 1)
    if ($this->is_authenticated()) {
      $uid = $this->auth["uid"];
      switch ($uid) {
        case "form":
          # Login in progress
          if ($$cl) {
            # If $$cl is set, delete all auth info 
            # and set state to "Not logged in", so eventually
            # default or automatic authentication may take place
            $this->unauth();
            $state = 1;
          } else {
            # Set state to "Login in progress"
            $state = 3;
          }
         break;
        default:
          # User is authenticated and auth not expired
          $state = 2;
          break;
      }
    } else {
      # User is not (yet) authenticated
      $this->unauth();
      $state = 1;
    }
    switch ($state) {
      case 1:
        # No valid auth info or auth is expired
        
        # Check for user supplied automatic login procedure 
        if ( $uid = $this->auth_preauth() ) {
          $this->auth["uid"] = $uid;
          $this->auth["exp"] = time() + (60 * $this->lifetime);
          $this->auth["refresh"] = time() + (60 * $this->refresh);
          return true;
        }
        
        # Check for "log" vs. "reg" mode
        switch ($this->mode) {
          case "yes":
          case "log":
            if ($this->nobody) {
              # Authenticate as nobody
              $this->auth["uid"] = "nobody";
              # $this->auth["uname"] = "nobody";
              $this->auth["exp"] = 0x7fffffff;
              $this->auth["refresh"] = 0x7fffffff;
              return true;
            } else {
              # Show the login form
              $this->auth_loginform();
              $this->auth["uid"] = "form";
              $this->auth["exp"] = 0x7fffffff;
              $this->auth["refresh"] = 0x7fffffff;
              $sess->freeze();
              exit;
            }
            break;
          case "reg":
           if ($this->nobody) {
              # Authenticate as nobody
              $this->auth["uid"] = "nobody";
              # $this->auth["uname"] = "nobody";
              $this->auth["exp"] = 0x7fffffff;
              $this->auth["refresh"] = 0x7fffffff;
              return true;
            } else {
            # Show the registration form
              $this->auth_registerform();
              $this->auth["uid"] = "form";
              $this->auth["exp"] = 0x7fffffff;
              $this->auth["refresh"] = 0x7fffffff;
              $sess->freeze();
              exit;
            }
            break;
          default:
            # This should never happen. Complain.
            echo "Error in auth handling: no valid mode specified.\n";
            $sess->freeze();
            exit;
        }
        break;
      case 2:
        # Valid auth info
        # Refresh expire info
        ## DEFAUTH handling: do not update exp for nobody.
        if ($uid != "nobody")
          $this->auth["exp"] = time() + (60 * $this->lifetime);
        break;
      case 3:
        # Login in progress, check results and act accordingly
        switch ($this->mode) {
          case "yes":
          case "log":
            if ( $uid = $this->auth_validatelogin() ) {
	      $this->auth_delete_old_sessions();			# Comment this out to allow multiple simultaneous logins.
              $this->auth["uid"] = $uid;
              $this->auth["exp"] = time() + (60 * $this->lifetime);
              $this->auth["refresh"] = time() + (60 * $this->refresh);
              return true;
            } else {
              $this->auth_loginform();
              $this->auth["uid"] = "form";
              $this->auth["exp"] = 0x7fffffff;
              $this->auth["refresh"] = 0x7fffffff;
              $sess->freeze();
              exit;
            }
            break;
          case "reg":
            if ($uid = $this->auth_doregister()) {
              $this->auth["uid"] = $uid;
              $this->auth["exp"] = time() + (60 * $this->lifetime);
              $this->auth["refresh"] = time() + (60 * $this->refresh);
              return true;
            } else {
              $this->auth_registerform();
              $this->auth["uid"] = "form";
              $this->auth["exp"] = 0x7fffffff;
              $this->auth["refresh"] = 0x7fffffff;
              $sess->freeze();
              exit;
            }
            break;
          default:
            # This should never happen. Complain.
            echo "Error in auth handling: no valid mode specified.\n";
            $sess->freeze();
            exit;
            break;
        }
        break;
      default:
        # This should never happen. Complain.
        echo "Error in auth handling: invalid state reached.\n";
        $sess->freeze();
        exit;
        break;
    }
  }

  function login_if( $t ) {
    if ( $t ) {
      $this->unauth();  # We have to relogin, so clear current auth info
      $this->nobody = false; # We are forcing login, so default auth is 
                             # disabled
      $this->start(); # Call authentication code
    }
  }

  function unauth($nobody = false) {
    $this->auth["uid"]   = "";
    $this->auth["perm"]  = "";
    $this->auth["exp"]   = 0;

    ## Back compatibility: passing $nobody to this method is
    ## deprecated
    if ($nobody) {
      $this->auth["uid"]   = "nobody";
      $this->auth["perm"]  = "";
      $this->auth["exp"]   = 0x7fffffff;
    }
  }
  

  function logout($nobody = "") {
    global $sess;
    $this->db = new $this->database_class;
    $this->db->prepare("DELETE FROM active_sessions WHERE name=? and sid=?");
    $this->db->execute($sess->name,$sess->id);
    $sess->unregister("auth");
    unset($this->auth["uname"]);
    unset($this->auth["uid"]);
    $this->unauth($nobody == "" ? $this->nobody : $nobody);
  }

  function is_authenticated() {
    if (
      isset($this->auth["uid"])
        &&
      $this->auth["uid"] 
        && 
      (($this->lifetime <= 0) || (time() < $this->auth["exp"]))
    ) {
      # If more than $this->refresh minutes are passed since last check,
      # perform auth data refreshing. Refresh is only done when current
      # session is valid (registered, not expired).
      if (
        ($this->refresh > 0) 
         && 
        ($this->auth["refresh"])
         && 
        ($this->auth["refresh"] < time())
      ) {
        if ( $this->auth_refreshlogin() ) {
          $this->auth["refresh"] = time() + (60 * $this->refresh);
        } else {
          return false;
        }
      }
      return $this->auth["uid"];
    } else {
      return false;
    }
  }
    
  ########################################################################
  ##
  ## Helper functions
  ##
  function url() {
    return $GLOBALS["sess"]->self_url();
  }

  function purl() {
    print $GLOBALS["sess"]->self_url();
  }

  ## This method can authenticate a user before the loginform
  ## is being displayed. If it does, it must set a valid uid 
  ## (i.e. nobody IS NOT a valid uid) just like auth_validatelogin,
  ## else it shall return false.

  function auth_preauth() { return false; }
  
  ##
  ## Authentication dummies. Must be overridden by user.
  ##
  
  function auth_loginform() { ; }

  function auth_validatelogin() { ; }
  
  function auth_refreshlogin() { ; }

  function auth_registerform() { ; }

  function auth_doregister() { ; }

  function auth_delete_old_sessions() {
	global $sess;
	if ($this->auth["uname"]<>"admin") {
		$this->db->prepare("DELETE FROM active_sessions WHERE name=? and sid!=? and username=?");
		$this->db->execute($sess->name,$sess->id,$this->auth["uname"]);
	}
  }

  ##
  ## Function for force login a user (eg: after forgot password )
  ##

  function login_user($id,$u,$p="user") {
	$this->auth["uname"] = $u;
	$this->auth["perm"] = $p;
	$this->auth["uid"] = $id;
	$this->auth["exp"] = time() + (60 * $this->lifetime);
	$this->auth["refresh"] = time() + (60 * $this->refresh);
	$this->lifetime = 0;
  }

/* 
Function which uses the below schema to keep a track of brute force auth attempts.

CREATE TABLE `badauth_counts` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `username` int(11) NOT NULL,
 `address` varchar(40) NOT NULL,
 `ctime` datetime NOT NULL,
 `mtime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 `count` int(11) NOT NULL DEFAULT '0',
 `no_cookie` int(11) NOT NULL DEFAULT '0',
 `user_agent` varchar(50) NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `username` (`username`),
 KEY `address` (`address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
*/
  var $grace = 10;	# Number of login attemps allowed, (or only half this many without a valid cookie)

  function check_brute_force($addr,$uid) {  # Address can be IPv4, IPv6 or MAC
	if (!preg_match('/^[0-9a-f\:\.]+$/i',$addr)) {  # Dodgy Address - immediate blockout
		$this->unauth();
		return true;
	}
	$block = false;
	$this->db->prepare("SELECT SUM(count+no_cookie) FROM badauth_counts WHERE address=?");
	$this->db->execute($addr);
	if ($count = $this->db->fetchColumn()) {
	    if ($count>$this->grace) {
		$block=true;
		$this->brute_force_block_address($addr,$count);
	    } else {
		$this->auth["tries"] = $count;
		$this->db->prepare("SELECT SUM(count+no_cookie) FROM badauth_counts WHERE username=?");
		$this->db->execute($this->auth["uname"]);
		if ($count = $this->db->fetchColumn()) {
			if ($count>$this->auth["tries"]) $this->auth["tries"] = $count;
			if ($count>$this->grace) {
				$block=true;
				$this->brute_force_block_username($this->auth["uname"],$count);
			}
		}
	    }
	}
	if ($block) { 
		# deny login
		$this->unauth();	
		return true;
	} else {
		if ($uid) {
			# login ok, reset counters
			$this->db->prepare("UPDATE badauth_counts SET count=0, no_cookie=0 WHERE username=? AND address=?");
			$this->db->execute($this->auth["uname"], $addr);
		} else {
			# failed login, increment counters
			if (!isset($_COOKIE[$GLOBALS["sess"]->name])) {  # If no valid cookie, double the penalty count
				$upd = "no_cookie=no_cookie+1,";
			} else {
				$upd="";
			}
			$address = "address = '$addr'";
			$user = "username = ".$this->db->quote($this->auth["uname"]);
			$agent = "user_agent = ".$this->db->quote($_SERVER["HTTP_USER_AGENT"]);

			$this->db->query("INSERT INTO badauth_counts SET count=1, $address, $user, $agent, ctime=NOW() ON DUPLICATE KEY UPDATE $upd count=count+1, mtime=NOW()");
		}
		return false;
	}
  }

  function brute_force_block_address($addr,$count) {
	# Assume Dave's SQL Injection Attack Scanner is Installed
	$sql = "INSERT INTO exploits SET "; 	$data = array();
	if (strlen($addr)==17) {
		$sql .= "mac = :mac, ";		$data[":mac"] = $addr;
	} else {
		$sql .= "ip = :ip, "; 		$data[":ip"] = $addr;
	}
	$sql .= "violation = :v, ";		$data[":v"] = $count;
	$sql .= "target = :target, ";		$data[":target"] = $_SERVER['HTTP_HOST'].$_SERVER["PHP_SELF"];
	$sql .= "string = 'Too Many Brute Force Password Attempts', banned=1, datetime=now()";
	$this->db->prepare($sql);
	$prevstate = $this->db->Halt_On_Error;
	$this->db->Halt_On_Error = "no";   # quietly ignore errors, if it's not installed.
	$this->db->execute($data);
	if ($this->db->num_rows()) {
		# If successfully entered into exploits, that address cannot reach login page, so clean up.
		$this->db->prepare("DELETE FROM badauth_counts WHERE address=?");
		$this->db->execute($addr);
	}
	$this->db->Halt_On_Error = $prevstate;
  }
  function brute_force_block_username($username,$count) {
	#Override this function to block a useraccount
  }

}
?>

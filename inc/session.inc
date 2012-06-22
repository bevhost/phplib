<?php
/*
 * Session Management for PHP3
 *
 * Copyright (c) 1998-2000 NetUSE AG
 *                    Boris Erdmann, Kristian Koehntopp
 *
 * $Id: session.inc,v 1.12 2001/08/29 07:30:21 richardarcher Exp $
 *
 */

class Session {
  var $classname = "Session";         ## Needed for object serialization.

  ## Define the parameters of your session by either overwriting
  ## these values or by subclassing session (recommended).

  var $magic = "";                    ## Some string you should change.
  var $mode = "cookie";               ## We propagate session IDs with cookies
  var $fallback_mode;                 ## If this doesn't work, fall back...
  var $lifetime = 0;                  ## 0 = do session cookies, else minutes

  var $cookie_domain = "";            ## If set, the domain for which the
                                      ## session cookie is set.

  var $gc_time  = 1440;               ## Purge all session data older than 1440 minutes.
  var $gc_probability = 5;            ## Garbage collect probability in percent

  var $auto_init = "";                ## Name of the autoinit-File, if any.
  var $secure_auto_init = 1;          ## Set to 0 only, if all pages call
                                      ## page_close() guaranteed.

  var $allowcache = "no";             ## "passive", "no", "private" or "public"
  var $allowcache_expire = 1440;      ## If you allowcache, data expires in this
                                      ## many minutes.
  var $that_class = "";               ## Name of data storage container


  ##
  ## End of parameters.
  ##

  var $name;                          ## Session name
  var $id;                            ## Unique Session ID
  var $that;

  var $pt = array();                  ## This Array contains the registered things
  var $in = 0;                        ## Marker: Did we already include the autoinit file?

  ## register($things):
  ##
  ## call this function to register the things that should become persistent

  function register($things) {
    $things = explode(",",$things);
    reset($things);
    while ( list(,$thing) = each($things) ) {
      $thing=trim($thing);
      if ( $thing ) {
        $this->pt[$thing] = true;
      }
    }
  }

  function is_registered($name) {
    if (isset($this->pt[$name]) && $this->pt[$name] == true)
      return true;
    return false;
  }

  function unregister($things) {
    $things = explode(",", $things);
    reset($things);
    while (list(,$thing) = each($things)) {
      $thing = trim($thing);
      if ($thing) {
        unset($this->pt[$thing]);
      }
    }
  }

  function erase($things) {
    $things = explode(",", $things);
    reset($things);
    while (list(,$thing) = each($things)) {
      $thing = trim($thing);
      if ($thing) {
        $GLOBALS[$thing]=false;
      }
    }
  }


  ## get_id():
  ##
  ## Propagate the session id according to mode and lifetime.
  ## Will create a new id if necessary. To take over abandoned sessions,
  ## one may provide the new session id as a parameter (not recommended).

  function get_id($id = "") {
    global $QUERY_STRING;
    $newid=true;

    $this->name = $this->cookiename==""?$this->classname:$this->cookiename;

    if ( "" == $id ) {
      $newid=false;
      switch ($this->mode) {
        case "cookie":
        case "get":
          $id = isset($_COOKIE[$this->name]) ?
                $_COOKIE[$this->name] :
                ( isset($_POST[$this->name]) ?
                $_POST[$this->name] :
                ( isset($_GET[$this->name]) ?
                $_GET[$this->name] :
                "")) ;
        break;
	case "none":
	break;
        default:
          die("This has not been coded yet.");
        break;
      }
    }

    if ( "" == $id ) {
      $newid=true;
      $id = $this->that->ac_newid(md5(uniqid($this->magic)), $this->name);
    }

    switch ($this->mode) {
      case "cookie":
        if ( $newid && ( 0 == $this->lifetime ) ) {
          SetCookie($this->name, $id, 0, "/", $this->cookie_domain);
        }
        if ( 0 < $this->lifetime ) {
          SetCookie($this->name, $id, time()+$this->lifetime*60, "/", $this->cookie_domain);
        }

        // Remove session ID info from QUERY String - it is in cookie
        if ( isset($QUERY_STRING) && ("" != $QUERY_STRING) ) {
          $QUERY_STRING = mb_ereg_replace(
            "(^|&)".quotemeta(urlencode($this->name))."=".$id."(&|$)",
            "\\1", $QUERY_STRING);
        }
      break;
      case "get":
        if ( isset($QUERY_STRING) && ("" != $QUERY_STRING) ) {
          $QUERY_STRING = mb_ereg_replace(
            "(^|&)".quotemeta(urlencode($this->name))."=".$id."(&|$)",
            "\\1", $QUERY_STRING);
        }
      break;
      default:
        ;
      break;
    }
    $this->id = $id;
  }

  ## put_id():
  ##
  ## Stop using the current session id (unset cookie, ...) and
  ## abandon a session.
  function put_id() {

    switch ($this->mode) {
      case "cookie":
        $this->name = $this->cookiename == "" ? $this->classname : $this->cookiename;
        SetCookie($this->name, "", 0, "/", $this->cookie_domain);
        $_COOKIE[$this->name] = "";
      break;

      default:
        // do nothing. We don't need to die for modes other than cookie here.
      break;
    }
  }

  ## delete():
  ##
  ## Delete the current session record and put the session id.

  function delete() {
      $this->that->ac_delete($this->id, $this->name);
      $this->put_id();
  }

  ## url($url):
  ##
  ## Helper function: returns $url concatenated with the current
  ## session $id.

  function url($url) {
    global $QUERY_STRING;
    // Remove existing session info from url
    $url = mb_ereg_replace(
      "([&?])".quotemeta(urlencode($this->name))."=".$this->id."(&|$)",
      "\\1", $url);

    // Remove trailing ?/& if needed
    $url=mb_ereg_replace("[&?]+$", "", $url);

    switch ($this->mode) {
      case "get":
        $url .= ( strpos($url, "?") != false ?  "&" : "?" ).
                urlencode($this->name)."=".$this->id;
      break;
      default:
        ;
      break;
    }

    // Encode naughty characters in the URL
    $url = str_replace(array("<", ">", " ", "\"", "'"), 
                       array("%3C", "%3E", "+", "%22", "%27"), $url);
    return $url;
	// .((isset($QUERY_STRING) && ("" != $QUERY_STRING)) ? "?".$QUERY_STRING : "");
  }

  function purl($url) {
    print $this->url($url);
  }

  function self_url() {
    global $QUERY_STRING;
    $PHP_SELF = $_SERVER["PHP_SELF"];

    return $this->url($PHP_SELF);
	//.  ((isset($QUERY_STRING) && ("" != $QUERY_STRING)) ? "?".$QUERY_STRING : ""));
  }

  function pself_url() {
    print $this->self_url();
  }

  function hidden_session()
  {
    printf("<input type=\"hidden\" name=\"%s\" value=\"%s\">\n", $this->name, $this->id);
  }

  function add_query($qarray) {
    global $QUERY_STRING;

    $in_sep = substr(ini_get("arg_separator.input")."&",0,1);

    if ((isset($QUERY_STRING) && ("" != $QUERY_STRING)) 
      || ($this->mode == "get")) {
      $sep_char = $in_sep;
    } else {
      $sep_char = "?";
    }

    $qstring = "";
    while (list($k, $v) = each($qarray)) {
      $qstring .= $sep_char . urlencode($k) . "=" . urlencode($v);
      $sep_char = $in_sep;
    }

    return $qstring;
  }

  function padd_query($qarray) {
    print $this->add_query($qarray);
  }

  ## serialize($var,&$str):
  ##
  ## appends a serialized representation of $$var
  ## at the end of $str.
  ##
  ## To be able to serialize an object, the object must implement
  ## a variable $classname (containing the name of the class as string)
  ## and a variable $persistent_slots (containing the names of the slots
  ## to be saved as an array of strings).

  function serialize($var, &$str) {
    static $t,$l,$k;

    ## Determine the type of $$var
    eval("\$t = gettype(\$$var);");
    switch ( $t ) {

      case "array":
        ## $$var is an array. Enumerate the elements and serialize them.
        eval("reset(\$$var); \$l = gettype(list(\$k)=each(\$$var));");
        $str .= "\$$var = array(); ";
        while ( "array" == $l ) {
          ## Structural recursion
          $this->serialize($var."['".mb_ereg_replace("([\\'])", "\\\\1", $k)."']", $str);
          eval("\$l = gettype(list(\$k)=each(\$$var));");
        }

      break;
      case "object":
        ## $$var is an object. Enumerate the slots and serialize them.
        eval("\$k = \$${var}->classname; \$l = reset(\$${var}->persistent_slots);");
        $str.="\$$var = new $k; ";
        while ( $l ) {
          ## Structural recursion.
          $this->serialize($var."->".$l, $str);
          eval("\$l = next(\$${var}->persistent_slots);");
        }

      break;
      default:
        ## $$var is an atom. Extract it to $l, then generate code.
        eval("\$l = \$$var;");
        $str.="\$$var = '".mb_ereg_replace("([\\'])", "\\\\1", $l)."'; ";
      break;
    }
  }

  function get_lock() {
      $this->that->ac_get_lock();
  }

  function release_lock() {
      $this->that->ac_release_lock();
  }

  ## freeze():
  ##
  ## freezes all registered things ( scalar variables, arrays, objects ) into
  ## a database table

  function freeze() {
    global $auth;
    unset($auth->db);  // PDO objects can't be serialized and we don't need this anyway
    $str="";
    $arr=array();
    //$this->serialize("this->in", $str);
    //$this->serialize("this->pt", $str);

    reset($this->pt);
    while ( list($thing) = each($this->pt) ) {
      $thing=trim($thing);
      if ( $thing ) {
        //$this->serialize("GLOBALS['".$thing."']", $str);  //php3 serialize was broken, use our own.
	$arr[$thing] = $GLOBALS[$thing];
      }
    }
    $str=serialize($arr);
    $r = $this->that->ac_store($this->id, $this->name, $str);
    $this->release_lock();

    if (!$r) $this->that->ac_halt("Session: freeze() failed.");
  }

  ## thaw:
  ##
  ## Reload frozen variables from the database and microwave them.

  function thaw() {
    $this->get_lock();

    $vals = $this->that->ac_get_value($this->id, $this->name);
    if ((isset($vals)) and (substr($vals,0,1)=="$")) {
	eval(sprintf(";%s",$vals));  // support old session data for php3
    } else {
	$arr = unserialize($vals);   // new serialised data req. php => 4.07
	if (is_array($arr)) {
	    foreach($arr as $k=>$v) {
		$this->pt[$k]=1;
		$GLOBALS[$k]=$v;
	    }
	}
    }
  }

  ##
  ## Variable precedence functions
  ##
  function reimport_get_vars() {
    $this->reimport_any_vars("_GET");
  }

  function reimport_post_vars() {
    $this->reimport_any_vars("_POST");
  }

  function reimport_cookie_vars() {
    $this->reimport_any_vars("_COOKIE");
  }

  function reimport_any_vars($arrayname) {
    global $$arrayname;

    if (!is_array($$arrayname))
      return;

    reset($$arrayname);
    while(list($key, $val) = each($$arrayname)) {
      $GLOBALS[$key] = $val;
    }
  }

  ##
  ## All this is support infrastructure for the start() method
  ##

  function set_container() {
    $name = $this->that_class;
    $this->that = new $name;

    $this->that->ac_start();
  }

  function set_tokenname() {
    $this->name = $this->cookiename==""?$this->classname:$this->cookiename;
  }

  function release_token($sid = "") {
    global $HTTPS, $PHP_SELF;

    if ( isset($this->fallback_mode)
      && ("get" == $this->fallback_mode)
      && ("cookie" == $this->mode)
      && (! isset($_COOKIE[$this->name])) ) {

      // Looks like no cookie here - check GET/POST params
      if ( isset($_GET[$this->name])
        || isset($_POST[$this->name]) ) {
        // Session info passed via GET/POST - go to fallback_mode
        $this->mode = $this->fallback_mode;
      } else {
        // It seems to be the first load of this page -
        // no cookie and no GET/POST params

        // Generate session ID and setup cookie.
        $this->get_id($sid);

        // Next line is to generate correct self_url() later
        $this->mode = $this->fallback_mode;

        if ( isset($HTTPS) && $HTTPS == 'on' ) {
          ## You will need to fix suexec as well, if you 
          ## use Apache and CGI PHP
          $PROTOCOL='https';
        } else {
          $PROTOCOL='http';
        }
	$url = $this->self_url();
        header("Status: 302 Moved Temporarily");
        header("Location: ". $PROTOCOL. "://".$_SERVER["HTTP_HOST"].$url);
        exit;
      }
    }
  }

  function put_headers() {
    # Allowing a limited amount of caching, as suggested by
    # Padraic Renaghan on phplib@lists.netuse.de.
    #
    # Note that in HTTP/1.1 the Cache-Control headers override the Expires
    # headers and HTTP/1.0 ignores headers it does not recognize (e.g,
    # Cache-Control). Mulitple Cache-Control directives are split into
    # mulitple headers to better support MSIE 4.x.
    #
    # Added pre- and post-check for MSIE 5.x as suggested by R.C.Winters,
    # see http://msdn.microsoft.com/workshop/author/perf/perftips.asp#Use%20Cache-Control%20Extensions
    # for details
    switch ($this->allowcache) {

      case "passive":
        $mod_gmt = gmdate("D, d M Y H:i:s", getlastmod()) . " GMT";
        header("Last-Modified: " . $mod_gmt);
        # possibly ie5 needs the pre-check line. This needs testing.
        header("Cache-Control: post-check=0, pre-check=0");
      break;

      case "public":
        $exp_gmt = gmdate("D, d M Y H:i:s", time() + $this->allowcache_expire * 60) . " GMT";
        $mod_gmt = gmdate("D, d M Y H:i:s", getlastmod()) . " GMT";
        header("Expires: " . $exp_gmt);
        header("Last-Modified: " . $mod_gmt);
        header("Cache-Control: public");
        header("Cache-Control: max-age=" . $this->allowcache_expire * 60);
      break;

      case "private":
        $mod_gmt = gmdate("D, d M Y H:i:s", getlastmod()) . " GMT";
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . $mod_gmt);
        header("Cache-Control: private");
        header("Cache-Control: max-age=" . $this->allowcache_expire * 60);
        header("Cache-Control: pre-check=" . $this->allowcache_expire * 60);
      break;

      default:
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-cache");
        header("Cache-Control: post-check=0, pre-check=0");
        header("Pragma: no-cache");
      break;
    }
  }

  ##
  ## Garbage collection
  ##
  ## Destroy all session data older than this
  ##
  function gc() {
    srand(time());
    if ((rand()%100) < $this->gc_probability) {
      $this->that->ac_gc($this->gc_time, $this->name);
    }
  }

  ##
  ## Initialization
  ##

  function get_domain() {
        $domain = "";
        if ($fp = fopen("/etc/resolv.conf","r")) {
                while (!feof($fp)) {
                        list($keyword,$firstparam) = explode(" ",fgets($fp,1000));
                        switch ($keyword) {
                                case "search":
                                        if ($domain) break;
                                case "domain":
                                        $domain = ".".$firstparam;
                                default:
                        }
                }
        }
        return $domain;
  }

  function start($sid = "") {
    $host = $_SERVER["HTTP_HOST"];
    if (!strpos($host,'.')) {
        if ( isset($HTTPS) && $HTTPS == 'on' ) {
          $PROTOCOL='https';
        } else {
          $PROTOCOL='http';
        }
        $host .= $this->get_domain();
        $url = $this->self_url();
        header("Status: 302 Moved Temporarily");
        header("Location: ". $PROTOCOL. "://".$host.$url);
        exit;
    }
    if (array_key_exists("HTTP_HOST",$_SERVER)) {
	$host = explode(":",$_SERVER["HTTP_HOST"]);
	$this->cookie_domain = $host[0];
	if (isset($host[1])) $port_no = $host[1];
    }
    $this->set_container();
    $this->set_tokenname();
    $this->put_headers();
//    $this->release_token($sid);
    $this->get_id($sid);
    $this->thaw();
    $this->gc();
  }


  ## copied here from perm.inc so that permissions can be checked for pages
  ## that don't require a login.  Especially useful for menus.
  ## eg: allows an editor or admin to see menus that goto private pages
  ## from pages that are public, but also allow those to be hidden from other users.

  function have_perm($p) {
    global $auth;

    if (empty($auth->auth["perm_class"])) {
	return false;  // user has not logged in yet.
    }

    $sessperm = new $auth->auth["perm_class"];

    if (! isset($auth->auth["perm"]) ) {
      $auth->auth["perm"] = "";
      echo $auth->auth["uname"]." does not have perm ";
    }
    $pageperm = mb_split(",", $p);
    $userperm = mb_split(",", $auth->auth["perm"]);

    list ($ok0, $pagebits) = $sessperm->permsum($pageperm);
    list ($ok1, $userbits) = $sessperm->permsum($userperm);

    $has_all = (($userbits & $pagebits) == $pagebits);
    if (!($has_all && $ok0 && $ok1) ) {
      return false;
    } else {
      return true;
    }
  }
  function have_edit_perm() {
      global $sess;
      if (!isset($_ENV["show_edit"])) {
         $ok = false;
         if (!empty($_ENV["edit_requires"])) {
                foreach(explode(",",$_ENV["edit_requires"]) as $need) {
                        if ($sess->have_perm($need)) $ok = true;
                }
         } else $ok = true;
         $_ENV["show_edit"] = $ok;
      }
      return $_ENV["show_edit"];
  }

}
?>

<?php
/*
 * Session Management for PHP3
 *
 * Copyright (c) 1998,1999 SH Online Dienst GmbH
 *                    Boris Erdmann, Kristian Koehntopp
 *
 * $Id: page.inc,v 1.7 1999/01/05 14:35:33 sas Exp $
 *
 */ 

function page_open($feature) {

  # enable sess and all dependent features.
  if (isset($feature["sess"])) {
    global $sess;
    $sess = new $feature["sess"];
    $sess->start();

    # the auth feature depends on sess
    if (isset($feature["auth"])) {
      global $auth;
      
      if (!isset($auth)) {
        $auth = new $feature["auth"];
      }
      $auth->start();
  
      # the perm feature depends on auth and sess
      if (isset($feature["perm"])) {
        global $perm;
        
        if (!isset($perm)) {
          $perm = new $feature["perm"];
	  $auth->auth["perm_class"] = $feature["perm"];
        }
      }

      # the user feature depends on auth and sess
      if (isset($feature["user"])) {
        global $user;
        
        if (!isset($user)) {
          $user = new $feature["user"];
        }
        $user->start($auth->auth["uid"]);
      }
    }

    if (class_exists("My_Cart")) {
	global $cart;
	$cart = new My_Cart;
	$cart->start();
    }

    ## Load the auto_init-File, if one is specified.
    if (($sess->auto_init != "") && ($sess->in != 1)) {
      $sess->in = 1;
      include($_ENV["local"] . $sess->auto_init);
      if ($sess->secure_auto_init != "") {
        $sess->freeze();
      }
    } 
  }
  if (@$_ENV["header"])					/* header set */ 
    if (!isset($feature["silent"]))			/* not disabled */
      if (@$GLOBALS["Field"]) 				/* popup window ? */
	include($_ENV["SiteRoot"].$_ENV["pophead"]);
      else						/* normal page */
	include($_ENV["SiteRoot"].$_ENV["header"]);
}

function page_close($feature="") {
  global $sess, $user;

  if (isset($sess)) {
    $sess->freeze();
    if (isset($user)) {
      if (is_object($user)) $user->freeze();
    }
  }
  if (@$_ENV["footer"])					/* footer set */ 
    if (!isset($feature["silent"]))			/* not disabled */
	include($_ENV["SiteRoot"].$_ENV["footer"]);
}

function sess_load($session) {
  foreach( $session as $k=>$v) {
    $GLOBALS[$k] = new $v;
    $GLOBALS[$k]->start();
  }
}

function sess_save($session) {
  reset($session);
#  while (list(,$v) = each($session)) {
  foreach( $session as $v) {
    $GLOBALS[$v]->freeze();
  }
}
?>

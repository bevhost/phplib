<?php
include('phplib/prepend.php');
$nav = "pass";
page_open(array("sess"=>$_ENV["SessionClass"],"auth"=>$_ENV["AuthClass"],"perm"=>$_ENV["PermClass"]));
$db = new $_ENV["DatabaseClass"];

if ($perm->have_perm("guest")) {
	echo "<big>Guest user cannot change password.</big>";
} else {
$debug=0;
/*
 * Session Management for PHP
 *
 * Copyright (c) 1998,1999 Jan Legenhausen, Kristian Koehntopp
 *
 * $Id: new_user.php3,v 1.10 1999/10/14 10:38:21 kk Exp $
 *
 * NOTE: This script requires that you have set up your PHPLIB
 *       with working Auth and Perm subclasses and that your
 *       $perm->permissions array includes a permission named
 *       "admin". If you are using the example, this will
 *       be the case.
 *
 * This script is capable of editing the user database. It requires
 * an authenticated user. If the user has admin privilege, he can
 * edit all users. If the user has less privilege, he can view all
 * users, but not the passwords and can only change the own password.
 *
 * The script generates forms that submit values back to the script.
 * Consequently the script below has three parts: 
 *
 * 1. A section where utility functions are defined.
 * 2. A section that is called only after the submit.
 * 3. And a final section that is called when the script runs first time and
 *    every time after the submit.
 *
 * Scripts organized in this way will allow the user perpetual
 * editing and they will reflect submitted changes immediately
 * after a form submission.
 *
 * We consider this to be the standard organization of table editor
 * scripts.
 *
 */
 


   $hash_secret = "Jabberwocky...";

###
### Utility functions
###

## my_error($msg):
##
## Display error messages

  function my_error($msg) {
?>
  <table border=0 class=default align="center" cellspacing=0 cellpadding=4 width=540>
   <tr>
    <td><font color=#FF2020>Error: <?php print $msg ?></font></td>
   </tr>
  </table>
  <BR>
<?php
}

## my_msg($msg):
##
## Display success messages
  function my_msg($msg) {
?>
 <table border=0 class=default align="center" cellspacing=0 cellpadding=4 width=540>
  <tr>
   <td><font color=#008000>O.K.: <?php print $msg ?></font></td>
  </tr>
 </table>
 <br>
<?php
}


?>
<html>
 <head>
<!--
// here i include my personal meta-tags; one of those might be useful:
// <META HTTP-EQUIV="REFRESH" CONTENT="<?php print $auth->
lifetime*60;?>; URL=logoff.html"> // 
<?php include($_ENV["libdir"] . "meta.inc");?>
--> <font class="bigTextBold"> </font> 
<?php

###
### Submit Handler
###

## Get a database connection

// Check if there was a submission
while (is_array($_POST) 
		  && list($key, $val) = each($_POST)) {
	if(debug == 1) {
		printf("key +$key+, val +$val+<br>");
	}
	switch ($key) {
		case "create": // Create a new user
			if (!$perm->have_perm("admin")) { // Do we have permission to do so?
				my_error("You do not have permission to create users.");
				break;
			}
			if (empty($username) || empty($password)) { // Do we have all necessary data?
				my_error("Please fill out <B>Username</B> and <B>Password</B>!");
				break;
			}
         /* Does the user already exist?
				NOTE: This should be a transaction, but it isn't... */
			$db->query("select * from auth_user where username='$username'");
			if ($db->nf()>0) {
				my_error("User <B>$username</B> already exists!");
				break;
			}
			// Create a uid and insert the user...
			$u_id=md5(uniqid($hash_secret));
			$permlist = addslashes(implode($perms,","));
			$query = "insert into auth_user values('$u_id','$username','$password','$permlist')";
			$db->query($query);
			if ($db->affected_rows() == 0) {
				my_error("<b>Failed:</b> $query");
				break;
			}
			my_msg("User \"$username\" created.<BR>");
			break;

		case "u_edit": // Change user parameters
			if($debug == 1)
				printf("u_edit, u_id +%s+<br>", $u_id);
			if (!$perm->have_perm("admin")) {    // user is not admin
				if($auth->auth["uid"] == $u_id) { // user changes his own account
					$query = "update auth_user set password='$password' where user_id='$u_id'";
					$db->query($query);
					if ($db->affected_rows() == 0) {
						my_error("<b>Failed:</b> $query");
						break;
					}
					my_msg("Password of ". $auth->auth["uname"] ." changed.<BR>");
				}
			} else { // user is admin
				if (empty($username) || empty($password)) { // Do we have all necessary data?
					my_error("Please fill out <B>Username</B> and <B>Password</B>!");
					break;
				}
				// Update user information.
				$permlist = addslashes(implode($perms,","));
				$query = "update auth_user set username='$username', password='$password', perms='$permlist' where user_id='$u_id'";
				$db->query($query);
				if ($db->affected_rows() == 0) {
					my_error("<b>Failed:</b> $query");
					break;
				}
				my_msg("User \"$username\" changed.<BR>");
			}
			break;

		case "u_kill": // Do we have permission to do so?
			if (!$perm->have_perm("admin")) {
				my_error("You do not have permission to delete users.");
				break;
			}
			// Delete that user.
			$query = "delete from auth_user where user_id='$u_id' and username='$username'";
			$db->query($query);
			if ($db->affected_rows() == 0) {
				my_error("<b>Failed:</b> $query");
				break;
			}
			my_msg("User \"$username\" deleted.<BR>");
			break;

		default:
			if(debug == 1)
				printf("default switch: u_id: .$u_id. <br>");
			break;
	}
}

/* Output user administration forms, including all updated
	information, if we come here after a submission...
*/

function showPassRecord($db) {
global $sess, $perm;
?>
              <!-- existing user -->
              <form method="post" action="<?php $sess->pself_url() ?>">
                <tr valign=middle align=left>
                  <?php
  if ($perm->have_perm("admin")):
 ?>
                  <td><input type="text" name="username" size=12 maxlength=32 value="<?php $db->p("username") ?>"></td>
                  <td><input type="text" name="password" size=12 maxlength=32 value="<?php $db->p("password") ?>"></td>
                  <td><?php print $perm->perm_sel("perms", $db->f($table."perms")) ?></td>
                  <td align=center> <input type="hidden" name="u_id"   value="<?php $db->p("user_id") ?>">
                        <input type="submit" name="u_edit" value="Change">
                        <input type="submit" name="u_kill" value="Kill">
                  </td>
                  <?php
  elseif (strtolower($auth->auth["uname"]) == strtolower($db->f("a.username"))):
 ?>
                  <td>
		    <input type="hidden" name="username"   value="<?php $db->p("username") ?>">
                    <?php $db->p("username") ?>
                  </td>
                  <td><input type="password" name="password" size=12 maxlength=32 value="<?php $db->p("password") ?>"></td>
                  <td>
                    <?php $db->p("perms") ?>
                  </td>
                  <td align=right> <input type="hidden" name="u_id"   value="<?php $db->p("user_id") ?>">
                    <input type="submit" name="u_edit" value="Change"> </td>
                  <?php else: ?>
                  <!--  <td><?php $db->p($table."username") ?>
                  <td>**********</td>
                  <td>
                    <?php $db->p($table."perms") ?>
                  </td>
                  <td align=right>&nbsp;</td>
                  -->
                  <?php
 endif;
 ?>
                </tr>
              </form>
              <?php
}


?>
<table border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td><font class="bigTextBold">Change Password</font> <br>
      <br>
      <table border="0" class=btable cellspacing="0" cellpadding="4">
        <tr> 
          <td><table width=540 border=0 align="center" cellpadding=4 cellspacing=0 bordercolor="#D2D2E5" bgcolor="#FFFFFF">
              <tr valign=top align=left> 
                <th>Username</th>
                <th>Password</th>
                <th>Level</th>
                <th align=right>Action</th>
              </tr>
              <?php 

  if ($perm->have_perm("admin")) {

 ?>
              <!-- create a new user -->
              <form method="post" action="<?php $sess->pself_url() ?>">
                <tr valign=middle align=left> 
                  <td><input type="text" name="username" size=12 maxlength=32 value=""></td>
                  <td><input type="text" name="password" size=12 maxlength=32 value=""></td>
                  <td><?php print $perm->perm_sel("perms","user");?></td>
                  <td align=center><input type="submit" name="create" value="Create User"></td>
                </tr>
              </form>
          <?php

	  if (!$letter) $letter="0";

	  $QUERY_STRING="";
	  $db->query("select a.* from auth_user a where username like '".$letter."%'order by username");
	  echo "<tr><td align=center colspan=4><table><tr>\n";
	  for ($l = 97 ; $l<=122; $l++)
		echo "<td><a href=".$sess->self_url().$sess->add_query(array("letter"=>chr($l))).">".chr($l)."</a></td>\n";
		echo "<td><a href=".$sess->self_url().$sess->add_query(array("letter"=>"%")).">".all."</a></td>\n";
  	  echo "</tr></table></td></tr>\n";
   } else 
	$db->query("select a.* from auth_user a where username='".strtolower($auth->auth["uname"])."'"); 
	## Traverse the result set
  	while ($db->next_record()):
		showPassRecord($db);
  	endwhile;
	echo "\n<tr><td colspan=4>&nbsp;</td></tr>\n";
?>
            </table></td>
        </tr>
      </table></td>
  </tr>
</table>
<?php
}
page_close();
?>

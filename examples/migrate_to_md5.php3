<? 
# Script to migrate from regular authentication to md5-hashed passwords
#
# Jim Zajkowski <jim@jimz.com>
#
# This only works for MySQL, since I don't have any other database.  
# You'll need to change it as necessary.

# Before running this script you must create the auth_user_md5 table.  
# Check out create_auth_md5.mysql, which is the proper SQL to create
# the table.

# Please set up DB_Example (or a subclass) in the php/local.inc file
# first, as I depend on it.  Configure for your site:

$database_class  = "DB_Example";       ## Class for login credentials
$from_table      = "auth_user";        ## Table with current users
$to_table        = "auth_user_md5";    ## New table to make
$clear_to_first  = 1;                  ## Delete auth table first

#####################################################################

$db = new $database_class;
$db2 = new $database_class;

if ($clear_to_first) {
   $db->query(sprintf("delete from %s", $to_table));
}

echo "Converting $from_table to $to_table...<br>";

$db->query(sprintf("select user_id, username, perms, password from %s",
                     $from_table)
          );

while($db->next_record()) {
   $uid  = $db->f("user_id");
   $user = $db->f("username");
   $perm = $db->f("perms");
   $pass = $db->f("password");
   
   $newpass = md5($pass);

   $db2->query(sprintf("insert into %s (user_id, username, perms, password)" .
		         "values ('%s', '%s', '%s', '%s')", 
	                 $to_table, $uid, $user, $perm, $newpass));

   echo "Converted $user...<br>";
}

echo "Conversion complete.<br>";

?>


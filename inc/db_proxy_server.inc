<?php

/* 
 *  Provides a proxy server for a database driver.
 *  useful when the linux application needs to access a windows ODBC driver
 *  or similar situation, where the database cannot be accessed directly
 *  from the main application host computer.
 *  To be used with db_proxy_client.inc
 */

#
# include the relevant database driver that matches your database
#
include("db_odbc.inc");

class DB_proxy extends DB_Sql {

}

$db = new DB_proxy;
$db->User = $_REQUEST["user"];		# Database Credentials.
$db->Password = $_REQUEST["pass"];
$db->Database = "pronto";		# Database Name

$db->query(stripslashes($_REQUEST["sql"]));
while ($db->next_record()) {
	$data[] = $db->Record;
}

echo serialize($data);

?>

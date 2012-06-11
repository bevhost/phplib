<?php
/*
What is this: Your MySQL database tables used character set latin1. But utf8 characters had somehow gone
              into the textual columns. You need to change the table character set to utf8. You need to
              transform the utf8 characters in latin1 columns correctly to utf8 characters in utf8 columns.

How to use this:
1. Rename the file name extension of this file to include .php (latin1-to-utf8.php).
2. Specify your database access details and table names (see below)
3. Take a backup of your database first!
4. Access this php file though a web browser (http://localhost/latin1-to-utf8.php) after placing the file in a web server. Or else, type "php latin1-to-utf8.php" - without quotes - from a terminal.


Log:
1. 2009/09/12 by Kamal Wickramanayake at Software View (kamal at swview.org)
Prepared the mysql hack from Drupal specific script found at http://www.computerminds.co.uk/drupal-mysql-utf8-and-latin1-character-set-issues


*/


/**************** Edit the following according to your database settings ******************/

$db_server = 'localhost';
$db_user="db_user";
$db_password="password";
$db_name = "db1";

// Specify the tables to be converted
$table_names = array();



/**************** You don't have to modify the below code **********************************/

mysql_connect($db_server, $db_user, $db_password) or die(mysql_error());
mysql_select_db($db_name);

$result = mysql_query("SHOW TABLES");
while ($column = mysql_fetch_assoc($result)) {
        $col = "Tables_in_$db_name";
        $table_names[] = $column[$col];
}


charset_fixer($table_names);


function charset_fixer($table_names){
  foreach($table_names as $type){
    $ret[] = charset_fixer_fix_table($type);
  }
}

function charset_fixer_fix_table($table) {
  $ret = array();
  $types = array('char' => 'binary',
                 'varchar' => 'varbinary',
                 'tinytext' => 'tinyblob',
                 'text' => 'blob',
                 'mediumtext' => 'mediumblob',
                 'longtext' => 'longblob');

  // Get next table in list
  $convert_to_binary = array();
  $convert_to_latin1 = array();
  $convert_to_utf8 = array();

  // Find out which columns need converting and build SQL statements
  $result = mysql_query('SHOW FULL COLUMNS FROM `'. $table .'`');
  while ($column = mysql_fetch_assoc($result)) {
    list($type) = explode('(', $column['Type']);
    if (isset($types[$type]) and $column['Collation']=='latin1_swedish_ci') {
      $names = "\nCHANGE `". $column['Field'] .'` `'. $column['Field'] .'` ';
      $attributes = ' DEFAULT '. ($column['Default'] == 'NULL' ? 'NULL ' :
                     "'". mysql_real_escape_string($column['Default']) ."' ") .
                    ($column['Null'] == 'YES' ? 'NULL' : 'NOT NULL');
      $convert_to_binary[] = $names . preg_replace('/'. $type .'/i', $types[$type], $column['Type']) . $attributes;
      $convert_to_latin1[] = $names . $column['Type'] .' CHARACTER SET latin1'. $attributes;
      $convert_to_utf8[] = $names . $column['Type'] .' CHARACTER SET utf8'. $attributes;
    }
  }


  if (count($convert_to_binary)) {
    //set the table default to latin1
 #   mysql_query('ALTER TABLE '. $table .' DEFAULT CHARACTER SET latin1');

    //Convert to latin1
  #  mysql_query('ALTER TABLE '. $table .' '. implode(', ', $convert_to_latin1));

    //set the table default to utf8
   # mysql_query('ALTER TABLE '. $table .' DEFAULT CHARACTER SET utf8');
        $sql = 'ALTER TABLE `'. $table .'` DEFAULT CHARACTER SET utf8';
        #mysql_query($sql);
        echo "$sql;\n";

    //Convert latin1 columns to binary
 #   mysql_query('ALTER TABLE '. $table .' '. implode(', ', $convert_to_binary));

    // Convert binary columns to UTF-8
  #  mysql_query('ALTER TABLE '. $table .' '. implode(', ', $convert_to_utf8));
        $sql = 'ALTER TABLE `'. $table .'` '. implode(', ', $convert_to_utf8);
        #mysql_query($sql);
        echo "$sql;\n";
  }
}

?>


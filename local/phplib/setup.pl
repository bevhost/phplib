#!/usr/bin/perl
strict;
print "Content-type:text/html\n\n";
print "<html><head><title>PHPLIB Setup</title></head>\n";
print "<body>\n";
print "\nTo be called by autogen.php\n\n";

use CGI qw(:cgi-lib);

$ret = &ReadParse(*data);
&CgiDie("Error in reading and parsing of CGI input") if !defined $ret;

if ($ENV{'HTTP_USER_AGENT'}!="") { print "Access Denied"; die "Local use only"; }

my $bn = $data{BN};
my $hd = $data{HD};
my $sd = $data{SD};
my $db = $data{DB};
my $usr = $data{USR};
my $pwd = $data{PWD};

if ($bn=="" or $hd="" or $db="" or $usr="" or $pwd="") { print "Missing Parameters"; die "Arg Err"; }

my $nl = "\n";
open(FILE, ">$hd/$bn/public_html/phplib/.htauth.local") || die "Can't open outfile";
print FILE '<?php'.$nl;
print FILE '$_ENV["HomeDirs"] = "'.$hd.'";'.$nl;
print FILE '$_ENV["BaseName"] = "'.$bn.'";'.$nl;
print FILE '$_ENV["SubFolder"] = "'.$sd.'";'.$nl;
print FILE '$_ENV["DatabaseClass"] = "DB_".$_ENV["BaseName"];'.$nl;
print FILE '$_ENV["SessionClass"] = $_ENV["BaseName"]."_Session";'.$nl;
print FILE '$_ENV["AuthClass"] = $_ENV["BaseName"]."_Auth";'.$nl;
print FILE '$_ENV["PermClass"] = $_ENV["BaseName"]."_Perm";'.$nl;
print FILE '$_ENV["Perms"] = "guest,user,editor,admin";'.$nl;
print FILE '$_ENV["MenuMode"] = "vert";   /*horiz/vert*/'.$nl;
print FILE '$_ENV["no_edit"] = array("radacct","pp_transactions","EventLog");'.$nl;
print FILE 'class DB_'.$bn.' extends DB_Sql {'.$nl;
print FILE '  var $Host     = "localhost";'.$nl;
print FILE '  var $Database = "'.$db.'";'.$nl;
print FILE '  var $User     = "'.$usr.'";'.$nl;
print FILE '  var $Password = "'.$pwd.'";'.$nl;
print FILE '}'.$nl;
print FILE '?>'.$nl;
close FILE;
mkdir("$hd$bn/autogen",0777);
mkdir("$hd$bn/templates",0777);
mkdir("$hd$bn/templates/old",0777);
print "Setup complete from $from";




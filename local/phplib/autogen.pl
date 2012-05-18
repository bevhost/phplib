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

system('php','autogen.php');


1;

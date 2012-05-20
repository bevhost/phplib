#! /usr/bin/env python
## apconfig - A simple installation menu
## This program is free software; you can redistribute it and/or modify
## it under the terms of the GNU General Public License as published by
## the Free Software Foundation; either version 2 of the License, or
## (at your option) any later version.

## This program is distributed in the hope that it will be useful,
## but WITHOUT ANY WARRANTY; without even the implied warranty of
## MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
## GNU General Public License for more details.

## You should have received a copy of the GNU General Public License
## along with this program; if not, write to the Free Software
## Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

PROGNAME = 'setup'
TITLE = 'PHPlib Web Setup'
COPYRIGHT = 'Copyright (C) 2002-2006 Red Hat, Inc.'
AUTHORS = ['David Beveridge <dave@bevhost.com>']
VERSION = '1.0'
GLADEPATH = ''
TRUE = (1==1)
FALSE = not TRUE

import re
import traceback
import sys
import signal
import os
import os.path
import csv
import shutil
import string
import gettext
from snack import *

MYSQL_PASSWD = ''
## check the secret squirrel place for the mysql root password
reader = csv.reader(open("/etc/ppp/pap-secrets", "rb"), delimiter='\t', quoting=csv.QUOTE_NONE)
for row in reader:
	if row[0]=="accessplus":
		MYSQL_PASSWD = row[2]

TEXT = 'The PHPlib Web Setup Tool is for creating new virtual web sites on this web server with MySQL and PHPlib.\n\n'

if not "/usr/lib/rhs/python" in sys.path:
    sys.path.append("/usr/lib/rhs/python")

##
## I18N
##
gettext.bindtextdomain(PROGNAME, '/usr/share/locale')
gettext.textdomain(PROGNAME)
_ = gettext.gettext


class mainDialog:
    def __init__(self):
        pass

    def main(self):
        self.hydrate()
        
    def hydrate(self):


	source = os.getcwd()
	webroot = '/var/www/'
	button = 'start'
	usernm = ''
	passwd = ''
	domain = ''
	mysql  = 'yes'
	phplib = 'yes'
	menu   = 'horiz'
	perms  = 'guest,user,editor,admin'
	subfolder = ''
	vhostsdir = '/etc/httpd/conf/vhosts/'
	backupdir = '/var/spool/backup/mysql/'

	if not os.path.exists('/var/run/mysqld'):
		mysql  = 'no'
		dlg = mainDialog.infoDialog(self,"MySQL Server is NOT installed.")
	else:
		if not os.path.exists('/var/run/mysqld/mysqld.pid'):
			mysql  = 'no'
			dlg = mainDialog.infoDialog(self,"MySQL Server is NOT running.")

    	while button != 'cancel':
		count = 0;
        	screen = SnackScreen()
        	t = TextboxReflowed(65, TEXT)
		sg = Grid(2,7)
		el = []
		e = Entry(12,usernm)
		sg.setField(Label('User Name'), 0, 0, padding = (0, 0, 1, 1), anchorLeft = 1)
		sg.setField(e, 1, 0, padding = (0, 0, 1, 1),anchorLeft = 1)
		el.append(e)

		e = Entry(12,passwd)
		sg.setField(Label('Password'), 0, 1, padding = (0, 0, 1, 1), anchorLeft = 1)
		sg.setField(e, 1, 1, padding = (0, 0, 1, 1),anchorLeft = 1)
		el.append(e)

		e = Entry(40,domain)
		sg.setField(Label('Domain Name  www.'), 0, 2, padding = (0, 0, 1, 1), anchorLeft = 1)
		sg.setField(e, 1, 2, padding = (0, 0, 1, 1), anchorLeft = 1)
		el.append(e)

		e = Entry(40,subfolder)
		sg.setField(Label('Subfolder (optional)'), 0, 3, padding = (0, 0, 1, 1), anchorLeft = 1)
		sg.setField(e, 1, 3, padding = (0, 0, 1, 1), anchorLeft = 1)
		el.append(e)

		myrootpass = MYSQL_PASSWD
		e = Entry(40,myrootpass,0,1)
		sg.setField(Label('MySQL Root Password'), 0, 4, padding = (0, 0, 1, 1), anchorLeft = 1)
		sg.setField(e, 1, 4, padding = (0, 0, 1, 1), anchorLeft = 1)
		el.append(e)

		e = Entry(40,perms)
		sg.setField(Label('Permission Levels'), 0, 5, padding = (0, 0, 1, 1), anchorLeft = 1)
		sg.setField(e, 1, 5, padding = (0, 0, 1, 1), anchorLeft = 1)
		el.append(e)

		if menu == 'horiz':
        		rb1 = RadioBar(screen, (('Horizontal', 'horiz', 1), ('Vertical', 'vert', 0)))
		else:
        		rb1 = RadioBar(screen, (('Horizontal', 'horiz', 0), ('Vertical', 'vert', 1)))
		sg.setField(Label('Menu Layout'), 0, 6, padding = (0, 0, 1, 1), anchorLeft = 1)
		sg.setField(rb1, 1, 6, padding = (0, 0, 1, 1), anchorLeft = 1)

        	# create button bar    
        	bb = ButtonBar(screen, (('Ok', 'ok'), ('Cancel', 'cancel')))

        	g = GridForm(screen, TITLE, 1, 3)
        	g.add(t, 0, 0)
        	g.add(sg, 0, 1)
        	g.add(bb, 0, 2, growx = 1)

        	result = g.runOnce()
        	screen.finish()

		button = bb.buttonPressed(result)
        	if button != 'cancel':
			INFO_TEXT = "";
			usernm = el[0].value()
			passwd = el[1].value()
			domain = el[2].value()
			subfolder = el[3].value()
			myrootpass = el[4].value()
			perms = el[5].value()
			menu  = rb1.getSelection()

			if not os.path.exists(vhostsdir):
				vhostsdir = '/etc/httpd/conf.d/'
 
			if os.path.exists(webroot+usernm):
				INFO_TEXT = "Directory already exists ("+webroot+usernm+")"
			if os.path.exists(vhostsdir+usernm+".conf"):
				INFO_TEXT = "Virtual Host already exists ("+vhostsdir+usernm+".conf)"
			reader = csv.reader(open("/etc/passwd", "rb"), delimiter=":", quoting=csv.QUOTE_NONE)
			for row in reader:
				if row[0] == usernm:
					INFO_TEXT = "Username already exists (/etc/passwd)"

			if INFO_TEXT:
				dlg = mainDialog.infoDialog(self,INFO_TEXT)
			else:
				os.system("mkdir "+webroot+usernm+"")
				os.system("mkdir "+webroot+usernm+"/public_html")
				os.system("mkdir "+webroot+usernm+"/cgi-bin")
				os.system("cp "+webroot+"cgi-bin/cgiemail "+webroot+usernm+"/cgi-bin/")
				os.system("cp "+webroot+"cgi-bin/cgiecho "+webroot+usernm+"/cgi-bin/")
				os.system("mkdir "+webroot+usernm+"/etc")
				os.system("useradd -d "+webroot+usernm+" "+usernm)
				os.system("chown "+usernm+"."+usernm+" "+webroot+usernm)
				os.system("chmod 711 "+webroot+usernm)
				os.chdir(webroot+usernm)
				os.system("tar zxvf ../ssl.tgz")
				os.system("chown "+usernm+"."+usernm+" public_html cgi-bin")
				os.chdir("etc")
				os.system("grep ^"+usernm+": /etc/passwd >> passwd")
				os.system("grep ^"+usernm+": /etc/group >> group")
				hostname = os.environ["HOSTNAME"]
				fd = open(vhostsdir+usernm+".conf","w")
				fd.write("<VirtualHost "+hostname+":80>\n")
				fd.write("    ServerAdmin webmaster@"+domain+"\n")
				fd.write("    DocumentRoot "+webroot+usernm+"/public_html\n")
				fd.write("    ServerName "+domain+"\n")
				fd.write("    ServerAlias www."+domain+"\n")
				fd.write("    ScriptAlias /cgi-bin/ "+webroot+usernm+"/cgi-bin/\n")
				fd.write("      <Directory "+webroot+usernm+"/cgi-bin>\n")
				fd.write("        AllowOverride None\n")
				fd.write("      </Directory>\n")
				fd.write("      <Location /cgi-bin>\n")
				fd.write("        Options ExecCGI\n")
				fd.write("        Order allow,deny\n")
				fd.write("        Allow from all\n")
				fd.write("      </Location>\n")
				fd.write("      <Directory "+webroot+usernm+"/public_html>\n")
				fd.write("        AllowOverride All\n")
				fd.write("      </Directory>\n")
				fd.write("    Alias /fckeditor /usr/share/phplib/fckeditor/\n")
				fd.write("    Alias /ckeditor /usr/share/phplib/ckeditor/\n")
				fd.write("    Alias /ckfinder /usr/share/phplib/ckfinder/\n")
				if os.path.exists(backupdir):
					fd.write("    Alias /backup /var/spool/backup/mysql/"+usernm+"/\n")
					fd.write("      <Directory /var/spool/backup/mysql/"+usernm+"/>\n")
					fd.write("        AllowOverride AuthConfig\n")
					fd.write("      </Directory>\n")
					fd.write("      <Location /backup>\n")
					fd.write("        IndexOptions FancyIndexing\n")
					fd.write("        Order allow,deny\n")
					fd.write("        Allow from all\n")
					fd.write("      </Location>\n")
				fd.write("    ErrorLog logs/"+domain+"-error_log\n")
				fd.write("    CustomLog logs/"+domain+"-access_log common\n")
				fd.write("</VirtualHost>\n")
				fd.close()
				if mysql == "yes":
					os.system("echo \"INSERT INTO user (Host, User, Password, Select_priv, Insert_priv, Update_priv, Delete_priv, Create_priv, Drop_priv, Reload_priv, Shutdown_priv, Process_priv, File_priv, Grant_priv, References_priv, Index_priv, Alter_priv) VALUES ('localhost', '"+usernm+"',PASSWORD('"+passwd+"'), 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N'); INSERT INTO db (Host, Db, User, Select_priv, Insert_priv, Update_priv, Delete_priv, Create_priv, Drop_priv, Grant_priv, References_priv, Index_priv, Alter_priv) VALUES ('localhost', '"+usernm+"', '"+usernm+"', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', '', '', '', ''); Flush Privileges;\" | mysql -u root -p"+myrootpass+" mysql")
					os.system("mysqladmin -u "+usernm+" -p"+passwd+" create "+usernm)
					if os.path.exists(backupdir):
						os.system("mkdir /var/spool/backup/mysql/"+usernm)
						fd = open("/var/spool/backup/mysql/"+usernm+"/.htaccess","w")
						fd.write("AuthType Basic\n")
						fd.write('AuthName "MySQL Server Backups"\n')
						fd.write("AuthUserFile "+webroot+usernm+"/htusers\n")
						fd.write("Require valid-user\n")
						fd.close()
					os.system("htpasswd -c -b "+webroot+usernm+"/htusers "+usernm+" "+passwd)
					if phplib == "yes":
						os.system("mkdir "+webroot+usernm+"/public_html/image")
						os.system("mkdir -p "+webroot+usernm+"/public_html/phplib/templates/old")
						os.system("cp -ir /usr/share/phplib/local/* "+webroot+usernm+"/public_html/")
						os.system("mkdir "+webroot+usernm+"/public_html/phplib/autogen")
						os.system("chmod 770 "+webroot+usernm+"/public_html/phplib/autogen")
						os.system("chmod -R 770 "+webroot+usernm+"/public_html/phplib/templates")
						os.system("chgrp apache "+webroot+usernm+"/public_html/phplib/autogen")
						os.system("chgrp -R apache "+webroot+usernm+"/public_html/phplib/templates")
						os.system("touch "+webroot+usernm+"/public_html/head.ihtml")
						os.system("touch "+webroot+usernm+"/public_html/foot.ihtml")
						if not os.path.exists(""+webroot+usernm+"/public_html/phplib/.htauth.local"):
							os.system("cat /usr/share/phplib/phplib.mysql | mysql -u root -p"+myrootpass+" "+usernm)
							os.system("echo \"INSERT INTO auth_user VALUES ('c14cbf141ab1b7cd009356f555b607dc','"+usernm+"','"+passwd+"','admin');\" | mysql -u root -p"+myrootpass+" "+usernm)
							fw = open(webroot+usernm+"/public_html/phplib/.htauth.local","w")
							fw.write('<?php\n')
							fw.write('$_ENV["HomeDirs"] = "'+webroot+'";\n')
							fw.write('$_ENV["SubFolder"] = "'+subfolder+'";\n')
							fw.write('$_ENV["BaseName"] = "'+usernm+'";\n')
							fw.write('$_ENV["Domain"] = "'+domain+'";\n')
							fw.write('$_ENV["DocRoot"] = "'+webroot+usernm+'/public_html";\n')
							fw.write('$_ENV["DatabaseClass"] = "DB_".$_ENV["BaseName"];\n')
							fw.write('$_ENV["SessionClass"] = $_ENV["BaseName"]."_Session";\n')
							fw.write('$_ENV["AuthClass"] = $_ENV["BaseName"]."_Auth";\n')
							fw.write('$_ENV["PermClass"] = $_ENV["BaseName"]."_Perm";\n')
							fw.write('$_ENV["Perms"] = "'+perms+'";\n')
							fw.write('$_ENV["LocalCurrency"] = "AUD";\n')
							fw.write('$_ENV["MenuMode"] = "'+menu+'";   /*horiz/vert*/\n')
							fw.write('$_ENV["RegisterMode"] = "Approve";  /* Auto, Approve or Email, see register.php */\n')
							fw.write('$_ENV["UserDetailsTable"] = "AddressBook";\n')
							fw.write('$_ENV["UserEmailAddressField"] = "Email";\n')
							fw.write('$_ENV["UserAuthIdField"] = "user_id";\n')
							fw.write('$_ENV["no_edit"] = array("radacct","pp_transactions","EventLog");\n')
							fw.write('$_ENV["editor"] = "fckeditor";   /* ckfinder, ckeditor, fckeditor (see of_htmlarea.inc) */\n');
							fw.write('\n')
							fw.write('class DB_'+usernm+' extends DB_Sql {\n')
							fw.write('  var $Host     = "localhost";\n')
							fw.write('  var $Database = "'+usernm+'";\n')
							fw.write('  var $User     = "'+usernm+'";\n')
							fw.write('  var $Password = "'+passwd+'";\n')
							fw.write('}\n')
							fw.write('?>\n')
						if not os.path.exists(webroot+usernm+"/public_html/phplib/.htaccess"):
							fd = open(webroot+usernm+"/public_html/phplib/.htaccess","w")
							fd.write("AuthType Basic\n")
							fd.write('AuthName "PHP Library for '+domain+'"\n')
							fd.write("AuthUserFile "+webroot+usernm+"/htusers\n")
							fd.write("Require valid-user\n")
							fd.close()
						if not os.path.exists(webroot+usernm+"/public_html/.htaccess"):
							fd = open(webroot+usernm+"/public_html/.htaccess","w")
							fd.write("IndexIgnore .htaccess */.??* *~ *# */HEADER* */README* */_vti*\n\n")
							fd.write("<Limit GET POST>\n")
							fd.write("order deny,allow\n")
							fd.write("deny from all\n")
							fd.write("allow from all\n")
							fd.write("</Limit>\n")
							fd.write("<Limit PUT DELETE>\n")
							fd.write("order deny,allow\n")
							fd.write("deny from all\n")
							fd.write("</Limit>\n")
							fd.write('AuthName "'+domain+'"\n\n')
							fd.write("<IfModule mod_rewrite.c>\n")
							fd.write("RewriteEngine On\n")
							fd.write("ErrorDocument 404 /error-404.php\n")
							fd.write("ErrorDocument 403 /error-403.php\n")
							fd.write("RewriteRule ^(.*).html$ template.php?page=$1 [L,NC]\n")
							fd.write("RewriteRule ^logout$ logout.php [L,NC]\n")
							fd.write("RewriteRule ^contact$ contact.php [L,NC]\n")
							fd.write("RewriteRule ^password$ password.php [L,NC]\n")
							fd.write("</IfModule>\n")
							fd.close()
						os.system("chown -R "+usernm+" "+webroot+usernm+"/public_html")



				button = 'cancel'
				os.system('echo Web Setup Complete - Press ENTER\necho Make your DNS host record for this web site and restart apache')
				os.system('read CMD')


    def warningDialog(self):
        screen = SnackScreen()
        b =  Button(_('Ok'))
        t = TextboxReflowed(40, WARNING_TEXT+'\n\n')
        g = GridFormHelp(screen, _('Warning'), None, 1, 2)
        g.add(t, 0, 0)
        g.add(b, 0, 1)
    
        result = g.runOnce()
        screen.finish()

    def infoDialog(self,text):
        screen = SnackScreen()
        b =  Button(_('OK'))
        t = TextboxReflowed(45, text+'\n\n')
        g = GridFormHelp(screen, _('Information'), None, 1, 2)
        g.add(t, 0, 0)
        g.add(b, 0, 1)
    
        result = g.runOnce()
        screen.finish()


# make ctrl-C work
if __name__ == '__main__':
    signal.signal (signal.SIGINT, signal.SIG_DFL)
    dlg = mainDialog().main()

    sys.exit(0)

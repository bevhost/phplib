<?php
/*
#
# The actual POST interface for dealing with incoming PayPal IPNs
#
# $Id: ipn.php,v 1.1.1.1 2004/03/28 16:45:11 nemesis Exp $
#
# Copyright (C) 2004 Kees Cook
# kees@outflux.net, http://outflux.net/
# 
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
# http://www.gnu.org/copyleft/gpl.html
#
*/

// By default, be in error mode (to handle uncatchable runtime errors, etc)
header("HTTP/1.0 503 Syntax error");

include("phplib/prepend.php");
require($_ENV["libdir"]."paypal.inc");

$ipn = new IPN_Agent();
#$ipn->ignore_cert = true;
#$ipn->ca_file = "/etc/ssl/certs/ca-certificates.crt";  #debian
$ipn->ca_file = "/etc/pki/tls/certs/ca-bundle.crt";    #redhat

$ipn->tablename_prefix = "pp_";

if (!$ipn->init()) {
    header("HTTP/1.0 500 Script error");
    trigger_error("Could not initialize IPN Agent",E_USER_ERROR);
}

if (!$ipn->handle_ipn($_POST)) {
    header("HTTP/1.0 500 Script error");
    trigger_error("IPN verification failed",E_USER_ERROR);
}

header("HTTP/1.0 200 OK");
echo "Thanks!\n";

/* vi:set ai ts=4 sw=4 expandtab: */ ?>

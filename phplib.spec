Summary: php library
Name: phplib
Version: 1.0
Release: 9
License: GNU Library or Lesser General Public License (LGPL)
BuildArch: noarch
BuildRoot: %{_tmppath}/%{name}-root
Requires: php-mcrypt
Group: System Environment/Daemons
Source: %{name}-%{version}.tar.gz
Packager: David Beveridge <david@beveridge.id.au>

%description
A library that provides session managent, authentication, permission
control, tables, forms, form elements, templating and sql query builder.
Compatible with a wide range of databases including MySQL, ODBC, Oracle,
Postgres, MSSQL, Sybase, SQLite etc.

%prep
%setup -q

%build

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT/usr/share/phplib/local
mkdir -p $RPM_BUILD_ROOT/usr/share/phplib/examples
install -m 750 setup $RPM_BUILD_ROOT/usr/share/phplib/
install -m 644 setup.py $RPM_BUILD_ROOT/usr/share/phplib/
install -m 644 README $RPM_BUILD_ROOT/usr/share/phplib/
install -m 644 inc/* $RPM_BUILD_ROOT/usr/share/phplib/
install -m 644 examples/* $RPM_BUILD_ROOT/usr/share/phplib/examples/
cp -a local $RPM_BUILD_ROOT/usr/share/phplib/

%post

%clean
rm -rf $RPM_BUILD_ROOT

%files
/usr/share/phplib/*

%changelog
* Mon Apr 28 2014 marado
- Added php-mcrypt dependency

* Sat Jul 07 2012 dave
- version 0.8

* Fri May 18 2012 dave
- version 0.7

* Mon Jun 28 2010 dave
- version 0.6

* Wed Feb 6 2010 dave
- version 0.5

* Sat Jan 16 2010 David Beveridge <david@beveridge.id.au>
- version 0.4

* Mon Jun 01 2009 David Beveridge <david@beveridge.id.au>
- initial build


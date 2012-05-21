Summary: php library
Name: phplib-editors
Version: 1.0
Release: 1
License: GNU Library or Lesser General Public License (LGPL)
BuildArch: noarch
BuildRoot: %{_tmppath}/%{name}-root
Group: System Environment/Daemons
Source: %{name}-%{version}.tar.gz
Packager: David Beveridge <david@beveridge.id.au>

%description
fckeditor, ckeditor and ckfinder packaged to work with phplib.

%prep
%setup -q

%build

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT/usr/share/phplib
mv fckeditor $RPM_BUILD_ROOT/usr/share/phplib/
mv ckeditor $RPM_BUILD_ROOT/usr/share/phplib/
mv ckfinder $RPM_BUILD_ROOT/usr/share/phplib/

%post

%clean
rm -rf $RPM_BUILD_ROOT

%files
/usr/share/phplib/*

%changelog
* Fri May 21 2012 dave
- version 0.1
- initial build


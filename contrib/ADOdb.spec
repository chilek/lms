# $Revision$, $Date$
# $Id$
Summary:	ADOdb Library for PHP
Summary(pl):	Biblioteka ADOdb dla PHP
Name:		ADOdb
Version:	3.20
Release:	1
License:	LGPL
Group:		Development/Languages/PHP
Source0:	http://phplens.com/lens/dl/adodb320.tgz
Requires:	php
Requires:	php-pear
BuildArch:	noarch
BuildRoot:	%{tmpdir}/%{name}-%{version}-root-%(id -u -n)
Vendor:		Rulez.PL

%description
PHP's database access functions are not standardised. This creates a
need for a database class library to hide the differences between the
different database API's (encapsulate the differences) so we can
easily switch databases. PHP 4.0.5 or later is now required (because
we use array-based str_replace).

%prep
%setup -q -a 0 -n adodb

%install
rm -rf $RPM_BUILD_ROOT
install -d $RPM_BUILD_ROOT%{_datadir}/pear/adodb
install -d $RPM_BUILD_ROOT%{_datadir}/pear/adodb/drivers

install *.inc.php $RPM_BUILD_ROOT%{_datadir}/pear/adodb
install drivers/*.inc.php $RPM_BUILD_ROOT%{_datadir}/pear/adodb/drivers/

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(644,root,root,755)
%doc tests {license,readme}.txt {old-changelog,readme,tips_portable_sql,tute}.htm server.php cute_icons_for_site
%dir %{_datadir}/pear/adodb/
%dir %{_datadir}/pear/adodb/drivers/
%{_datadir}/pear/adodb/*.inc.php
%{_datadir}/pear/adodb/drivers/*.inc.php

%define date	%(echo `LC_ALL="C" date +"%a %b %d %Y"`)
%changelog

# $Revision$, $Date$
# %define		_doc_version	2.4.0
Summary:	Template engine for PHP
Summary(pl):	System szablonów dla PHP
Name:		Smarty
Version:	2.5.0
Release:	1
License:	LGPL
Group:		Development/Languages/PHP
Source0:	http://smarty.php.net/distributions/%{name}-%{version}.tar.gz
Source1:	http://smarty.php.net/distributions/manual/en/%{name}-%{version}-docs.tar.gz
Requires:	php
Requires:	php-pear
BuildArch:	noarch
BuildRoot:	%{tmpdir}/%{name}-%{version}-root-%(id -u -n)

%description
Smarty is a template engine for PHP. Smarty provides your basic
variable substitution and dynamic block functionality, and also takes
a step further to be a "smart" template engine, adding features such
as configuration files, template functions, variable modifiers, and
making all of this functionality as easy as possible to use for both
programmers and template designers.

%description -l pl
Smarty jest systemem szablonów dla PHP. Pozwala na podstawowe
podstawianie warto¶ci zmiennych oraz dynamiczne operacje na blokach;
idzie o krok dalej, aby byæ "m±drym" silnikiem szablonów, dodaj±c
takie mo¿liwo¶ci jak pliki konfiguracyjne, funkcje, zmienne
modyfikatory oraz czyni±c ca³± funkcjonalno¶æ jak naj³atwiejsz± w
u¿yciu jednocze¶nie dla programistów i projektantów szablonów.

%package doc
Summary:	Template engine for PHP - documentation
Summary(pl):	System szablonów dla PHP - dokumentacja
Version:	%{_doc_version}
Group:		Development/Languages/PHP

%description doc
Documentation for Smarty template engine.

%description doc -l pl
Dokumentacja do systemu szablonów Smarty.

%prep
%setup -q -a 1

%install
rm -rf $RPM_BUILD_ROOT
install -d $RPM_BUILD_ROOT%{_datadir}/pear/%{name}/plugins

install libs/{Config_File,Smarty{,_Compiler}}.class.php $RPM_BUILD_ROOT%{_datadir}/pear/%{name}
install libs/debug.tpl $RPM_BUILD_ROOT%{_datadir}/pear/%{name}
install libs/plugins/*.php $RPM_BUILD_ROOT%{_datadir}/pear/%{name}/plugins

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(644,root,root,755)
%doc BUGS ChangeLog FAQ INSTALL NEWS README RELEASE_NOTES TODO
%dir %{_datadir}/pear/%{name}
%dir %{_datadir}/pear/%{name}/plugins
%{_datadir}/pear/%{name}/*.class.php
%{_datadir}/pear/%{name}/debug.tpl
%{_datadir}/pear/%{name}/plugins/*.php

%files doc
%defattr(644,root,root,755)
%doc manual/*

%define date	%(echo `LC_ALL="C" date +"%a %b %d %Y"`)
%changelog
* %{date} PLD Team <feedback@pld.org.pl>
All persons listed below can be reached at <cvs_login>@pld.org.pl

$Log$
Revision 1.3  2003/04/12 23:02:25  lukasz
- updates (Smarty-2.5.0)

Revision 1.6  2003/04/12 22:53:46  baseciq
- temporary removed _doc_version

Revision 1.5  2003/04/12 22:49:58  baseciq
- updated for Smarty 2.5.0

Revision 1.4  2003/03/31 12:31:44  qboosh
- removed COPYING.lib from doc (just LGPL)

Revision 1.3  2003/03/27 22:03:49  qboosh
- cleanups, little longer pl description

Revision 1.2  2003/03/17 21:13:33  adamg
- small typo fixed
- removed Vendor line - rulez.pl is not vendor for this package.

Revision 1.1  2003/02/22 00:00:36  baseciq
- new

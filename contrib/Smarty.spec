# $Revision$, $Date$
# $Id$
Summary:	Template engine for PHP
Summary(pl):	System template'owy dla PHP
Name:		Smarty
Version:	2.3.1
Release:	1
License:	LGPL
Group:		Development/Languages/PHP
Source0:	http://smarty.php.net/distributions/%{name}-%{version}.tar.gz
Source1:	http://smarty.php.net/distributions/manual/en/%{name}-%{_doc_version}-docs.tar.gz
Requires:	php
Requires:	php-pear
BuildArch:	noarch
BuildRoot:	%{tmpdir}/%{name}-%{version}-root-%(id -u -n)
Vendor:		Rulez.PL

%description
Smarty is a template engine for PHP. Smarty provides your basic
variable substitution and dynamic block functionality, and also takes
a step further to be a "smart" template engine, adding features such
as configuration files, template functions, variable modifiers, and
making all of this functionality as easy as possible to use for both
programmers and template designers.

%description -l pl
Smarty jest systemem template'owum dla PHP. Smarty pozwala na
podstawowe podstawianie warto¶ci zmiennych i na zaawansowane operacje
na tablicach.

%package -n Smarty-doc
Summary:	Template engine for PHP - documentation
Summary(pl):	System template'owy dla PHP - dokumentacja
Version:	%{_doc_version}
Group:		Development/Languages/PHP

%description -n Smarty-doc
Documentation for Smarty template engine.

%description -n Smarty-doc -l pl
Dokumentacja do systemu template'owego Smarty.

%prep
%setup -q -a 0
%setup -q -a 1

%install
rm -rf $RPM_BUILD_ROOT
install -d $RPM_BUILD_ROOT%{_datadir}/pear/%{name}
install -d $RPM_BUILD_ROOT%{_datadir}/pear/%{name}/plugins


install {Config_File,Smarty{,_Compiler}}.class.php $RPM_BUILD_ROOT%{_datadir}/pear/%{name}
install debug.tpl $RPM_BUILD_ROOT%{_datadir}/pear/%{name}
install plugins/*.php $RPM_BUILD_ROOT%{_datadir}/pear/%{name}/plugins/

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(644,root,root,755)
%doc AUTHORS BUGS COPYING.lib CREDITS ChangeLog FAQ INSTALL NEWS QUICKSTART README RELEASE_NOTES RESOURCES TESTIMONIALS TODO
%dir %{_datadir}/pear/%{name}
%dir %{_datadir}/pear/%{name}/plugins
%{_datadir}/pear/Smarty/*.class.php
%{_datadir}/pear/%{name}/debug.tpl
%{_datadir}/pear/%{name}/plugins/*.php

%files -n Smarty-doc
%defattr(644,root,root,755)
%doc manual/*

%define date	%(echo `LC_ALL="C" date +"%a %b %d %Y"`)
%changelog

# $Revision$, $Date$
%include	/usr/lib/rpm/macros.php
Summary:	Unique interface to access different SQL databases
Summary(pl):	Jednolity inferfejs dostêpu do baz danych SQL
Name:		adodb
Version:	3.40
Release:	1
Group:		Libraries
License:	dual licensed using BSD-Style and LGPL
Source0:	http://phplens.com/lens/dl/%{name}%(echo %{version} | sed -e 's#\.##').tgz
URL:		http://php.weblogs.com/ADOdb
Requires:	php
Requires:	php-pear
BuildRequires:	rpm-php-pearprov
BuildArch:	noarch
BuildRoot:	%{tmpdir}/%{name}-%{version}-root-%(id -u -n)

%description
PHP's database access functions are not standardised. This creates a
need for a database class library to hide the differences between the
different databases (encapsulate the differences) so we can easily
switch databases.

Is currently support MySQL, Interbase, Oracle, Microsoft SQL Server,
Sybase, PostgreSQL, Foxpro, Access, ADO and ODBC.

%description -l pl
Funkcje dostêpu do baz danych w PHP nie s± ustandaryzowane. To
powoduje i¿ potrzebna jest biblioteka dostarczaj±ca jednolite funkcje
ukrywaj±ca ró¿nice pomiêdzy ró¿nymi bazami dziêki czemu ³atwo mo¿na
zmieniaæ bazy.

Aktualnie wspiera MySQL, Interbase, Oracle, Microsoft SQL Server,
Sybase, PostgreSQL, Foxpro, Access, ADO i ODBC.

%prep
%setup  -q -n %{name}

%install
rm -rf $RPM_BUILD_ROOT
install -d $RPM_BUILD_ROOT%{php_pear_dir}/%{name}/drivers 
install -d $RPM_BUILD_ROOT%{php_pear_dir}/%{name}/datadict
install -d $RPM_BUILD_ROOT%{php_pear_dir}/%{name}/tests

install *.php      $RPM_BUILD_ROOT%{php_pear_dir}/%{name}
install drivers/*  $RPM_BUILD_ROOT%{php_pear_dir}/%{name}/drivers
install datadict/* $RPM_BUILD_ROOT%{php_pear_dir}/%{name}/datadict
install tests/*    $RPM_BUILD_ROOT%{php_pear_dir}/%{name}/tests

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(644,root,root,755)
%doc license.txt readme.txt
%doc old-changelog.htm readme.htm tips_portable_sql.htm tute.htm
%doc cute_icons_for_site
%{php_pear_dir}/%{name}

%define date	%(echo `LC_ALL="C" date +"%a %b %d %Y"`)
%changelog
* %{date} PLD Team <feedback@pld.org.pl>
All persons listed below can be reached at <cvs_login>@pld.org.pl

$Log$
Revision 1.5.2.4  2003/08/13 00:22:57  lukasz
- force commit for LMS-1.0.2

Revision 1.5.2.3  2003/06/21 10:19:24  lukasz
- bump up rel to release

Revision 1.5.2.2  2003/05/21 12:11:03  lukasz
- s/1.0.0/1.0-cvs/g

Revision 1.5.2.1  2003/05/21 11:59:53  lukasz
- 1.0-cvs

Revision 1.5  2003/05/21 11:57:31  lukasz
- fscking cvs recovery :(

Revision 1.5.2.1  2003/05/18 22:54:18  lukasz
- grrr

Revision 1.5  2003/05/18 21:07:18  lukasz
- 1.0-cvs

Revision 1.4  2003/04/15 04:17:22  lukasz
- sync with PLD CVS

Revision 1.11  2003/04/15 04:04:21  baseciq
- in fact, adodb is pear-compatible, so moved to /usr/share/pear (it's
  the only dir that php looks for aditional libs by default)
- added missing br: rpm-php-pearprov
- added missing %include macro.rpm
- cleanups
- updated URL
- this library is called 'ADOdb', not 'adodb'...

Revision 1.10  2003/04/07 18:51:25  adamg
- updated to 3.40

Revision 1.9  2003/03/18 12:53:05  adamg
- updated to 3.31

Revision 1.8  2003/03/16 17:54:41  adamg
- updated to 3.30
- added missing files

Revision 1.7  2002/07/20 17:33:58  kloczek
- removed commented lines.

Revision 1.6  2002/07/18 22:24:41  bonkey
- version: 2.20
- changed license to dual licensed using BSD-Style and LGPL
- clean-ups in %%doc

Revision 1.5  2002/04/25 15:42:02  arturs
fixed a small typo

Revision 1.4  2002/03/02 10:24:13  kloczek
- cosmetics.

Revision 1.3  2002/02/22 23:28:40  kloczek
- removed all Group fields translations (our rpm now can handle translating
  Group field using gettext).

Revision 1.2  2002/01/18 02:12:18  kloczek
perl -pi -e "s/pld-list\@pld.org.pl/feedback\@pld.org.pl/"

Revision 1.1  2001/09/01 17:50:41  misiek
initial pld release

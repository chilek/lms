# $Revision$, $Date$
#
# Conditional build:
%bcond_without	almsd		# without almsd daemon
#
# TODO:
# - almsd description
# - cosmetics (sort in %%files and %%install)
# - contrib split
Summary:	LAN Managment System
Summary(pl):	System Zarz±dzania Sieci± Lokaln±
Name:		lms
Version:	1.4.0
Release:	0.7.4
License:	GPL
Vendor:		LMS Developers
Group:		Networking/Utilities
Source0:	http://lms.rulez.pl/download/%{version}/%{name}-%{version}.tar.gz
Source1:	%{name}.conf
Source2:	%{name}.init
Source3:	%{name}.sysconfig
Patch0:		%{name}-PLD.patch
Patch1:		%{name}-amd64.patch
URL:		http://lms.rulez.pl/
%{?with_almsd:BuildRequires:	libgadu-devel}
%{?with_almsd:BuildRequires:	mysql-devel}
%{?with_almsd:BuildRequires:	postgresql-devel}
%{?with_almsd:PreReq:		rc-scripts}
%{?with_almsd:Requires(post,preun):	/sbin/chkconfig}
Requires:	php
Requires:	php-gd
Requires:	php-posix
Requires:	php-pcre
Requires:	webserver
Requires:	Smarty >= 2.5.0
BuildRoot:	%{tmpdir}/%{name}-%{version}-root-%(id -u -n)

%define		_sysconfdir	/etc/%{name}
%define		_lmsdir		%{_datadir}/%{name}
%define		_lmsvar		/var/lib/%{name}

%description
This is a package of applications in PHP and Perl for managing LANs.
It's using MySQL or PostgreSQL. The main goal is to get the best
service of users at provider's level. The main features in LMS are:
- database of users (name, surname, address, telefon number,
  commentary);
- database of computers (IP, MAC);
- easy-ridden financial system and funds of network;
- different subscriptions;
- sending warnings to users;
- many levels of access for LMS administrators;
- autogenerating ipchains, iptables, dhcpd, ethers file, oidentd,
  openbsd packet filter configuration files/scripts;
- autogenerating almost any kind of config file using templates;

%description -l pl
"LMS" jest skrótem od "LAN Management System". Jest to zestaw
aplikacji w PHP i Perlu, u³atwiaj±cych zarz±dzanie sieciami
osiedlowymi (popularnie zwanymi Amatorskimi Sieciami Komputerowymi),
opartych o bazê danych MySQL lub PostgreSQL. G³ówne za³o¿enia to
uzyskanie jako¶ci us³ug oraz obs³ugi u¿ytkowników na poziomie
providera z prawdziwego zdarzenia. Najbardziej podstawowe cechy LMS
to:
- baza danych u¿ytkowników (imiê, nazwisko, adres, numer telefonu,
  uwagi);
- baza danych komputerów (adres IP, adres MAC);
- prowadzenie prostego rachunku operacji finansowych oraz stanu
  funduszów sieci;
- ró¿ne taryfy abonamentowe;
- wysy³anie poczt± elektroniczn± upomnieñ do u¿ytkowników;
- automatyczne naliczanie op³at miesiêcznych;
- ró¿ne poziomy dostêpu do funkcji LMS dla administratorów;
- generowanie regu³ i plików konfiguracyjnych dla ipchains, iptables,
  dhcpd, oidentd, packet filtra openbsd, wpisów /etc/ethers
- generowanie praktycznie ka¿dego pliku konfiguracyjnego na podstawie
  danych w bazie przy u¿yciu prostych szablonów;

%package scripts
Summary:	LAN Managment System - scripts
Summary(pl):	LAN Managment System - skrypty
Group:		Networking/Utilities
Requires:	perl-Net-SMTP-Server
Requires:	perl-Config-IniFiles
Requires:	perl-DBI

%description scripts
This package contains scripts to integrate LMS with your system,
monthly billing, notify users about their debts and cutting off
customers. Also you can build propably any kind of config file using
lms-mgc.

%description scripts -l pl
Ten pakiet zawiera skrypty do zintegrowania LMS z systemem, naliczania
comiesiêcznych op³at, powiadamiania u¿ytkowników o ich zad³u¿eniu oraz
ich automagicznego od³±czania. Mo¿esz tak¿e zbudowaæ prawdopodobnie
ka¿dy typ pliku konfiguracyjnego przy u¿yciu lms-mgc;

%package sqlpanel
Summary:	LAN Managment System - sqlpanel module
Summary(pl):	LAN Managment System - modu³ sqlpanel
Group:		Networking/Utilities
Requires:	%{name}

%description sqlpanel
SQL-panel module allows you to execute SQL queries and directly modify
data.

%description sqlpanel -l pl
Modu³ 'SQL - panel' daje mo¿liwo¶æ bezpo¶redniego dostêpu do bazy
danych poprzez zadawanie zapytañ SQL. Wyniki wy¶wietlane s± w formie
tabeli. Ponadto podawany jest czas wykonania zapytania.

%package user
Summary:	LAN Managment System - simple user interface
Summary(pl):	LAN Managment System - prosty interfejs u¿ytkownika
Group:		Networking/Utilities
Requires:	%{name}

%description user
Simple user interface.

%description user -l pl
Prosty interfejs u¿ytkownika.

%package almsd
Summary:	LAN Managment System - almsd
Group:		Networking/Utilities
Requires:	%{name}

%description almsd
TODO

%prep
%setup -q -n %{name}
%patch0 -p1
%ifarch amd64
%patch1 -p1
%endif
%patch2 -p1

%build
%if %{with almsd}

cd daemon

./configure --with-mysql
%{__make} \
	CC='%{__cc}' CFLAGS='%{rpmcflags} -fPIC -DUSE_MYSQL -I../..'
mv almsd almsd-mysql

rm db.o

./configure --with-pgsql
%{__make} almsd \
	CC='%{__cc}' \
	CFLAGS='%{rpmcflags} -fPIC -DUSE_PGSQL -I../..'
mv almsd almsd-pgsql

cd ..
%endif

%install
rm -rf $RPM_BUILD_ROOT
install -d $RPM_BUILD_ROOT%{_sbindir} \
	   $RPM_BUILD_ROOT/etc/{rc.d/init.d,sysconfig,httpd} \
	   $RPM_BUILD_ROOT/etc/lms/modules/{dns,ggnofity,nofity} \
	   $RPM_BUILD_ROOT{%{_lmsvar}/{backups,templates_c},/usr/lib/lms} \
	   $RPM_BUILD_ROOT%{_lmsdir}/www/{img,doc,user}

install *.php $RPM_BUILD_ROOT%{_lmsdir}/www
install img/* $RPM_BUILD_ROOT%{_lmsdir}/www/img
cp -r doc/html $RPM_BUILD_ROOT%{_lmsdir}/www/doc
cp -r lib config_templates contrib modules templates sample $RPM_BUILD_ROOT%{_lmsdir}
install bin/* $RPM_BUILD_ROOT%{_sbindir}

install sample/%{name}.ini $RPM_BUILD_ROOT%{_sysconfdir}
install %{SOURCE1} $RPM_BUILD_ROOT/etc/httpd/%{name}.conf

# sqlpanel
install contrib/sqlpanel/sql.php $RPM_BUILD_ROOT%{_lmsdir}/modules
install contrib/sqlpanel/*.html $RPM_BUILD_ROOT%{_lmsdir}/templates

# user
install contrib/customer/* $RPM_BUILD_ROOT%{_lmsdir}/www/user

# daemon
%if %{with almsd}
install daemon/almsd-* $RPM_BUILD_ROOT%{_sbindir}
install daemon/modules/*/*.so $RPM_BUILD_ROOT/usr/lib/lms
cp -r daemon/modules/dns/sample $RPM_BUILD_ROOT%{_sysconfdir}/modules/dns
cp -r daemon/modules/ggnotify/sample $RPM_BUILD_ROOT%{_sysconfdir}/modules/ggnotify
cp -r daemon/modules/dns/sample $RPM_BUILD_ROOT%{_sysconfdir}/modules/nofity
install %{SOURCE2} $RPM_BUILD_ROOT/etc/rc.d/init.d/lmsd
install %{SOURCE3} $RPM_BUILD_ROOT/etc/sysconfig/%{name}
%endif

%clean
rm -rf $RPM_BUILD_ROOT

%post
if [ -f /etc/httpd/httpd.conf ] && ! grep -q "^Include.*%{name}.conf" /etc/httpd/httpd.conf; then
	echo "Include /etc/httpd/%{name}.conf" >> /etc/httpd/httpd.conf
	if [ -f /var/lock/subsys/httpd ]; then
		/usr/sbin/apachectl restart 1>&2
	fi
elif [ -d /etc/httpd/httpd.conf ]; then
	ln -sf /etc/httpd/%{name}.conf /etc/httpd/httpd.conf/99_%{name}.conf
	if [ -f /var/lock/subsys/httpd ]; then
		/usr/sbin/apachectl restart 1>&2
	fi
fi

%post almsd
/sbin/chkconfig --add lmsd
if [ -f /var/lock/subsys/lmsd ]; then
	/etc/rc.d/init.d/lmsd restart >&2
else
	echo "Run \"/etc/rc.d/init.d/lmsd start\" to start almsd daemon."
fi

%preun
if [ "$1" = "0" ]; then
	umask 027
	if [ -d /etc/httpd/httpd.conf ]; then
		rm -f /etc/httpd/httpd.conf/99_%{name}.conf
	else
		grep -v "^Include.*%{name}.conf" /etc/httpd/httpd.conf > \
			/etc/httpd/httpd.conf.tmp
		mv -f /etc/httpd/httpd.conf.tmp /etc/httpd/httpd.conf
	fi
	if [ -f /var/lock/subsys/httpd ]; then
		/usr/sbin/apachectl restart 1>&2
	fi
fi

%preun almsd
if [ "$1" = "0" ]; then
	if [ -f /var/lock/subsys/lmsd ]; then
		/etc/rc.d/init.d/lmsd stop >&2
	fi
	/sbin/chkconfig --del lmsd
fi

%triggerpostun -- %{name} <= 1.0.4
echo "WARNING!!!"
echo "_READ_ and upgrade LMS database:"
echo "MySQL: /usr/share/doc/%{name}-%{version}/UPGRADE-1.0-1.5.mysql.gz"
echo "PostgreSQL: /usr/share/doc/%{name}-%{version}/UPGRADE-1.0-1.5.pgsql.gz"
echo

%files
%defattr(644,root,root,755)
%doc doc/{AUTHORS,ChangeLog*,README,TODO,UPGRADE*,lms*}
%dir %{_sysconfdir}
%attr(640,root,http) %config(noreplace) %verify(not size mtime md5) %{_sysconfdir}/*.ini
%config(noreplace) %verify(not size mtime md5) /etc/httpd/%{name}.conf
#
%dir %{_lmsvar}
%attr(770,root,http) %{_lmsvar}/backups
%attr(770,root,http) %{_lmsvar}/templates_c
#
%dir %{_lmsdir}
%{_lmsdir}/www
%exclude %{_lmsdir}/www/user
%{_lmsdir}/lib
%{_lmsdir}/modules
%exclude %{_lmsdir}/modules/sql.php
%{_lmsdir}/contrib
%{_lmsdir}/sample
%attr(755,root,root) %{_lmsdir}/sample/traffic_ipt.sh
%{_lmsdir}/templates
%{_lmsdir}/config_templates
%exclude %{_lmsdir}/templates/sql.html
%exclude %{_lmsdir}/templates/sqlprint.html

%files scripts
%defattr(644,root,root,755)
%dir %{_sbindir}
%attr(755,root,root) %{_sbindir}/*

%files sqlpanel
%defattr(644,root,root,755)
%{_lmsdir}/modules/sql.php
%{_lmsdir}/templates/sql.html
%{_lmsdir}/templates/sqlprint.html

%files user
%defattr(644,root,root,755)
%{_lmsdir}/www/user

%if %{with almsd}
%files almsd
%defattr(644,root,root,755)
%doc daemon/{lms.ini.sample,TODO}
%attr(755,root,root) %{_sbindir}/almsd-*
%attr(755,root,root) /usr/lib/lms/*.so
%attr(754,root,root) /etc/rc.d/init.d/lmsd
/etc/lms/modules/*
%attr(640,root,root) %config(noreplace) %verify(not md5 mtime size) /etc/sysconfig/%{name}
%endif

%define	date	%(echo `LC_ALL="C" date +"%a %b %d %Y"`)
%changelog
* %{date} PLD Team <feedback@pld-linux.org>
All persons listed below can be reached at <cvs_login>@pld-linux.org

$Log$
Revision 1.1.2.2  2004/10/15 11:51:10  averne
- fixed Source0

Revision 1.1.2.1  2004/10/15 11:33:52  averne
- new release from PLD CVS

Revision 1.31.2.49  2004/10/11 21:08:55  domelu
- chmod 755 traffic_ipt.sh

Revision 1.31.2.48  2004/10/11 19:31:32  domelu
- R: php-gd

Revision 1.31.2.47  2004/10/08 20:49:06  domelu
- TODO-done: ,,fix pinger.c (daemon/modules/pinger)''

Revision 1.31.2.46  2004/10/08 20:47:46  domelu
- lms-pinger.patch

Revision 1.31.2.45  2004/10/08 10:48:18  domelu
- TODO-done ,,fix lms-amd64.patch''

Revision 1.31.2.44  2004/10/08 07:53:35  averne
- -fPIC

Revision 1.31.2.43  2004/10/08 01:59:25  domelu
- TODO done: contrib stuff, samples
- TODO: contrib split

Revision 1.31.2.42  2004/10/08 01:54:33  domelu
- lms-almsd package: added doc (lms.ini.sample, TODO)

Revision 1.31.2.41  2004/10/08 01:23:02  domelu
- TODO: samples
- typo in TODO and changelog

Revision 1.31.2.40  2004/10/08 01:19:14  domelu
- added /etc/lms/modules/{dns,nofity,ggnofity}
- TODO: contrib stuff
	fix pinger.c (daemon/modules/pinger)

Revision 1.31.2.39  2004/10/07 20:53:04  domelu
- revert my revert ;)

Revision 1.31.2.38  2004/10/07 20:23:36  ankry
- restored cleaning: description formatting, spaces -> tabs; restored changelog entry

Revision 1.31.2.37  2004/10/07 20:16:58  domelu
- /etc/rc.d/init.d/alsd -> lmds (tx ares)

Revision 1.31.2.36  2004/10/07 19:51:27  domelu
- and again ;)

Revision 1.31.2.35  2004/10/07 19:45:56  domelu
- typo in post and preun

Revision 1.31.2.34  2004/10/07 19:01:05  domelu
- cosmetics

Revision 1.31.2.33  2004/10/07 18:59:49  domelu
- grr, revert ,,- cleanups; now it builds''

Revision 1.31.2.32  2004/10/07 18:41:40  paszczus
- cleanups; now it builds

Revision 1.31.2.31  2004/10/07 18:40:57  domelu
- typo

Revision 1.31.2.30  2004/10/07 18:36:38  domelu
- rel. 0.7.1

Revision 1.31.2.29  2004/10/07 18:35:22  domelu
- chkconfig stuff

Revision 1.31.2.28  2004/10/07 18:00:34  domelu
- rel. 0.7

Revision 1.31.2.27  2004/10/07 17:58:21  domelu
- lms.init for almsd daemon
- lms.sysconfig for almsd daemon
- sort Group/Requires
- fixed almsd bcond
- install -d cosmetics
- almsd-mysql and almsd-pgsql moved to sbindir
- chmod 755 /usr/lib/lms/*.so

Revision 1.31.2.26  2004/10/07 14:53:57  domelu
- scripts moved to sbindir

Revision 1.31.2.25  2004/10/07 14:14:41  domelu
- TODO: lms.init and lms.sysconfig for almsd

Revision 1.31.2.24  2004/10/07 07:32:03  domelu
- removed install contrib/*

Revision 1.31.2.23  2004/10/07 07:27:36  domelu
- bcond almsd fixes

Revision 1.31.2.22  2004/10/07 07:15:18  domelu
- added bcond almsd (build wuth almsd daemon, default --with)

Revision 1.31.2.21  2004/10/07 07:00:47  domelu
- fixed trigger

Revision 1.31.2.20  2004/10/07 06:46:59  domelu
- fix in TODO

Revision 1.31.2.19  2004/10/07 06:15:25  domelu
- added UPGRADE* to doc

Revision 1.31.2.18  2004/10/07 06:10:30  domelu
- rel. 0.3

Revision 1.31.2.17  2004/10/07 06:09:01  domelu
- trigger (upgrade from old 1.0.4)

Revision 1.31.2.16  2004/10/07 05:59:08  domelu
- removed upgrade package

Revision 1.31.2.15  2004/10/04 01:12:46  domelu
- done some todo things (fixed lms.ini and added upgrade package)
- TODO:
  almsd and upgrade description
  cosmetics (sort in %files, %install)

Revision 1.31.2.14  2004/10/04 00:22:51  domelu
- TODO (tx gaber):
  tigger (upgrade from old 1.0.4)
  lms-upgrade package
  fix lms-PLD.patch (paths in lms.ini)

Revision 1.31.2.13  2004/10/03 11:07:56  domelu
- %install added contrib, sorted

Revision 1.31.2.12  2004/10/03 03:33:07  domelu
- TODO: fix lms-amd64.patch

Revision 1.31.2.11  2004/10/03 02:26:24  domelu
- lms-PLD.patch

Revision 1.31.2.10  2004/10/03 00:42:02  domelu
- amd64 fix

Revision 1.31.2.9  2004/10/02 19:27:44  domelu
- config_templates cosmetics
- added config_templates to %files

Revision 1.31.2.8  2004/09/30 11:41:33  domelu
- lms.conf things in %post and %preun
- lms.conf patch fix

Revision 1.31.2.7  2004/09/30 11:22:53  domelu
- lms.conf patch, /etc/httpd/httpd.conf/ -> /etc/httpd/

Revision 1.31.2.6  2004/09/30 11:06:09  domelu
- config_templates
- SOURCES/lms.conf

Revision 1.31.2.5  2004/09/22 05:43:19  orzech
- up to 1.5.0

Revision 1.31.2.4  2004/09/12 10:08:29  orzech
- almsd for pgsql

Revision 1.31.2.3  2004/09/01 22:17:54  orzech
- testing 1.3.6

Revision 1.31.2.2  2004/08/14 11:49:38  orzech
- subpackages

Revision 1.31.2.1  2004/07/31 19:18:33  orzech
- testing 1.3.5

Revision 1.31  2003/12/12 04:02:58  baseciq
- up to 1.0.4
- fixed descriptions
- maybe someone can look at lms-1.1.x and make .spec for it?

Revision 1.30  2003/08/30 02:21:31  baseciq
- up to 1.0.3

Revision 1.29  2003/08/20 20:58:32  undefine
- update to 1.0.2

Revision 1.28  2003/08/18 08:08:18  gotar
- mass commit: cosmetics (removed trailing white spaces)

Revision 1.27  2003/06/30 08:10:10  baseciq
- up: 1.0.1
- rel 1

Revision 1.26  2003/06/12 22:39:06  baseciq
- sync up to 1.0.0 (latest stable version)

Revision 1.25  2003/05/28 12:59:37  malekith
- massive attack: source-md5

Revision 1.24  2003/05/25 05:50:15  misi3k
- massive attack s/pld.org.pl/pld-linux.org/

Revision 1.23  2003/05/04 18:01:55  djrzulf
- %doc updated

Revision 1.22  2003/04/28 12:29:51  baseciq
- added missing br: php-pcre (tnx byko)

Revision 1.21  2003/04/15 08:25:47  qboosh
- missing defattr for scripts, cosmetics in scripts description

Revision 1.20  2003/04/15 07:37:23  djrzulf
- added sugestion

Revision 1.19  2003/04/15 04:16:47  baseciq
- moved perl scripts into separated package

Revision 1.18  2003/04/14 23:00:18  baseciq
- another reorganization
- don't change default macros like localstate and etc, just define
  _sharedstatedir that already in rpm-build-4.1.x
- pre10
- cosmetics

Revision 1.17  2003/04/14 22:07:27  djrzulf
- reorganization

Revision 1.16  2003/04/09 10:15:32  baseciq
- LMS doesn't depend on some particular database, so we shouldn't require
  mysql or postgresql, and even drivers (are they provide: something like
  sqlserver or database server?)

Revision 1.15  2003/04/09 06:25:40  djrzulf
- updated

Revision 1.14  2003/04/09 05:36:17  pbern
- update to pre8
- force to use Smarty >= 2.4.2

Revision 1.13  2003/03/31 12:48:47  qboosh
- missing dir (%%{_sysconfdir}/%%{name})

Revision 1.12  2003/03/29 22:11:21  mwinkler
- next bug fixed, now works

Revision 1.11  2003/03/29 21:43:19  mwinkler
- templates fix

Revision 1.10  2003/03/29 21:28:57  mwinkler
- update to 1.0pre7
- simplifications & cleaning

Revision 1.9  2003/03/19 09:34:13  djrzulf
- change requires

Revision 1.8  2003/03/17 17:28:56  pbern
- update to 1.0pre6

Revision 1.7  2003/02/25 21:40:48  djrzulf
- release 0.4,
- added conf files,

Revision 1.6  2003/02/25 21:31:04  djrzulf
- cosmetics, release 0.3

Revision 1.5  2003/02/25 14:51:20  qboosh
- en fixes, removed second Log
- moved /var/lms to FHS-compliant /var/lib/lms

Revision 1.4  2003/02/24 21:53:10  djrzulf
- cosmetics (part2)

Revision 1.3  2003/02/24 16:17:44  djrzulf
- cosmetics

Revision 1.2  2003/02/24 09:47:09  djrzulf
- updated description - somebody who know english - check it,
- changes source to without libs,
- added 2 Requries,
- Release 0.2,

Revision 1.1  2003/02/19 08:39:49  djrzulf
- initial PLD release (0.1)

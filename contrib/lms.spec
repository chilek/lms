# $Revision$, $Date$
Summary:	LAN Managment System
Summary(pl):	System Zarz±dzania Sieci± Lokaln±
Name:		lms
Version:	1.0.4
Release:	1
License:	GPL
Vendor:		LMS Developers
Group:		Networking/Utilities
Source0:	http://lms.rulez.pl/download/%{name}-%{version}.tar.gz
# Source0-md5:	1481b7b7b8c14a739ce38f14c1fd2aeb
Patch0:		%{name}-PLD.patch
URL:		http://lms.rulez.pl/
Requires:	php
Requires:	php-posix
Requires:	php-pcre
Requires:	webserver
Requires:	Smarty >= 2.5.0
Requires:	adodb >= 2.90
BuildArch:	noarch
BuildRoot:	%{tmpdir}/%{name}-%{version}-root-%(id -u -n)

%define		_lmsdir		/home/services/httpd/html/%{name}
%define		_sharedstatedir	/var/lib
# when spec'll be finished, this sould go to RA-branch
# because sharedstatedir is already defined at rpm macros from HEAD

%description
This is a package of applications in PHP and Perl for managing LANs.
It's using MySQL or PostgreSQL. The main goal is to get the best 
service of users at provider's level.
The main features in LMS are:
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
providera z prawdziwego zdarzenia. 
Najbardziej podstawowe cechy LMS to:
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
Requires:	perl-Net-SMTP-Server
Requires:	perl-Config-IniFiles
Requires:	perl-DBI
BuildArch:	noarch
Group:		Networking/Utilities

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

%prep
%setup -q -n %{name}
%patch0 -p1

%install
rm -rf $RPM_BUILD_ROOT
install -d $RPM_BUILD_ROOT%{_lmsdir}/img
install -d $RPM_BUILD_ROOT%{_datadir}/%{name}
install -d $RPM_BUILD_ROOT%{_bindir}
install -d $RPM_BUILD_ROOT%{_sysconfdir}/%{name}
install -d $RPM_BUILD_ROOT%{_sharedstatedir}/%{name}/{backups,templates_c}
install -d $RPM_BUILD_ROOT%{_libexecdir}/%{name}/{lib,modules,templates}

install *.php $RPM_BUILD_ROOT%{_lmsdir}
install bin/* $RPM_BUILD_ROOT%{_bindir}
install lib/* $RPM_BUILD_ROOT%{_libexecdir}/%{name}/lib
install img/* $RPM_BUILD_ROOT%{_lmsdir}/img
install modules/* $RPM_BUILD_ROOT%{_libexecdir}/%{name}/modules
install templates/* $RPM_BUILD_ROOT%{_libexecdir}/%{name}/templates
install sample/%{name}.ini $RPM_BUILD_ROOT%{_sysconfdir}/%{name}

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(644,root,root,755)
%doc doc/* sample/*.ini sample/*txt sample/rc.reminder_1st sample/crontab-entry
%dir %{_lmsdir}
%dir %{_libexecdir}/%{name}
%dir %{_sharedstatedir}/%{name}
%attr(770,root,http) %{_sharedstatedir}/%{name}/templates_c
%attr(770,root,http) %{_sharedstatedir}/%{name}/backups
%{_lmsdir}/*.php
%{_lmsdir}/img
%{_libexecdir}/%{name}/lib
%{_libexecdir}/%{name}/modules
%{_libexecdir}/%{name}/templates
%dir %{_sysconfdir}/%{name}
%config(noreplace) %verify(not size mtime md5) %{_sysconfdir}/%{name}/*.ini

%files scripts
%defattr(644,root,root,755)
%attr(755,root,root) %{_bindir}/lms-*
%doc sample/*.ini

%define date	%(echo `LC_ALL="C" date +"%a %b %d %Y"`)
%changelog
* %{date} PLD Team <feedback@pld-linux.org>
All persons listed below can be reached at <cvs_login>@pld-linux.org

$Log$
Revision 1.14  2003/12/12 04:03:47  lukasz
- gez. synced with PLD's version

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

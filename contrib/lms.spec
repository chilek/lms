# $Revision$, $Date$
Summary:	LAN Managment System
Summary(pl):	System Zarz�dzania Siec� Lokaln�
Name:		lms
Version:	1.0pre10
Release:	0.1
License:	GPL
Group:		Networking/Utilities
Source0:	http://lms.rulez.pl/download/%{name}-%{version}.tar.gz
Patch0:		%{name}-PLD.patch
Vendor:		LMS Developers
URL:		http://lms.rulez.pl
Requires:	php
Requires:	php-posix
Requires:	webserver
Requires:	Smarty >= 2.5.0
Requires:	adodb >= 2.90
BuildArch:	noarch
BuildRoot:	%{tmpdir}/%{name}-%{version}-root-%(id -u -n)

%define		_lmsdir		/home/services/httpd/html/%{name}
%define		_sharedstatedir	/var/lib

%description
This is a package of applications in PHP and Perl for managing LANs.
It's using MySQL (for now) but PostgreSQL will be supported in near
future. The main goal is to get the best service of users at
provider's level.
The main features in LMS are:
- database of users (name, surname, address, telefon number, 
  commentary);
- database of computers (IP, MAC);
- easy-ridden financial system and funds of network;
- different subscriptions;
- sending warnings to users;
- autogenerating dhcpd.conf;
- autogenerating firewall rules (ipchains/iptables);
- autogenerating idents for ident daemon;
- many levels of access for LMS administrators;
- integration with LinuxStat package;
- autogenerating ARP rules (ether auth);
- autogenerating DNS files.

%description -l pl
"LMS" jest skr�tem od "LAN Management System". Jest to zestaw
aplikacji w PHP i Perlu, u�atwiaj�cych zarz�dzanie sieciami
osiedlowymi (popularnie zwanymi Amatorskimi Sieciami Komputerowymi),
opartych o baz� danych MySQL (docelowo, do wyboru, MySQL lub
PostgreSQL). G��wne za�o�enia to uzyskanie jako�ci us�ug oraz obs�ugi
u�ytkownik�w na poziomie providera z prawdziwego zdarzenia.
Najbardziej podstawowe cechy LMS to:
- baza danych u�ytkownik�w (imi�, nazwisko, adres, numer telefonu,
  uwagi);
- baza danych komputer�w (adres IP, adres MAC);
- prowadzenie prostego rachunku operacji finansowych oraz stanu
  fundusz�w sieci;
- r�ne taryfy abonamentowe;
- wysy�anie poczt� elektroniczn� upomnie� do u�ytkownik�w;
- automatyczne naliczanie op�at miesi�cznych;
- generowanie dhcpd.conf;
- generowanie regu� firewalla (ipchains/iptables);
- generowanie ident�w dla demona oidentd;
- r�ne poziomy dost�pu do funkcji LMS dla administrator�w;
- integracja z pakietem LinuxStat;
- generowanie wpis�w ARP (blokada adres�w IP po ARP);
- generowanie wpis�w do DNS.

%package scripts
Summary:	LAN Managment System - scripts
Summary(pl):	LAN Managment System - skrypty
Requires:	perl-Net-SMTP-Server
Requires:	perl-Config-IniFiles
Requires:	perl-DBI
BuildArch:	noarch
Group:		Networking/Utilities

%description scripts
This package contains scripts to integrate LMS with your system, monthly
billing, notify users about their debts and cutting off customers. Also
you can build propably any kind of config file using lms-mgc.

%description scripts -l pl
Ten pakiet zawiera skrypty do zintegrowania LMS z twoim systemem,
naliczania comiesi�cznych op�at, powiadamiania u�ytkownik�w o ich
zad�u�eniu oraz ich automagicznego od��czania. Mo�esz tak�e zbudowa�
prawdopodobnie ka�dy typ pliku konfiguracyjnego przy u�yciu lms-mgc.

%prep
%setup -q -n lms
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
%doc doc sample/*.ini sample/*txt sample/rc.reminder_1st sample/crontab-entry
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
%attr(755,root,root) %{_bindir}/lms-*
%doc sample/*.ini

%define date	%(echo `LC_ALL="C" date +"%a %b %d %Y"`)
%changelog
* %{date} PLD Team <feedback@pld.org.pl>
All persons listed below can be reached at <cvs_login>@pld.org.pl

$Log$
Revision 1.5  2003/04/15 04:17:22  lukasz
- sync with PLD CVS

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

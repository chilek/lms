# $Revision$, $Date$
Summary:	LAN Managment System
Summary(pl):	System Zarz±dzania Siec± Lokaln±
Name:		lms
Version:	1.0pre5
Release:	0.4
License:	GPL
Group:		Networking/Utilities
Source0:	http://lms.rulez.pl/download/%{name}-%{version}.tar.gz
Patch0:		%{name}.ini-PLD.patch
Vendor:		Rulez.PL
Requires:	php
Requires:	php-posix
Requires:	webserver
Requires:	mysql
Requires:	perl-Net-SMTP-Server
Requires:	perl-Config-IniFiles
Requires:	perl-DBI
Requires:	perl-DBD-mysql
Requires:	perl-DBD-Pg
Requires:	Smarty
Requires:	ADOdb
BuildArch:	noarch
BuildRoot:	%{tmpdir}/%{name}-%{version}-root-%(id -u -n)

%define		_lmsdir		/home/services/httpd/html/%{name}
%define		_localstatedir	/var/lib/lms

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
"LMS" jest skrótem od "LAN Management System". Jest to zestaw
aplikacji w PHP i Perlu, u³atwiaj±cych zarz±dzanie sieciami
osiedlowymi (popularnie zwanymi Amatorskimi Sieciami Komputerowymi),
opartych o bazê danych MySQL (docelowo, do wyboru, MySQL lub
PostgreSQL). G³ówne za³o¿enia to uzyskanie jako¶ci us³ug oraz obs³ugi
u¿ytkowników na poziomie providera z prawdziwego zdarzenia.
Najbardziej podstawowe cechy LMS to:
- baza danych u¿ytkowników (imiê, nazwisko, adres, numer telefonu,
  uwagi);
- baza danych komputerów (adres IP, adres MAC);
- prowadzenie prostego rachunku operacji finansowych oraz stanu
  funduszów sieci;
- ró¿ne taryfy abonamentowe;
- wysy³anie poczt± elektroniczn± upomnieñ do u¿ytkowników;
- automatyczne naliczanie op³at miesiêcznych;
- generowanie dhcpd.conf;
- generowanie regu³ firewalla (ipchains/iptables);
- generowanie identów dla demona oidentd;
- ró¿ne poziomy dostêpu do funkcji LMS dla administratorów;
- integracja z pakietem LinuxStat;
- generowanie wpisów ARP (blokada adresów IP po ARP);
- generowanie wpisów do DNS.

%prep
%setup -q -n lms
%patch0 -p1

%install
rm -rf $RPM_BUILD_ROOT
install -d $RPM_BUILD_ROOT%{_lmsdir}/{img,lib,modules,templates,templates_c}
install -d $RPM_BUILD_ROOT%{_datadir}/%{name}
install -d $RPM_BUILD_ROOT%{_bindir}
install -d $RPM_BUILD_ROOT%{_sysconfdir}/%{name}

install *.php $RPM_BUILD_ROOT%{_lmsdir}
install -d {img,lib,modules,templates,templates_c} $RPM_BUILD_ROOT%{_lmsdir}
install -d backup $RPM_BUILD_ROOT%{_localstatedir}
install bin/* $RPM_BUILD_ROOT%{_bindir}
install sample/%{name}.ini $RPM_BUILD_ROOT%{_sysconfdir}/%{name}

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(644,root,root,755)
%doc doc sample/lms-mgc* sample/*txt sample/rc.reminder_1st
%attr(755,root,root) %{_bindir}/lms-*
%dir %{_lmsdir}
%attr(770,root,http) %{_lmsdir}/templates_c
%{_lmsdir}/*.php
%{_lmsdir}/img
%{_lmsdir}/lib
%{_lmsdir}/modules
%{_lmsdir}/templates
%{_localstatedir}
%{_sysconfdir}/%{name}

%define date	%(echo `LC_ALL="C" date +"%a %b %d %Y"`)
%changelog
* %{date} PLD Team <feedback@pld.org.pl>
All persons listed below can be reached at <cvs_login>@pld.org.pl

$Log$
Revision 1.1  2003/04/11 22:00:33  lukasz
- recovery from lms-1.0pre8

Revision 1.1  2003/04/11 21:07:32  lukasz
- recovered from lms-1.0pre8

Revision 1.8  2003/03/16 16:23:58  lukasz
- no, it isn't required ;)

Revision 1.7  2003/02/25 21:42:07  djrzulf
- aktualizacja

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

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

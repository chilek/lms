W konfiguracji LMS tworzymy nową sekcję 'callcenter'.

W pierwszej kolejności należy stworzyć katalog templates_c oraz nadać mu właściwe uprawnienia.

Jeśli używasz Debiana będą to najprawdopodobniej komendy:
mkdir /var/www/html/lms/contrib/tele.centrum.v2/templates_c/;
chmod 755 /var/www/html/lms/contrib/tele.centrum.v2/templates_c/;
chown 33:33 /var/www/html/lms/contrib/tele.centrum.v2/templates_c/;

Jeśli używasz CentOS:
mkdir /var/www/html/lms/contrib/tele.centrum.v2/templates_c/;
chmod 755 /var/www/html/lms/contrib/tele.centrum.v2/templates_c/;
chown 48:48 /var/www/html/lms/contrib/tele.centrum.v2/templates_c/;

Uzupełniamy zmienne konfiguracyjne z sekcji callcenter:
- callcenterip - podać adres IP z którego łączyć się będą agenci callcenter,
- networks - adresacja sieci, która może wyświetlać formularz callcenter np. 10.10.10.0/24 (można podać kilka sieci oddzielonych przecinkiem), 

- fault_issues_queue - ID kolejki helpdesk dla zgłoszeń usterek,
- offer_issues_queue - ID kolejki helpdesk dla zgłoszeń ze sprawami handlowymi,
- payment_issues_queue - ID kolejki helpdesk dla zgłoszeń ze sprawami księgowymi,

- service_internet_category - kategoria nadawana zgłoszeniom które dotyczą usługi internet
- service_phone_category - kategoria nadawana zgłoszeniom które dotyczą usługi telefon
- service_tv_category - kategoria nadawana zgłoszeniom które dotyczą usługi telewizja
- default_category - domyślna kategoria, która zostanie nadana zgłoszeniu bez przypisanej usługi

- queueuser - id użytkownika do którego ma być przypisane zgłoszenie (może być 0),
- warning - treść wiadomości specjalnej wyświetlanej na górze strony,
- information - możliwość dodanie dodatkowych informacji do wysuwającego się panelu (np. tabela z godzinami pracy).

Skrypt, który dodaje nagrania rozmów callcenter do odpowiednich zgłoszeń znajduję się w folderze bin. 
Skrypt wymaga rozszerzenia imap dla PHP.

Należy dodać go do crontab.

Wymagane ustawienia do pobierania nagrań z rozmów:
- hostname - nazwa hosta poczty,
- user - nazwa użytkownika poczty,
- pass - hasło użytkownika,
- mailfrom - nazwa maila callcenter.

Dodatkowo, należy upewnić się czy w sekcji 'rt' utworzona jest opcja 'mail_dir' z lokalizacją folderu. 
Będzie ona wykorzystywana do zapisywanie nagrań z rozmów.

Numer telefonu pobierany jest na podstawie URL. System callcenter automatycznie dodaje do URL takie dane jak: ID konsultanta, nr tel. dzwoniącego, nr sprawy, które potem wykorzystywane są w skrypcie.

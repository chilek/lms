**Co potrafi skrypt:**
- automatyzuje wrzucanie raportów zasięgów z LMS do SIDUSIS,
- automatyzuje przetwarzanie "zapotrzebowań na internet" z SIDUSIS tworząc ticket w module helpdesk LMSa,

**Instalacja:**
1. W LMS muszą być wgrane punkty adresowe TERYT

Dla przypomnienia:
```
bin/lms-teryt.php -f
bin/lms-teryt.php -f --building-base-provider gugik
bin/lms-teryt.php -f --building-base-provider sidusis
bin/lms-teryt.php -u
bin/lms-teryt.php -b
bin/lms-teryt.php -b --building-base-provider gugik
bin/lms-teryt.php -b --building-base-provider sidusis
```

2. W LMS (Menu->Konfiguracja) ustaw zmienne:

**sekcja [sidusis]:**

'api_token' - token REST API wygenerowany na stronie internet.gov.pl - po zalogowaniu w edycji Profilu użytkownika,

'api_host' - uzupełnić tylko w wypadku jeśli chcemy używać instancji testowej sidusis,

'selected_division' - ID raportowanego oddziału,

'internet_demands_queueid' - ID kolejki helpdesk, w której będą tworzone tickety dotyczące zapotrzebowań na internet,

'internet_demands_categoryids' - ID kategorii helpdesk, która zostanie przypisana utworzonemu ticketowi,

**sekcja [uke]**

'sidusis_operator_offer_url' - adres URL z ofertą usług,

'sidusis_operator_phone' - telefon operatora,

**sekcja [rt]**

'lms_url' - adres WWW instancji LMS

(można wrzucać raporty z wielu oddziałów wystarczy użyć zmiennej --config i ustawiać te zmienne w różnych plikach np. /etc/lms/lms.ini i /etc/lms/lms-oddzial2.ini w sekcji [sidusis])

3. Dodaj kilka zasięgów (Osprzęt sieciowy->Zasięgi sieciowe)

4. Uruchom pierwszy import i sprawdź poprawność danych w internet.gov.pl
```bin/lms-sidusis.php --export-ranges --debug```

5. Dodaj do systemowego harmonogramu zadań (crontab) komendę wrzucającą raport z typem przyrostowym

```0 15   * * 1   root    /var/www/html/lms/bin/lms-sidusis.php -q --export-ranges```

6. Dodaj do systemowego harmonogramu zadań (crontab) komendę wrzucającą zapotrzebowania na internet jako tickety helpdesk

```*/30 * * * *   root    /var/www/html/lms/bin/lms-sidusis.php -q --import-demands```

**Pozdrawiam i miłej kawusi**

Jarosław @interduo Kłopotek, kontakt: jkl@interduo.pl

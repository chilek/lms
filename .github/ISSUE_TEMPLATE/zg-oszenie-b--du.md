---
name: Zgłoszenie błędu
about: Pozwala zgłosić problem z działaniem aplikacji LMS+
title: ''
labels: bug
assignees: ''

---

**Wstępna weryfikacja**
Zanim zgłosisz błąd spróbuj najpierw go samodzielnie rozwiązać wspomagając się artykułem
z [naszego wiki](https://github.com/chilek/lms-plus/wiki/faq#problem-z-funkcjonowaniem-aplikacji-lms-plus).

**Opis błędu**
Jasny i precyzyjny opis czym jest znaleziony błąd.

**Odtworzenie problemu**
Kroki odtwarzające błędne zachowanie:
1. Przejdź do '...'
2. Kliknij  '....'
3. Przewiń do '....'
4. Zaobserwuj błąd.

**Oczekiwane zachowanie**
Jasny i precyzyjny opis tego, czego oczekujesz, by miało miejsce.

**Zrzuty ekranu**
Jeśli dotyczy, prosimy o dodanie zrzutów ekranu, które pomogą wyjaśnić Twój problem.

**Środowisko - prosimy o uzupełnienie następującej informacji:**
 - system operacyjny, np. _Linux CentOS 8.1 x86_64_,
 - przeglądarka www, np. _Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.122 Safari/537.36_ (można odczytać pod adresem: https://www.whatismybrowser.com/detect/what-is-my-user-agent),
 - wersja LMS+ - [jak sprawdzić?](https://github.com/chilek/lms-plus/wiki/faq#wersje-lms) - w nowszych wersjach LMS+ można przy aktywnej planszy powitalnej (moduł **welcome**) kliknąć w boksie **Informacje o LMS+** przycisk **Kopiuj**. Wszystkie informacje o komponentach LMS+ zostaną skopiowane do schowka, co następnie wklejamy do raportu,
 - wyjście polecenia
    ```sh
    composer info
    ```
    wykonanego w katalogu głównym instalacji.

**Środowisko mobilne (tablet/telefon) - prosimy o uzupełnienie następujących informacji:**
 - urządzenie, np. Samsung Galaxy S10,
 - system operacyjny, np. Android 10,
 - przeglądarka www, np. przeglądarka zainstalowana fabrycznie, Google Chrome (wraz z wersją przeglądarki),

**Informacje diagnostyczne**
- [ ] Brak błędów w dzienniku zdarzeń bazy danych (dotyczy PostgreSQL). W przypadku błędów prosimy o ich przesłanie.
- [ ] Brak błędów w dzienniku zdarzeń serwera www lub daemona php FastCGI (zwykle plik error_log lub ssl_error_log w przypadku Apache/httpd).
- [ ] Wykonałem polecenie: `rm -f templates_c/* userpanel/templates_c/*`.
- [ ] Wykonałem polecenie: `composer update --no-dev`.
- [ ] Wyczyściłem pamięć podręczną przeglądarki www lub przeładowałem zawartość www z pominięciem pamięci podręcznej (w Google Chrome skrót klawiszowy SHIFT+F5).
- [ ] W konsoli deweloperskiej przeglądarki www nie ma błędów. W przypadku wystąpienia prosimy o ich przesłanie.

**Dodatkowe informacje**
Dodaj jakiekolwiek dodatkowe informacje, które mogą być pomocne w obserwacji problemu.

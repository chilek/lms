W konfiguracji utworzyæ nowy interfejs u¿ytkownika 'callcenter'.

Uzupe³niæ o opcjê:
- callcenterip - podaæ adres IP z którego ³¹czyæ siê bêd¹ agenci callcenter,
- networks - adresacja sieci, która mo¿e wyœwietlaæ formularz callcenter np. 10.10.10.0/24 (mo¿na podaæ kilka sieci oddzielonych przecinkiem), 
- queues - id kolejek w LMS, odzielone przecinkami. 

Kolejno Zg³oszenie awarii, informacja handlowa oraz sprawy finansowe,
- categories - id kategorii zg³oszeñ w LMS, odzielone przecinkami. 

Kolejno Internet, telewizja, telefon oraz ogólna, 
(UWAGA! ZACHOWANIE KOLEJNOŒCI JEST WYMAGANE DO POPRAWNEGO DZIA£ANIA.)
- queueuser - id u¿ytkownika do którego ma byæ przypisane zg³oszenie (mo¿e byæ 0),
- warning - treœæ wiadomoœci specjalnej wyœwietlanej na górze strony,
- information - mo¿liwoœæ dodanie dodatkowych informacji do wysuwaj¹cego siê panelu (np. tabela z godzinami pracy).


Skrypt który piszuje nagrania do poprawnych zg³oszeñ znajdujê siê w folderze bin. 
Skrypt wymaga rozszerzenia imap dla PHP.


Nale¿y ustawiæ go cyklicznie w cron. 

Wymagane do pobierania nagrañ z rozmów:
- hostname - nazwa hosta poczty,
- user - nazwa u¿ytkownika poczty,
- pass - has³o u¿ytkownika,
- mailfrom - nazwa maila callcenter.



Dodatkowo, nale¿y upewniæ siê czy w sekcji 'rt' utworzona jest opcja 'mail_dir' z lokalizacj¹ folderu. 
Bêdzie ona wykorzystywana do zapisywanie nagrañ z rozmów.

Numer telefonu pobierany jest na podstawie URL. System callcenter automatycznie dodaje do URL takie dane jak: ID konsultanta, nr tel. dzwoni¹cego, nr sprawy, które potem wykorzystywane s¹ w skrypcie.

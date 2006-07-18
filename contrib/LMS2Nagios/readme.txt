pyLMS2Nagios v4

Kompatybilne z LMS v1.4.3
Kompatybilne z nagios-2.3.1-1

Przemys³aw Stanis³aw Knycz <djrzulf>
psk@recesja.icm.edu.pl

Ustawienia:

1. Skrypt zak³ada instalacjê plików konfiguracyjnych nagiosa w /etc/nagios/
2. Skrypt potrafi pracowaæ z wieloma LMSami (zmienna "lmsy", dodanie kolejnego
   lmsa np: lmsy=['lms1','lms2','lms3'] etc...
3. Pierwsze zapytanie - szuka sieci, z których bêdzie wyci±ga³ hosty, które
   nie posiadaj± w³a¶ciciela. Zapytanie eliminuje z wyników adresy publiczne,
   adresy, w których urz±dzenia siê nigdy nie znajd±, sieci rozpoczynaj±ce siê
   od nazwy 'ADDR-' oraz 'IMPORT'
4. Drugie zapytanie - wybiera hosty z danej klasy adresowej.
5. Trzecie zapytanie - wybiera sieci, w których s± urz±dzenia klienckie, np
   routery, telefony/bramki VoIP, które zawsze odpowiadaj± na pingi.
6. Czwarte zapytanie - wybiera hosty ze znalezionych klas adresowych.
7. Pi±te zapytanie - wrzuca do LMS'a informacjê, ¿e siê prze³adowa³ (nie u¿ywa).


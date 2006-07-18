pyLMS2Nagios v4

Kompatybilne z LMS v1.4.3
Kompatybilne z nagios-2.3.1-1

Przemys�aw Stanis�aw Knycz <djrzulf>
psk@recesja.icm.edu.pl

Ustawienia:

1. Skrypt zak�ada instalacj� plik�w konfiguracyjnych nagiosa w /etc/nagios/
2. Skrypt potrafi pracowa� z wieloma LMSami (zmienna "lmsy", dodanie kolejnego
   lmsa np: lmsy=['lms1','lms2','lms3'] etc...
3. Pierwsze zapytanie - szuka sieci, z kt�rych b�dzie wyci�ga� hosty, kt�re
   nie posiadaj� w�a�ciciela. Zapytanie eliminuje z wynik�w adresy publiczne,
   adresy, w kt�rych urz�dzenia si� nigdy nie znajd�, sieci rozpoczynaj�ce si�
   od nazwy 'ADDR-' oraz 'IMPORT'
4. Drugie zapytanie - wybiera hosty z danej klasy adresowej.
5. Trzecie zapytanie - wybiera sieci, w kt�rych s� urz�dzenia klienckie, np
   routery, telefony/bramki VoIP, kt�re zawsze odpowiadaj� na pingi.
6. Czwarte zapytanie - wybiera hosty ze znalezionych klas adresowych.
7. Pi�te zapytanie - wrzuca do LMS'a informacj�, �e si� prze�adowa� (nie u�ywa).


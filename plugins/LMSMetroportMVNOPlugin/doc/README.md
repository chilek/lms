# "Metroport MVNO"

Wtyczka obsługuje synchronizację LMS z API Metroport MVNO w zakresie użytkowników, kont użytkowników oraz bilingów.

Synchronizacji dokonuje się za pomocą skryptu lms-metroportmvno-sync.php [--help], który jest dostępny w katalogu wtyczki. 

#### Synchronizacja użytkowników Metroport MVNO z klientami LMS (wywołanie skryptu z paramterem --customers)

Synchronizacja natępuje na podstawie porównania numrów NIP lub PESEL.
W przypadku dopasowania dla klienta LMS ustawiany jest zewnętrzny identyfikator, który odpowiada kodowi użytkownika w systemie MMS Metroport.

Zdarza się, że w LMS mamy wielu klientów z tym samym numerem NIP lub PESEL, wtedy nie jest możliwe jednoznaczne dopasowanie
(zostanie to zgłoszone w wynikach działania skryptu).
W takim przypadku operator musi sam zdecydować, którego klienta z LMS powiąże z użytkownikiem Metroport.
Aby tego dokonać w kartotece klienta LMS w panelu 'Identyfikatory w systemach zewnętrznych'
należy dodać wpis, w którym pole 'Identyfikator w systemie zewnętrznym' uzupełniamy o kod użytkownika Metroport, a jako dostawcę usług wskazujemy 'Metroport MVNO'.

#### Synchronizacja kont Metroport MVNO z kontami LMS (wywołanie skryptu z paramterem --accounts)

Synchronizacja następuje tylko dla klientów LMS, którzy zostali już zsynchronizowani z użytkownikami Metroport MVNO. W wyniku synchronizacji tworzone są konta VoIP w LMS.

#### Import cennika Metroport MVNO (wywołanie skryptu z paramterem --pricelist-file)

Do poprawnego rozliczenia biliongów należy dokonać importu pliku cennika do systemu Metroport MVNO. Plik cennika w formacie 'csv' każdy operator otrzymuje bezpośrednio od Metroport.

#### Wczytanie bilingów Metroport MVNO do LMS (wywołanie skryptu z paramterem --billings)

Synchronizacja następuje tylko dla klientów LMS, którzy zostali już zsynchronizowani z użytkownikami Metroport MVNO oraz dla kont, które zostały zsynchronizowane.
Parametry 'start-date' i 'end-date' określają okres z jakiego mają zostać wczytane bilingi. 
Jeśli nie podamy parametrów 'start-date' i 'end-date' nastąpi zaczytanie bilingów z ostatniego miesiąca sprzed daty wywołania skryptu.

#### Generowania obciążeń z tytułu billingów w LMS
Do generowania obciążeń z tytułu billingów należy użyć skryptu lms-payments.php, który jest dostepny w LMS.

#### Zarządzanie kontami klientów i billingami
Zarządzanie kontami klientów i billingami w UI LMS dostępne jest w zakładce 'Konta VoIP'.

O  lms-daemon-radius-mikrotik 
Demon lms-daemon-radius-mikrotik umożliwia wykonywanie zadań zauotoryzowanym klientom zadań na mikrotiku:
- ustawia przepustowości dla klientów
- tworzy wpisy do firewall z przekierowaniem na winietkę z ostrzeżeniem dla klientów, którzy mają włączone ostrzeżenia
- tworzy listę osób, którzy mają status odłączony

Wszystkie zadania są kolejkowanie i wykonywane z kliku sekundowym poślizgiem żeby zapobiec przeciążaniu mikrotika i bazy danych przez klientów, którzy robią up-down. Połączania pomiędzy demonem a mikrotikiem wykonywane są po ssh.

Całość instalujemy na freeradiusie, obsługuje naraz wiele mikrotików. 

Skrócony opis instalacji:
z root zainstalować: pip i virtualenv
prze-logować się na użytkownika i wykonać rzeczy z pliku install.txt 

<?php
/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2015 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  @author Marcin Romanowski <marcin@nicram.net>
 */

use \plugins\LMSNovitusHDPlugin\lib\LMSHelper;
/**
 * Class NovitusHD
 */
class NovitusHD
{
	/**
	 * @var LMSDBInterface
	 */
	protected $db;

	/**
	 * @var null|resource
	 */
	protected $socket = null;

	/**
	 * @var string
	 */
	protected $ip;

	/**
	 * @var string
	 */
	protected $port;

	/**
	 *
	 */
	const CONFIGOPTIONS = [
		'0' => 'Czas w sekundach, po jakim bezczynna drukarka ulegnie auto wyłączeniu',
		'1' => 'Czas w sekundach, po jakim bezczynna drukarka ulegnie auto wygaszeniu',
		'2' => 'Kontrast wydruku (0-9)',
		'3' => 'Drukowanie lini podkreśleń. 0 - wszystkie, 1 - tylko fiskalne, 2 - żadne',
		'5' => 'Protokół komunikacyjny. 0 - NOVITUS zgod., 1 - NOVITUS, 2 - XML',
		'6' => 'Żródlo lini dodatkowych. 0 - stopka, 1 - nagłówek',
		'7' => 'Linie dodatkowe na paragonie. 0 - brak, 1 - stałe',
		'8' => 'Linia numeru systemowego paragonu. 0 – brak, 1 – pierwsza, 2 – druga, 3 – trzecia, 4 – czwarta, 5 – piąta, 6 – ostatnia',
		'9' => 'Nazwa odesłana. 0 – HD, 1 – BONO, 2 – DELIO, 3 – QUARTO, 4 – VIVO, 5 – VENTO, 6 – WIKING, 7 – definiowana',
		'10' => 'Format odsyłanego numeru unikatowego. Dla drukarek z 13 znakowym numerem unikatowym: 0 – XXX##########, 1 – XXX########, 2 – XX########. Dla drukarek z 11 znakowym numerem unikatowym: 0 –XXX########, 1 – XX########. (X – litery, # -cyfry)',
		'11' => 'Buforowanie paragonu. 0 - nie, 1 - tak',
		'12' => 'Czujnik zwijania. 0 - włączony, 1 - wyłączony',
		'31' => 'Opcje wydruku faktury. Komunikat o wydruku kopii (faktury) na wyświetlaczu przed jej wydrukiem. 0 - włączony, 1 - wyłączony',
		'34' => 'Typ czcionki na wydrukach. 0 - zwykła, 1 - cienka',
		'35' => 'Typ czcionki w tytule wydruku. 0 - zwykła, 1 - cienka. (powiększona)',
		'36' => 'Format wydruku pozycji na fakturze. 0 - Podstawowy, 1 - Skrócony',
		'37' => 'Format wydruku pozycji na paragonie. 0 - Podstawowy, 1 - Wyrównanie cen',
//		'60' => 'Maska sieci',
//		'61' => 'Brama domyślna',
		'63' => 'Serwer HTTP. 0 - Nieaktywny, 1 - aktywny',
		'64' => 'Szerokość wydruku. 0 - 57mm, 1 - 80mm',
		'66' => 'Obcinacz. 0 - wyłączony, 1 - Pełne obcięcie, 2 - Częściowe obcięcie',
		'70' => 'Kontrola wykonania rap. dobowego. 0 - wyłączona, 1 - włączona',
		'108' => 'Aktualny adres IP',
		'116' => 'Automatyczny raport miesięczny. 0 - wyłączony, 1 - włączony',
		'117' => 'Automatyczny raport okresowy. 0 - wyłączony, 1 - włączony'
	];

	/**
	 *
	 */
	const ERRORCODES = [
		'0' => 'Brak błędów',
		'1' => 'Nie zainicjowany zegar RTC',
		'2' => 'Nieprawidłowy bajt kontrolny',
		'3' => 'Nieprawidłowa ilość parametrów',
		'4' => 'Nieprawidłowy parametr',
		'5' => 'Błąd operacji z zegarem RTC',
		'6' => 'Błąd operacji z modułem fiskalnym',
		'7' => 'Nieprawidłowa data',
		'8' => 'Błąd operacji – niezerowe totalizery',
		'9' => 'Błąd operacji wejścia/wyjścia',
		'10' => 'Przekroczony zakres danych',
		'11' => 'Nieprawidłowa ilość stawek PTU',
		'12' => 'Nieprawidłowy nagłówek',
		'13' => 'Nie można refiskalizować urządzenia',
		'14' => 'Nie można zapisać nagłówka',
		'15' => 'Nieprawidłowe linie dodatkowe',
		'16' => 'Nieprawidłowa nazwa towaru',
		'17' => 'Nieprawidłowa ilość',
		'18' => 'Nieprawidłowa stawka PTU towaru',
		'19' => 'Nieprawidłowa cena towaru',
		'20' => 'Nieprawidłowa wartość towaru',
		'21' => 'Paragon nie został rozpoczęty',
		'22' => 'Błąd operacji storno',
		'23' => 'Nieprawidłowa ilość linii paragonu',
		'24' => 'Przepełnienie bufora wydruku',
		'25' => 'Nieprawidłowy tekst lub nazwa kasjera',
		'26' => 'Nieprawidłowa wartość płatności',
		'27' => 'Nieprawidłowa wartość całkowita',
		'28' => 'Przepełnienie totalizera sprzedaży',
		'29' => 'Próba zakończenia nie rozpoczętego paragonu',
		'30' => 'Nieprawidłowa wartość płatności 2',
		'31' => 'Przepełnienie stanu kasy',
		'32' => 'Ujemny stan kasy został zastąpiony zerowym',
		'33' => 'Nieprawidłowy tekst zmiany',
		'34' => 'Nieprawidłowa wartość lub tekst',
		'35' => 'Zerowe totalizery sprzedaży',
		'36' => 'Rekord już istnieje',
		'37' => 'Anulowane przez użytkownika',
		'38' => 'Nieprawidłowy nazwa',
		'39' => 'Nieprawidłowy symbol stawki VAT',
		'40' => 'Nie zaprogramowany nagłówek',
		'41' => 'Nieprawidłowy numer kasy',
		'42' => 'Nieprawidłowy numer kasjera',
		'43' => 'Nieprawidłowy numer paragonu',
		'44' => 'Nieprawidłowa nazwa klienta',
		'45' => 'Nieprawidłowy terminal',
		'46' => 'Nieprawidłowa nazwa karty kredytowej',
		'47' => 'Nieprawidłowy numer karty kredytowej',
		'48' => 'Nieprawidłowy miesiąc ważności karty kredytowej',
		'49' => 'Nieprawidłowy rok ważności karty kredytowej',
		'50' => 'Nieprawidłowy kod autoryzacyjny',
		'51' => 'Nieprawidłowa kwota',
		'52' => '**Niepusta tablica wycen',
		'53' => '**Wartość niezgodna z wyceną',
		'54' => '**Brak wyceny leku',
		'55' => '**Brak opisu leku',
		'56' => '**Błąd kwoty OPŁATA',
		'57' => '**Przepełnienie tablicy wycen',
		'58' => 'Paragon offline pełny',
		'82' => 'Niedozwolony rozkaz',
		'83' => 'Zła wartość kaucji',
		'84' => 'Przekroczona liczba wysłanych napisów',
		'1000' => 'Błąd inicjalizacji',
		'1001' => 'Uszkodzenie pamięci RAM',
		'1002' => 'Paragon jest już rozpoczęty',
		'1003' => 'Brak identyfikatora stawki PTU',
		'1004' => 'Nieprawidłowy rabat',
		'1005' => 'Nieprawidłowe dane',
		'1006' => 'Drukarka nie jest w trybie fiskalnym',
		'1007' => 'Nie zaprogramowane stawki PTU',
		'1008' => 'Pamięć fiskalna pełna',
		'1009' => 'Nieprawidłowa suma kontrolna pamięci RAM',
		'1010' => 'Nieprawidłowa suma kontrolna bazy danych',
		'1011' => 'Nieprawidłowa suma kontrolna nagłówka',
		'1012' => 'Nieprawidłowa suma kontrolna nazwy kasjera',
		'1013' => 'Nieprawidłowa suma kontrolna numeru kasy',
		'1014' => 'Nie powiodło się uaktualnienie danych',
		'1015' => 'Nie zaprogramowany numer unikatowy',
		'1016' => 'Brak pamięci fiskalnej',
		'1017' => 'Brak mechanizmu drukującego **Kody błędów występujące w drukarkach aptecznych 199',
		'1018' => 'Brak wyświetlacza',
		'1019' => 'Pamięć fiskalna została wymieniona',
		'1020' => 'Identyczne stawki PTU są już zaprogramowane',
		'1021' => 'Urządzenie jest w trybie tylko do odczytu',
		'1022' => 'Nierozpoznany rozkaz',
		'1023' => 'Nieprawidłowy rozkaz',
		'1024' => 'Nieprawidłowy zakres raportu',
		'1025' => 'Brak danych raportu w podanym zakresie',
		'1026' => 'Przepełnienie bufora transmisji',
		'1027' => 'Niezakończony tryb fiskalny',
		'1028' => 'Uszkodzenie pamięci fiskalnej',
		'1029' => 'Przekroczony limit ograniczeń pamięci fiskalnej',
		'1030' => 'Uszkodzona mapa pamięci fiskalnej',
		'1031' => 'Rozkaz wysłany w niewłaściwym trybie',
		'1032' => 'Nieprawidłowy wskaźnik ramki',
		'1033' => '*Pamięć fiskalna jest zajęta',
		'1034' => '*Drukarka fiskalna jest zajęta',
		'1037' => 'Brak papieru',
		'1038' => 'Błąd zapisu kopii elektronicznej',
		'1039' => 'Błąd instalacji karty pamięci',
		'1040' => 'Karta pamięci została zamknięta',
		'1041' => 'Błąd weryfikacji kopii elektronicznej',
		'1042' => 'Pamięć podręczna pełna',
		'1043' => 'Nie zapisano kopii elektronicznej',
		'1044' => 'Błąd wydruku kopii elektronicznej',
		'1045' => 'Brak karty',
		'1046' => 'Brak danych',
		'1047' => 'Brak gotowości karty',
		'1048' => 'Błąd zamknięcia karty',
		'1049' => 'Błąd otwarcia karty',
		'1050' => 'Błąd pliku id.txt',
		'1051' => 'Błąd pliku no.txt',
		'1052' => 'Błąd odtwarzania bazy plików',
		'1053' => 'Błąd hasła karty pamięci',
		'1054' => 'Brak dostępu',
		'1055' => 'Błąd pamięci podręcznej',
		'1056' => 'Błąd pamięci podręcznej',
		'1057' => 'Błąd bazy kart kopii elektronicznej *Dodatkowe numery błędów występujących w drukarkach QUARTO i nowszych ',
		'1058' => 'Błąd bazy plików kopii elektronicznej',
		'1059' => 'Błąd weryfikacji kopii elektronicznej',
		'1060' => 'Błąd weryfikacji kopii elektronicznej',
		'1061' => 'Błąd formatowania karty pamięci',
		'1062' => 'Błąd dostępu wewnętrznej karty pamięci',
		'1063' => 'Błąd linii wewnętrznej kopii elektronicznej',
		'1064' => 'Błąd weryfikacji wewnętrznej kopii elektronicznej',
		'1065' => 'Brak bieżącej karty pamięci',
		'1066' => 'Błąd stanu kopii zapasowej',
		'1067' => 'Błąd podpisu kopii zapasowej',
		'1068' => 'Błąd danych weryfikacji zapisu',
		'1069' => 'Błąd weryfikacji zapisu',
		'1070' => 'Błąd graficznego nagłówka wydruku',
		'1071' => 'Niezakończony miesiąc',
		'1072' => 'Dane z nieobsługiwanego urządzenia',
		'1073' => 'Błąd grafik wydruku',
		'1074' => 'Błędna grafika wydruku',
		'1075' => 'Błędna grafika',
		'1076' => 'Grafika już zaprogramowana',
		'1084' => 'Niedozwolona wersja programu',
		'1085' => 'Niezgodność daty/czasu z ostatnim zapisem w pamięci fiskalnej',
		'1086' => 'Wykonaj zaległy raport dobowy',
		'1087' => 'Błąd IO systemu plików',
		'1088' => 'Osiągnięto limit zerowań pamięci RAM',
		'9999' => 'Błąd fatalny'
	];

	/**
	 * TaxRates
	 */
	const TAXRATES = [
		'23.00' => 'A',
		'8.00' => 'B',
		'0.00' => 'C',
		'5.00' => 'D',
		'free' => 'G'
	];

	/**
	 * NovitusHD constructor.
	 */
	public function __construct() {
		$this->db = LMSDB::getInstance(); // LMS DB Connection instance
		$this->ip = ConfigHelper::getConfig('novitus.ip_address');
		$this->port = ConfigHelper::getConfig('novitus.port');

		try {
			if (!$this->socket = socket_create(AF_INET, SOCK_STREAM, 0)) {
				return socket_strerror(socket_last_error());
			};
			set_time_limit(0);
			if (!socket_connect($this->socket, $this->ip, $this->port)) {
				return socket_strerror(socket_last_error($this->socket));
			};
		} catch (Error $errorException) {
			var_dump($errorException->getMessage());
		}

	}

	/**
	 * @param string $xml
	 * @return array|bool
	 */
	protected function parseXmlToArray($xml){
		libxml_use_internal_errors(true);

		$x = simplexml_load_string($xml);

		if (!$x) {
			libxml_clear_errors();
			return false;
		}

		$result = [];
		if(!function_exists('parseSimpleElement')) {
			function parseSimpleElement(SimpleXMLElement $xml, &$res)
			{
				$res['name'] = $xml->getName();
				$res['value'] = $xml->__toString();
				foreach ($xml->attributes() as $k => $v) {
					$res['attr'][$k] = iconv('ISO-8859-2', 'UTF-8', $v->__toString());
				}
				foreach ($xml->children() as $child) {
					parseSimpleElement($child, $res['children'][]);
				}
			}
		}
		parseSimpleElement($x, $result);

		return $result;
	}

	/**
	 * @param $data
	 * @return bool|string
	 *
	 * Write $data to socket
	 *
	 */
	protected function write($data){
		set_time_limit (10);

		$data = iconv('UTF-8', 'Windows-1250', $data);


		$length = strlen($data);
		while (true) {

			$sent = socket_write($this->socket, $data, $length);

			sleep(1);
//			usleep(1000000);
			if ($sent == false) {
				return socket_strerror(socket_last_error());
			}
			if ($sent < $length) {
				$st = substr($st, $sent);
				$length -= $sent;
			} else {
				return true;
			}
		}

		return true;
	}

	/**
	 * @return bool|string
	 *
	 * reads socket
	 */
	protected function read(){
		set_time_limit (10);

		$buf = '';
		if ($bytes = socket_recv($this->socket, $buf, 4096, MSG_DONTWAIT)){
			return iconv('Windows-1250', 'UTF-8', $buf);
		} else {
			return false;
		}
	}

	/**
	 * @param $value
	 * @return mixed
	 *
	 * Parsing value replaceing comma to dot for double
	 *
	 */
	protected function parseValue($value){
		return str_replace(',', '.', $value);
	}

	/**
	 * @param $writePacket
	 * @return bool | array
	 *
	 * function reads afeter data send to printer
	 *
	 */
	protected function readAfterWrite($writePacket){

		$count = 1;
		while ($count <= 5){
			if ($this->write($writePacket)){

				if (!$res = $this->read()) {
					$count++;
					continue;
				};

				if ($tab = $this->parseXmlToArray($res)) {
					return $tab['children'][0];
				} else {
					$count++;
					continue;
				}
			} else {
				$count++;
				continue;
			}
		}
		// if count reaches 10 times so no data and return false
		return false;

	}

	/**
	 * @return bool | array
	 *
	 * checks if printer is ready to receiving data
	 *
	 */
	public function pinterReadyStatus(){

		$checkCount = 1;
		while ($checkCount <= 10) {
			if ($data = $this->getState()) {
				if ($data['attr']['online'] === 'yes' && $data['attr']['papererror'] === 'no' && $data['attr']['printererror'] === 'no') {
					syslog(LOG_DEBUG, 'NOVITUS: Online state. Checking transactions.');
					if ($enq = $this->getENQ()) {
						if ($enq['attr']['intransaction'] === 'no') {
							syslog(LOG_DEBUG, 'NOVITUS: Printer ready.');
							return ['status' => 'OK'];
						} else {
							syslog(LOG_DEBUG, 'NOVITUS: '.trans('Printer in transaction state. You must close reciept/invoice').'.');
							return ['status' => 'NOK', 'error' => 'NOVITUS: '.trans('Printer in transaction state. You must close reciept/invoice').'.'];
						}
					}
				} else {
					syslog(LOG_DEBUG, 'NOVITUS: '.trans('Printer has errors or no paper. Check printer'));
					return ['status' => 'NOK', 'error' => trans('Printer has errors or no paper. Check printer')];
				}
			};
			syslog(LOG_DEBUG, 'NOVITUS: '.trans('No data recieved from printer. Check printer or paper').' ('.$checkCount.' of 10)');

			sleep(1);
			$checkCount++;
		}
		return ['status' => 'NOK', 'error' => trans('No data recieved from printer. Check printer or paper')];

	}

	/**
	 * @return bool
	 *
	 * checks if last sent command was executed by printer properly
	 */
	protected function isWriteSuccess(){

		$enq = $this->getENQ();
		if (!$enq) return false;

		return $enq['attr']['lastcommanderror'] === 'yes' ? false : true ;
	}


	/**
	 * @return bool
	 *
	 * Getting header form pirnter
	 *
	 */
	public function getHeader(){
		$packet = '<packet><header action="get"></header></packet>';

		return $this->readAfterWrite($packet);
	}

	/**
	 * @param array $data
	 * @return bool|string
	 *
	 * Setting header of prints
	 *
	 * format of array for each line max. 4 lines
	 * 	$header = [
	 *	 '1' => [
	 *  	'bold' => 'yes',  // yes,no
	 *		'align' => 'center', // left, center, right
	 *		'text' => 'TEXT CENTERED AND BOLD'
	 *		],
	 *	'2' => [
	 *		'bold' => 'no',
	 *		'align' => 'center',
	 *		'text' => 'Normal an centered text in second line
	 *		]
	 *	];
	 *
	 */
	public function setHeader(array $data )
	{
		$packet = '<packet><header action="set">';

		for ($i = 1; $i <= count($data); $i++){
			$packet .= '<line bold="'.$data[$i]['bold'].'" align="'.$data[$i]['align'].'">'.$data[$i]['text'].'</line>';
		}
		$packet .='</header></packet>';

		return $this->write((string)$packet);
	}


	/**
	 * @param array $data
	 * @param string $action
	 * @return array|bool|string
	 *
	 * Gets or sets configuration
	 * while get keys from CONFIGOPTIONS must be given in array(), one or more ['0' ...]
	 * while set must be given array of config option [ '0' => '60', ...  ]
	 *
	 */
	public function configure(array $data, string $action = 'get')
	{
		$packet = '<packet><config action="'.$action.'">';
		if (is_array($data)){
			if ($action === 'get'){
				foreach ($data as $c) { // TODO check confiig parameters
					$packet .= '<set id="' . $c . '"></set>';
				}
			} elseif ($action === 'set') {
				foreach ($data as $k => $v) {
					$packet .= '<set id="' . $k . '">' . $v . '</set>';
				}
			}
		}
		$packet .= '</config></packet>';

		if ($write = $this->write($packet)){
			if ($action === 'set') {
				return $write;

			} elseif ($action === 'get'){

				if (!$res = $this->read()) return false;
				$tab = $this->parseXmlToArray($res);
				foreach ($tab['children']['0']['children'] as $idx => $opt){
					$tab['children']['0']['children'][$idx]['attr']['desc'] = self::CONFIGOPTIONS[$opt['attr']['id']];
				}
				return $tab;
			}
		}

		return false;
	}

	/**
	 * @return bool
	 *
	 * Returns printer version
	 *
	 */
	public function getPrinterVersion(){
		$packet = '<packet><info action="version" version="" date=""></info></packet>';

		return $this->readAfterWrite($packet);
	}

	/**
	 * @return bool
	 *
	 * Returns printer state
	 *
	 */
	public function getState()
	{
		return $this->readAfterWrite('<packet><dle/></packet>');
	}

	/**
	 * @return bool
	 */
	public function getENQ()
	{
		return $this->readAfterWrite('<packet><enq/></packet>');
	}


	/**
	 * @return bool
	 *
	 * Returns fiscal memory data
	 *
	 */
	public function getFiscalMemory(){
		$packet = '<packet><info action="fiscalmemory"></info></packet>';
		return $this->readAfterWrite($packet);
	}

	/**
	 * @param null $date
	 * @return bool|string|array
	 *
	 * Sets date and time on printer. if no args given current date is set
	 *
	 */
	public function setDateTime($date = null){
		$date = new DateTime($date);

		$packet = '<packet><clock date="'.$date->format('d-m-Y H:i').'"></clock></packet>';

		if ($this->write($packet)) {

			if ($this->isWriteSuccess()) {
				return ['status' => 'OK'];
			} else {
				$err = $this->getLastError();

				return ['status' => 'NOK', 'error' => trans('Sent data included logical errors. Check data').': '.trans($err['data']['attr']['desc'])];
			}
		} else {
			syslog(LOG_DEBUG, 'NOVITUS: '.trans('Error sending data'));

			return ['status' => 'NOK', 'error' => trans('Error sending data')];
		}

	}
	/**
	 * @return mixed
	 *
	 * Returns programmed Tax Rates in printer memory. Should be identical with TAXRATES
	 *
	 */
	public function getTaxRates()
	{
		$packet = '<packet><taxrates action="get"></taxrates></packet>';
		$res = $this->readAfterWrite($packet);

		return $res['children'];
	}

	/**
	 * @param string $type
	 * @return bool
	 */
	public function getCashInformation(string $type = 'invoice'){
		$packet = '<packet><info action="checkout" type="'.$type.'" lasterror="?" isfiscal="?" receiptopen="?" lastreceipterror="?" resetcount="?" date="?" receiptcount="?" cash="?" uniqueno="?" lastreceipt="?" lastinvoice="?" lastprintout="?"></info></packet>';

		return $this->readAfterWrite($packet);
	}

	/**
	 * @param $value
	 * @return string
	 */
	protected function getFiscalPTUFromInvoiceItem($value){

		foreach (self::TAXRATES as $key => $type) {
			if (str_replace('%', '', $value) == str_replace('%', '', $key)){
				return $type;
			}
		}
		return 'A';
	}

	/**
	 * @return bool
	 */
	public function getCurrentTransactionState()
	{
		$packet = '<packet><info action="transaction"></info></packet>';

		return $this->readAfterWrite($packet);
	}

	/**
	 * @return bool
	 */
	public function getLastTransactionState()
	{
		$packet = '<packet><info action="lasttransaction"></info></packet>';

		return $this->readAfterWrite($packet);
	}

	/**
	 * @param integer $invoice
	 * @return array| bool
	 *
	 * Fiscalizes invocie. as argument document id
	 */
	public function printInvoice(int $invoice)
	{
		global $LMS;
		$invoice = $LMS->GetInvoiceContent($invoice);
		$invNo = $invoice['template'];
		$invNo = str_replace('%N', $invoice['number'], $invNo);
		$invNo = str_replace('%m', $invoice['month'], $invNo);
		$invNo = str_replace('%Y', $invoice['year'], $invNo);
		$invoice['humanNo'] = $invNo;

		// Check if this invoice is fiscalized
		if (LMSHelper::isInvoiceFiscalized($invoice['id']))
			return ['status' => 'NOK', 'reason' => 2, 'error' => 'Faktura <strong>'.$invNo.'</strong> była już zafiskalizowana. Nie drukuję.'];

		// If printer is ready prepare data and send it to printer
		$printerStatsus = $this->pinterReadyStatus();

		if ($printerStatsus['status'] === 'OK'){
			$data = '';
			$peselData = $invoice['ssn'] ? 'pesel=": '.$invoice['ssn'].'"' : '';
			/*
			 * option 1 - dont print "do zaplaty slownie"
			 * option 2 - pomin kwoty brutto w rozliczeniu podatku
			 * option 3 - pogrubiona etykieta "nabywca"
			 * option 4 - pogrubiona etykieta "sprzedawca"
			 * option 5 - pogrubiony NIP nabywcy
			 * option 6 - pogrubiony NIP sprzedawcy
			 * option 7 - wydruk etykiety "opis/symbol" w nagłówku faktury przed pozycjami faktury
			 * option 8 - wydruk numeru pozycji w pozycji fakutry
			 * option 9 - etykieta "do zaplaty" przed blokiem rozliczenia podatkow
			 * option 10 - wydruk ilosci groszy w postaci slownej
			 * option 11 - nie drukuj daty sprzedazy jeśli taka sama jak wystawienia
			 * option 12 - nie drukuj danych sprzedawcy
			 * option 13 - nie drukuj opisow do pozycji faktury
			 * option 14 - wylaczona obsluga platnosci
			 * option 15 - nie drukuj danych odbiorcy
			 * option 16 - drukuj napis "Zaplacono gotowka"
			 * option 17 - nie drukuj etykiety "sprzedawca"
			 * option 18 - pomin etykietye ORYGINAL (dziala tylko w przypdku braku kopi do wydrukowania)
			 * option 19 - drukuj etykiete "FAKTURA VAT" zamiast "FAKTURA"
			 */

			$data .= '<invoice 
						action="begin" 
						number="'.$invNo.'" 
						description="original" 
						paymentname="'.$invoice['paytypename'].'" 
						selldate="'.date('d/m/Y', $invoice['cdate']).'" 
						paymentdate="'.date('d/m/Y', $invoice['pdate']).'" 
						copies="255" 
						margins="no" 
						signarea="no" 
						customernameoptions="none" 
						sellernameoptions="none" 
						nip="'.$invoice['ten'].'" 
						paidlabel="" >
							<customer>'.$invoice['name'].'</customer>
							<customer>'.$invoice['address'].', '.$invoice['zip'].' '.$invoice['city'].'</customer>
							<option id="1" />
							<option id="2" />
							<option id="11" />
							<option id="12" />
							<option id="17" />
							<option id="19" />
					</invoice>';
			foreach ($invoice['content'] as $item){
				$data .= '<item name="'.substr(preg_replace('/\s+/', ' ', $item['description']), 0, 57).'..." quantity="'.(int)$item['count'].'" quantityunit="'.$item['content'].'" ptu="'.$this->getFiscalPTUFromInvoiceItem($item['taxvalue']).'" price="'.$this->parseValue($item['value']).'" action="sale" description="'. preg_replace('/\s+/', ' ', $item['description']) .'" />';

			}
			$data .= '<invoice action="close"'.$peselData.' total="'.$this->parseValue($invoice['total']).'" systemno="'.$invoice['customerid'].'"></invoice>';

			$data = preg_replace('/\t+|\r+|\n/', '', $data); // remove all not needed tabs, new lines to prevent errors in printer
			$data = '<packet>'.$data.'</packet>';

			if ($this->write($data)) {

				if ($this->isWriteSuccess()) {
					$this->db->Execute("INSERT INTO novitus_fiscalized_invoices (doc_id, fiscalized) VALUES (?, ?)", [$invoice['id'], true]);
					return ['status' => 'OK', 'data' => $invoice];
				} else {
					syslog(LOG_DEBUG, 'NOVITUS: '. trans('Sent data included logical errors. Check data'));
					return ['status' => 'NOK', 'error' => trans('Sent data included logical errors. Check data').' - '.$invNo];
				}
			} else {
				syslog(LOG_DEBUG, 'NOVITUS: '.trans('Error sending data').' - '.$invoice['id']);

				return ['status' => 'NOK', 'error' => trans('Error sending data').' - '.$invNo];
			}
		} else {
			return ['status' => 'NOK', 'error' => $printerStatsus['error']];
		}

	}

	/**
	 * @return bool|string
	 *
	 * cancel opened invoice
	 *
	 */
	public function cancelInvoice()
	{
		return $this->write('<packet><invoice action="cancel"></invoice></packet>');
	}

	/**
	 * @param null $date
	 * @return bool|string|array
	 *
	 * Prints daily report for given date. If no date args prints report for current day
	 *
	 */
	public function printDailyReport($date = null){
		$date = new DateTime($date);

		$packet = '<packet><report type="daily" date="'.$date->format('d-m-Y').'"></report></packet>';

		if ($this->write($packet)) {

			if ($this->isWriteSuccess()) {
				return ['status' => 'OK', 'data' => trans('Report task has been sent to printer')];
			} else {
				$err = $this->getLastError();
				return ['status' => 'NOK', 'error' => trans('Error sending data').' - '.$err['attr']['desc']];
			}
		} else {
			syslog(LOG_DEBUG, 'NOVITUS: '.trans('Error sending data'));

			return ['status' => 'NOK', 'error' => trans('Error sending data')];
		}
	}

	/**
	 * @param $dateFrom
	 * @param $dateTo
	 * @param string $kind
	 * @return bool|string|array
	 *
	 * Prints periods reports
	 *
	 * Kind options:
	 * monthlyfull - Monthly full - fiscalized
	 * full - Full from given dates - fiscalized
	 * salesummary - Sales summary - not fiscalized
	 * monthlysummary - Monthly summary - not fiscalized
	 * billingfull - Full financial settlement
	 * billingsummary - Financial settlement from given dates
	 */
	public function printPeriodReport($dateFrom, $dateTo, $kind = 'monthlyfull')
	{
		$from = new DateTime($dateFrom);
		$to = new DateTime($dateTo);

		if ($to - $from < 0) return false;

		$packet = '<packet><report type="periodical" from="'.$from->format('d-m-Y').'" to="'.$to->format('d-m-Y').'" kind="'.$kind.'"></report></packet>';

		if ($this->write($packet)) {

			if ($this->isWriteSuccess()) {
				return ['status' => 'OK', 'data' => trans('Report task has been sent to printer')];
			} else {
				$err = $this->getLastError();
				return ['status' => 'NOK', 'error' => trans('Error sending data').' - '.$err['attr']['desc']];
			}
		} else {
			syslog(LOG_DEBUG, 'NOVITUS: '.trans('Error sending data'));

			return ['status' => 'NOK', 'error' => trans('Error sending data')];
		}
	}

	/**
	 * @param string $type
	 * @return array
	 *
	 * Setting display errors
	 * type:
	 *      silent - don't show errors on printer's display
	 *      display - show errors on printer's display and waits for operator reaction
	 *
	 */
	public function setErrorHandler($type = 'silent'){ // silent, display

		$packet = '<packet><error action="set" value="'.$type.'"></error></packet>';

		$this->write($packet);
		if($this->isWriteSuccess()){
			syslog(LOG_DEBUG, 'NOVITUS: Error handling set to printer');
			return ['status' => 'OK'];
		} else {
			syslog(LOG_DEBUG, 'NOVITUS: Error sending data');
			return ['status' => 'NOK', 'error' => trans('Error sending data')];
		}

	}

	/**
	 * @return array
	 *
	 * Gets Last occoured error and returns name of error.
	 *
	 */
	public function getLastError()
	{
		$packet = '<packet><error action="get"></error></packet>';

		if ($res = $this->readAfterWrite($packet)) {
			$res['attr']['desc'] = self::ERRORCODES[$res['attr']['value']];
			return ['status' => 'OK', 'data' => $res];
		} else {
			return ['status' => 'NOK', 'error' => trans('Error reading data')];
		}
	}
}


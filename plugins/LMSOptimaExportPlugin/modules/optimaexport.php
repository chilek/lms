<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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
 *  $Id$
 */

function form_num($num) {
	return str_replace(',','.', sprintf('%.2f',f_round($num)));
}


function parse_address($address) {
	if (!($res = preg_match('/^(?<street>.+)\s+(?<house>[0-9][0-9a-z]*(?:\/[0-9][0-9a-z]*)?)(?:\s+|\s*(?:\/|m\.?|lok\.?)\s*)(?<flat>[0-9a-z]+)$/i', $address, $m)))
		if (!($res = preg_match('/^(?<street>.+)\s+(?<house>[0-9][0-9a-z]*)$/i', $address, $m)))
			$res = preg_match('/^(?<street>.+)$/i', $address, $m);
	if (!$res)
		return null;
	return array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
}

if (isset($_GET['type']) && !empty($_POST)) {
	$eol = "\n";

	header('Content-Type: application/octetstream');
	header('Content-Disposition: attachment; filename=' . strftime('optima-%Y%m%d-%H%M%S.xml'));
	header('Pragma: public');

	echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>$eol";
	echo "<ROOT xmlns=\"http://www.comarch.pl/cdn/optima/offline\">$eol";

	if ($_POST['from']) {
		list($year, $month, $day) = explode('/', $_POST['from']);
		$from = mktime(0, 0, 0, $month, $day, $year);
	} else
		$from = mktime(0, 0, 0);

	if ($_POST['to']) {
		list($year, $month, $day) = explode('/', $_POST['to']);
		$to = mktime(23, 59, 59, $month, $day, $year);
	} else
		$to = mktime(23, 59, 59);

	$customers = array();

	$invoices = $DB->GetAllByKey('SELECT d.id AS docid, d.type AS doctype, d.customerid, c.name, c.lastname,
		d.name AS customername, c.type AS ctype, c.status, d.address, d.zip, d.city, post_name,
		post_address, post_zip, post_city, d.ten, d.ssn, p.phones, f.fax, m.email
		FROM documents d
		JOIN customersview c ON c.id = d.customerid
		LEFT JOIN (SELECT ' . $DB->GroupConcat('contact') . ' AS phones, customerid FROM customercontacts
			WHERE (type & ?) > 0 GROUP BY customerid
		) p ON p.customerid = c.id
		LEFT JOIN (SELECT ' . $DB->GroupConcat('contact') . ' AS fax, customerid FROM customercontacts
			WHERE (type & ?) > 0 GROUP BY customerid
		) f ON f.customerid = c.id
		LEFT JOIN (SELECT ' . $DB->GroupConcat('contact') . ' AS email, customerid FROM customercontacts
			WHERE (type & ?) > 0 GROUP BY customerid
		) m ON m.customerid = c.id
		WHERE (d.type = ? OR d.type = ?) AND (cdate BETWEEN ? AND ?)
		ORDER BY cdate, d.id', 'docid', array(CONTACT_MOBILE | CONTACT_LANDLINE, CONTACT_FAX, CONTACT_EMAIL,
			DOC_INVOICE, DOC_CNOTE, $from, $to));

	if (!empty($invoices)) {
		$scustomers = '';
		$sinvoices = '';
		foreach ($invoices as &$invoice) {
			foreach ($invoice as &$value)
				$value = trim($value);
			$extcustomerid = sprintf("kl%06d", $invoice['customerid']);
			$finalcustomer = $invoice['ctype'] == CTYPES_PRIVATE;
			$address = parse_address($invoice['address']);
			if (!array_key_exists($invoice['customerid'], $customers)) {
				$customers[$invoice['customerid']] = true;
				$scustomers .= "\t<KONTRAHENT>$eol";
				$scustomers .= "\t\t<ID_ZRODLA><![CDATA[${invoice['customerid']}]]></ID_ZRODLA>$eol";
				$scustomers .= "\t\t<AKRONIM><![CDATA[$extcustomerid]]></AKRONIM>$eol";
				$scustomers .= "\t\t<RODZAJ>odbiorca</RODZAJ>$eol";
				$scustomers .= "\t\t<EKSPORT>krajowy</EKSPORT>$eol";
				$scustomers .= "\t\t<FINALNY>" . ($finalcustomer ? "Tak" : "Nie") . "</FINALNY>$eol";
				$scustomers .= "\t\t<PLATNIK_VAT>" . ($finalcustomer ? "Nie" : "Tak"). "</PLATNIK_VAT>$eol";
				$scustomers .= "\t\t<MEDIALNY>Nie</MEDIALNY>$eol";
				$scustomers .= "\t\t<NIEAKTYWNY>" . ($invoice['status'] == CSTATUS_CONNECTED ? "Nie" : "Tak") . "</NIEAKTYWNY>$eol";
				$scustomers .= "\t\t<ADRES>$eol";
				$scustomers .= "\t\t\t<STATUS>aktualny</STATUS>$eol";
				$scustomers .= "\t\t\t<NAZWA1><![CDATA[]]></NAZWA1>$eol";
				$scustomers .= "\t\t\t<NAZWA2><![CDATA[" . $invoice['customername'] . "]]></NAZWA2>$eol";
				$scustomers .= "\t\t\t<NAZWA3><![CDATA[]]></NAZWA3>$eol";
				$scustomers .= "\t\t\t<KRAJ><![CDATA[Polska]]></KRAJ>$eol";
				$scustomers .= "\t\t\t<ULICA><![CDATA[${address['street']}]]></ULICA>$eol";
				$scustomers .= "\t\t\t<NR_DOMU><![CDATA[${address['house']}]]></NR_DOMU>$eol";
				$scustomers .= "\t\t\t<NR_LOKALU><![CDATA[" . (array_key_exists('flat', $address) ? $address['flat'] : '') . "]]></NR_LOKALU>$eol";
				$scustomers .= "\t\t\t<MIASTO><![CDATA[${invoice['city']}]]></MIASTO>$eol";
				$scustomers .= "\t\t\t<KOD_POCZTOWY><![CDATA[${invoice['zip']}]]></KOD_POCZTOWY>$eol";
				$scustomers .= "\t\t\t<POCZTA><![CDATA[${invoice['city']}]]></POCZTA>$eol";
				$scustomers .= "\t\t\t<NIP_KRAJ></NIP_KRAJ>$eol";
				$scustomers .= "\t\t\t<NIP><![CDATA[${invoice['ten']}]]></NIP>$eol";
				$scustomers .= "\t\t\t<PESEL><![CDATA[${invoice['ssn']}]]></PESEL>$eol";
				if (!empty($invoice['phones'])) {
					$phones = explode(',', $invoice['phones']);
					$phone1 = $phones[0];
					if (count($phones) > 1)
						$phone2 = count($phones) > 1 ? $phones[1] : '';
				} else
					$phone1 = $phone2 = '';
				$scustomers .= "\t\t\t<TELEFON1><![CDATA[$phone1]]></TELEFON1>$eol";
				$scustomers .= "\t\t\t<TELEFON2><![CDATA[$phone2]]></TELEFON2>$eol";
				if (!empty($invoice['fax']))
					$fax = preg_replace('/,.+$/', '', $invoice['fax']);
				else
					$fax = '';
				$scustomers .= "\t\t\t<FAX><![CDATA[$fax]]></FAX>$eol";
				if (!empty($invoice['email']))
					$email = preg_replace('/,.+$/', '', $invoice['email']);
				else
					$email = '';
				$scustomers .= "\t\t\t<EMAIL><![CDATA[$email]]></EMAIL>$eol";
				$scustomers .= "\t\t</ADRES>$eol";

				$scustomers .= "\t\t<ADRES_KORESPONDENCYJNY>$eol";
				$scustomers .= "\t\t\t<KORESP_KRAJ><![CDATA[Polska]]></KORESP_KRAJ>$eol";
				$scustomers .= "\t\t\t<KORESP_MIASTO><![CDATA[" . (!empty($invoice['post_city']) ? $invoice['post_city'] : $invoice['city']) . "]]></KORESP_MIASTO>$eol";
				if (!empty($invoice['post_address']))
					$address = parse_address($invoice['post_address']);
				$scustomers .= "\t\t\t<KORESP_ULICA><![CDATA[${address['street']}]]></KORESP_ULICA>$eol";
				$scustomers .= "\t\t\t<KORESP_NR_DOMU><![CDATA[${address['house']}]]></KORESP_NR_DOMU>$eol";
				$scustomers .= "\t\t\t<KORESP_NR_LOKALU><![CDATA[" . (array_key_exists('flat', $address) ? $address['flat'] : '') . "]]></KORESP_NR_LOKALU>$eol";
				$scustomers .= "\t\t\t<KORESP_KOD_POCZTOWY><![CDATA[" . (!empty($invoice['post_zip']) ? $invoice['post_zip'] : $invoice['zip']) . "]]></KORESP_KOD_POCZTOWY>$eol";
				$scustomers .= "\t\t\t<KORESP_POCZTA><![CDATA[" . (!empty($invoice['post_city']) ? $invoice['post_city'] : $invoice['city']) . "]]></KORESP_POCZTA>$eol";
				$scustomers .= "\t\t</ADRES_KORESPONDENCYJNY>$eol";

				$scustomers .= "\t</KONTRAHENT>$eol";
			}

			$doc = $LMS->GetInvoiceContent($invoice['docid']);
			$number = docnumber($doc['number'], $doc['template'], $doc['cdate']);
			if ($invoice['doctype'] == DOC_CNOTE)
				$cnotenumber = docnumber($doc['invoice']['number'], $doc['invoice']['template'], $doc['invoice']['cdate']);
			else
				$cnotenumber = '';
			$sinvoices .= "\t<REJESTR_SPRZEDAZY_VAT>$eol";
			$sinvoices .= "\t\t<ID_ZRODLA><![CDATA[${invoice['docid']}]]></ID_ZRODLA>$eol";
			$sinvoices .= "\t\t<MODUL>Handel</MODUL>$eol";
			$sinvoices .= "\t\t<REJESTR><![CDATA[]]></REJESTR>$eol";
			$sinvoices .= "\t\t<DATA_WYSTAWIENIA><![CDATA[" . strftime("%Y-%m-%d", $doc['cdate']) . "]]></DATA_WYSTAWIENIA>$eol";
			$sinvoices .= "\t\t<DATA_SPRZEDAZY><![CDATA[" . strftime("%Y-%m-%d", $doc['sdate']) . "]]></DATA_SPRZEDAZY>$eol";
			$sinvoices .= "\t\t<TERMIN><![CDATA[" . strftime("%Y-%m-%d", $doc['pdate']) . "]]></TERMIN>$eol";
			$sinvoices .= "\t\t<DATA_DATAOBOWIAZKUPODATKOWEGO><![CDATA[" . strftime("%Y-%m-%d", $doc['sdate']) . "]]></DATA_DATAOBOWIAZKUPODATKOWEGO>$eol";
			$sinvoices .= "\t\t<DATA_DATAPRAWAODLICZENIA><![CDATA[" . strftime("%Y-%m-%d", $doc['sdate']) . "]]></DATA_DATAPRAWAODLICZENIA>$eol";
			$sinvoices .= "\t\t<NUMER><![CDATA[$number]]></NUMER>$eol";
			$sinvoices .= "\t\t<KOREKTA>" . (!empty($cnotenumber) ? "Tak" : "Nie") . "</KOREKTA>$eol";
			$sinvoices .= "\t\t<KOREKTA_NUMER><![CDATA[$cnotenumber]]></KOREKTA_NUMER>$eol";
			$sinvoices .= "\t\t<WEWNETRZNA>Nie</WEWNETRZNA>$eol";
			$sinvoices .= "\t\t<FISKALNA>" . ($finalcustomer ? "Tak" : "Nie") . "</FISKALNA>$eol";
			$sinvoices .= "\t\t<DETALICZNA>Nie</DETALICZNA>$eol";
			$sinvoices .= "\t\t<EKSPORT>Nie</EKSPORT>$eol";
			$sinvoices .= "\t\t<FINALNY>" . ($finalcustomer ? "Tak" : "Nie") . "</FINALNY>$eol";
			$sinvoices .= "\t\t<PODATNIK_CZYNNY>" . ($finalcustomer ? "Nie" : "Tak") . "</PODATNIK_CZYNNY>$eol";
			$sinvoices .= "\t\t<TYP_PODMIOTU>kontrahent</TYP_PODMIOTU>$eol";
			$sinvoices .= "\t\t<PODMIOT><![CDATA[$extcustomerid]]></PODMIOT>$eol";
			$sinvoices .= "\t\t<NAZWA1><![CDATA[${invoice['customername']}]]></NAZWA1>$eol";
			$sinvoices .= "\t\t<NAZWA2><![CDATA[]]></NAZWA2>$eol";
			$sinvoices .= "\t\t<NAZWA3><![CDATA[]]></NAZWA3>$eol";
			$sinvoices .= "\t\t<NIP_KRAJ></NIP_KRAJ>$eol";
			$sinvoices .= "\t\t<NIP><![CDATA[${invoice['ten']}]]></NIP>$eol";
			$sinvoices .= "\t\t<KRAJ><![CDATA[]]></KRAJ>$eol";
			$sinvoices .= "\t\t<ULICA><![CDATA[${address['street']}]]></ULICA>$eol";
			$sinvoices .= "\t\t<NR_DOMU><![CDATA[${address['house']}]]></NR_DOMU>$eol";
			$sinvoices .= "\t\t<NR_LOKALU><![CDATA[" . (array_key_exists('flat', $address) ? $address['flat'] : '') . "]]></NR_LOKALU>$eol";
			$sinvoices .= "\t\t<MIASTO><![CDATA[${invoice['city']}]]></MIASTO>$eol";
			$sinvoices .= "\t\t<KOD_POCZTOWY><![CDATA[${invoice['zip']}]]></KOD_POCZTOWY>$eol";
			$sinvoices .= "\t\t<POCZTA><![CDATA[${invoice['city']}]]></POCZTA>$eol";
			$sinvoices .= "\t\t<PESEL><![CDATA[${invoice['ssn']}]]></PESEL>$eol";
			$sinvoices .= "\t\t<TYP_PLATNIKA>kontrahent</TYP_PLATNIKA>$eol";
			$sinvoices .= "\t\t<PLATNIK><![CDATA[$extcustomerid]]></PLATNIK>$eol";
			$sinvoices .= "\t\t<FORMA_PLATNOSCI><![CDATA[${doc['paytypename']}]]></FORMA_PLATNOSCI>$eol";
			$sinvoices .= "\t\t<POZYCJE>$eol";

			foreach ($doc['content'] as $pos) {
				$sinvoices .= "\t\t\t<POZYCJA>$eol";
				$sinvoices .= "\t\t\t\t<STAWKA_VAT>" . form_num($pos['taxvalue']) . "</STAWKA_VAT>$eol";
				$sinvoices .= "\t\t\t\t<STATUS_VAT>opodatkowana</STATUS_VAT>$eol";
				$sinvoices .= "\t\t\t\t<NETTO>" . form_num($pos['totalbase']) . "</NETTO>$eol";
				$sinvoices .= "\t\t\t\t<VAT>" . form_num($pos['totaltax']) . "</VAT>$eol";
				$sinvoices .= "\t\t\t\t<NETTO_SYS>" . form_num($pos['totalbase']) . "</NETTO_SYS>$eol";
				$sinvoices .= "\t\t\t\t<VAT_SYS>" . form_num($pos['totaltax']) . "</VAT_SYS>$eol";
				$sinvoices .= "\t\t\t\t<NETTO_SYS2>" . form_num($pos['totalbase']) . "</NETTO_SYS2>$eol";
				$sinvoices .= "\t\t\t\t<VAT_SYS2>" . form_num($pos['totaltax']) . "</VAT_SYS2>$eol";
				$sinvoices .= "\t\t\t\t<RODZAJ_SPRZEDAZY>usługi</RODZAJ_SPRZEDAZY>$eol";
				$sinvoices .= "\t\t\t\t<UWZ_W_PROPORCJI>Tak</UWZ_W_PROPORCJI>$eol";
				$sinvoices .= "\t\t\t\t<KOLUMNA_KPR><![CDATA[Sprzedaż]]></KOLUMNA_KPR>$eol";
				$sinvoices .= "\t\t\t\t<OPIS_POZ><![CDATA[${pos['description']}]]></OPIS_POZ>$eol";
				$sinvoices .= "\t\t\t\t<OPIS_POZ_2><![CDATA[]]></OPIS_POZ_2>$eol";
				$sinvoices .= "\t\t\t</POZYCJA>$eol";
			}
			$sinvoices .= "\t\t</POZYCJE>$eol";

			$sinvoices .= "\t\t<PLATNOSCI>$eol";
			$sinvoices .= "\t\t\t<PLATNOSC>$eol";
			$sinvoices .= "\t\t\t\t<ID_ZRODLA_PLAT><![CDATA[${invoice['docid']}]]></ID_ZRODLA_PLAT>$eol";
			$sinvoices .= "\t\t\t\t<TERMIN_PLAT><![CDATA[" . strftime("%Y-%m-%d", $doc['pdate']) . "]]></TERMIN_PLAT>$eol";
			$sinvoices .= "\t\t\t\t<FORMA_PLATNOSCI_PLAT><![CDATA[${doc['paytypename']}]]></FORMA_PLATNOSCI_PLAT>$eol";
			$sinvoices .= "\t\t\t\t<WALUTA_PLAT><![CDATA[]]></WALUTA_PLAT>$eol";
			$sinvoices .= "\t\t\t\t<KWOTA_PLN_PLAT>" . form_num(abs($doc['total'])) . "</KWOTA_PLN_PLAT>$eol";
			$sinvoices .= "\t\t\t\t<KWOTA_PLAT>" . form_num(abs($doc['total'])) . "</KWOTA_PLAT>$eol";
			$sinvoices .= "\t\t\t\t<KIERUNEK>" . ($doc['total'] < 0 ? "rozchód" : "przychód") . "</KIERUNEK>$eol";
			$sinvoices .= "\t\t\t\t<DATA_KURSU_PLAT><![CDATA[" . strftime("%Y-%m-%d", $doc['cdate']) . "]]></DATA_KURSU_PLAT>$eol";
			$sinvoices .= "\t\t\t\t<WALUTA_DOK><![CDATA[]]></WALUTA_DOK>$eol";
			$sinvoices .= "\t\t\t\t<PLATNOSC_TYP_PODMIOTU>kontrahent</PLATNOSC_TYP_PODMIOTU>$eol";
			$sinvoices .= "\t\t\t\t<PLATNOSC_PODMIOT><![CDATA[$extcustomerid]]></PLATNOSC_PODMIOT>$eol";
			$sinvoices .= "\t\t\t\t<PLATNOSC_PODMIOT_RACHUNEK_NR><![CDATA[" . bankaccount($invoice['customerid'], $doc['account']) . "]]></PLATNOSC_PODMIOT_RACHUNEK_NR>$eol";
			$sinvoices .= "\t\t\t</PLATNOSC>$eol";
			$sinvoices .= "\t\t</PLATNOSCI>$eol";

			$sinvoices .= "\t</REJESTR_SPRZEDAZY_VAT>$eol";

			$invoice['topay'] = $doc['total'];
		}

		echo "<KONTRAHENCI>$eol";
		echo "\t<WERSJA>2.00</WERSJA>$eol";
		echo "\t<BAZA_ZRD_ID></BAZA_ZRD_ID>$eol";
		echo "\t<BAZA_DOC_ID>KS</BAZA_DOC_ID>$eol";
		echo "$scustomers";
		echo "</KONTRAHENCI>$eol";

		echo "<REJESTRY_SPRZEDAZY_VAT>$eol";
		echo "\t<WERSJA>2.00</WERSJA>$eol";
		echo "\t<BAZA_ZRD_ID></BAZA_ZRD_ID>$eol";
		echo "\t<BAZA_DOC_ID>KS</BAZA_DOC_ID>$eol";
		echo "$sinvoices";
		echo "</REJESTRY_SPRZEDAZY_VAT>$eol";
	}

	$settlements = array();
	$ssettlements = '';

	$cids = $DB->GetCol('SELECT DISTINCT customerid FROM cash
		WHERE (time BETWEEN ? AND ?)', array($from, $to));
//	$cids = array(187);

	foreach ($cids as $cid) {
		$finances = $DB->GetAll('SELECT c.id, time, value, comment, c.docid,
				d.number, d.cdate, d.type AS doctype, p.template FROM cash c
			LEFT JOIN documents d ON d.id = c.docid
			LEFT JOIN numberplans p ON p.id = d.numberplanid
			WHERE c.customerid = ? AND value <> 0 AND time <= ?
			ORDER BY time, docid', array($cid, $to));

		$currdoc = null;
		$balance = 0;
		foreach ($finances as $finance) {
			$oldbalance = $balance;
			$balance += $finance['value'];
//			if ($finance['time'] >= $from && $firstidx == 0)
//				$firstidx = count($finances2);
			if ($finance['docid'] == 0) {
				if (!empty($currdoc))
					$finances2[] = array(
						'value' => $currdoc['value'],
						'tosettle' => $currdoc['value'],
						'docid' => $currdoc['docid'],
						'doctype' => $currdoc['doctype'],
						'date' => $currdoc['cdate'],
//						'cdate' => strftime('%Y-%m-%d', $currdoc['cdate']),
						'number' => $currdoc['number'],
						'template' => $currdoc['template'],
//						'docnumber' => docnumber($currdoc['number'], $currdoc['template'], $currdoc['cdate']),
						'income' => $currdoc['doctype'] != DOC_INVOICE,
						'balance' => form_num($oldbalance),
					);
				$finance2 = array(
					'value' => $finance['value'],
					'tosettle' => $finance['value'],
					'id' => $finance['id'],
					'date' => $finance['time'],
//					'cdate' => strftime('%Y-%m-%d', $finance['time']),
					'comment' => $finance['comment'],
					'income' => $finance['value'] >= 0,
					'balance' => form_num($balance),
				);
				if ($finance['value'] < 0) {
					$finance2['docid'] = $finance['id'];
					$finance2['number'] = $finance2['template'] = trans('Payment $a', $finance['id']);
				}
				$finances2[] = $finance2;
				$currdoc = null;
				continue;
			}
			if (empty($currdoc)) {
				$currdoc = $finance;
				continue;
			}
			if ($currdoc['docid'] == $finance['docid'])
				$currdoc['value'] += $finance['value'];
			else {
				$finances2[] = array(
					'value' => $currdoc['value'],
					'tosettle' => $currdoc['value'],
					'docid' => $currdoc['docid'],
					'doctype' => $currdoc['doctype'],
					'date' => $currdoc['cdate'],
//					'cdate' => strftime('%Y-%m-%d', $currdoc['cdate']),
					'number' => $currdoc['number'],
					'template' => $currdoc['template'],
//					'docnumber' => docnumber($currdoc['number'], $currdoc['template'], $currdoc['cdate']),
					'income' => $currdoc['doctype'] != DOC_INVOICE,
					'balance' => form_num($oldbalance),
				);
				$currdoc = $finance;
			}
		}
		if (!empty($currdoc))
			$finances2[] = array(
				'value' => $currdoc['value'],
				'tosettle' => $currdoc['value'],
				'docid' => $currdoc['docid'],
				'doctype' => $currdoc['doctype'],
				'date' => $currdoc['cdate'],
//				'cdate' => strftime('%Y-%m-%d', $currdoc['cdate']),
				'number' => $currdoc['number'],
				'template' => $currdoc['template'],
//				'docnumber' => docnumber($currdoc['number'], $currdoc['template'], $currdoc['cdate']),
				'income' => $currdoc['doctype'] != DOC_INVOICE,
				'balance' => form_num($balance),
			);

		$finances = $finances2;
		unset($finances2);

		$iidx = 0;

//		print_r($finances);

		foreach ($finances as $idx => &$finance) {
			// searching for liability transaction which is not fully settled
			if (!$finance['tosettle'] || $finance['income'])
				continue;
			while ($iidx < count($finances)) {
				// searching for income transaction which is not fully settled
				if (!$finances[$iidx]['income'] || !$finances[$iidx]['tosettle']) {
					$iidx++;
					continue;
				}

				$settle = $finances[$iidx]['tosettle'];

//				echo "=======$eol";
//				echo "i_tosettle=$settle$eol";
//				echo "l_tosettle=" . $finance['tosettle'] . "$eol";

				if ($settle + $finance['tosettle'] > 0)
					$settle = -$finance['tosettle'];

//				echo "settle=$settle$eol";
//				echo "=======$eol";

				$finances[$iidx]['tosettle'] -= $settle;
				$finance['tosettle'] += $settle;

				if (($finances[$iidx]['date'] >= $from && $finances[$iidx]['date'] <= $to)
					|| ($finance['date'] >= $from && $finance['date'] <= $to))
					$settlements[$finances[$iidx]['id']] = array(
						'id' => $finance['docid'] . '_' . (array_key_exists('docid', $finances[$iidx]) ? $finances[$iidx]['docid'] : $finances[$iidx]['id']),
						'leftid' => $finance['docid'],
//						'leftnumber' => $finance['docnumber'],
						'leftnumber' => docnumber($finance['number'], $finance['template'], $finance['date']),
						'rightid' => array_key_exists('docid', $finances[$iidx]) ? $finances[$iidx]['docid'] : 'p' . $finances[$iidx]['id'],
						'rightnumber' => array_key_exists('docid', $finances[$iidx])
							? docnumber($finances[$iidx]['number'], $finances[$iidx]['template'], $finances[$iidx]['date'])
							: trans('Payment $a', $finances[$iidx]['id']),
						'righttype' => array_key_exists('docid', $finances[$iidx]) ? $finances[$iidx]['doctype'] : 0,
						'date' => $finances[$iidx]['date'] >= $from && $finances[$iidx]['date'] <= $to
							? $finances[$iidx]['date'] : $finance['date'],
//						'cdate' => $finances[$iidx]['cdate'],
						'value' => $settle,
					);
				if (!$finances[$iidx]['tosettle'])
					$iidx++;
				if (!$finance['tosettle'])
					break;
			}
		}
	}

//	print_r($settlements);
	ksort($settlements);

	foreach ($settlements as $settlement) {
		$ssettlements .= "\t<ROZLICZENIE>$eol";
		$ssettlements .= "\t\t<ID_ZRODLA><![CDATA[${settlement['id']}]]></ID_ZRODLA>$eol";
		$ssettlements .= "\t\t<NUMER_LEWEGO_DOKUMENTU><![CDATA[${settlement['leftnumber']}]]></NUMER_LEWEGO_DOKUMENTU>$eol";
		$ssettlements .= "\t\t<ROWID_LEWEGO><![CDATA[${settlement['leftid']}]]></ROWID_LEWEGO>$eol";
		$ssettlements .= "\t\t<NUMER_PRAWEGO_DOKUMENTU><![CDATA[${settlement['rightnumber']}]]></NUMER_PRAWEGO_DOKUMENTU>$eol";
		$ssettlements .= "\t\t<ROWID_PRAWEGO><![CDATA[${settlement['rightid']}]]></ROWID_PRAWEGO>$eol";
		$ssettlements .= "\t\t<DATA_DOKUMENTU><![CDATA[" . strftime("%Y-%m-%d", $settlement['date']) . "]]></DATA_DOKUMENTU>$eol";
		$ssettlements .= "\t\t<TYP_LEWEGO_DOKUMENTU>zdarzenie</TYP_LEWEGO_DOKUMENTU>$eol";
		$ssettlements .= "\t\t<TYP_PRAWEGO_DOKUMENTU>" . ($settlement['righttype'] == DOC_CNOTE ? "zdarzenie" : "zapis") . "</TYP_PRAWEGO_DOKUMENTU>$eol";
		$ssettlements .= "\t\t<KWOTA>" . form_num($settlement['value']) . "</KWOTA>$eol";
		$ssettlements .= "\t</ROZLICZENIE>$eol";
	}

	echo "<ROZLICZENIA>$eol";
	echo "\t<WERSJA>2.00</WERSJA>$eol";
	echo "\t<BAZA_ZRD_ID></BAZA_ZRD_ID>$eol";
	echo "\t<BAZA_DOC_ID>KS</BAZA_DOC_ID>$eol";
	echo "$ssettlements";
	echo "</ROZLICZENIA>$eol";

	echo "</ROOT>$eol";
	die;
}

$layout['pagetitle'] = trans('Optima Export');

$SMARTY->display('optimaexport.html');

?>

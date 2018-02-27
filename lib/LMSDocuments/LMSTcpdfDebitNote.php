<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

class LMSTcpdfDebitNote extends LMSTcpdfInvoice {
	public function __construct($title, $pagesize = 'A4', $orientation = 'portrait') {
		parent::__construct('LMSTcpdfBackend', $title, $pagesize, $orientation);
	}

	public function note_date() {
		$this->backend->SetFont('arial', '', 10);
		$this->backend->writeHTMLCell(0, 0, '', 20, trans('Draw-up date:') . ' <b>' . date("d.m.Y", $this->data['cdate']) . '</b>', 0, 1, 0, true, 'R');
		$this->backend->writeHTMLCell(0, 0, '', '', trans('Deadline:') . ' <b>' . date("d.m.Y", $this->data['pdate']) . '</b>', 0, 1, 0, true, 'R');
	}

	public function note_title() {
		$this->backend->SetY(35);
		$this->backend->SetFont('arial', 'B', 16);
		$docnumber = docnumber(array(
			'number' => $this->data['number'],
			'template' => $this->data['template'],
			'cdate' => $this->data['cdate'],
			'customerid' => $this->data['customerid'],
		));
		$title = trans('Debit Note No. $a', $docnumber);

		$this->backend->Write(0, $title, '', 0, 'C', true, 0, false, false, 0);
	}

	public function note_drawer() {
		$this->backend->SetFont('arial', '', 10);
		$drawer = '<b>' . trans('Note drawer:') . '</b><br>';
		$tmp = $this->data['division_header'];

		$accounts = array(bankaccount($this->data['customerid'], $this->data['account']));
		if (ConfigHelper::checkConfig('invoices.show_all_accounts'))
			$accounts = array_merge($accounts, $this->data['bankaccounts']);
		foreach ($accounts as &$account)
			$account = format_bankaccount($account);
		$tmp = str_replace('%bankaccount', implode("\n", $accounts), $tmp);

		$tmp = preg_split('/\r?\n/', $tmp);
		foreach ($tmp as $line)
			$drawer .= $line . '<br>';
		$this->backend->Ln(0);
		$this->backend->writeHTMLCell(80, '', '', 45, $drawer, 0, 1, 0, true, 'L');
	}

	public function note_recipient() {
		$oldy = $this->backend->GetY();

		$recipient = '<b>' . trans('Note recipient:') . '</b><br>';

		$recipient .= $this->data['name'] . '<br>';
		$recipient .= $this->data['address'] . '<br>';
		$recipient .= $this->data['zip'] . ' ' . $this->data['city'] . '<br>';
		if ($this->data['ten'])
			$recipient .= trans('TEN') . ': ' . $this->data['ten'];
		elseif ($this->data['ssn'])
			$recipient .= trans('SSN') . ': ' . $this->data['ssn'];
		$this->backend->SetFont('arial', '', 10);
		$this->backend->writeHTMLCell(80, '', 120, 50, $recipient, 0, 1, 0, true, 'L');

		$y = $this->backend->GetY();

		if (ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.customer_bankaccount', true))) {
			$bankaccount = trans('Bank account:') .' <b>' . format_bankaccount(bankaccount($this->data['customerid'], $this->data['account'])) . '</b>';
			$this->backend->SetFont('arial', 'B', 8);
			$this->backend->writeHTMLCell('', '', 120, '', $bankaccount, 0, 1, 0, true, 'L');
		}

		if (ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.customer_credentials', true))) {
			$pin = '<b>' . trans('Customer ID: $a', sprintf('%04d', $this->data['customerid'])) . '</b><br>';
			$pin .= '<b>PIN: ' . sprintf('%04d', $this->data['customerpin']) . '</b><br>';

			$this->backend->SetFont('arial', 'B', 8);
			$this->backend->writeHTMLCell('', '', 120, '', $pin, 0, 1, 0, true, 'L');
		}

		$this->backend->SetY($y);
	}

	public function note_data() {
		/* print table */
		$this->backend->Ln(20);
		$this->backend->writeHTMLCell('', '', '', '', '', 0, 1, 0, true, 'L');

		$this->backend->SetFillColor(200, 200, 200);
		$this->backend->SetTextColor(0);
		$this->backend->SetDrawColor(0, 0, 0);
		$this->backend->SetLineWidth(0.3);
		$this->backend->SetFont('arial', 'B', 8);

		$margins = $this->backend->getMargins();
		$table_width = $this->backend->getPageWidth() - ($margins['left'] + $margins['right']);

		/* headers */
		$heads['no'] = trans('No.');
		$heads['name'] = trans('Title:');
		$heads['total'] = trans('Value:');

		/* width of the columns */
		foreach ($heads as $name => $text)
			$h_width[$name] = $this->backend->getWrapStringWidth($text, 'B');

		/* change the column widths if are wider than the header */
		if ($this->data['content'])
			foreach ($this->data['content'] as $item) {
				$t_width['no'] = 7;
				$t_width['name'] = $this->backend->getStringWidth($item['description']);
				$t_width['total'] = $this->backend->getStringWidth(moneyf($item['value'])) + 1;
			}

		foreach ($t_width as $name => $w)
			if ($w > $h_width[$name])
				$h_width[$name] = $w;

		/* dynamic setting the width of the table 'name' */
		$sum = 0;
		foreach ($h_width as $name => $w)
			if ($name != 'name')
				$sum += $w;
		$h_width['name'] = $table_width - $sum;

		$h_head = 6;
		/* data table headers */
		foreach ($heads as $item => $name) {
			$h_cell = $this->backend->getStringHeight($h_width[$item], $heads[$item], true, false, 0, 1);
			if ($h_cell > $h_head)
				$h_head = $h_cell;
		}
		foreach ($heads as $item => $name)
			if($item == 'name')
				$this->backend->MultiCell($h_width[$item], $h_head, $heads[$item], 1, 'L', true, 0, '', '', true, 0, false, false, $h_head, 'M');
			else
				$this->backend->MultiCell($h_width[$item], $h_head, $heads[$item], 1, 'C', true, 0, '', '', true, 0, false, false, $h_head, 'M');

		$this->backend->Ln();
		$this->backend->SetFont('arial', '', 8);

		/* data */
		$i = 1;
		foreach ($this->data['content'] as $item) {
			$this->backend->Cell($h_width['no'], 6, $i . '.', 1, 0, 'C', 0, '', 1);
			$this->backend->Cell($h_width['name'], 6, $item['description'], 1, 0, 'L', 0, '', 1);
			$this->backend->Cell($h_width['total'], 6, moneyf($item['value']), 1, 0, 'R', 0, '', 1);
			$this->backend->Ln();
			$i++;
		}

		/* summary table - headers */
		$sum = 0;
		foreach ($h_width as $name => $w)
			if (in_array($name, array('no', 'name')))
				$sum += $w;

		$this->backend->SetFont('arial', 'B', 8);
		$this->backend->Cell($sum, 5, trans('Total:'), 0, 0, 'R', 0, '', 1);
		$this->backend->SetFont('arial', '', 8);
		$this->backend->Cell($h_width['total'], 5, moneyf($this->data['value']), 1, 0, 'R', 0, '', 1);
		$this->backend->Ln();
		$this->backend->Ln(3);
	}

	protected function invoice_to_pay() {
		$this->backend->Ln(0);
		$this->backend->SetFont('arial', 'B', 14);
		$this->backend->writeHTMLCell(0, 0, '', '', trans('To pay:') . ' ' . moneyf($this->data['value']), 0, 1, 0, true, 'R');

		$this->backend->SetFont('arial', '', 10);
		$this->backend->writeHTMLCell(0, 6, '', '', trans('In words:') . ' ' . moneyf_in_words($this->data['value']), 0, 1, 0, true, 'R');
	}

	public function Draw($note) {
		$this->data = $note;

		$this->invoice_no_accountant();
		$this->note_date();
		$this->invoice_expositor();
		$this->note_title();
		$this->note_drawer();
		$this->note_recipient();
		$this->note_data();
		$this->invoice_to_pay();
		$this->invoice_footnote();
		$docnumber = docnumber(array(
			'number' => $this->data['number'],
			'template' => $this->data['template'],
			'cdate' => $this->data['cdate'],
			'customerid' => $this->data['customerid'],
		));
		$this->backend->SetTitle(trans('Debit Note No. $a', $docnumber));
		$this->backend->SetAuthor($this->data['division_name']);
		$this->backend->setBarcode($docnumber);

		/* setup your cert & key file */
		$cert = 'file://' . LIB_DIR . '/tcpdf/config/lms.cert';
		$key = 'file://' . LIB_DIR . '/tcpdf/config/lms.key';

		/* setup signature additional information */
		$info = array(
			'Name' => $this->data['division_name'],
			'Location' => trans('Debit Notes'),
			'Reason' => trans('Debit Note No. $a', $docnumber),
			'ContactInfo' => $this->data['division_author']
		);

		/* set document digital signature & protection */
		if (file_exists($cert) && file_exists($key)) {
			$this->backend->setSignature($cert, $key, 'lms-debitnote', '', 1, $info);
			$this->backend->setSignatureAppearance(13, 10, 50, 20);
		}
		$this->backend->SetProtection(array('modify', 'annot-forms', 'fill-forms', 'extract', 'assemble'), '', 'PASSWORD_CHANGEME', '1');
	}
}

?>

<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

/*  To improve performance of TCPDF
 *  install and configure a PHP opcode cacher like XCache
 *  http://xcache.lighttpd.net/
 *  This reduces execution time by ~30-50%
 */

require_once(LIB_DIR . '/tcpdf/config/lang/pol.php');
require_once(LIB_DIR . '/tcpdf/tcpdf.php');

class TCPDFpl extends TCPDF {

	public $invoice_type;

	/* convert UTF-8 to ISO-8859-2 */

	protected function UTF8ToLatin1($str) {
		if (!$this->isunicode) {
			return $str;
		}
		if (function_exists('mb_convert_encoding'))
			return mb_convert_encoding($str, "ISO-8859-2", "UTF-8");
		else
			return iconv("UTF-8", "ISO-8859-2", $str);
	}

	/* set own Header function */

	public function Header() {
		/* insert your own logo in lib/tcpdf/images/logo.png */
		$image_file = K_PATH_IMAGES . 'logo.png';
		if (file_exists($image_file))
			$this->Image($image_file, 13, 10, 50, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
	}

	/* set own Footer function */

	public function Footer() {
		$cur_y = $this->y;
		$this->SetTextColor(0, 0, 0);
		$line_width = 0.85 / $this->k;
		$this->SetLineStyle(array('width' => $line_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		/* print barcode with invoice number in footer */
		$barcode = $this->getBarcode();
		if (!empty($barcode) && ($this->invoice_type == 'standard')) {
			$this->Ln($line_width);
			$style = array(
					'position' => 'L',
					'align' => 'L',
					'stretch' => false,
					'fitwidth' => true,
					'cellfitalign' => '',
					'border' => false,
					'padding' => 0,
					'fgcolor' => array(0, 0, 0),
					'bgcolor' => false,
					'text' => true,
					'font' => 'times',
					'fontsize' => 6,
					'stretchtext' => 0
			);
			$this->write1DBarcode($barcode, 'C128', '', $cur_y + $line_width - 0.25, '', ($this->footer_margin - 2), 0.3, $style, '');
			/* draw line */
			$this->SetY($cur_y);
			$this->SetX($this->original_rMargin);
			$this->Cell(0, 0, '', array('T' => array('width' => 0.1)), 0, 'L');
		}
	}

	public function getWrapStringWidth($txt, $font_style) {
		$long = '';
		if ($words = explode(' ', $txt)) {
			foreach ($words as $word)
				if (strlen($word) > strlen($long))
					$long = $word;
		} else {
			$long = $txt;
		}

		return $this->getStringWidth($long, '', $font_style) + 2.5;
	}

	public function Table($header, $invoice) {
		/* set the line width and headers font */
		$this->SetFillColor(200, 200, 200);
		$this->SetTextColor(0);
		$this->SetDrawColor(0, 0, 0);
		$this->SetLineWidth(0.3);
		$this->SetFont('arial', 'B', 8);

		$margins = $this->getMargins();
		$table_width = $this->getPageWidth() - ($margins['left'] + $margins['right']);

		/* invoice headers */
		$heads['no'] = trans('No.');
		$heads['name'] = trans('Name of Product, Commodity or Service:');
		$heads['prodid'] = trans('Product ID:');
		$heads['content'] = trans('Unit:');
		$heads['count'] = trans('Amount:');
		if (!empty($invoice['pdiscount']) || !empty($invoice['vdiscount']))
			$heads['discount'] = trans('Discount:');
		$heads['basevalue'] = trans('Unitary Net Value:');
		$heads['totalbase'] = trans('Net Value:');
		$heads['taxlabel'] = trans('Tax Rate:');
		$heads['totaltax'] = trans('Tax Value:');
		$heads['total'] = trans('Gross Value:');

		/* width of the columns on the invoice */
		foreach ($heads as $name => $text)
		//$h_width[$name] = $this->getStringWidth($text, '', 'B', 8);
			$h_width[$name] = $this->getWrapStringWidth($text, 'B');

		/* change the column widths if are wider than the header */
		if ($invoice['content'])
			foreach ($invoice['content'] as $item) {
				$t_width['no'] = 7;
				$t_width['name'] = $this->getStringWidth($item['description']);
				$t_width['prodid'] = $this->getStringWidth($item['prodid']);
				$t_width['content'] = $this->getStringWidth($item['content']);
				$t_width['count'] = $this->getStringWidth(sprintf('%.2f', $item['count']));
				if (!empty($invoice['pdiscount']))
					$t_width['discount'] = $this->getStringWidth(sprintf('%.2f%%', $item['pdiscount']));
				elseif (!empty($invoice['vdiscount']))
					$t_width['discount'] = $this->getStringWidth(moneyf($item['vdiscount'])) + 1;
				$t_width['basevalue'] = $this->getStringWidth(moneyf($item['basevalue'])) + 1;
				$t_width['totalbase'] = $this->getStringWidth(moneyf($item['totalbase'])) + 1;
				$t_width['taxlabel'] = $this->getStringWidth($item['taxlabel']) + 1;
				$t_width['totaltax'] = $this->getStringWidth(moneyf($item['totaltax'])) + 1;
				$t_width['total'] = $this->getStringWidth(moneyf($item['total'])) + 1;
			}

		foreach ($t_width as $name => $w)
			if ($w > $h_width[$name])
				$h_width[$name] = $w;

		if (isset($invoice['invoice']['content']))
			foreach ($invoice['invoice']['content'] as $item) {
				$t_width['no'] = 7;
				$t_width['name'] = $this->getStringWidth($item['description']);
				$t_width['prodid'] = $this->getStringWidth($item['prodid']);
				$t_width['content'] = $this->getStringWidth($item['content']);
				$t_width['count'] = $this->getStringWidth(sprintf('%.2f', $item['count']));
				if (!empty($invoice['pdiscount']))
					$t_width['discount'] = $this->getStringWidth(sprintf('%.2f%%', $item['pdiscount']));
				elseif (!empty($invoice['vdiscount']))
					$t_width['discount'] = $this->getStringWidth(moneyf($item['vdiscount'])) + 1;
				$t_width['basevalue'] = $this->getStringWidth(moneyf($item['basevalue'])) + 1;
				$t_width['totalbase'] = $this->getStringWidth(moneyf($item['totalbase'])) + 1;
				$t_width['taxlabel'] = $this->getStringWidth($item['taxlabel']) + 1;
				$t_width['totaltax'] = $this->getStringWidth(moneyf($item['totaltax'])) + 1;
				$t_width['total'] = $this->getStringWidth(moneyf($item['total'])) + 1;
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

		$h_head = 0;
		/* invoice data table headers */
		foreach ($heads as $item => $name) {
			//$this->Cell($h_width[$item], 7, $heads[$item], 1, 0, 'C', 1, '', 1);
			$h_cell = $this->getStringHeight($h_width[$item], $heads[$item], true, false, 0, 1);
			if ($h_cell > $h_head)
				$h_head = $h_cell;
		}
		foreach ($heads as $item => $name)
			$this->MultiCell($h_width[$item], $h_head, $heads[$item], 1, 'C', true, 0, '', '', true, 0, false, false, $h_head, 'M');

		$this->Ln();
		$this->SetFont('arial', '', 8);

		/* invoice correction data */
		if (isset($invoice['invoice'])) {
			$this->Ln(3);
			$this->writeHTMLCell(0, 0, '', '', '<b>' . trans('Was:') . '</b>', 0, 1, 0, true, 'L');
			$this->Ln(3);
			$i = 1;
			if ($invoice['invoice']['content'])
				foreach ($invoice['invoice']['content'] as $item) {
					$this->Cell($h_width['no'], 6, $i . '.', 1, 0, 'C', 0, '', 1);
					$this->Cell($h_width['name'], 6, $item['description'], 1, 0, 'L', 0, '', 1);
					$this->Cell($h_width['prodid'], 6, $item['prodid'], 1, 0, 'C', 0, '', 1);
					$this->Cell($h_width['content'], 6, $item['content'], 1, 0, 'C', 0, '', 1);
					$this->Cell($h_width['count'], 6, sprintf('%.2f', $item['count']), 1, 0, 'C', 0, '', 1);
					if (!empty($invoice['pdiscount']))
						$this->Cell($h_width['discount'], 6, sprintf('%.2f%%', $item['pdiscount']), 1, 0, 'R', 0, '', 1);
					elseif (!empty($invoice['vdiscount']))
						$this->Cell($h_width['discount'], 6, moneyf($item['vdiscount']), 1, 0, 'R', 0, '', 1);
					$this->Cell($h_width['basevalue'], 6, moneyf($item['basevalue']), 1, 0, 'R', 0, '', 1);
					$this->Cell($h_width['totalbase'], 6, moneyf($item['totalbase']), 1, 0, 'R', 0, '', 1);
					$this->Cell($h_width['taxlabel'], 6, $item['taxlabel'], 1, 0, 'C', 0, '', 1);
					$this->Cell($h_width['totaltax'], 6, moneyf($item['totaltax']), 1, 0, 'R', 0, '', 1);
					$this->Cell($h_width['total'], 6, moneyf($item['total']), 1, 0, 'R', 0, '', 1);
					$this->Ln();
					$i++;
				}

			/* invoice correction summary table - headers */
			$sum = 0;
			foreach ($h_width as $name => $w)
				if (in_array($name, array('no', 'name', 'prodid', 'content', 'count', 'discount', 'basevalue')))
					$sum += $w;

			$this->SetFont('arial', 'B', 8);
			$this->Cell($sum, 5, trans('Total:'), 0, 0, 'R', 0, '', 1);
			$this->SetFont('arial', '', 8);
			$this->Cell($h_width['totalbase'], 5, moneyf($invoice['invoice']['totalbase']), 1, 0, 'R', 0, '', 1);
			$this->SetFont('arial', 'B', 8);
			$this->Cell($h_width['taxlabel'], 5, 'x', 1, 0, 'C', 0, '', 1);
			$this->SetFont('arial', '', 8);
			$this->Cell($h_width['totaltax'], 5, moneyf($invoice['invoice']['totaltax']), 1, 0, 'R', 0, '', 1);
			$this->Cell($h_width['total'], 5, moneyf($invoice['invoice']['total']), 1, 0, 'R', 0, '', 1);
			$this->Ln();

			/* invoice correction summary table - data */
			if ($invoice['invoice']['taxest']) {
				$i = 1;
				foreach ($invoice['invoice']['taxest'] as $item) {
					$this->SetFont('arial', 'B', 8);
					$this->Cell($sum, 5, trans('in it:'), 0, 0, 'R', 0, '', 1);
					$this->SetFont('arial', '', 8);
					$this->Cell($h_width['totalbase'], 5, moneyf($item['base']), 1, 0, 'R', 0, '', 1);
					$this->Cell($h_width['taxlabel'], 5, $item['taxlabel'], 1, 0, 'C', 0, '', 1);
					$this->Cell($h_width['totaltax'], 5, moneyf($item['tax']), 1, 0, 'R', 0, '', 1);
					$this->Cell($h_width['total'], 5, moneyf($item['total']), 1, 0, 'R', 0, '', 1);
					$this->Ln(12);
					$i++;
				}
			}

			/* reason of issue of invoice correction */
			if ($invoice['reason'] != '') {
				$this->writeHTMLCell(0, 0, '', '', '<b>' . trans('Reason:') . ' ' . $invoice['reason'] . '</b>', 0, 1, 0, true, 'L');
				$this->writeHTMLCell(0, 0, '', '', '<b>' . trans('Corrected to:') . '</b>', 0, 1, 0, true, 'L');
				$this->Ln(3);
			}
		}

		/* invoice data */
		$i = 1;
		foreach ($invoice['content'] as $item) {
			$this->Cell($h_width['no'], 6, $i . '.', 1, 0, 'C', 0, '', 1);
			$this->Cell($h_width['name'], 6, $item['description'], 1, 0, 'L', 0, '', 1);
			$this->Cell($h_width['prodid'], 6, $item['prodid'], 1, 0, 'C', 0, '', 1);
			$this->Cell($h_width['content'], 6, $item['content'], 1, 0, 'C', 0, '', 1);
			$this->Cell($h_width['count'], 6, sprintf('%.2f', $item['count']), 1, 0, 'C', 0, '', 1);
			if (!empty($invoice['pdiscount']))
				$this->Cell($h_width['discount'], 6, sprintf('%.2f%%', $item['pdiscount']), 1, 0, 'R', 0, '', 1);
			elseif (!empty($invoice['vdiscount']))
				$this->Cell($h_width['discount'], 6, moneyf($item['vdiscount']), 1, 0, 'R', 0, '', 1);
			$this->Cell($h_width['basevalue'], 6, moneyf($item['basevalue']), 1, 0, 'R', 0, '', 1);
			$this->Cell($h_width['totalbase'], 6, moneyf($item['totalbase']), 1, 0, 'R', 0, '', 1);
			$this->Cell($h_width['taxlabel'], 6, $item['taxlabel'], 1, 0, 'C', 0, '', 1);
			$this->Cell($h_width['totaltax'], 6, moneyf($item['totaltax']), 1, 0, 'R', 0, '', 1);
			$this->Cell($h_width['total'], 6, moneyf($item['total']), 1, 0, 'R', 0, '', 1);
			$this->Ln();
			$i++;
		}

		/* invoice summary table - headers */
		$sum = 0;
		foreach ($h_width as $name => $w)
			if (in_array($name, array('no', 'name', 'prodid', 'content', 'count', 'discount', 'basevalue')))
				$sum += $w;

		$this->SetFont('arial', 'B', 8);
		$this->Cell($sum, 5, trans('Total:'), 0, 0, 'R', 0, '', 1);
		$this->SetFont('arial', '', 8);
		$this->Cell($h_width['totalbase'], 5, moneyf($invoice['totalbase']), 1, 0, 'R', 0, '', 1);
		$this->SetFont('arial', 'B', 8);
		$this->Cell($h_width['taxlabel'], 5, 'x', 1, 0, 'C', 0, '', 1);
		$this->SetFont('arial', '', 8);
		$this->Cell($h_width['totaltax'], 5, moneyf($invoice['totaltax']), 1, 0, 'R', 0, '', 1);
		$this->Cell($h_width['total'], 5, moneyf($invoice['total']), 1, 0, 'R', 0, '', 1);
		$this->Ln();

		/* invoice summary table - data */
		if ($invoice['taxest']) {
			$i = 1;
			foreach ($invoice['taxest'] as $item) {
				$this->SetFont('arial', 'B', 8);
				$this->Cell($sum, 5, trans('in it:'), 0, 0, 'R', 0, '', 1);
				$this->SetFont('arial', '', 8);
				$this->Cell($h_width['totalbase'], 5, moneyf($item['base']), 1, 0, 'R', 0, '', 1);
				$this->Cell($h_width['taxlabel'], 5, $item['taxlabel'], 1, 0, 'C', 0, '', 1);
				$this->Cell($h_width['totaltax'], 5, moneyf($item['tax']), 1, 0, 'R', 0, '', 1);
				$this->Cell($h_width['total'], 5, moneyf($item['total']), 1, 0, 'R', 0, '', 1);
				$this->Ln();
				$i++;
			}
		}

		$this->Ln(3);
		/* difference between the invoice and the invoice correction */
		if (isset($invoice['invoice'])) {
			$total = $invoice['total'] - $invoice['invoice']['total'];
			$totalbase = $invoice['totalbase'] - $invoice['invoice']['totalbase'];
			$totaltax = $invoice['totaltax'] - $invoice['invoice']['totaltax'];

			$this->SetFont('arial', 'B', 8);
			$this->Cell($sum, 5, trans('Difference value:'), 0, 0, 'R', 0, '', 1);
			$this->SetFont('arial', '', 8);
			$this->Cell($h_width['totalbase'], 5, moneyf($totalbase), 1, 0, 'R', 0, '', 1);
			$this->SetFont('arial', 'B', 8);
			$this->Cell($h_width['taxlabel'], 5, 'x', 1, 0, 'C', 0, '', 1);
			$this->SetFont('arial', '', 8);
			$this->Cell($h_width['totaltax'], 5, moneyf($totaltax), 1, 0, 'R', 0, '', 1);
			$this->Cell($h_width['total'], 5, moneyf($total), 1, 0, 'R', 0, '', 1);
			$this->Ln();
		}
	}

}

function init_pdf($pagesize, $orientation, $title) {
	global $layout, $CONFIG;

	$pdf = new TCPDFpl($orientation, PDF_UNIT, $pagesize, true, 'UTF-8', false, false);
	$pdf->invoice_type = $CONFIG['invoices']['template_file'];

	$pdf->SetProducer('LMS Developers');
	$pdf->SetSubject($title);
	$pdf->SetCreator('LMS ' . $layout['lmsv']);
	$pdf->SetDisplayMode('fullwidth', 'SinglePage', 'UseNone');

	$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
	$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
	$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
	$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

	$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
	$pdf->setLanguageArray($l);
	/* disable font subsetting to improve performance */
	$pdf->setFontSubsetting(false);

	$pdf->AddPage();
	return $pdf;
}

function close_pdf(&$pdf, $name = false) {
	ob_clean();
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');
	if (!empty($name))
		$pdf->Output($name, 'D');
	else
		$pdf->Output();
}

?>

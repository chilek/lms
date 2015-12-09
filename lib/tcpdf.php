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

/*  To improve performance of TCPDF
 *  install and configure a PHP opcode cacher like XCache
 *  http://xcache.lighttpd.net/
 *  This reduces execution time by ~30-50%
 */

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'pol.php');

class LMSTCPDF extends TCPDF {
	public $invoice_type;

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
		if (!empty($barcode) && ConfigHelper::getConfig('invoices.template_file') == 'standard') {
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

	public function SetProducer($producer) {
		$this->producer = $producer;
	}
}

function new_page() {
	global $pdf;
	$pdf->AddPage();
}

function init_pdf($pagesize, $orientation, $title) {
	global $layout;

	$pdf = new LMSTCPDF($orientation, PDF_UNIT, $pagesize, true, 'UTF-8', false, false);
	$pdf->invoice_type = ConfigHelper::getConfig('invoices.template_file');

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

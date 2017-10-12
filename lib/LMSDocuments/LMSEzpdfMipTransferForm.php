<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

class LMSEzpdfMipTransferForm extends LMSDocument {
	protected $id;

	public function __construct($title, $pagesize = 'A4', $orientation = 'portrait') {
		parent::__construct('LMSEzpdfBackend', $title, $pagesize, $orientation);

		$this->backend->setLineStyle(2);
	}

	protected function main_fill($x, $y, $scale) {
		if (ConfigHelper::checkConfig('finances.control_lines')) {
			$this->backend->line(7 * $scale + $x, 115 * $scale + $y, 7 * $scale + $x, 145 * $scale + $y);
			$this->backend->line(7 * $scale + $x, 115 * $scale + $y, 37 * $scale + $x, 115 * $scale + $y);
			$this->backend->line(978 * $scale + $x, 115 * $scale + $y, 978 * $scale + $x, 145 * $scale + $y);
			$this->backend->line(978 * $scale + $x, 115 * $scale + $y, 948 * $scale + $x, 115 * $scale + $y);
			$this->backend->line(7 * $scale + $x, 726 * $scale + $y, 7 * $scale + $x, 696 * $scale + $y);
			$this->backend->line(7 * $scale + $x, 726 * $scale + $y, 37 * $scale + $x, 726 * $scale + $y);
			$this->backend->line(978 * $scale + $x, 726 * $scale + $y, 978 * $scale + $x, 696 * $scale + $y);
			$this->backend->line(978 * $scale + $x, 726 * $scale + $y, 948 * $scale + $x, 726 * $scale + $y);
		}
		$this->backend->addtext(15 * $scale + $x, 680 * $scale + $y, 30 * $scale, $this->data['d_name']);
		$this->backend->addtext(15 * $scale + $x, 617 * $scale + $y, 30 * $scale, $this->data['d_address'] . ' ' . $this->data['d_zip'] . ' ' . $this->data['d_city']);
		$this->backend->addtext(15 * $scale + $x, 555 * $scale + $y, 30 * $scale, format_bankaccount(bankaccount($this->data['id'], $this->data['account'])));
		$this->backend->addtext(550 * $scale + $x, 497 * $scale + $y, 30 * $scale, number_format($this->data['total'],2,',',''));
		$this->backend->addtext(15 * $scale + $x, 375 * $scale + $y, 30 * $scale, $this->data['customername']);
		$this->backend->addtext(15 * $scale + $x, 315 * $scale + $y, 30 * $scale, $this->data['address'] . '; ' . $this->data['zip'] . ' ' . $this->data['city']);
		$this->backend->addtext(15 * $scale + $x, 250 * $scale + $y, 30 * $scale, trans('Payment for invoice No. $a', $this->data['t_number']));
	}

	protected function simple_fill_mip($x, $y, $scale) {
		if (ConfigHelper::checkConfig('finances.control_lines')) {
			$this->backend->line(7 * $scale + $x, 180 * $scale + $y, 7 * $scale + $x, 210 * $scale + $y);
			$this->backend->line(7 * $scale + $x, 180 * $scale + $y, 37 * $scale + $x, 180 * $scale + $y);
			$this->backend->line(370 * $scale + $x, 180 * $scale + $y, 370 * $scale + $x, 210 * $scale + $y);
			$this->backend->line(370 * $scale + $x, 180 * $scale + $y, 340 * $scale + $x, 180 * $scale + $y);
			$this->backend->line(7 * $scale + $x, 726 * $scale + $y, 7 * $scale + $x, 696 * $scale + $y);
			$this->backend->line(7 * $scale + $x, 726 * $scale + $y, 37 * $scale + $x, 726 * $scale + $y);
			$this->backend->line(370 * $scale + $x, 726 * $scale + $y, 370 * $scale + $x, 696 * $scale + $y);
			$this->backend->line(370 * $scale + $x, 726 * $scale + $y, 340 * $scale + $x, 726 * $scale + $y);
		}
		$this->backend->addtext(15 * $scale + $x, 560 * $scale + $y, 30 * $scale, $this->data['d_shortname']);
		$this->backend->addtext(15 * $scale + $x, 525 * $scale + $y, 30 * $scale, $this->data['d_address']);
		$this->backend->addtext(15 * $scale + $x, 490 * $scale + $y, 30 * $scale, $this->data['d_zip'] . ' ' . $this->data['d_city']);
		$this->backend->addtext(15 * $scale + $x, 680 * $scale + $y, 30 * $scale, substr(bankaccount($this->data['id'], $this->data['account']), 0, 17));
		$this->backend->addtext(15 * $scale + $x, 620 * $scale + $y, 30 * $scale, substr(bankaccount($this->data['id'], $this->data['account']), 18, 200));
		$this->backend->addtext(15 * $scale + $x, 435 * $scale + $y, 30 * $scale,'**' . number_format($this->data['total'], 2, ',', '') . '**');
//		$this->backend->addtext(15 * $scale + $x, 310 * $scale+$y, 30 * $scale, $this->data['name']);

		$font_size = 30;
		while ($this->backend->getTextWidth($font_size * $scale, $this->data['name']) > 135)
			$font_size -= 1;
		$this->backend->addtext(15 * $scale + $x, 310 * $scale + $y, $font_size * $scale, $this->data['name']);
		$font_size=30;
		while ($this->backend->getTextWidth($font_size * $scale, $this->data['address']) > 135)
			$font_size -= 1;
		$this->backend->addtext(15 * $scale + $x, 275 * $scale + $y, $font_size * $scale, $this->data['address']);
		$this->backend->addtext(15 * $scale + $x, 240 * $scale + $y, 30 * $scale, $this->data['zip'] . ' ' . $this->data['city']);

		$font_size = 30;
		while ($this->backend->getTextWidth($font_size * $scale, trans('Invoice No. $a', $this->data['t_number'])) > 135)
			$font_size -= 1;
		$this->backend->addtext(15 * $scale + $x, 385 * $scale + $y, $font_size * $scale, trans('Invoice No. $a', $this->data['t_number']));
	}

	protected function address_box($x, $y, $scale) {
		$font_size = 30;
		while ($this->backend->getTextWidth($font_size * $scale, $this->data['name']) > 240)
			$font_size -= 1;
		$this->backend->addtext(5 * $scale + $x, 310 * $scale + $y, $font_size * $scale, $this->data['name']);
		$this->backend->addtext(5 * $scale + $x, 275 * $scale + $y, 30 * $scale, $this->data['address']);
		$this->backend->addtext(5 * $scale + $x, 240 * $scale + $y, 30 * $scale, $this->data['zip'] . ' ' . $this->data['city']);
	}

	public function Draw($data) {
		parent::Draw($data);

		$this->main_fill(177, 12, 0.395);
		$this->main_fill(177, 313, 0.396);
		$this->simple_fill_mip(5, 12, 0.395);
		$this->simple_fill_mip(5, 313, 0.395);
		$this->address_box(390, 600, 0.395);

		if (!$this->data['last'])
			$this->id = $this->backend->newPage(1, $this->id);
	}
}

?>

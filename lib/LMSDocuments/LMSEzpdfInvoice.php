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

// brzydkie hacki dla ezpdf
@setlocale(LC_NUMERIC, 'C');

class LMSEzpdfInvoice extends LMSInvoice {
	public function __construct($title, $pagesize = 'A4', $orientation = 'portrait') {
		parent::__construct('LMSEzpdfBackend', $title, $pagesize, $orientation);
	}

	protected function invoice_simple_form_fill($x, $y, $scale) {
		$this->backend->setlinestyle(1);

		$this->backend->line(7*$scale+$x,724*$scale+$y,7*$scale+$x,694*$scale+$y);
		$this->backend->line(7*$scale+$x,724*$scale+$y,37*$scale+$x,724*$scale+$y);
		$this->backend->line(370*$scale+$x,724*$scale+$y,370*$scale+$x,694*$scale+$y);
		$this->backend->line(370*$scale+$x,724*$scale+$y,340*$scale+$x,724*$scale+$y);
		$this->backend->line(7*$scale+$x,197*$scale+$y,7*$scale+$x,227*$scale+$y);
		$this->backend->line(7*$scale+$x,197*$scale+$y,37*$scale+$x,197*$scale+$y);
		$this->backend->line(370*$scale+$x,197*$scale+$y,370*$scale+$x,227*$scale+$y);
		$this->backend->line(370*$scale+$x,197*$scale+$y,340*$scale+$x,197*$scale+$y);

		$shortname = $this->data['division_shortname'];
		$address = $this->data['division_address'];
		$zip = $this->data['division_zip'];
		$city = $this->data['division_city'];
		$account = bankaccount($this->data['customerid'], $this->data['account']);

		$this->backend->text_autosize(15*$scale+$x,568*$scale+$y,30*$scale, $shortname,350*$scale);
		$this->backend->text_autosize(15*$scale+$x,534*$scale+$y,30*$scale, $address,350*$scale);
		$this->backend->text_autosize(15*$scale+$x,500*$scale+$y,30*$scale, $zip.' '.$city,350*$scale);

		//$this->backend->text_autosize(15*$scale+$x,683*$scale+$y,30*$scale, substr($tmp,0,17),350*$scale);
		//$this->backend->text_autosize(15*$scale+$x,626*$scale+$y,30*$scale, substr($tmp,18,200),350*$scale);
		$this->backend->text_autosize(15*$scale+$x,683*$scale+$y,30*$scale, format_bankaccount($account), 350*$scale);
		if (ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.customer_balance_in_form', false)))
			$value = $this->data['customerbalance'] * -1;
		else
			$value = $this->data['total'];
		$this->backend->text_autosize(15*$scale+$x,445*$scale+$y,30*$scale,"*".number_format($value,2,',','')."*",350*$scale);

		$this->backend->text_autosize(15*$scale+$x,390*$scale+$y,30*$scale, $this->data['name'],350*$scale);
		$this->backend->text_autosize(15*$scale+$x,356*$scale+$y,30*$scale, $this->data['address'],350*$scale);
		$this->backend->text_autosize(15*$scale+$x,322*$scale+$y,30*$scale, $this->data['zip'].' '.$this->data['city'],350*$scale);

		if (ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.customer_balance_in_form', false)))
			$this->backend->text_autosize(15*$scale+$x,215*$scale+$y,30*$scale,trans('Payment for liabilities'),350*$scale);
		else {
			$tmp = docnumber($this->data['number'], $this->data['template'], $this->data['cdate']);
			$this->backend->text_autosize(15*$scale+$x,215*$scale+$y,30*$scale,trans('Payment for invoice No. $a', $tmp),350*$scale);
		}
	}

	protected function invoice_main_form_fill($x, $y, $scale) {
		$this->backend->setlinestyle(1);

		$this->backend->line(7*$scale+$x,724*$scale+$y,7*$scale+$x,694*$scale+$y);
		$this->backend->line(7*$scale+$x,724*$scale+$y,37*$scale+$x,724*$scale+$y);
		$this->backend->line(970*$scale+$x,724*$scale+$y,970*$scale+$x,694*$scale+$y);
		$this->backend->line(970*$scale+$x,724*$scale+$y,940*$scale+$x,724*$scale+$y);
		$this->backend->line(7*$scale+$x,172*$scale+$y,7*$scale+$x,202*$scale+$y);
		$this->backend->line(7*$scale+$x,172*$scale+$y,37*$scale+$x,172*$scale+$y);

		$name = $this->data['division_name'];
		$address = $this->data['division_address'];
		$zip = $this->data['division_zip'];
		$city = $this->data['division_city'];
		$account = bankaccount($this->data['customerid'], $this->data['account']);

		$this->backend->text_autosize(15*$scale+$x,680*$scale+$y,30*$scale,$name,950*$scale);
		$this->backend->text_autosize(15*$scale+$x,617*$scale+$y,30*$scale,$address." ".$zip." ".$city,950*$scale);
		$this->backend->text_autosize(15*$scale+$x,555*$scale+$y,30*$scale, format_bankaccount($account), 950*$scale);
		$this->backend->addtext(330*$scale+$x,495*$scale+$y,30*$scale,'X');
		$this->backend->text_autosize(550*$scale+$x,495*$scale+$y,30*$scale,"*".number_format($this->data['total'],2,',','')."*",400*$scale);
		if (ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.customer_balance_in_form', false)))
			$value = $this->data['customerbalance'] * -1;
		else
			$value = $this->data['total'];
		$this->backend->text_autosize(15*$scale+$x,434*$scale+$y,30*$scale, trans('$a dollars $b cents',to_words(floor($value)),to_words(round(($value-floor($value))*100))),950*$scale);
		$this->backend->text_autosize(15*$scale+$x,372*$scale+$y,30*$scale, $this->data['name'],950*$scale);
		$this->backend->text_autosize(15*$scale+$x,312*$scale+$y,30*$scale, $this->data['address']." ".$this->data['zip']." ".$this->data['city'],950*$scale);
		if (ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.customer_balance_in_form', false)))
			$this->backend->text_autosize(15*$scale+$x,250*$scale+$y,30*$scale, trans('Payment for liabilities'),950*$scale);
		else {
			$tmp = docnumber($this->data['number'], $this->data['template'], $this->data['cdate']);
			$this->backend->text_autosize(15*$scale+$x,250*$scale+$y,30*$scale, trans('Payment for invoice No. $a',$tmp),950*$scale);
		}
	}

	protected function invoice_dates($x, $y) {
		$font_size = 12;
		$this->backend->text_align_right($x, $y, $font_size, trans('Settlement date:').' ');
		$y = $y - $this->backend->text_align_left($x, $y, $font_size, date("Y/m/d", $this->data['cdate']));
		$this->backend->text_align_right($x, $y, $font_size, trans('Sale date:').' ');
		$y = $y - $this->backend->text_align_left($x, $y, $font_size, date("Y/m/d", $this->data['sdate']));
		$this->backend->text_align_right($x, $y, $font_size, trans('Deadline:').' ');
		$y = $y - $this->backend->text_align_left($x, $y, $font_size, date("Y/m/d", $this->data['pdate']));
		$this->backend->text_align_right($x, $y, $font_size, trans('Payment type:').' ');
		$y = $y - $this->backend->text_align_left($x, $y, $font_size, $this->data['paytypename']);
		return $y;
	}

	protected function invoice_buyer($x, $y) {
		$font_size=10;
		$y=$y-$this->backend->text_align_left($x,$y,$font_size,'<b>' . trans('Purchaser:') . '</b>');
		$y=$this->backend->text_wrap($x,$y,350,$font_size, $this->data['name'],'left');
		$y=$y-$this->backend->text_align_left($x,$y,$font_size, $this->data['address']);
		$y=$y-$this->backend->text_align_left($x,$y,$font_size, $this->data['zip']." ".$this->data['city']);
		if ($this->data['division_countryid'] && $this->data['countryid'] && $this->data['division_countryid'] != $this->data['countryid'])
			$y=$y-$this->backend->text_align_left($x,$y,$font_size, trans($this->data['country']));
		if ($this->data['ten']) 
			$y=$y-$this->backend->text_align_left($x,$y,$font_size, trans('TEN').' '.$this->data['ten']);
		else if ($this->data['ssn']) 
			$y=$y-$this->backend->text_align_left($x,$y,$font_size, trans('SSN').' '.$this->data['ssn']);
		$y=$y-$this->backend->text_align_left($x,$y,$font_size,'<b>' . trans('Customer No.: $a',sprintf('%04d',$this->data['customerid'])) . '</b>');
		return $y;
	}

	protected function invoice_seller($x, $y) {
		$font_size = 10;
		$y = $y - $this->backend->text_align_left($x, $y, $font_size, '<b>' . trans('Seller:') . '</b>');
		$tmp = $this->data['division_header'];

		$accounts = array(bankaccount($this->data['customerid'], $this->data['account']));
		if (ConfigHelper::checkConfig('invoices.show_all_accounts'))
			$accounts = array_merge($accounts, $this->data['bankaccounts']);
		foreach ($accounts as &$account)
			$account = format_bankaccount($account);
		$tmp = str_replace('%bankaccount', implode("\n", $accounts), $tmp);

		$tmp = preg_split('/\r?\n/', $tmp);
		foreach ($tmp as $line)
			$y = $y - $this->backend->text_align_left($x, $y, $font_size, $line);

		return $y;
	}

	protected function invoice_title($x, $y) {
		$font_size = 16;
		$tmp = docnumber($this->data['number'], $this->data['template'], $this->data['cdate']);
		if (isset($this->data['invoice']))
			$y=$y - $this->backend->text_align_left($x,$y,$font_size,'<b>' . trans('Credit Note No. $a',$tmp) . '</b>');
		else
			$y=$y - $this->backend->text_align_left($x,$y,$font_size,'<b>' . trans('Invoice No. $a',$tmp) . '</b>');

		if(isset($this->data['invoice'])) {
			$font_size = 12; $y += 8;
			$tmp = docnumber($this->data['invoice']['number'], $this->data['invoice']['template'], $this->data['invoice']['cdate']);
			$y = $y - $this->backend->text_align_left($x,$y,$font_size,'<b>' . trans('for Invoice No. $a',$tmp) . '</b>');
			$y -= 5;
		}

		//$font_size = 16;
		//$y = $y - $this->backend->text_align_left($x, $y, $font_size, $this->data['type']);

		if ($this->data['type'] == trans('DUPLICATE')) {
			$font_size = 12;
			$y = $y - $this->backend->text_align_left($x,$y+4,$font_size, trans('Duplicate draw-up date:') . ' ' . date('Y/m/d'));
		}

		if (isset($this->data['invoice']))
			$y += 10;
		return $y;
	}

	protected function invoice_address_box($x, $y) {
		$font_size = 12;
/*
		$this->invoice_name = $this->data['name'];
		if (strlen($this->invoice_name)>25) 
			$this->invoice_name = preg_replace('/(.{25})/',"$b<i>&gt;</i>\n",$this->invoice_name);
		$tmp = preg_split('/\r?\n/', $this->invoice_name);
		foreach ($tmp as $line)
			$y = $y - $this->backend->text_align_left($x,$y,$font_size,"<b>".$line."</b>");
*/
		if ($this->data['post_name'] || $this->data['post_address']) {
			if ($this->data['post_name'])
				$y = $this->backend->text_wrap($x, $y, 160, $font_size, '<b>' . $this->data['post_name'] . '</b>', 'left');
			else
				$y = $this->backend->text_wrap($x, $y, 160, $font_size, '<b>' . $this->data['name'] . '</b>', 'left');
			$y = $y - $this->backend->text_align_left($x, $y, $font_size, '<b>' . $this->data['post_address'] . '</b>');
			$y = $y - $this->backend->text_align_left($x, $y, $font_size, '<b>' . $this->data['post_zip'] . " " . $this->data['post_city'] . '</b>');
		} else {
			$y = $this->backend->text_wrap($x, $y, 160, $font_size, '<b>' . $this->data['name'] . '</b>', 'left');
			$y = $y - $this->backend->text_align_left($x, $y, $font_size, '<b>' . $this->data['address'] . '</b>');
			$y = $y - $this->backend->text_align_left($x, $y, $font_size, '<b>' . $this->data['zip'] . " " . $this->data['city'] . '</b>');
		}
		return $y;
	}

	protected function invoice_data_row($x, $y, $width, $font_size, $margin, $data, $t_width, $t_justify) {
		$fy = $y - $margin - $this->backend->GetFontHeight($font_size);
		$left = $x+$margin;
		$ny = $fy;
		$cols = sizeof($data);
		for ($i = 1; $i <= $cols; $i++) {
			$ly = $this->backend->text_wrap($left+$margin, $fy, $t_width[$i]-2*$margin, $font_size,$data[$i],$t_justify[$i]);
			$left = $left + $t_width[$i]+2*$margin;
			if ($ly<$ny) $ny=$ly;
		}
		$left = $x;
		for ($i = 1; $i <= $cols; $i++) {
			$this->backend->line($left, $y, $left, $ny+$font_size/2);
			$left = $left + $t_width[$i]+2*$margin;
		}
		$this->backend->line($left, $y, $left, $ny+$font_size/2);
		$y = $ny + $font_size / 2;
		$this->backend->line($x,$y,$x+$width,$y);

		return $y;
	}

	protected function invoice_short_data_row($x, $y, $width, $font_size, $margin, $data, $t_width, $t_justify) {
		$fy = $y - $margin - $this->backend->GetFontHeight($font_size);
		$left = $x+$margin;
		$ny = $fy;
		$cols = sizeof($data);
		for ($i = $cols-3; $i <= $cols; $i++) {
			$ly = $this->backend->text_wrap($left+$margin, $fy, $t_width[$i]-2*$margin, $font_size,$data[$i],$t_justify[$i]);
			$left = $left + $t_width[$i]+2*$margin;
			if ($ly<$ny) $ny=$ly;
		}
		$left = $x;
		for ($i = $cols-3; $i <= $cols; $i++) {
			$this->backend->line($left, $y, $left, $ny+$font_size/2);
			$left = $left + $t_width[$i]+2*$margin;
		}
		$this->backend->line($left, $y, $left, $ny+$font_size/2);
		$y = $ny + $font_size / 2;
		//$this->backend->line($x,$y,$x+$width,$y);
		$v = $cols - 3;
		$this->backend->line($x,$y,$x+$t_width[$v++]+$t_width[$v++]+$t_width[$v++]+$t_width[$v++]+8*$margin,$y);

		return $y;
	}

	protected function invoice_data($x, $y, $width, $font_size, $margin) {
		$this->backend->setlinestyle(0.5);
		$this->backend->line($x, $y, $x + $width, $y);

		$v = 1;
		$t_data[$v++] = '<b>' . trans('No.') . '</b>';
		$t_data[$v++] = '<b>' . trans('Name of Product, Commodity or Service:') . '</b>';
		$t_data[$v++] = '<b>' . trans('Product ID:') . '</b>';
		$t_data[$v++] = '<b>' . trans('Unit:') . '</b>';
		$t_data[$v++] = '<b>' . trans('Amount:') . '</b>';
		if (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount']))
			$t_data[$v++] = '<b>' . trans('Discount:') . '</b>';
		$t_data[$v++] = '<b>' . trans('Unitary Net Value:') . '</b>';
		$t_data[$v++] = '<b>' . trans('Net Value:') . '</b>';
		$t_data[$v++] = '<b>' . trans('Tax Rate:') . '</b>';
		$t_data[$v++] = '<b>' . trans('Tax Value:') . '</b>';
		$t_data[$v++] = '<b>' . trans('Gross Value:') . '</b>';

		for ($i = 1; $i < $v; $i++) $t_justify[$i] = "center";
		for ($i = 1; $i < $v; $i++) $t_width[$i] = $this->backend->getWrapTextWidth($font_size, $t_data[$i]) + 2 * $margin + 2;

		// tutaj jeszcze trzeba będzie sprawdzić jaką szerokość mają pola w tabelce później
		if ($this->data['content'])
			foreach ($this->data['content'] as $item) {
				$v = 2;
				$tt_width[$v++] = $this->backend->getTextWidth($font_size, $item['description']);
				$tt_width[$v++] = $this->backend->getTextWidth($font_size, $item['prodid']);
				$tt_width[$v++] = $this->backend->getTextWidth($font_size, $item['content']);
				$tt_width[$v++] = $this->backend->getTextWidth($font_size, sprintf('%.2f', $item['count']));
				if (!empty($this->data['pdiscount']))
					$tt_width[$v] = $this->backend->getTextWidth($font_size, sprintf('%.2f %%', $item['pdiscount']));
				if (!empty($this->data['vdiscount'])) {
					$tmp_width = $this->backend->getTextWidth($font_size, moneyf($item['vdiscount']));
					if ($tmp_width > $tt_width[$v])
						$tt_width[$v] = $tmp_width;
				}
				if (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount']))
					$v++;
				$tt_width[$v++] = $this->backend->getTextWidth($font_size, moneyf($item['basevalue'])) + 6;
				$tt_width[$v++] = $this->backend->getTextWidth($font_size, moneyf($item['totalbase'])) + 6;
				$tt_width[$v++] = $this->backend->getTextWidth($font_size, $item['taxlabel']) + 6;
				$tt_width[$v++] = $this->backend->getTextWidth($font_size, moneyf($item['totaltax'])) + 6;
				$tt_width[$v++] = $this->backend->getTextWidth($font_size, moneyf($item['total'])) + 6;
				for ($i = 2; $i < $v; $i++) 
					if(($tt_width[$i] + 2 * $margin + 2) > $t_width[$i])
						$t_width[$i] = $tt_width[$i] + 2 * $margin + 2;
			}

		if (isset($this->data['invoice']['content']))
			foreach ($this->data['invoice']['content'] as $item) {
				$v = 2;
				$tt_width[$v++] = $this->backend->getTextWidth($font_size, $item['description']);
				$tt_width[$v++] = $this->backend->getTextWidth($font_size, $item['prodid']);
				$tt_width[$v++] = $this->backend->getTextWidth($font_size, $item['content']);
				$tt_width[$v++] = $this->backend->getTextWidth($font_size, sprintf('%.2f', $item['count']));
				if (!empty($this->data['pdiscount']))
					$tt_width[$v] = $this->backend->getTextWidth($font_size, sprintf('%.2f %%', $item['pdiscount']));
				if (!empty($this->data['vdiscount'])) {
					$tmp_width = $this->backend->getTextWidth($font_size, moneyf($item['vdiscount']));
					if ($tmp_width > $tt_width[$v])
						$tt_width[$v] = $tmp_width;
				}
				if (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount']))
					$v++;
				$tt_width[$v++] = $this->backend->getTextWidth($font_size, moneyf($item['basevalue'])) + 6;
				$tt_width[$v++] = $this->backend->getTextWidth($font_size, moneyf($item['totalbase'])) + 6;
				$tt_width[$v++] = $this->backend->getTextWidth($font_size, $item['taxlabel']) + 6;
				$tt_width[$v++] = $this->backend->getTextWidth($font_size, moneyf($item['totaltax'])) + 6;
				$tt_width[$v++] = $this->backend->getTextWidth($font_size, moneyf($item['total'])) + 6;
				for ($i = 2; $i < $v; $i++) 
					if(($tt_width[$i] + 2 * $margin + 2) > $t_width[$i])
						$t_width[$i] = $tt_width[$i] + 2 * $margin + 2;
			}
		// Kolumna 2 będzie miała rozmiar ustalany dynamicznie
		$t_width[2] = $width - ($t_width[1] + $t_width[3] + $t_width[4] + $t_width[5] + $t_width[6] + $t_width[7]
			+ $t_width[8] + $t_width[9] + $t_width[10] + (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount']) ? $t_width[11] : 0)
			+ 2 * $margin * (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount']) ? 11 : 10));
		$y = $this->invoice_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);
		$t_justify[11] = $t_justify[10] = $t_justify[9] = $t_justify[8] = $t_justify[7] = $t_justify[6] = $t_justify[5] = "right";
		$t_justify[2] = 'left';

		if (isset($this->data['invoice'])) {
			// we have credit note, so first print corrected invoice data
			$xx = $x;
			$y = $y - $this->backend->text_align_left($x, $y - 10, $font_size, '<b>' . trans('Was:') . '</b>');
			$y -= 6;
			$this->backend->line($x, $y, $x + $width, $y);
			$lp = 1;
			if ($this->data['invoice']['content']) 
				foreach ($this->data['invoice']['content'] as $item) {
					$v = 1;
					$t_data[$v++] = $lp;
					$t_data[$v++] = $item['description'];
					$t_data[$v++] = $item['prodid'];
					$t_data[$v++] = $item['content'];
					$t_data[$v++] = sprintf('%.2f',$item['count']);
					$item['pdiscount'] = floatval($item['pdiscount']);
					$item['vdiscount'] = floatval($item['vdiscount']);
					if (!empty($item['pdiscount']))
						$t_data[$v++] = sprintf('%.2f %%', $item['pdiscount']);
					elseif (!empty($item['vdiscount']))
						$t_data[$v++] = moneyf($item['vdiscount']);
					elseif (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount']))
						$t_data[$v++] = '';
					$t_data[$v++] = moneyf($item['basevalue']);
					$t_data[$v++] = moneyf($item['totalbase']);
					$t_data[$v++] = $item['taxlabel'];
					$t_data[$v++] = moneyf($item['totaltax']);
					$t_data[$v++] = moneyf($item['total']);

					$lp++;
					$y = $this->invoice_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);
				}

			$x = $x + (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount']) ? 7 : 6) * 2 * $margin + $t_width[1] + $t_width[2] + $t_width[3]
				+ $t_width[4] + $t_width[5] + $t_width[6] + (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount']) ? $t_width[7] : 0);

			$fy = $y - $margin - $this->backend->GetFontHeight($font_size);
			$this->backend->text_align_right($x - $margin, $fy, $font_size, '<b>' . trans('Total:') . '</b>');

			$v = (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount'])) ? 8 : 7;
			$t_data[$v++] = moneyf($this->data['invoice']['totalbase']);
			$t_data[$v++] = "<b>x</b>";
			$t_data[$v++] = moneyf($this->data['invoice']['totaltax']);
			$t_data[$v++] = moneyf($this->data['invoice']['total']);

			$y = $this->invoice_short_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);
			$y -= 5;

			$fy = $y - $margin - $this->backend->GetFontHeight($font_size);
			$this->backend->text_align_right($x - $margin, $fy, $font_size, '<b>' . trans('in it:') . '</b>');
			$v = (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount'])) ? 8 : 7;
			$this->backend->line($x, $y, $x + $t_width[$v++] + $t_width[$v++] + $t_width[$v++] + $t_width[$v++] + 8 * $margin, $y);

			if ($this->data['invoice']['taxest'])
				foreach ($this->data['invoice']['taxest'] as $item) {
					$v = (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount'])) ? 8 : 7;
					$t_data[$v++] = moneyf($item['base']);
					$t_data[$v++] = $item['taxlabel'];
					$t_data[$v++] = moneyf($item['tax']);
					$t_data[$v++] = moneyf($item['total']);
					$y = $this->invoice_short_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);
				}

			$x = $xx;
			if ($this->data['reason'] != '') {
				$y = $y - $this->backend->text_align_left($x, $y - 10, $font_size, '<b>' . trans('Reason:') . ' ' . $this->data['reason'] . '</b>');
				$y -= 10;
			}
			$y = $y - $this->backend->text_align_left($x, $y - 10, $font_size, '<b>' . trans('Corrected to:') . '</b>');
			$y -= 5;
			$this->backend->line($x, $y, $x + $width, $y);
		}

		$lp = 1;
		if ($this->data['content'])
			foreach ($this->data['content'] as $item) {
				$v = 1;
				$t_data[$v++] = $lp;
				$t_data[$v++] = $item['description'];
				$t_data[$v++] = $item['prodid'];
				$t_data[$v++] = $item['content'];
				$t_data[$v++] = sprintf('%.2f',$item['count']);
				$item['pdiscount'] = floatval($item['pdiscount']);
				$item['vdiscount'] = floatval($item['vdiscount']);
				if (!empty($item['pdiscount']))
					$t_data[$v++] = sprintf('%.2f %%',$item['pdiscount']);
				elseif (!empty($item['vdiscount']))
					$t_data[$v++] = moneyf($item['vdiscount']);
				elseif (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount']))
					$t_data[$v++] = '';
				$t_data[$v++] = moneyf($item['basevalue']);
				$t_data[$v++] = moneyf($item['totalbase']);
				$t_data[$v++] = $item['taxlabel'];
				$t_data[$v++] = moneyf($item['totaltax']);
				$t_data[$v++] = moneyf($item['total']);

				$lp++;
				$y = $this->invoice_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);
			}

		$return[1] = $y;

		$x = $x + (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount']) ? 7 : 6) * 2 * $margin + $t_width[1] + $t_width[2] + $t_width[3]
			+ $t_width[4] + $t_width[5] + $t_width[6] + (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount']) ? $t_width[7] : 0);

		$fy = $y - $margin - $this->backend->GetFontHeight($font_size);
		$this->backend->text_align_right($x - $margin, $fy, $font_size, '<b>' . trans('Total:') . '</b>');

		$v = (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount'])) ? 8 : 7;
		$t_data[$v++] = moneyf($this->data['totalbase']);
		$t_data[$v++] = "<b>x</b>";
		$t_data[$v++] = moneyf($this->data['totaltax']);
		$t_data[$v++] = moneyf($this->data['total']);

		$y = $this->invoice_short_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);

		$y = $y - 5;

		$fy = $y - $margin - $this->backend->GetFontHeight($font_size);
		$this->backend->text_align_right($x - $margin, $fy, $font_size, '<b>' . trans('in it:') . '</b>');
		$v = (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount'])) ? 8 : 7;
		$this->backend->line($x, $y, $x + $t_width[$v++] + $t_width[$v++] + $t_width[$v++] + $t_width[$v++] + 8 * $margin, $y);

		if ($this->data['taxest'])
			foreach ($this->data['taxest'] as $item) {
				$v = (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount'])) ? 8 : 7;
				$t_data[$v++] = moneyf($item['base']);
				$t_data[$v++] = $item['taxlabel'];
				$t_data[$v++] = moneyf($item['tax']);
				$t_data[$v++] = moneyf($item['total']);
				$y = $this->invoice_short_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);
			}

		if (isset($this->data['invoice'])) {
			$total = $this->data['total'] - $this->data['invoice']['total'];
			$totalbase = $this->data['totalbase'] - $this->data['invoice']['totalbase'];
			$totaltax = $this->data['totaltax'] - $this->data['invoice']['totaltax'];

			$y = $y - 5;
			$fy = $y - $margin - $this->backend->GetFontHeight($font_size);
			$v = (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount'])) ? 8 : 7;
			$this->backend->line($x, $y, $x + $t_width[$v++] + $t_width[$v++] + $t_width[$v++] + $t_width[$v++] + 8 * $margin, $y);
			$this->backend->text_align_right($x - $margin, $fy, $font_size, '<b>' . trans('Difference value:') . '</b>');

			$v = (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount'])) ? 8 : 7;
			$t_data[$v++] = ($totalbase > 0 ? '+' : '') . moneyf($totalbase);
			$t_data[$v++] = "<b>x</b>";
			$t_data[$v++] = ($totaltax > 0 ? '+' : '') . moneyf($totaltax);
			$t_data[$v++] = ($total > 0 ? '+' : '') . moneyf($total);

			$y = $this->invoice_short_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);
		}

		$return[2] = $y;

		return $return;
	}

	protected function new_invoice_data($x, $y, $width, $font_size, $margin) {
		$this->backend->setlinestyle(0.5);
		$data = array();
		$cols = array();
		$params = array(
			'fontSize' => $font_size,
			'xPos' => $x,
			'xOrientation' => 'right', // I think it should be left here (bug?)
			'rowGap' => 2,
			'colGap' => 2,
			'showHeadings' => 0,
			'cols' => array(),
		);

		// tabelka glowna
		$cols['no'] = '<b>' . trans('No.') . '</b>';
		$cols['name'] = '<b>' . trans('Name of Product, Commodity or Service:') . '</b>';
		$cols['prodid'] = '<b>' . trans('Product ID:') . '</b>';
		$cols['content'] = '<b>' . trans('Unit:') . '</b>';
		$cols['count'] = '<b>' . trans('Amount:') . '</b>';
		if (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount']))
			$cols['discount'] = '<b>' . trans('Discount:') . '</b>';
		$cols['basevalue'] = '<b>' . trans('Unitary Net Value:') . '</b>';
		$cols['totalbase'] = '<b>' . trans('Net Value:') . '</b>';
		$cols['taxlabel'] = '<b>' . trans('Tax Rate:') . '</b>';
		$cols['totaltax'] = '<b>' . trans('Tax Value:') . '</b>';
		$cols['total'] = '<b>' . trans('Gross Value:') . '</b>';

		foreach($cols as $name => $text) {
			$params['cols'][$name] = array(
				'justification' => 'center',
				'width' => $this->backend->getWrapTextWidth($font_size, $text) + 2 * $margin + 2,
			);
		}

		// tutaj jeszcze trzeba będzie sprawdzić jaką szerokość mają pola w tabelce później
		if ($this->data['content'])
			foreach ($this->data['content'] as $item) {
				$tt_width['name'] = $this->backend->getTextWidth($font_size, $item['description']);
				$tt_width['prodid'] = $this->backend->getTextWidth($font_size, $item['prodid']);
				$tt_width['content'] = $this->backend->getTextWidth($font_size, $item['content']);
				$tt_width['count'] = $this->backend->getTextWidth($font_size, sprintf('%.2f',$item['count']));
				if (!empty($this->data['pdiscount']))
					$tt_width['discount'] = $this->backend->getTextWidth($font_size, sprintf('%.2f %%', $item['pdiscount']));
				if (!empty($this->data['vdiscount'])) {
					$tmp_width = $this->backend->getTextWidth($font_size, moneyf($item['vdiscount']));
					if ($tmp_width > $tt_width['discount'])
						$tt_width['discount'] = $tmp_width;
				}
				$tt_width['basevalue'] = $this->backend->getTextWidth($font_size, moneyf($item['basevalue'])) + 6;
				$tt_width['totalbase'] = $this->backend->getTextWidth($font_size, moneyf($item['totalbase'])) + 6;
				$tt_width['taxlabel'] = $this->backend->getTextWidth($font_size, $item['taxlabel']) + 6;
				$tt_width['totaltax'] = $this->backend->getTextWidth($font_size, moneyf($item['totaltax'])) + 6;
				$tt_width['total'] = $this->backend->getTextWidth($font_size, moneyf($item['total'])) + 6;

				foreach ($tt_width as $name => $w) 
					if (($w + 2 * $margin + 2) > $params['cols'][$name]['width'])
						$params['cols'][$name]['width'] = $w + 2 * $margin + 2;
			}

		if (isset($this->data['invoice']['content']))
			foreach ($this->data['invoice']['content'] as $item) {
				$tt_width['name'] = $this->backend->getTextWidth($font_size, $item['description']);
				$tt_width['prodid'] = $this->backend->getTextWidth($font_size, $item['prodid']);
				$tt_width['content'] = $this->backend->getTextWidth($font_size, $item['content']);
				$tt_width['count'] = $this->backend->getTextWidth($font_size, sprintf('%.2f', $item['count']));
				if (!empty($this->data['pdiscount']))
					$tt_width['discount'] = $this->backend->getTextWidth($font_size, sprintf('%.2f %%', $item['pdiscount']));
				if (!empty($this->data['vdiscount'])) {
					$tmp_width = $this->backend->getTextWidth($font_size, moneyf($item['vdiscount']));
					if ($tmp_width > $tt_width['discount'])
					$tt_width['discount'] = $tmp_width;
				}
				$tt_width['basevalue'] = $this->backend->getTextWidth($font_size, moneyf($item['basevalue'])) + 6;
				$tt_width['totalbase'] = $this->backend->getTextWidth($font_size, moneyf($item['totalbase'])) + 6;
				$tt_width['taxlabel'] = $this->backend->getTextWidth($font_size, $item['taxlabel']) + 6;
				$tt_width['totaltax'] = $this->backend->getTextWidth($font_size, moneyf($item['totaltax'])) + 6;
				$tt_width['total'] = $this->backend->getTextWidth($font_size, moneyf($item['total'])) + 6;

				foreach($tt_width as $name => $w) 
					if(($w + 2 * $margin + 2) > $params['cols'][$name]['width']) 
						$params['cols'][$name]['width'] = $w + 2 * $margin + 2;
			}

		// Kolumna 'name' bedzie miala rozmiar ustalany dynamicznie
		$sum = 0;
		foreach($params['cols'] as $name => $col)
			if ($name != 'name')
				$sum += $col['width'];
		$params['cols']['name']['width'] = $width - $sum;

		// table header
		$this->backend->ezSetY($y);
		$data = array(0=>$cols);
		$y = $this->backend->ezTable($data, $cols, '', $params);
		$data = array();

		foreach ($cols as $name => $text) {
			switch ($name) {
				case 'no': $params['cols'][$name]['justification'] = 'center'; break;
				case 'name': $params['cols'][$name]['justification'] = 'left'; break;
				default: $params['cols'][$name]['justification'] = 'right'; break;
			}
		}

		// size of taxes summary table
		$xx = $x;
		foreach ($params['cols'] as $name => $value)
			if (in_array($name, array('no', 'name', 'prodid', 'content', 'count', 'discount', 'basevalue')))
				$xx += $params['cols'][$name]['width'];
			else
				$cols2[$name] = $params['cols'][$name];

		$data2 = array();
		$params2 = array(
			'fontSize' => $font_size,
			'xPos' => $xx,
			'xOrientation' => 'right',
			'rowGap' => 2,
			'colGap' => 2,
			'showHeadings' => 0,
			'cols' => $cols2,
		);

		if (isset($this->data['invoice'])) {
			// we have credit note, so first print corrected invoice data

			$y -= 20;
			$this->backend->check_page_length($y);
			$y = $y - $this->backend->text_align_left($x, $y, $font_size, '<b>' . trans('Was:') . '</b>');

			$i = 0;
			if ($this->data['invoice']['content']) 
				foreach ($this->data['invoice']['content'] as $item) {
				$data[$i]['no'] = $i + 1;
				$data[$i]['name'] = $item['description'];
				$data[$i]['prodid'] = $item['prodid'];
				$data[$i]['content'] = $item['content'];
				$data[$i]['count'] = sprintf('%.2f', $item['count']);
				$item['pdiscount'] = floatval($item['pdiscount']);
				$item['vdiscount'] = floatval($item['vdiscount']);
				if (!empty($item['pdiscount']))
					$data[$i]['discount'] = sprintf('%.2f %%', $item['pdiscount']);
				elseif (!empty($item['vdiscount']))
					$data[$i]['discount'] = moneyf($item['vdiscount']);
				$data[$i]['basevalue'] = moneyf($item['basevalue']);
				$data[$i]['totalbase'] = moneyf($item['totalbase']);
				$data[$i]['taxlabel'] = $item['taxlabel'];
				$data[$i]['totaltax'] = moneyf($item['totaltax']);
				$data[$i]['total'] = moneyf($item['total']);

				$i++;
			}

			$this->backend->ezSetY($y);
			$y = $this->backend->ezTable($data, $cols, '', $params);
			$data = array();

			$y -= 10;
			$this->backend->check_page_length($y);

			$data2[0]['totalbase'] = moneyf($this->data['invoice']['totalbase']);
			$data2[0]['taxlabel'] = "<b>x</b>";
			$data2[0]['totaltax'] = moneyf($this->data['invoice']['totaltax']);
			$data2[0]['total'] = moneyf($this->data['invoice']['total']);

			$this->backend->ezSetY($y);
			$y = $this->backend->ezTable($data2, NULL, '', $params2);
			$data2 = array();

			$fy = $y + $this->backend->GetFontHeight($font_size) / 2;
			$this->backend->text_align_right($xx - 5, $fy, $font_size, '<b>' . trans('Total:') . '</b>');

			$this->backend->check_page_length($y);
			$fy = $y - $margin - $this->backend->GetFontHeight($font_size);
			$this->backend->text_align_right($xx - 5,$fy, $font_size,'<b>' . trans('in it:') . '</b>');

			if ($this->data['invoice']['taxest']) {
				$i = 0;
				foreach ($this->data['invoice']['taxest'] as $item) {
					$data2[$i]['totalbase'] = moneyf($item['base']);
					$data2[$i]['taxlabel'] = $item['taxlabel'];
					$data2[$i]['totaltax'] = moneyf($item['tax']);
					$data2[$i]['total'] = moneyf($item['total']);
					$i++;
				}
				//$this->backend->ezSetY($y);
				$this->backend->ezSetY($y + 3);
				$y = $this->backend->ezTable($data2, NULL, '', $params2);
				$data2 = array();
			}

			$y -= 20;
			if ($this->data['reason'] != '') {
				$this->backend->check_page_length($y);
				$y = $this->backend->text_wrap($x, $y, $width, $font_size, '<b>' . trans('Reason:') . ' ' . $this->data['reason'] . '</b>', 'left');
				$y -= 10;
			}
			$this->backend->check_page_length($y);
			$y = $y - $this->backend->text_align_left($x, $y, $font_size, '<b>' . trans('Corrected to:') . '</b>');
		}

		// pozycje faktury
		$i = 0;
		if (isset($this->data['content']))
			foreach ($this->data['content'] as $item) {
				$data[$i]['no'] = $i + 1;
				$data[$i]['name'] = $item['description'];
				$data[$i]['prodid'] = $item['prodid'];
				$data[$i]['content'] = $item['content'];
				$data[$i]['count'] = sprintf('%.2f', $item['count']);
				$item['pdiscount'] = floatval($item['pdiscount']);
				$item['vdiscount'] = floatval($item['vdiscount']);
				if (!empty($item['pdiscount']))
					$data[$i]['discount'] = sprintf('%.2f %%', $item['pdiscount']);
				elseif (!empty($item['vdiscount']))
					$data[$i]['discount'] = moneyf($item['vdiscount']);
				$data[$i]['basevalue'] = moneyf($item['basevalue']);
				$data[$i]['totalbase'] = moneyf($item['totalbase']);
				$data[$i]['taxlabel'] = $item['taxlabel'];
				$data[$i]['totaltax'] = moneyf($item['totaltax']);
				$data[$i]['total'] = moneyf($item['total']);

				$i++;
			}

		//$this->backend->ezSetY($y);
		$this->backend->ezSetY($y + 3);
		$y = $this->backend->ezTable($data, $cols, '', $params);

		$y -= 10;
		$this->backend->check_page_length($y);

		// podsumowanie podatku
		$data2[0]['totalbase'] = moneyf($this->data['totalbase']);
		$data2[0]['taxlabel'] = "<b>x</b>";
		$data2[0]['totaltax'] = moneyf($this->data['totaltax']);
		$data2[0]['total'] = moneyf($this->data['total']);

		$this->backend->ezSetY($y);
		$y = $this->backend->ezTable($data2, NULL, '', $params2);
		$data2 = array();

		$fy = $y + $this->backend->GetFontHeight($font_size) / 2;
		$this->backend->text_align_right($xx - 5, $fy, $font_size, '<b>' . trans('Total:') . '</b>');

		$return[1] = $y;

		$this->backend->check_page_length($y);
		$fy = $y - $margin - $this->backend->GetFontHeight($font_size);
		$this->backend->text_align_right($xx - 5, $fy, $font_size, '<b>' . trans('in it:') . '</b>');

		if (isset($this->data['taxest'])) {
			$i = 0;
			foreach ($this->data['taxest'] as $item) {
				$data2[$i]['totalbase'] = moneyf($item['base']);
				$data2[$i]['taxlabel'] = $item['taxlabel'];
				$data2[$i]['totaltax'] = moneyf($item['tax']);
				$data2[$i]['total'] = moneyf($item['total']);
				$i++;
			}
			//$this->backend->ezSetY($y);
			$this->backend->ezSetY($y + 3);
			$y = $this->backend->ezTable($data2, NULL, '', $params2);
			$data2 = array();
		}

		if (isset($this->data['invoice'])) {
			$total = $this->data['total'] - $this->data['invoice']['total'];
			$totalbase = $this->data['totalbase'] - $this->data['invoice']['totalbase'];
			$totaltax = $this->data['totaltax'] - $this->data['invoice']['totaltax'];

			$y -= 10;
			$fy = $y - $margin - $this->backend->GetFontHeight($font_size);
			$this->backend->text_align_right($xx - 5, $fy, $font_size, '<b>' . trans('Difference value:') . '</b>');

			$data2[0]['totalbase'] = ($totalbase>0 ? '+' : '') . moneyf($totalbase);
			$data2[0]['taxlabel'] = "<b>x</b>";
			$data2[0]['totaltax'] = ($totaltax>0 ? '+' : '') . moneyf($totaltax);
			$data2[0]['total'] = ($total>0 ? '+' : '') . moneyf($total);

			$this->backend->ezSetY($y);
			$y = $this->backend->ezTable($data2, NULL, '', $params2);
			$data2 = array();
		}

		$return[2] = $y;

		return $return;
	}

	protected function invoice_to_pay($x, $y) {
		if (isset($this->data['rebate']))
			$y = $y - $this->backend->text_align_left($x,$y,14, trans('To repay:') . ' ' . moneyf($this->data['value']));
		else
			$y = $y - $this->backend->text_align_left($x,$y,14, trans('To pay:') . ' ' . moneyf($this->data['value']));
		$y = $y - $this->backend->text_align_left($x,$y,10, trans('In words:') . ' ' . trans('$a dollars $b cents', to_words(floor($this->data['value'])), to_words(round(($this->data['value']-floor($this->data['value']))*100))));
		return $y;
	}

	protected function invoice_expositor($x, $y) {
		$expositor = $this->data['division_author'];

		if ($expositor)
			$y = $y - $this->backend->text_align_left($x,$y,10, trans('Expositor:') . ' ' . $expositor);

		return $y;
	}

	protected function invoice_footnote($x, $y, $width, $font_size) {
		if (!empty($this->data['division_footer'])) {
			$y = $y - $this->backend->getFontHeight($font_size);
			//$y = $y - $this->backend->text_align_left($x, $y, $font_size, '<b>' . trans('Notes:') . '</b>');
			$tmp = $this->data['division_footer'];

			$accounts = array(bankaccount($this->data['customerid'], $this->data['account']));
			if (ConfigHelper::checkConfig('invoices.show_all_accounts'))
				$accounts = array_merge($accounts, $this->data['bankaccounts']);
			foreach ($accounts as &$account)
				$account = format_bankaccount($account);
			$tmp = str_replace('%bankaccount', implode("\n", $accounts), $tmp);

			$tmp = preg_split('/\r?\n/', $tmp);
			foreach ($tmp as $line)
				$y = $this->backend->text_wrap($x, $y, $width, $font_size, $line, "full");
		}
	}

	public function invoice_body_standard() {
		$page = $this->backend->ezStartPageNumbers($this->backend->ez['pageWidth']-50,20,8,'right',trans('Page $a of $b', '{PAGENUM}','{TOTALPAGENUM}'),1);
		$top = 800;
		$this->invoice_dates(500, 800);
		$this->invoice_address_box(400, 700);
		$top = $this->invoice_title(30, $top);
		$top = $top - 20;
		$top = $this->invoice_seller(30, $top);
		$top = $top - 20;
		$top = $this->invoice_buyer(30, $top);
		$top = $top - 20;
		$return = $this->new_invoice_data(30, $top, 530, 7, 2);
		$return[1] += 5;
		$this->invoice_expositor(30, $return[1] - 20);
		$top = $return[2] - 20;
		$top = $this->invoice_to_pay(30, $top);
		$top = $top - 20;
		$this->invoice_footnote(30, $top, 530, 10);
		$page = $this->backend->ezStopPageNumbers(1, 1, $page);
	}

	public function invoice_body_ft0100() {
		$page = $this->backend->ezStartPageNumbers($this->backend->ez['pageWidth']/2+10,$this->backend->ez['pageHeight']-30,8,'',trans('Page $a of $b', '{PAGENUM}','{TOTALPAGENUM}'),1);
		$top = $this->backend->ez['pageHeight']-50;
		$this->invoice_dates(500, $top);
		$this->invoice_address_box(400, 700);
		$top = $this->invoice_title(30, $top);
		$top = $top - 10;
		$top = $this->invoice_seller(30, $top);
		$top = $top - 10;
		$top = $this->invoice_buyer(30, $top);
		$top = $top - 10;
		$this->invoice_footnote(470, $top, 90, 8);
		$return = $this->new_invoice_data(30, $top, 430, 6, 1);
		$top = $return[2] - 10;
		$return[1] += 5;
		$this->invoice_expositor(30, $return[1]);
		$this->invoice_to_pay(30, $top);
		$this->backend->check_page_length($top, 200);
		if ($this->data['customerbalance'] < 0 || ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.always_show_form', true))) {
			$this->invoice_main_form_fill(187, 3, 0.4);
			$this->invoice_simple_form_fill(14, 3, 0.4);
		}
		$page = $this->backend->ezStopPageNumbers(1,1,$page);
	}
}

?>

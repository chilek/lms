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
// Faktury w PDF, do użycia z formularzami FT-0100 (c) Polarnet
// w razie pytań mailto:lexx@polarnet.org

function invoice_simple_form_fill($x,$y,$scale)
{
    global $pdf,$invoice;
    $pdf->setlinestyle(1);

    $pdf->line(7*$scale+$x,724*$scale+$y,7*$scale+$x,694*$scale+$y);
    $pdf->line(7*$scale+$x,724*$scale+$y,37*$scale+$x,724*$scale+$y);
    $pdf->line(370*$scale+$x,724*$scale+$y,370*$scale+$x,694*$scale+$y);
    $pdf->line(370*$scale+$x,724*$scale+$y,340*$scale+$x,724*$scale+$y);
    $pdf->line(7*$scale+$x,197*$scale+$y,7*$scale+$x,227*$scale+$y);
    $pdf->line(7*$scale+$x,197*$scale+$y,37*$scale+$x,197*$scale+$y);
    $pdf->line(370*$scale+$x,197*$scale+$y,370*$scale+$x,227*$scale+$y);
    $pdf->line(370*$scale+$x,197*$scale+$y,340*$scale+$x,197*$scale+$y);

    $shortname = $invoice['division_shortname'];
    $address = $invoice['division_address'];
    $zip = $invoice['division_zip'];
    $city = $invoice['division_city'];
    $account = bankaccount($invoice['customerid'], $invoice['account']);

    text_autosize(15*$scale+$x,568*$scale+$y,30*$scale, $shortname,350*$scale);
    text_autosize(15*$scale+$x,534*$scale+$y,30*$scale, $address,350*$scale);
    text_autosize(15*$scale+$x,500*$scale+$y,30*$scale, $zip.' '.$city,350*$scale);

    //text_autosize(15*$scale+$x,683*$scale+$y,30*$scale, substr($tmp,0,17),350*$scale);
    //text_autosize(15*$scale+$x,626*$scale+$y,30*$scale, substr($tmp,18,200),350*$scale);
    text_autosize(15*$scale+$x,683*$scale+$y,30*$scale, format_bankaccount($account), 350*$scale);
    if (ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.customer_balance_in_form', false)))
        $value = $invoice['customerbalance'] * -1;
    else
        $value = $invoice['total'];
    text_autosize(15*$scale+$x,445*$scale+$y,30*$scale,"*".number_format($value,2,',','')."*",350*$scale);

    text_autosize(15*$scale+$x,390*$scale+$y,30*$scale, $invoice['name'],350*$scale);
    text_autosize(15*$scale+$x,356*$scale+$y,30*$scale, $invoice['address'],350*$scale);
    text_autosize(15*$scale+$x,322*$scale+$y,30*$scale, $invoice['zip'].' '.$invoice['city'],350*$scale);

    if (ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.customer_balance_in_form', false)))
        text_autosize(15*$scale+$x,215*$scale+$y,30*$scale,trans('Payment for liabilities'),350*$scale);
    else {
        $tmp = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);
        text_autosize(15*$scale+$x,215*$scale+$y,30*$scale,trans('Payment for invoice No. $a', $tmp),350*$scale);
    }
}

function invoice_main_form_fill($x,$y,$scale)
{
    global $pdf,$invoice;
    $pdf->setlinestyle(1);

    $pdf->line(7*$scale+$x,724*$scale+$y,7*$scale+$x,694*$scale+$y);
    $pdf->line(7*$scale+$x,724*$scale+$y,37*$scale+$x,724*$scale+$y);
    $pdf->line(970*$scale+$x,724*$scale+$y,970*$scale+$x,694*$scale+$y);
    $pdf->line(970*$scale+$x,724*$scale+$y,940*$scale+$x,724*$scale+$y);
    $pdf->line(7*$scale+$x,172*$scale+$y,7*$scale+$x,202*$scale+$y);
    $pdf->line(7*$scale+$x,172*$scale+$y,37*$scale+$x,172*$scale+$y);

    $name = $invoice['division_name'];
    $address = $invoice['division_address'];
    $zip = $invoice['division_zip'];
    $city = $invoice['division_city'];
    $account = bankaccount($invoice['customerid'], $invoice['account']);

    text_autosize(15*$scale+$x,680*$scale+$y,30*$scale,$name,950*$scale);
    text_autosize(15*$scale+$x,617*$scale+$y,30*$scale,$address." ".$zip." ".$city,950*$scale);
    text_autosize(15*$scale+$x,555*$scale+$y,30*$scale, format_bankaccount($account), 950*$scale);
    $pdf->addtext(330*$scale+$x,495*$scale+$y,30*$scale,'X');
    text_autosize(550*$scale+$x,495*$scale+$y,30*$scale,"*".number_format($invoice['total'],2,',','')."*",400*$scale);
    if (ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.customer_balance_in_form', false)))
        $value = $invoice['customerbalance'] * -1;
    else
        $value = $invoice['total'];
    text_autosize(15*$scale+$x,434*$scale+$y,30*$scale, trans('$a dollars $b cents',to_words(floor($value)),to_words(round(($value-floor($value))*100))),950*$scale);
    text_autosize(15*$scale+$x,372*$scale+$y,30*$scale, $invoice['name'],950*$scale);
    text_autosize(15*$scale+$x,312*$scale+$y,30*$scale, $invoice['address']." ".$invoice['zip']." ".$invoice['city'],950*$scale);
    if (ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.customer_balance_in_form', false)))
        text_autosize(15*$scale+$x,250*$scale+$y,30*$scale, trans('Payment for liabilities'),950*$scale);
    else {
        $tmp = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);
        text_autosize(15*$scale+$x,250*$scale+$y,30*$scale, trans('Payment for invoice No. $a',$tmp),950*$scale);
    }
}

function invoice_dates($x,$y)
{
	global $invoice, $pdf;
	$font_size = 12;
	text_align_right($x, $y, $font_size, trans('Settlement date:').' ');
	$y = $y - text_align_left($x, $y, $font_size, date("Y/m/d", $invoice['cdate']));
	text_align_right($x, $y, $font_size, trans('Sale date:').' ');
	$y = $y - text_align_left($x, $y, $font_size, date("Y/m/d", $invoice['sdate']));
	text_align_right($x, $y, $font_size, trans('Deadline:').' ');
	$y = $y - text_align_left($x, $y, $font_size, date("Y/m/d", $invoice['pdate']));
	text_align_right($x, $y, $font_size, trans('Payment type:').' ');
	$y = $y - text_align_left($x, $y, $font_size, $invoice['paytypename']);
	return $y;
}

function invoice_buyer($x,$y) 
{
    global $invoice,$pdf;
    $font_size=10;
    $y=$y-text_align_left($x,$y,$font_size,'<b>' . trans('Purchaser:') . '</b>');
    $y=text_wrap($x,$y,350,$font_size, $invoice['name'],'left');
    $y=$y-text_align_left($x,$y,$font_size, $invoice['address']);
    $y=$y-text_align_left($x,$y,$font_size, $invoice['zip']." ".$invoice['city']);
    if ($invoice['division_countryid'] && $invoice['countryid'] && $invoice['division_countryid'] != $invoice['countryid'])
        $y=$y-text_align_left($x,$y,$font_size, trans($invoice['country']));
    if ($invoice['ten']) 
	$y=$y-text_align_left($x,$y,$font_size, trans('TEN').' '.$invoice['ten']);
    else if ($invoice['ssn']) 
	$y=$y-text_align_left($x,$y,$font_size, trans('SSN').' '.$invoice['ssn']);
    $y=$y-text_align_left($x,$y,$font_size,'<b>' . trans('Customer No.: $a',sprintf('%04d',$invoice['customerid'])) . '</b>');
    return $y;
}

function invoice_seller($x, $y)
{
	global $pdf, $invoice;

	$font_size = 10;
	$y = $y - text_align_left($x, $y, $font_size, '<b>' . trans('Seller:') . '</b>');
	$tmp = $invoice['division_header'];

	$account = format_bankaccount(bankaccount($invoice['customerid'], $invoice['account']));
	$tmp = str_replace('%bankaccount', $account, $tmp);

	$tmp = preg_split('/\r?\n/', $tmp);
	foreach ($tmp as $line) $y = $y-text_align_left($x, $y, $font_size, $line);

	return $y;
}

function invoice_title($x,$y) 
{
    global $invoice,$pdf,$type;
    $font_size = 16;
    $tmp = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);
    if(isset($invoice['invoice']))
    	$y=$y-text_align_left($x,$y,$font_size,'<b>' . trans('Credit Note No. $a',$tmp) . '</b>');
    else
	$y=$y-text_align_left($x,$y,$font_size,'<b>' . trans('Invoice No. $a',$tmp) . '</b>');
    
    if(isset($invoice['invoice']))
    {
	$font_size = 12; $y += 8;
	$tmp = docnumber($invoice['invoice']['number'], $invoice['invoice']['template'], $invoice['invoice']['cdate']);
	$y=$y-text_align_left($x,$y,$font_size,'<b>' . trans('for Invoice No. $a',$tmp) . '</b>');
	$y -= 5;
    }
    
    //$font_size = 16;
    //$y = $y - text_align_left($x, $y, $font_size, $type);

    if($type==trans('DUPLICATE'))
    {
	    $font_size = 12;
	    $y=$y-text_align_left($x,$y+4,$font_size, trans('Duplicate draw-up date:') . ' ' . date('Y/m/d'));
    }
    
    if(isset($invoice['invoice']))
    	$y += 10;
    return $y;
}

function invoice_address_box($x,$y) 
{
	global $invoice, $pdf;
	$font_size = 12;
/*
	$invoice_name = $invoice['name'];
	if (strlen($invoice_name)>25) 
		$invoice_name = preg_replace('/(.{25})/',"$b<i>&gt;</i>\n",$invoice_name);
	$tmp = preg_split('/\r?\n/', $invoice_name);
	foreach ($tmp as $line) $y=$y-text_align_left($x,$y,$font_size,"<b>".$line."</b>");
*/
	if ($invoice['post_name'] || $invoice['post_address']) {
		if ($invoice['post_name'])
			$y = text_wrap($x, $y, 160, $font_size, '<b>' . $invoice['post_name'] . '</b>', 'left');
		else
			$y = text_wrap($x, $y, 160, $font_size, '<b>' . $invoice['name'] . '</b>', 'left');
		$y = $y - text_align_left($x, $y, $font_size, '<b>' . $invoice['post_address'] . '</b>');
		$y = $y - text_align_left($x, $y, $font_size, '<b>' . $invoice['post_zip'] . " " . $invoice['post_city'] . '</b>');
	} else {
		$y = text_wrap($x, $y, 160, $font_size, '<b>' . $invoice['name'] . '</b>', 'left');
		$y = $y - text_align_left($x, $y, $font_size, '<b>' . $invoice['address'] . '</b>');
		$y = $y - text_align_left($x, $y, $font_size, '<b>' . $invoice['zip'] . " " . $invoice['city'] . '</b>');
	}
	return $y;
}

function invoice_data_row($x,$y,$width,$font_size,$margin,$data,$t_width,$t_justify) 
{
    global $pdf;
    $fy=$y-$margin-$pdf->GetFontHeight($font_size);
    $left = $x+$margin;
    $ny = $fy;
    $cols = sizeof($data);
    for ($i = 1; $i <= $cols; $i++) {
	$ly = text_wrap($left+$margin, $fy, $t_width[$i]-2*$margin, $font_size,$data[$i],$t_justify[$i]);
	$left = $left + $t_width[$i]+2*$margin;
	if ($ly<$ny) $ny=$ly;
    }
    $left = $x;
    for ($i = 1; $i <= $cols; $i++) {
	$pdf->line($left, $y, $left, $ny+$font_size/2);
	$left = $left + $t_width[$i]+2*$margin;
    }
    $pdf->line($left, $y, $left, $ny+$font_size/2);
    $y=$ny+$font_size/2;
    $pdf->line($x,$y,$x+$width,$y);
    return($y);
}

function invoice_short_data_row($x,$y,$width,$font_size,$margin,$data,$t_width,$t_justify) 
{
    global $pdf;
    $fy=$y-$margin-$pdf->GetFontHeight($font_size);    
    $left = $x+$margin;
    $ny = $fy;
    $cols = sizeof($data);
    for ($i = $cols-3; $i <= $cols; $i++) {
	$ly = text_wrap($left+$margin, $fy, $t_width[$i]-2*$margin, $font_size,$data[$i],$t_justify[$i]);
	$left = $left + $t_width[$i]+2*$margin;
	if ($ly<$ny) $ny=$ly;
    }
    $left = $x;
    for ($i = $cols-3; $i <= $cols; $i++) {
	$pdf->line($left, $y, $left, $ny+$font_size/2);
	$left = $left + $t_width[$i]+2*$margin;
    }
    $pdf->line($left, $y, $left, $ny+$font_size/2);
    $y=$ny+$font_size/2;
    //$pdf->line($x,$y,$x+$width,$y);
    $v = $cols-3;
    $pdf->line($x,$y,$x+$t_width[$v++]+$t_width[$v++]+$t_width[$v++]+$t_width[$v++]+8*$margin,$y);
    return($y);
}

function invoice_data($x, $y, $width, $font_size, $margin)
{
	global $invoice, $pdf;

	$pdf->setlinestyle(0.5);
	$pdf->line($x, $y, $x + $width, $y);

	$v = 1;
	$t_data[$v++] = '<b>' . trans('No.') . '</b>';
	$t_data[$v++] = '<b>' . trans('Name of Product, Commodity or Service:') . '</b>';
	$t_data[$v++] = '<b>' . trans('Product ID:') . '</b>';
	$t_data[$v++] = '<b>' . trans('Unit:') . '</b>';
	$t_data[$v++] = '<b>' . trans('Amount:') . '</b>';
	if (!empty($invoice['pdiscount']) || !empty($invoice['vdiscount']))
		$t_data[$v++] = '<b>' . trans('Discount:') . '</b>';
	$t_data[$v++] = '<b>' . trans('Unitary Net Value:') . '</b>';
	$t_data[$v++] = '<b>' . trans('Net Value:') . '</b>';
	$t_data[$v++] = '<b>' . trans('Tax Rate:') . '</b>';
	$t_data[$v++] = '<b>' . trans('Tax Value:') . '</b>';
	$t_data[$v++] = '<b>' . trans('Gross Value:') . '</b>';

	for ($i = 1; $i < $v; $i++) $t_justify[$i] = "center";
	for ($i = 1; $i < $v; $i++) $t_width[$i] = getWrapTextWidth($font_size, $t_data[$i]) + 2 * $margin + 2;

	// tutaj jeszcze trzeba będzie sprawdzić jaką szerokość mają pola w tabelce później
	if ($invoice['content'])
		foreach ($invoice['content'] as $item)
		{
			$v = 2;
			$tt_width[$v++] = $pdf->getTextWidth($font_size, $item['description']);
			$tt_width[$v++] = $pdf->getTextWidth($font_size, $item['prodid']);
			$tt_width[$v++] = $pdf->getTextWidth($font_size, $item['content']);
			$tt_width[$v++] = $pdf->getTextWidth($font_size, sprintf('%.2f', $item['count']));
			if (!empty($invoice['pdiscount']))
				$tt_width[$v] = $pdf->getTextWidth($font_size, sprintf('%.2f %%', $item['pdiscount']));
			if (!empty($invoice['vdiscount']))
			{
				$tmp_width = $pdf->getTextWidth($font_size, moneyf($item['vdiscount']));
				if ($tmp_width > $tt_width[$v])
					$tt_width[$v] = $tmp_width;
			}
			if (!empty($invoice['pdiscount']) || !empty($invoice['vdiscount']))
				$v++;
			$tt_width[$v++] = $pdf->getTextWidth($font_size, moneyf($item['basevalue'])) + 6;
			$tt_width[$v++] = $pdf->getTextWidth($font_size, moneyf($item['totalbase'])) + 6;
			$tt_width[$v++] = $pdf->getTextWidth($font_size, $item['taxlabel']) + 6;
			$tt_width[$v++] = $pdf->getTextWidth($font_size, moneyf($item['totaltax'])) + 6;
			$tt_width[$v++] = $pdf->getTextWidth($font_size, moneyf($item['total'])) + 6;
			for ($i = 2; $i < $v; $i++) 
				if(($tt_width[$i] + 2 * $margin + 2) > $t_width[$i])
					$t_width[$i] = $tt_width[$i] + 2 * $margin + 2;
		}

	if (isset($invoice['invoice']['content']))
		foreach ($invoice['invoice']['content'] as $item)
		{
			$v = 2;
			$tt_width[$v++] = $pdf->getTextWidth($font_size, $item['description']);
			$tt_width[$v++] = $pdf->getTextWidth($font_size, $item['prodid']);
			$tt_width[$v++] = $pdf->getTextWidth($font_size, $item['content']);
			$tt_width[$v++] = $pdf->getTextWidth($font_size, sprintf('%.2f', $item['count']));
			if (!empty($invoice['pdiscount']))
				$tt_width[$v] = $pdf->getTextWidth($font_size, sprintf('%.2f %%', $item['pdiscount']));
			if (!empty($invoice['vdiscount']))
			{
				$tmp_width = $pdf->getTextWidth($font_size, moneyf($item['vdiscount']));
				if ($tmp_width > $tt_width[$v])
					$tt_width[$v] = $tmp_width;
			}
			if (!empty($invoice['pdiscount']) || !empty($invoice['vdiscount']))
				$v++;
			$tt_width[$v++] = $pdf->getTextWidth($font_size, moneyf($item['basevalue'])) + 6;
			$tt_width[$v++] = $pdf->getTextWidth($font_size, moneyf($item['totalbase'])) + 6;
			$tt_width[$v++] = $pdf->getTextWidth($font_size, $item['taxlabel']) + 6;
			$tt_width[$v++] = $pdf->getTextWidth($font_size, moneyf($item['totaltax'])) + 6;
			$tt_width[$v++] = $pdf->getTextWidth($font_size, moneyf($item['total'])) + 6;
			for ($i = 2; $i < $v; $i++) 
				if(($tt_width[$i] + 2 * $margin + 2) > $t_width[$i])
					$t_width[$i] = $tt_width[$i] + 2 * $margin + 2;
		}
	// Kolumna 2 będzie miała rozmiar ustalany dynamicznie
	$t_width[2] = $width - ($t_width[1] + $t_width[3] + $t_width[4] + $t_width[5] + $t_width[6] + $t_width[7]
		+ $t_width[8] + $t_width[9] + $t_width[10] + (!empty($invoice['pdiscount']) || !empty($invoice['vdiscount']) ? $t_width[11] : 0)
		+ 2 * $margin * (!empty($invoice['pdiscount']) || !empty($invoice['vdiscount']) ? 11 : 10));
	$y = invoice_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);
	$t_justify[11] = $t_justify[10] = $t_justify[9] = $t_justify[8] = $t_justify[7] = $t_justify[6] = $t_justify[5] = "right";
	$t_justify[2] = 'left';

	if (isset($invoice['invoice']))
	{
		// we have credit note, so first print corrected invoice data
		$xx = $x;
		$y = $y-text_align_left($x, $y - 10, $font_size, '<b>' . trans('Was:') . '</b>');
		$y -= 6;
		$pdf->line($x, $y, $x + $width, $y);
		$lp = 1;
		if ($invoice['invoice']['content']) 
			foreach ($invoice['invoice']['content'] as $item)
			{
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
				elseif (!empty($invoice['pdiscount']) || !empty($invoice['vdiscount']))
					$t_data[$v++] = '';
				$t_data[$v++] = moneyf($item['basevalue']);
				$t_data[$v++] = moneyf($item['totalbase']);
				$t_data[$v++] = $item['taxlabel'];
				$t_data[$v++] = moneyf($item['totaltax']);
				$t_data[$v++] = moneyf($item['total']);

			$lp++;
			$y = invoice_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);
			}

		$x = $x + (!empty($invoice['pdiscount']) || !empty($invoice['vdiscount']) ? 7 : 6) * 2 * $margin + $t_width[1] + $t_width[2] + $t_width[3]
			+ $t_width[4] + $t_width[5] + $t_width[6] + (!empty($invoice['pdiscount']) || !empty($invoice['vdiscount']) ? $t_width[7] : 0);

		$fy=$y-$margin-$pdf->GetFontHeight($font_size);
		text_align_right($x - $margin, $fy, $font_size, '<b>' . trans('Total:') . '</b>');

		$v = (!empty($invoice['pdiscount']) || !empty($invoice['vdiscount'])) ? 8 : 7;
		$t_data[$v++] = moneyf($invoice['invoice']['totalbase']);
		$t_data[$v++] = "<b>x</b>";
		$t_data[$v++] = moneyf($invoice['invoice']['totaltax']);
		$t_data[$v++] = moneyf($invoice['invoice']['total']);

		$y = invoice_short_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);
		$y -= 5;

		$fy = $y - $margin - $pdf->GetFontHeight($font_size);
		text_align_right($x - $margin, $fy, $font_size, '<b>' . trans('in it:') . '</b>');
		$v = (!empty($invoice['pdiscount']) || !empty($invoice['vdiscount'])) ? 8 : 7;
		$pdf->line($x, $y, $x + $t_width[$v++] + $t_width[$v++] + $t_width[$v++] + $t_width[$v++] + 8 * $margin, $y);

		if ($invoice['invoice']['taxest']) 
			foreach ($invoice['invoice']['taxest'] as $item)
			{
				$v = (!empty($invoice['pdiscount']) || !empty($invoice['vdiscount'])) ? 8 : 7;
				$t_data[$v++] = moneyf($item['base']);
				$t_data[$v++] = $item['taxlabel'];
				$t_data[$v++] = moneyf($item['tax']);
				$t_data[$v++] = moneyf($item['total']);
				$y = invoice_short_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);
			}

		$x = $xx;
		if ($invoice['reason'] != '')
		{
			$y = $y - text_align_left($x, $y - 10, $font_size, '<b>' . trans('Reason:') . ' ' . $invoice['reason'] . '</b>');
			$y -= 10;
		}
		$y = $y - text_align_left($x, $y - 10, $font_size, '<b>' . trans('Corrected to:') . '</b>');
		$y -= 5;
		$pdf->line($x, $y, $x + $width, $y);
	}

	$lp = 1;
	if ($invoice['content'])
		foreach ($invoice['content'] as $item)
		{
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
			elseif (!empty($invoice['pdiscount']) || !empty($invoice['vdiscount']))
				$t_data[$v++] = '';
			$t_data[$v++] = moneyf($item['basevalue']);
			$t_data[$v++] = moneyf($item['totalbase']);
			$t_data[$v++] = $item['taxlabel'];
			$t_data[$v++] = moneyf($item['totaltax']);
			$t_data[$v++] = moneyf($item['total']);

			$lp++;
			$y = invoice_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);
		}

	$return[1] = $y;

	$x = $x + (!empty($invoice['pdiscount']) || !empty($invoice['vdiscount']) ? 7 : 6) * 2 * $margin + $t_width[1] + $t_width[2] + $t_width[3]
		+ $t_width[4] + $t_width[5] + $t_width[6] + (!empty($invoice['pdiscount']) || !empty($invoice['vdiscount']) ? $t_width[7] : 0);

	$fy = $y - $margin - $pdf->GetFontHeight($font_size);
	text_align_right($x - $margin, $fy, $font_size, '<b>' . trans('Total:') . '</b>');

	$v = (!empty($invoice['pdiscount']) || !empty($invoice['vdiscount'])) ? 8 : 7;
	$t_data[$v++] = moneyf($invoice['totalbase']);
	$t_data[$v++] = "<b>x</b>";
	$t_data[$v++] = moneyf($invoice['totaltax']);
	$t_data[$v++] = moneyf($invoice['total']);

	$y = invoice_short_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);

	$y = $y - 5;

	$fy = $y - $margin - $pdf->GetFontHeight($font_size);
	text_align_right($x - $margin, $fy, $font_size, '<b>' . trans('in it:') . '</b>');
	$v = (!empty($invoice['pdiscount']) || !empty($invoice['vdiscount'])) ? 8 : 7;
	$pdf->line($x, $y, $x + $t_width[$v++] + $t_width[$v++] + $t_width[$v++] + $t_width[$v++] + 8 * $margin, $y);

	if ($invoice['taxest'])
		foreach ($invoice['taxest'] as $item) 
		{
			$v = (!empty($invoice['pdiscount']) || !empty($invoice['vdiscount'])) ? 8 : 7;
			$t_data[$v++] = moneyf($item['base']);
			$t_data[$v++] = $item['taxlabel'];
			$t_data[$v++] = moneyf($item['tax']);
			$t_data[$v++] = moneyf($item['total']);
			$y = invoice_short_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);
		}

	if (isset($invoice['invoice']))
	{
		$total = $invoice['total'] - $invoice['invoice']['total'];
		$totalbase = $invoice['totalbase'] - $invoice['invoice']['totalbase'];
		$totaltax = $invoice['totaltax'] - $invoice['invoice']['totaltax'];

		$y = $y - 5;
		$fy = $y - $margin - $pdf->GetFontHeight($font_size);
		$v = (!empty($invoice['pdiscount']) || !empty($invoice['vdiscount'])) ? 8 : 7;
		$pdf->line($x, $y, $x + $t_width[$v++] + $t_width[$v++] + $t_width[$v++] + $t_width[$v++] + 8 * $margin, $y);
		text_align_right($x - $margin, $fy, $font_size, '<b>' . trans('Difference value:') . '</b>');

		$v = (!empty($invoice['pdiscount']) || !empty($invoice['vdiscount'])) ? 8 : 7;
		$t_data[$v++] = ($totalbase > 0 ? '+' : '') . moneyf($totalbase);
		$t_data[$v++] = "<b>x</b>";
		$t_data[$v++] = ($totaltax > 0 ? '+' : '') . moneyf($totaltax);
		$t_data[$v++] = ($total > 0 ? '+' : '') . moneyf($total);

		$y = invoice_short_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);
	}

	$return[2] = $y;

	return $return;
}

function new_invoice_data($x, $y, $width, $font_size, $margin) 
{
	global $invoice, $pdf;

	$pdf->setlinestyle(0.5);
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
	if (!empty($invoice['pdiscount']) || !empty($invoice['vdiscount']))
		$cols['discount'] = '<b>' . trans('Discount:') . '</b>';
	$cols['basevalue'] = '<b>' . trans('Unitary Net Value:') . '</b>';
	$cols['totalbase'] = '<b>' . trans('Net Value:') . '</b>';
	$cols['taxlabel'] = '<b>' . trans('Tax Rate:') . '</b>';
	$cols['totaltax'] = '<b>' . trans('Tax Value:') . '</b>';
	$cols['total'] = '<b>' . trans('Gross Value:') . '</b>';

	foreach($cols as $name => $text)
	{
		$params['cols'][$name] = array(
			'justification' => 'center',
			'width' => getWrapTextWidth($font_size, $text) + 2 * $margin + 2,
		);
	}

	// tutaj jeszcze trzeba będzie sprawdzić jaką szerokość mają pola w tabelce później
	if ($invoice['content'])
		foreach ($invoice['content'] as $item)
		{
			$tt_width['name'] = $pdf->getTextWidth($font_size, $item['description']);
			$tt_width['prodid'] = $pdf->getTextWidth($font_size, $item['prodid']);
			$tt_width['content'] = $pdf->getTextWidth($font_size, $item['content']);
			$tt_width['count'] = $pdf->getTextWidth($font_size, sprintf('%.2f',$item['count']));
			if (!empty($invoice['pdiscount']))
				$tt_width['discount'] = $pdf->getTextWidth($font_size, sprintf('%.2f %%', $item['pdiscount']));
			if (!empty($invoice['vdiscount']))
			{
				$tmp_width = $pdf->getTextWidth($font_size, moneyf($item['vdiscount']));
				if ($tmp_width > $tt_width['discount'])
					$tt_width['discount'] = $tmp_width;
			}
			$tt_width['basevalue'] = $pdf->getTextWidth($font_size, moneyf($item['basevalue'])) + 6;
			$tt_width['totalbase'] = $pdf->getTextWidth($font_size, moneyf($item['totalbase'])) + 6;
			$tt_width['taxlabel'] = $pdf->getTextWidth($font_size, $item['taxlabel']) + 6;
			$tt_width['totaltax'] = $pdf->getTextWidth($font_size, moneyf($item['totaltax'])) + 6;
			$tt_width['total'] = $pdf->getTextWidth($font_size, moneyf($item['total'])) + 6;

			foreach($tt_width as $name => $w) 
				if (($w + 2 * $margin + 2) > $params['cols'][$name]['width'])
					$params['cols'][$name]['width'] = $w + 2 * $margin + 2;
		}

	if (isset($invoice['invoice']['content']))
		foreach ($invoice['invoice']['content'] as $item)
		{
			$tt_width['name'] = $pdf->getTextWidth($font_size, $item['description']);
			$tt_width['prodid'] = $pdf->getTextWidth($font_size, $item['prodid']);
			$tt_width['content'] = $pdf->getTextWidth($font_size, $item['content']);
			$tt_width['count'] = $pdf->getTextWidth($font_size, sprintf('%.2f', $item['count']));
			if (!empty($invoice['pdiscount']))
				$tt_width['discount'] = $pdf->getTextWidth($font_size, sprintf('%.2f %%', $item['pdiscount']));
			if (!empty($invoice['vdiscount']))
			{
				$tmp_width = $pdf->getTextWidth($font_size, moneyf($item['vdiscount']));
				if ($tmp_width > $tt_width['discount'])
					$tt_width['discount'] = $tmp_width;
			}
			$tt_width['basevalue'] = $pdf->getTextWidth($font_size, moneyf($item['basevalue'])) + 6;
			$tt_width['totalbase'] = $pdf->getTextWidth($font_size, moneyf($item['totalbase'])) + 6;
			$tt_width['taxlabel'] = $pdf->getTextWidth($font_size, $item['taxlabel']) + 6;
			$tt_width['totaltax'] = $pdf->getTextWidth($font_size, moneyf($item['totaltax'])) + 6;
			$tt_width['total'] = $pdf->getTextWidth($font_size, moneyf($item['total'])) + 6;

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
	$pdf->ezSetY($y);
	$data = array(0=>$cols);
	$y = $pdf->ezTable($data, $cols, '', $params);
	$data = array();

	foreach($cols as $name => $text)
	{
		switch($name)
		{
			case 'no': $params['cols'][$name]['justification'] = 'center'; break;
			case 'name': $params['cols'][$name]['justification'] = 'left'; break;
			default: $params['cols'][$name]['justification'] = 'right'; break;
		}
	}

	// size of taxes summary table
	$xx = $x;
	foreach($params['cols'] as $name => $value)
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

	if (isset($invoice['invoice']))
	{
		// we have credit note, so first print corrected invoice data

		$y -= 20;
		check_page_length($y);
		$y = $y - text_align_left($x, $y, $font_size, '<b>' . trans('Was:') . '</b>');

		$i = 0;
		if ($invoice['invoice']['content']) 
			foreach ($invoice['invoice']['content'] as $item)
			{
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

		$pdf->ezSetY($y);
		$y = $pdf->ezTable($data, $cols, '', $params);
		$data = array();

		$y -= 10;
		check_page_length($y);

		$data2[0]['totalbase'] = moneyf($invoice['invoice']['totalbase']);
		$data2[0]['taxlabel'] = "<b>x</b>";
		$data2[0]['totaltax'] = moneyf($invoice['invoice']['totaltax']);
		$data2[0]['total'] = moneyf($invoice['invoice']['total']);

		$pdf->ezSetY($y);
		$y = $pdf->ezTable($data2, NULL, '', $params2);
		$data2 = array();

		$fy = $y + $pdf->GetFontHeight($font_size) / 2;
		text_align_right($xx - 5, $fy, $font_size, '<b>' . trans('Total:') . '</b>');

		check_page_length($y);
		$fy = $y - $margin - $pdf->GetFontHeight($font_size);
		text_align_right($xx - 5,$fy, $font_size,'<b>' . trans('in it:') . '</b>');

		if ($invoice['invoice']['taxest']) 
		{
			$i = 0;
			foreach ($invoice['invoice']['taxest'] as $item) 
			{
				$data2[$i]['totalbase'] = moneyf($item['base']);
				$data2[$i]['taxlabel'] = $item['taxlabel'];
				$data2[$i]['totaltax'] = moneyf($item['tax']);
				$data2[$i]['total'] = moneyf($item['total']);
				$i++;
			}
			//$pdf->ezSetY($y);
			$pdf->ezSetY($y + 3);
			$y = $pdf->ezTable($data2, NULL, '', $params2);
			$data2 = array();
		}

		$y -= 20;
		if ($invoice['reason'] != '')
		{
			check_page_length($y);
			$y = text_wrap($x, $y, $width, $font_size, '<b>' . trans('Reason:') . ' ' . $invoice['reason'] . '</b>', 'left');
			$y -= 10;
		}
		check_page_length($y);
		$y = $y-text_align_left($x, $y, $font_size, '<b>' . trans('Corrected to:') . '</b>');
	}

	// pozycje faktury
	$i = 0;
	if (isset($invoice['content']))
		foreach ($invoice['content'] as $item)
		{
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

	//$pdf->ezSetY($y);
	$pdf->ezSetY($y + 3);
	$y = $pdf->ezTable($data, $cols, '', $params);

	$y -= 10;
	check_page_length($y);

	// podsumowanie podatku
	$data2[0]['totalbase'] = moneyf($invoice['totalbase']);
	$data2[0]['taxlabel'] = "<b>x</b>";
	$data2[0]['totaltax'] = moneyf($invoice['totaltax']);
	$data2[0]['total'] = moneyf($invoice['total']);

	$pdf->ezSetY($y);
	$y = $pdf->ezTable($data2, NULL, '', $params2);
	$data2 = array();

	$fy = $y + $pdf->GetFontHeight($font_size) / 2;
	text_align_right($xx - 5, $fy, $font_size, '<b>' . trans('Total:') . '</b>');

	$return[1] = $y;

	check_page_length($y);
	$fy = $y - $margin - $pdf->GetFontHeight($font_size);
	text_align_right($xx - 5, $fy, $font_size, '<b>' . trans('in it:') . '</b>');

	if (isset($invoice['taxest'])) 
	{
		$i = 0;
		foreach ($invoice['taxest'] as $item)
		{
			$data2[$i]['totalbase'] = moneyf($item['base']);
			$data2[$i]['taxlabel'] = $item['taxlabel'];
			$data2[$i]['totaltax'] = moneyf($item['tax']);
			$data2[$i]['total'] = moneyf($item['total']);
			$i++;
		}
		//$pdf->ezSetY($y);
		$pdf->ezSetY($y + 3);
		$y = $pdf->ezTable($data2, NULL, '', $params2);
		$data2 = array();
	}

	if(isset($invoice['invoice']))
	{
		$total = $invoice['total'] - $invoice['invoice']['total'];
		$totalbase = $invoice['totalbase'] - $invoice['invoice']['totalbase'];
		$totaltax = $invoice['totaltax'] - $invoice['invoice']['totaltax'];

		$y -= 10;
		$fy = $y - $margin - $pdf->GetFontHeight($font_size);
		text_align_right($xx - 5, $fy, $font_size, '<b>' . trans('Difference value:') . '</b>');

		$data2[0]['totalbase'] = ($totalbase>0 ? '+' : '') . moneyf($totalbase);
		$data2[0]['taxlabel'] = "<b>x</b>";
		$data2[0]['totaltax'] = ($totaltax>0 ? '+' : '') . moneyf($totaltax);
		$data2[0]['total'] = ($total>0 ? '+' : '') . moneyf($total);

		$pdf->ezSetY($y);
		$y = $pdf->ezTable($data2, NULL, '', $params2);
		$data2 = array();
	}

	$return[2] = $y;

	return $return;
}

function invoice_to_pay($x,$y) 
{
    global $pdf, $invoice;
    if(isset($invoice['rebate']))
	    $y = $y - text_align_left($x,$y,14, trans('To repay:') . ' ' . moneyf($invoice['value']));
    else
	    $y = $y - text_align_left($x,$y,14, trans('To pay:') . ' ' . moneyf($invoice['value']));
    $y = $y - text_align_left($x,$y,10, trans('In words:') . ' ' . trans('$a dollars $b cents', to_words(floor($invoice['value'])), to_words(round(($invoice['value']-floor($invoice['value']))*100))));
    return $y;
}

function invoice_expositor ($x,$y)
{
    global $pdf, $invoice;

    $expositor = $invoice['division_author'];

    if ($expositor) {
        $y = $y - text_align_left($x,$y,10, trans('Expositor:') . ' ' . $expositor);
    }
    return $y;
}

function invoice_footnote($x, $y, $width, $font_size)
{
	global $pdf, $invoice;

	if (!empty($invoice['division_footer'])) {
		$y = $y - $pdf->getFontHeight($font_size);
		//$y = $y - text_align_left($x, $y, $font_size, '<b>' . trans('Notes:') . '</b>');
		$tmp = $invoice['division_footer'];

		$account = format_bankaccount(bankaccount($invoice['customerid'], $invoice['account']));
		$tmp = str_replace('%bankaccount', $account, $tmp);

		$tmp = preg_split('/\r?\n/', $tmp);
		foreach ($tmp as $line) $y = text_wrap($x, $y, $width, $font_size, $line, "full");
	}
}

function invoice_body_standard()
{
	global $pdf;
	$page = $pdf->ezStartPageNumbers($pdf->ez['pageWidth']-50,20,8,'right',trans('Page $a of $b', '{PAGENUM}','{TOTALPAGENUM}'),1);
	$top=800;
	invoice_dates(500,800);    
    	invoice_address_box(400,700);
	$top=invoice_title(30,$top);
	$top=$top-20;
    	$top=invoice_seller(30,$top);
	$top=$top-20;
    	$top=invoice_buyer(30,$top);
	$top=$top-20;
    	$return=new_invoice_data(30,$top,530,7,2);
	$return[1] += 5;
	invoice_expositor(30,$return[1]-20);
    	$top=$return[2]-20;
	$top=invoice_to_pay(30,$top);
	$top=$top-20;
	invoice_footnote(30,$top,530,10);
	$page = $pdf->ezStopPageNumbers(1,1,$page);
}

function invoice_body_ft0100()
{
	global $pdf, $invoice;
	
	$page = $pdf->ezStartPageNumbers($pdf->ez['pageWidth']/2+10,$pdf->ez['pageHeight']-30,8,'',trans('Page $a of $b', '{PAGENUM}','{TOTALPAGENUM}'),1);
	$top=$pdf->ez['pageHeight']-50;
	invoice_dates(500,$top);    
    	invoice_address_box(400,700);
	$top=invoice_title(30,$top);
	$top=$top-10;
    	$top=invoice_seller(30,$top);
	$top=$top-10;
    	$top=invoice_buyer(30,$top);
	$top=$top-10;
	invoice_footnote(470,$top,90,8);
    	$return=new_invoice_data(30,$top,430,6,1);
    	$top=$return[2]-10;
	$return[1] += 5;
	invoice_expositor(30,$return[1]);
	invoice_to_pay(30,$top);
	check_page_length($top, 200);
	if ($invoice['customerbalance'] < 0 || ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.always_show_form', false))) {
		invoice_main_form_fill(187,3,0.4);
		invoice_simple_form_fill(14,3,0.4);
	}
	$page = $pdf->ezStopPageNumbers(1,1,$page);
}

?>

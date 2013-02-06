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

function receipt_header($x, $y)
{
	global $receipt, $pdf;

	$font_size = 12;
	$yy = $y;
	$xmax = $x + 420;
	$pdf->line($x, $y, $xmax, $y);
	$y -= $font_size;

//	text_align_left($x + 2,$y + 2,$font_size - 4, iconv("UTF-8", "ISO-8859-2", trans('Stamp:')));
	$y = text_wrap($x + 2, $y, 170, $font_size - 2, '<b>'.iconv("UTF-8", "ISO-8859-2", $receipt['d_name']).'</b>', NULL);
	$y = text_wrap($x + 2, $y, 170, $font_size - 2, '<b>'.iconv("UTF-8", "ISO-8859-2", $receipt['d_address']).'</b>', NULL);
	text_wrap($x + 2, $y, 170, $font_size - 2,'<b>'.iconv("UTF-8", "ISO-8859-2", $receipt['d_zip'].' '.$receipt['d_city']).'</b>', NULL);
	$y = $yy - $font_size;

	if($receipt['type'] == 'out')
		text_align_center($xmax - 70, $y - 10, $font_size + 4, '<b>'.iconv("UTF-8", "ISO-8859-2", trans('CR-out')).'</b>');
	else
		text_align_center($xmax - 70, $y - 10, $font_size + 4, '<b>'.iconv("UTF-8", "ISO-8859-2", trans('CR-in')).'</b>');
	text_align_center($xmax - 70, $y - 30, $font_size, '<b>'.iconv("UTF-8", "ISO-8859-2", trans('No. $a',$receipt['number'])).'</b>');

	if($receipt['type'] == 'out')
		$y -= text_align_center($x + 210, $y, $font_size, '<b>'.iconv("UTF-8", "ISO-8859-2", trans('Proof of Pay-out')).'</b>');
	else
		$y -= text_align_center($x + 210, $y, $font_size, '<b>'.iconv("UTF-8", "ISO-8859-2", trans('Proof of Payment')).'</b>');
	$y -= text_align_center($x + 210, $y, $font_size + 4,'<b>'.iconv("UTF-8", "ISO-8859-2", trans('Receipt')).'</b>');
	$y -= text_align_center($x + 210, $y, $font_size, iconv("UTF-8", "ISO-8859-2", trans('Date:')).' '.date("Y/m/d", $receipt['cdate']));

	$y += $font_size / 2;
	$pdf->line($x, $yy, $x, $y);
	$pdf->line($x + 140, $yy, $x + 140, $y);
	$pdf->line($x + 280, $yy, $x + 280, $y);
	$pdf->line($xmax, $yy, $xmax, $y);
	$pdf->line($x, $y, $xmax, $y);

	return $y;
}

function receipt_buyer($x,$y)
{
    global $receipt,$pdf;

    $font_size=12;
    $yy = $y;
    $xmax = $x + 420;
    $pdf->line($x, $y, $xmax, $y);
    $y -= $font_size;

    if($receipt['type'] == 'out')
    {
	    text_align_center($x+315,$y-4,$font_size+4,'<b>'.iconv("UTF-8","ISO-8859-2",trans('Has')).'</b>');
	    $y -= text_align_center($x+385,$y-4,$font_size+4,'<b>'.iconv("UTF-8","ISO-8859-2",trans('Owing')).'</b>');
    }
    else
    {
	    text_align_center($x+315,$y-4,$font_size+4,'<b>'.iconv("UTF-8","ISO-8859-2",trans('Owing')).'</b>');
	    $y -= text_align_center($x+385,$y-4,$font_size+4,'<b>'.iconv("UTF-8","ISO-8859-2",trans('Has')).'</b>');
    }
    text_align_center($x+315,$y,$font_size,'<b>'.iconv("UTF-8","ISO-8859-2",trans('Cash')).'</b>');
    $y -= text_align_center($x+385,$y,$font_size,'<b>'.iconv("UTF-8","ISO-8859-2",trans('Account<!singular:noun>')).'</b>');

    $y = $yy - $font_size;
    if($receipt['type'] == 'out')
	    text_align_left($x+2,$y+2,$font_size-4,iconv("UTF-8","ISO-8859-2",trans('To whom:')));
    else
	    text_align_left($x+2,$y+2,$font_size-4,iconv("UTF-8","ISO-8859-2",trans('From who:')));
    $y = text_wrap($x+40,$y-4,240,$font_size-2,'<b>'.iconv("UTF-8","ISO-8859-2",$receipt['name']).'</b>', NULL);
    $y = text_wrap($x+40,$y,240,$font_size-2,'<b>'.iconv("UTF-8","ISO-8859-2",$receipt['zip'].' '.$receipt['city'].' '.$receipt['address']).'</b>', NULL);

    $y += $font_size/2;
    $pdf->line($x, $yy, $x, $y);
    $pdf->line($x+280, $yy, $x+280, $y);
    $pdf->line($x+350, $yy, $x+350, $y);
    $pdf->line($xmax, $yy, $xmax, $y);
    $pdf->line($x, $y, $xmax, $y);

    return $y;
}

function receipt_footer($x,$y) 
{
    global $pdf, $CONFIG, $receipt;

    $font_size = 8;
    $yy = $y;
    $xmax = $x + 420;
    $pdf->line($x, $y, $xmax, $y);
    $y -= $font_size;
    
    text_align_center($x+35,$y,$font_size,iconv("UTF-8","ISO-8859-2",trans('Exposed By')));
    text_align_center($x+105,$y,$font_size,iconv("UTF-8","ISO-8859-2",trans('Checked By')));
    text_align_center($x+175,$y,$font_size,iconv("UTF-8","ISO-8859-2",trans('Approved By')));
    text_align_center($x+245,$y,$font_size,iconv("UTF-8","ISO-8859-2",trans('Cash Report')));
    if($receipt['type'] == 'out')
	    text_align_center($x+350,$y,$font_size,iconv("UTF-8","ISO-8859-2",trans('Above amount')));

    else
	    text_align_center($x+350,$y,$font_size,iconv("UTF-8","ISO-8859-2",trans('I confirm receipt of above amount')));

    $y -= 2;
    $pdf->line($x, $y, $xmax, $y);

    if($receipt['type'] == 'out')
    {
	    text_align_center($x+315,$y-8,$font_size,iconv("UTF-8","ISO-8859-2",trans('payed out')));
	    $y -= text_align_center($x+385,$y-8,$font_size,iconv("UTF-8","ISO-8859-2",trans('received')));
	    $y -= 34;
    }
    else
    {
	    $y -= 34;
	    $pdf->line($x+300, $y, $xmax-20, $y);
	    $y -= text_align_center($x+350,$y-8,$font_size,iconv("UTF-8","ISO-8859-2",trans('Signature of cashier')));
    }

    $pdf->line($x, $yy, $x, $y);
    $pdf->line($x+70, $yy, $x+70, $y);
    $pdf->line($x+140, $yy, $x+140, $y);
    $pdf->line($x+210, $yy, $x+210, $y);
    $pdf->line($x+280, $yy, $x+280, $y);
    if($receipt['type'] == 'out') $pdf->line($x+350, $yy-8, $x+350, $y);
    $pdf->line($xmax, $yy, $xmax, $y);
    $pdf->line($x, $y, $xmax, $y);

    $y -= text_align_right($xmax,$y-6,$font_size/2,iconv("UTF-8","ISO-8859-2",'Copyright (C) 2001-2013 LMS Developers'));
    $y -= 15;
    $pdf->setLineStyle(0.5, NULL, NULL, array(2,2));
    $pdf->line($x-10, $y, $xmax+10, $y);
    $pdf->setLineStyle(0.5, NULL, NULL, array(1,0));

    return $y;
}

function receipt_data($x,$y) 
{
    global $receipt,$pdf;

    $font_size = 12;
    $yy = $y;
    $xmax = $x + 420;
    $pdf->line($x, $y, $xmax, $y);
    $y -= 8;

    text_align_center($x+140,$y,8,iconv("UTF-8","ISO-8859-2",trans('For what')));
    text_align_center($x+315,$y,8,iconv("UTF-8","ISO-8859-2",trans('Value')));
    text_align_center($x+385,$y,8,iconv("UTF-8","ISO-8859-2",trans('Number')));
    $y -= 2;

    $pdf->line($x, $y, $xmax, $y);
    $y -= $font_size;

    $i=0;
    if($receipt['contents']) 
	foreach($receipt['contents'] as $item)
	{
	    $i++;
	    text_align_left($x+2,$y,$font_size-2,'<b>'.$i.'.</b>');
	    $y = text_wrap($x+15,$y,270,$font_size-2,iconv("UTF-8","ISO-8859-2",$item['description']),'');
	    text_align_right($x+345,$y+$font_size,$font_size-2,iconv("UTF-8","ISO-8859-2",moneyf($item['value'])));
	}

    $y += $font_size/2;
    $pdf->line($x, $y, $xmax, $y);
    $y -= $font_size;

    text_align_right($x+275,$y-6,$font_size-2,'<b>'.iconv("UTF-8","ISO-8859-2",trans('Total:')).'</b>');
    text_align_right($x+345,$y-6,$font_size-2,'<b>'.iconv("UTF-8","ISO-8859-2",moneyf($receipt['total'])).'</b>');
    $y -= text_align_center($x+385,$y,8,'Symbole');
    $y -= text_align_center($x+385,$y,8,'PL. KAS. Nr');

    $pdf->line($x, $yy, $x, $y);
    $pdf->line($x+280, $yy, $x+280, $y);
    $pdf->line($x+350, $yy, $x+350, $y);
    $pdf->line($xmax, $yy, $xmax, $y);
    $pdf->line($x, $y, $xmax, $y);
    $y -= 16;
    
    text_align_left($x+2,$y,8,iconv("UTF-8","ISO-8859-2",trans('In words:')));
    $y = text_wrap($x+40,$y,300,$font_size-2,iconv("UTF-8","ISO-8859-2",trans('$a dollars $b cents',to_words(floor($receipt['total'])),to_words($receipt['totalg']))),'');
    $y -= 8;

    $y += $font_size/2;
    $pdf->line($x, $yy, $x, $y);
    $pdf->line($x+350, $yy, $x+350, $y);
    $pdf->line($xmax, $yy, $xmax, $y);
    $pdf->line($x, $y, $xmax, $y);

    return $y;
}

function receipt_body()
{
	global $receipt,$pdf,$id,$CONFIG, $type;

	$template = $CONFIG['receipts']['template_file'];

	switch ($template)
	{
		case 'standard':
	    		$top = 800;
			$y = receipt_header(80,$top);
			$y = receipt_buyer(80,$y);
    			$y = receipt_data(80,$y);
			$y = receipt_footer(80,$y);
			if(!$type)
			{
				$y -= 20;
				$y = receipt_header(80,$y);
				$y = receipt_buyer(80,$y);
    				$y = receipt_data(80,$y);
				$y = receipt_footer(80,$y);
			}
		break;
		default:
			require($template);
		break;
	}
	if (!($receipt['last'])) $id = $pdf->newPage(1,$id,'after');
}

require_once(LIB_DIR.'/pdf.php');

$pdf = init_pdf('A4', 'portrait', trans('Receipts'));

$id = $pdf->getFirstPageId();

if(isset($_GET['print']) && $_GET['print'] == 'cached' && sizeof($_POST['marks']))
{
        $SESSION->restore('rlm', $rlm);
        $SESSION->remove('rlm');

        if(sizeof($_POST['marks']))
                foreach($_POST['marks'] as $idx => $mark)
                        $rlm[$idx] = $mark;
	if(sizeof($rlm))
	        foreach($rlm as $mark)
		        $ids[] = intval($mark);

	if(empty($ids))
	{
	        $SESSION->close();
	        die;
	}

	if(!empty($_GET['cash']))
	{
        $ids = $DB->GetCol('SELECT DISTINCT docid FROM cash, documents
            WHERE docid = documents.id AND documents.type = 2
                AND cash.id IN ('.implode(',', $ids).')');
	}

	sort($ids);

        $i = 0;
        $count = sizeof($ids);
        foreach($ids as $idx => $receiptid)
        {
                if($receipt = GetReceipt($receiptid))
        	{
			$type = $_GET['which'];
		        $i++;
		        if($i == $count) $receipt['last'] = TRUE;
			$receipt['first'] = $i > 1 ? FALSE : TRUE;
			receipt_body();
		}
	}
}
elseif($receipt = GetReceipt($_GET['id']))
{
	$type = isset($_GET['which']) ? $_GET['which'] : '';
	$receipt['last'] = TRUE;
	$receipt['first'] = TRUE;
	receipt_body();
}
else
{
	$SESSION->redirect('?m=receiptlist');
}

close_pdf($pdf);

?>

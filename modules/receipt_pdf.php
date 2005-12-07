<?php

/*
 * LMS version 1.8-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

function text_autosize($x,$y,$size,$text,$width) 
{
    global $pdf;
    while ($pdf->getTextWidth($size,$text)>$width) $size=$size-1;
    $pdf->addtext($x,$y,$size,$text);
}

function text_align_right($x,$y,$size,$text) 
{
    global $pdf;
    $pdf->addText($x-$pdf->getTextWidth($size,$text),$y,$size,$text);
    return $pdf->getFontHeight($size);
}

function text_align_left($x,$y,$size,$text) 
{
    global $pdf;
    $pdf->addText($x,$y,$size,$text);
    return $pdf->getFontHeight($size);
}

function text_align_center($x,$y,$size,$text) 
{
    global $pdf;
    $pdf->addText($x - $pdf->getTextWidth($size,$text)/2,$y,$size,$text);
    return $pdf->getFontHeight($size);
}

function text_wrap($x,$y,$width,$size,$text,$justify) 
{
    global $pdf;
    while ($text!='') 
    {
	$text = $pdf->addTextWrap($x, $y, $width, $size,$text,$justify);
	$y -= $pdf->getFontHeight($size);
    }
    return $y;
}

function receipt_header($x,$y)
{
    global $receipt,$pdf;
    
    $font_size = 12;
    $yy = $y;
    $xmax = $x + 420;
    $pdf->line($x, $y, $xmax, $y);
    $y -= $font_size;
    
    text_align_left($x+2,$y+2,$font_size-4,iconv("UTF-8","ISO-8859-2",trans('Stamp:')));
    text_align_center($xmax-70,$y-10,$font_size+4,'<b>'.iconv("UTF-8","ISO-8859-2",trans('CR')).'</b>');
    text_align_center($xmax-70,$y-30,$font_size,'<b>'.iconv("UTF-8","ISO-8859-2",trans('No. $0',$receipt['number'])).'</b>');
    
    $y -= text_align_center($x+210,$y,$font_size,'<b>'.iconv("UTF-8","ISO-8859-2",trans('Proof of Payment')).'</b>');
    $y -= text_align_center($x+210,$y,$font_size+4,'<b>'.iconv("UTF-8","ISO-8859-2",trans('Receipt')).'</b>');
    $y -= text_align_center($x+210,$y,$font_size,iconv("UTF-8","ISO-8859-2",trans('Date:')).' '.date("Y/m/d",$receipt['cdate']));
    
    $y += $font_size/2;
    $pdf->line($x, $yy, $x, $y);
    $pdf->line($x+140, $yy, $x+140, $y);
    $pdf->line($x+280, $yy, $x+280, $y);
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
    
    text_align_center($x+315,$y-4,$font_size+4,'<b>'.iconv("UTF-8","ISO-8859-2",trans('Owing')).'</b>');
    $y -= text_align_center($x+385,$y-4,$font_size+4,'<b>'.iconv("UTF-8","ISO-8859-2",trans('Has')).'</b>');
    text_align_center($x+315,$y,$font_size,'<b>'.iconv("UTF-8","ISO-8859-2",trans('Cash')).'</b>');
    $y -= text_align_center($x+385,$y,$font_size,'<b>'.iconv("UTF-8","ISO-8859-2",trans('Account<!singular:noun>')).'</b>');

    $y = $yy - $font_size;
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
    global $pdf,$CONFIG;

    $font_size = 8;
    $yy = $y;
    $xmax = $x + 420;
    $pdf->line($x, $y, $xmax, $y);
    $y -= $font_size;
    
    text_align_center($x+35,$y,$font_size,iconv("UTF-8","ISO-8859-2",trans('Exposed By')));
    text_align_center($x+105,$y,$font_size,iconv("UTF-8","ISO-8859-2",trans('Checked By')));
    text_align_center($x+175,$y,$font_size,iconv("UTF-8","ISO-8859-2",trans('Approved By')));
    text_align_center($x+245,$y,$font_size,iconv("UTF-8","ISO-8859-2",trans('Cash Report')));
    text_align_center($x+350,$y,$font_size,iconv("UTF-8","ISO-8859-2",trans('I confirm receipt of above amount')));
    $y -= 2;
    $pdf->line($x, $y, $xmax, $y);
    $y -= 34;
    $pdf->line($x+300, $y, $xmax-20, $y);
    $y -= text_align_center($x+350,$y-8,$font_size,iconv("UTF-8","ISO-8859-2",trans('Signature of cashier')));
    
    $pdf->line($x, $yy, $x, $y);
    $pdf->line($x+70, $yy, $x+70, $y);
    $pdf->line($x+140, $yy, $x+140, $y);
    $pdf->line($x+210, $yy, $x+210, $y);
    $pdf->line($x+280, $yy, $x+280, $y);
    $pdf->line($xmax, $yy, $xmax, $y);
    $pdf->line($x, $y, $xmax, $y);
    
    $y -= text_align_right($xmax,$y-6,$font_size/2,iconv("UTF-8","ISO-8859-2",'Copyright (C) 2001-2005 LMS Developers'));
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
    $y = text_wrap($x+40,$y,300,$font_size-2,iconv("UTF-8","ISO-8859-2",trans('$0 dollars $1 cents',to_words(floor($receipt['total'])),to_words($receipt['totalg']))),'');
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
	global $receipt,$pdf,$id,$CONFIG;
    
	$template = $CONFIG['receipts']['template_file'];

	switch ($template)
	{
		case 'standard':
	    		$top = 800;
			$y = receipt_header(80,$top);
			$y = receipt_buyer(80,$y);
    			$y = receipt_data(80,$y);
			$y = receipt_footer(80,$y);
			if($receipt['type']=='')
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

// brzydki hack dla ezpdf 
setlocale(LC_ALL,'C');
require_once($_LIB_DIR.'/ezpdf/class.ezpdf.php');

$diff = array(177=>'aogonek',161=>'Aogonek',230=>'cacute',198=>'Cacute',234=>'eogonek',202=>'Eogonek',
241=>'nacute',209=>'Nacute',179=>'lslash',163=>'Lslash',182=>'sacute',166=>'Sacute',
188=>'zacute',172=>'Zacute',191=>'zdot',175=>'Zdot');
//$pdf =& new Cezpdf('A4','landscape');
$pdf =& new Cezpdf('A4','portrait');
$pdf->addInfo('Producer','LMS Developers');
$pdf->addInfo('Title',iconv("UTF-8","ISO-8859-2",trans('Receipts')));
$pdf->addInfo('Creator','LMS '.$layout['lmsv']);
$pdf->setPreferences('FitWindow','1');
$pdf->ezSetMargins(0,0,0,0);
$tmp = array(
    'b'=>'arialbd.afm',
);
$pdf->setFontFamily('arial.afm',$tmp);
$pdf->setLineStyle(0.5);

$pdf->selectFont($_LIB_DIR.'/ezpdf/arialbd.afm',array('encoding'=>'WinAnsiEncoding','differences'=>$diff));
$pdf->selectFont($_LIB_DIR.'/ezpdf/arial.afm',array('encoding'=>'WinAnsiEncoding','differences'=>$diff));

$id = $pdf->getFirstPageId();

if($_GET['print'] == 'cached' && sizeof($_POST['marks']))
{
        $SESSION->restore('rlm', $rlm);
        $SESSION->remove('rlm');

        if(sizeof($_POST['marks']))
                foreach($_POST['marks'] as $idx => $mark)
                        $rlm[$idx] = $mark;
	if(sizeof($rlm))
	        foreach($rlm as $mark)
		        $ids[] = $mark;
	
	if(!$ids)
	{
	        $SESSION->close();
	        die;
	}
	
	if($_GET['cash'])
	{
	        foreach($ids as $cashid)
	                if($rid = $DB->GetOne('SELECT docid FROM cash, documents WHERE docid = documents.id AND documents.type = 2 AND cash.id = ?', array($cashid)))
			        $idsx[] = $rid;
		$ids = array_unique((array)$idsx);
	}
	
	sort($ids);

        $i = 0;
        $count = sizeof($ids);
        foreach($ids as $idx => $receiptid)
        {
                if($receipt = GetReceipt($receiptid))
        	{
			$receipt['type'] = $_GET['which'];
		        $i++;
		        if($i == $count) $receipt['last'] = TRUE;
			receipt_body();
		}
	}
}
elseif($receipt = GetReceipt($_GET['id']))
{
	$receipt['type'] = $_GET['which'];
	$receipt['last'] = TRUE;
	receipt_body();
}
else
{
	$SESSION->redirect('?m=receiptlist');
}
	
$pdf->ezStream();

?>

<?php

/*
 * LMS version 1.5-cvs
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

// Faktury w PDF, do u¿ycia z formularzami FT-0100 (c) Polarnet
// w razie pytañ mailto:lexx@polarnet.org

function invoice_simple_form_fill($x,$y,$scale)  {
    global $pdf,$invoice,$_CONFIG;
    $finances = $_CONFIG['finances'];

    $pdf->line(7*$scale+$x,724*$scale+$y,7*$scale+$x,694*$scale+$y);
    $pdf->line(7*$scale+$x,724*$scale+$y,37*$scale+$x,724*$scale+$y);
    $pdf->line(370*$scale+$x,724*$scale+$y,370*$scale+$x,694*$scale+$y);
    $pdf->line(370*$scale+$x,724*$scale+$y,340*$scale+$x,724*$scale+$y);
    $pdf->line(7*$scale+$x,197*$scale+$y,7*$scale+$x,227*$scale+$y);
    $pdf->line(7*$scale+$x,197*$scale+$y,37*$scale+$x,197*$scale+$y);
    $pdf->line(370*$scale+$x,197*$scale+$y,370*$scale+$x,227*$scale+$y);
    $pdf->line(370*$scale+$x,197*$scale+$y,340*$scale+$x,197*$scale+$y);
    
    $pdf->addtext(15*$scale+$x,568*$scale+$y,30*$scale, iconv("UTF-8","ISO-8859-2",$finances['shortname']));
    $pdf->addtext(15*$scale+$x,534*$scale+$y,30*$scale, iconv("UTF-8","ISO-8859-2",$finances['address']));
    $pdf->addtext(15*$scale+$x,500*$scale+$y,30*$scale, iconv("UTF-8","ISO-8859-2",$finances['zip']." ".$finances['city']));
    $tmp = iconv("UTF-8","ISO-8859-2",$finances['account']);
    $pdf->addtext(15*$scale+$x,683*$scale+$y,30*$scale, substr($tmp,0,17));
    $pdf->addtext(15*$scale+$x,626*$scale+$y,30*$scale, substr($tmp,18,200));
    $pdf->addtext(15*$scale+$x,445*$scale+$y,30*$scale,"**".number_format($invoice['total'],2,',','')."**");

    $font_size=30;
    while ($pdf->getTextWidth($font_size*$scale,$invoice['name'])>135)
        $font_size=$font_size-1;
    $pdf->addtext(15*$scale+$x,390*$scale+$y,$font_size*$scale,$invoice['name']);
    $font_size=30;
    while ($pdf->getTextWidth($font_size*$scale,$invoice['address'])>135)
        $font_size=$font_size-1;
    $pdf->addtext(15*$scale+$x,356*$scale+$y,$font_size*$scale,$invoice['address']);
    $pdf->addtext(15*$scale+$x,322*$scale+$y,30*$scale,$invoice['zip']." ".$invoice['city']);

    $tmp = $_CONFIG['invoices'];
    $tmp = iconv("UTF-8","ISO-8859-2",$tmp['number_template']);
    $tmp = str_replace("%N",$invoice['number'],$tmp);
    $tmp = str_replace("%Y",$invoice['year'],$tmp);
    $tmp = str_replace("%M",$invoice['month'],$tmp);

    $font_size=30;
    while ($pdf->getTextWidth($font_size*$scale,"Op³ata za FV: ".$tmp)>135)
        $font_size=$font_size-1;
    $pdf->addtext(15*$scale+$x,215*$scale+$y,$font_size*$scale,"Op³ata za FV: ".$tmp);

}

function invoice_main_form_fill($x,$y,$scale)	{
    global $pdf,$invoice,$_CONFIG;
    $finances = $_CONFIG['finances'];

    $pdf->line(7*$scale+$x,724*$scale+$y,7*$scale+$x,694*$scale+$y);
    $pdf->line(7*$scale+$x,724*$scale+$y,37*$scale+$x,724*$scale+$y);
    $pdf->line(970*$scale+$x,724*$scale+$y,970*$scale+$x,694*$scale+$y);
    $pdf->line(970*$scale+$x,724*$scale+$y,940*$scale+$x,724*$scale+$y);
    $pdf->line(7*$scale+$x,172*$scale+$y,7*$scale+$x,202*$scale+$y);
    $pdf->line(7*$scale+$x,172*$scale+$y,37*$scale+$x,172*$scale+$y);

    $pdf->addtext(15*$scale+$x,680*$scale+$y,30*$scale,iconv("UTF-8","ISO-8859-2",$finances['name']));
    $pdf->addtext(15*$scale+$x,617*$scale+$y,30*$scale,iconv("UTF-8","ISO-8859-2",$finances['address']." ".$finances['zip']." ".$finances['city']));
    $pdf->addtext(15*$scale+$x,555*$scale+$y,30*$scale,iconv("UTF-8","ISO-8859-2",$finances['account']));
    $pdf->addtext(330*$scale+$x,495*$scale+$y,30*$scale,'X');
    $pdf->addtext(550*$scale+$x,495*$scale+$y,30*$scale,number_format($invoice['total'],2,',',''));
    $pdf->addtext(15*$scale+$x,434*$scale+$y,30*$scale,to_words(floor($invoice['total']))." z³, ".to_words(round(($invoice['total']-floor($invoice['total']))*100))." gr");
    $pdf->addtext(15*$scale+$x,372*$scale+$y,30*$scale,$invoice['name']);
    $pdf->addtext(15*$scale+$x,312*$scale+$y,30*$scale,$invoice['address']." ".$invoice['zip']." ".$invoice['city']);
    $tmp = $_CONFIG['invoices'];
    $tmp = iconv("UTF-8","ISO-8859-2",$tmp['number_template']);
    $tmp = str_replace("%N",$invoice['number'],$tmp);
    $tmp = str_replace("%Y",$invoice['year'],$tmp);
    $tmp = str_replace("%M",$invoice['month'],$tmp);
    $pdf->addtext(15*$scale+$x,250*$scale+$y,30*$scale,'Op³ata za fakturê VAT nr: '.$tmp);

}

function text_align_right($x,$y,$size,$text) 
{
    global $pdf;
    $pdf->addText($x-$pdf->getTextWidth($size,$text),$y,$size,$text);
    return($pdf->getFontHeight($size));
}

function text_align_left($x,$y,$size,$text) 
{
    global $pdf;
    $pdf->addText($x,$y,$size,$text);
    return($pdf->getFontHeight($size));
}

function text_wrap($x,$y,$width,$size,$text) 
{
    global $pdf;
    while ($text!='') {
	$text = $pdf->addTextWrap($x, $y, $width, $size,$text);
	$y = $y - $pdf->getFontHeight($size);
    }
    return($y);
}

function invoice_dates($x,$y) {
    global $invoice,$pdf;
    $font_size=12;
    text_align_right($x,$y,$font_size,'Data wystawienia: ');
    $y=$y-text_align_left($x,$y,$font_size,date("d.m.Y",$invoice['cdate']));
    text_align_right($x,$y,$font_size,'Data sprzeda¿y: ');
    $y=$y-text_align_left($x,$y,$font_size,date("d.m.Y",$invoice['cdate']));
    text_align_right($x,$y,$font_size,'Termin zap³aty: ');
    $y=$y-text_align_left($x,$y,$font_size,date("d.m.Y",$invoice['pdate']));
    text_align_right($x,$y,$font_size,'Sposób zap³aty: ');
    $y=$y-text_align_left($x,$y,$font_size,$invoice['paytype']);
    return $y;
}

function invoice_buyer($x,$y) {
    global $invoice,$pdf;
    $font_size=10;
    $y=$y-text_align_left($x,$y,$font_size,'Nabywca:');
    $y=$y-text_align_left($x,$y,$font_size,$invoice['name']);
    $y=$y-text_align_left($x,$y,$font_size,$invoice['address']);
    $y=$y-text_align_left($x,$y,$font_size,$invoice['zip']." ".$invoice['city']);
    if ($invoice['phone']) $y=$y-text_align_left($x,$y,$font_size,'Tel: '.$invoice['phone']);
    if ($invoice['nip']) 
	$y=$y-text_align_left($x,$y,$font_size,'NIP: '.$invoice['nip']);
    else if ($invoice['pesel']) 
	$y=$y-text_align_left($x,$y,$font_size,'PESEL: '.$invoice['pesel']);
    $y=$y-text_align_left($x,$y,$font_size,'Numer klienta: '.sprintf('%04d',$invoice['customerid']));
    return $y;
}

function invoice_seller($x,$y) {
    global $invoice,$pdf,$_CONFIG;
    $font_size=10;
    $y=$y-text_align_left($x,$y,$font_size,'Sprzedawca:');
    $tmp = $_CONFIG['invoices'];
    $tmp = iconv("UTF-8","ISO-8859-2",$tmp['header']);
    $tmp = explode("\n",$tmp);
    foreach ($tmp as $line) $y=$y-text_align_left($x,$y,$font_size,$line);

    return $y;
}

function invoice_title($x,$y) {
    global $invoice,$pdf,$_CONFIG,$type;
    $font_size=16;
    $tmp = $_CONFIG['invoices'];
    $tmp = iconv("UTF-8","ISO-8859-2",$tmp['number_template']);
    $tmp = str_replace("%N",$invoice['number'],$tmp);
    $tmp = str_replace("%Y",$invoice['year'],$tmp);
    $tmp = str_replace("%M",$invoice['month'],$tmp);
    $y=$y-text_align_left($x,$y,$font_size,'Faktura VAT nr: '.$tmp);
    $y=$y-text_align_left($x,$y,$font_size,$type);
    return $y;
}

function invoice_address_box($x,$y) {
    global $invoice,$pdf;
    $font_size=12;
    $y=$y-text_align_left($x,$y,$font_size,$invoice['name']);
    if ($invoice['serviceaddr']) {
	$tmp = explode("\n",$invoice['serviceaddr']);
	foreach ($tmp as $line) $y=$y-text_align_left($x,$y,$font_size,$line);
    } else {
	$y=$y-text_align_left($x,$y,$font_size,$invoice['address']);
	$y=$y-text_align_left($x,$y,$font_size,$invoice['zip']." ".$invoice['city']);
    }
    return $y;
}

function invoice_data_row($x,$y,$width,$font_size,$margin,$data,$t_width) {
    global $pdf;
    $fy=$y-$margin-$pdf->GetFontHeight($font_size);    
    $left = $x+$margin;
    $ny = $fy;
    for ($i = 1; $i <= 10; $i++) {
	$ly = text_wrap($left+$margin, $fy, $t_width[$i], $font_size,$data[$i]);
	$left = $left + $t_width[$i]+2*$margin;
	if ($ly<$ny) $ny=$ly;
    }
    $left = $x;
    for ($i = 1; $i <= 10; $i++) {
	$pdf->line($left, $y, $left, $ny);
	$left = $left + $t_width[$i]+2*$margin;
    }
    $pdf->line($left, $y, $left, $ny);
    $y=$ny;
    $pdf->line($x,$y,$x+$width,$y);
    return($y);
}

function invoice_short_data_row($x,$y,$width,$font_size,$margin,$data,$t_width) {
    global $pdf;
    $fy=$y-$margin-$pdf->GetFontHeight($font_size);    
    $left = $x+$margin;
    $ny = $fy;
    for ($i = 7; $i <= 10; $i++) {
	$ly = text_wrap($left+$margin, $fy, $t_width[$i], $font_size,$data[$i]);
	$left = $left + $t_width[$i]+2*$margin;
	if ($ly<$ny) $ny=$ly;
    }
    $left = $x;
    for ($i = 7; $i <= 10; $i++) {
	$pdf->line($left, $y, $left, $ny);
	$left = $left + $t_width[$i]+2*$margin;
    }
    $pdf->line($left, $y, $left, $ny);
    $y=$ny;
    //$pdf->line($x,$y,$x+$width,$y);
    $pdf->line($x,$y,$x+$t_width[7]+$t_width[8]+$t_width[9]+$t_width[10]+8*$margin,$y);
    return($y);
}

function invoice_data($x,$y,$width) {
    global $invoice,$pdf;
    $font_size=7;
    $margin = 2;
    $pdf->setlinestyle(1);
    $pdf->line($x,$y,$x+$width,$y);
    $t_data[1] = "Lp.";
    $t_data[2] = "Nazwa wyrobu, towaru lub us³ugi:";
    $t_data[3] = "PKWiU:";
    $t_data[4] = "JM:";
    $t_data[5] = "Ilo¶æ:";
    $t_data[6] = "Cena jedn. netto:";
    $t_data[7] = "Warto¶æ netto:";
    $t_data[8] = "St. PTU:";
    $t_data[9] = "Kwota PTU:";
    $t_data[10] = "Warto¶æ brutto:";
    for ($i = 1; $i <= 10; $i++) $t_width[$i]=$pdf->getTextWidth($font_size,$t_data[$i])+2*$margin;
    // tutaj jeszcze trzeba bêdzie sprawdziæ jak± szeroko¶æ maj± pola w tabelce pu¼niej
    
    // Kolumna 2 bêdzie mia³a rozmiar ustalany dynamicznie
    $t_width[2] = $width-($t_width[1]+$t_width[3]+$t_width[4]+$t_width[5]+$t_width[6]+$t_width[7]+$t_width[8]+$t_width[9]+$t_width[10]+20*$margin);

    $y = invoice_data_row($x,$y,$width,$font_size,$margin,$t_data,$t_width);
    $lp = 1;
    foreach ($invoice['content'] as $item) {
	$t_data[1] = $lp;
	$t_data[2] = $item['description'];
	$t_data[3] = $item['pkwiu'];
	$t_data[4] = $item['content'];
	$t_data[5] = $item['count'];
	$t_data[6] = iconv("UTF-8","ISO-8859-2",moneyf($item['basevalue']));
	$t_data[7] = iconv("UTF-8","ISO-8859-2",moneyf($item['totalbase']));
	$t_data[8] = $item['taxvalue']." %";
	$t_data[9] = iconv("UTF-8","ISO-8859-2",moneyf($item['totaltax']));
	$t_data[10] = iconv("UTF-8","ISO-8859-2",moneyf($item['total']));
	
	$lp++;
	$y = invoice_data_row($x,$y,$width,$font_size,$margin,$t_data,$t_width);
    }
    
    $return[1] = $y;
    
    $x = $x + 12*$margin + $t_width[1] + $t_width[2] + $t_width[3] + $t_width[4] + $t_width[5] + $t_width[6];

    $fy=$y-$margin-$pdf->GetFontHeight($font_size);    
    text_align_right($x-$margin,$fy,$font_size,"Razem:");

    $t_data[7] = iconv("UTF-8","ISO-8859-2",moneyf($invoice['totalbase']));
    $t_data[8] = "x";
    $t_data[9] = iconv("UTF-8","ISO-8859-2",moneyf($invoice['totaltax']));
    $t_data[10] = iconv("UTF-8","ISO-8859-2",moneyf($invoice['total']));

    $y = invoice_short_data_row($x,$y,$width,$font_size,$margin,$t_data,$t_width);
    
    $y = $y - 5;

    $fy=$y-$margin-$pdf->GetFontHeight($font_size);    
    text_align_right($x-$margin,$fy,$font_size,"W tym:");
    $pdf->line($x,$y,$x+$t_width[7]+$t_width[8]+$t_width[9]+$t_width[10]+8*$margin,$y);
    
    foreach ($invoice['taxest'] as $item) {
	$t_data[7] = iconv("UTF-8","ISO-8859-2",moneyf($item['base']));
	$t_data[8] = $item['taxvalue']." %";
	$t_data[9] = iconv("UTF-8","ISO-8859-2",moneyf($item['tax']));
	$t_data[10] = iconv("UTF-8","ISO-8859-2",moneyf($item['total']));
	$y = invoice_short_data_row($x,$y,$width,$font_size,$margin,$t_data,$t_width);
    }
    $return[2] = $y;
    return $return;
}

function invoice_to_pay($x,$y) {
    global $pdf, $invoice;
    $y = $y - text_align_left($x,$y,14,"Do zap³aty: ".iconv("UTF-8","ISO-8859-2",moneyf($invoice['total'])));
    $y = $y - text_align_left($x,$y,10,"S³ownie: ".to_words(floor($invoice['total']))." z³ ".to_words(round(($invoice['total']-floor($invoice['total']))*100))." gr");
    return $y;
}

function invoice_expositor ($x,$y) {
    global $pdf, $_CONFIG;
    $y = $y - text_align_left($x,$y,10,"Wystawi³: ".iconv("UTF-8","ISO-8859-2",$_CONFIG['invoices']['default_author']));
    return $y;
}

function invoice_body() {
    global $invoice,$pdf,$id,$_CONFIG;
    switch ($_CONFIG['invoices']['template_file']) {
	case "standard":
	    $top=800;
	    invoice_dates(500,800);    
    	    invoice_address_box(400,700);
	    $top=invoice_title(30,$top);
	    $top=$top-20;
    	    $top=invoice_seller(30,$top);
	    $top=$top-20;
    	    $top=invoice_buyer(30,$top);
	    $top=$top-20;
    	    $return=invoice_data(30,$top,530);
	    invoice_expositor(30,$return[1]-20);
    	    $top=$return[2]-20;
	    invoice_to_pay(30,$top);
	    break;
	case "FT-0100":
	    $top=800;
	    invoice_dates(500,800);    
    	    invoice_address_box(400,700);
	    $top=invoice_title(30,$top);
	    $top=$top-10;
    	    $top=invoice_seller(30,$top);
	    $top=$top-10;
    	    $top=invoice_buyer(30,$top);
	    $top=$top-10;
    	    $return=invoice_data(30,$top,530);
	    invoice_expositor(30,$return[1]-20);
    	    $top=$return[2]-10;
	    invoice_to_pay(30,$top);
	    invoice_main_form_fill(187,3,0.4);
	    invoice_simple_form_fill(14,3,0.4);
	    break;
	default:
	    require($_CONFIG['invoices']['template_file']);
    }
    if (!($invoice['last'])) $id=$pdf->newPage(1,$id,'after'); 
}

// brzydki hack dla ezpdf 
setlocale(LC_ALL,'C');
require_once($_LIB_DIR.'/ezpdf/class.ezpdf.php');

$diff=array(177=>'aogonek',161=>'Aogonek',230=>'cacute',198=>'Cacute',234=>'eogonek',202=>'Eogonek',
241=>'nacute',209=>'Nacute',179=>'lslash',163=>'Lslash',182=>'sacute',166=>'Sacute',
188=>'zacute',172=>'Zacute',191=>'zdot',175=>'Zdot');
//$pdf =& new Cezpdf('A4','landscape');
$pdf =& new Cezpdf('A4','portrait');
$pdf->addInfo('Producer','LMS Developers');
$pdf->addInfo('Title','Druki przelewu/wplaty');
$pdf->addInfo('Creator','LMS '.$layout['lmsv']);
$pdf->setPreferences('FitWindow','1');
$pdf->ezSetMargins(0,0,0,0);
$pdf->selectFont($_LIB_DIR.'/ezpdf/arial.afm',array('encoding'=>'WinAnsiEncoding','differences'=>$diff));
//$pdf->setLineStyle(2);
$id=$pdf->getFirstPageId();

if($_GET['print'] == 'cached' && sizeof($_POST['marks']))
{
	$layout['pagetitle'] = trans('Invoices');
	foreach($_POST['marks'] as $markid => $junk)
		if($junk)
			$ids[] = $markid;
	sort($ids);
	$which = ($_GET['which'] != '' ? $_GET['which'] : trans('ORIGINAL+COPY'));
	foreach($ids as $idx => $invoiceid)
	{
		$invoice = $LMS->GetInvoiceContent($invoiceid);
		$invoice['serviceaddr'] = $LMS->GetUserServiceAddress($invoice['customerid']);
		foreach(split('\+', $which) as $type)
		{
			invoice_body();
		}
	}
}
elseif($_GET['fetchallinvoices'])
{
	$layout['pagetitle'] = trans('Invoices');
	$which = ($_GET['which'] != '' ? $_GET['which'] : trans('ORIGINAL+COPY'));
	
	$ids = $LMS->DB->GetCol('SELECT id FROM invoices 
				WHERE cdate > ? AND cdate < ?'
				.($_GET['userid'] ? ' AND customerid = '.$_GET['userid'] : '')
				.' ORDER BY cdate',
				array($_GET['from'], $_GET['to']));
	if(!$ids) die;

	foreach($ids as $idx => $invoiceid)
	{
		echo '<PRE>';
		$invoice = $LMS->GetInvoiceContent($invoiceid);
		$invoice['serviceaddr'] = $LMS->GetUserServiceAddress($invoice['customerid']);
		foreach(split('\+', $which) as $type)
		{
			invoice_body();
		}
	}
}
elseif($_GET['fetchsingle'])
{
	$invoice = $LMS->GetInvoiceContent($_GET['id']);
	$ntempl = $LMS->CONFIG['invoices']['number_template'];
	$ntempl = str_replace('%N',$invoice['number'],$ntempl);
	$ntempl = str_replace('%M',$invoice['month'],$ntempl);
	$ntempl = str_replace('%Y',$invoice['year'],$ntempl);
	$layout['pagetitle'] = trans('Invoice No. $0', $ntempl);
	$invoice['last'] = TRUE;
	$invoice['serviceaddr'] = $LMS->GetUserServiceAddress($invoice['customerid']);
	invoice_body();

}
elseif($invoice = $LMS->GetInvoiceContent($_GET['id']))
{
	$ntempl = $LMS->CONFIG['invoices']['number_template'];
	$ntempl = str_replace('%N',$invoice['number'],$ntempl);
	$ntempl = str_replace('%M',$invoice['month'],$ntempl);
	$ntempl = str_replace('%Y',$invoice['year'],$ntempl);
	$layout['pagetitle'] = trans('Invoice No. $0', $ntempl);
	$invoice['serviceaddr'] = $LMS->GetUserServiceAddress($invoice['customerid']);
	$type = trans('ORIGINAL');
	invoice_body();
	$type = trans('COPY');
	$invoice['last'] = TRUE;
	invoice_body();
}
else
{
	header('Location: ?m=invoicelist');
	die;
}

global $pdf;

$pdf->ezStream();

?>

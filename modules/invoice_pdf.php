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
    
    $pdf->addtext(15*$scale+$x,560*$scale+$y,30*$scale, iconv("UTF-8","ISO-8859-2",$finances['shortname']));
    $pdf->addtext(15*$scale+$x,525*$scale+$y,30*$scale, iconv("UTF-8","ISO-8859-2",$finances['address']));
    $pdf->addtext(15*$scale+$x,490*$scale+$y,30*$scale, iconv("UTF-8","ISO-8859-2",$finances['zip']." ".$finances['city']));
    $tmp = iconv("UTF-8","ISO-8859-2",$finances['account']);
    $pdf->addtext(15*$scale+$x,680*$scale+$y,30*$scale, substr($tmp,0,17));
    $pdf->addtext(15*$scale+$x,620*$scale+$y,30*$scale, substr($tmp,18,200));
    $pdf->addtext(15*$scale+$x,435*$scale+$y,30*$scale,"**".number_format($invoice['total'],2,',','')."**");

    $font_size=30;
    while ($pdf->getTextWidth($font_size*$scale,$invoice['name'])>135)
        $font_size=$font_size-1;
    $pdf->addtext(15*$scale+$x,380*$scale+$y,$font_size*$scale,$invoice['name']);
    $font_size=30;
    while ($pdf->getTextWidth($font_size*$scale,$invoice['address'])>135)
        $font_size=$font_size-1;
    $pdf->addtext(15*$scale+$x,345*$scale+$y,$font_size*$scale,$invoice['address']);
    $pdf->addtext(15*$scale+$x,310*$scale+$y,30*$scale,$invoice['zip']." ".$invoice['city']);

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

function invoice_data_row($x,$y,$width,$font_size,$margin,$data) {
    global $pdf;
    $fy=$y-$margin-$pdf->GetFontHeight($font_size);    
    $left = $x+$margin;
    $ny = $fy;
    for ($i = 1; $i <= 10; $i++) {
	$ly = text_wrap($left+$margin, $fy, $data[$i]['width'], $font_size,$data[$i]['title']);
	$left = $left + $data[$i]['width']+2*$margin;
	if ($ly<$ny) $ny=$ly;
    }
    $left = $x;
    for ($i = 1; $i <= 10; $i++) {
	$pdf->line($left, $y, $left, $ny);
	$left = $left + $data[$i]['width']+2*$margin;
    }
    $pdf->line($left, $y, $left, $ny);
    $y=$ny;
    $pdf->line($x,$y,$x+$width,$y);
    return($y);
}

function invoice_data($x,$y,$width) {
    global $invoice,$pdf;
    $font_size=7;
    $margin = 2;
    $pdf->setlinestyle(1);
    $pdf->line($x,$y,$x+$width,$y);
    $header[1]['title'] = "Lp.";
    $header[2]['title'] = "Nazwa wyrobu, towaru lub us³ugi:";
    $header[3]['title'] = "PKWiU:";
    $header[4]['title'] = "JM:";
    $header[5]['title'] = "Ilo¶æ:";
    $header[6]['title'] = "Cena jedn. netto:";
    $header[7]['title'] = "Warto¶æ netto:";
    $header[8]['title'] = "St. PTU:";
    $header[9]['title'] = "Kwota PTU:";
    $header[10]['title'] = "Warto¶æ brutto:";
    for ($i = 1; $i <= 10; $i++) $header[$i]['width']=$pdf->getTextWidth($font_size,$header[$i]['title'])+2*$margin;
    // tutaj jeszcze trzeba bêdzie sprawdziæ jak± szeroko¶æ maj± pola w tabelce pu¼niej
    
    // Kolumna 2 bêdzie mia³a rozmiar ustalany dynamicznie
    $header[2]['width'] = $width-($header[1]['width']+$header[3]['width']+$header[4]['width']+$header[5]['width']+$header[6]['width']+$header[7]['width']+$header[8]['width']+$header[9]['width']+$header[10]['width']+20*$margin);

    $y = invoice_data_row($x,$y,$width,$font_size,$margin,$header);
    $lp = 1;
    foreach ($invoice['content'] as $item) {
	$header[1]['title'] = $lp;
	$header[2]['title'] = $item['description'];
	$header[3]['title'] = $item['pkwiu'];
	$header[4]['title'] = $item['content'];
	$header[5]['title'] = $item['count'];
	$header[6]['title'] = iconv("UTF-8","ISO-8859-2",moneyf($item['basevalue']));
	$header[7]['title'] = iconv("UTF-8","ISO-8859-2",moneyf($item['totalbase']));
	$header[8]['title'] = $item['taxvalue']." %";
	$header[9]['title'] = iconv("UTF-8","ISO-8859-2",moneyf($item['totaltax']));
	$header[10]['title'] = iconv("UTF-8","ISO-8859-2",moneyf($item['total']));
	
	$lp++;
	$y = invoice_data_row($x,$y,$width,$font_size,$margin,$header);
    }
    $x = $x + 12*$margin + $header[1]['width'] + $header[2]['width'] + $header[3]['width'] + $header[4]['width'] + $header[5]['width'] + $header[6]['width'];
    $fy=$y-$margin-$pdf->GetFontHeight($font_size);    
    text_align_right($x-$margin,$fy,$font_size,"Razem:");

    $left = $x+$margin;
    $ny = $fy;
    $ly = text_wrap($left+$margin, $fy, $header[7]['width'], $font_size,iconv("UTF-8","ISO-8859-2",moneyf($invoice['totalbase'])));
    $left = $left + $header[7]['width']+2*$margin;
    if ($ly<$ny) $ny=$ly;
    $ly = text_wrap($left+$margin, $fy, $header[8]['width'], $font_size,"X");
    $left = $left + $header[8]['width']+2*$margin;
    if ($ly<$ny) $ny=$ly;
    $ly = text_wrap($left+$margin, $fy, $header[9]['width'], $font_size,iconv("UTF-8","ISO-8859-2",moneyf($invoice['totaltax'])));
    $left = $left + $header[9]['width']+2*$margin;
    if ($ly<$ny) $ny=$ly;
    $ly = text_wrap($left+$margin, $fy, $header[9]['width'], $font_size,iconv("UTF-8","ISO-8859-2",moneyf($invoice['total'])));
    $left = $left + $header[9]['width']+2*$margin;
    if ($ly<$ny) $ny=$ly;
    
    $left = $x;
    for ($i = 7; $i <= 10; $i++) {
	$pdf->line($left, $y, $left, $ny);
	$left = $left + $header[$i]['width']+2*$margin;
    }
    $pdf->line($left, $y, $left, $ny);
    $y=$ny;
    $pdf->line($x,$y,$x+$header[7]['width']+$header[8]['width']+$header[9]['width']+$header[10]['width']+8*$margin,$y);
    
    
    //$pdf->line($x,$y,$x,$y-20);
}

function invoice_body() {
    global $pdf;
    $pdf->addJpegFromFile('/var/www/devel/lms/lib/ezpdf/formularz.jpg',0,0,595);
    global $invoice,$pdf,$id;
    $top=800;
    invoice_dates(500,800);    
    invoice_address_box(400,700);
    $top=invoice_title(30,$top);
    $top=$top-20;
    $top=invoice_seller(30,$top);
    $top=$top-20;
    $top=invoice_buyer(30,$top);
    $top=$top-20;
    $top=invoice_data(30,$top,440);
    
    invoice_main_form_fill(187,3,0.4);
    invoice_simple_form_fill(14,3,0.4);

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
$pdf->setLineStyle(2);
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

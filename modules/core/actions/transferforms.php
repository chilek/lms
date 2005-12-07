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


function main_fill($x,$y,$scale)
{
    global $pdf,$_NAME,$_ADDRESS,$_ZIP,$_CITY,$control_lines,$invoice;
    if ($control_lines) {
	$pdf->line(7*$scale+$x,115*$scale+$y,7*$scale+$x,145*$scale+$y);
        $pdf->line(7*$scale+$x,115*$scale+$y,37*$scale+$x,115*$scale+$y);
	$pdf->line(978*$scale+$x,115*$scale+$y,978*$scale+$x,145*$scale+$y);
        $pdf->line(978*$scale+$x,115*$scale+$y,948*$scale+$x,115*$scale+$y);
	$pdf->line(7*$scale+$x,726*$scale+$y,7*$scale+$x,696*$scale+$y);
        $pdf->line(7*$scale+$x,726*$scale+$y,37*$scale+$x,726*$scale+$y);
	$pdf->line(978*$scale+$x,726*$scale+$y,978*$scale+$x,696*$scale+$y);
        $pdf->line(978*$scale+$x,726*$scale+$y,948*$scale+$x,726*$scale+$y);
    }
    $pdf->addtext(15*$scale+$x,680*$scale+$y,30*$scale, iconv('UTF-8', 'ISO-8859-2', $_NAME));
    $pdf->addtext(15*$scale+$x,617*$scale+$y,30*$scale, iconv('UTF-8', 'ISO-8859-2', $_ADDRESS." ".$_ZIP." ".$_CITY));
    $pdf->addtext(15*$scale+$x,555*$scale+$y,30*$scale, bankaccount($invoice['customerid']));
    $pdf->addtext(550*$scale+$x,497*$scale+$y,30*$scale,number_format($invoice['total'],2,',',''));
    $pdf->addtext(15*$scale+$x,375*$scale+$y,30*$scale, iconv('UTF-8', 'ISO-8859-2',$invoice['name']));
    $pdf->addtext(15*$scale+$x,315*$scale+$y,30*$scale, iconv('UTF-8', 'ISO-8859-2',$invoice['address']."; ".$invoice['zip']." ".$invoice['city']));
    $pdf->addtext(15*$scale+$x,250*$scale+$y,30*$scale, iconv('UTF-8', 'ISO-8859-2',trans('Payment for invoice No. $0', $invoice['t_number'])));
}

function simple_fill_mip($x,$y,$scale)
{
    global $pdf,$_NAME,$_ADDRESS,$_ZIP,$_CITY,$_SHORT_NAME,$control_lines,$invoice;

    if ($control_lines) {
        $pdf->line(7*$scale+$x,180*$scale+$y,7*$scale+$x,210*$scale+$y);
	$pdf->line(7*$scale+$x,180*$scale+$y,37*$scale+$x,180*$scale+$y);
        $pdf->line(370*$scale+$x,180*$scale+$y,370*$scale+$x,210*$scale+$y);
	$pdf->line(370*$scale+$x,180*$scale+$y,340*$scale+$x,180*$scale+$y);
        $pdf->line(7*$scale+$x,726*$scale+$y,7*$scale+$x,696*$scale+$y);
	$pdf->line(7*$scale+$x,726*$scale+$y,37*$scale+$x,726*$scale+$y);
        $pdf->line(370*$scale+$x,726*$scale+$y,370*$scale+$x,696*$scale+$y);
	$pdf->line(370*$scale+$x,726*$scale+$y,340*$scale+$x,726*$scale+$y);
    }
    $pdf->addtext(15*$scale+$x,560*$scale+$y,30*$scale, iconv('UTF-8', 'ISO-8859-2',$_SHORT_NAME));
    $pdf->addtext(15*$scale+$x,525*$scale+$y,30*$scale, iconv('UTF-8', 'ISO-8859-2',$_ADDRESS));
    $pdf->addtext(15*$scale+$x,490*$scale+$y,30*$scale, iconv('UTF-8', 'ISO-8859-2',$_ZIP." ".$_CITY));
    $pdf->addtext(15*$scale+$x,680*$scale+$y,30*$scale, substr(bankaccount($invoice['customerid']),0,17));
    $pdf->addtext(15*$scale+$x,620*$scale+$y,30*$scale, substr(bankaccount($invoice['customerid']),18,200));
    $pdf->addtext(15*$scale+$x,435*$scale+$y,30*$scale,'**'.number_format($invoice['total'],2,',','').'**');
    //$pdf->addtext(15*$scale+$x,310*$scale+$y,30*$scale,$invoice['name']);

    $font_size=30;
    while ($pdf->getTextWidth($font_size*$scale, iconv('UTF-8', 'ISO-8859-2',$invoice['name']))>135)
	$font_size=$font_size-1;    
    $pdf->addtext(15*$scale+$x,310*$scale+$y,$font_size*$scale, iconv('UTF-8', 'ISO-8859-2',$invoice['name']));
    $font_size=30;
    while ($pdf->getTextWidth($font_size*$scale, iconv('UTF-8', 'ISO-8859-2',$invoice['address']))>135)
	$font_size=$font_size-1;    
    $pdf->addtext(15*$scale+$x,275*$scale+$y,$font_size*$scale, iconv('UTF-8', 'ISO-8859-2',$invoice['address']));
    $pdf->addtext(15*$scale+$x,240*$scale+$y,30*$scale, iconv('UTF-8', 'ISO-8859-2',$invoice['zip']." ".$invoice['city']));

    $font_size=30;
    while ($pdf->getTextWidth($font_size*$scale,trans('Invoice No. $0', $invoice['t_number']))>135)
	$font_size=$font_size-1;    
    $pdf->addtext(15*$scale+$x,385*$scale+$y,$font_size*$scale,trans('Invoice No. $0', $invoice['t_number']));

}

function address_box($x,$y,$scale)
{
    global $pdf,$_NAME,$_ADDRESS,$_ZIP,$_CITY,$_SERVICE,$_SHORT_NAME,$invoice;

    $font_size=30;
    while ($pdf->getTextWidth($font_size*$scale, iconv('UTF-8', 'ISO-8859-2',$invoice['name']))>240)
	$font_size=$font_size-1;    
    $pdf->addtext(5*$scale+$x,310*$scale+$y,$font_size*$scale, iconv('UTF-8', 'ISO-8859-2',$invoice['name']));
    $pdf->addtext(5*$scale+$x,275*$scale+$y,30*$scale, iconv('UTF-8', 'ISO-8859-2',$invoice['address']));
    $pdf->addtext(5*$scale+$x,240*$scale+$y,30*$scale, iconv('UTF-8', 'ISO-8859-2',$invoice['zip']." ".$invoice['city']));
}

require_once($_LIB_DIR.'/ezpdf/class.ezpdf.php');

$diff=array(177=>'aogonek',161=>'Aogonek',230=>'cacute',198=>'Cacute',234=>'eogonek',202=>'Eogonek',
241=>'nacute',209=>'Nacute',179=>'lslash',163=>'Lslash',182=>'sacute',166=>'Sacute',
188=>'zacute',172=>'Zacute',191=>'zdot',175=>'Zdot'); 
//$pdf =& new Cezpdf('A4','landscape');
$pdf =& new Cezpdf('A4','portrait');
$pdf->addInfo('Producer','LMS Developers');
$pdf->addInfo('Title',trans('Form of Cash Transfer'));
$pdf->addInfo('Creator','LMS '.$layout['lmsv']);
$pdf->setPreferences('FitWindow','1');
$pdf->ezSetMargins(0,0,0,0);
$pdf->selectFont($_LIB_DIR.'/ezpdf/arial.afm',array('encoding'=>'WinAnsiEncoding','differences'=>$diff)); 
$pdf->setLineStyle(2);
$id=$pdf->getFirstPageId();

$_NAME = (! $CONFIG['finances']['name'] ? trans('Not set') : $CONFIG['finances']['name']);
$_SHORT_NAME = (! $CONFIG['finances']['shortname'] ? trans('Not set') : $CONFIG['finances']['shortname']);
$_ADDRESS = (! $CONFIG['finances']['address'] ? trans('Not set') : $CONFIG['finances']['address']);
$_ZIP = (! $CONFIG['finances']['zip'] ? trans('Not set') : $CONFIG['finances']['zip']);
$_CITY = (! $CONFIG['finances']['city'] ? trans('Not set') : $CONFIG['finances']['city']);

$control_lines = 0;

$ids = $DB->GetCol('SELECT id FROM documents
        WHERE cdate > ? AND cdate < ? AND type = 1'
        .($_GET['customerid'] ? ' AND customerid = '.$_GET['customerid'] : '')
        .' ORDER BY customerid',
        array($_GET['from'], $_GET['to']));

if(!$ids)
{
    $SESSION->close();
    die;
}

@setlocale('LC_NUMERIC','C');																						

$count = (strstr($which, '+') ? sizeof($ids)*2 : sizeof($ids));
$i=0;

foreach($ids as $idx => $invoiceid)
{
    $invoice = $LMS->GetInvoiceContent($invoiceid);
    $invoice['t_number'] = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);
    main_fill(177,12,0.395);
    main_fill(177,313,0.396);
    simple_fill_mip(5,12,0.395);
    simple_fill_mip(5,313,0.395);
    address_box(390,600,0.395);
    $i++;
    if($i < $count) $id = $pdf->newPage(1,$id,'after');
}

$pdf->ezStream();

?>

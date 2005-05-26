<?php
							    
/*
 * LMS version 1.7-cvs
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

function main_fill($x,$y,$scale)	{
    global $pdf,$_NAME,$_ADDRESS,$_ZIP,$_CITY,$_ACCOUNT,$customerinfo,$_SERVICE,$control_lines;
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
    $pdf->addtext(15*$scale+$x,680*$scale+$y,30*$scale,$_NAME);
    $pdf->addtext(15*$scale+$x,617*$scale+$y,30*$scale,$_ADDRESS." ".$_ZIP." ".$_CITY);
    $pdf->addtext(15*$scale+$x,555*$scale+$y,30*$scale,$_ACCOUNT);
    $pdf->addtext(550*$scale+$x,497*$scale+$y,30*$scale,number_format(-$customerinfo['balance'],2,',',''));
    $pdf->addtext(15*$scale+$x,375*$scale+$y,30*$scale,iconv('UTF-8','ISO-8859-2',$customerinfo['customername']));
    $pdf->addtext(15*$scale+$x,315*$scale+$y,30*$scale,iconv('UTF-8','ISO-8859-2',$customerinfo['address']." ".$customerinfo['zip']." ".$customerinfo['city']));
    $pdf->addtext(15*$scale+$x,250*$scale+$y,30*$scale,$_SERVICE." mc. ".iconv('UTF-8','ISO-8859-2',strftime("%B"))." ID:".sprintf("%04d",$customerinfo['id']));
}

function simple_fill_mip($x,$y,$scale)	{
    global $pdf,$_NAME,$_ADDRESS,$_ZIP,$_CITY,$_ACCOUNT,$customerinfo,$_SERVICE,$_SHORT_NAME,$control_lines;

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
    $pdf->addtext(15*$scale+$x,560*$scale+$y,30*$scale,$_SHORT_NAME);
    $pdf->addtext(15*$scale+$x,525*$scale+$y,30*$scale, $_ADDRESS);
    $pdf->addtext(15*$scale+$x,490*$scale+$y,30*$scale, $_ZIP." ".$_CITY);
    $pdf->addtext(15*$scale+$x,680*$scale+$y,30*$scale, substr($_ACCOUNT,0,17));
    $pdf->addtext(15*$scale+$x,620*$scale+$y,30*$scale, substr($_ACCOUNT,18,200));
    $pdf->addtext(15*$scale+$x,435*$scale+$y,30*$scale,"**".number_format(-$customerinfo['balance'],2,',','')."**");
    //$pdf->addtext(15*$scale+$x,310*$scale+$y,30*$scale,iconv('UTF-8','ISO-8859-2',$customerinfo['customername']));

    $font_size=30;
    while ($pdf->getTextWidth($font_size*$scale,iconv('UTF-8','ISO-8859-2',$customerinfo['customername']))>135)
	$font_size=$font_size-1;    
    $pdf->addtext(15*$scale+$x,310*$scale+$y,$font_size*$scale,iconv('UTF-8','ISO-8859-2',$customerinfo['customername']));
    $pdf->addtext(15*$scale+$x,275*$scale+$y,30*$scale,iconv('UTF-8','ISO-8859-2',$customerinfo['address']));
    $pdf->addtext(15*$scale+$x,240*$scale+$y,30*$scale,iconv('UTF-8','ISO-8859-2',$customerinfo['zip']." ".$customerinfo['city']));

    $font_size=30;
    while ($pdf->getTextWidth($font_size*$scale,$_SERVICE." mc. ".iconv('UTF-8','ISO-8859-2',strftime("%B")))>135)
	$font_size=$font_size-1;    
    $pdf->addtext(15*$scale+$x,385*$scale+$y,$font_size*$scale,$_SERVICE." mc. ".iconv('UTF-8','ISO-8859-2',strftime("%B")));

}

function address_box($x,$y,$scale)	{
    global $pdf,$_NAME,$_ADDRESS,$_ZIP,$_CITY,$_ACCOUNT,$customerinfo,$_SERVICE,$_SHORT_NAME;

    $font_size=30;
    while ($pdf->getTextWidth($font_size*$scale,iconv('UTF-8','ISO-8859-2',$customerinfo['customername']))>240)
	$font_size=$font_size-1;    
    $pdf->addtext(15*$scale+$x,310*$scale+$y,$font_size*$scale,iconv('UTF-8','ISO-8859-2',$customerinfo['customername']));
    $pdf->addtext(15*$scale+$x,275*$scale+$y,30*$scale,iconv('UTF-8','ISO-8859-2',$customerinfo['address']));
    $pdf->addtext(15*$scale+$x,240*$scale+$y,30*$scale,iconv('UTF-8','ISO-8859-2',$customerinfo['zip']." ".$customerinfo['city']));
}

// Dobra, czytamy z lms.ini
$_NAME = (! $_CONFIG[finances]['name'] ? "Nazwa nieustawiona" : $_CONFIG[finances]['name']);
$_SHORT_NAME = (! $_CONFIG[finances]['shortname'] ? "Nazwa nieustawiona" : $_CONFIG[finances]['shortname']);
$_ADDRESS = (! $_CONFIG[finances]['address'] ? "Adres nieustawiony" : $_CONFIG[finances]['address']);
$_ZIP = (! $_CONFIG[finances]['zip'] ? "00-000" : $_CONFIG[finances]['zip']);
$_CITY = (! $_CONFIG[finances]['city'] ? "Miasto nieustawione" : $_CONFIG[finances]['city']);
$_SERVICE = (! $_CONFIG[finances]['service'] ? "Nazwa uslugi nieustawiona" : $_CONFIG[finances]['service']);
$_ACCOUNT = (! $_CONFIG[finances]['account'] ? "123456789012345678901234567" : $_CONFIG[finances]['account']);

$control_lines = 0;

$customerlist = $LMS->GetCustomerList($o, 6, $n, $g);
$total = $customerlist['total'];
unset($customerlist['total']);
unset($customerlist['state']);
unset($customerlist['network']);
unset($customerlist['customergroup']);
unset($customerlist['order']);
unset($customerlist['below']);
unset($customerlist['over']);
unset($customerlist['direction']);

$i=0;
if($total) foreach ($customerlist as $customer) 
{
    $i++;
    $customerinfo = $LMS->GetCustomer($customer['id']);
    main_fill(177,12,0.395);
    main_fill(177,313,0.396);
    simple_fill_mip(5,12,0.395);
    simple_fill_mip(5,313,0.395);
    address_box(40,600,0.395);

    if($i != $total)
	    $id = $pdf->newPage(1,$id,'after');
}
$pdf->ezStream();

?>

<?php

/*
 * LMS Userpanel
 *
 *  (C) Copyright 2004 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is licensed under LMS License. Please, see
 *  doc/LICENSE.pl file for information about copyright notice.
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
    global $pdf,$_NAME,$_ADDRESS,$_ZIP,$_CITY,$_ACCOUNT,$userinfo,$_SERVICE,$control_lines;
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
    $pdf->addtext(15*$scale+$x,617*$scale+$y,30*$scale, $_ADDRESS." ".$_ZIP." ".$_CITY);
    $pdf->addtext(15*$scale+$x,555*$scale+$y,30*$scale, $_ACCOUNT);
    $pdf->addtext(550*$scale+$x,497*$scale+$y,30*$scale,number_format(-$userinfo['balance'],2,',',''));
    $pdf->addtext(15*$scale+$x,375*$scale+$y,30*$scale,$userinfo['username']);
    $pdf->addtext(15*$scale+$x,315*$scale+$y,30*$scale,$userinfo['address']." ".$userinfo['zip']." ".$userinfo['city']);
    $pdf->addtext(15*$scale+$x,250*$scale+$y,30*$scale,$_SERVICE." mc. ".strftime("%B")." ID:".sprintf("%04d",$userinfo['id']));
}

function simple_fill_mip($x,$y,$scale)	{
    global $pdf,$_NAME,$_ADDRESS,$_ZIP,$_CITY,$_ACCOUNT,$userinfo,$_SERVICE,$_SHORT_NAME,$control_lines;

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
    $pdf->addtext(15*$scale+$x,435*$scale+$y,30*$scale,"**".number_format(-$userinfo['balance'],2,',','')."**");
    //$pdf->addtext(15*$scale+$x,310*$scale+$y,30*$scale,$userinfo['username']);

    $font_size=30;
    while ($pdf->getTextWidth($font_size*$scale,$userinfo['username'])>135)
	$font_size=$font_size-1;    
    $pdf->addtext(15*$scale+$x,310*$scale+$y,$font_size*$scale,$userinfo['username']);
    $pdf->addtext(15*$scale+$x,275*$scale+$y,30*$scale,$userinfo['address']);
    $pdf->addtext(15*$scale+$x,240*$scale+$y,30*$scale,$userinfo['zip']." ".$userinfo['city']);

    $font_size=30;
    while ($pdf->getTextWidth($font_size*$scale,$_SERVICE." mc. ".strftime("%B"))>135)
	$font_size=$font_size-1;    
    $pdf->addtext(15*$scale+$x,385*$scale+$y,$font_size*$scale,$_SERVICE." mc. ".strftime("%B"));

}

function address_box($x,$y,$scale)	{
    global $pdf,$_NAME,$_ADDRESS,$_ZIP,$_CITY,$_ACCOUNT,$userinfo,$_SERVICE,$_SHORT_NAME;

    $font_size=30;
    while ($pdf->getTextWidth($font_size*$scale,$userinfo['username'])>240)
	$font_size=$font_size-1;    
    $pdf->addtext(15*$scale+$x,310*$scale+$y,$font_size*$scale,$userinfo['username']);
    $pdf->addtext(15*$scale+$x,275*$scale+$y,30*$scale,$userinfo['address']);
    $pdf->addtext(15*$scale+$x,240*$scale+$y,30*$scale,$userinfo['zip']." ".$userinfo['city']);
}

// Dobra, czytamy z lms.ini
$_NAME = (! $_CONFIG[finances]['name'] ? "Nazwa nieustawiona" : $_CONFIG[finances]['name']);
$_SHORT_NAME = (! $_CONFIG[finances]['shortname'] ? "Nazwa nieustawiona" : $_CONFIG[finances]['shortname']);
$_ADDRESS = (! $_CONFIG[finances]['address'] ? "Adres nieustawiony" : $_CONFIG[finances]['address']);
$_ZIP = (! $_CONFIG[finances]['zip'] ? "00-000" : $_CONFIG[finances]['zip']);
$_CITY = (! $_CONFIG[finances]['city'] ? "Miasto nieustawione" : $_CONFIG[finances]['city']);
$_SERVICE = (! $_CONFIG[finances]['service'] ? "Nazwa us³ugi nieustawiona" : $_CONFIG[finances]['service']);
$_ACCOUNT = (! $_CONFIG[finances]['account'] ? "123456789012345678901234567" : $_CONFIG[finances]['account']);

$control_lines = 0;

$userlist=$LMS->GetUserList($o, $s, $n, $g);
foreach ($userlist as $user) {
    $userinfo=$LMS->GetUser($user['id']);
    if (($user['id']>0) && ($userinfo['balance']<0)) {
        main_fill(177,12,0.395);
	main_fill(177,313,0.396);
        simple_fill_mip(5,12,0.395);
	simple_fill_mip(5,313,0.395);
        address_box(40,600,0.395);

	$id=$pdf->newPage(1,$id,'after');
    }
}
$pdf->ezStream();

?>
							    

<?php

/*
 * LMS version 1.9-cvs
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

// Przygotowane dla druków firmy Michalczyk i Prokop Sp. z o.o.
// dla drukarki HP LJ 1010 zostawiæ leftmargin = 0, bottommargin = 0

function truncate($str, $max=60)
{
	$len = strlen($str);
	if(!$max || $max >= $len)
		return $str;
		
	// musimy pokombinowac bo nie mamy czcionki o stalym rozmiarze,
	// ten sposob i tak jest do kitu, ale dziala lepiej niz staly limit
	for($i=0; $i<$len; $i++)
	{
		if(ctype_upper($str[$i]))
			$l += 1.4;
		else
			$l += 1;
	}
	$max = $max * ($len/$l);

	return substr($str, 0, $max);
}

function main_form($x, $y, $data)
{
    global $pdf;
    global $_NAME, $_ADDRESS, $_ZIP, $_CITY, $_ACCOUNT, $_TITLE, $_LMARGIN, $_BMARGIN;
    
    $balance = $data['balance'] < 0 ? -$data['balance'] : $data['balance'];

    $font_size = 14;
    $lineh = 25;
    $x += $_LMARGIN;
    $y += $_BMARGIN;

    $y += 275;
    $pdf->addtext($x,$y,$font_size,iconv('UTF-8', 'ISO-8859-2',$_NAME));
    $y -= $lineh;
    $pdf->addtext($x,$y,$font_size,iconv('UTF-8', 'ISO-8859-2',trim($_ZIP.' '.$_CITY.' '.$_ADDRESS)));
    $y -= $lineh;
//    for($i=0; $i<26; $i++)
//    {
//	    $pdf->addtext($x+$i*14.6,$y,$font_size,$_ACCOUNT[$i]);
//    }
    $pdf->addtext($x,$y,$font_size,$_ACCOUNT);
    $y -= $lineh;
    $pdf->addtext($x+220,$y,$font_size,sprintf('%.2f',$balance));
    $y -= $lineh;
    $pdf->addtext($x,$y,$font_size,iconv('UTF-8', 'ISO-8859-2',trans('$0 dollars $1 cents',to_words(floor($balance)),to_words(round(($balance-floor($balance))*100)))));
    $y -= $lineh;
    $pdf->addtext($x,$y,$font_size,truncate(iconv('UTF-8', 'ISO-8859-2',$data['customername'])));
    $y -= $lineh;
    $pdf->addtext($x,$y,$font_size,truncate(iconv('UTF-8', 'ISO-8859-2',trim($data['zip'].' '.$data['city'].' '.$data['address']))));
    $y -= $lineh;
    $pdf->addtext($x,$y,$font_size,iconv('UTF-8', 'ISO-8859-2',$_TITLE));
    $y -= $lineh;
    $pdf->addtext($x,$y,$font_size,iconv('UTF-8', 'ISO-8859-2',trans('Customer ID: $0',sprintf('%04d',$data['id']))));
}

$balance = $_POST['balance'] ? $_POST['balance'] : 0;
$customer = $_POST['customer'] ? $_POST['customer'] : 0;
$group = $_POST['customergroup'] ? $_POST['customergroup'] : 0;

$list = $DB->GetAll('SELECT customers.id AS id, address, zip, city, '
	.$DB->Concat('UPPER(lastname)',"' '",'customers.name').' AS customername,   
	COALESCE(SUM(value), 0.00) AS balance
	FROM customers LEFT JOIN cash ON (customers.id=cash.customerid) '
	.($group ? 'LEFT JOIN customerassignments ON (customers.id=customerassignments.customerid)' : '')
	.'WHERE deleted = 0'
	.($customer ? ' AND customers.id='.$customer : '')
	.($group ? ' AND customergroupid='.$group : '')
	.' GROUP BY customers.id, lastname, customers.name, address, zip, city
	HAVING COALESCE(SUM(value), 0.00) < ? ORDER BY customers.id',
	array($balance));

if(!$list)
{
    $SESSION->close();
    die;
}

$_NAME = (! $CONFIG['finances']['name'] ? trans("Not set") : $CONFIG['finances']['name']);
$_ADDRESS = (! $CONFIG['finances']['address'] ? trans("Not set") : $CONFIG['finances']['address']);
$_ZIP = (! $CONFIG['finances']['zip'] ? trans("Not set") : $CONFIG['finances']['zip']);
$_CITY = (! $CONFIG['finances']['city'] ? trans("Not set") : $CONFIG['finances']['city']);
$_ACCOUNT = (! $CONFIG['finances']['account'] ? '00000000000000000000000000' : $CONFIG['finances']['account']);
$_TITLE = (! $CONFIG['finances']['pay_title'] ? trans("Not set") : $CONFIG['finances']['pay_title']);
$_LMARGIN = (! $CONFIG['finances']['leftmargin'] ? 0 : $CONFIG['finances']['leftmargin']);
$_BMARGIN = (! $CONFIG['finances']['bottommargin'] ? 0 : $CONFIG['finances']['bottommargin']);

require_once($_LIB_DIR.'/ezpdf/class.ezpdf.php');

$diff = array(177=>'aogonek',161=>'Aogonek',230=>'cacute',198=>'Cacute',234=>'eogonek',202=>'Eogonek',
	241=>'nacute',209=>'Nacute',179=>'lslash',163=>'Lslash',182=>'sacute',166=>'Sacute',
	188=>'zacute',172=>'Zacute',191=>'zdot',175=>'Zdot'); 
$pdf =& new Cezpdf('A4','landscape');
//$pdf =& new Cezpdf('A4','portrait');
$pdf->addInfo('Producer','LMS Developers');
$pdf->addInfo('Title',trans('Form of Cash Transfer'));
$pdf->addInfo('Creator','LMS '.$layout['lmsv']);
$pdf->setPreferences('FitWindow','1');
$pdf->ezSetMargins(0,0,0,0);
$pdf->selectFont($_LIB_DIR.'/ezpdf/arial.afm',array('encoding'=>'WinAnsiEncoding','differences'=>$diff)); 
$pdf->setLineStyle(2);
$id = $pdf->getFirstPageId();

@setlocale('LC_NUMERIC','C');																						

$count = sizeof($list);;
$i = 0;

foreach($list as $row)
{
    main_form(0,0,$row);
    main_form(0,310,$row);
    main_form(440,0,$row);
    main_form(440,310,$row);
    $i++;
    if($i < $count) $id = $pdf->newPage(1, $id, 'after');
}

$pdf->ezStream();

?>

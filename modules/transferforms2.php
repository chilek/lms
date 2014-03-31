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

// Przygotowane dla druków firmy Michalczyk i Prokop Sp. z o.o.
// dla drukarki HP LJ 1010 zostawił leftmargin = 0, bottommargin = 0

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
    global $_TITLE, $_LMARGIN, $_BMARGIN;

    $balance = $data['balance'] < 0 ? -$data['balance'] : $data['balance'];

    $font_size = 14;
    $lineh = 25;
    $x += $_LMARGIN;
    $y += $_BMARGIN;

    $y += 275;
    $pdf->addtext($x,$y,$font_size,iconv('UTF-8', 'ISO-8859-2',$data['d_name']));
    $y -= $lineh;
    $pdf->addtext($x,$y,$font_size,iconv('UTF-8', 'ISO-8859-2',trim($data['d_zip'].' '.$data['d_city'].' '.$data['d_address'])));
    $y -= $lineh;
//    for($i=0; $i<26; $i++)
//    {
//	    $pdf->addtext($x+$i*14.6,$y,$font_size,$_ACCOUNT[$i]);
//    }
    $pdf->addtext($x,$y,$font_size, bankaccount($data['id'], $data['account']));
    $y -= $lineh;
    $pdf->addtext($x+220,$y,$font_size,sprintf('%.2f',$balance));
    $y -= $lineh;
    $pdf->addtext($x,$y,$font_size,iconv('UTF-8', 'ISO-8859-2',trans('$a dollars $b cents',to_words(floor($balance)),to_words(round(($balance-floor($balance))*100)))));
    $y -= $lineh;
    $pdf->addtext($x,$y,$font_size,truncate(iconv('UTF-8', 'ISO-8859-2',$data['customername'])));
    $y -= $lineh;
    $pdf->addtext($x,$y,$font_size,truncate(iconv('UTF-8', 'ISO-8859-2',trim($data['zip'].' '.$data['city'].' '.$data['address']))));
    $y -= $lineh;
    $pdf->addtext($x,$y,$font_size,iconv('UTF-8', 'ISO-8859-2',$_TITLE));
    $y -= $lineh;
    $pdf->addtext($x,$y,$font_size,iconv('UTF-8', 'ISO-8859-2',trans('Customer ID: $a',sprintf('%04d',$data['id']))));
}

$balance = $_POST['balance'] ? $_POST['balance'] : 0;
$customer = isset($_POST['customer']) ? intval($_POST['customer']) : 0;
$group = isset($_POST['customergroup']) ? intval($_POST['customergroup']) : 0;
$exclgroup = isset($_POST['groupexclude']) ? 1 : 0;

$list = $DB->GetAll('SELECT c.id, c.address, c.zip, c.city, d.account,
	d.name AS d_name, d.address AS d_address, d.zip AS d_zip, d.city AS d_city, '
	.$DB->Concat('UPPER(lastname)',"' '",'c.name').' AS customername,
	COALESCE(SUM(cash.value), 0.00) AS balance
	FROM customersview c 
	LEFT JOIN cash ON (c.id = cash.customerid)
	LEFT JOIN divisions d ON (d.id = c.divisionid)
	WHERE deleted = 0'
	.($customer ? ' AND c.id = '.$customer : '')
	.($group ?
        ' AND '.($exclgroup ? 'NOT' : '').'
	        EXISTS (SELECT 1 FROM customerassignments a
		WHERE a.customergroupid = '.$group.' AND a.customerid = c.id)' : '')
	.' GROUP BY c.id, c.lastname, c.name, c.address, c.zip, c.city, d.account, d.name, d.address, d.zip, d.city
	HAVING COALESCE(SUM(cash.value), 0.00) < ? ORDER BY c.id',
	array(str_replace(',','.',$balance)));

if(!$list)
{
    $SESSION->close();
    die;
}

$_TITLE = (!isset($CONFIG['finances']['pay_title']) ? trans('Not set') : $CONFIG['finances']['pay_title']);
$_LMARGIN = (!isset($CONFIG['finances']['leftmargin']) ? 0 : $CONFIG['finances']['leftmargin']);
$_BMARGIN = (!isset($CONFIG['finances']['bottommargin']) ? 0 : $CONFIG['finances']['bottommargin']);

require_once(LIB_DIR . '/ezpdf.php');

$pdf = init_pdf('A4', 'landscape', trans('Form of Cash Transfer'));

$pdf->setLineStyle(2);

$id = $pdf->getFirstPageId();

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

close_pdf($pdf);

?>

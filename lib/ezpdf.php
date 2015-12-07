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

define('PDF_MARGIN_BOTTOM', 40);
define('PDF_MARGIN_TOP', 40);
define('PDF_MARGIN_LEFT', 30);
define('PDF_MARGIN_RIGHT', 30);

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
    return($pdf->getFontHeight($size));
}

function text_align_left($x,$y,$size,$text) 
{
    global $pdf;
    $pdf->addText($x,$y,$size,$text);
    return($pdf->getFontHeight($size));
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
    while ($text!='') {
	$text = $pdf->addText($x, $y, $size,$text, $width, $justify);
	$y = $y - $pdf->getFontHeight($size);
    }
    return($y);
}

function getWrapTextWidth($font_size,$txt)
{
    global $pdf, $margin;

    $long = '';
    if($words = explode(' ', $txt))
    {
        foreach($words as $word)
	    if(strlen($word) > strlen($long))
		$long = $word;
    }
    else
	    $long = $txt;

    return $pdf->getTextWidth($font_size, $long)+2*$margin+1;
}

// page break checking
function check_page_length(&$y, $len=0)
{
	global $pdf, $id;

	if($y - $len < PDF_MARGIN_BOTTOM)
	{
		$pdf->ezNewPage();
		$y = $pdf->ez['pageHeight'] - PDF_MARGIN_TOP;
	}
}

// brzydkie hacki dla ezpdf 
@setlocale(LC_NUMERIC, 'C');
//mb_internal_encoding('ISO-8859-2'); // can't be set to UTF-8

function new_page() {
	global $pdf;

	$pdf->ezNewPage();
}

function init_pdf($pagesize, $orientation, $title)
{
	global $layout;

	$diff = array(
		177=>'aogonek',
		161=>'Aogonek',
		230=>'cacute',
		198=>'Cacute',
		234=>'eogonek',
		202=>'Eogonek',
		241=>'nacute',
		209=>'Nacute',
		179=>'lslash',
		163=>'Lslash',
		182=>'sacute',
		166=>'Sacute',
		188=>'zacute',
	        172=>'Zacute',
		191=>'zdot',
		175=>'Zdot',
		185=>'scaron',
		169=>'Scaron',
		232=>'ccaron',
		200=>'Ccaron',
		236=>'edot',
		204=>'Edot',
		231=>'iogonek',
		199=>'Iogonek',
		249=>'uogonek',
		217=>'Uogonek',
		254=>'umacron',
		222=>'Umacron',
		190=>'zcaron',
		174=>'Zcaron'
	);

	$pdf = new Cezpdf($pagesize, $orientation); //landscape/portrait
	$pdf->isUnicode = true;

	$pdf->addInfo('Producer','LMS Developers');
	$pdf->addInfo('Title', $title);
	$pdf->addInfo('Creator','LMS '.$layout['lmsv']);
	$pdf->setPreferences('FitWindow','1');
	$pdf->ezSetMargins(PDF_MARGIN_TOP, PDF_MARGIN_BOTTOM, PDF_MARGIN_LEFT, PDF_MARGIN_RIGHT);
	$pdf->setLineStyle(0.5);
	$pdf->setFontFamily('arial', array('b' => 'arialbd'));
	$pdf->selectFont('arial', array('encoding' => 'WinAnsiEncoding', 'differences' => $diff),
		1, true);

	return $pdf;
}

function close_pdf(&$pdf, $name = null)
{
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');
	if (!is_null($name))
		$options = array('Content-Disposition' => $name);
	$pdf->ezStream($options);
}

?>

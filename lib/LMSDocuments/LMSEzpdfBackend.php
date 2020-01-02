<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

// brzydkie hacki dla ezpdf
@setlocale(LC_NUMERIC, 'C');

class LMSEzpdfBackend extends Cezpdf
{
    private $margin;

    public function __construct($pagesize, $orientation, $title)
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

        parent::__construct($pagesize, $orientation); //landscape/portrait
        $this->isUnicode = true;

        $this->addInfo('Producer', 'LMS Developers');
        $this->addInfo('Title', $title);
        $this->addInfo('Creator', 'LMS '.$layout['lmsv']);
        $this->setPreferences('FitWindow', '1');
        list ($margin_top, $margin_right, $margin_bottom, $margin_left) =
            explode(',', ConfigHelper::getConfig('invoices.ezpdf_margins', '40,30,40,30'));
        $this->ezSetMargins(trim($margin_top), trim($margin_bottom), trim($margin_left), trim($margin_right));
        $this->setLineStyle(0.5);
        $this->setFontFamily('arial', array('b' => 'arialbd'));
        $this->selectFont(
            'arial',
            array('encoding' => 'WinAnsiEncoding', 'differences' => $diff),
            1,
            true
        );
    }

    public function AppendPage()
    {
        $this->ezNewPage();
    }

    public function WriteToBrowser($filename = null)
    {
        header('Pragma: private');
        header('Cache-control: private, must-revalidate');
        if (!is_null($filename)) {
            $options = array('Content-Disposition' => $filename);
        }
        $this->ezStream($options);
    }

    public function WriteToString()
    {
        return $this->ezOutput();
    }

    public function text_autosize($x, $y, $size, $text, $width)
    {
        while ($this->getTextWidth($size, $text) > $width) {
            $size = $size - 1;
        }
        $this->addtext($x, $y, $size, $text);
    }

    public function text_align_right($x, $y, $size, $text)
    {
        $this->addText($x - $this->getTextWidth($size, $text), $y, $size, $text);
        return $this->getFontHeight($size);
    }

    public function text_align_left($x, $y, $size, $text)
    {
        $this->addText($x, $y, $size, $text);
        return $this->getFontHeight($size);
    }

    public function text_align_center($x, $y, $size, $text)
    {
        $this->addText($x - $this->getTextWidth($size, $text) / 2, $y, $size, $text);
        return $this->getFontHeight($size);
    }

    public function text_wrap($x, $y, $width, $size, $text, $justify)
    {
        while ($text!='') {
            $text = $this->addText($x, $y, $size, $text, $width, $justify);
            $y = $y - $this->getFontHeight($size);
        }
        return $y;
    }

    public function getWrapTextWidth($font_size, $txt)
    {
        $long = '';
        if ($words = explode(' ', $txt)) {
            foreach ($words as $word) {
                if (strlen($word) > strlen($long)) {
                    $long = $word;
                }
            }
        } else {
            $long = $txt;
        }

        return $this->getTextWidth($font_size, $long) + 2 * $this->margin + 1;
    }

    // page break checking
    public function check_page_length(&$y, $len = 0)
    {
        if ($y - $len < PDF_MARGIN_BOTTOM) {
            $this->ezNewPage();
            $y = $this->ez['pageHeight'] - PDF_MARGIN_TOP;
        }
    }
}

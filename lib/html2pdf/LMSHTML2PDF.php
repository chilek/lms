<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

class LMSHTML2PDF extends \Spipu\Html2Pdf\Html2Pdf
{
    // default font
    public const TCPDF_FONT = 'liberationsans';

    /**
     * class constructor
     *
     * @access public
     * @param  string      $orientation page orientation, same as TCPDF
     * @param  mixed       $format      The format used for pages, same as TCPDF
     * @param  $tring      $langue      Lang : fr, en, it...
     * @param  boolean     $unicode     TRUE means that the input text is unicode (default = true)
     * @param  String      $encoding    charset encoding; default is UTF-8
     * @param  array       $marges      Default margins (left, top, right, bottom)
     * @return LMSHTML2PDF $this
     */
    public function __construct(
        $orientation = 'P',
        $format = 'A4',
        $langue = 'fr',
        $unicode = true,
        $encoding = 'UTF-8',
        $margins = array(5, 5, 5, 8),
        $pdfa = false
    ) {
        parent::__construct(
            $orientation,
            $format,
            $langue,
            $unicode,
            $encoding,
            $margins,
            $pdfa
        );

        $this->pdf->AddFont(self::TCPDF_FONT, 'I', LIB_DIR . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . 'liberationsansi');
        $this->pdf->AddFont(self::TCPDF_FONT, 'B', LIB_DIR . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . 'liberationsansb');
        $this->pdf->AddFont(self::TCPDF_FONT, 'BI', LIB_DIR . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . 'liberationsansbi');
        $this->pdf->AddFont(self::TCPDF_FONT, '', LIB_DIR . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . 'liberationsans');

        // init the CSS parsing object
        $cssConverter = new \Spipu\Html2Pdf\CssConverter();
        $textParser = new \Spipu\Html2Pdf\Parsing\TextParser($encoding);
        $tagParser = new \Spipu\Html2Pdf\Parsing\TagParser($textParser);
        $this->parsingCss = new LMSHTML2PDF_parsingCss($this->pdf, $tagParser, $cssConverter);
        $this->parsingCss->fontSet();

        return $this;
    }
}

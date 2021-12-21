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
        // init the page number
        $this->_page         = 0;
        $this->_firstPage    = true;

        // save the parameters
        $this->_orientation  = $orientation;
        $this->_format       = $format;
        $this->_langue       = strtolower($langue);
        $this->_unicode      = $unicode;
        $this->_encoding     = $encoding;
        $this->_pdfa         = $pdfa;

        // load the Local
        \Spipu\Html2Pdf\Locale::load($this->_langue);

        // create the LMSTML2PDF_myPdf object
        $this->pdf = new \Spipu\Html2Pdf\MyPdf($orientation, 'mm', $format, $unicode, $encoding, false, $pdfa);

        // init the CSS parsing object
        $this->cssConverter = new \Spipu\Html2Pdf\CssConverter();
        $textParser = new \Spipu\Html2Pdf\Parsing\TextParser($encoding);
        $tagParser = new \Spipu\Html2Pdf\Parsing\TagParser($textParser);
        $this->parsingCss = new LMSHTML2PDF_parsingCss($this->pdf, $tagParser, $this->cssConverter);
        $this->parsingCss->fontSet();
        $this->_defList = array();

        // init some tests
        $this->setTestTdInOnePage(false);
        $this->setTestIsImage(true);

        // init the default font
        $this->setDefaultFont(null);

        // init the HTML parsing object
        $this->parsingHtml = new \Spipu\Html2Pdf\Parsing\Html($textParser);
        $this->_subHtml = null;
        $this->_subPart = false;

        // init the marges of the page
        if (!is_array($margins)) {
            $margins = array($margins, $margins, $margins, $margins);
        }
        $this->setDefaultMargins($margins);
        $this->setMargins();
        $this->_marges = array();

        // init the form's fields
        $this->_lstField = array();

        $this->svgDrawer = new \Spipu\Html2Pdf\SvgDrawer($this->pdf, $this->cssConverter);

        $htmlExtension = new \Spipu\Html2Pdf\Extension\Core\HtmlExtension();
        $this->addExtension($htmlExtension);
        $svgExtension = new \Spipu\Html2Pdf\Extension\Core\SvgExtension($this->svgDrawer);
        $this->addExtension($svgExtension);

        return $this;
    }
}

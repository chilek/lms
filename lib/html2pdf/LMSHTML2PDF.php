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

class LMSHTML2PDF extends HTML2PDF {
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
    public function __construct($orientation = 'P', $format = 'A4', $langue='fr', $unicode=true, $encoding='UTF-8', $marges = array(5, 5, 5, 8))
    {
        // init the page number
        $this->_page         = 0;
        $this->_firstPage    = true;

        // save the parameters
        $this->_orientation  = $orientation;
        $this->_format       = $format;
        $this->_langue       = strtolower($langue);
        $this->_unicode      = $unicode;
        $this->_encoding     = $encoding;

        // load the Local
        HTML2PDF_locale::load($this->_langue);

        // create the LMSTML2PDF_myPdf object
        $this->pdf = new HTML2PDF_myPdf($orientation, 'mm', $format, $unicode, $encoding);

        // init the CSS parsing object
        $this->parsingCss = new LMSHTML2PDF_parsingCss($this->pdf);
        $this->parsingCss->fontSet();
        $this->_defList = array();

        // init some tests
        $this->setTestTdInOnePage(false);
        $this->setTestIsImage(true);
        $this->setTestIsDeprecated(true);

        // init the default font
        $this->setDefaultFont(null);

        // init the HTML parsing object
        $this->parsingHtml = new HTML2PDF_parsingHtml($this->_encoding);
        $this->_subHtml = null;
        $this->_subPart = false;

        // init the marges of the page
        if (!is_array($marges)) $marges = array($marges, $marges, $marges, $marges);
        $this->_setDefaultMargins($marges[0], $marges[1], $marges[2], $marges[3]);
        $this->_setMargins();
        $this->_marges = array();

        // init the form's fields
        $this->_lstField = array();

        return $this;
    }
}

?>

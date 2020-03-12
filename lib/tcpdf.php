<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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

/*  To improve performance of TCPDF
 *  install and configure a PHP opcode cacher like XCache
 *  http://xcache.lighttpd.net/
 *  This reduces execution time by ~30-50%
 */

if (!defined('LIB_DIR')) {
    define('LIB_DIR', dirname(__FILE__));
}

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'pol.php');

class LMSTCPDF extends TCPDF
{
    /* set own Header function */
    public function Header()
    {
        /* insert your own logo in lib/tcpdf/images/logo.png */
        $image_file = K_PATH_IMAGES . 'logo.png';
        if (file_exists($image_file)) {
            $this->Image($image_file, 13, 10, 50, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
    }

    /* set own Footer function */
    public function Footer()
    {
        $cur_y = $this->y;
        $this->SetTextColor(0, 0, 0);
        $line_width = 0.85 / $this->k;
        $this->SetLineStyle(array('width' => $line_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
        /* print barcode with invoice number in footer */
        $barcode = $this->getBarcode();
        if (!empty($barcode) && ConfigHelper::getConfig('invoices.template_file') == 'standard') {
            $this->Ln($line_width);
            $style = array(
                    'position' => 'L',
                    'align' => 'L',
                    'stretch' => false,
                    'fitwidth' => true,
                    'cellfitalign' => '',
                    'border' => false,
                    'padding' => 0,
                    'fgcolor' => array(0, 0, 0),
                    'bgcolor' => false,
                    'text' => true,
                    'font' => 'times',
                    'fontsize' => 6,
                    'stretchtext' => 0
            );
            $this->write1DBarcode($barcode, 'C128', '', $cur_y + $line_width - 0.25, '', ($this->footer_margin - 2), 0.3, $style, '');
            /* draw line */
            $this->SetY($cur_y);
            $this->SetX($this->original_rMargin);
            $this->Cell(0, 0, '', array('T' => array('width' => 0.1)), 0, 'L');
        }
    }

    public function getWrapStringWidth($txt, $font_style)
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

        return $this->getStringWidth($long, '', $font_style) + 2.5;
    }

    public function SetProducer($producer)
    {
        $this->producer = $producer;
    }

    /**
     * Overrides standars TCPDF putinfo method to add producer
     */
    protected function _putinfo()
    {

        $oid = $this->_newobj();
        $out = '<<';
        // store current isunicode value
        $prev_isunicode = $this->isunicode;
        if ($this->docinfounicode) {
            $this->isunicode = true;
        }
        if (!TCPDF_STATIC::empty_string($this->title)) {
            // The document's title.
            $out .= ' /Title '.$this->_textstring($this->title, $oid);
        }
        if (!TCPDF_STATIC::empty_string($this->author)) {
            // The name of the person who created the document.
            $out .= ' /Author '.$this->_textstring($this->author, $oid);
        }
        if (!TCPDF_STATIC::empty_string($this->subject)) {
            // The subject of the document.
            $out .= ' /Subject '.$this->_textstring($this->subject, $oid);
        }
        if (!TCPDF_STATIC::empty_string($this->keywords)) {
            // Keywords associated with the document.
            $out .= ' /Keywords '.$this->_textstring($this->keywords, $oid);
        }
        if (!TCPDF_STATIC::empty_string($this->creator)) {
            // If the document was converted to PDF from another format, the name of the conforming product that created the original document from which it was converted.
            $out .= ' /Creator '.$this->_textstring($this->creator, $oid);
        }
        // restore previous isunicode value
        $this->isunicode = $prev_isunicode;
        if (!TCPDF_STATIC::empty_string($this->producer)) {
            // The producer of the document
            $out .= ' /Producer '.$this->_textstring($this->producer.' - '.TCPDF_STATIC::getTCPDFProducer(), $oid);
        } else {
            // default producer
            $out .= ' /Producer '.$this->_textstring(TCPDF_STATIC::getTCPDFProducer(), $oid);
        }
        // The date and time the document was created, in human-readable form
        $out .= ' /CreationDate '.$this->_datestring(0, $this->doc_creation_timestamp);
        // The date and time the document was most recently modified, in human-readable form
        $out .= ' /ModDate '.$this->_datestring(0, $this->doc_modification_timestamp);
        // A name object indicating whether the document has been modified to include trapping information
        $out .= ' /Trapped /False';
        $out .= ' >>';
        $out .= "\n".'endobj';
        $this->_out($out);
        return $oid;
    }

    public function SetFont($family, $style = '', $size = null, $fontfile = '', $subset = 'default', $out = true)
    {
        static $supported_fonts = array(
            'liberationsans' => true,
        );
        $fontfile = LIB_DIR . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR
            . (isset($supported_fonts[$family]) ? $family : 'liberationsans') . $style . '.php';
        parent::SetFont($family, $style, $size, $fontfile, $subset, $out);
    }
}

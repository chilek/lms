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
        
    /**
     * Returns the PDF string code to print a cell (rectangular area) with optional borders, background color and character string. The upper-left corner of the cell corresponds to the current position. The text can be aligned or centered. After the call, the current position moves to the right or to the next line. It is possible to put a link on the text.<br />
     * If automatic page breaking is enabled and the cell goes beyond the limit, a page break is done before outputting.
     * @param $w (float) Cell width. If 0, the cell extends up to the right margin.
     * @param $h (float) Cell height. Default value: 0.
     * @param $txt (string) String to print. Default value: empty string.
     * @param $border (mixed) Indicates if borders must be drawn around the cell. The value can be a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul> or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul> or an array of line styles for each border group - for example: array('LTRB' => array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)))
     * @param $ln (int) Indicates where the current position should go after the call. Possible values are:<ul><li>0: to the right (or left for RTL languages)</li><li>1: to the beginning of the next line</li><li>2: below</li></ul>Putting 1 is equivalent to putting 0 and calling Ln() just after. Default value: 0.
     * @param $align (string) Allows to center or align the text. Possible values are:<ul><li>L or empty string: left align (default value)</li><li>C: center</li><li>R: right align</li><li>J: justify</li></ul>
     * @param $fill (boolean) Indicates if the cell background must be painted (true) or transparent (false).
     * @param $link (mixed) URL or identifier returned by AddLink().
     * @param $stretch (int) font stretch mode: <ul><li>0 = disabled</li><li>1 = horizontal scaling only if text is larger than cell width</li><li>2 = forced horizontal scaling to fit cell width</li><li>3 = character spacing only if text is larger than cell width</li><li>4 = forced character spacing to fit cell width</li></ul> General font stretching and scaling values will be preserved when possible.
     * @param $ignore_min_height (boolean) if true ignore automatic minimum height value.
     * @param $calign (string) cell vertical alignment relative to the specified Y value. Possible values are:<ul><li>T : cell top</li><li>C : center</li><li>B : cell bottom</li><li>A : font top</li><li>L : font baseline</li><li>D : font bottom</li></ul>
     * @param $valign (string) text vertical alignment inside the cell. Possible values are:<ul><li>T : top</li><li>M : middle</li><li>B : bottom</li></ul>
     * @return string containing cell code
     * @protected
     * @since 1.0
     * @see Cell()
     */
    protected function getCellCode($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M')
    {
        // replace 'NO-BREAK SPACE' (U+00A0) character with a simple space
        $txt = str_replace(TCPDF_FONTS::unichr(160, $this->isunicode), ' ', $txt);
        $prev_cell_margin = $this->cell_margin;
        $prev_cell_padding = $this->cell_padding;
        $txt = TCPDF_STATIC::removeSHY($txt, $this->isunicode);
        $rs = ''; //string to be returned
        $this->adjustCellPadding($border);
        if (!$ignore_min_height) {
            $min_cell_height = $this->getCellHeight($this->FontSize);
            if ($h < $min_cell_height) {
                $h = $min_cell_height;
            }
        }
        $k = $this->k;
        // check page for no-write regions and adapt page margins if necessary
        list($this->x, $this->y) = $this->checkPageRegions($h, $this->x, $this->y);
        if ($this->rtl) {
            $x = $this->x - $this->cell_margin['R'];
        } else {
            $x = $this->x + $this->cell_margin['L'];
        }
        $y = $this->y + $this->cell_margin['T'];
        $prev_font_stretching = $this->font_stretching;
        $prev_font_spacing = $this->font_spacing;
        // cell vertical alignment
        switch ($calign) {
            case 'A': {
                // font top
                switch ($valign) {
                    case 'T': {
                        // top
                        $y -= $this->cell_padding['T'];
                        break;
                    }
                    case 'B': {
                        // bottom
                        $y -= ($h - $this->cell_padding['B'] - $this->FontAscent - $this->FontDescent);
                        break;
                    }
                    default:
                    case 'C':
                    case 'M': {
                        // center
                        $y -= (($h - $this->FontAscent - $this->FontDescent) / 2);
                        break;
                    }
                }
                break;
            }
            case 'L': {
                // font baseline
                switch ($valign) {
                    case 'T': {
                        // top
                        $y -= ($this->cell_padding['T'] + $this->FontAscent);
                        break;
                    }
                    case 'B': {
                        // bottom
                        $y -= ($h - $this->cell_padding['B'] - $this->FontDescent);
                        break;
                    }
                    default:
                    case 'C':
                    case 'M': {
                        // center
                        $y -= (($h + $this->FontAscent - $this->FontDescent) / 2);
                        break;
                    }
                }
                break;
            }
            case 'D': {
                // font bottom
                switch ($valign) {
                    case 'T': {
                        // top
                        $y -= ($this->cell_padding['T'] + $this->FontAscent + $this->FontDescent);
                        break;
                    }
                    case 'B': {
                        // bottom
                        $y -= ($h - $this->cell_padding['B']);
                        break;
                    }
                    default:
                    case 'C':
                    case 'M': {
                        // center
                        $y -= (($h + $this->FontAscent + $this->FontDescent) / 2);
                        break;
                    }
                }
                break;
            }
            case 'B': {
                // cell bottom
                $y -= $h;
                break;
            }
            case 'C':
            case 'M': {
                // cell center
                $y -= ($h / 2);
                break;
            }
            default:
            case 'T': {
                // cell top
                break;
            }
        }
        // text vertical alignment
        switch ($valign) {
            case 'T': {
                // top
                $yt = $y + $this->cell_padding['T'];
                break;
            }
            case 'B': {
                // bottom
                $yt = $y + $h - $this->cell_padding['B'] - $this->FontAscent - $this->FontDescent;
                break;
            }
            default:
            case 'C':
            case 'M': {
                // center
                $yt = $y + (($h - $this->FontAscent - $this->FontDescent) / 2);
                break;
            }
        }
        $basefonty = $yt + $this->FontAscent;
        if (TCPDF_STATIC::empty_string($w) or ($w <= 0)) {
            if ($this->rtl) {
                $w = $x - $this->lMargin;
            } else {
                $w = $this->w - $this->rMargin - $x;
            }
        }
        $s = '';
        // fill and borders
        if (is_string($border) and (strlen($border) == 4)) {
            // full border
            $border = 1;
        }
        if ($fill or ($border == 1)) {
            if ($fill) {
                $op = ($border == 1) ? 'B' : 'f';
            } else {
                $op = 'S';
            }
            if ($this->rtl) {
                $xk = (($x - $w) * $k);
            } else {
                $xk = ($x * $k);
            }
            $s .= sprintf('%F %F %F %F re %s ', $xk, (($this->h - $y) * $k), ($w * $k), (-$h * $k), $op);
        }
        // draw borders
        $s .= $this->getCellBorder($x, $y, $w, $h, $border);
        if ($txt != '') {
            $txt2 = $txt;
            if ($this->isunicode) {
                                $txt2 = $this->UTF8ToLatin2($txt2, $this->isunicode);
            }
            $txt2 = TCPDF_STATIC::_escape($txt2);
            // get current text width (considering general font stretching and spacing)
            $txwidth = $this->GetStringWidth($txt);
            $width = $txwidth;
            // check for stretch mode
            if ($stretch > 0) {
                // calculate ratio between cell width and text width
                if ($width <= 0) {
                    $ratio = 1;
                } else {
                    $ratio = (($w - $this->cell_padding['L'] - $this->cell_padding['R']) / $width);
                }
                // check if stretching is required
                if (($ratio < 1) or (($ratio > 1) and (($stretch % 2) == 0))) {
                    // the text will be stretched to fit cell width
                    if ($stretch > 2) {
                        // set new character spacing
                        $this->font_spacing += ($w - $this->cell_padding['L'] - $this->cell_padding['R'] - $width) / (max(($this->GetNumChars($txt) - 1), 1) * ($this->font_stretching / 100));
                    } else {
                        // set new horizontal stretching
                        $this->font_stretching *= $ratio;
                    }
                    // recalculate text width (the text fills the entire cell)
                    $width = $w - $this->cell_padding['L'] - $this->cell_padding['R'];
                    // reset alignment
                    $align = '';
                }
            }
            if ($this->font_stretching != 100) {
                // apply font stretching
                $rs .= sprintf('BT %F Tz ET ', $this->font_stretching);
            }
            if ($this->font_spacing != 0) {
                // increase/decrease font spacing
                $rs .= sprintf('BT %F Tc ET ', ($this->font_spacing * $this->k));
            }
            if ($this->ColorFlag and ($this->textrendermode < 4)) {
                $s .= 'q '.$this->TextColor.' ';
            }
            // rendering mode
            $s .= sprintf('BT %d Tr %F w ET ', $this->textrendermode, ($this->textstrokewidth * $this->k));
            // count number of spaces
            $ns = substr_count($txt, chr(32));
            // Justification
            $spacewidth = 0;
            if (($align == 'J') and ($ns > 0)) {
                if ($this->isUnicodeFont()) {
                    // get string width without spaces
                    $width = $this->GetStringWidth(str_replace(' ', '', $txt));
                    // calculate average space width
                    $spacewidth = -1000 * ($w - $width - $this->cell_padding['L'] - $this->cell_padding['R']) / ($ns?$ns:1) / ($this->FontSize?$this->FontSize:1);
                    if ($this->font_stretching != 100) {
                        // word spacing is affected by stretching
                        $spacewidth /= ($this->font_stretching / 100);
                    }
                    // set word position to be used with TJ operator
                    $txt2 = str_replace(chr(0).chr(32), ') '.sprintf('%F', $spacewidth).' (', $txt2);
                    $unicode_justification = true;
                } else {
                    // get string width
                    $width = $txwidth;
                    // new space width
                    $spacewidth = (($w - $width - $this->cell_padding['L'] - $this->cell_padding['R']) / ($ns?$ns:1)) * $this->k;
                    if ($this->font_stretching != 100) {
                        // word spacing (Tw) is affected by stretching
                        $spacewidth /= ($this->font_stretching / 100);
                    }
                    // set word spacing
                    $rs .= sprintf('BT %F Tw ET ', $spacewidth);
                }
                $width = $w - $this->cell_padding['L'] - $this->cell_padding['R'];
            }
            // replace carriage return characters
            $txt2 = str_replace("\r", ' ', $txt2);
            switch ($align) {
                case 'C': {
                    $dx = ($w - $width) / 2;
                    break;
                }
                case 'R': {
                    if ($this->rtl) {
                        $dx = $this->cell_padding['R'];
                    } else {
                        $dx = $w - $width - $this->cell_padding['R'];
                    }
                    break;
                }
                case 'L': {
                    if ($this->rtl) {
                        $dx = $w - $width - $this->cell_padding['L'];
                    } else {
                        $dx = $this->cell_padding['L'];
                    }
                    break;
                }
                case 'J':
                default: {
                    if ($this->rtl) {
                        $dx = $this->cell_padding['R'];
                    } else {
                        $dx = $this->cell_padding['L'];
                    }
                    break;
                }
            }
            if ($this->rtl) {
                $xdx = $x - $dx - $width;
            } else {
                $xdx = $x + $dx;
            }
            $xdk = $xdx * $k;
            // print text
            $s .= sprintf('BT %F %F Td [(%s)] TJ ET', $xdk, (($this->h - $basefonty) * $k), $txt2);
            if (isset($uniblock)) {
                // print overlapping characters as separate string
                $xshift = 0; // horizontal shift
                $ty = (($this->h - $basefonty + (0.2 * $this->FontSize)) * $k);
                $spw = (($w - $txwidth - $this->cell_padding['L'] - $this->cell_padding['R']) / ($ns?$ns:1));
                foreach ($uniblock as $uk => $uniarr) {
                    if (($uk % 2) == 0) {
                        // x space to skip
                        if ($spacewidth != 0) {
                            // justification shift
                            $xshift += (count(array_keys($uniarr, 32)) * $spw);
                        }
                        $xshift += $this->GetArrStringWidth($uniarr); // + shift justification
                    } else {
                        // character to print
                        $topchr = TCPDF_FONTS::arrUTF8ToUTF16BE($uniarr, false);
                        $topchr = TCPDF_STATIC::_escape($topchr);
                        $s .= sprintf(' BT %F %F Td [(%s)] TJ ET', ($xdk + ($xshift * $k)), $ty, $topchr);
                    }
                }
            }
            if ($this->underline) {
                $s .= ' '.$this->_dounderlinew($xdx, $basefonty, $width);
            }
            if ($this->linethrough) {
                $s .= ' '.$this->_dolinethroughw($xdx, $basefonty, $width);
            }
            if ($this->overline) {
                $s .= ' '.$this->_dooverlinew($xdx, $basefonty, $width);
            }
            if ($this->ColorFlag and ($this->textrendermode < 4)) {
                $s .= ' Q';
            }
            if ($link) {
                $this->Link($xdx, $yt, $width, ($this->FontAscent + $this->FontDescent), $link, $ns);
            }
        }
        // output cell
        if ($s) {
            // output cell
            $rs .= $s;
            if ($this->font_spacing != 0) {
                // reset font spacing mode
                $rs .= ' BT 0 Tc ET';
            }
            if ($this->font_stretching != 100) {
                // reset font stretching mode
                $rs .= ' BT 100 Tz ET';
            }
        }
        // reset word spacing
        if (!$this->isUnicodeFont() and ($align == 'J')) {
            $rs .= ' BT 0 Tw ET';
        }
        // reset stretching and spacing
        $this->font_stretching = $prev_font_stretching;
        $this->font_spacing = $prev_font_spacing;
        $this->lasth = $h;
        if ($ln > 0) {
            //Go to the beginning of the next line
            $this->y = $y + $h + $this->cell_margin['B'];
            if ($ln == 1) {
                if ($this->rtl) {
                    $this->x = $this->w - $this->rMargin;
                } else {
                    $this->x = $this->lMargin;
                }
            }
        } else {
            // go left or right by case
            if ($this->rtl) {
                $this->x = $x - $w - $this->cell_margin['L'];
            } else {
                $this->x = $x + $w + $this->cell_margin['R'];
            }
        }
        $gstyles = ''.$this->linestyleWidth.' '.$this->linestyleCap.' '.$this->linestyleJoin.' '.$this->linestyleDash.' '.$this->DrawColor.' '.$this->FillColor."\n";
        $rs = $gstyles.$rs;
        $this->cell_padding = $prev_cell_padding;
        $this->cell_margin = $prev_cell_margin;
        return $rs;
    }

    private function UTF8ToLatin2($str, $isunicode = true)
    {
        /* convert UTF-8 to ISO-8859-2 */
        if (!$isunicode) {
            return $str;
        }

        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($str, "ISO-8859-2", "UTF-8");
        } else {
            return iconv("UTF-8", "ISO-8859-2", $str);
        }
    }

    public function SetFont($family, $style = '', $size = null, $fontfile = '', $subset = 'default', $out = true)
    {
        if (in_array($family, array('arial', 'tahoma', 'verdana'))) {
            $fontfile = LIB_DIR . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . $family . $style . '.php';
        }
        parent::SetFont($family, $style, $size, $fontfile, $subset, $out);
    }
}

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

class LMSHTML2PDF_parsingCss extends HTML2PDF_parsingCss
{
    public function fontSet()
    {
        $family = strtolower($this->value['font-family']);

        $b = ($this->value['font-bold']        ? 'B' : '');
        $i = ($this->value['font-italic']      ? 'I' : '');
        $u = ($this->value['font-underline']   ? 'U' : '');
        $d = ($this->value['font-linethrough'] ? 'D' : '');
        $o = ($this->value['font-overline']    ? 'O' : '');

        // font style
        $style = $b.$i;

        if ($this->_defaultFont) {
            if ($family == 'helvetica') {
                $family = 'liberationsans';
            } elseif ($family == 'symbol' || $family == 'zapfdingbats') {
                $style='';
            }

            $fontkey = $family.$style;
            if (!$this->_pdf->isLoadedFont($fontkey)) {
                $family = $this->_defaultFont;
            }
        }

        if ($family == 'helvetica') {
            $family = 'liberationsans';
        } elseif ($family == 'symbol' || $family == 'zapfdingbats') {
            $style='';
        }

        // complete style
        $style.= $u.$d.$o;

        // size : mm => pt
        $size = $this->value['font-size'];
        $size = 72 * $size / 25.4;

        // apply the font
        $this->_pdf->SetFont($family, $style, $this->value['mini-size']*$size);
        $this->_pdf->setTextColorArray($this->value['color']);
        if ($this->value['background']['color']) {
            $this->_pdf->setFillColorArray($this->value['background']['color']);
        } else {
            $this->_pdf->setFillColor(255);
        }
    }
}

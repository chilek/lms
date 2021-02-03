<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

class LMSEzpdfTransferForm extends LMSDocument
{
    protected $id;

    public function __construct($title, $pagesize = 'A4', $orientation = 'portrait')
    {
        parent::__construct('LMSEzpdfBackend', $title, $pagesize, $orientation);

        $this->backend->setLineStyle(2);
    }

    private function truncate($str, $max = 60)
    {
        $len = strlen($str);
        if (!$max || $max >= $len) {
            return $str;
        }

        // musimy pokombinowac bo nie mamy czcionki o stalym rozmiarze,
        // ten sposob i tak jest do kitu, ale dziala lepiej niz staly limit
        for ($i = 0; $i < $len; $i++) {
            if (ctype_upper($str[$i])) {
                $l += 1.4;
            } else {
                $l += 1;
            }
        }
        $max = $max * ($len / $l);

        return substr($str, 0, $max);
    }

    protected function main_form($x, $y)
    {
        $balance = $this->data['balance'] < 0 ? - $this->data['balance'] : $this->data['balance'];

        $font_size = 14;
        $lineh = 25;
        $x += ConfigHelper::getConfig('finances.leftmargin', 0, true);
        $y += ConfigHelper::getConfig('finances.bottommargin', 0, true);

        $y += 275;
        $this->backend->addText($x, $y, $font_size, $this->data['d_name']);
        $y -= $lineh;
        $this->backend->addText($x, $y, $font_size, trim($this->data['d_zip'] . ' ' . $this->data['d_city'] . ' ' . $this->data['d_address']));
        $y -= $lineh;
        $this->backend->addText($x, $y, $font_size, format_bankaccount(bankaccount($this->data['id'], $this->data['account'])));
        $y -= $lineh;
        $this->backend->addText($x + 220, $y, $font_size, sprintf('%.2f', $balance));
        $y -= $lineh;
        $this->backend->addText($x, $y, $font_size, moneyf_in_words($balance));
        $y -= $lineh;
        $this->backend->addText($x, $y, $font_size, $this->truncate($this->data['customername']));
        $y -= $lineh;
        $this->backend->addText($x, $y, $font_size, $this->truncate(trim($this->data['zip'] . ' ' . $this->data['city'] . ' ' . $this->data['address'])));
        $y -= $lineh;
        $this->backend->addText($x, $y, $font_size, ConfigHelper::getConfig('finances.pay_title', trans('Not set')));
        $y -= $lineh;
        $this->backend->addText($x, $y, $font_size, trans('Customer ID: $a', sprintf('%04d', $this->data['id'])));
    }

    public function Draw($data)
    {
        parent::Draw($data);

        $this->main_form(0, 0);
        $this->main_form(0, 310);
        $this->main_form(440, 0);
        $this->main_form(440, 310);

        if (!$this->data['last']) {
            $this->id = $this->backend->newPage(1, $this->id);
        }
    }
}

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

class LMSEzpdfReceipt extends LMSDocument
{
    public function __construct($title, $pagesize = 'A4', $orientation = 'portrait')
    {
        parent::__construct('LMSEzpdfBackend', $title, $pagesize, $orientation);
    }

    protected function receipt_header($x, $y)
    {
        $font_size = 12;
        $yy = $y;
        $xmax = $x + 420;
        $this->backend->line($x, $y, $xmax, $y);
        $y -= $font_size;

//      $this->backend->text_align_left($x + 2,$y + 2,$font_size - 4, trans('Stamp:'));
        $y = $this->backend->text_wrap($x + 2, $y, 170, $font_size - 2, '<b>' . $this->data['d_name'] . '</b>', null);
        $y = $this->backend->text_wrap($x + 2, $y, 170, $font_size - 2, '<b>' . $this->data['d_address'] . '</b>', null);
        $y = $this->backend->text_wrap($x + 2, $y, 170, $font_size - 2, '<b>' . $this->data['d_zip'] . ' ' . $this->data['d_city'] . '</b>', null);
        if (!empty($this->data['countryid']) && !empty($this->data['d_countryid'])
            && $this->data['countryid'] != $this->data['d_countryid']) {
            $this->backend->text_wrap($x + 2, $y, 170, $font_size - 2, '<b>' . $this->data['d_country'] . '</b>', null);
        }
        $y = $yy - $font_size;

        if ($this->data['type'] == 'out') {
            $this->backend->text_align_center($xmax - 70, $y - 10, $font_size + 4, '<b>' . trans('CR-out') . '</b>');
        } else {
            $this->backend->text_align_center($xmax - 70, $y - 10, $font_size + 4, '<b>' . trans('CR-in') . '</b>');
        }
        $this->backend->text_align_center($xmax - 70, $y - 30, $font_size, '<b>' . trans('No. $a', $this->data['number']) . '</b>');

        if ($this->data['type'] == 'out') {
            $y -= $this->backend->text_align_center($x + 210, $y, $font_size, '<b>' . trans('Proof of Pay-out') . '</b>');
        } else {
            $y -= $this->backend->text_align_center($x + 210, $y, $font_size, '<b>' . trans('Proof of Payment') . '</b>');
        }
        $y -= $this->backend->text_align_center($x + 210, $y, $font_size + 4, '<b>' . trans('Receipt') . '</b>');
        $y -= $this->backend->text_align_center($x + 210, $y, $font_size, trans('Date:') . ' ' . date("Y/m/d", $this->data['cdate']));

        $y += $font_size / 2;
        $this->backend->line($x, $yy, $x, $y);
        $this->backend->line($x + 140, $yy, $x + 140, $y);
        $this->backend->line($x + 280, $yy, $x + 280, $y);
        $this->backend->line($xmax, $yy, $xmax, $y);
        $this->backend->line($x, $y, $xmax, $y);

        return $y;
    }

    protected function receipt_buyer($x, $y)
    {
        $font_size=12;
        $yy = $y;
        $xmax = $x + 420;
        $this->backend->line($x, $y, $xmax, $y);
        $y -= $font_size;

        if ($this->data['type'] == 'out') {
            $this->backend->text_align_center($x+315, $y-4, $font_size+4, '<b>' . trans('Has') . '</b>');
            $y -= $this->backend->text_align_center($x+385, $y-4, $font_size+4, '<b>' . trans('Owing') . '</b>');
        } else {
            $this->backend->text_align_center($x+315, $y-4, $font_size+4, '<b>' . trans('Owing') . '</b>');
            $y -= $this->backend->text_align_center($x+385, $y-4, $font_size+4, '<b>' . trans('Has') . '</b>');
        }
        $this->backend->text_align_center($x+315, $y, $font_size, '<b>' . trans('Cash') . '</b>');
        $y -= $this->backend->text_align_center($x+385, $y, $font_size, '<b>' . trans('Account<!singular:noun>') . '</b>');

        $y = $yy - $font_size;
        if ($this->data['type'] == 'out') {
            $this->backend->text_align_left($x+2, $y+2, $font_size-4, trans('To whom:'));
        } else {
            $this->backend->text_align_left($x+2, $y+2, $font_size-4, trans('From who:'));
        }
        $y = $this->backend->text_wrap($x+40, $y-4, 240, $font_size-2, '<b>' . $this->data['name'] . '</b>', null);

        if (!empty($this->data['countryid']) && !empty($this->data['d_countryid'])
            && $this->data['countryid'] != $this->data['d_countryid']) {
            $country = ', ' . $this->data['country'];
        } else {
            $country = '';
        }

        if (empty($this->data['zip'])) {
            $address = '';
        } else {
            $address = $this->data['zip'];
        }
        if (!empty($this->data['city'])) {
            $address .= ' ' . $this->data['city'];
        }
        if (!empty($this->data['address'])) {
            $address .= ', ' . $this->data['address'] . $country;
        }
        if (!empty($address)) {
            $y = $this->backend->text_wrap($x + 2, $y, 240, $font_size - 2, '<b>' . $address . '</b>', null);
        }

        $y += $font_size/2;
        $this->backend->line($x, $yy, $x, $y);
        $this->backend->line($x+280, $yy, $x+280, $y);
        $this->backend->line($x+350, $yy, $x+350, $y);
        $this->backend->line($xmax, $yy, $xmax, $y);
        $this->backend->line($x, $y, $xmax, $y);

        return $y;
    }

    protected function receipt_footer($x, $y)
    {
        $font_size = 8;
        $yy = $y;
        $xmax = $x + 420;
        $this->backend->line($x, $y, $xmax, $y);
        $y -= $font_size;

        $this->backend->text_align_center($x+35, $y, $font_size, trans('Exposed By'));
        $this->backend->text_align_center($x+105, $y, $font_size, trans('Checked By'));
        $this->backend->text_align_center($x+175, $y, $font_size, trans('Approved By'));
        $this->backend->text_align_center($x+245, $y, $font_size, trans('Cash Report'));
        if ($this->data['type'] == 'out') {
            $this->backend->text_align_center($x+350, $y, $font_size, trans('Above amount'));
        } else {
            $this->backend->text_align_center($x+350, $y, $font_size, trans('I confirm receipt of above amount'));
        }

        $y -= 2;
        $this->backend->line($x, $y, $xmax, $y);

        if ($this->data['type'] == 'out') {
            $this->backend->text_align_center($x+315, $y-8, $font_size, trans('payed out'));
            $y -= $this->backend->text_align_center($x+385, $y-8, $font_size, trans('received'));
            $y -= 34;
        } else {
            $y -= 34;
            $this->backend->line($x+300, $y, $xmax-20, $y);
            $y -= $this->backend->text_align_center($x+350, $y-8, $font_size, trans('Signature of cashier'));
        }

        $this->backend->line($x, $yy, $x, $y);
        $this->backend->line($x+70, $yy, $x+70, $y);
        $this->backend->line($x+140, $yy, $x+140, $y);
        $this->backend->line($x+210, $yy, $x+210, $y);
        $this->backend->line($x+280, $yy, $x+280, $y);
        if ($this->data['type'] == 'out') {
            $this->backend->line($x+350, $yy-8, $x+350, $y);
        }
        $this->backend->line($xmax, $yy, $xmax, $y);
        $this->backend->line($x, $y, $xmax, $y);

        $y -= $this->backend->text_align_right($xmax, $y - 6, $font_size / 2, 'Copyright (C) 2001-' . date('Y') . ' LMS Developers');
        $y -= 15;
        $this->backend->setLineStyle(0.5, null, null, array(2,2));
        $this->backend->line($x-10, $y, $xmax+10, $y);
        $this->backend->setLineStyle(0.5, null, null, array(1,0));

        return $y;
    }

    protected function receipt_data($x, $y)
    {
        $font_size = 12;
        $yy = $y;
        $xmax = $x + 420;
        $this->backend->line($x, $y, $xmax, $y);
        $y -= 8;

        $this->backend->text_align_center($x+140, $y, 8, trans('For what'));
        $this->backend->text_align_center($x+315, $y, 8, trans('Value'));
        $this->backend->text_align_center($x+385, $y, 8, trans('Number'));
        $y -= 2;

        $this->backend->line($x, $y, $xmax, $y);
        $y -= $font_size;

        $i = 0;
        if ($this->data['contents']) {
            foreach ($this->data['contents'] as $item) {
                $i++;
                $this->backend->text_align_left($x+2, $y, $font_size-2, '<b>'.$i.'.</b>');
                $y = $this->backend->text_wrap($x+15, $y, 270, $font_size-2, $item['description'], '');
                $this->backend->text_align_right($x+345, $y+$font_size, $font_size-2, moneyf($item['value'], $this->data['currency']));
            }
        }

        $y += $font_size/2;
        $this->backend->line($x, $y, $xmax, $y);
        $y -= $font_size;

        $this->backend->text_align_right($x+275, $y-6, $font_size-2, '<b>' . trans('Total:') . '</b>');
        $this->backend->text_align_right($x+345, $y-6, $font_size-2, '<b>' . moneyf($this->data['total'], $this->data['currency']) . '</b>');
        $y -= $this->backend->text_align_center($x+385, $y, 8, 'Symbole');
        $y -= $this->backend->text_align_center($x+385, $y, 8, 'PL. KAS. Nr');

        $this->backend->line($x, $yy, $x, $y);
        $this->backend->line($x+280, $yy, $x+280, $y);
        $this->backend->line($x+350, $yy, $x+350, $y);
        $this->backend->line($xmax, $yy, $xmax, $y);
        $this->backend->line($x, $y, $xmax, $y);
        $y -= 16;

        $this->backend->text_align_left($x+2, $y, 8, trans('In words:'));
        $y = $this->backend->text_wrap($x+40, $y, 300, $font_size-2, moneyf_in_words($this->data['total'], $this->data['currency']), '');
        $y -= 8;

        $y += $font_size/2;
        $this->backend->line($x, $yy, $x, $y);
        $this->backend->line($x+350, $yy, $x+350, $y);
        $this->backend->line($xmax, $yy, $xmax, $y);
        $this->backend->line($x, $y, $xmax, $y);

        return $y;
    }

    public function Draw($data)
    {
        parent::Draw($data);

        $y = 800;

        for ($i = 0; $i < Utils::docEntityCount($this->data['which']); $i++) {
            $y = $this->receipt_header(80, $y);
            $y = $this->receipt_buyer(80, $y);
            $y = $this->receipt_data(80, $y);
            $y = $this->receipt_footer(80, $y);
            $y -= 20;
        }
        if (!$this->data['last']) {
            $this->NewPage();
        }
    }
}

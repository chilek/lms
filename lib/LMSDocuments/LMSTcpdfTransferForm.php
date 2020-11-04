<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

class LMSTcpdfTransferForm extends LMSDocument
{
    const TCPDF_FONT = 'liberationsans';
    const VALUE_BALANCE = 1;
    const VALUE_ASSIGNMENTS = 2;
    const VALUE_CUSTOM = 3;

    public function __construct($title, $pagesize = 'A4', $orientation = 'portrait')
    {
        parent::__construct('LMSTcpdfBackend', $title, $pagesize, $orientation);

        list ($margin_top, $margin_right, $margin_bottom, $margin_left) = explode(',', ConfigHelper::getConfig('invoices.tcpdf_margins', '27,15,25,15'));
        $this->backend->SetMargins(trim($margin_left), trim($margin_top), trim($margin_right));
        $this->backend->SetAutoPageBreak(true, trim($margin_bottom));
    }

    protected function transferform_simple_form_draw()
    {
        /* set line styles */
        $line_thin = array('width' => 0.15, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 0, 0));
        $line_dash = array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '3, 3', 'phase' => 10, 'color' => array(255, 0, 0));
        $line_light = array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '3, 5', 'phase' => 10, 'color' => array(245, 200, 200));

        $this->backend->setColor('text', 255, 0, 0);
        $this->backend->SetFont(self::TCPDF_FONT, '', 8);
        $this->backend->setFontStretching(120);

        $this->backend->StartTransform();
        $this->backend->Rotate(90, 1, 75);
        $this->backend->Text(1, 75, 'Pokwitowanie dla zleceniodawcy');
        $this->backend->StopTransform();

        $this->backend->SetFont(self::TCPDF_FONT, '', 6);
        $this->backend->setFontStretching(100);

        /* draw simple form */
        $this->backend->Line(1, 1, 210, 1, $line_light);
        $this->backend->Line(61.5, 0, 61.5, 107, $line_light);
        $this->backend->Rect(6, 2, 54, 103, 'F', '', array(245, 200, 200));

        /* division name */
        $this->backend->Rect(7, 3, 17, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(7, 3, 'nazwa odbiorcy');
        $this->backend->Rect(7, 6, 52, 5, 'F', '', array(255, 255, 255));
        $this->backend->Rect(7, 12, 52, 5, 'F', '', array(255, 255, 255));
        $this->backend->Rect(7, 18, 52, 5, 'F', '', array(255, 255, 255));

        /* account */
        $this->backend->Rect(7, 25, 22, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(7, 25, 'nr rachunku odbiorcy');
        $this->backend->Rect(7, 28, 52, 5, 'F', '', array(255, 255, 255));

        /* customer name */
        $this->backend->Rect(7, 34, 22, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(7, 34, 'nazwa zleceniodawcy');
        $this->backend->Rect(7, 37, 52, 5, 'F', '', array(255, 255, 255));
        $this->backend->Rect(7, 43, 52, 5, 'F', '', array(255, 255, 255));
        $this->backend->Rect(7, 49, 52, 5, 'F', '', array(255, 255, 255));

        /* title */
        $this->backend->Rect(7, 55, 11, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(7, 55, 'tytułem');
        $this->backend->Rect(7, 58, 52, 10, 'F', '', array(255, 255, 255));

        /* amount */
        $this->backend->Rect(7, 69, 9, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(7, 69, 'kwota');
        $this->backend->Rect(7, 72, 52, 5, 'F', '', array(255, 255, 255));

        /* stamp */
        $this->backend->Rect(8, 79, 9, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(8, 79, 'stempel');
        $this->backend->Rect(8, 82, 22, 25, 'F', '', array(255, 255, 255));
        $this->backend->Line(8, 82, 8, 105, $line_thin);
        $this->backend->Line(8, 82, 30, 82, $line_thin);
        $this->backend->Line(30, 82, 30, 105, $line_thin);
        $this->backend->Line(8, 105, 30, 105, $line_thin);
        $this->backend->SetLineStyle($line_dash);
        $this->backend->Circle(19, 93, 8);

        /* payment */
        $this->backend->Rect(34, 79, 9, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(34, 79, 'opłata');
        $this->backend->Rect(34, 82, 26, 25, 'F', '', array(255, 255, 255));
        $this->backend->Line(34, 82, 34, 105, $line_thin);
        $this->backend->Line(34, 82, 60, 82, $line_thin);
        $this->backend->Line(60, 82, 60, 105, $line_thin);
        $this->backend->Line(34, 105, 60, 105, $line_thin);
    }

    protected function transferform_main_form_draw()
    {
        /* set line styles */
        $line_thin = array('width' => 0.15, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 0, 0));
        $line_bold = array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 0, 0));
        $line_dash = array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '3, 3', 'phase' => 10, 'color' => array(255, 0, 0));

        $this->backend->setColor('text', 255, 0, 0);
        $this->backend->SetFont(self::TCPDF_FONT, '', 8);
        $this->backend->setFontStretching(120);

        $this->backend->StartTransform();
        $this->backend->Rotate(90, 62, 82);

        $this->backend->Text(62, 82, 'Polecenie przelewu / wpłata gotówkowa');
        $this->backend->StopTransform();

        $this->backend->StartTransform();
        $this->backend->Rotate(90, 202, 75);
        $this->backend->Text(202, 75, 'odcinek dla banku zleceniodawcy');
        $this->backend->StopTransform();

        $this->backend->SetFont(self::TCPDF_FONT, '', 6);
        $this->backend->setFontStretching(100);

        /* draw main form */
        $this->backend->Rect(66, 2, 135, 88, 'F', '', array(245, 200, 200));

        /* division name */
        $this->backend->Rect(68, 3, 17, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(68, 3, 'nazwa odbiorcy');
        $this->backend->Rect(66.25, 6, 135, 5, 'F', '', array(255, 255, 255));
        $this->backend->Rect(68, 12, 20, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(68, 12, 'nazwa odbiorcy cd.');
        $this->backend->Rect(66.25, 15, 135, 5, 'F', '', array(255, 255, 255));

        /* account */
        $this->backend->Rect(66.3, 20.5, 134.5, 9, 'D', array('all' => $line_bold));
        $this->backend->Rect(67.5, 21, 22, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(68, 21, 'nr rachunku odbiorcy');
        $this->backend->Rect(67, 24, 128.5, 5, 'F', '', array(255, 255, 255));

        /* payment/transfer */
        for ($i = 0; $i < 2; $i++) {
            $this->backend->Rect(105 + ($i * 5.5), 33, 5, 5, 'DF', array('all' => $line_thin));
        }
        $this->backend->SetFont(self::TCPDF_FONT, '', 12);
        $this->backend->Text(104.5, 33, 'W');
        $this->backend->Text(110.5, 33, 'P');

        /* currency */
        $this->backend->SetFont(self::TCPDF_FONT, '', 6);
        $this->backend->Rect(121, 30, 10, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(121, 30, 'waluta');
        for ($i = 0; $i < 3; $i++) {
            $this->backend->Rect(120 + ($i * 4.5), 33, 4, 5, 'F', '', array(255, 255, 255));
        }

        /* amount */
        $this->backend->Rect(139.5, 29.5, 61.25, 9, 'D', array('all' => $line_bold));
        $this->backend->Rect(141, 30, 10, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(141, 30, 'kwota');
        $this->backend->Rect(140, 33, 60.25, 5, 'F', '', array(255, 255, 255));

        /* account/amount */
        $this->backend->Rect(68, 40, 60, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(68, 40, 'nr rachunku zleceniodawcy (przelew) / kwota słownie (wpłata)');
        for ($i = 0; $i < 26; $i++) {
            $this->backend->Rect(66 + ($i * 4.5), 43, 4.5, 5, 'DF', array('all' => $line_thin));
        }
        for ($i = 0; $i < 6; $i++) {
            $this->backend->Line(75 + ($i * 18), 48, 75 + ($i * 18), 46.5, $line_bold);
        }

        /* customer name */
        $this->backend->Rect(68, 50, 22, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(68, 50, 'nazwa zleceniodawcy');
        $this->backend->Rect(66.25, 53, 135, 5, 'F', '', array(255, 255, 255));
        $this->backend->Rect(68, 59, 25, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(68, 59, 'nazwa zleceniodawcy cd.');
        $this->backend->Rect(66.25, 62, 135, 5, 'F', '', array(255, 255, 255));

        /* title */
        $this->backend->Rect(68, 69, 11, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(68, 69, 'tytułem');
        $this->backend->Rect(66.25, 72, 135, 10, 'F', '', array(255, 255, 255));

        /* stamps */
        $this->backend->Rect(191, 83, 10, 6, 'F', '', array(255, 255, 255));
        $this->backend->Line(201, 80, 201, 90, $line_thin);
        $this->backend->Rect(66, 2, 135, 80, 'D', array('all' => $line_thin));
        $this->backend->Rect(66, 83, 68, 20, 'DF', array('all' => $line_thin));
        $this->backend->StartTransform();
        $this->backend->Translate(0, 23);
        $this->backend->Text(80, 75, 'pieczęć, data i podpis(y) zleceniodawcy');
        $this->backend->StopTransform();
        $this->backend->Line(134, 90, 201, 90, $line);
        $this->backend->Rect(155, 83, 20, 20, 'DF', array('all' => $line_thin));
        $this->backend->SetLineStyle($line_dash);
        $this->backend->Circle(165, 93, 8);
        for ($i = 0; $i < 4; $i++) {
            $this->backend->Rect(135.5 + ($i * 4.5), 95, 4.5, 4.5, 'DF', array('all' => $line_thin));
        }
        $this->backend->Line(144.5, 95, 144.5, 99.5, $line_bold);
        $this->backend->StartTransform();
        $this->backend->Translate(0, 16);
        $this->backend->Text(135, 75, 'opłata');
        $this->backend->StopTransform();
    }

    protected function transferform_simple_form_fill()
    {
        /* set font style & color */
        if (mb_strlen($this->data['division_shortname']) > 25) {
            $this->backend->SetFont(self::TCPDF_FONT, '', floor(235 / mb_strlen($this->data['division_shortname'])));
        } else {
            $this->backend->SetFont(self::TCPDF_FONT, '', 9);
        }
        $this->backend->setColor('text', 0, 0, 0);

        /* division name */
        $this->backend->Text(7, 7, $this->data['division_shortname']);
        $this->backend->Text(7, 13, $this->data['division_address']);
        $this->backend->Text(7, 19, $this->data['division_zip'] . ' ' . $this->data['division_city']);

        /* account */
        $this->backend->Text(7, 29, $this->data['account']);

        /* customer name */
        $this->backend->SetFont(self::TCPDF_FONT, '', 9);
        /* if customer name lenght > 26 chars then cut string */
        if (mb_strlen($this->data['name']) > 26) {
            $this->backend->Text(7, 38, mb_substr($this->data['name'], 0, 26));
        } else {
            $this->backend->Text(7, 38, $this->data['name']);
        }
        $this->backend->Text(7, 44, $this->data['address']);
        $this->backend->Text(7, 50, $this->data['zip'] . ' ' . $this->data['city']);

        /* title */
        $this->backend->MultiCell(50, 10, $this->data['title'], 0, 'L', false, 1, 7, 59, true, 0, false, true, 10, 'M');

        /* amount */
        $this->backend->SetFont(self::TCPDF_FONT, 'B', 10);
        $this->backend->Text(7, 73, moneyf($this->data['value'], $this->data['currency']));
    }

    protected function transferform_main_form_fill()
    {
        /* set font style & color */
        $this->backend->SetFont(self::TCPDF_FONT, '', 9);
        $this->backend->setColor('text', 0, 0, 0);

        /* division name */
        $this->backend->Text(67, 7, $this->data['division_name']);
        $this->backend->Text(67, 16, $this->data['division_address'] . ', ' . $this->data['division_zip'] . ' ' . $this->data['division_city']);

        /* account */
        $this->backend->SetFont(self::TCPDF_FONT, 'B', 9);
        $this->backend->Text(67, 25, format_bankaccount($this->data['account'], isset($this->data['export']) ? $this->data['export'] : false));

        /* currency */
        $this->backend->SetFont(self::TCPDF_FONT, 'B', 10);
        $this->backend->setFontSpacing(2.2);
        $this->backend->Text(120, 33.5, $this->data['currency']);
        $this->backend->setFontSpacing(0);

        /* amount */
        $this->backend->Text(142, 34, moneyf($this->data['value'], $this->data['currency']));
        $this->backend->Text(67, 43, moneyf_in_words($this->data['value'], $this->data['currency']));

        /* customer name */
        $this->backend->SetFont(self::TCPDF_FONT, '', 9);
        /* if customer name lenght > 70 chars then stretch font */
        if (mb_strlen($this->data['name']) > 70) {
            $this->backend->setFontStretching(85);
        }
        $this->backend->Text(67, 53.5, $this->data['name']);
        $this->backend->setFontStretching(100);
        $this->backend->Text(67, 62.5, $this->data['address'] . ', ' . $this->data['zip'] . ' ' . $this->data['city']);

        /* barcode */
        if (!empty($this->data['barcode'])) {
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
                'text' => false,
            );
            $this->backend->StartTransform();
            $this->backend->TranslateX(55);
            $this->backend->write1DBarcode($this->data['barcode'], 'C128', '', 73, 60, 8, 0.3, $style, '');
            $this->backend->StopTransform();
        }

        /* deadline */
        $paytype = $this->data['paytype'];
        if ($paytype != 8) {
            $this->backend->SetFont(self::TCPDF_FONT, '', 8);
            if ($paytype != 8) {
                $this->backend->MultiCell(135 - 70, 10, trans('Deadline:') . ' ' . date("d.m.Y", $this->data['pdate']) . ' r.', 0, 'R', false, 1, 66.25 + 68.5, 69, true, 0, false, true, 10, 'M');
            }
        }

        /* title */
        $this->backend->SetFont(self::TCPDF_FONT, 'B', 9);
        $cell_height_ratio = $this->backend->getCellHeightRatio();
        $this->backend->setCellHeightRatio(0.9);
        $this->backend->MultiCell(135 - 70, 10, $this->data['title'], 0, 'R', false, 1, 66.25 + 68.5, 73.5, true, 0, false, true, 10, 'M');
        $this->backend->setCellHeightRatio($cell_height_ratio);
    }

    public function GetCommonData($data)
    {
        $LMS = LMS::getInstance();

        $customerinfo = $LMS->GetCustomer($data['customerid']);
        $divisionid = $LMS->GetCustomerDivision($data['customerid']);
        $division = $LMS->GetDivision($divisionid);

        $this->data['customerinfo'] = $customerinfo;
        $this->data['$division'] = $division;

        // division data
        $this->data['division_name'] = $division['name'];
        $this->data['division_shortname'] = $division['shortname'];
        $this->data['division_address'] = $division['address'];
        $this->data['division_zip'] = $division['zip'];
        $this->data['division_city'] = $division['city'];

        // customer data
        $customerinfo['accounts'] = array_filter($customerinfo['accounts'], function ($account) {
            return ($account['type'] & CONTACT_INVOICES) > 0;
        });
        if (!empty($customerinfo['accounts'])) {
            $account = $customerinfo['accounts'][0]['account'];
        } else {
            $account = bankaccount($data['customerid'], $customerinfo['account'], isset($data['export']) ? $data['export'] : false);
        }
        $this->data['account'] = $account;
        $this->data['name'] = $customerinfo['customername'];
        $this->data['address'] = $customerinfo['address'];
        $this->data['zip'] = $customerinfo['zip'];
        $this->data['city'] = $customerinfo['city'];

        //default values for custom data
        $this->data['title'] = trans('Payment for liabilities');
        $this->data['value'] = 0;
        $this->data['currency'] = 'PLN';
        $this->data['paytype'] = 2;
        $this->data['pdate'] = time();
        $this->data['barcode'] = $data['customerid'];

        return $this->data;
    }

    public function SetCustomData($common_data, $custom_data)
    {
        return array_merge($common_data, $custom_data);
    }

    public function Draw($data, $translateX = 0, $translateY = 0, $scale = 100, $scaleX = 0, $scaleY = 0)
    {
        parent::Draw($data);
        $this->backend->StartTransform();
        $this->backend->ScaleXY($scale, $scaleX, $scaleY);
        $this->backend->Translate($translateX, $translateY);
        $this->transferform_simple_form_draw();
        $this->transferform_main_form_draw();
        $this->transferform_simple_form_fill();
        $this->transferform_main_form_fill();
        $this->backend->StopTransform();
    }
}

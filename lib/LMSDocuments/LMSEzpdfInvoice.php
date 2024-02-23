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

// brzydkie hacki dla ezpdf
@setlocale(LC_NUMERIC, 'C');

class LMSEzpdfInvoice extends LMSInvoice
{
    public const HEADER_IMAGE_HEIGHT = 40;

    private $use_alert_color;

    public function __construct($title, $pagesize = 'A4', $orientation = 'portrait')
    {
        parent::__construct('LMSEzpdfBackend', $title, $pagesize, $orientation);

        $this->use_alert_color = ConfigHelper::checkConfig('invoices.use_alert_color');

        list ($margin_top, $margin_right, $margin_bottom, $margin_left) =
            explode(',', ConfigHelper::getConfig('invoices.ezpdf_margins', '40,30,40,30'));
        $this->backend->ezSetMargins(trim($margin_top), trim($margin_bottom), trim($margin_left), trim($margin_right));
    }

    protected function invoice_simple_form_fill($x, $y, $scale)
    {
        $this->backend->setlinestyle(1);

        $this->backend->line(7*$scale+$x, 724*$scale+$y, 7*$scale+$x, 694*$scale+$y);
        $this->backend->line(7*$scale+$x, 724*$scale+$y, 37*$scale+$x, 724*$scale+$y);
        $this->backend->line(370*$scale+$x, 724*$scale+$y, 370*$scale+$x, 694*$scale+$y);
        $this->backend->line(370*$scale+$x, 724*$scale+$y, 340*$scale+$x, 724*$scale+$y);
        $this->backend->line(7*$scale+$x, 197*$scale+$y, 7*$scale+$x, 227*$scale+$y);
        $this->backend->line(7*$scale+$x, 197*$scale+$y, 37*$scale+$x, 197*$scale+$y);
        $this->backend->line(370*$scale+$x, 197*$scale+$y, 370*$scale+$x, 227*$scale+$y);
        $this->backend->line(370*$scale+$x, 197*$scale+$y, 340*$scale+$x, 197*$scale+$y);

        $shortname = $this->data['division_shortname'];
        $address = $this->data['division_address'];
        $zip = $this->data['division_zip'];
        $city = $this->data['division_city'];
        if (count($this->data['bankaccounts']) == 1) {
            $account = $this->data['bankaccounts'][0];
        } else {
            $account = bankaccount($this->data['customerid'], $this->data['account'], $this->data['export']);
        }

        $this->backend->text_autosize(15*$scale+$x, 568*$scale+$y, 30*$scale, $shortname, 350*$scale);
        $this->backend->text_autosize(15*$scale+$x, 534*$scale+$y, 30*$scale, $address, 350*$scale);
        $this->backend->text_autosize(15*$scale+$x, 500*$scale+$y, 30*$scale, $zip.' '.$city, 350*$scale);

        //$this->backend->text_autosize(15*$scale+$x,683*$scale+$y,30*$scale, substr($tmp,0,17),350*$scale);
        //$this->backend->text_autosize(15*$scale+$x,626*$scale+$y,30*$scale, substr($tmp,18,200),350*$scale);
        $this->backend->text_autosize(15*$scale+$x, 683*$scale+$y, 30*$scale, format_bankaccount($account, $this->data['export']), 350*$scale);
        if (ConfigHelper::checkConfig('invoices.customer_balance_in_form')) {
            $value = ($this->data['customerbalance'] / $this->data['currencyvalue']) * -1;
        } else {
            $value = $this->data['total'];
        }
        $this->backend->text_autosize(15*$scale+$x, 445*$scale+$y, 30*$scale, '*' . moneyf($value, $this->data['currency']) . '*', 350*$scale);

        $this->backend->text_autosize(15*$scale+$x, 390*$scale+$y, 30*$scale, $this->data['name'], 350*$scale);
        $this->backend->text_autosize(15*$scale+$x, 356*$scale+$y, 30*$scale, $this->data['address'], 350*$scale);
        $this->backend->text_autosize(15*$scale+$x, 322*$scale+$y, 30*$scale, $this->data['zip'].' '.$this->data['city'], 350*$scale);

        if (ConfigHelper::checkConfig('invoices.customer_balance_in_form')) {
            $this->backend->text_autosize(15*$scale+$x, 215*$scale+$y, 30*$scale, trans('Payment for liabilities'), 350*$scale);
        } else {
            $tmp = docnumber(array(
                'number' => $this->data['number'],
                'template' => $this->data['template'],
                'cdate' => $this->data['cdate'],
                'customerid' => $this->data['customerid'],
            ));
            if ($this->data['doctype'] == DOC_INVOICE_PRO) {
                $this->backend->text_autosize(15*$scale+$x, 215*$scale+$y, 30*$scale, trans('Payment for pro forma invoice No. $a', $tmp), 350*$scale);
            } else {
                $this->backend->text_autosize(15*$scale+$x, 215*$scale+$y, 30*$scale, trans('Payment for invoice No. $a', $tmp), 350*$scale);
            }
        }
    }

    protected function invoice_main_form_fill($x, $y, $scale)
    {
        $this->backend->setlinestyle(1);

        $this->backend->line(7*$scale+$x, 724*$scale+$y, 7*$scale+$x, 694*$scale+$y);
        $this->backend->line(7*$scale+$x, 724*$scale+$y, 37*$scale+$x, 724*$scale+$y);
        $this->backend->line(970*$scale+$x, 724*$scale+$y, 970*$scale+$x, 694*$scale+$y);
        $this->backend->line(970*$scale+$x, 724*$scale+$y, 940*$scale+$x, 724*$scale+$y);
        $this->backend->line(7*$scale+$x, 172*$scale+$y, 7*$scale+$x, 202*$scale+$y);
        $this->backend->line(7*$scale+$x, 172*$scale+$y, 37*$scale+$x, 172*$scale+$y);

        $name = $this->data['division_name'];
        $address = $this->data['division_address'];
        $zip = $this->data['division_zip'];
        $city = $this->data['division_city'];
        if (count($this->data['bankaccounts']) == 1) {
            $account = $this->data['bankaccounts'][0];
        } else {
            $account = bankaccount($this->data['customerid'], $this->data['account'], $this->data['export']);
        }

        $this->backend->text_autosize(15*$scale+$x, 680*$scale+$y, 30*$scale, $name, 950*$scale);
        $this->backend->text_autosize(15*$scale+$x, 617*$scale+$y, 30*$scale, $address." ".$zip." ".$city, 950*$scale);
        $this->backend->text_autosize(15*$scale+$x, 555*$scale+$y, 30*$scale, format_bankaccount($account, $this->data['export']), 950*$scale);
        $this->backend->addtext(330*$scale+$x, 495*$scale+$y, 30*$scale, 'X');
        $this->backend->text_autosize(550*$scale+$x, 495*$scale+$y, 30*$scale, "*".number_format($this->data['total'], 2, ',', '')."*", 400*$scale);
        if (ConfigHelper::checkConfig('invoices.customer_balance_in_form')) {
            $value = ($this->data['customerbalance'] / $this->data['currencyvalue']) * -1;
        } else {
            $value = $this->data['total'];
        }
        $this->backend->text_autosize(
            15*$scale+$x,
            434*$scale+$y,
            30*$scale,
            moneyf_in_words($value, $this->data['currency']),
            950*$scale
        );
        $this->backend->text_autosize(15*$scale+$x, 372*$scale+$y, 30*$scale, $this->data['name'], 950*$scale);
        $this->backend->text_autosize(15*$scale+$x, 312*$scale+$y, 30*$scale, $this->data['address']." ".$this->data['zip']." ".$this->data['city'], 950*$scale);

        $payment_docnumber = docnumber(array(
            'number' => $this->data['number'],
            'template' => $this->data['template'],
            'cdate' => $this->data['cdate'],
            'customerid' => $this->data['customerid'],
        ));

        $payment_title = ConfigHelper::getConfig('invoices.payment_title', null, true);
        if (empty($payment_title)) {
            if (ConfigHelper::checkConfig('invoices.customer_balance_in_form')) {
                $payment_title = trans('Payment for liabilities');
            } else {
                if ($this->data['doctype'] == DOC_INVOICE_PRO) {
                    $payment_title = trans('Payment for pro forma invoice No. $a', $payment_docnumber);
                } else {
                    $payment_title = trans('Payment for invoice No. $a', $payment_docnumber);
                }
            }
        } else {
            $customerid = $this->data['customerid'];
            $payment_title = str_replace(
                array(
                    '%number',
                ),
                array(
                    $payment_docnumber,
                ),
                preg_replace_callback(
                    '/%(\\d*)cid/',
                    function ($m) use ($customerid) {
                        return sprintf('%0' . $m[1] . 'd', $customerid);
                    },
                    $payment_title
                )
            );
        }
        $this->backend->text_autosize(15 * $scale + $x, 250 * $scale + $y, 30 * $scale, $payment_title, 950 * $scale);
    }

    protected function invoice_jpk_flags()
    {
        global $DOC_FLAGS;
        $flags = array();

        if (!empty($this->data['splitpayment'])) {
            $flags[] = $DOC_FLAGS[DOC_FLAG_SPLIT_PAYMENT];
        }

        foreach ($this->data['flags'] as $flag => $value) {
            if (($flag & DOC_FLAG_TELECOM_SERVICE) && $this->data['cdate'] >= mktime(0, 0, 0, 7, 1, 2021)) {
                $flags[] = trans('WSTO_EE');
            } else {
                $flags[] = $DOC_FLAGS[$flag];
            }
        }

        if (!empty($this->data['taxcategories'])) {
            foreach ($this->data['taxcategories'] as $taxcategory) {
                $flags[] = sprintf('GTU_%02d', $taxcategory);
            }
        }

        if (!empty($flags)) {
            $this->backend->text_align_center(
                $this->backend->ez['pageWidth'] / 2,
                $this->backend->ez['pageHeight'] - 15,
                10,
                trans('JPK:') . ' <b>' . implode(', ', $flags) . '</b>'
            );
        }
    }


    protected function invoice_dates($x, $y)
    {
        global $PAYTYPES;

        $font_size = 12;
        $this->backend->text_align_right($x, $y, $font_size, trans('Settlement date:').' ');
        $y = $y - $this->backend->text_align_left($x, $y, $font_size, date("Y/m/d", $this->data['cdate']));
        if (!ConfigHelper::checkConfig('invoices.hide_sale_date')) {
            $this->backend->text_align_right($x, $y, $font_size, trans('Sale date:').' ');
            $y = $y - $this->backend->text_align_left($x, $y, $font_size, date("Y/m/d", $this->data['sdate']));
        }
        if ($PAYTYPES[$this->data['paytype']]['features'] & INVOICE_FEATURE_DEADLINE) {
            $this->backend->text_align_right(
                $x,
                $y,
                $font_size,
                ($this->use_alert_color ? '<c:color:255,0,0>' : '')
                . trans('Deadline:') . ' '
                . ($this->use_alert_color ? '</c:color>' : '')
            );
            $y = $y - $this->backend->text_align_left(
                $x,
                $y,
                $font_size,
                ($this->use_alert_color ? '<c:color:255,0,0>' : '')
                . date("Y/m/d", $this->data['pdate'])
                . ($this->use_alert_color ? '</c:color>' : '')
            );
        } else {
            $y = $y - $this->backend->text_align_left(
                $x,
                $y,
                $font_size,
                ($this->use_alert_color ? '<c:color:255,0,0>' : '')
                . trans('Payment Cleared')
                . ($this->use_alert_color ? '</c:color>' : '')
            );
        }
        if (!ConfigHelper::checkConfig('invoices.hide_payment_type')) {
            $this->backend->text_align_right($x, $y, $font_size, trans('Payment type:').' ');
            $y = $y - $this->backend->text_align_left($x, $y, $font_size, trans($this->data['paytypename']));
            if (!empty($this->data['splitpayment'])) {
                $this->backend->text_align_right($x + 50, $y, $font_size, trans('(split payment)'));
            }
            if (!empty($this->data['flags'][DOC_FLAG_RECEIPT])) {
                $this->backend->text_align_right($x + 50, $y, $font_size, trans('<!invoice>(receipt)'));
            }
        }
        return $y;
    }

    protected function invoice_buyer($x, $y)
    {
        $font_size=10;
        $y=$y-$this->backend->text_align_left($x, $y, $font_size, '<b>' . trans('Purchaser:') . '</b>');
        $y=$this->backend->text_wrap($x, $y, 350, $font_size, $this->data['name'], 'left');
        $y=$y-$this->backend->text_align_left($x, $y, $font_size, $this->data['address']);
        $y=$y-$this->backend->text_align_left($x, $y, $font_size, $this->data['zip']." ".$this->data['city']);
        if ($this->data['export']) {
            $y=$y-$this->backend->text_align_left($x, $y, $font_size, trans($this->data['country']));
        }
        if ($this->data['ten']) {
            $currentSystemLanguage = Localisation::getCurrentSystemLanguage();
            Localisation::setSystemLanguage($this->data['lang']);
            $y = $y - $this->backend->text_align_left($x, $y, $font_size, trans('TEN') . ' ' . format_ten($this->data['ten'], $this->data['export']));
            Localisation::setSystemLanguage($currentSystemLanguage);
        } else if (!ConfigHelper::checkConfig('invoices.hide_ssn', true) && $this->data['ssn']) {
            $y=$y-$this->backend->text_align_left($x, $y, $font_size, trans('SSN').' '.$this->data['ssn']);
        }
        $y=$y-$this->backend->text_align_left($x, $y, $font_size, '<b>' . trans('Customer No.: $a', sprintf('%04d', $this->data['customerid'])) . '</b>');
        return $y;
    }

    protected function invoice_seller($x, $y)
    {
        $font_size = 10;
        $y = $y - $this->backend->text_align_left($x, $y, $font_size, '<b>' . trans('Seller:') . '</b>');
        $tmp = str_replace('%ten%', format_ten($this->data['division_ten'], $this->data['export']), $this->data['division_header']);

        if (!ConfigHelper::checkConfig('invoices.show_only_alternative_accounts')
            || empty($this->data['bankccounts'])) {
            $accounts = array(bankaccount($this->data['customerid'], $this->data['account'], $this->data['export']));
        } else {
            $accounts = array();
        }
        if (ConfigHelper::checkConfig('invoices.show_all_accounts')
            || ConfigHelper::checkConfig('invoices.show_only_alternative_accounts')) {
            $accounts = array_merge($accounts, $this->data['bankaccounts']);
        }
        foreach ($accounts as &$account) {
            $account = format_bankaccount($account, $this->data['export']);
        }
        $account_text = ($this->use_alert_color ? '<c:color:255,0,0>' : '')
            .  implode("</c:color>\n<c:color:255,0,0>", $accounts)
            . ($this->use_alert_color ? '</c:color>' : '');
        $tmp = str_replace('%bankaccount', $account_text, $tmp);
        $tmp = str_replace('%bankname', $this->data['div_bank'], $tmp);

        if (ConfigHelper::checkConfig('invoices.customer_bankaccount', true)) {
            $tmp .= "\n" . trans('Bank account:') . "\n" . '<b>' . $account_text . '</b>';
        }

        $tmp = preg_split('/\r?\n/', $tmp);
        foreach ($tmp as $line) {
            $y = $y - $this->backend->text_align_left($x, $y, $font_size, $line);
        }

        return $y;
    }

    protected function invoice_title($x, $y)
    {
        $font_size = 16;
        $tmp = docnumber(array(
            'number' => $this->data['number'],
            'template' => $this->data['template'],
            'cdate' => $this->data['cdate'],
            'customerid' => $this->data['customerid'],
        ));

        if (isset($this->data['invoice']) && $this->data['doctype'] == DOC_CNOTE) {
            $y=$y - $this->backend->text_align_left($x, $y, $font_size, '<b>' . trans('Credit Note No. $a', $tmp) . '</b>');
        } elseif ($this->data['doctype'] == DOC_INVOICE_PRO) {
            $y=$y - $this->backend->text_align_left($x, $y, $font_size, '<b>' . trans('Pro Forma Invoice No. $a', $tmp) . '</b>');
        } else {
            $y=$y - $this->backend->text_align_left($x, $y, $font_size, '<b>' . trans('Invoice No. $a', $tmp) . '</b>');
        }

        if (isset($this->data['invoice']) && $this->data['doctype'] == DOC_CNOTE) {
            $font_size = 12;
            $y += 8;
            $tmp = docnumber(array(
                'number' => $this->data['invoice']['number'],
                'template' => $this->data['invoice']['template'],
                'cdate' => $this->data['invoice']['cdate'],
                'customerid' => $this->data['customerid'],
            ));
            $y = $y - $this->backend->text_align_left($x, $y, $font_size, '<b>' . trans('for Invoice No. $a', $tmp) . '</b>');

            //$y -= 5;
        }

        //$font_size = 16;
        //$y = $y - $this->backend->text_align_left($x, $y, $font_size, $this->data['type']);

        if ($this->data['type'] == DOC_ENTITY_DUPLICATE) {
            $font_size = 10;
            $y = $y - $this->backend->text_align_left($x, $y+4, $font_size, trans('DUPLICATE, draw-up date:') . ' '
                . date('Y/m/d', $this->data['duplicate-date'] ?: time()));
        }

        $y -= 5;

        if (isset($this->data['invoice']) && $this->data['doctype'] == DOC_CNOTE) {
            $y += 10;
        }
        return $y;
    }

    protected function invoice_recipient($x, $y)
    {
        $font_size = 10;

        $y -= $this->backend->text_align_left($x, $y, $font_size, '<b>' . trans('Recipient:') . '</b>');

        $rec_lines = document_address(array(
            'name' => $this->data['rec_name'],
            'address' => $this->data['rec_address'],
            'street' => $this->data['rec_street'],
            'zip' => $this->data['rec_zip'],
            'postoffice' => $this->data['rec_postoffice'],
            'city' => $this->data['rec_city'],
        ));

        foreach ($rec_lines as $line) {
            $y -= $this->backend->text_align_left($x, $y, $font_size, $line);
        }

        return $y;
    }

    protected function invoice_address_box($x, $y)
    {
        $font_size = 12;
/*
        $this->invoice_name = $this->data['name'];
        if (strlen($this->invoice_name)>25)
            $this->invoice_name = preg_replace('/(.{25})/',"$b<i>&gt;</i>\n",$this->invoice_name);
        $tmp = preg_split('/\r?\n/', $this->invoice_name);
        foreach ($tmp as $line)
            $y = $y - $this->backend->text_align_left($x,$y,$font_size,"<b>".$line."</b>");
*/

        if (ConfigHelper::checkConfig('invoices.post_address', true)) {
            if ($this->data['post_name'] || $this->data['post_address']) {
                $lines = document_address(array(
                    'name' => $this->data['post_name'] ?: $this->data['name'],
                    'address' => $this->data['post_address'],
                    'street' => $this->data['post_street'],
                    'zip' => $this->data['post_zip'],
                    'postoffice' => $this->data['post_postoffice'],
                    'city' => $this->data['post_city'],
                ));
                $i = 0;
                foreach ($lines as $line) {
                    if ($i) {
                        $y = $y - $this->backend->text_align_left($x, $y, $font_size, '<b>' . $line . '</b>');
                    } else {
                        $y = $this->backend->text_wrap($x, $y, 160, $font_size, '<b>' . $line . '</b>', 'left');
                    }
                }
            } else {
                $y = $this->backend->text_wrap($x, $y, 160, $font_size, '<b>' . $this->data['name'] . '</b>', 'left');
                $y = $y - $this->backend->text_align_left($x, $y, $font_size, '<b>' . $this->data['address'] . '</b>');
                $y = $y - $this->backend->text_align_left($x, $y, $font_size, '<b>' . $this->data['zip'] . " " . $this->data['city'] . '</b>');
            }
        }

        return $y;
    }

    protected function invoice_data_row($x, $y, $width, $font_size, $margin, $data, $t_width, $t_justify)
    {
        $fy = $y - $margin - $this->backend->GetFontHeight($font_size);
        $left = $x+$margin;
        $ny = $fy;
        $cols = count($data);
        for ($i = 1; $i <= $cols; $i++) {
            $ly = $this->backend->text_wrap($left+$margin, $fy, $t_width[$i]-2*$margin, $font_size, $data[$i], $t_justify[$i]);
            $left = $left + $t_width[$i]+2*$margin;
            if ($ly<$ny) {
                $ny=$ly;
            }
        }
        $left = $x;
        for ($i = 1; $i <= $cols; $i++) {
            $this->backend->line($left, $y, $left, $ny+$font_size/2);
            $left = $left + $t_width[$i]+2*$margin;
        }
        $this->backend->line($left, $y, $left, $ny+$font_size/2);
        $y = $ny + $font_size / 2;
        $this->backend->line($x, $y, $x+$width, $y);

        return $y;
    }

    protected function invoice_short_data_row($x, $y, $width, $font_size, $margin, $data, $t_width, $t_justify)
    {
        $fy = $y - $margin - $this->backend->GetFontHeight($font_size);
        $left = $x+$margin;
        $ny = $fy;
        $cols = count($data);
        for ($i = $cols-3; $i <= $cols; $i++) {
            $ly = $this->backend->text_wrap($left+$margin, $fy, $t_width[$i]-2*$margin, $font_size, $data[$i], $t_justify[$i]);
            $left = $left + $t_width[$i]+2*$margin;
            if ($ly<$ny) {
                $ny=$ly;
            }
        }
        $left = $x;
        for ($i = $cols-3; $i <= $cols; $i++) {
            $this->backend->line($left, $y, $left, $ny+$font_size/2);
            $left = $left + $t_width[$i]+2*$margin;
        }
        $this->backend->line($left, $y, $left, $ny+$font_size/2);
        $y = $ny + $font_size / 2;
        //$this->backend->line($x,$y,$x+$width,$y);
        $v = $cols - 3;
        $this->backend->line($x, $y, $x+$t_width[$v++]+$t_width[$v++]+$t_width[$v++]+$t_width[$v++]+8*$margin, $y);

        return $y;
    }

    protected function invoice_data($x, $y, $width, $font_size, $margin)
    {
        $hide_discount = ConfigHelper::checkConfig('invoices.hide_discount');
        $hide_prodid = ConfigHelper::checkConfig('invoices.hide_prodid');
        $show_tax_category = ConfigHelper::checkConfig('invoices.show_tax_category', true) && !empty($this->data['taxcategories']);

        $this->backend->setlinestyle(0.5);
        $this->backend->line($x, $y, $x + $width, $y);

        $v = 1;
        $t_data[$v++] = '<b>' . trans('No.') . '</b>';
        $t_data[$v++] = '<b>' . trans('Name of Product, Commodity or Service') . '</b>';
        if (!$hide_prodid) {
            $t_data[$v++] = '<b>' . trans('<!invoice>Product ID') . '</b>';
        }
        if ($show_tax_category) {
            $t_data[$v++] = '<b>' . trans('Tax Category') . '</b>';
        }
        $t_data[$v++] = '<b>' . trans('Unit') . '</b>';
        $t_data[$v++] = '<b>' . trans('Amount') . '</b>';
        if (!$hide_discount && (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount']))) {
            $t_data[$v++] = '<b>' . trans('Discount') . '</b>';
        }
        $t_data[$v++] = '<b>' . ($this->data['netflag'] ? trans('Unitary Net Value') : trans('Unitary gross value')) . '</b>';
        $t_data[$v++] = '<b>' . trans('Net Value') . '</b>';
        $t_data[$v++] = '<b>' . trans('Tax Rate') . '</b>';
        $t_data[$v++] = '<b>' . trans('Tax Value') . '</b>';
        $t_data[$v++] = '<b>' . trans('Gross Value') . '</b>';

        for ($i = 1; $i < $v; $i++) {
            $t_justify[$i] = "center";
        }
        for ($i = 1; $i < $v; $i++) {
            $t_width[$i] = $this->backend->getWrapTextWidth($font_size, $t_data[$i]) + 2 * $margin + 2;
        }

        // tutaj jeszcze trzeba będzie sprawdzić jaką szerokość mają pola w tabelce później
        if ($this->data['content']) {
            foreach ($this->data['content'] as $item) {
                $v = 2;
                $tt_width[$v++] = $this->backend->getTextWidth($font_size, $item['description']);
                if (!$hide_prodid) {
                    $tt_width[$v++] = $this->backend->getTextWidth($font_size, $item['prodid']);
                }
                if ($show_tax_category) {
                    $tt_width[$v++] = $this->backend->getTextWidth($font_size, sprintf('%02d', $item['taxcategory']));
                }
                $tt_width[$v++] = $this->backend->getTextWidth($font_size, $item['content']);
                $tt_width[$v++] = $this->backend->getTextWidth($font_size, (float)$item['count']);
                if (!$hide_discount) {
                    if (!empty($this->data['pdiscount'])) {
                        $tt_width[$v] = $this->backend->getTextWidth($font_size, Localisation::formatNumber($item['pdiscount']) . ' %');
                    }
                    if (!empty($this->data['vdiscount'])) {
                        $tmp_width = $this->backend->getTextWidth($font_size, Localisation::smartFormatNumber($item['vdiscount']));
                        if ($tmp_width > $tt_width[$v]) {
                            $tt_width[$v] = $tmp_width;
                        }
                    }
                    if (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount'])) {
                        $v++;
                    }
                }
                if ($this->data['netflag']) {
                    $tt_width[$v++] = $this->backend->getTextWidth($font_size, Localisation::smartFormatNumber($item['basevalue'])) + 6;
                } else {
                    $tt_width[$v++] = $this->backend->getTextWidth($font_size, Localisation::smartFormatNumber($item['value'])) + 6;
                }
                $tt_width[$v++] = $this->backend->getTextWidth($font_size, Localisation::formatNumber($item['totalbase'])) + 6;
                $tt_width[$v++] = $this->backend->getTextWidth($font_size, $item['taxlabel']) + 6;
                $tt_width[$v++] = $this->backend->getTextWidth($font_size, Localisation::formatNumber($item['totaltax'])) + 6;
                $tt_width[$v++] = $this->backend->getTextWidth($font_size, Localisation::formatNumber($item['total'])) + 6;
                for ($i = 2; $i < $v; $i++) {
                    if (($tt_width[$i] + 2 * $margin + 2) > $t_width[$i]) {
                        $t_width[$i] = $tt_width[$i] + 2 * $margin + 2;
                    }
                }
            }
        }

        if (isset($this->data['invoice']['content']) && $this->data['doctype'] == DOC_CNOTE) {
            foreach ($this->data['invoice']['content'] as $item) {
                $v = 2;
                $tt_width[$v++] = $this->backend->getTextWidth($font_size, $item['description']);
                if (!$hide_prodid) {
                    $tt_width[$v++] = $this->backend->getTextWidth($font_size, $item['prodid']);
                }
                if ($show_tax_category) {
                    $tt_width[$v++] = $this->backend->getTextWidth($font_size, sprintf('%02d', $item['taxcategory']));
                }
                $tt_width[$v++] = $this->backend->getTextWidth($font_size, $item['content']);
                $tt_width[$v++] = $this->backend->getTextWidth($font_size, (float)$item['count']);
                if (!$hide_discount) {
                    if (!empty($this->data['pdiscount'])) {
                        $tt_width[$v] = $this->backend->getTextWidth($font_size, Localisation::formatNumber($item['pdiscount']) . ' %');
                    }
                    if (!empty($this->data['vdiscount'])) {
                        $tmp_width = $this->backend->getTextWidth($font_size, Localisation::smartFormatNumber($item['vdiscount']));
                        if ($tmp_width > $tt_width[$v]) {
                            $tt_width[$v] = $tmp_width;
                        }
                    }
                    if (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount'])) {
                        $v++;
                    }
                }
                if ($this->data['invoice']['netflag']) {
                    $tt_width[$v++] = $this->backend->getTextWidth($font_size, Localisation::smartFormatNumber($item['basevalue'])) + 6;
                } else {
                    $tt_width[$v++] = $this->backend->getTextWidth($font_size, Localisation::smartFormatNumber($item['value'])) + 6;
                }
                $tt_width[$v++] = $this->backend->getTextWidth($font_size, Localisation::formatNumber($item['totalbase'])) + 6;
                $tt_width[$v++] = $this->backend->getTextWidth($font_size, $item['taxlabel']) + 6;
                $tt_width[$v++] = $this->backend->getTextWidth($font_size, Localisation::formatNumber($item['totaltax'])) + 6;
                $tt_width[$v++] = $this->backend->getTextWidth($font_size, Localisation::formatNumber($item['total'])) + 6;
                for ($i = 2; $i < $v; $i++) {
                    if (($tt_width[$i] + 2 * $margin + 2) > $t_width[$i]) {
                        $t_width[$i] = $tt_width[$i] + 2 * $margin + 2;
                    }
                }
            }
        }
        // Kolumna 2 będzie miała rozmiar ustalany dynamicznie
        $cols = array(1, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12);
        if ($hide_prodid) {
            array_pop($cols);
        }
        if (!$show_tax_category) {
            array_pop($cols);
        }
        if ($hide_discount || (empty($this->data['pdiscount']) && empty($this->data['vdiscount']))) {
            array_pop($cols);
        }
        $t_width[2] = $width;
        foreach ($cols as $col) {
            $t_width[2] -= $t_width[$col] + 2 * $margin;
        }

        $y = $this->invoice_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);
        $t_justify[11] = $t_justify[10] = $t_justify[9] = $t_justify[8] = $t_justify[7] = $t_justify[6] = $t_justify[5] = "right";
        $t_justify[2] = 'left';

        if (isset($this->data['invoice']) && $this->data['doctype'] == DOC_CNOTE) {
            // we have credit note, so first print corrected invoice data
            $xx = $x;
            $y = $y - $this->backend->text_align_left($x, $y - 10, $font_size, '<b>' . trans('Was:') . '</b>');
            $y -= 6;
            $this->backend->line($x, $y, $x + $width, $y);
            $lp = 1;
            if ($this->data['invoice']['content']) {
                foreach ($this->data['invoice']['content'] as $item) {
                    $v = 1;
                    $t_data[$v++] = $lp;
                    $t_data[$v++] = $item['description'];
                    if (!$hide_prodid) {
                        $t_data[$v++] = $item['prodid'];
                    }
                    if ($show_tax_category) {
                        $t_data[$v++] = empty($item['taxcategory']) ? '' : sprintf('%02d', $item['taxcategory']);
                    }
                    $t_data[$v++] = $item['content'];
                    $t_data[$v++] = (float)$item['count'];
                    if (!$hide_discount) {
                        $item['pdiscount'] = floatval($item['pdiscount']);
                        $item['vdiscount'] = floatval($item['vdiscount']);
                        if (!empty($item['pdiscount'])) {
                            $t_data[$v++] = Localisation::formatNumber($item['pdiscount']) . ' %';
                        } elseif (!empty($item['vdiscount'])) {
                            $t_data[$v++] = Localisation::smartFormatNumber($item['vdiscount']);
                        } elseif (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount'])) {
                            $t_data[$v++] = '';
                        }
                    }
                    if ($this->data['invoice']['netflag']) {
                        $t_data[$v++] = Localisation::smartFormatNumber($item['basevalue']);
                    } else {
                        $t_data[$v++] = Localisation::smartFormatNumber($item['value']);
                    }
                    $t_data[$v++] = Localisation::formatNumber($item['totalbase']);
                    $t_data[$v++] = $item['taxlabel'];
                    $t_data[$v++] = Localisation::formatNumber($item['totaltax']);
                    $t_data[$v++] = Localisation::formatNumber($item['total']);

                    $lp++;
                    $y = $this->invoice_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);
                }
            }

            $cols = array(1, 2, 3, 4, 5, 6, 7, 8);
            if ($hide_prodid) {
                array_pop($cols);
            }
            if (!$show_tax_category) {
                array_pop($cols);
            }
            if ($hide_discount || (empty($this->data['pdiscount']) && empty($this->data['vdiscount']))) {
                array_pop($cols);
            }

            $x += count($cols) * 2 * $margin;
            foreach ($cols as $col) {
                $x += $t_width[$col];
            }

            $fy = $y - $margin - $this->backend->GetFontHeight($font_size);
            $this->backend->text_align_right($x - $margin, $fy, $font_size, '<b>' . trans('Total:') . '</b>');

            $v = count($cols) + 1;
            $t_data[$v++] = Localisation::formatNumber($this->data['invoice']['totalbase']);
            $t_data[$v++] = "<b>x</b>";
            $t_data[$v++] = Localisation::formatNumber($this->data['invoice']['totaltax']);
            $t_data[$v++] = Localisation::formatNumber($this->data['invoice']['total']);

            $y = $this->invoice_short_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);
            $y -= 5;

            $fy = $y - $margin - $this->backend->GetFontHeight($font_size);
            $this->backend->text_align_right($x - $margin, $fy, $font_size, '<b>' . trans('in it:') . '</b>');
            $v = count($cols) + 1;
            $this->backend->line($x, $y, $x + $t_width[$v++] + $t_width[$v++] + $t_width[$v++] + $t_width[$v++] + 8 * $margin, $y);

            if ($this->data['invoice']['taxest']) {
                foreach ($this->data['invoice']['taxest'] as $item) {
                    $v = count($cols) + 1;
                    $t_data[$v++] = Localisation::formatNumber($item['base']);
                    $t_data[$v++] = $item['taxlabel'];
                    $t_data[$v++] = Localisation::formatNumber($item['tax']);
                    $t_data[$v++] = Localisation::formatNumber($item['total']);
                    $y = $this->invoice_short_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);
                }
            }

            $x = $xx;
            if ($this->data['reason'] != '') {
                $y = $y - $this->backend->text_align_left($x, $y - 10, $font_size, '<b>' . trans('Reason:') . ' ' . $this->data['reason'] . '</b>');
                $y -= 10;
            }
            $y = $y - $this->backend->text_align_left($x, $y - 10, $font_size, '<b>' . trans('Corrected to:') . '</b>');
            $y -= 5;
            $this->backend->line($x, $y, $x + $width, $y);
        }

        $lp = 1;
        if ($this->data['content']) {
            foreach ($this->data['content'] as $item) {
                $v = 1;
                $t_data[$v++] = $lp;
                $t_data[$v++] = $item['description'];
                if (!$hide_prodid) {
                    $t_data[$v++] = $item['prodid'];
                }
                if ($show_tax_category) {
                    $t_data[$v++] = empty($item['taxcategory']) ? '' : sprintf('%02d', $item['taxcategory']);
                }
                $t_data[$v++] = $item['content'];
                $t_data[$v++] = (float)$item['count'];
                if (!$hide_discount) {
                    $item['pdiscount'] = floatval($item['pdiscount']);
                    $item['vdiscount'] = floatval($item['vdiscount']);
                    if (!empty($item['pdiscount'])) {
                        $t_data[$v++] = Localisation::formatNumber($item['pdiscount']) . ' %';
                    } elseif (!empty($item['vdiscount'])) {
                        $t_data[$v++] = Localisation::smartFormatNumber($item['vdiscount']);
                    } elseif (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount'])) {
                        $t_data[$v++] = '';
                    }
                }
                if ($this->data['netflag']) {
                    $t_data[$v++] = Localisation::smartFormatNumber($item['basevalue']);
                } else {
                    $t_data[$v++] = Localisation::smartFormatNumber($item['value']);
                }
                $t_data[$v++] = Localisation::formatNumber($item['totalbase']);
                $t_data[$v++] = $item['taxlabel'];
                $t_data[$v++] = Localisation::formatNumber($item['totaltax']);
                $t_data[$v++] = Localisation::formatNumber($item['total']);

                $lp++;
                $y = $this->invoice_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);
            }
        }

        $return[1] = $y;

        $x += count($cols) * 2 * $margin;
        foreach ($cols as $col) {
            $x += $t_width[$col];
        }

        $fy = $y - $margin - $this->backend->GetFontHeight($font_size);
        $this->backend->text_align_right($x - $margin, $fy, $font_size, '<b>' . trans('Total:') . '</b>');

        $v = count($cols) + 1;
        $t_data[$v++] = Localisation::formatNumber($this->data['totalbase']);
        $t_data[$v++] = "<b>x</b>";
        $t_data[$v++] = Localisation::formatNumber($this->data['totaltax']);
        $t_data[$v++] = Localisation::formatNumber($this->data['total']);

        $y = $this->invoice_short_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);

        $y = $y - 5;

        $fy = $y - $margin - $this->backend->GetFontHeight($font_size);
        $this->backend->text_align_right($x - $margin, $fy, $font_size, '<b>' . trans('in it:') . '</b>');
        $v = count($cols) + 1;
        $this->backend->line($x, $y, $x + $t_width[$v++] + $t_width[$v++] + $t_width[$v++] + $t_width[$v++] + 8 * $margin, $y);

        if ($this->data['taxest']) {
            foreach ($this->data['taxest'] as $item) {
                $v = count($cols) + 1;
                $t_data[$v++] = Localisation::formatNumber($item['base']);
                $t_data[$v++] = $item['taxlabel'];
                $t_data[$v++] = Localisation::formatNumber($item['tax']);
                $t_data[$v++] = Localisation::formatNumber($item['total']);
                $y = $this->invoice_short_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);
            }
        }

        if (isset($this->data['invoice']) && $this->data['doctype'] == DOC_CNOTE) {
            $total = $this->data['total'] - $this->data['invoice']['total'];
            $totalbase = $this->data['totalbase'] - $this->data['invoice']['totalbase'];
            $totaltax = $this->data['totaltax'] - $this->data['invoice']['totaltax'];

            $y = $y - 5;
            $fy = $y - $margin - $this->backend->GetFontHeight($font_size);
            $v = count($cols) + 1;
            $this->backend->line($x, $y, $x + $t_width[$v++] + $t_width[$v++] + $t_width[$v++] + $t_width[$v++] + 8 * $margin, $y);
            $this->backend->text_align_right($x - $margin, $fy, $font_size, '<b>' . trans('Difference value:') . '</b>');

            $v = count($cols) + 1;
            $t_data[$v++] = ($totalbase > 0 ? '+' : '') . Localisation::formatNumber($totalbase);
            $t_data[$v++] = "<b>x</b>";
            $t_data[$v++] = ($totaltax > 0 ? '+' : '') . Localisation::formatNumber($totaltax);
            $t_data[$v++] = ($total > 0 ? '+' : '') . Localisation::formatNumber($total);

            $y = $this->invoice_short_data_row($x, $y, $width, $font_size, $margin, $t_data, $t_width, $t_justify);
        }

        $return[2] = $y;

        return $return;
    }

    protected function new_invoice_data($x, $y, $width, $font_size, $margin)
    {
        $hide_discount = ConfigHelper::checkConfig('invoices.hide_discount');
        $hide_prodid = ConfigHelper::checkConfig('invoices.hide_prodid');
        $show_tax_category = ConfigHelper::checkConfig('invoices.show_tax_category') && !empty($this->data['taxcategories']);

        $this->backend->setlinestyle(0.5);
        $data = array();
        $cols = array();
        $params = array(
            'fontSize' => $font_size,
            'xPos' => $x,
            'xOrientation' => 'right', // I think it should be left here (bug?)
            'rowGap' => 2,
            'colGap' => 2,
            'showHeadings' => 0,
            'cols' => array(),
        );

        // tabelka glowna
        $cols['no'] = '<b>' . trans('No.') . '</b>';
        $cols['name'] = '<b>' . trans('Name of Product, Commodity or Service') . '</b>';
        if (!$hide_prodid) {
            $cols['prodid'] = '<b>' . trans('<!invoice>Product ID') . '</b>';
        }
        if ($show_tax_category) {
            $cols['taxcategory'] = '<b>' . trans('Tax Category') . '</b>';
        }
        $cols['content'] = '<b>' . trans('Unit') . '</b>';
        $cols['count'] = '<b>' . trans('Amount') . '</b>';
        if (!$hide_discount && (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount']))) {
            $cols['discount'] = '<b>' . trans('Discount') . '</b>';
        }
        if ($show_tax_category) {
            $cols['taxcategory'] = '<b>' . trans('Tax Category') . '</b>';
        }
        $cols['basevalue'] = '<b>' . ($this->data['netflag'] ? trans('Unitary Net Value') : trans('Unitary gross value')) . '</b>';
        $cols['totalbase'] = '<b>' . trans('Net Value') . '</b>';
        $cols['taxlabel'] = '<b>' . trans('Tax Rate') . '</b>';
        $cols['totaltax'] = '<b>' . trans('Tax Value') . '</b>';
        $cols['total'] = '<b>' . trans('Gross Value') . '</b>';

        foreach ($cols as $name => $text) {
            $params['cols'][$name] = array(
                'justification' => 'center',
                'width' => $this->backend->getWrapTextWidth($font_size, $text) + 2 * $margin + 2,
            );
        }

        // tutaj jeszcze trzeba będzie sprawdzić jaką szerokość mają pola w tabelce później
        if ($this->data['content']) {
            foreach ($this->data['content'] as $item) {
                $tt_width['name'] = $this->backend->getTextWidth($font_size, $item['description']);
                if (!$hide_prodid) {
                    $tt_width['prodid'] = $this->backend->getTextWidth($font_size, $item['prodid']);
                }
                if ($show_tax_category) {
                    $tt_width['taxcategory'] = $this->backend->getTextWidth($font_size, sprintf('%02d', $item['taxcategory']));
                }
                $tt_width['content'] = $this->backend->getTextWidth($font_size, $item['content']);
                $tt_width['count'] = $this->backend->getTextWidth($font_size, (float)$item['count']);
                if (!$hide_discount) {
                    if (!empty($this->data['pdiscount'])) {
                        $tt_width['discount'] = $this->backend->getTextWidth($font_size, Localisation::formatNumber($item['pdiscount']) . ' %');
                    }
                    if (!empty($this->data['vdiscount'])) {
                        $tmp_width = $this->backend->getTextWidth($font_size, Localisation::smartFormatNumber($item['vdiscount']));
                        if (!isset($tt_width['discount']) || $tmp_width > $tt_width['discount']) {
                            $tt_width['discount'] = $tmp_width;
                        }
                    }
                }
                if ($this->data['netflag']) {
                    $tt_width['basevalue'] = $this->backend->getTextWidth($font_size, Localisation::smartFormatNumber($item['basevalue'])) + 6;
                } else {
                    $tt_width['basevalue'] = $this->backend->getTextWidth($font_size, Localisation::smartFormatNumber($item['value'])) + 6;
                }
                $tt_width['totalbase'] = $this->backend->getTextWidth($font_size, Localisation::formatNumber($item['totalbase'])) + 6;
                $tt_width['taxlabel'] = $this->backend->getTextWidth($font_size, $item['taxlabel']) + 6;
                $tt_width['totaltax'] = $this->backend->getTextWidth($font_size, Localisation::formatNumber($item['totaltax'])) + 6;
                $tt_width['total'] = $this->backend->getTextWidth($font_size, Localisation::formatNumber($item['total'])) + 6;

                foreach ($tt_width as $name => $w) {
                    if (($w + 2 * $margin + 2) > $params['cols'][$name]['width']) {
                        $params['cols'][$name]['width'] = $w + 2 * $margin + 2;
                    }
                }
            }
        }

        if (isset($this->data['invoice']['content']) && $this->data['doctype'] == DOC_CNOTE) {
            foreach ($this->data['invoice']['content'] as $item) {
                $tt_width['name'] = $this->backend->getTextWidth($font_size, $item['description']);
                if (!$hide_prodid) {
                    $tt_width['prodid'] = $this->backend->getTextWidth($font_size, $item['prodid']);
                }
                if ($show_tax_category) {
                    $tt_width['taxcategory'] = $this->backend->getTextWidth($font_size, sprintf('%02d', $item['taxcategory']));
                }
                $tt_width['content'] = $this->backend->getTextWidth($font_size, $item['content']);
                $tt_width['count'] = $this->backend->getTextWidth($font_size, (float)$item['count']);
                if (!$hide_discount) {
                    if (!empty($this->data['pdiscount'])) {
                        $tt_width['discount'] = $this->backend->getTextWidth($font_size, Localisation::formatNumber($item['pdiscount']) . ' %');
                    }
                    if (!empty($this->data['vdiscount'])) {
                        $tmp_width = $this->backend->getTextWidth($font_size, Localisation::smartFormatNumber($item['vdiscount']));
                        if ($tmp_width > $tt_width['discount']) {
                            $tt_width['discount'] = $tmp_width;
                        }
                    }
                }
                if ($this->data['invoice']['netflag']) {
                    $tt_width['basevalue'] = $this->backend->getTextWidth($font_size, Localisation::smartFormatNumber($item['basevalue'])) + 6;
                } else {
                    $tt_width['basevalue'] = $this->backend->getTextWidth($font_size, Localisation::smartFormatNumber($item['value'])) + 6;
                }
                $tt_width['totalbase'] = $this->backend->getTextWidth($font_size, Localisation::formatNumber($item['totalbase'])) + 6;
                $tt_width['taxlabel'] = $this->backend->getTextWidth($font_size, $item['taxlabel']) + 6;
                $tt_width['totaltax'] = $this->backend->getTextWidth($font_size, Localisation::formatNumber($item['totaltax'])) + 6;
                $tt_width['total'] = $this->backend->getTextWidth($font_size, Localisation::formatNumber($item['total'])) + 6;

                foreach ($tt_width as $name => $w) {
                    if (($w + 2 * $margin + 2) > $params['cols'][$name]['width']) {
                        $params['cols'][$name]['width'] = $w + 2 * $margin + 2;
                    }
                }
            }
        }

        // Kolumna 'name' bedzie miala rozmiar ustalany dynamicznie
        $sum = 0;
        foreach ($params['cols'] as $name => $col) {
            if ($name != 'name') {
                $sum += $col['width'];
            }
        }
        $params['cols']['name']['width'] = $width - $sum;

        // table header
        $this->backend->ezSetY($y);
        $data = array(0=>$cols);
        $y = $this->backend->ezTable($data, $cols, '', $params) - 2;
        $data = array();

        foreach ($cols as $name => $text) {
            switch ($name) {
                case 'no':
                case 'taxcategory':
                    $params['cols'][$name]['justification'] = 'center';
                    break;
                case 'name':
                    $params['cols'][$name]['justification'] = 'left';
                    break;
                default:
                    $params['cols'][$name]['justification'] = 'right';
                    break;
            }
        }

        // size of taxes summary table
        $xx = $x;
        foreach ($params['cols'] as $name => $value) {
            if (in_array($name, array('no', 'name', 'prodid', 'taxcategory', 'content', 'count', 'discount', 'basevalue'))) {
                $xx += $params['cols'][$name]['width'];
            } else {
                $cols2[$name] = $params['cols'][$name];
            }
        }

        $data2 = array();
        $params2 = array(
            'fontSize' => $font_size,
            'xPos' => $xx,
            'xOrientation' => 'right',
            'rowGap' => 2,
            'colGap' => 2,
            'showHeadings' => 0,
            'cols' => $cols2,
        );

        if (isset($this->data['invoice']) && $this->data['doctype'] == DOC_CNOTE) {
            // we have credit note, so first print corrected invoice data

            $y -= 20;
            $this->backend->check_page_length($y);
            $y = $y - $this->backend->text_align_left($x, $y, $font_size, '<b>' . trans('Was:') . '</b>');

            $i = 0;
            if ($this->data['invoice']['content']) {
                foreach ($this->data['invoice']['content'] as $item) {
                    $data[$i]['no'] = $i + 1;
                    $data[$i]['name'] = $item['description'];
                    if (!$hide_prodid) {
                        $data[$i]['prodid'] = $item['prodid'];
                    }
                    if ($show_tax_category) {
                        $data[$i]['taxcategory'] = empty($item['taxcategory']) ? '' : sprintf('%02d', $item['taxcategory']);
                    }
                    $data[$i]['content'] = $item['content'];
                    $data[$i]['count'] = (float)$item['count'];
                    if (!$hide_discount) {
                        $item['pdiscount'] = floatval($item['pdiscount']);
                        $item['vdiscount'] = floatval($item['vdiscount']);
                        if (!empty($item['pdiscount'])) {
                            $data[$i]['discount'] = Localisation::formatNumber($item['pdiscount']) . ' %';
                        } elseif (!empty($item['vdiscount'])) {
                            $data[$i]['discount'] = Localisation::smartFormatNumber($item['vdiscount']);
                        }
                    }
                    if ($this->data['invoice']['netflag']) {
                        $data[$i]['basevalue'] = Localisation::smartFormatNumber($item['basevalue']);
                    } else {
                        $data[$i]['basevalue'] = Localisation::smartFormatNumber($item['value']);
                    }
                    $data[$i]['totalbase'] = Localisation::formatNumber($item['totalbase']);
                    $data[$i]['taxlabel'] = $item['taxlabel'];
                    $data[$i]['totaltax'] = Localisation::formatNumber($item['totaltax']);
                    $data[$i]['total'] = Localisation::formatNumber($item['total']);

                    $i++;
                }
            }

            $this->backend->ezSetY($y);
            $y = $this->backend->ezTable($data, $cols, '', $params);
            $data = array();

            $y -= 10;
            $this->backend->check_page_length($y);

            $data2[0]['totalbase'] = Localisation::formatNumber($this->data['invoice']['totalbase']);
            $data2[0]['taxlabel'] = "<b>x</b>";
            $data2[0]['totaltax'] = Localisation::formatNumber($this->data['invoice']['totaltax']);
            $data2[0]['total'] = Localisation::formatNumber($this->data['invoice']['total']);

            $this->backend->ezSetY($y);
            $y = $this->backend->ezTable($data2, null, '', $params2) - 2;
            $data2 = array();

            $fy = $y + $this->backend->GetFontHeight($font_size) / 2;
            $this->backend->text_align_right($xx - 5, $fy, $font_size, '<b>' . trans('Total:') . '</b>');

            $this->backend->check_page_length($y);
            $fy = $y - $margin - $this->backend->GetFontHeight($font_size);
            $this->backend->text_align_right($xx - 5, $fy, $font_size, '<b>' . trans('in it:') . '</b>');

            if ($this->data['invoice']['taxest']) {
                $i = 0;
                foreach ($this->data['invoice']['taxest'] as $item) {
                    $data2[$i]['totalbase'] = Localisation::formatNumber($item['base']);
                    $data2[$i]['taxlabel'] = $item['taxlabel'];
                    $data2[$i]['totaltax'] = Localisation::formatNumber($item['tax']);
                    $data2[$i]['total'] = Localisation::formatNumber($item['total']);
                    $i++;
                }
                //$this->backend->ezSetY($y);
                $this->backend->ezSetY($y + 3);
                $y = $this->backend->ezTable($data2, null, '', $params2);
                $data2 = array();
            }

            $y -= 20;
            if ($this->data['reason'] != '') {
                $this->backend->check_page_length($y);
                $y = $this->backend->text_wrap($x, $y, $width, $font_size, '<b>' . trans('Reason:') . ' ' . $this->data['reason'] . '</b>', 'left');
                $y -= 10;
            }
            $this->backend->check_page_length($y);
            $y = $y - $this->backend->text_align_left($x, $y, $font_size, '<b>' . trans('Corrected to:') . '</b>');
        }

        // pozycje faktury
        $i = 0;
        if (isset($this->data['content'])) {
            foreach ($this->data['content'] as $item) {
                $data[$i]['no'] = $i + 1;
                $data[$i]['name'] = $item['description'];
                if (!$hide_prodid) {
                    $data[$i]['prodid'] = $item['prodid'];
                }
                if ($show_tax_category) {
                    $data[$i]['taxcategory'] = empty($item['taxcategory']) ? '' : sprintf('%02d', $item['taxcategory']);
                }
                $data[$i]['content'] = $item['content'];
                $data[$i]['count'] = (float)$item['count'];
                if (!$hide_discount) {
                    $item['pdiscount'] = floatval($item['pdiscount']);
                    $item['vdiscount'] = floatval($item['vdiscount']);
                    if (!empty($item['pdiscount'])) {
                        $data[$i]['discount'] = Localisation::formatNumber($item['pdiscount']) . ' %';
                    } elseif (!empty($item['vdiscount'])) {
                        $data[$i]['discount'] = Localisation::smartFormatNumber($item['vdiscount']);
                    }
                }
                if ($this->data['netflag']) {
                    $data[$i]['basevalue'] = Localisation::smartFormatNumber($item['basevalue']);
                } else {
                    $data[$i]['basevalue'] = Localisation::smartFormatNumber($item['value']);
                }
                $data[$i]['totalbase'] = Localisation::formatNumber($item['totalbase']);
                $data[$i]['taxlabel'] = $item['taxlabel'];
                $data[$i]['totaltax'] = Localisation::formatNumber($item['totaltax']);
                $data[$i]['total'] = Localisation::formatNumber($item['total']);

                $i++;
            }
        }

        //$this->backend->ezSetY($y);
        $this->backend->ezSetY($y + 3);
        $y = $this->backend->ezTable($data, $cols, '', $params);

        $y -= 10;
        $this->backend->check_page_length($y);

        // podsumowanie podatku
        $data2[0]['totalbase'] = Localisation::formatNumber($this->data['totalbase']);
        $data2[0]['taxlabel'] = "<b>x</b>";
        $data2[0]['totaltax'] = Localisation::formatNumber($this->data['totaltax']);
        $data2[0]['total'] = Localisation::formatNumber($this->data['total']);

        $this->backend->ezSetY($y);
        $y = $this->backend->ezTable($data2, null, '', $params2) - 2;
        $data2 = array();

        $fy = $y + $this->backend->GetFontHeight($font_size) / 2;
        $this->backend->text_align_right($xx - 5, $fy, $font_size, '<b>' . trans('Total:') . '</b>');

        $return[1] = $y;

        $this->backend->check_page_length($y);
        $fy = $y - $margin - $this->backend->GetFontHeight($font_size);
        $this->backend->text_align_right($xx - 5, $fy, $font_size, '<b>' . trans('in it:') . '</b>');

        if (isset($this->data['taxest'])) {
            $i = 0;
            foreach ($this->data['taxest'] as $item) {
                $data2[$i]['totalbase'] = Localisation::formatNumber($item['base']);
                $data2[$i]['taxlabel'] = $item['taxlabel'];
                $data2[$i]['totaltax'] = Localisation::formatNumber($item['tax']);
                $data2[$i]['total'] = Localisation::formatNumber($item['total']);
                $i++;
            }
            //$this->backend->ezSetY($y);
            $this->backend->ezSetY($y + 3);
            $y = $this->backend->ezTable($data2, null, '', $params2);
            $data2 = array();
        }

        if (isset($this->data['invoice']) && $this->data['doctype'] == DOC_CNOTE) {
            $total = $this->data['total'] - $this->data['invoice']['total'];
            $totalbase = $this->data['totalbase'] - $this->data['invoice']['totalbase'];
            $totaltax = $this->data['totaltax'] - $this->data['invoice']['totaltax'];

            $y -= 10;
            $fy = $y - $margin - $this->backend->GetFontHeight($font_size);
            $this->backend->text_align_right($xx - 5, $fy, $font_size, '<b>' . trans('Difference value:') . '</b>');

            $data2[0]['totalbase'] = ($totalbase>0 ? '+' : '') . Localisation::formatNumber($totalbase);
            $data2[0]['taxlabel'] = "<b>x</b>";
            $data2[0]['totaltax'] = ($totaltax>0 ? '+' : '') . Localisation::formatNumber($totaltax);
            $data2[0]['total'] = ($total>0 ? '+' : '') . Localisation::formatNumber($total);

            $this->backend->ezSetY($y);
            $y = $this->backend->ezTable($data2, null, '', $params2);
            $data2 = array();
        }

        $return[2] = $y;

        return $return;
    }

    protected function invoice_to_pay($x, $y)
    {
        global $PAYTYPES;

        $show_balance_summary = ConfigHelper::checkConfig('invoices.show_balance_summary');

        if (isset($this->data['rebate'])) {
            $y = $y - $this->backend->text_align_left(
                $x,
                $y,
                $show_balance_summary ? 10 : 14,
                trans(
                    $this->data['doctype'] != DOC_CNOTE
                        ? ($PAYTYPES[$this->data['paytype']]['features'] & INVOICE_FEATURE_TO_PAY
                            ? 'Invoice value: $a (to repay)'
                            : 'Invoice value: $a'
                        )
                        : ($PAYTYPES[$this->data['paytype']]['features'] & INVOICE_FEATURE_TO_PAY
                            ? 'Correction value: $a (to repay)'
                            : 'Correction value: $a'
                        ),
                    Utils::formatMoney($this->data['value'], $this->data['currency'])
                )
            );
        } else {
            $y = $y - $this->backend->text_align_left(
                $x,
                $y,
                $show_balance_summary ? 10 : 14,
                (!$show_balance_summary && $this->use_alert_color ? '<c:color:255,0,0>' : '')
                . trans(
                    $this->data['doctype'] != DOC_CNOTE
                        ? ($PAYTYPES[$this->data['paytype']]['features'] & INVOICE_FEATURE_TO_PAY
                            ? 'Invoice value: $a (to pay)'
                            : 'Invoice value: $a'
                        )
                        : ($PAYTYPES[$this->data['paytype']]['features'] & INVOICE_FEATURE_TO_PAY
                            ? 'Correction value: $a (to pay)'
                            : 'Correction value: $a'
                        ),
                    Utils::formatMoney($this->data['value'], $this->data['currency'])
                )
                . (!$show_balance_summary && $this->use_alert_color ? '</c:color>' : '')
            );
        }
        if (!ConfigHelper::checkConfig('invoices.hide_in_words')) {
            $y = $y - $this->backend->text_align_left($x, $y, 10, trans('In words:') . ' ' . moneyf_in_words($this->data['value'], $this->data['currency']));
        }
        return $y;
    }

    protected function invoice_balance($x, $y)
    {
        $show_balance_summary = ConfigHelper::checkConfig('invoices.show_balance_summary');

        $balance = $this->data['customerbalance'];

        if ($show_balance_summary) {
            if (isset($this->data['invoice']) && $this->data['doctype'] == DOC_CNOTE) {
                $total = $this->data['total'] - $this->data['invoice']['total'];
            } else {
                $total = $this->data['total'];
            }
            $previous_balance = $balance + $total;

            if ($previous_balance > 0) {
                $comment = trans('(excess payment)');
            } elseif ($previous_balance < 0) {
                $comment = trans('(to pay)');
            } else {
                $comment = '';
            }


            $y = $y - $this->backend->text_align_left(
                $x,
                $y,
                9,
                '<b>' . trans(
                    'Previous balance: $a $b',
                    Utils::formatMoney(abs($previous_balance) / $this->data['currencyvalue'], $this->data['currency']),
                    $comment
                ) . '</b>'
            );
        } else {
            if ($balance > 0) {
                $comment = trans('(excess payment)');
            } elseif ($balance < 0) {
                $comment = trans('(to pay)');
            } else {
                $comment = '';
            }

            $y = $y - $this->backend->text_align_left(
                $x,
                $y,
                9,
                ($this->use_alert_color ? '<c:color:255,0,0>' : '') . '<b>'
                . trans(
                    'Your balance on date of invoice issue: $a $b',
                    Utils::formatMoney($balance / $this->data['currencyvalue'], $this->data['currency']),
                    $comment
                )
                . ($this->use_alert_color ? '</c:color>' : '') . '</b>'
            );
            $y = $y - $this->backend->text_align_left(
                $x,
                $y,
                9,
                '<b>' . trans('Balance includes current invoice') . '</b>'
            );
        }

        if ($show_balance_summary) {
            $y = $y - $this->backend->text_align_left(
                $x,
                $y,
                14,
                ($this->use_alert_color ? '<c:color:255,0,0>' : '') . '<b>'
                . ($balance >= 0
                    ? trans(
                        'Excess payment: $a',
                        Utils::formatMoney($balance / $this->data['currencyvalue'], $this->data['currency'])
                    )
                    : trans(
                        'Total to pay: $a',
                        Utils::formatMoney(-$balance / $this->data['currencyvalue'], $this->data['currency'])
                    ))
                . ($this->use_alert_color ? '</c:color>' : '') . '</b>'
            );
        }

        return $y;
    }

    protected function invoice_pricing_method($x, $y)
    {
        if (isset($this->data['netflag']) && !empty($this->data['netflag'])) {
            $comment = trans('net');
        } else {
            $comment = trans('gross');
        }

        $y = $y - $this->backend->text_align_left(
            $x,
            $y,
            9,
            '<b>' . trans('The document is issued according to the $a price', $comment) . '</b>'
        );

        return $y;
    }

    protected function invoice_expositor($x, $y)
    {
        if (!ConfigHelper::checkConfig('invoices.hide_expositor')) {
            $y = $y - $this->backend->text_align_right($x, $y, 10, trans('Expositor: <b>$a</b>', $this->data['expositor']));
        }
        return $y;
    }

    protected function invoice_comment($x, $y)
    {
        if (empty($this->data['comment'])) {
            return $y;
        } else {
            return $y - $this->backend->text_align_left($x, $y, 10, trans('Comment:') . ' ' . $this->data['comment']);
        }
    }

    protected function invoice_footnote($x, $y, $width, $font_size)
    {
        if (!empty($this->data['division_footer'])) {
            $y = $y - $this->backend->getFontHeight($font_size);
            //$y = $y - $this->backend->text_align_left($x, $y, $font_size, '<b>' . trans('Notes:') . '</b>');
            $tmp = $this->data['division_footer'];

            $accounts = array(bankaccount($this->data['customerid'], $this->data['account'], $this->data['export']));
            if (ConfigHelper::checkConfig('invoices.show_all_accounts')) {
                $accounts = array_merge($accounts, $this->data['bankaccounts']);
            }
            foreach ($accounts as &$account) {
                $account = format_bankaccount($account, $this->data['export']);
            }
            $tmp = str_replace('%bankaccount', implode("\n", $accounts), $tmp);
            $tmp = str_replace('%bankname', $this->data['div_bank'], $tmp);

            $tmp = preg_split('/\r?\n/', $tmp);
            foreach ($tmp as $line) {
                $y = $this->backend->text_wrap($x, $y, $width, $font_size, $line, 'left');
            }
        }
        return $y;
    }

    protected function invoice_memo($x, $y)
    {
        if (empty($this->data['memo'])) {
            return $y;
        } else {
            return $y - $this->backend->text_align_left($x, $y, 10, trans('Memo:') . ' ' . $this->data['memo']);
        }
    }

    protected function invoice_header_image($x, $y)
    {
        $image_path = ConfigHelper::getConfig('invoices.header_image', '', true);
        if (!file_exists($image_path) || !preg_match('/\.(?<ext>gif|jpg|jpeg|png)$/', $image_path, $m)) {
            return false;
        }

        switch (strtolower($m['ext'])) {
            case 'gif':
                $this->backend->addGifFromFile($image_path, $x, $y, 0, self::HEADER_IMAGE_HEIGHT);
                return true;
                break;
            case 'jpg':
            case 'jpeg':
                $this->backend->addJpegFromFile($image_path, $x, $y, 0, self::HEADER_IMAGE_HEIGHT);
                return true;
                break;
            case 'png':
                $this->backend->addPngFromFile($image_path, $x, $y, 0, self::HEADER_IMAGE_HEIGHT);
                return true;
                break;
        }

        return false;
    }

    protected function invoice_cancelled()
    {
        if ($this->data['cancelled']) {
            $this->backend->setColor(0.5, 0.5, 0.5);
            $this->backend->addText(180, 350, 50, trans('CANCELLED'), 0, 'left', -45);
            $this->backend->setColor(0, 0, 0);
        }
    }

    protected function invoice_no_accountant()
    {
        if (!empty($this->data['dontpublish']) && !$this->data['cancelled']) {
            $this->backend->setColor(0.5, 0.5, 0.5);
            $this->backend->addText(80, 200, 50, trans('NO ACCOUNTANT DOCUMENT'), 0, 'left', -45);
            $this->backend->setColor(0, 0, 0);
        }
    }

    public function invoice_body_standard()
    {
        if (!empty($this->data['div_ccode'])) {
            Localisation::setSystemLanguage($this->data['div_ccode']);
        }

        $page = $this->backend->ezStartPageNumbers($this->backend->ez['pageWidth']-50, 20, 8, 'right', trans('Page $a of $b', '{PAGENUM}', '{TOTALPAGENUM}'), 1);

        if (ConfigHelper::checkConfig('invoices.jpk_flags')) {
            $this->invoice_jpk_flags();
        }

        $top = $this->backend->ez['pageHeight'] - 50;

        $this->invoice_cancelled();
        $this->invoice_no_accountant();
        $header_image = $this->invoice_header_image(30, $top - (self::HEADER_IMAGE_HEIGHT / 2));
        $this->invoice_dates(500, $top);
        $this->invoice_address_box(400, $top - 100);

        if ($header_image == true) {
            $top -= self::HEADER_IMAGE_HEIGHT;
        }

        $top = $this->invoice_title(30, $top);
        $top = $this->invoice_seller(30, $top) - 7;
        $top = $this->invoice_buyer(30, $top) - 7;

        if (!empty($this->data['recipient_address_id'])) {
            $top = $this->invoice_recipient(30, $top) - 7;
        }

        $return = $this->new_invoice_data(30, $top, 530, 7, 2);
        $top = $return[2] + 5 - 40;
        $this->backend->check_page_length($top);
        $this->invoice_expositor(530, $top);

        $top = $return[2] - 0;
        $this->backend->check_page_length($top);
        $top = $this->invoice_to_pay(30, $top);

        if (ConfigHelper::checkConfig('invoices.show_pricing_method', true)) {
            $top = $this->invoice_pricing_method(30, $top);
        }
        if (ConfigHelper::checkConfig('invoices.show_balance', true)
            || ConfigHelper::checkConfig('invoices.show_expired_balance')) {
            $top = $this->invoice_balance(30, $top);
        }

        $top = $top - 20;
        $this->backend->check_page_length($top);
        $top = $this->invoice_comment(30, $top);

        $this->backend->check_page_length($top);
        $top = $this->invoice_footnote(30, $top, 530, 10);

        $this->backend->check_page_length($top);

        if (ConfigHelper::checkConfig('invoices.show_memo', true)) {
            $this->invoice_memo(30, $top);
        }

        $page = $this->backend->ezStopPageNumbers(1, 1, $page);

        if (!$this->data['disable_protection'] && $this->data['protection_password']) {
            $this->backend->setEncryption(
                '',
                $this->data['protection_password'],
                array('modify', 'copy', 'fill', 'extract', 'assemble'),
                2
            );
        }

        if (!empty($this->data['div_ccode'])) {
            Localisation::resetSystemLanguage();
        }
    }

    public function invoice_body_ft0100()
    {
        global $PAYTYPES;

        if (!empty($this->data['div_ccode'])) {
            Localisation::setSystemLanguage($this->data['div_ccode']);
        }

        $page = $this->backend->ezStartPageNumbers($this->backend->ez['pageWidth']/2+10, $this->backend->ez['pageHeight']-30, 8, '', trans('Page $a of $b', '{PAGENUM}', '{TOTALPAGENUM}'), 1);

        if (ConfigHelper::checkConfig('invoices.jpk_flags')) {
            $this->invoice_jpk_flags();
        }

        $top = $this->backend->ez['pageHeight'] - 50;

        $this->invoice_cancelled();
        $this->invoice_no_accountant();
        $header_image = $this->invoice_header_image(30, $top - (self::HEADER_IMAGE_HEIGHT / 2));
        $this->invoice_dates(500, $top);
        $this->invoice_address_box(400, $top - 100);

        if ($header_image == true) {
            $top -= self::HEADER_IMAGE_HEIGHT;
        }

        $top = $this->invoice_title(30, $top);
        $top = $this->invoice_seller(30, $top) - 10;
        $top = $this->invoice_buyer(30, $top) - 10;

        if (!empty($this->data['recipient_address_id'])) {
            $top = $this->invoice_recipient(30, $top) - 7;
        }

        $top = $this->invoice_comment(470, $top);
        $top = $this->invoice_footnote(470, $top, 90, 8);

        if (ConfigHelper::checkConfig('invoices.show_memo', true)) {
            $this->invoice_memo(30, $top);
        }

        $return = $this->new_invoice_data(30, $top, 430, 6, 1);
        $top = $return[2] + 5 - 40;
        $this->invoice_expositor(430, $top);
        $top = $return[2] - 0;
        $top = $this->invoice_to_pay(30, $top);

        if (ConfigHelper::checkConfig('invoices.show_pricing_method', true)) {
            $top = $this->invoice_pricing_method(30, $top);
        }
        if (ConfigHelper::checkConfig('invoices.show_balance', true)
            || ConfigHelper::checkConfig('invoices.show_expired_balance')) {
            $top = $this->invoice_balance(30, $top);
        }

        $this->backend->check_page_length($top, 200);
        if (($PAYTYPES[$this->data['paytype']]['features'] & INVOICE_FEATURE_TRANSFER_FORM)
            && ($this->data['customerbalance'] < 0 || ConfigHelper::checkConfig('invoices.always_show_form', true))) {
            $lms = LMS::getInstance();
            if ($lms->checkCustomerConsent($this->data['customerid'], CCONSENT_TRANSFERFORM)) {
                $this->invoice_main_form_fill(187, 3, 0.4);
                $this->invoice_simple_form_fill(14, 3, 0.4);
            }
        }
        $page = $this->backend->ezStopPageNumbers(1, 1, $page);

        if (!$this->data['disable_protection'] && $this->data['protection_password']) {
            $this->backend->setEncryption(
                '',
                $this->data['protection_password'],
                array('modify', 'copy', 'fill', 'extract', 'assemble'),
                2
            );
        }

        if (!empty($this->data['div_ccode'])) {
            Localisation::resetSystemLanguage();
        }
    }
}

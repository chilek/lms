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

class LMSTcpdfInvoice extends LMSInvoice
{
    private $use_alert_color;

    public function __construct($title, $pagesize = 'A4', $orientation = 'portrait')
    {
        parent::__construct('LMSTcpdfBackend', $title, $pagesize, $orientation);

        $this->use_alert_color = ConfigHelper::checkConfig('invoices.use_alert_color');

        list ($margin_top, $margin_right, $margin_bottom, $margin_left) = explode(',', ConfigHelper::getConfig('invoices.tcpdf_margins', '27,15,25,15'));
        $this->backend->SetMargins(trim($margin_left), trim($margin_top), trim($margin_right));
        $this->backend->SetAutoPageBreak(true, trim($margin_bottom));
    }

    protected function Table()
    {
        $hide_discount = ConfigHelper::checkConfig('invoices.hide_discount');

        /* set the line width and headers font */
        $this->backend->SetFillColor(255, 255, 255);
        $this->backend->SetTextColor(0);
        $this->backend->SetDrawColor(0, 0, 0);
        $this->backend->SetLineWidth(0.1);
        $this->backend->SetFont('arial', 'B', 7);

        $margins = $this->backend->getMargins();
        $table_width = $this->backend->getPageWidth() - ($margins['left'] + $margins['right']);

        /* invoice headers */
        $heads['no'] = trans('No.');
        $heads['name'] = trans('Name of Product, Commodity or Service:');
        $heads['prodid'] = trans('Product ID:');
        $heads['content'] = trans('Unit:');
        $heads['count'] = trans('Amount:');
        if (!$hide_discount && (!empty($this->data['pdiscount']) || !empty($this->data['vdiscount']))) {
            $heads['discount'] = trans('Discount:');
        }
        $heads['basevalue'] = trans('Unitary Net Value:');
        $heads['totalbase'] = trans('Net Value:');
        $heads['taxlabel'] = trans('Tax Rate:');
        $heads['totaltax'] = trans('Tax Value:');
        $heads['total'] = trans('Gross Value:');

        /* width of the columns on the invoice */
        foreach ($heads as $name => $text) {
            //$h_width[$name] = $this->getStringWidth($text, '', 'B', 8);
            $h_width[$name] = $this->backend->getWrapStringWidth($text, 'B');
        }

        /* change the column widths if are wider than the header */
        if ($this->data['content']) {
            foreach ($this->data['content'] as $item) {
                $t_width['no'] = 7;
                $t_width['name'] = $this->backend->getStringWidth($item['description']);
                $t_width['prodid'] = $this->backend->getStringWidth($item['prodid']);
                $t_width['content'] = $this->backend->getStringWidth($item['content']);
                $t_width['count'] = $this->backend->getStringWidth((float)$item['count']);
                if (!$hide_discount) {
                    if (!empty($this->data['pdiscount'])) {
                        $t_width['discount'] = $this->backend->getStringWidth(sprintf('%.2f%%', $item['pdiscount']));
                    } elseif (!empty($this->data['vdiscount'])) {
                        $t_width['discount'] = $this->backend->getStringWidth(sprintf('%01.2f', $item['vdiscount'])) + 1;
                    }
                }
                $t_width['basevalue'] = $this->backend->getStringWidth(sprintf('%01.2f', $item['basevalue'])) + 1;
                $t_width['totalbase'] = $this->backend->getStringWidth(sprintf('%01.2f', $item['totalbase'])) + 1;
                $t_width['taxlabel'] = $this->backend->getStringWidth($item['taxlabel']) + 1;
                $t_width['totaltax'] = $this->backend->getStringWidth(sprintf('%01.2f', $item['totaltax'])) + 1;
                $t_width['total'] = $this->backend->getStringWidth(sprintf('%01.2f', $item['total'])) + 1;
            }
        }

        foreach ($t_width as $name => $w) {
            if ($w > $h_width[$name]) {
                $h_width[$name] = $w;
            }
        }

        if (isset($this->data['invoice']['content'])) {
            foreach ($this->data['invoice']['content'] as $item) {
                $t_width['no'] = 7;
                $t_width['name'] = $this->backend->getStringWidth($item['description']);
                $t_width['prodid'] = $this->backend->getStringWidth($item['prodid']);
                $t_width['content'] = $this->backend->getStringWidth($item['content']);
                $t_width['count'] = $this->backend->getStringWidth((float)$item['count']);
                if (!$hide_discount) {
                    if (!empty($this->data['pdiscount'])) {
                        $t_width['discount'] = $this->backend->getStringWidth(sprintf('%.2f%%', $item['pdiscount']));
                    } elseif (!empty($this->data['vdiscount'])) {
                        $t_width['discount'] = $this->backend->getStringWidth(sprintf('%01.2f', $item['vdiscount'])) + 1;
                    }
                }
                $t_width['basevalue'] = $this->backend->getStringWidth(sprintf('%01.2f', $item['basevalue'])) + 1;
                $t_width['totalbase'] = $this->backend->getStringWidth(sprintf('%01.2f', $item['totalbase'])) + 1;
                $t_width['taxlabel'] = $this->backend->getStringWidth($item['taxlabel']) + 1;
                $t_width['totaltax'] = $this->backend->getStringWidth(sprintf('%01.2f', $item['totaltax'])) + 1;
                $t_width['total'] = $this->backend->getStringWidth(sprintf('%01.2f', $item['total'])) + 1;
            }
        }

        foreach ($t_width as $name => $w) {
            if ($w > $h_width[$name]) {
                $h_width[$name] = $w;
            }
        }

        /* dynamic setting the width of the table 'name' */
        $sum = 0;
        foreach ($h_width as $name => $w) {
            if ($name != 'name') {
                $sum += $w;
            }
        }
        $h_width['name'] = $table_width - $sum;

        $h_head = 0;
        /* invoice data table headers */
        foreach ($heads as $item => $name) {
//          $h_cell = $this->backend->getStringHeight($h_width[$item], $heads[$item], true, false, 0, 1);

            $this->backend->startTransaction();
            $old_y = $this->backend->GetY();
            $this->backend->MultiCell($h_width[$item], 0, $heads[$item], 1, 'C', true, 1, '', '', true, 0, false, false, 0);
            $h_cell = $this->backend->GetY() - $old_y;
            $this->backend->rollbackTransaction(true);

            if ($h_cell > $h_head) {
                $h_head = $h_cell;
            }
        }
        foreach ($heads as $item => $name) {
            $this->backend->MultiCell($h_width[$item], $h_head, $heads[$item], 1, 'C', true, 0, '', '', true, 0, false, false, $h_head, 'M');
        }

        $this->backend->Ln();
        $this->backend->SetFont('arial', '', 7);

        /* invoice correction data */
        if (isset($this->data['invoice'])) {
            $this->backend->Ln(3);
            $this->backend->writeHTMLCell(0, 0, '', '', '<b>' . trans('Was:') . '</b>', 0, 1, 0, true, 'L');
            $this->backend->Ln(3);
            $i = 1;
            if ($this->data['invoice']['content']) {
                foreach ($this->data['invoice']['content'] as $item) {
                    $h = $this->backend->getStringHeight($h_width['name'], $item['description'], true, false, '', 1) + 1;
                    $this->backend->Cell($h_width['no'], $h, $i . '.', 1, 0, 'C', 0, '', 1);
                    $this->backend->MultiCell($h_width['name'], $h, $item['description'], 1, 'L', false, 0, '', '', true, 0, false, false, $h, 'M');
                    $this->backend->Cell($h_width['prodid'], $h, $item['prodid'], 1, 0, 'C', 0, '', 1);
                    $this->backend->Cell($h_width['content'], $h, $item['content'], 1, 0, 'C', 0, '', 1);
                    $this->backend->Cell($h_width['count'], $h, (float)$item['count'], 1, 0, 'C', 0, '', 1);
                    if (!$hide_discount) {
                        if (!empty($this->data['pdiscount'])) {
                            $this->backend->Cell($h_width['discount'], $h, sprintf('%.2f%%', $item['pdiscount']), 1, 0, 'R', 0, '', 1);
                        } elseif (!empty($this->data['vdiscount'])) {
                            $this->backend->Cell($h_width['discount'], $h, sprintf('%01.2f', $item['vdiscount']), 1, 0, 'R', 0, '', 1);
                        }
                    }
                    $this->backend->Cell($h_width['basevalue'], $h, sprintf('%01.2f', $item['basevalue']), 1, 0, 'R', 0, '', 1);
                    $this->backend->Cell($h_width['totalbase'], $h, sprintf('%01.2f', $item['totalbase']), 1, 0, 'R', 0, '', 1);
                    $this->backend->Cell($h_width['taxlabel'], $h, $item['taxlabel'], 1, 0, 'C', 0, '', 1);
                    $this->backend->Cell($h_width['totaltax'], $h, sprintf('%01.2f', $item['totaltax']), 1, 0, 'R', 0, '', 1);
                    $this->backend->Cell($h_width['total'], $h, sprintf('%01.2f', $item['total']), 1, 0, 'R', 0, '', 1);
                    $this->backend->Ln();
                    $i++;
                }
            }

            /* invoice correction summary table - headers */
            $sum = 0;
            foreach ($h_width as $name => $w) {
                if (in_array($name, array('no', 'name', 'prodid', 'content', 'count', 'discount', 'basevalue'))) {
                    $sum += $w;
                }
            }

            $this->backend->SetFont('arial', 'B', 8);
            $this->backend->Cell($sum, 5, trans('Total:'), 0, 0, 'R', 0, '', 1);
            $this->backend->SetFont('arial', '', 8);
            $this->backend->Cell($h_width['totalbase'], 5, sprintf('%01.2f', $this->data['invoice']['totalbase']), 1, 0, 'R', 0, '', 1);
            $this->backend->SetFont('arial', 'B', 8);
            $this->backend->Cell($h_width['taxlabel'], 5, 'x', 1, 0, 'C', 0, '', 1);
            $this->backend->SetFont('arial', '', 8);
            $this->backend->Cell($h_width['totaltax'], 5, sprintf('%01.2f', $this->data['invoice']['totaltax']), 1, 0, 'R', 0, '', 1);
            $this->backend->Cell($h_width['total'], 5, sprintf('%01.2f', $this->data['invoice']['total']), 1, 0, 'R', 0, '', 1);
            $this->backend->Ln();

            /* invoice correction summary table - data */
            if ($this->data['invoice']['taxest']) {
                $i = 1;
                foreach ($this->data['invoice']['taxest'] as $item) {
                    $this->backend->SetFont('arial', 'B', 8);
                    $this->backend->Cell($sum, 5, trans('in it:'), 0, 0, 'R', 0, '', 1);
                    $this->backend->SetFont('arial', '', 8);
                    $this->backend->Cell($h_width['totalbase'], 5, sprintf('%01.2f', $item['base']), 1, 0, 'R', 0, '', 1);
                    $this->backend->Cell($h_width['taxlabel'], 5, $item['taxlabel'], 1, 0, 'C', 0, '', 1);
                    $this->backend->Cell($h_width['totaltax'], 5, sprintf('%01.2f', $item['tax']), 1, 0, 'R', 0, '', 1);
                    $this->backend->Cell($h_width['total'], 5, sprintf('%01.2f', $item['total']), 1, 0, 'R', 0, '', 1);
                    $this->backend->Ln(12);
                    $i++;
                }
            }

            /* reason of issue of invoice correction */
            if ($this->data['reason'] != '') {
                $this->backend->writeHTMLCell(0, 0, '', '', '<b>' . trans('Reason:') . ' ' . $this->data['reason'] . '</b>', 0, 1, 0, true, 'L');
            }
            $this->backend->writeHTMLCell(0, 0, '', '', '<b>' . trans('Corrected to:') . '</b>', 0, 1, 0, true, 'L');
            $this->backend->Ln(3);
        }

        /* invoice data */
        $i = 1;
        foreach ($this->data['content'] as $item) {
            $h = $this->backend->getStringHeight($h_width['name'], $item['description'], true, false, '', 1) + 1;
            $this->backend->Cell($h_width['no'], $h, $i . '.', 1, 0, 'C', 0, '', 1);
            $this->backend->MultiCell($h_width['name'], $h, $item['description'], 1, 'L', false, 0, '', '', true, 0, false, false, $h, 'M');
            $this->backend->Cell($h_width['prodid'], $h, $item['prodid'], 1, 0, 'C', 0, '', 1);
            $this->backend->Cell($h_width['content'], $h, $item['content'], 1, 0, 'C', 0, '', 1);
            $this->backend->Cell($h_width['count'], $h, (float)$item['count'], 1, 0, 'C', 0, '', 1);
            if (!$hide_discount) {
                if (!empty($this->data['pdiscount'])) {
                    $this->backend->Cell($h_width['discount'], $h, sprintf('%01.2f%%', $item['pdiscount']), 1, 0, 'R', 0, '', 1);
                } elseif (!empty($this->data['vdiscount'])) {
                    $this->backend->Cell($h_width['discount'], $h, sprintf('%01.2f', $item['vdiscount']), 1, 0, 'R', 0, '', 1);
                }
            }
            $this->backend->Cell($h_width['basevalue'], $h, sprintf('%01.2f', $item['basevalue']), 1, 0, 'R', 0, '', 1);
            $this->backend->Cell($h_width['totalbase'], $h, sprintf('%01.2f', $item['totalbase']), 1, 0, 'R', 0, '', 1);
            $this->backend->Cell($h_width['taxlabel'], $h, $item['taxlabel'], 1, 0, 'C', 0, '', 1);
            $this->backend->Cell($h_width['totaltax'], $h, sprintf('%01.2f', $item['totaltax']), 1, 0, 'R', 0, '', 1);
            $this->backend->Cell($h_width['total'], $h, sprintf('%01.2f', $item['total']), 1, 0, 'R', 0, '', 1);
            $this->backend->Ln();
            $i++;
        }

        /* invoice summary table - headers */
        $sum = 0;
        foreach ($h_width as $name => $w) {
            if (in_array($name, array('no', 'name', 'prodid', 'content', 'count', 'discount', 'basevalue'))) {
                $sum += $w;
            }
        }

        $this->backend->SetFont('arial', 'B', 8);
        $this->backend->Cell($sum, 5, trans('Total:'), 0, 0, 'R', 0, '', 1);
        $this->backend->SetFont('arial', '', 8);
        $this->backend->Cell($h_width['totalbase'], 5, sprintf('%01.2f', $this->data['totalbase']), 1, 0, 'R', 0, '', 1);
        $this->backend->SetFont('arial', 'B', 8);
        $this->backend->Cell($h_width['taxlabel'], 5, 'x', 1, 0, 'C', 0, '', 1);
        $this->backend->SetFont('arial', '', 8);
        $this->backend->Cell($h_width['totaltax'], 5, sprintf('%01.2f', $this->data['totaltax']), 1, 0, 'R', 0, '', 1);
        $this->backend->Cell($h_width['total'], 5, sprintf('%01.2f', $this->data['total']), 1, 0, 'R', 0, '', 1);
        $this->backend->Ln();

        /* invoice summary table - data */
        if ($this->data['taxest']) {
            $i = 1;
            foreach ($this->data['taxest'] as $item) {
                $this->backend->SetFont('arial', 'B', 8);
                $this->backend->Cell($sum, 5, trans('in it:'), 0, 0, 'R', 0, '', 1);
                $this->backend->SetFont('arial', '', 8);
                $this->backend->Cell($h_width['totalbase'], 5, sprintf('%01.2f', $item['base']), 1, 0, 'R', 0, '', 1);
                $this->backend->Cell($h_width['taxlabel'], 5, $item['taxlabel'], 1, 0, 'C', 0, '', 1);
                $this->backend->Cell($h_width['totaltax'], 5, sprintf('%01.2f', $item['tax']), 1, 0, 'R', 0, '', 1);
                $this->backend->Cell($h_width['total'], 5, sprintf('%01.2f', $item['total']), 1, 0, 'R', 0, '', 1);
                $this->backend->Ln();
                $i++;
            }
        }

        $this->backend->Ln(3);
        /* difference between the invoice and the invoice correction */
        if (isset($this->data['invoice'])) {
            $total = $this->data['total'] - $this->data['invoice']['total'];
            $totalbase = $this->data['totalbase'] - $this->data['invoice']['totalbase'];
            $totaltax = $this->data['totaltax'] - $this->data['invoice']['totaltax'];

            $this->backend->SetFont('arial', 'B', 8);
            $this->backend->Cell($sum, 5, trans('Difference value:'), 0, 0, 'R', 0, '', 1);
            $this->backend->SetFont('arial', '', 8);
            $this->backend->Cell($h_width['totalbase'], 5, sprintf('%01.2f', $totalbase), 1, 0, 'R', 0, '', 1);
            $this->backend->SetFont('arial', 'B', 8);
            $this->backend->Cell($h_width['taxlabel'], 5, 'x', 1, 0, 'C', 0, '', 1);
            $this->backend->SetFont('arial', '', 8);
            $this->backend->Cell($h_width['totaltax'], 5, sprintf('%01.2f', $totaltax), 1, 0, 'R', 0, '', 1);
            $this->backend->Cell($h_width['total'], 5, sprintf('%01.2f', $total), 1, 0, 'R', 0, '', 1);
            $this->backend->Ln();
        }
    }

    protected function invoice_simple_form_draw()
    {
        /* set line styles */
        $line_thin = array('width' => 0.15, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 0, 0));
        $line_dash = array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '3, 3', 'phase' => 10, 'color' => array(255, 0, 0));
        $line_light = array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '3, 5', 'phase' => 10, 'color' => array(245, 200, 200));

        $this->backend->setColor('text', 255, 0, 0);
        $this->backend->SetFont('arial', '', 8);
        $this->backend->setFontStretching(120);

        $this->backend->StartTransform();
        $this->backend->Rotate(90, 135, 135);
        $this->backend->Text(1, 1, 'Pokwitowanie dla zleceniodawcy');
        $this->backend->StopTransform();

        $this->backend->SetFont('arial', '', 6);
        $this->backend->setFontStretching(100);

        /* draw simple form */
        $this->backend->Line(0, 190, 210, 190, $line_light);
        $this->backend->Line(60, 190, 60, 297, $line_light);
        $this->backend->Rect(6, 192, 54, 105, 'F', '', array(245, 200, 200));

        /* division name */
        $this->backend->Rect(7, 193, 17, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(7, 193, 'nazwa odbiorcy');
        $this->backend->Rect(7, 196, 52, 5, 'F', '', array(255, 255, 255));
        $this->backend->Rect(7, 202, 52, 5, 'F', '', array(255, 255, 255));
        $this->backend->Rect(7, 208, 52, 5, 'F', '', array(255, 255, 255));

        /* account */
        $this->backend->Rect(7, 215, 22, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(7, 215, 'nr rachunku odbiorcy');
        $this->backend->Rect(7, 218, 52, 5, 'F', '', array(255, 255, 255));

        /* customer name */
        $this->backend->Rect(7, 224, 22, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(7, 224, 'nazwa zleceniodawcy');
        $this->backend->Rect(7, 227, 52, 5, 'F', '', array(255, 255, 255));
        $this->backend->Rect(7, 233, 52, 5, 'F', '', array(255, 255, 255));
        $this->backend->Rect(7, 239, 52, 5, 'F', '', array(255, 255, 255));

        /* title */
        $this->backend->Rect(7, 245, 11, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(7, 245, 'tytułem');
        $this->backend->Rect(7, 248, 52, 10, 'F', '', array(255, 255, 255));

        /* amount */
        $this->backend->Rect(7, 259, 9, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(7, 259, 'kwota');
        $this->backend->Rect(7, 262, 52, 5, 'F', '', array(255, 255, 255));

        /* stamp */
        $this->backend->Rect(8, 269, 9, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(8, 269, 'stempel');
        $this->backend->Rect(8, 272, 22, 25, 'F', '', array(255, 255, 255));
        $this->backend->Line(8, 272, 8, 297, $line_thin);
        $this->backend->Line(17, 272, 30, 272, $line_thin);
        $this->backend->Line(30, 272, 30, 297, $line_thin);
        $this->backend->SetLineStyle($line_dash);
        $this->backend->Circle(19, 283, 8);

        /* payment */
        $this->backend->Rect(34, 269, 9, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(34, 269, 'opłata');
        $this->backend->Rect(34, 272, 26, 25, 'F', '', array(255, 255, 255));
        $this->backend->Line(34, 272, 34, 297, $line_thin);
        $this->backend->Line(43, 272, 60, 272, $line_thin);
        $this->backend->Line(60, 272, 60, 297, $line_thin);
    }

    protected function invoice_main_form_draw()
    {
        /* set line styles */
        $line_thin = array('width' => 0.15, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 0, 0));
        $line_bold = array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 0, 0));
        $line_dash = array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '3, 3', 'phase' => 10, 'color' => array(255, 0, 0));

        $this->backend->setColor('text', 255, 0, 0);
        $this->backend->SetFont('arial', '', 8);
        $this->backend->setFontStretching(120);

        $this->backend->StartTransform();
        $this->backend->Rotate(90, 135, 135);
        $this->backend->Text(1, 61, 'Polecenie przelewu / wpłata gotówkowa');
        $this->backend->StopTransform();

        $this->backend->StartTransform();
        $this->backend->Rotate(90, 135, 135);
        $this->backend->Text(10, 202, 'odcinek dla banku zleceniodawcy');
        $this->backend->StopTransform();

        $this->backend->SetFont('arial', '', 6);
        $this->backend->setFontStretching(100);

        /* draw main form */
        $this->backend->Rect(66, 192, 135, 88, 'F', '', array(245, 200, 200));

        /* division name */
        $this->backend->Rect(68, 193, 17, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(68, 193, 'nazwa odbiorcy');
        $this->backend->Rect(66.25, 196, 135, 5, 'F', '', array(255, 255, 255));
        $this->backend->Rect(68, 202, 20, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(68, 202, 'nazwa odbiorcy cd.');
        $this->backend->Rect(66.25, 205, 135, 5, 'F', '', array(255, 255, 255));

        /* account */
        $this->backend->Rect(66.5, 210.5, 131, 9, 'D', array('all' => $line_bold));
        $this->backend->Rect(68, 211, 22, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(68, 211, 'nr rachunku odbiorcy');
        $this->backend->Rect(67, 214, 130, 5, 'F', '', array(255, 255, 255));

        /* payment/transfer */
        for ($i = 0; $i < 2; $i++) {
            $this->backend->Rect(105 + ($i * 5.5), 223, 5, 5, 'DF', array('all' => $line_thin));
        }
        $this->backend->SetFont('arial', '', 12);
        $this->backend->Text(104.5, 223, 'W');
        $this->backend->Text(110.5, 223, 'P');

        /* currency */
        $this->backend->SetFont('arial', '', 6);
        $this->backend->Rect(121, 220, 10, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(121, 220, 'waluta');
        for ($i = 0; $i < 3; $i++) {
            $this->backend->Rect(120 + ($i * 4.5), 223, 4, 5, 'F', '', array(255, 255, 255));
        }

        /* amount */
        $this->backend->Rect(139.5, 219.5, 61.25, 9, 'D', array('all' => $line_bold));
        $this->backend->Rect(141, 220, 10, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(141, 220, 'kwota');
        $this->backend->Rect(140, 223, 60.25, 5, 'F', '', array(255, 255, 255));

        /* account/amount */
        $this->backend->Rect(68, 230, 60, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(68, 230, 'nr rachunku zleceniodawcy (przelew) / kwota słownie (wpłata)');
        for ($i = 0; $i < 26; $i++) {
            $this->backend->Rect(66 + ($i * 4.5), 233, 4.5, 5, 'DF', array('all' => $line_thin));
        }
        for ($i = 0; $i < 6; $i++) {
            $this->backend->Line(75 + ($i * 18), 236, 75 + ($i * 18), 238, $line_bold);
        }

        /* customer name */
        $this->backend->Rect(68, 240, 22, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(68, 240, 'nazwa zleceniodawcy');
        $this->backend->Rect(66.25, 243, 135, 5, 'F', '', array(255, 255, 255));
        $this->backend->Rect(68, 249, 25, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(68, 249, 'nazwa zleceniodawcy cd.');
        $this->backend->Rect(66.25, 252, 135, 5, 'F', '', array(255, 255, 255));

        /* title */
        $this->backend->Rect(68, 258, 11, 3, 'F', '', array(255, 255, 255));
        $this->backend->Text(68, 258, 'tytułem');
        $this->backend->Rect(66.25, 261, 135, 10, 'F', '', array(255, 255, 255));

        /* stamps */
        $this->backend->Rect(191, 272, 10, 6, 'F', '', array(255, 255, 255));
        $this->backend->Line(201, 270, 201, 280, $line_thin);
        $this->backend->Rect(66, 192, 135, 80, 'D', array('all' => $line_thin));
        $this->backend->Rect(66, 273, 68, 20, 'DF', array('all' => $line_thin));
        $this->backend->StartTransform();
        $this->backend->Translate(0, 23);
        $this->backend->Text(80, 265, 'pieczęć, data i podpis(y) zleceniodawcy');
        $this->backend->StopTransform();
        $this->backend->Line(134, 280, 210, 280, $line);
        $this->backend->Rect(155, 273, 20, 20, 'DF', array('all' => $line_thin));
        $this->backend->SetLineStyle($line_dash);
        $this->backend->Circle(165, 283, 8);
        for ($i = 0; $i < 4; $i++) {
            $this->backend->Rect(135.5 + ($i * 4.5), 285, 4.5, 4.5, 'DF', array('all' => $line_thin));
        }
        $this->backend->Line(144.5, 285, 144.5, 289.5, $line_bold);
        $this->backend->StartTransform();
        $this->backend->Translate(0, 16);
        $this->backend->Text(135, 265, 'opłata');
        $this->backend->StopTransform();
    }

    protected function invoice_simple_form_fill()
    {
        /* set font style & color */
        if (mb_strlen($this->data['division_shortname']) > 25) {
            $this->backend->SetFont('arial', '', floor(235 / mb_strlen($this->data['division_shortname'])));
        } else {
            $this->backend->SetFont('arial', '', 9);
        }
        $this->backend->setColor('text', 0, 0, 0);

        /* division name */
        $this->backend->Text(7, 197, $this->data['division_shortname']);
        $this->backend->Text(7, 203, $this->data['division_address']);
        $this->backend->Text(7, 209, $this->data['division_zip'] . ' ' . $this->data['division_city']);

        /* account */
        $this->backend->SetFont('arial', 'B', 9);
        if (count($this->data['bankaccounts']) == 1) {
            $account = $this->data['bankaccounts'][0];
        } else {
            $account = bankaccount($this->data['customerid'], $this->data['account']);
        }
        $this->backend->Text(7, 219, $account);

        /* customer name */
        $this->backend->SetFont('arial', '', 9);
        /* if customer name lenght > 26 chars then cut string */
        if (mb_strlen($this->data['name']) > 26) {
            $this->backend->Text(7, 228, mb_substr($this->data['name'], 0, 26));
        } else {
            $this->backend->Text(7, 228, $this->data['name']);
        }
        $this->backend->Text(7, 234, $this->data['address']);
        $this->backend->Text(7, 240, $this->data['zip'] . ' ' . $this->data['city']);

        if (ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.customer_balance_in_form', false))) {
            /* title */
            $this->backend->Text(7, 249, trans('Payment for liabilities'));

            $value = ($this->data['customerbalance'] / $this->data['currencyvalue']) * -1;
        } else {
            /* title */
            $this->backend->Text(7, 249, trans('Payment for invoice No. $a', null));
            $this->backend->SetFont('arial', 'B', 10);
            $this->backend->Text(7, 253, docnumber(array(
                'number' => $this->data['number'],
                'template' => $this->data['template'],
                'cdate' => $this->data['cdate'],
                'customerid' => $this->data['customerid'],
            )));

            $value = $this->data['value'];
        }
        /* amount */
        $this->backend->SetFont('arial', 'B', 10);
        $this->backend->Text(7, 263, moneyf($value, $this->data['currency']));
    }

    protected function invoice_main_form_fill()
    {
        /* set font style & color */
        $this->backend->SetFont('arial', '', 9);
        $this->backend->setColor('text', 0, 0, 0);

        /* division name */
        $this->backend->Text(67, 197, $this->data['division_name']);
        $this->backend->Text(67, 206, $this->data['division_address'] . ', ' . $this->data['division_zip'] . ' ' . $this->data['division_city']);

        /* account */
        $this->backend->SetFont('arial', 'B', 9);
        if (count($this->data['bankaccounts']) == 1) {
            $account = $this->data['bankaccounts'][0];
        } else {
            $account = bankaccount($this->data['customerid'], $this->data['account']);
        }
        $this->backend->Text(67, 215, format_bankaccount($account));

        /* currency */
        $this->backend->SetFont('arial', 'B', 10);
        $this->backend->setFontSpacing(2.5);
        $this->backend->Text(120, 224, 'PLN');
        $this->backend->setFontSpacing(0);

        /* amount */
        if (ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.customer_balance_in_form', false))) {
            $value = ($this->data['customerbalance'] / $this->data['currencyvalue']) * -1;
        } else {
            $value = $this->data['value'];
        }
        $this->backend->Text(142, 224, moneyf($value, $this->data['currency']));
        $this->backend->Text(67, 233, moneyf_in_words($value, $this->data['currency']));

        /* customer name */
        $this->backend->SetFont('arial', '', 9);
        /* if customer name lenght > 70 chars then stretch font */
        if (mb_strlen($this->data['name']) > 70) {
            $this->backend->setFontStretching(85);
        }
        $this->backend->Text(67, 243.5, $this->data['name']);
        $this->backend->setFontStretching(100);
        $this->backend->Text(67, 252.5, $this->data['address'] . ', ' . $this->data['zip'] . ' ' . $this->data['city']);

        /* barcode */
        $barcode = docnumber(array(
            'number' => $this->data['number'],
            'template' => $this->data['template'],
            'cdate' => $this->data['cdate'],
            'customerid' => $this->data['customerid'],
        ));
        if (!empty($barcode)) {
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
            $this->backend->write1DBarcode($barcode, 'C128', '', 262, 60, 8, 0.3, $style, '');
            $this->backend->StopTransform();
        }

        if (ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.customer_balance_in_form', false))) {
                /* title */
                $this->backend->SetFont('arial', '', 9);
                $this->backend->Text(124, 264, trans('Payment for liabilities'));
        } else {
                /* title */
                $this->backend->SetFont('arial', 'B', 9);
                $this->backend->Text(124, 264, trans('Payment for invoice No. $a', $barcode));
        }

        /* deadline */
        $paytype = $this->data['paytype'];
        $this->backend->SetFont('arial', '', 6);
        if ($paytype != 8) {
            $this->backend->StartTransform();
            $this->backend->Translate(0, 13);
            $this->backend->Text(135, 260, trans('Deadline:'));
            $this->backend->Text(135, 263, date("d.m.Y", $this->data['pdate']) . ' r.');
            $this->backend->StopTransform();
        }
    }

    protected function invoice_date()
    {
        $this->backend->SetFont('arial', '', 8);
        $this->backend->writeHTMLCell(0, 0, '', 10, trans('Settlement date:') . ' <b>' . date("d.m.Y", $this->data['cdate']) . '</b>', 0, 1, 0, true, 'R');
        if (!ConfigHelper::checkConfig('invoices.hide_sale_date')) {
            $this->backend->writeHTMLCell(0, 0, '', '', trans('Sale date:') . ' <b>' . date("d.m.Y", $this->data['sdate']) . '</b>', 0, 1, 0, true, 'R');
        }
    }

    protected function invoice_title()
    {
        $this->backend->SetY(29);
        $this->backend->SetFont('arial', 'B', 16);
        $docnumber = docnumber(array(
            'number' => $this->data['number'],
            'template' => $this->data['template'],
            'cdate' => $this->data['cdate'],
            'customerid' => $this->data['customerid'],
        ));
        if (isset($this->data['invoice'])) {
            $title = trans('Credit Note No. $a', $docnumber);
        } elseif ($this->data['doctype'] == DOC_INVOICE) {
            $title = trans('Invoice No. $a', $docnumber);
        } else {
            $title = trans('Pro Forma Invoice No. $a', $docnumber);
        }
        $this->backend->Write(0, $title, '', 0, 'C', true, 0, false, false, 0);

        if (isset($this->data['invoice'])) {
            $this->backend->SetFont('arial', 'B', 12);
            $docnumber = docnumber(array(
                'number' => $this->data['invoice']['number'],
                'template' => $this->data['invoice']['template'],
                'cdate' => $this->data['invoice']['cdate'],
                'customerid' => $this->data['customerid'],
            ));
            $this->backend->Write(0, trans('for Invoice No. $a', $docnumber), '', 0, 'C', true, 0, false, false, 0);
        }

        //$this->backend->SetFont('arial', '', 16);
        //$this->backend->Write(0, $this->data['type'], '', 0, 'C', true, 0, false, false, 0);

        if ($this->data['type'] == DOC_ENTITY_DUPLICATE) {
            $this->backend->SetFont('arial', '', 10);
            $title = trans('DUPLICATE, draw-up date:') . ' ' . date('d.m.Y', $this->data['duplicate-date']
                ? $this->data['duplicate-date'] : time());
            $this->backend->Write(0, $title, '', 0, 'C', true, 0, false, false, 0);
        }
    }

    protected function invoice_seller()
    {
        $this->backend->SetFont('arial', '', 8);
        $seller = '<b>' . trans('Seller:') . '</b><br>';
        $tmp = $this->data['division_header'];

        $accounts = array(bankaccount($this->data['customerid'], $this->data['account']));
        if (ConfigHelper::checkConfig('invoices.show_all_accounts')) {
            $accounts = array_merge($accounts, $this->data['bankaccounts']);
        }
        foreach ($accounts as &$account) {
            $account = format_bankaccount($account);
        }
        $account_text = ($this->use_alert_color ? '<span style="color:red">' : '')
            . implode("\n", $accounts)
            . ($this->use_alert_color ? '</span>' : '');
        $tmp = str_replace('%bankaccount', $account_text, $tmp);
        $tmp = str_replace('%bankname', $this->data['div_bank'], $tmp);

        if (ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.customer_bankaccount', true))) {
            $tmp .= "\n" . trans('Bank account:') . "\n" . '<B>' . $account_text . '<B>';
        }

        $tmp = preg_split('/\r?\n/', $tmp);
        foreach ($tmp as $line) {
            $seller .= $line . '<br>';
        }
        $this->backend->Ln(0);
        $this->backend->writeHTMLCell(80, '', '', 45, $seller, 0, 1, 0, true, 'L');
    }

    protected function invoice_buyer()
    {
        $oldy = $this->backend->GetY();

        $buyer = '<b>' . trans('Purchaser:') . '</b><br>';

        $buyer .= $this->data['name'] . '<br>';
        $buyer .= $this->data['address'] . '<br>';
        $buyer .= $this->data['zip'] . ' ' . $this->data['city'];
        if ($this->data['division_countryid'] && $this->data['countryid'] && $this->data['division_countryid'] != $this->data['countryid']) {
            $buyer .= ', ' . trans($this->data['country']);
        }
        $buyer .= '<br>';
        if ($this->data['ten']) {
            $buyer .= trans('TEN') . ': ' . $this->data['ten'] . '<br>';
        } elseif (!ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.hide_ssn', true)) && $this->data['ssn']) {
            $buyer .= trans('SSN') . ': ' . $this->data['ssn'] . '<br>';
        }
        if (ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.show_customerid', true))) {
            $buyer .= '<b>' . trans('Customer No.:') . ' ' . $this->data['customerid'] . '</b><br>';
        }
        $this->backend->SetFont('arial', '', 8);
        $this->backend->writeHTMLCell(80, '', '', '', $buyer, 0, 1, 0, true, 'L');

        $y = $this->backend->GetY();

        if (ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.post_address', true))) {
            $postbox = '';
            if ($this->data['post_name'] || $this->data['post_address']) {
                $lines = document_address(array(
                    'name' => $this->data['post_name'] ? $this->data['post_name'] : $this->data['name'],
                    'address' => $this->data['post_address'],
                    'street' => $this->data['post_street'],
                    'zip' => $this->data['post_zip'],
                    'postoffice' => $this->data['post_postoffice'],
                    'city' => $this->data['post_city'],
                ));
                $postbox .= implode('<br>', $lines);
            } else {
                $postbox .= $this->data['name'] . '<br>';
                $postbox .= $this->data['address'] . '<br>';
                $postbox .= $this->data['zip'] . ' ' . $this->data['city'] . '<br>';
            }

            if ($this->data['division_countryid'] && $this->data['post_countryid'] && $this->data['division_countryid'] != $this->data['post_countryid']) {
                $postbox .= ', ' . trans($this->data['post_country']) . '<br>';
            }

            $this->backend->SetFont('arial', 'B', 10);
            $this->backend->writeHTMLCell(80, '', 125, 50, $postbox, 0, 1, 0, true, 'L');
        }

        if (ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.customer_credentials', true))) {
            $pin = '<b>' . trans('Customer ID: $a', sprintf('%04d', $this->data['customerid'])) . '</b><br>';
            $pin .= '<b>PIN: ' . (strlen($this->data['customerpin']) < 4 ? sprintf('%04d', $this->data['customerpin']) : $this->data['customerpin']) . '</b><br>';

            $this->backend->SetFont('arial', 'B', 8);
            $this->backend->writeHTMLCell('', '', 125, $oldy + round(($y - $oldy) / 2), $pin, 0, 1, 0, true, 'L');
        }

        $this->backend->SetY($y);
    }

    protected function invoice_recipient()
    {
        if (empty($this->data['recipient_address_id'])) {
            return 0;
        }

        $this->backend->SetFont('arial', '', 8);

        $this->backend->writeHTMLCell(80, '', '', '', '<b>' . trans('Recipient:') . '</b>', 0, 1, 0, true, 'L');

        $rec_lines = document_address(array(
            'name' => $this->data['rec_name'],
            'address' => $this->data['rec_address'],
            'street' => $this->data['rec_street'],
            'zip' => $this->data['rec_zip'],
            'postoffice' => $this->data['rec_postoffice'],
            'city' => $this->data['rec_city'],
        ));

        foreach ($rec_lines as $line) {
            $this->backend->writeHTMLCell(80, '', '', '', $line, 0, 1, 0, true, 'L');
        }
    }

    protected function invoice_data()
    {
        /* print table */
        $this->backend->writeHTMLCell('', '', '', '', '', 0, 1, 0, true, 'L');
        $this->Table();
    }

    protected function invoice_to_pay()
    {
        $this->backend->Ln(-9);
        $this->backend->SetFont('arial', 'B', 14);
        if (isset($this->data['rebate'])) {
            $this->backend->writeHTMLCell(0, 0, '', '', trans('To repay:') . ' ' . moneyf($this->data['value'], $this->data['currency']), 0, 1, 0, true, 'L');
        } else {
            if ($this->use_alert_color) {
                $this->backend->SetTextColorArray(array(255, 0, 0));
            }
            $this->backend->writeHTMLCell(0, 0, '', '', trans('To pay:') . ' ' . moneyf($this->data['value'], $this->data['currency']), 0, 1, 0, true, 'L');
            if ($this->use_alert_color) {
                $this->backend->SetTextColor();
            }
        }

        $this->backend->SetFont('arial', '', 8);
        if (!ConfigHelper::checkConfig('invoices.hide_in_words')) {
            $this->backend->writeHTMLCell(0, 5, '', '', trans('In words:') . ' ' . moneyf_in_words($this->data['value'], $this->data['currency']), 0, 1, 0, true, 'L');
        }
    }

    protected function invoice_balance()
    {
        $this->backend->SetFont('arial', 'B', 9);

        $balance = $this->data['customerbalance'];
        if ($balance > 0) {
            $comment = trans('(excess payment)');
        } elseif ($balance < 0) {
            $comment = trans('(underpayment)');
        } else {
            $comment = '';
        }
        if ($this->use_alert_color) {
            $this->backend->SetTextColorArray(array(255, 0, 0));
        }
        $this->backend->writeHTMLCell(
            0,
            0,
            '',
            '',
            trans(
                'Your balance on date of invoice issue: $a $b',
                moneyf($balance / $this->data['currencyvalue'], $this->data['currency']),
                $comment
            ),
            0,
            1,
            0,
            true,
            'L'
        );
        if ($this->use_alert_color) {
            $this->backend->SetTextColor();
        }
        $this->backend->writeHTMLCell(0, 0, '', '', trans('Balance includes current invoice'), 0, 1, 0, true, 'L');
    }

    protected function invoice_dates()
    {
        $this->backend->SetFont('arial', '', 8);
        if ($this->data['paytype'] != 8) {
            if ($this->use_alert_color) {
                    $this->backend->SetTextColorArray(array(255, 0, 0));
            }
            $this->backend->writeHTMLCell(0, 0, '', 17, trans('Deadline:') . '<b>' . date("d.m.Y", $this->data['pdate']) . '</b>', 0, 1, 0, true, 'R');
            if ($this->use_alert_color) {
                    $this->backend->SetTextColor();
            }
        }
        if (!ConfigHelper::checkConfig('invoices.hide_payment_type')) {
            $this->backend->writeHTMLCell(0, 0, '', '', trans('Payment type:') . '<b>' . $this->data['paytypename'] . '</b>', 0, 1, 0, true, 'R');
            if (!empty($this->data['splitpayment'])) {
                $this->backend->writeHTMLCell(0, 0, '', '', '<b>' . trans('(split payment)') . '</b>', 0, 1, 0, true, 'R');
            }
        }
    }

    protected function invoice_expositor()
    {
        $expositor = isset($this->data['user']) ? $this->data['user'] : $this->data['division_author'];
        $this->backend->SetFont('arial', '', 8);
        if (!ConfigHelper::checkConfig('invoices.hide_expositor')) {
            $this->backend->writeHTMLCell(0, 0, '', '', trans('Expositor:') . ' <b>' . (empty($expositor) ? trans('system') : $expositor) . '</b>', 0, 1, 0, true, 'R');
        }
    }

    protected function invoice_comment()
    {
        if (!empty($this->data['comment'])) {
            if (ConfigHelper::checkConfig('invoices.qr2pay') && !isset($this->data['rebate'])) {
                $width = 150;
            } else {
                $width = 0;
            }
            $this->backend->Ln(5);
            $this->backend->writeHTMLCell($width, 0, '', '', trans('Comment:') . ' ' . $this->data['comment'], 0, 1, 0, true, 'C');
        }
    }

    protected function invoice_footnote()
    {
        if (!empty($this->data['division_footer'])) {
            //$this->backend->Ln(145);
            //$this->backend->SetFont('arial', 'B', 10);
            //$this->backend->Write(0, trans('Notes:'), '', 0, 'L', true, 0, false, false, 0);
            $tmp = $this->data['division_footer'];

            $accounts = array(bankaccount($this->data['customerid'], $this->data['account']));
            if (ConfigHelper::checkConfig('invoices.show_all_accounts')) {
                $accounts = array_merge($accounts, $this->data['bankaccounts']);
            }
            foreach ($accounts as &$account) {
                $account = format_bankaccount($account);
            }
            $tmp = str_replace('%bankaccount', implode("\n", $accounts), $tmp);
            $tmp = str_replace('%bankname', $this->data['div_bank'], $tmp);

            $this->backend->SetFont('arial', '', 8);
            //$h = $this->backend->getStringHeight(0, $tmp);
            $tmp = mb_ereg_replace('\r?\n', '<br>', $tmp);
            if (ConfigHelper::checkConfig('invoices.qr2pay') && !isset($this->data['rebate'])) {
                $width = 150;
            } else {
                $width = 0;
            }
            $this->backend->Ln(5);
            $this->backend->writeHTMLCell($width, 0, '', '', $tmp, 0, 1, 0, true, 'C');
        }
    }

    public function invoice_header_image()
    {
        $image_path = ConfigHelper::getConfig('invoices.header_image', '', true);
        if (!file_exists($image_path)) {
            return;
        }
        $this->backend->writeHTMLCell(40, 0, 12, 8, '<img src="' . $image_path . '">');
    }

    public function invoice_cancelled()
    {
        if ($this->data['cancelled']) {
            $x = $this->backend->GetX();
            $y = $this->backend->GetY();

            $this->backend->setTextColorArray(array(128, 128, 128));
            $this->backend->StartTransform();
            $this->backend->SetFont('arial', '', 40);
            $this->backend->Rotate(45, 10, 210);
            $this->backend->Translate(30, 0);
            $this->backend->SetXY(10, 210);
            $this->backend->Write(0, trans('CANCELLED'), '', 0, 'C', true, 0, false, false, 0);
            $this->backend->StopTransform();
            $this->backend->setTextColorArray(array(0, 0, 0));

            $this->backend->SetXY($x, $y);
        }
    }

    public function invoice_no_accountant()
    {
        if ($this->data['dontpublish'] && !$this->data['cancelled']) {
            $x = $this->backend->GetX();
            $y = $this->backend->GetY();

            $this->backend->setTextColorArray(array(128, 128, 128));
            $this->backend->StartTransform();
            $this->backend->SetFont('arial', '', 40);
            $this->backend->Rotate(45, 10, 210);
            $this->backend->Translate(30, 0);
            $this->backend->SetXY(10, 210);
            $this->backend->Write(0, trans('NO ACCOUNTANT DOCUMENT'), '', 0, 'C', true, 0, false, false, 0);
            $this->backend->StopTransform();
            $this->backend->setTextColorArray(array(0, 0, 0));

            $this->backend->SetXY($x, $y);
        }
    }

    public function invoice_qr2pay_code()
    {
        $x = $this->backend->GetX();
        $y = $this->backend->GetY();

        $docnumber = docnumber(array(
            'number' => $this->data['number'],
            'template' => $this->data['template'],
            'cdate' => $this->data['cdate'],
            'customerid' => $this->data['customerid'],
        ));

        $this->backend->SetFont('arial', '', 7);
        $this->backend->writeHTMLCell(150, 0, '', '', trans("&nbsp; <BR> Scan and Pay <BR> You can make a transfer simply and quickly using your phone. <BR> To make a transfer, please scan QRcode on you smartphone in your bank's application."), 0, 1, 0, true, 'R');
        $tmp = '|PL|'.bankaccount($this->data['customerid'], $this->data['account']).'|'.str_pad($this->data['value'] * 100, 6, 0, STR_PAD_LEFT).'|'.$this->data['division_name'].'|' . trans('QR Payment for Internet Invoice no.').' '.$docnumber.'|||';
        $style['position'] = 'R';
        $this->backend->write2DBarcode($tmp, 'QRCODE,M', $x, $y, 30, 30, $style);
        unset($tmp);
    }

    public function invoice_body_standard()
    {
        $this->invoice_cancelled();
        $this->invoice_no_accountant();
        $this->invoice_header_image();
        $this->invoice_date();
        $this->invoice_dates();
        $this->invoice_title();
        $this->invoice_seller();
        $this->invoice_buyer();
        $this->invoice_recipient();
        $this->invoice_data();
        $this->invoice_to_pay();
        $this->invoice_expositor();
        if (ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.show_balance', true))) {
            $this->invoice_balance();
        }
        if (ConfigHelper::checkConfig('invoices.qr2pay') && !isset($this->data['rebate'])) {
            $this->invoice_qr2pay_code();
        }
        $this->invoice_comment();
        $this->invoice_footnote();

        $docnumber = docnumber(array(
            'number' => $this->data['number'],
            'template' => $this->data['template'],
            'cdate' => $this->data['cdate'],
            'customerid' => $this->data['customerid'],
        ));
        if ($this->data['doctype'] == DOC_INVOICE_PRO) {
            $this->backend->SetTitle(trans('Pro Forma Invoice No. $a', $docnumber));
        } else {
            $this->backend->SetTitle(trans('Invoice No. $a', $docnumber));
        }
        $this->backend->SetAuthor($this->data['division_name']);
        $this->backend->setBarcode($docnumber);

        /* setup your cert & key file */
        $cert = 'file://' . LIB_DIR . '/tcpdf/config/lms.cert';
        $key = 'file://' . LIB_DIR . '/tcpdf/config/lms.key';

        /* setup signature additional information */
        $info = array(
            'Name' => $this->data['division_name'],
            'Location' => trans('Invoices'),
            'Reason' => $this->data['doctype'] == DOC_INVOICE_PRO ? trans('Pro Forma Invoice No. $a', $docnumber) : trans('Invoice No. $a', $docnumber),
            'ContactInfo' => $this->data['division_author']
        );

        /* set document digital signature & protection */
        if (file_exists($cert) && file_exists($key)) {
            $this->backend->setSignature($cert, $key, 'lms-invoices', '', 1, $info);
            $this->backend->setSignatureAppearance(13, 10, 50, 20);
        }

        if (!$this->data['disable_protection'] && $this->data['protection_password']) {
            $this->backend->SetProtection(
                array('modify', 'copy', 'annot-forms', 'fill-forms', 'extract', 'assemble'),
                '',
                $this->data['protection_password'],
                1
            );
        }
    }

    public function invoice_body_ft0100()
    {
        $this->invoice_cancelled();
        $this->invoice_no_accountant();
        $this->invoice_header_image();
        $this->invoice_date();
        $this->invoice_dates();
        $this->invoice_title();
        $this->invoice_seller();
        $this->invoice_buyer();
        $this->invoice_recipient();
        $this->invoice_data();
        $this->invoice_to_pay();
        $this->invoice_expositor();
        if (ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.show_balance', true))) {
            $this->invoice_balance();
        }
        if (ConfigHelper::checkConfig('invoices.qr2pay') && !isset($this->data['rebate'])) {
            $this->invoice_qr2pay_code();
        }
        $this->invoice_comment();
        $this->invoice_footnote();
        if (($this->data['customerbalance'] < 0 || ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.always_show_form', true)))
            && !isset($this->data['rebate'])) {
            if ($this->backend->GetY() > 180) {
                $this->backend->AppendPage();
            }

            /* draw FT-0100 form */
            $this->invoice_simple_form_draw();
            $this->invoice_main_form_draw();
            /* fill FT-0100 form */
            $this->invoice_simple_form_fill();
            $this->invoice_main_form_fill();
        }

        $docnumber = docnumber(array(
            'number' => $this->data['number'],
            'template' => $this->data['template'],
            'cdate' => $this->data['cdate'],
            'customerid' => $this->data['customerid'],
        ));
        if ($this->data['doctype'] == DOC_INVOICE_PRO) {
            $this->backend->SetTitle(trans('Pro Forma Invoice No. $a', $docnumber));
        } else {
            $this->backend->SetTitle(trans('Invoice No. $a', $docnumber));
        }
        $this->backend->SetAuthor($this->data['division_name']);

        /* setup your cert & key file */
        $cert = 'file://' . LIB_DIR . '/tcpdf/config/lms.cert';
        $key = 'file://' . LIB_DIR . '/tcpdf/config/lms.key';

        /* setup signature additional information */
        $info = array(
            'Name' => $this->data['division_name'],
            'Location' => trans('Invoices'),
            'Reason' => $this->data['doctype'] == DOC_INVOICE_PRO ? trans('Pro Forma Invoice No. $a', $docnumber) : trans('Invoice No. $a', $docnumber),
            'ContactInfo' => $this->data['division_author']
        );

        /* set document digital signature & protection */
        if (file_exists($cert) && file_exists($key)) {
            $this->backend->setSignature($cert, $key, 'lms-invoices', '', 1, $info);
            $this->backend->setSignatureAppearance(13, 10, 50, 20);
        }

        if (!$this->data['disable_protection'] && $this->data['protection_password']) {
            $this->backend->SetProtection(
                array('modify', 'copy', 'annot-forms', 'fill-forms', 'extract', 'assemble'),
                '',
                $this->data['protection_password'],
                1
            );
        }
    }
}

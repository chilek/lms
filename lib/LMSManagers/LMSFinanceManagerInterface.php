<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C); 2001-2013 LMS Developers
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

/**
 * LMSFinanceManagerInterface
 * 
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
interface LMSFinanceManagerInterface
{
    public function GetCustomerTariffsValue($id);

    public function GetCustomerAssignments($id, $show_expired = false);

    public function DeleteAssignment($id);

    public function AddAssignment($data);

    public function SuspendAssignment($id, $suspend = TRUE);

    public function AddInvoice($invoice);

    public function InvoiceDelete($invoiceid);

    public function InvoiceContentDelete($invoiceid, $itemid = 0);

    public function GetInvoiceContent($invoiceid);

    public function GetNoteContent($id);

    public function TariffAdd($tariff);

    public function TariffUpdate($tariff);

    public function TariffDelete($id);

    public function GetTariff($id, $network = NULL);

    public function GetTariffs();

    public function TariffSet($id);

    public function TariffExists($id);

    public function ReceiptContentDelete($docid, $itemid = 0);

    public function DebitNoteContentDelete($docid, $itemid = 0);

    public function AddBalance($addbalance);

    public function DelBalance($id);

    public function GetPaymentList();

    public function GetPayment($id);

    public function GetPaymentName($id);

    public function GetPaymentIDByName($name);

    public function PaymentExists($id);

    public function PaymentAdd($paymentdata);

    public function PaymentDelete($id);

    public function PaymentUpdate($paymentdata);
    
    public function GetHostingLimits($customerid);
    
    public function GetTaxes($from = NULL, $to = NULL);
    
    public function CalcAt($period, $date);
}

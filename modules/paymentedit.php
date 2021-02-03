<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

if (!$LMS->PaymentExists($_GET['id'])) {
    $SESSION->redirect('?m=paymentlist');
}

if (isset($_POST['payment'])) {
    $payment = $_POST['payment'];
    
    foreach ($payment as $key => $value) {
        $payment[$key] = trim($value);
    }

    $payment['value'] = str_replace(',', '.', $payment['value']);
    
    if ($payment['creditor'] == '') {
        $error['creditor'] = trans('Creditor name is required!');
    }

    if ($payment['name'] == '') {
        $error['name'] = trans('Payment name is required!');
    } elseif ($LMS->GetpaymentIDByName($payment['name']) && $payment['name'] != $LMS->GetPaymentName($_GET['id'])) {
        $error['name'] = trans('Specified name is in use!');
    }

    if ($payment['value'] == '') {
        $error['value'] = trans('Payment value is required!');
    } elseif (!preg_match('/^[0-9.,]+$/', $payment['value'])) {
        $error['value'] = trans('Incorrect value!');
    }

    $period = sprintf('%d', $payment['period']);
    
    switch ($period) {
        case DAILY:
            $at = 0;
            break;

        case WEEKLY:
            $at = sprintf('%d', $payment['at']);
            if ($at < 1 || $at > 7) {
                $error['at'] = trans('Incorrect day of week (1-7)!');
            }
            break;
        
        case QUARTERLY:
            if (!preg_match('/^[0-9]{2}\/[0-9]{2}$/', trim($payment['at']))) {
                $error['at'] = trans('Incorrect date format!');
            } else {
                list($d,$m) = explode('/', trim($payment['at']));
                if ($d>30 || $d<1) {
                    $error['at'] = trans('Incorrect day of month (1-30)!');
                }
                if ($m>3 || $m<1) {
                    $error['at'] = trans('Incorrect month number (max.3)!');
                }
                
                $at = ($m-1) * 100 + $d;
            };
            break;
        
        case HALFYEARLY:
            if (!preg_match('/^[0-9]{2}\/[0-9]{2}$/', $payment['at']) && $payment['at']) {
                $error['at'] = trans('Incorrect date format! Enter date in DD/MM format!');
            } else {
                list($d,$m) = explode('/', $payment['at']);
                    
                if ($d>30 || $d<1 || ($d>28 && $m==2)) {
                    $error['at'] = trans('This month doesn\'t contain specified number of days');
                }
                if ($m>6 || $m<1) {
                    $error['at'] = trans('Incorrect month number (max.6)!');
                }
                $at = ($m-1) * 100 + $d;
            }
            break;
        
        case YEARLY:
            if (!preg_match('/^[0-9]{2}\/[0-9]{2}$/', trim($payment['at']))) {
                $error['at'] = trans('Incorrect date format!');
            } else {
                list($d,$m) = explode('/', trim($payment['at']));
                $ttime = mktime(12, 0, 0, $m, $d, 1990);
                $at = date('z', $ttime) + 1;
            }
            break;
        
        default: // MONTHLY
            $period = MONTHLY;
            $at = sprintf('%d', $payment['at']);
            if ($at == 0) {
                $at = 1 + date('d', time());
                if ($at > 28) {
                    $at = 1;
                }
            }
            if ($at < 1 || $at > 28) {
                $error['at'] = trans('Incorrect day of month (1-28)!');
            }
            break;
    }
    
    $payment['period'] = $period;
    $payment['id'] = $_GET['id'];

    if (!$error) {
        $payment['at'] = $at;
        $LMS->PaymentUpdate($payment);
        $SESSION->redirect('?m=paymentinfo&id='.$payment['id']);
    }
} else {
    $payment = $LMS->GetPayment($_GET['id']);

    if ($payment['period'] == YEARLY) {
        $payment['at'] = date('d/m', ($payment['at']-1)*86400);
    } elseif ($payment['period'] == QUARTERLY) {
        $payment['at'] = sprintf('%02d/%02d', ($payment['at']%100), $payment['at']/100+1);
    } elseif ($payment['period'] == HALFYEARLY) {
                $payment['at'] = sprintf('%02d/%02d', $payment['at']%100, $payment['at']/100+1);
    }
}
    
$layout['pagetitle'] = trans('Payment Edit: $a', $payment['name']);

$SMARTY->assign('payment', $payment);
$SMARTY->assign('error', $error);
$SMARTY->display('payment/paymentedit.html');

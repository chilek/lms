<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

$addbalance = isset($_POST['addbalance']) ? $_POST['addbalance'] : $_POST['instantpayment'];

foreach ($addbalance as $key => $value) {
    if (!is_array($value)) {
        $addbalance[$key] = trim($value);
    }
}

$addbalance['value'] = str_replace(',', '.', $addbalance['value']);

$currenttime = false;
if (isset($_POST['addbalance']) && !empty($addbalance['time'])) {
    $addbalance['time'] = datetime_to_timestamp($addbalance['time']);
    if (empty($addbalance['time'])) {
        $addbalance['time'] = time();
    }
} else {
    $addbalance['time'] = time();
    $currenttime = true;
}

if (isset($_POST['addbalance'])) {
    $SESSION->save('addbc', $addbalance['comment']);
}

if ($currenttime) {
    $SESSION->remove('addbt');
} else {
    $SESSION->save('addbt', $addbalance['time']);
}

$SESSION->save('addbtax', isset($addbalance['taxid']) ? $addbalance['taxid'] : 0);

if (!isset($addbalance['type'])) {
        $addbalance['type'] = 1;
}

if (!empty($addbalance['sourceid'])) {
    if (!$addbalance['type']) {
        $addbalance['sourceid'] = null;
    }
    $SESSION->save('addsource', $addbalance['sourceid']);
}

if ($addbalance['type'] == 0) {
    $addbalance['value'] *= -1;
} else {
    $addbalance['taxid'] = 0;
}

if (isset($addbalance['mcustomerid'])) {
    $addbalance['currencyvalue'] = $LMS->getCurrencyValue($addbalance['currency'], $addbalance['time']);
    if (!isset($addbalance['currencyvalue'])) {
        die('Fatal error: couldn\'t get quote for ' . $addbalance['currency'] . ' currency!<br>');
    }
    foreach ($addbalance['mcustomerid'] as $value) {
        if ($LMS->CustomerExists($value)) {
            $addbalance['customerid'] = $value;
            if ($addbalance['value'] != 0) {
                $LMS->AddBalance($addbalance);
            }
        }
    }
} elseif (isset($addbalance['customerid'])) {
    if ($LMS->CustomerExists($addbalance['customerid'])) {
        if ($addbalance['value'] != 0) {
            if ($addbalance['value'] > 0 && $addbalance['type'] == 1 && isset($_GET['receipt'])) {
                $cashregistries = $LMS->GetCashRegistries($addbalance['customerid']);
                $instantpayment = false;
                if (!empty($cashregistries)) {
                    if (count($cashregistries) == 1) {
                        $instantpayment = true;
                    } else {
                        $cashregistries = array_filter($cashregistries, function ($cashreg) {
                            return !empty($cashreg['isdefault']);
                        });
                        if (count($cashregistries) == 1) {
                            $instantpayment = true;
                        }
                    }
                }
                if ($instantpayment) {
                    // issues instant receipt
                    $liabilities = $LMS->GetOpenedLiabilities($addbalance['customerid']);
                    $cashregistry = reset($cashregistries);
                    $value = $addbalance['value'];
                    $payments = array();
                    foreach ($liabilities as $liability) {
                        $value_to_pay = $liability['value'] * -1;
                        $liability['value'] = min($value, $value_to_pay);
                        $liability['description'] = $liability['comment'];
                        $payments[] = $liability;
                        $value -= $value_to_pay;
                        $value = round($value, 2);
                        if ($value <= 0) {
                            break;
                        }
                    }
                    if ($value > 0) {
                        $payments[] = array(
                            'description' => $addbalance['comment'],
                            'value' => $value,
                        );
                    }
                    $receipt = array(
                        'number' => 0,
                        'numberplanid' => intval($cashregistry['in_numberplanid']),
                        'regid' => intval($cashregistry['id']),
                        'cdate' => time(),
                        'type' => 'in',
                        'customer' => $LMS->GetCustomer($addbalance['customerid'], true),
                        'contents' => $payments,
                    );
                    $rid = $LMS->AddReceipt($receipt);
                    if (!empty($rid) && isset($addbalance['print'])) {
                        $which = array();
                        if (!empty($_POST['original'])) {
                            $which[] = 'original';
                        }
                        if (!empty($_POST['copy'])) {
                            $which[] = 'copy';
                        }
                        if (empty($which)) {
                            $which = explode(',', ConfigHelper::getConfig('receipts.default_printpage', 'original,copy'));
                        }
                        $SESSION->save('receiptprint', array(
                            'receipt' => $rid,
                            'which' => implode(',', $which),
                        ), true);
                    }
                }
            } else {
                $addbalance['currencyvalue'] = $LMS->getCurrencyValue($addbalance['currency'], $addbalance['time']);
                if (!isset($addbalance['currencyvalue'])) {
                    die('Fatal error: couldn\'t get quote for ' . $addbalance['currency'] . ' currency!<br>');
                }
                $LMS->AddBalance($addbalance);
            }
        }
    }
} else {
    $addbalance['customerid'] = null;
    $addbalance['taxid'] = '0';
    $addbalance['type'] = '1';

    if ($addbalance['value'] != 0) {
        $addbalance['currencyvalue'] = $LMS->getCurrencyValue($addbalance['currency'], $addbalance['time']);
        if (!isset($addvalance['currencyvalue'])) {
            die('Fatal error: couldn\'t get quote for ' . $addbalance['currency'] . ' currency!<br>');
        }
        $LMS->AddBalance($addbalance);
    }
}

header('Location: ?'.$SESSION->get('backto'));

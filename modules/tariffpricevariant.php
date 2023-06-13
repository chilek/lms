<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2023 LMS Developers
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

$lms = LMS::getInstance();
$db = LMSDB::getInstance();
$error = array();

if (isset($_GET['oper'])) {
    switch ($_GET['oper']) {
        case 'add':
            $params = $_POST;
            $tariffId = $params['tariff_id'] ?? null;
            $quantityThreshold = $params['quantity_threshold'] ?? null;
            $grossPrice = isset($params['gross_price']) ? str_replace(',', '.', $params['gross_price']) : null;
            $netPrice = isset($params['net_price']) ? str_replace(',', '.', $params['net_price']) : null;

            if (!empty($tariffId)) {
                //<editor-fold desc="validation">
                if (!empty($quantityThreshold)) {
                    if (!preg_match('/^[0-9]+$/', $quantityThreshold)) {
                        $error['threshold_error'] = trans('Incorrect value!');
                    } elseif ($lms->checkTariffQuantityThresholdExists($tariffId, $quantityThreshold)) {
                        $error['threshold_error'] = trans('Quantity threshold already exists!');
                    }
                } else {
                    $error['threshold_error'] = trans('No value!');
                }

                if (!empty($grossPrice)) {
                    if (!preg_match('/^[-]?[0-9.,]+$/', $grossPrice)) {
                        $error['gross_price_error'] = trans('Incorrect value!');
                    }
                } else {
                    $error['gross_price_error'] = trans('No value!');
                }

                if (!empty($netPrice)) {
                    if (!preg_match('/^[-]?[0-9.,]+$/', $netPrice)) {
                        $error['net_price_error'] = trans('Incorrect value!');
                    }
                } else {
                    $error['net_price_error'] = trans('No value!');
                }

                if ($error) {
                    die(json_encode($error));
                }
                //</editor-fold>

                $args = array(
                    'tariff_id' => $tariffId,
                    'quantity_threshold' => $quantityThreshold,
                    'gross_price' => $grossPrice,
                    'net_price' => $netPrice
                );

                $db->BeginTrans();
                $db->LockTables('tariffpricevariants');
                $priceVariantId = $lms->addTariffPriceVariant($args);
                if (!empty($priceVariantId)) {
                    $db->CommitTrans();
                    $db->UnlockTables();
                    die(json_encode($lms->getTariffPriceVariant($priceVariantId)));
                } else {
                    $db->RollbackTrans();
                    $db->UnlockTables();
                }
            }
            break;
        case 'edit':
            $params = r_trim($_POST);
            $tariffId = $params['tariff_id'] ?? null;
            $tariffPriceVariantId = $params['tariff_price_variant_id'] ?? null;
            $quantityThreshold = $params['quantity_threshold'] ?? null;
            $grossPrice = isset($params['gross_price']) ? str_replace(',', '.', $params['gross_price']) : null;
            $netPrice = isset($params['net_price']) ? str_replace(',', '.', $params['net_price']) : null;

            if (!empty($tariffPriceVariantId)) {
                $oldTariffPriceVariantData = $lms->getTariffPriceVariant($tariffPriceVariantId);

                //<editor-fold desc="validation">
                if (!empty($quantityThreshold)) {
                    if ($oldTariffPriceVariantData['quantity_threshold'] != $quantityThreshold) {
                        if (!preg_match('/^[0-9]+$/', $quantityThreshold)) {
                            $error['threshold_error'] = trans('Incorrect value!');
                        } elseif ($lms->checkTariffQuantityThresholdExists($tariffId, $quantityThreshold)) {
                            $error['threshold_error'] = trans('Quantity threshold already exists!');
                        }
                    }
                } else {
                    $error['threshold_error'] = trans('No value!');
                }

                if (!empty($grossPrice)) {
                    if (!preg_match('/^[-]?[0-9.,]+$/', $grossPrice)
                        && $oldTariffPriceVariantData['gross_price'] != $grossPrice) {
                        $error['gross_price_error'] = trans('Incorrect value!');
                    }
                } else {
                    $error['gross_price_error'] = trans('No value!');
                }

                if (!empty($netPrice)) {
                    if (!preg_match('/^[-]?[0-9.,]+$/', $netPrice)
                        && $oldTariffPriceVariantData['net_price'] != $netPrice) {
                        $error['net_price_error'] = trans('Incorrect value!');
                    }
                } else {
                    $error['net_price_error'] = trans('No value!');
                }

                if ($error) {
                    die(json_encode($error));
                }
                //</editor-fold>

                $args = array(
                    'tariff_id' => $tariffId,
                    'tariff_price_variant_id' => $tariffPriceVariantId,
                    'quantity_threshold' => $quantityThreshold,
                    'gross_price' => $grossPrice,
                    'net_price' => $netPrice
                );

                $db->BeginTrans();
                $priceVariantId = $lms->updateTariffPriceVariant($args);
                if (!empty($priceVariantId)) {
                    $db->CommitTrans();
                    die(json_encode($lms->getTariffPriceVariant($priceVariantId)));
                } else {
                    $db->RollbackTrans();
                }
            }
            break;
        case 'del':
            $lms->delTariffPriceVariant($_GET['id']);
            break;
    }
}

die('[]');

<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2023 LMS Developers
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

class LMSTariffPriceVariantManager extends LMSManager implements LMSTariffPriceVariantManagerInterface
{
    public function addTariffPriceVariant($params): int
    {
        $args = array(
            SYSLOG::RES_TARIFF => $params['tariff_id'],
            'quantity_threshold' => $params['quantity_threshold'],
            'net_price' => $params['net_price'],
            'gross_price' => $params['gross_price'],
        );

        $res = $this->db->Execute(
            'INSERT INTO tariffpricevariants (tariffid, quantity_threshold, net_price, gross_price)
            VALUES (?, ?, ?, ?)',
            array_values($args)
        );

        if ($res) {
            $id = $this->db->GetLastInsertID('tariffpricevariants');
            if ($this->syslog) {
                $args = array(
                    SYSLOG::RES_TARIFF_PRICE_VARIANT => $id,
                    SYSLOG::RES_TARIFF => $params['tariff_id'],
                    'quantity_threshold' => $params['quantity_threshold'],
                    'net_price' => $params['net_price'],
                    'gross_price' => $params['gross_price'],
                );
                $this->syslog->AddMessage(SYSLOG::RES_TARIFF_PRICE_VARIANT, SYSLOG::OPER_ADD, $args);
            }
        } else {
            $id = 0;
        }

        return $id;
    }

    public function updateTariffPriceVariant($params): int
    {
        $price_variant_id = $params['tariff_price_variant_id'];
        $args = array(
            'quantity_threshold' => $params['quantity_threshold'],
            'net_price' => $params['net_price'],
            'gross_price' => $params['gross_price'],
            SYSLOG::RES_TARIFF_PRICE_VARIANT => $price_variant_id,
        );

        $res = $this->db->Execute(
            'UPDATE tariffpricevariants SET quantity_threshold = ?, net_price = ?, gross_price = ?
            WHERE id = ?',
            array_values($args)
        );

        if ($res) {
            if ($this->syslog) {
                $this->syslog->AddMessage(SYSLOG::RES_TARIFF_PRICE_VARIANT, SYSLOG::OPER_UPDATE, $args);
            }
        } else {
            $price_variant_id = 0;
        }

        return $price_variant_id;
    }

    public function delTariffPriceVariant($tariff_price_variant_id)
    {
        $price_variant_id = intval($tariff_price_variant_id);

        $res = $this->db->Execute('DELETE FROM tariffpricevariants WHERE id = ?', array($price_variant_id));

        if ($res && $this->syslog) {
            $args = array(
                SYSLOG::RES_TARIFF_PRICE_VARIANT => $price_variant_id
            );
            $this->syslog->AddMessage(SYSLOG::RES_TARIFF_PRICE_VARIANT, SYSLOG::OPER_DELETE, $args);
        }

        return $res;
    }

    public function getTariffPriceVariant($tariff_price_variant_id)
    {
        return $this->db->GetRow(
            'SELECT * FROM tariffpricevariants
             WHERE id = ?',
            array(
                intval($tariff_price_variant_id)
            )
        );
    }

    public function getTariffPriceVariantByQuantityThreshold($tariff_id, $quantity)
    {
        $priceVariant = array();
        if (!empty($tariff_id) && !empty($quantity)) {
            $priceVariants = $this->getTariffPriceVariants($tariff_id);
            // $priceVariants are ordered by threshold acs

            if (!empty($priceVariants)) {
                foreach ($priceVariants as $price_variant) {
                    $upThreshold = intval($price_variant['quantity_threshold']);
                    if ($quantity > $upThreshold) {
                        $priceVariant = $price_variant;
                    } else {
                        break;
                    }
                }
            }
        }

        return $priceVariant;
    }

    public function getTariffPriceVariants($tariff_id)
    {
        return $this->db->GetAllByKey(
            'SELECT tpv.*, t.currency FROM tariffpricevariants tpv
             JOIN tariffs t on tpv.tariffid = t.id   
             WHERE tariffid = ?
             ORDER BY quantity_threshold ASC',
            'id',
            array(
                intval($tariff_id)
            )
        );
    }

    public function checkTariffQuantityThresholdExists($tariff_id, $quantity_threshold)
    {
        return $this->db->GetRow(
            'SELECT * FROM tariffpricevariants
            WHERE tariffid = ?
            AND quantity_threshold = ?',
            array(
                intval($tariff_id),
                intval($quantity_threshold)
            )
        );
    }

    public function recalculateTariffPriceVariants($tariff, $calculation_method)
    {
        $tariffPriceVariants = $this->getTariffPriceVariants($tariff['id']);

        if (!empty($tariffPriceVariants)) {
            $tariffTaxValue = $this->db->GetOne('SELECT value FROM taxes WHERE id = ?', array($tariff['taxid']));

            foreach ($tariffPriceVariants as $tariffPriceVariant) {
                $netPrice = $tariffPriceVariant['net_price'];
                $grossPrice = $tariffPriceVariant['gross_price'];

                if ($calculation_method == 'from_net') {
                    $grossPrice = f_round($netPrice * ($tariffTaxValue / 100 + 1), 3);
                    $grossPrice = str_replace(',', '.', $grossPrice);
                }

                if ($calculation_method == 'from_gross') {
                    $netPrice = f_round($grossPrice / ($tariffTaxValue / 100 + 1), 3);
                    $netPrice = str_replace(',', '.', $netPrice);
                }

                $args = array(
                    'tariff_price_variant_id' => $tariffPriceVariant['id'],
                    'quantity_threshold' => $tariffPriceVariant['quantity_threshold'],
                    'gross_price' => $grossPrice,
                    'net_price' => $netPrice
                );
                $this->updateTariffPriceVariant($args);
            }
        }
    }
}

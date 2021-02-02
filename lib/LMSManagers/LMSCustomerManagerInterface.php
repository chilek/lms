<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C); 2001-2017 LMS Developers
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
 * LMSCustomerManagerInterface
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
interface LMSCustomerManagerInterface
{
    public function getCustomerName($id);

    public function getCustomerEmail($id, $requiredFlags = 0, $forbiddenFlags = 0);

    public function customerExists($id);

    public function getCustomerNodesNo($id);

    public function getCustomerIDByIP($ipaddr);

    public function getCustomerStatus($id);

    public function getCustomerNames();

    public function getAllCustomerNames();

    public function getCustomerNodesAC($id);

    public function getCustomerBalance($id, $totime = null, $expired = false);

    public function getCustomerBalanceList($id, $totime = null, $direction = 'ASC', $aggregate_documents = false);

    public function GetCustomerShortBalanceList($customerid, $limit = 10, $order = 'DESC');

    public function getLastNInTable($body, $customerid, $format, $aggregate_documents = false);

    public function customerStats();

    public function updateCustomerConsents($customerid, $current_consents, $new_consents);

    public function customerAdd($customeradd);

    public function getCustomerList($params);

    public function getCustomerNodes($id, $count = null);

    public function getCustomerNetDevs($customer_id);

    public function GetCustomerNetworks($id, $count = null);

    public function getCustomerConsents($id);

    public function GetCustomer($id, $short = false);

    public function customerUpdate($customerdata);

    public function deleteCustomer($id);

    public function deleteCustomerPermanent($id);

    public function checkCustomerAddress($a_id, $c_id);

    public function determineDefaultCustomerAddress(array &$caddr);

    public function getCustomerAddresses($id, $hide_deleted);

    public function getAddressForCustomerStuff($customer_id);

    public function getFullAddressForCustomerStuff($customer_id);

    public function isTerritAddress($address_id);

    public function GetCustomerContacts($id, $mask = null);

    public function GetCustomerDivision($id);

    public function isSplitPaymentSuggested($customerid, $cdate, $value);

    public function isTelecomServiceSuggested($customerid);

    public function getCustomerSMSOptions();

    public function GetCustomerAddressesWithoutEndPoints($customerid);

    public function checkCustomerTenExistence($customerid, $ten, $divisionid = null);

    public function checkCustomerSsnExistence($customerid, $ssn, $divisionid = null);

    public function checkCustomerConsent($customerid, $consent);

    public function customerNotificationReplaceSymbols($string, $data);

    public function addCustomerConsents($customerid, $consents);

    public function removeCustomerConsents($customerid, $consents);

    public function addCustomerContactFlags($customerid, $type, $flags);

    public function removeCustomerContactFlags($customerid, $type, $flags);

    public function getCustomerNotes($cid);

    public function getCustomerNote($id);

    public function addCustomerNote($params);

    public function delCustomerNote($id);

    public function raiseCustomerKarma($id);

    public function lowerCustomerKarma($id);

    public function getCustomerPin($id);
}

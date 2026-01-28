<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C); 2001-2022 LMS Developers
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

    public function getLastNInTable($body, $customerid, $format, $aggregate_documents = false, $reverse_order = true, $item_description_format = null);

    public function customerStats();

    public function updateCustomerConsents($customerid, $current_consents, $new_consents, $consent_mask = null);

    public function customerAdd($customeradd);

    public function getCustomerList($params);

    public function getCustomerNodes($id, $count = null);

    public function getCustomerNetDevs($customer_id);

    public function GetCustomerNetworks($id, $count = null);

    public function getCustomerConsents($id);

    public function getCustomerSensitiveData($id);

    public function GetCustomer($id, $short = false);

    public function GetCustomerAltName($id);

    public function customerUpdate($customerdata);

    public function deleteCustomer($id);

    public function deleteCustomerPermanent($id, $transaction = true);

    public function restoreCustomer($id);

    public function checkCustomerAddress($a_id, $c_id);

    public function determineDefaultCustomerAddress(array &$caddr);

    public function getCustomerAddresses($id, $hide_deleted);

    public function getAddressForCustomerStuff($customer_id);

    public function getFullAddressForCustomerStuff($customer_id);

    public function detectCustomerLocationAddress($customer_id);

    public function isTerritAddress($address_id);

    public function GetCustomerContacts($id, $mask = null);

    public function GetCustomerDivision($id);

    public function isSplitPaymentSuggested($customerid, $cdate, $value);

    public function isTelecomServiceSuggested($customerid);

    public function getCustomerSMSOptions();

    public function getCustomerAddressesWithOrWithoutEndPoints($customerid, $with = true);

    public function GetCustomerAddressesWithEndPoints($customerid);

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

    public function updateCustomerNote($params);

    public function delCustomerNote($id);

    public function raiseCustomerKarma($id);

    public function lowerCustomerKarma($id);

    public function getCustomerPin($id);

    public function getCustomerPinRequirements();

    public function checkCustomerPin($id, $pin);

    public function getCustomerTen($id);

    public function getCustomerSsn($id);

    public function changeCustomerType($id, $tyoe);

    public function changeCustomerStatus($id, $status);

    public function getCustomerCalls(array $params);

    public function deleteCustomerCall($id, $callid);

    public function getCustomerCallContent($callid);

    public function isCustomerCallExists(array $params);

    public function addCustomerCall(array $params);

    public function updateCustomerCall($callid, array $params);

    public function addCustomerCallAssignment($customerid, $callid);

    public function getCustomerModificationInfo($customerid);

    public function getCustomerExternalIDs($customerid, $serviceproviderid = null, $serviceprovidersonly = false);

    public function addCustomerExternalID($customerid, $extid, $serviceproviderid);

    public function updateCustomerExternalID($customerid, $extid, $oldextid, $serviceproviderid, $oldserviceproviderid);

    public function updateCustomerExternalIDs($customerid, array $customerextids, $only_passed_service_providers = false);

    public function deleteCustomerExternalID($customerid, $extid, $serviceproviderid);

    public function getServiceProviders();
}

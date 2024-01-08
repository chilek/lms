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
$customerId = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
$extId = isset($_POST['ext_id']) ? strval(trim($_POST['ext_id'])) : null;
$oldExtId = isset($_POST['old_ext_id']) ? strval($_POST['old_ext_id']) : null;
$serviceProviderId = isset($_POST['service_provider_id']) ? intval($_POST['service_provider_id']) : null;
$oldServiceProviderId = isset($_POST['old_service_provider_id']) ? intval($_POST['old_service_provider_id']) : null;
$error = array();

if (isset($_GET['oper']) && !empty($customerId)) {
    switch ($_GET['oper']) {
        case 'add':
            // validate service provider
            if (!empty($serviceProviderId)) {
                $extidForProvider = $lms->getCustomerExternalIDs($customerId, $serviceProviderId);
                if (!empty($extidForProvider)) {
                    $error['provider_error'] = trans('Selected service provider has assigned External ID already!');
                }
            } else {
                $error['provider_error'] = trans('No external system!');
            }

            if ($error) {
                die(json_encode($error));
            }

            if (!empty($extId)) {
                $result = $lms->addCustomerExternalID($customerId, $extId, $serviceProviderId);
                if (!empty($result)) {
                    die(json_encode(array('result' => 1)));
                }
            }
            break;
        case 'edit':
            if (!empty($serviceProviderId)) {
                $extidForProvider = $lms->getCustomerExternalIDs($customerId, $serviceProviderId);
                if (!empty($extidForProvider) && !empty($oldServiceProviderId) && $extidForProvider[$serviceProviderId]['serviceproviderid'] != $oldServiceProviderId) {
                    $error['provider_error'] = trans('Selected service provider has assigned External ID already!');
                }
            } else {
                $error['provider_error'] = trans('No external system!');
            }

            if ($error) {
                die(json_encode($error));
            }

            if (!empty($extId)) {
                $result = $lms->updateCustomerExternalID($customerId, $extId, $oldExtId, $serviceProviderId, $oldServiceProviderId);
                if (!empty($result)) {
                    die(json_encode(array('result' => 1)));
                }
            }
            break;
        case 'del':
            if (!empty($serviceProviderId) && !empty($extId)) {
                $lms->deleteCustomerExternalID($customerId, $extId, $serviceProviderId);
            }
            break;
        case 'showextids':
            die(json_encode($serviceproviders));
    }
}

die('[]');

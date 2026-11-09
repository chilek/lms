<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2026 LMS Developers
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

$invoiceSendContacts = $LMS->GetCustomerContacts($customerid, CONTACT_EMAIL);

$customerDivisionId = $LMS->GetCustomerDivision($customerid);
$currentDivisionId = LMSDivisionManager::getCurrentDivision();
if ($customerDivisionId != $currentDivisionId) {
    ConfigHelper::setFilter($customerDivisionId, Auth::GetCurrentUser());
}

$invoiceSendSubject = ConfigHelper::getConfig('sendinvoices.mail_subject', 'Invoice No. %invoice');
$invoiceSendBody = ConfigHelper::getConfig('sendinvoices.mail_body', ConfigHelper::getConfig('mail.sendinvoice_mail_body'));

if ($customerDivisionId != $currentDivisionId) {
    ConfigHelper::setFilter($currentDivisionId, Auth::GetCurrentUser());
}

if (!empty($invoiceSendBody) && $invoiceSendBody[0] == DIRECTORY_SEPARATOR && is_readable($invoiceSendBody)) {
    $invoiceSendBody = file_get_contents($invoiceSendBody);
}

$SMARTY->assign(array(
    'invoiceSendContacts' => $invoiceSendContacts,
    'invoiceSendSubject' => $invoiceSendSubject,
    'invoiceSendBody' => $invoiceSendBody,
    'invoiceSendCustomerId' => $customerid,
));

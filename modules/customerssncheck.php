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

header('Content-type: application/json');

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    die('[]');
}

if (!isset($_GET['ssn']) || !ctype_digit($_GET['ssn'])) {
    die('[]');
}

try {
    $result = Utils::checkPeselReservationStatus($_GET['ssn']);
} catch (Exception $e) {
    if ($SYSLOG) {
        $SYSLOG->AddMessage(
            SYSLOG::RES_CUST,
            SYSLOG::OPER_SSN_RESERVATION_CHECK_ERROR,
            array(
                SYSLOG::RES_CUST => $_GET['id'],
                'ssn' => $_GET['ssn'],
                'error' => $e->getMessage(),
            )
        );
    }

    die(json_encode(array('error' => $e->getMessage())));
}

if ($SYSLOG) {
    if (!isset($result['error'], $result['errors'])) {
        $SYSLOG->AddMessage(
            SYSLOG::RES_CUST,
            SYSLOG::OPER_SSN_RESERVATION_CHECK,
            array(
                SYSLOG::RES_CUST => $_GET['id'],
                'ssn' => $_GET['ssn'],
                'reserved' => $result['reserved'] ? 1 : 0,
            )
        );
    } else {
        $SYSLOG->AddMessage(
            SYSLOG::RES_CUST,
            SYSLOG::OPER_SSN_RESERVATION_CHECK_ERROR,
            array(
                SYSLOG::RES_CUST => $_GET['id'],
                'ssn' => $_GET['ssn'],
                'error' => isset($result['error']) ? $result['error'] : implode(', ', $result['errors']),
            )
        );
    }
}

die(json_encode($result));

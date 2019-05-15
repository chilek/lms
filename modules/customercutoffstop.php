<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

$customerid = intval($_GET['customerid']);

if (!$LMS->CustomerExists($customerid)) {
    $SESSION->redirect('?m=customerlist');
}

if (isset($_GET['cutoffstop'])) {
    if (isset($_GET['cutoffstopindefinitely'])) {
        $cutoffstop = intval(pow(2, 31) - 1);
    } elseif ($_GET['cutoffstop'] == '') {
        $cutoffstop = 0;
    } elseif (check_date($_GET['cutoffstop'])) {
        list ($y, $m, $d) = explode('/', $_GET['cutoffstop']);
        if (checkdate($m, $d, $y)) {
            $cutoffstop = mktime(23, 59, 59, $m, $d, $y);
        }
    }
    // excluded groups check
    if (!$DB->GetOne(
        'SELECT 1 FROM customerassignments a
			JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
			WHERE e.userid = lms_current_user() AND a.customerid = ?',
        array($customerid)
    )) {
        $args = array(
            'cutoffstop' => $cutoffstop,
            SYSLOG::RES_CUST => $customerid,
        );
        $DB->Execute('UPDATE customers SET cutoffstop = ? WHERE id = ?', array_values($args));
        if ($SYSLOG) {
            $SYSLOG->AddMessage(SYSLOG::RES_CUST, SYSLOG::OPER_UPDATE, $args);
        }
    }
}

$SESSION->redirect('?'.$SESSION->get('backto'));

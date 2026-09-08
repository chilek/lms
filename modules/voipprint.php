<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2025 LMS Developers
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

if (!ConfigHelper::checkConfig('privileges.superuser') && !ConfigHelper::checkConfig('privileges.reports')) {
    access_denied();
}

$type = isset($_GET['type']) ? $_GET['type'] : '';

switch ($type) {
    case 'summary':
        if (!empty($_POST['datefrom'])) {
            $datefrom = strtotime($_POST['datefrom']);
            if ($datefrom === false) {
                $datefrom = 0;
            }
        } else {
            $datefrom = 0;
        }

        if (!empty($_POST['dateto'])) {
            $dateto = strtotime($_POST['dateto']);
            if ($dateto === false) {
                $dateto = 0;
            } else {
                $dateto = strtotime('tomorrow', $dateto) - 1;
            }
        } else {
            $dateto = 0;
        }

        if (!empty($_POST['domestic-prefix'])) {
            $domestic_prefix = $_POST['domestic-prefix'];
        } else {
            $domestic_prefix = '48';
        }

        $summary = $DB->GetRow(
            'SELECT
                COUNT(CASE WHEN c1.type = ? AND r.callee ?LIKE? ? THEN 1 ELSE NULL END) AS outgoing_domestic_private_count,
                COUNT(CASE WHEN c1.type = ? AND r.callee ?LIKE? ? THEN 1 ELSE NULL END) AS outgoing_domestic_business_count,
                COUNT(CASE WHEN c1.type = ? AND r.callee NOT ?LIKE? ? THEN 1 ELSE NULL END) AS outgoing_international_private_count,
                COUNT(CASE WHEN c1.type = ? AND r.callee NOT ?LIKE? ? THEN 1 ELSE NULL END) AS outgoing_international_business_count,

                SUM(CASE WHEN c1.type = ? AND r.callee ?LIKE? ? THEN r.billedtime ELSE NULL END) AS outgoing_domestic_private_time,
                SUM(CASE WHEN c1.type = ? AND r.callee ?LIKE? ? THEN r.billedtime ELSE NULL END) AS outgoing_domestic_business_time,
                SUM(CASE WHEN c1.type = ? AND r.callee NOT ?LIKE? ? THEN r.billedtime ELSE NULL END) AS outgoing_international_private_time,
                SUM(CASE WHEN c1.type = ? AND r.callee NOT ?LIKE? ? THEN r.billedtime ELSE NULL END) AS outgoing_international_business_time,

                SUM(CASE WHEN c1.type = ? AND r.callee ?LIKE? ? THEN r.price ELSE NULL END) AS outgoing_domestic_private_cost,
                SUM(CASE WHEN c1.type = ? AND r.callee ?LIKE? ? THEN r.price ELSE NULL END) AS outgoing_domestic_business_cost,
                SUM(CASE WHEN c1.type = ? AND r.callee NOT ?LIKE? ? THEN r.price ELSE NULL END) AS outgoing_international_private_cost,
                SUM(CASE WHEN c1.type = ? AND r.callee NOT ?LIKE? ? THEN r.price ELSE NULL END) AS outgoing_international_business_cost,

                COUNT(CASE WHEN c2.type = ? AND r.caller ?LIKE? ? THEN 1 ELSE NULL END) AS incoming_domestic_private_count,
                COUNT(CASE WHEN c2.type = ? AND r.caller ?LIKE? ? THEN 1 ELSE NULL END) AS incoming_domestic_business_count,
                COUNT(CASE WHEN c2.type = ? AND r.caller NOT ?LIKE? ? THEN 1 ELSE NULL END) AS incoming_international_private_count,
                COUNT(CASE WHEN c2.type = ? AND r.caller NOT ?LIKE? ? THEN 1 ELSE NULL END) AS incoming_international_business_count,

                SUM(CASE WHEN c2.type = ? AND r.caller ?LIKE? ? THEN r.billedtime ELSE NULL END) AS incoming_domestic_private_time,
                SUM(CASE WHEN c2.type = ? AND r.caller ?LIKE? ? THEN r.billedtime ELSE NULL END) AS incoming_domestic_business_time,
                SUM(CASE WHEN c2.type = ? AND r.caller NOT ?LIKE? ? THEN r.billedtime ELSE NULL END) AS incoming_international_private_time,
                SUM(CASE WHEN c2.type = ? AND r.caller NOT ?LIKE? ? THEN r.billedtime ELSE NULL END) AS incoming_international_business_time,

                SUM(CASE WHEN c2.type = ? AND r.caller ?LIKE? ? THEN r.price ELSE NULL END) AS incoming_domestic_private_cost,
                SUM(CASE WHEN c2.type = ? AND r.caller ?LIKE? ? THEN r.price ELSE NULL END) AS incoming_domestic_business_cost,
                SUM(CASE WHEN c2.type = ? AND r.caller NOT ?LIKE? ? THEN r.price ELSE NULL END) AS incoming_international_private_cost,
                SUM(CASE WHEN c2.type = ? AND r.caller NOT ?LIKE? ? THEN r.price ELSE NULL END) AS incoming_international_business_cost
            FROM voip_cdr r
            LEFT JOIN voipaccounts va1 ON va1.id = r.callervoipaccountid
            LEFT JOIN customers c1 ON c1.id = va1.ownerid
            LEFT JOIN voipaccounts va2 ON va2.id = r.calleevoipaccountid
            LEFT JOIN customers c2 ON c2.id = va2.ownerid
            WHERE r.call_start_time >= ?
                AND r.call_start_time <= ?',
            array(
                CTYPES_PRIVATE,
                $domestic_prefix . '%',
                CTYPES_COMPANY,
                $domestic_prefix . '%',
                CTYPES_PRIVATE,
                $domestic_prefix . '%',
                CTYPES_COMPANY,
                $domestic_prefix . '%',
                CTYPES_PRIVATE,
                $domestic_prefix . '%',
                CTYPES_COMPANY,
                $domestic_prefix . '%',
                CTYPES_PRIVATE,
                $domestic_prefix . '%',
                CTYPES_COMPANY,
                $domestic_prefix . '%',
                CTYPES_PRIVATE,
                $domestic_prefix . '%',
                CTYPES_COMPANY,
                $domestic_prefix . '%',
                CTYPES_PRIVATE,
                $domestic_prefix . '%',
                CTYPES_COMPANY,
                $domestic_prefix . '%',
                CTYPES_PRIVATE,
                $domestic_prefix . '%',
                CTYPES_COMPANY,
                $domestic_prefix . '%',
                CTYPES_PRIVATE,
                $domestic_prefix . '%',
                CTYPES_COMPANY,
                $domestic_prefix . '%',
                CTYPES_PRIVATE,
                $domestic_prefix . '%',
                CTYPES_COMPANY,
                $domestic_prefix . '%',
                CTYPES_PRIVATE,
                $domestic_prefix . '%',
                CTYPES_COMPANY,
                $domestic_prefix . '%',
                CTYPES_PRIVATE,
                $domestic_prefix . '%',
                CTYPES_COMPANY,
                $domestic_prefix . '%',
                CTYPES_PRIVATE,
                $domestic_prefix . '%',
                CTYPES_COMPANY,
                $domestic_prefix . '%',
                $datefrom,
                $dateto,
            )
        );

        $layout['pagetitle'] = trans('VoIP summary report');

        $SMARTY->assign('datefrom', $datefrom);
        $SMARTY->assign('dateto', $dateto);
        $SMARTY->assign('summary', $summary);

        $print_template = 'print/printvoipsummary.html';

        if (strtolower(ConfigHelper::getConfig('phpui.report_type', 'html')) == 'pdf') {
            $output = $SMARTY->fetch("$print_template");
            Utils::html2pdf(array(
                'content' => $output,
                'subject' => trans('Reports'),
                'title' => $layout['pagetitle'],
                'orientation' => 'L',
            ));
        } else {
            $SMARTY->display("$print_template");
        }

        break;

    default:
        /*******************************************************/

        $layout['pagetitle'] = trans('Reports');

        $SMARTY->assign('domestic_prefix', '48');

        $SMARTY->assign('printmenu', 'voip');
        $SMARTY->display('print/printindex.html');

        break;
}

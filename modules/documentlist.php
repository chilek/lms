<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

if (!isset($_GET['init'])) {
    if (isset($_GET['o'])) {
        $filter['order'] = $_GET['o'];
    }

    if (isset($_GET['t'])) {
        if (is_array($_GET['t'])) {
            $filter['type'] = array_filter(Utils::filterIntegers($_GET['t']), function ($type) {
                return !empty($type);
            });
            if (empty($filter['type'])) {
                $filter['type'] = 0;
            }
        } else {
            $filter['type'] = intval($_GET['t']);
        }
    }

    if (isset($_GET['service'])) {
        if (is_array($_GET['service'])) {
            $filter['service'] = Utils::filterIntegers($_GET['service']);
            if (count($filter['service']) == 1) {
                $first = reset($filter['service']);
                if ($first == 0) {
                    $filter['service'] = 0;
                }
            }
        } else {
            $filter['service'] = intval($_GET['service']);
        }
    }

    if (isset($_GET['c'])) {
        $filter['customer'] = $_GET['c'];
    }

    if (isset($_GET['p'])) {
        $filter['numberplan'] = $_GET['p'];
    }

    if (isset($_GET['usertype'])) {
        $filter['usertype'] = $_GET['usertype'];
    }
    if (!isset($filter['usertype']) || empty($filter['usertype'])) {
        $filter['usertype'] = 'creator';
    }

    if (isset($_GET['u'])) {
        if (is_array($_GET['u'])) {
            $filter['userid'] = Utils::filterIntegers($_GET['u']);
            if (count($filter['userid']) == 1) {
                $first = reset($filter['userid']);
                if ($first == 0) {
                    $filter['userid'] = 0;
                }
            }
        } else {
            $filter['userid'] = intval($_GET['u']);
        }
    }

    if (isset($_GET['periodtype'])) {
        $filter['periodtype'] = $_GET['periodtype'];
    }
    if (!isset($filter['periodtype']) || empty($filter['periodtype'])) {
        $filter['periodtype'] = 'creationdate';
    }

    if (isset($_GET['from'])) {
        if ($_GET['from'] != '') {
            list ($year, $month, $day) = explode('/', $_GET['from']);
            $filter['from'] = mktime(0, 0, 0, $month, $day, $year);
        } else {
            $filter['from'] = 0;
        }
    } elseif (!isset($filter['from'])) {
        $filter['from'] = 0;
    }

    if (isset($_GET['to'])) {
        if ($_GET['to'] != '') {
            list ($year, $month, $day) = explode('/', $_GET['to']);
            $filter['to'] = mktime(23, 59, 59, $month, $day, $year);
        } else {
            $filter['to'] = 0;
        }
    } elseif (!isset($filter['to'])) {
        $filter['to'] = 0;
    }

    if (isset($_GET['s'])) {
        $filter['status'] = $_GET['s'];
    } elseif (!isset($filter['status'])) {
        $filter['status'] = -1;
    }

    if (isset($_GET['archived'])) {
        $filter['archived'] = $_GET['archived'];
    } elseif (!isset($filter['archived'])) {
        $filter['archived'] = -1;
    }
} else {
    $filter = array(
        'status' => -1,
        'archived' => -1,
    );
    $SMARTY->clearAssign('persistent_filter');
    $SESSION->saveFilter($filter);
}

if (isset($_GET['init'])) {
    $default_current_period = ConfigHelper::getConfig('phpui.documentlist_default_current_period', '', true);
    if (preg_match('/^(day|month)$/', $default_current_period)) {
        list ($year, $month, $day) = explode('/', date('Y/m/d'));
        if ($default_current_period == 'day') {
            $filter['from'] = mktime(0, 0, 0, $month, $day, $year);
        } else {
            $filter['from'] = mktime(0, 0, 0, $month, 1, $year);
        }
    }
}

$filter['count'] = true;
$filter['total'] = intval($LMS->GetDocumentList($filter));

$filter['limit'] = intval(ConfigHelper::getConfig('phpui.documentlist_pagelimit', 100));
$filter['page'] = intval($_GET['page'] ?? ceil($filter['total'] / $filter['limit']));
if (empty($filter['page'])) {
    $filter['page'] = 1;
}
$filter['offset'] = ($filter['page'] - 1) * $filter['limit'];

$filter['count'] = false;
$documentlist = $LMS->GetDocumentList($filter);

//if (isset($_GET['init']) && isset($filter['from'])) {
//    $from = $filter['from'];
//    unset($filter['from']);
//    $SESSION->saveFilter($filter);
//    $filter['from'] = $from;
//} else {
    $SESSION->saveFilter($filter);
//}

$pagination = LMSPaginationFactory::getPagination(
    $filter['page'],
    $filter['total'],
    $filter['limit'],
    ConfigHelper::checkConfig('phpui.short_pagescroller')
);

$filter['order'] = $documentlist['order'];
$filter['direction'] = $documentlist['direction'];

unset($documentlist['total']);
unset($documentlist['order']);
unset($documentlist['direction']);

$layout['pagetitle'] = trans('Documents List');

$SESSION->add_history_entry();

if ($docid = $SESSION->get('documentprint')) {
    $SMARTY->assign('docid', $docid);
    $SMARTY->assign('attachments', $SESSION->get('document-with-attachments'));
    $SESSION->remove('documentprint');
    $SESSION->remove('document-with-attachments');
}

if ($filter['total']) {
    $SMARTY->assign('docrights', $DB->GetAllByKey('SELECT doctype, rights
			FROM docrights WHERE userid = ? AND rights > 1', 'doctype', array(Auth::GetCurrentUser())));
}

if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customers', $LMS->GetCustomerNames());
}

$SMARTY->assign('users', $LMS->GetUserNames());
$SMARTY->assign('numberplans', $LMS->GetNumberPlans(array(
    'doctype' => array(DOC_CONTRACT, DOC_ANNEX, DOC_PROTOCOL, DOC_ORDER, DOC_SHEET, -6, -7, -8, -9, -99, DOC_PRICE_LIST, DOC_PROMOTION, DOC_WARRANTY, DOC_REGULATIONS, DOC_OTHER),
)));
$SMARTY->assign('documentlist', $documentlist);
$SMARTY->assign('pagination', $pagination);
$SMARTY->assign('filter', $filter);
$SMARTY->display('document/documentlist.html');

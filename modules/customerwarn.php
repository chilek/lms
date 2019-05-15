<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

function getMessageTemplate($tmplid)
{
    global $DB;

    $result = new xajaxResponse();
    $message = $DB->GetOne('SELECT message FROM templates WHERE id = ?', array($tmplid));
    $result->call('messageTemplateReceived', $message);

    return $result;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array('getMessageTemplate'));
$SMARTY->assign('xajax', $LMS->RunXajax());

$setwarnings = isset($_POST['setwarnings']) ? $_POST['setwarnings'] : array();

if (isset($setwarnings['mcustomerid'])) {
    $warnon = isset($setwarnings['warnon']) ? $setwarnings['warnon'] : false;
    $warnoff = isset($setwarnings['warnoff']) ? $setwarnings['warnoff'] : false;
    $message = isset($setwarnings['message']) ? $setwarnings['message'] : null;

    $msgtmplid = intval($setwarnings['tmplid']);
    $msgtmploper = intval($setwarnings['tmploper']);
    $msgtmplname = $setwarnings['tmplname'];
    if ($msgtmploper > 1) {
        switch ($msgtmploper) {
            case 2:
                if (empty($msgtmplid)) {
                    break;
                }
                $LMS->UpdateMessageTemplate($msgtmplid, TMPL_WARNING, null, '', $setwarnings['message']);
                break;
            case 3:
                if (!strlen($msgtmplname)) {
                    break;
                }
                $LMS->AddMessageTemplate(TMPL_WARNING, $msgtmplname, '', $setwarnings['message']);
                break;
        }
    }

    $cids = array_filter($setwarnings['mcustomerid'], 'is_natural');
    if (!empty($cids)) {
        $LMS->NodeSetWarnU($cids, $warnon ? 1 : 0);
        if (isset($message)) {
            $DB->Execute(
                'UPDATE customers SET message = ? WHERE id IN (' . implode(',', $cids) . ')',
                array($message)
            );
            if ($SYSLOG) {
                foreach ($cids as $cid) {
                    $args = array(
                    SYSLOG::RES_CUST => $cid,
                    'message' => $message
                    );
                    $SYSLOG->AddMessage(SYSLOG::RES_CUST, SYSLOG::OPER_UPDATE, $args);
                }
            }
        }
    }

    $SESSION->save('warnmessage', $message);
    $SESSION->save('warnon', $warnon);
    $SESSION->save('warnoff', $warnoff);

    $SESSION->redirect('?'.$SESSION->get('backto'));
}

if (isset($_GET['search'])) {
    $SESSION->restore('customersearch', $search);
    $SESSION->restore('cslo', $order);
    $SESSION->restore('csls', $state);
    $SESSION->restore('csln', $network);
    $SESSION->restore('cslg', $customergroup);
    $SESSION->restore('cslk', $sqlskey);

        $customerlist = $LMS->GetCustomerList(compact("order", "state", "network", "customergroup", "search", "time", "sqlskey"));
    
    unset($customerlist['total']);
    unset($customerlist['state']);
    unset($customerlist['network']);
    unset($customerlist['customergroup']);
    unset($customerlist['direction']);
    unset($customerlist['order']);
    unset($customerlist['below']);
    unset($customerlist['over']);

    $selected = array();
    if ($customerlist) {
        foreach ($customerlist as $row) {
            $selected[$row['id']] = $row['id'];
        }
    }
    
    $SMARTY->assign('selected', $selected);
}

$layout['pagetitle'] = trans('Notices');

$customerlist = $DB->GetAllByKey('SELECT c.id AS id, MAX(warning) AS warning, '.
            $DB->Concat('UPPER(lastname)', "' '", 'c.name').' AS customername 
		    FROM customerview c 
		    LEFT JOIN nodes ON c.id = ownerid 
		    WHERE deleted = 0 
		    GROUP BY c.id, lastname, c.name 
		    ORDER BY customername ASC', 'id');

$SMARTY->assign('messagetemplates', $LMS->GetMessageTemplates(TMPL_WARNING));
$SMARTY->assign('warnmessage', $SESSION->get('warnmessage'));
$SMARTY->assign('warnon', $SESSION->get('warnon'));
$SMARTY->assign('warnoff', $SESSION->get('warnoff'));
$SMARTY->assign('customerlist', $customerlist);
$SMARTY->display('customer/customerwarnings.html');

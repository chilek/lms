<?php

/*
 *  LMS version 1.11-git
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

/*!
 * \brief Check if string has date format.
 *
 * \param  string  date string
 * \return boolean
 */
function is_date($date) {
    list($year,$month,$day) = explode('/', $date);
    return checkdate((int)$month,(int)$day,(int)$year);
}

if (isset($_GET['record'])) {
    global $SESSION, $LMS;

    $uid = $LMS->DB->GetOne('SELECT uniqueid
                             FROM voip_cdr c
                             WHERE
                                id = ? AND
                               (c.callervoipaccountid in (select id from voipaccounts where ownerid = ?) OR
                                c.calleevoipaccountid in (select id from voipaccounts where ownerid = ?))',
                             array($_GET['record'], $SESSION->id, $SESSION->id));

    if (empty($uid))
        die();

    define('VOIP_CALL_DIR', ConfigHelper::getConfig('voip.call_recording_directory',
            SYS_DIR . DIRECTORY_SEPARATOR . 'voipcalls' . DIRECTORY_SEPARATOR));

    $filepath = VOIP_CALL_DIR . $uid;

    if (is_readable($filepath . '.mp3'))
        $filepath .= '.mp3';
    elseif (is_readable($filepath . '.ogg'))
        $filepath .= '.ogg';
    else
        $filepath .= '.wav';

    header('Content-Type: ' . mime_content_type($filepath));

    echo file_get_contents($filepath);
    die();
}

function module_main() {
    global $LMS, $SMARTY, $SESSION;

    $user_account_ids = array_keys($LMS->DB->GetAllByKey('SELECT id FROM voipaccounts
                                                          WHERE ownerid = ?', 'id', array($SESSION->id)));

    if ($user_account_ids) {
        $tmp_phones = $LMS->DB->GetAll('SELECT phone FROM voip_numbers
                                        WHERE voip_account_id IN ('.implode(',',$user_account_ids).');');

        $phones = array();
        foreach($tmp_phones as $v) {
            $phones[] = $v['phone'];
        }

        $params = array();

        if (empty($_GET['phone']) && count($user_account_ids) > 1) {
            $params['id'] = $user_account_ids;
        } else {
            if (in_array($_GET['phone'], $phones))
                $params['phone'] = $_GET['phone'];
            else
                $params['id'] = $user_account_ids;
        }

        if (isset($_GET['date_from']) && is_date($_GET['date_from']))
            $params['frangefrom'] = $_GET['date_from'];
        else if (!isset($_GET['date_from']))
            $params['frangefrom'] = date("Y/m/01");

        if (isset($_GET['date_to']) && is_date($_GET['date_to']))
            $params['frangeto'] = $_GET['date_to'];

        if (!empty($_GET['fstatus'])) {
            switch ($_GET['fstatus']) {
                case CALL_ANSWERED:
                case CALL_NO_ANSWER:
                case CALL_BUSY:
                case CALL_SERVER_FAILED:
                    $params['fstatus'] = $_GET['fstatus'];
                break;
            }
        }

        if (!empty($_GET['ftype'])) {
            switch ($_GET['ftype']) {
                case CALL_OUTGOING:
                case CALL_INCOMING:
                    $params['ftype'] = $_GET['ftype'];
                break;
            }
        }

        if (!empty($_GET['o'])) {
            $order = explode(',', $_GET['o']);

            if (empty($order[1]) || $order[1] != 'desc')
                $order[1] = 'asc';

            $params['order'] = $order[0];
            $params['direction'] = $order[1];
            $params['o'] = $_GET['o'];
        }

        $billings = $LMS->getVoipBillings($params);
    }

    $pagin = new LMSPagination_ext();
    $pagin->setItemsPerPage( ConfigHelper::getConfig('phpui.billinglist_pagelimit', 100) );
    $pagin->setItemsCount( count($billings) );
    $pagin->setCurrentPage( ((!$_GET['page']) ? 1 : (int) $_GET['page']) );
    $pagin->setRange(3);

    $SMARTY->assign('pagination'         , $pagin);
    $SMARTY->assign('pagin_result'       , $pagin->getPages());
    $SMARTY->assign('params'             , $params);
    $SMARTY->assign('billings'           , $billings);
    $SMARTY->assign('customer_phone_list', $phones);
    $SMARTY->display('module:billing.html');
}

?>

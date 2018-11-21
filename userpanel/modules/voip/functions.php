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

global $LMS, $SESSION;

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

if (empty($_GET['action'])) {
    $_GET['action'] = 'none';
}

switch ($_GET['action']) {
    case 'getaccountinfo':
        $id = (int) $_POST['accid'];
        $user_accs = $LMS->DB->GetAllByKey('SELECT id FROM voipaccounts WHERE ownerid = ?', 'id', array( $SESSION->id ));

        if ( !isset($user_accs[$id])) {
            die();
        }

        $info = $LMS->DB->GetAll('SELECT vn.phone, vn.number_index, vacc.flags
                                  FROM voip_numbers vn
                                  LEFT JOIN voipaccounts vacc ON vn.voip_account_id = vacc.id
                                  WHERE voip_account_id = ? ORDER BY vn.number_index', array($id));

        die( json_encode( $info ) );
    break;

    case 'updateaccountinfo':
        $rec = ($_POST['recording'] == 1) ? 1 : 0;
        $id  = (int) $_POST['voipaccid'];

        $user_accs = $LMS->DB->GetAllByKey('SELECT id FROM voipaccounts WHERE ownerid = ?', 'id', array($SESSION->id));

        if ( !isset($user_accs[$id])) {
            die(); // failure
        }

        $LMS->DB->BeginTrans();

        // --- UPDATE CUSTOMER FLAG RESPONSIBILITY FOR CALL RECORDINGS ---
        $flags = $LMS->DB->GetOne('SELECT flags FROM voipaccounts WHERE id = ?', array($id));

        if ($rec) {
            $flags |= CALL_FLAG_CUSTOMER_RECORDING;
        } else {
            $flags &= ~(CALL_FLAG_CUSTOMER_RECORDING);
        }
        $LMS->DB->Execute('UPDATE voipaccounts SET flags = ? WHERE id = ?', array($flags, $id));

        if (isset($_POST['phones'])) {
            // --- UPDATE CUSTOMER PHONE INDEXES ---
            $phones = $_POST['phones'];

            //get list of current phones
            $current_phones = $LMS->DB->GetAllBykey('SELECT phone FROM voip_numbers WHERE voip_account_id = ?', 'phone', array($id));

            // reset indexes before set new
            $LMS->DB->Execute('UPDATE voip_numbers SET number_index = null WHERE voip_account_id = ?', array($id));

            // set new indexes
            if (count($_POST['phones']) != count($current_phones) ) {
                $LMS->DB->RollbackTrans();
                die();
            }

            $i = 0;
            foreach ($phones as $p) {
                if (!isset($current_phones[$p])) {
                    $LMS->DB->RollbackTrans();
                    die();
                }

                $LMS->DB->Execute('UPDATE voip_numbers SET number_index = ? WHERE phone ?LIKE? ?', array(++$i, $p));
            }
        }

        $LMS->DB->CommitTrans();
        die( json_encode(1) ); // success
    break;
}

if (isset($_GET['record'])) {
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

    $phones = array();
    $params = array();

    $user_accounts = $LMS->DB->GetAllByKey('SELECT id, login, flags FROM voipaccounts
                                            WHERE ownerid = ?', 'id', array($SESSION->id));

    $user_acc_ids = (is_array($user_accounts)) ? array_keys($user_accounts) : '';

    if ($user_acc_ids) {
        $tmp_phones = $LMS->DB->GetAll('SELECT phone FROM voip_numbers
                                        WHERE voip_account_id IN ('.implode(',',$user_acc_ids).');');

        foreach($tmp_phones as $v) {
            $phones[] = $v['phone'];
        }

        if (empty($_GET['phone']) && count($user_acc_ids) > 1) {
            $params['id'] = $user_acc_ids;
        } else {
            if (in_array($_GET['phone'], $phones))
                $params['phone'] = $_GET['phone'];
            else
                $params['id'] = $user_acc_ids;
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

    $SMARTY->assign('pagination'            , $pagin);
    $SMARTY->assign('pagin_result'          , $pagin->getPages());
    $SMARTY->assign('params'                , $params);
    $SMARTY->assign('billings'              , $billings);
    $SMARTY->assign('customer_phone_list'   , $phones);
    $SMARTY->assign('user_accounts'         , $user_accounts);
    $SMARTY->display('module:billing.html');
}

?>

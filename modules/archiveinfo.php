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

if (!$SYSLOG) {
    $body = trans('Transaction logging is disabled.');
    $SMARTY->assign('body', $body);
    $SMARTY->display('dialog.html');
    die;
}

$type = isset($_GET['type']) ? intval($_GET['type']) : 0;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$date = isset($_GET['date']) ? intval($_GET['date']) : 0;

$resource = array(
    'type' => $type,
    'id' => $id
);
if (!empty($date)) {
    $resource['date'] = $date;
}

$resource['properties'] = $SYSLOG->GetResourceProperties($resource);
$resource['name'] = SYSLOG::getResourceName($type);

//xdebug_var_dump($resource);
//die;

$trans = $SYSLOG->GetTransactions(array('key' => SYSLOG::getResourceKey($type), 'value' => $id));
if (!empty($trans)) {
    foreach ($trans as $idx => $tran) {
        $SYSLOG->DecodeTransaction($trans[$idx]);
    }
}
$SMARTY->assign('transactions', $trans);

$layout['pagetitle'] = trans('Archived Resource Information');

$SMARTY->assign('resource', $resource);
$SMARTY->display('archive/archiveinfo.html');

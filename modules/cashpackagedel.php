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

$sourcefileid = intval($_GET['id']);
$name = $DB->GetOne('SELECT name FROM sourcefiles WHERE id = ?', array($sourcefileid));
$layout['pagetitle'] = trans('Removing package "$a"', $name);

if (!$name) {
    $body = '<P>'.trans('Specified ID is not proper or does not exist!').'</P>';
} elseif ($_GET['is_sure'] != 1) {
    $body = '<P>'.trans('Do you want to remove package "$a"?', $name).'</P>';
    $body .= '<P>'.trans('All cash operations from that package will be lost.').'</P>';
    $body .= '<P><A HREF="?m=cashpackagedel&id=' . $sourcefileid . '&is_sure=1">'.trans('Yes, I know what I do.').'</A>&nbsp;';
    $body .= '<A HREF="?'.$SESSION->get('backto').'">'.trans('No, I\'ve changed my mind.').'</A></P>';
} else {
    $DB->BeginTrans();

    if ($SYSLOG) {
        $imports = $DB->GetAll('SELECT id, customerid, sourceid FROM cashimport WHERE sourcefileid = ?', array($sourcefileid));
        if (!empty($imports)) {
            $importids = array();
            foreach ($imports as $import) {
                $args = array(
                    SYSLOG::RES_CASHIMPORT => $import['id'],
                    SYSLOG::RES_CUST => $import['customerid'],
                    SYSLOG::RES_CASHSOURCE => $import['sourceid'],
                    SYSLOG::RES_SOURCEFILE => $sourcefileid,
                );
                $SYSLOG->AddMessage(SYSLOG::RES_CASHIMPORT, SYSLOG::OPER_DELETE, $args, array_keys($args));
                $importids[] = $import['id'];
            }
            $cash = $DB->GetAll('SELECT id, customerid, docid FROM cash WHERE importid IN (' . implode(',', $importids) . ')');
            if (!empty($cash)) {
                foreach ($cash as $item) {
                    $args = array(
                    SYSLOG::RES_CASH => $item['id'],
                    SYSLOG::RES_CUST => $item['customerid'],
                    SYSLOG::RES_DOC => $item['docid'],
                    );
                    $SYSLOG->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_DELETE, $args);
                }
            }
            $userid = $DB->GetOne('SELECT userid FROM sourcefiles WHERE id = ?', array($sourcefileid));
            $args = array(
                SYSLOG::RES_SOURCEFILE => $sourcefileid,
                SYSLOG::RES_USER => $userid,
            );
            $SYSLOG->AddMessage(SYSLOG::RES_SOURCEFILE, SYSLOG::OPER_DELETE, $args);
        }
    }
    $DB->Execute('DELETE FROM cash WHERE importid IN (
		SELECT id FROM cashimport WHERE sourcefileid = ?)', array($sourcefileid));
    $DB->Execute('DELETE FROM cashimport WHERE sourcefileid = ?', array($sourcefileid));
    $DB->Execute('DELETE FROM sourcefiles WHERE id = ?', array($sourcefileid));

    $DB->CommitTrans();

    $SESSION->redirect('?m=cashimport');
}

$SMARTY->assign('body', $body);
$SMARTY->display('dialog.html');

<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

$name = $DB->GetOne('SELECT name FROM cashregs WHERE id=?', array($_GET['id']));

if (!$name) {
    $layout['pagetitle'] = trans('Removing registry "$a"', $name);
    $body = '<P>' . trans('Specified ID is not proper or does not exist!') . '</P>';
    $body .= '<A HREF="?' . $SESSION->get('backto') . '">' . trans('Back') . '</A></P>';
    $SMARTY->assign('body', $body);
    $SMARTY->display('dialog.html');
} else {
    $doclist = $DB->GetCol('SELECT docid FROM receiptcontents WHERE regid=? GROUP BY docid', array($_GET['id']));
    if ($doclist) {
        foreach ($doclist as $docid) {
            if ($SYSLOG) {
                $customerid = $DB->GetOne('SELECT customerid FROM documents WHERE id = ?', array($docid));
                $args = array(
                    SYSLOG::RES_DOC => $docid,
                    SYSLOG::RES_CUST => $customerid,
                );
                $SYSLOG->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_DELETE, $args);
                $cashids = $DB->GetCol('SELECT id FROM cash WHERE docid = ?', array($docid));
                foreach ($cashids as $cashid) {
                    $args = array(
                        SYSLOG::RES_CASH => $cashid,
                        SYSLOG::RES_DOC => $docid,
                        SYSLOG::RES_CUST => $customerid,
                    );
                    $SYSLOG->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_DELETE, $args);
                }
            }
            $DB->Execute('DELETE FROM documents WHERE id=?', array($docid));
            $DB->Execute('DELETE FROM cash WHERE docid=?', array($docid));
        }
    }

    if ($SYSLOG) {
        $contents = $DB->GetAll('SELECT docid, itemid FROM receiptcontents WHERE regid = ?', array($_GET['id']));
        if (!empty($contents)) {
            foreach ($contents as $content) {
                $args = array(
                    SYSLOG::RES_DOC => $content['docid'],
                    SYSLOG::RES_CASHREG => $_GET['id'],
                    'itemid' => $content['itemid'],
                );
                $SYSLOG->AddMessage(SYSLOG::RES_RECEIPTCONT, SYSLOG::OPER_DELETE, $args);
            }
        }
        $cashrights = $DB->GetAll('SELECT id, userid FROM cashrights WHERE regid = ?', array($_GET['id']));
        if (!empty($cashrights)) {
            foreach ($cashrights as $cashright) {
                $args = array(
                    SYSLOG::RES_CASHRIGHT => $cashright['id'],
                    SYSLOG::RES_USER => $cashright['id'],
                    SYSLOG::RES_CASHREG => $_GET['id'],
                );
                $SYSLOG->AddMessage(SYSLOG::RES_CASHRIGHT, SYSLOG::OPER_DELETE, $args);
            }
        }
        $args = array(SYSLOG::RES_CASHREG => $_GET['id']);
        $SYSLOG->AddMessage(SYSLOG::RES_CASHREG, SYSLOG::OPER_DELETE, $args);
    }
    $DB->Execute('DELETE FROM receiptcontents WHERE regid=?', array($_GET['id']));
    $DB->Execute('DELETE FROM cashrights WHERE regid = ?', array($_GET['id']));
    $DB->Execute('DELETE FROM cashregs WHERE id=?', array($_GET['id']));

    $SESSION->redirect('?m=cashreglist');
}

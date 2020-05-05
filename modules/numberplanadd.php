<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

$numberplanadd = isset($_POST['numberplanadd']) ? $_POST['numberplanadd'] : null;

if (!empty($numberplanadd) && count($numberplanadd)) {
    $numberplanadd['template'] = trim($numberplanadd['template']);

    if ($numberplanadd['template']=='' && $numberplanadd['doctype']==0 && $numberplanadd['period']==0) {
        $SESSION->redirect('?m=numberplanlist');
    }

    if ($numberplanadd['template'] == '') {
        $error['template'] = trans('Number template is required!');
    } elseif (!preg_match('/%[1-9]{0,1}N/', $numberplanadd['template'])
        && !preg_match('/%[1-9]{0,1}C/', $numberplanadd['template'])) {
        $error['template'] = trans('Template must contain "%N" or "%C" specifier!');
    }

    if ($numberplanadd['doctype'] == 0) {
        $error['doctype'] = trans('Document type is required!');
    }

    if ($numberplanadd['period'] == 0) {
        $error['period'] = trans('Numbering period is required!');
    }

    if ($numberplanadd['doctype'] && isset($numberplanadd['isdefault'])) {
        if ($DB->GetOne(
            'SELECT 1 FROM numberplans n
			WHERE doctype = ? AND isdefault = 1'
            .(!empty($_POST['selected']) ? ' AND EXISTS (
			        SELECT 1 FROM numberplanassignments WHERE planid = n.id
			        AND divisionid IN ('.implode(',', array_keys($_POST['selected'])).'))'
            : ' AND NOT EXISTS (SELECT 1 FROM numberplanassignments
				WHERE planid = n.id)'),
            array($numberplanadd['doctype'])
        )) {
            $error['doctype'] = trans('Selected document type has already defined default plan!');
        }
    }

    if (!$error) {
        $args = array(
            'template' => $numberplanadd['template'],
            'doctype' => $numberplanadd['doctype'],
            'period' => $numberplanadd['period'],
            'isdefault' => isset($numberplanadd['isdefault']) ? 1 : 0
        );
        $DB->Execute('INSERT INTO numberplans (template, doctype, period, isdefault)
				VALUES (?,?,?,?)', array_values($args));

        $id = $DB->GetLastInsertID('numberplans');

        if ($SYSLOG) {
            $args[SYSLOG::RES_NUMPLAN] = $id;
            $SYSLOG->AddMessage(SYSLOG::RES_NUMPLAN, SYSLOG::OPER_ADD, $args);
        }

        if (!empty($_POST['selected'])) {
            foreach ($_POST['selected'] as $idx => $name) {
                $DB->Execute('INSERT INTO numberplanassignments (planid, divisionid)
						VALUES (?, ?)', array($id, intval($idx)));
                if ($SYSLOG) {
                    $planassignid = $DB->GetLastInsertID('numberplanassignments');
                    $args = array(
                        SYSLOG::RES_NUMPLANASSIGN => $planassignid,
                        SYSLOG::RES_NUMPLAN => $id,
                        SYSLOG::RES_DIV => intval($idx)
                    );
                    $SYSLOG->AddMessage(SYSLOG::RES_NUMPLANASSIGN, SYSLOG::OPER_ADD, $args);
                }
            }
        }

        if (!isset($numberplanadd['reuse'])) {
            $SESSION->redirect('?m=numberplanlist');
        }

        unset($numberplanadd['template']);
        unset($numberplanadd['period']);
        unset($numberplanadd['doctype']);
        unset($numberplanadd['isdefault']);
    } else {
        $numberplanadd['selected'] = array();
        if (isset($_POST['selected'])) {
            foreach ($_POST['selected'] as $idx => $name) {
                        $numberplanadd['selected'][$idx]['id'] = $idx;
                        $numberplanadd['selected'][$idx]['name'] = $name;
            }
        }
    }
}

$layout['pagetitle'] = trans('New Numbering Plan');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('numberplanadd', $numberplanadd);
$SMARTY->assign('available', $LMS->GetDivisions(array('status' => 0)));
$SMARTY->assign('error', $error);
$SMARTY->display('numberplan/numberplanadd.html');

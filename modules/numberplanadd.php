<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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

    if ($numberplanadd['doctype'] && isset($numberplanadd['isdefault']) && !$LMS->validateNumberPlan($numberplanadd)) {
        $error['doctype'] = trans('Selected document type has already defined default plan!');
    }

    if (!$error) {
        $LMS->addNumberPlan($numberplanadd);

        if (!isset($numberplanadd['reuse'])) {
            $SESSION->redirect('?m=numberplanlist');
        }

        unset($numberplanadd['template']);
        unset($numberplanadd['period']);
        unset($numberplanadd['doctype']);
        unset($numberplanadd['isdefault']);
        $numberplanadd['divisions'] = array();
    } else {
        $divisions = array();
        if (!empty($numberplanadd['divisions'])) {
            $divisions = array_flip($numberplanadd['divisions']);
        }
        $numberplanadd['divisions'] = $divisions;
    }
}

$layout['pagetitle'] = trans('New Numbering Plan');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('numberplanadd', $numberplanadd);
$SMARTY->assign('divisions', $LMS->GetDivisions(array('status' => 0)));
$SMARTY->assign('error', $error);
$SMARTY->display('numberplan/numberplanadd.html');

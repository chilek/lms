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

if (!ConfigHelper::checkConfig('privileges.superuser') && !ConfigHelper::checkConfig('privileges.reports')) {
    access_denied();
}

$type = isset($_GET['type']) ? $_GET['type'] : '';

switch ($type) {
    case 'uke-siis':
        /***********************************************/
        if (isset($_POST['invprojects'])) {
            $invprojects = $_POST['invprojects'];
            if (!is_array($invprojects)) {
                $invprojects = array();
            }
        } else {
            $invprojects = array();
        }
        include(MODULES_DIR . DIRECTORY_SEPARATOR . 'ukesiis.php');
        break;

    case 'uke-income':
        /***********************************************/
        include(MODULES_DIR . DIRECTORY_SEPARATOR . 'ukeincome.php');
        break;

    default:
        $layout['pagetitle'] = trans('Reports');

        $SMARTY->assign('divisions', $LMS->GetDivisions());
        $SMARTY->assign('invprojects', $LMS->GetProjects());
        $SMARTY->assign('printmenu', 'netdev');
        $SMARTY->assign('linktypes', $LINKTYPES);
        $SMARTY->display('print/printindex.html');
        break;
}

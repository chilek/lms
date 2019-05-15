<?php

/*
 * LMS version 1.11-git
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

include(ConfigHelper::getConfig('phpui.import_config', 'cashimportcfg.php'));

if (!isset($patterns) || !is_array($patterns)) {
    $error['file'] = trans('Configuration error. Patterns array not found!');
} elseif (isset($_FILES['file']) && is_uploaded_file($_FILES['file']['tmp_name']) && $_FILES['file']['size']) {
    $contents = file_get_contents($_FILES['file']['tmp_name']);
    $filename = $_FILES['file']['name'];
    $ln = 0;

    $error = $LMS->CashImportParseFile($filename, $contents, $patterns);

    include(MODULES_DIR . DIRECTORY_SEPARATOR . 'cashimport.php');
    die;
} elseif (isset($_FILES['file'])) { // upload errors
    switch ($_FILES['file']['error']) {
        case 1:
        case 2:
            $error['file'] = trans('File is too large.');
            break;
        case 3:
            $error['file'] = trans('File upload has finished prematurely.');
            break;
        case 4:
            $error['file'] = trans('Path to file was not specified.');
            break;
        default:
            $error['file'] = trans('Problem during file upload.');
            break;
    }
}

$layout['pagetitle'] = trans('Cash Operations Import');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$sourcefiles = $DB->GetAll('SELECT s.*, u.name AS username,
    (SELECT COUNT(*) FROM cashimport WHERE sourcefileid = s.id) AS count
    FROM sourcefiles s
    LEFT JOIN vusers u ON (u.id = s.userid)
    ORDER BY s.idate DESC LIMIT 10');

$SMARTY->assign('error', $error);
if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customerlist', $LMS->GetCustomerNames());
}
$SMARTY->assign('sourcelist', $DB->GetAll('SELECT id, name FROM cashsources WHERE deleted = 0 ORDER BY name'));
$SMARTY->assign('sourcefiles', $sourcefiles);
$SMARTY->display('cash/cashimport.html');

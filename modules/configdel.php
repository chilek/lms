<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id) {
        $DB->BeginTrans();
        $LMS->DeleteConfigOption($id);
        $DB->CommitTrans();

        $configVariable = $LMS->GetConfigVariable($id);
        if ($configVariable['section'] == 'ksef' && $configVariable['var'] == 'delay') {
            $ksef = new \Lms\KSeF\KSeF($DB, $LMS);
            $ksef->updateDelays();
        }
    }
} elseif (isset($_POST['marks'])) {
    $options = Utils::filterIntegers($_POST['marks']);
    if (!empty($options)) {
        $DB->BeginTrans();
        foreach ($options as $option) {
            $LMS->DeleteConfigOption($option);
        }
        $DB->CommitTrans();

        $updateDelaysRequired = false;
        foreach ($options as $option) {
            $configVariable = $LMS->GetConfigVariable($option);
            if ($configVariable['section'] == 'ksef' && $configVariable['var'] == 'delay') {
                $updateDelaysRequired = true;
                break;
            }
        }
        if ($updateDelaysRequired) {
            $ksef = new \Lms\KSeF\KSeF($DB, $LMS);
            $ksef->updateDelays();
        }
    }
}

if ($SESSION->get_history_entry() != 'm=configlist') {
    $SESSION->remove_history_entry();
}
$SESSION->redirect_to_history_entry();

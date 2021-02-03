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

if (!$error && isset($_POST['rights'])) {
    // $id is defined in users' add action
    $userid = isset($_GET['id']) ? intval($_GET['id']) : $id;

    // deleting old access rights
    $DB->GetOne('DELETE FROM rights WHERE userid = ?', array($userid));

    // writing serialized access rights array
    $DB->Execute(
        'INSERT INTO rights (userid, data) VALUES (?, ?)',
        array($userid, serialize($_POST['rights']))
    );
} elseif ($ExecStack->action=='install') {
    if ($id = $DB->GetOne('SELECT id FROM users')) {
        // build full access table for first (default) admin
        if ($handle = opendir($ExecStack->modules_dir)) {
            while (false !== ($file = readdir($handle))) {
                if (is_dir($ExecStack->modules_dir.'/'.$file) && is_readable($ExecStack->modules_dir)) {
                    if (file_exists($ExecStack->modules_dir.'/'.$file.'/modinfo.php')) {
                        $rights[$file] = 1;
                    }
                }
            }
        }
    
        $DB->Execute(
            'INSERT INTO rights (userid, data) VALUES (?, ?)',
            array($id, serialize($rights))
        );
    }
}

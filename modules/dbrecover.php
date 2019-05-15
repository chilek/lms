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

function DBLoad($filename = null)
{
    global $DB, $SYSLOG;

    if (!$filename) {
        return false;
    }
    $finfo = pathinfo($filename);
    $ext = $finfo['extension'];

    if ($ext == 'gz' && extension_loaded('zlib')) {
        $file = gzopen($filename, 'r'); //jezeli chcemy gz to plik najpierw trzeba rozpakowac
    } else {
        $file = fopen($filename, 'r');
    }

    if (!$file) {
        return false;
    }

    $DB->BeginTrans(); // przyspieszmy działanie jeżeli baza danych obsługuje transakcje
    while (!feof($file)) {
        $line = fgets($file);
        if ($line!='') {
            $line = str_replace(";\n", '', $line);
            $DB->Execute($line);
        }
    }
    $DB->CommitTrans();

    if ((extension_loaded('zlib'))&&($ext=='gz')) {
        gzclose($file);
    } else {
        fclose($file);
    }

    // Okej, zróbmy parę bzdurek db depend :S
    // Postgres sux ! (warden)
    // Tak, a łyżka na to 'niemożliwe' i poleciała za wanną potrącając bannanem musztardę (lukasz)

    switch (ConfigHelper::getConfig('database.type')) {
        case 'postgres':
            // actualize postgres sequences ...
            foreach ($DB->ListTables() as $tablename) {
                // ... where we have *_id_seq
                if (!in_array($tablename, array(
                            'rtattachments',
                            'dbinfo',
                            'invoicecontents',
                            'receiptcontents',
                            'documentcontents',
                            'stats',
                            'eventassignments',
                            'sessions'))) {
                    $DB->Execute("SELECT setval('".$tablename."_id_seq',max(id)) FROM ".$tablename);
                }
            }
            break;
    }

    if ($SYSLOG) {
        $SYSLOG->AddMessage(
            SYSLOG::RES_DBBACKUP,
            SYSLOG::OPER_DBBACKUPRECOVER,
            array('filename' => $filename)
        );
    }
}

if (isset($_GET['is_sure'])) {
    set_time_limit(0);

    if (!empty($_GET['gz'])) {
        $LMS->DatabaseCreate(true, false);
    } else {
        $LMS->DatabaseCreate(false, false);
    }

    $db = $_GET['db'];

    if (file_exists(ConfigHelper::getConfig('directories.backup_dir').'/lms-'.$db.'.sql')) {
        DBLoad(ConfigHelper::getConfig('directories.backup_dir').'/lms-'.$db.'.sql');
    } elseif (extension_loaded('zlib') && file_exists(ConfigHelper::getConfig('directories.backup_dir').'/lms-'.$db.'.sql.gz')) {
        DBLoad(ConfigHelper::getConfig('directories.backup_dir').'/lms-'.$db.'.sql.gz');
    }

    include(MODULES_DIR . '/dblist.php');
//  $SESSION->redirect('?m='.$SESSION->get('lastmodule'));
} else {
    $layout['pagetitle'] = trans('Database Backup Recovery');
    $SMARTY->display('header.html');
    echo '<H1>'.trans('Database Backup Recovery').'</H1>';
    echo '<P>'.trans('Are you sure, you want to recover database created at $a?', date('Y/m/d H:i.s', $_GET['db'])).'</P>';
    echo '<A href="?m=dbrecover&db='.$_GET['db'].'&is_sure=1">'.trans('Yes, I am sure.').'</A>';
    $SMARTY->display('footer.html');
}

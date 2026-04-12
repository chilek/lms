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
    $ext = isset($finfo['extension']) ? $finfo['extension'] : '';

    // Otwieramy plik (obsługa GZIP lub zwykły tekst)
    if ($ext == 'gz' && extension_loaded('zlib')) {
        $file = gzopen($filename, 'r');
    } else {
        $file = fopen($filename, 'r');
    }

    if (!$file) {
        return false;
    }

    // Pobieramy konfigurację bazy danych z LMS
    $dbType = ConfigHelper::getConfig('database.type');
    $dbHost = ConfigHelper::getConfig('database.host');
    $dbUser = ConfigHelper::getConfig('database.user');
    $dbPass = ConfigHelper::getConfig('database.password');
    $dbName = ConfigHelper::getConfig('database.database');

    // Jeśli używamy mysqli, tworzymy surowe połączenie, by ominąć parser LMS (problem znaków "?")
    $rawConnection = null;
    if ($dbType == 'mysqli') {
        try {
            // Parsowanie hosta (na wypadek portu, np. localhost:3307)
            $hostParts = explode(':', $dbHost);
            $host = $hostParts[0];
            $port = isset($hostParts[1]) ? (int)$hostParts[1] : 3306;

            $rawConnection = new mysqli($host, $dbUser, $dbPass, $dbName, $port);
            if ($rawConnection->connect_error) {
                // W razie błędu fallback do null - użyjemy standardowego $DB
                $rawConnection = null;
            } else {
                // Ustawiamy kodowanie zgodne z LMS
                $rawConnection->set_charset("utf8");
                // Wyłączamy autocommit dla szybkości importu
                $rawConnection->query("SET AUTOCOMMIT=0");
                $rawConnection->query("START TRANSACTION");
            }
        } catch (Exception $e) {
            $rawConnection = null;
        }
    }

    // Bufor 1MB (1048576 bajtów) - kluczowe dla długich linii (np. rozbudowane INSERT lub CONSTRAINT)
    $bufferSize = 1048576;

    while (!feof($file)) {
        if ($ext == 'gz') {
            $line = gzgets($file, $bufferSize);
        } else {
            $line = fgets($file, $bufferSize);
        }

        $line = trim($line);

        // Pomijamy puste linie i komentarze SQL
        if (empty($line) || strpos($line, '--') === 0 || strpos($line, '/*') === 0) {
            continue;
        }

        if ($rawConnection) {
            // ŚCIEŻKA SZYBKA I BEZPIECZNA (Omija parser LMS)
            // Metoda query() w mysqli nie analizuje treści pod kątem "?"
            $result = $rawConnection->query($line);

            if (!$result) {
                // Opcjonalnie: Logowanie błędów importu
                // error_log("DBRecover Error: " . $rawConnection->error . " in line: " . substr($line, 0, 100));
            }
        } else {
            // ŚCIEŻKA STANDARDOWA (Dla Postgresa lub gdy surowe połączenie zawiodło)
            // Używamy array() by zminimalizować ryzyko błędów parsera, ale to wciąż przechodzi przez LMSDB
            $DB->Execute($line, array());
        }
    }

    // Zamykanie transakcji i sprzątanie
    if ($rawConnection) {
        $rawConnection->query("COMMIT");
        $rawConnection->query("SET AUTOCOMMIT=1");
        $rawConnection->close();
    }

    if ($ext == 'gz') {
        gzclose($file);
    } else {
        fclose($file);
    }

    // Dodatkowe operacje specyficzne dla LMS (sekwencje w Postgres)
    if ($dbType == 'postgres') {
        $tables = $DB->GetCol('SELECT tablename FROM pg_tables WHERE schemaname=\'public\'');
        foreach ($tables as $tablename) {
            if ($DB->GetOne("SELECT count(*) FROM pg_class WHERE relname = '".$tablename."_id_seq'")) {
                 $DB->Execute("SELECT setval('".$tablename."_id_seq',max(id)) FROM ".$tablename);
            }
        }
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
        $LMS->DatabaseCreate(true);
    } else {
        $LMS->DatabaseCreate();
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
    $timestamp = explode('-', $_GET['db'])[0];
    echo '<P>'.trans('Are you sure, you want to recover database created at $a?', date('Y/m/d H:i.s', $timestamp)).'</P>';
    echo '<A href="?m=dbrecover&db='.$_GET['db'].'&is_sure=1">'.trans('Yes, I am sure.').'</A>';
    $SMARTY->display('footer.html');
}

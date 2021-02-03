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

/**
 * LMSDBInterface
 *
 * Interface for database access objects.
 *
 * @package LMS
 */
interface LMSDBInterface
{

    public function Connect($dbhost, $dbuser, $dbpasswd, $dbname);

    public function Destroy();

    public function Execute($query, array $inputarray = null);

    public function MultiExecute($query, array $inputarray = null);

    public function GetAll($query = null, array $inputarray = null);

    public function GetAllByKey($query = null, $key = null, array $inputarray = null);

    public function GetRow($query = null, array $inputarray = null);

    public function GetCol($query = null, array $inputarray = null);

    public function GetOne($query = null, array $inputarray = null);

    public function Exec($query, array $inputarray = null);

    public function FetchRow($result);

    public function Concat();

    public function Now();

    public function Year($date);

    public function Month($date);

    public function Day($date);

    public function ListTables();

    public function BeginTrans();

    public function CommitTrans();

    public function RollbackTrans();

    public function LockTables($table, $locktype = null);

    public function UnLockTables();

    public function GetDBVersion();

    public function SetEncoding($name);

    public function GetLastInsertID($table = null);

    public function Escape($input);

    public function GroupConcat($field, $separator = ',');

    public function GetVersion();

    public function GetRevision();

    public function IsLoaded();

    public function GetDbType();

    public function GetDbLink();

    public function GetResult();

    public function &GetErrors();

    public function SetErrors(array $errors = array());

    public function SetDebug($debug = true);

    public function UpgradeDb($dbver = DBVERSION, $pluginclass = null, $libdir = null, $docdir = null);
}

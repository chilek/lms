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

/**
 * LMSDB
 *
 * LMS database provider. Factory pattern. Singleton pattern.
 *
 * @package LMS
 */
class LMSDB
{
    public const MYSQL = 'mysql';
    public const MYSQLI = 'mysqli';
    public const POSTGRESQL = 'postgres';

    public const RESOURCE_TYPE_TABLE = 1;
    public const RESOURCE_TYPE_VIEW = 2;
    public const RESOURCE_TYPE_COLUMN = 3;
    public const RESOURCE_TYPE_CONSTRAINT = 4;
    public const RESOURCE_TYPE_INDEX = 5;
    public const RESOURCE_TYPE_COLUMN_TYPE = 6;
    public const RESOURCE_TYPE_TRIGGER = 7;

    const DEBUG_DETAILS = 1;
    const DEBUG_TIME = 2;

    private static $db;
    
    /**
     * Returns singleton database handler.
     *
     * @return \LMSDBInterface
     */
    public static function getInstance($force_new = false)
    {
        if (self::$db === null || $force_new) {
            $_DBTYPE = LMSConfig::getIniConfig()->getSection('database')->getVariable('type')->getValue();
            $_DBHOST = LMSConfig::getIniConfig()->getSection('database')->getVariable('host')->getValue();
            $_DBUSER = LMSConfig::getIniConfig()->getSection('database')->getVariable('user')->getValue();
            $_DBPASS = LMSConfig::getIniConfig()->getSection('database')->getVariable('password')->getValue();
            $_DBNAME = LMSConfig::getIniConfig()->getSection('database')->getVariable('database')->getValue();
            $_DBDEBUG = false;
            if (LMSConfig::getIniConfig()->getSection('database')->hasVariable('debug')) {
                $debug = LMSConfig::getIniConfig()->getSection('database')->getVariable('debug')->getValue();
                switch ($debug) {
                    case 'time':
                        $_DBDEBUG = self::DEBUG_TIME;
                        break;
                    case 'details':
                        $_DBDEBUG = self::DEBUG_DETAILS;
                        break;
                    default:
                        $_DBDEBUG = ConfigHelper::checkValue($debug) ? self::DEBUG_DETAILS | self::DEBUG_TIME : false;
                }
            }
            self::$db = self::getDB($_DBTYPE, $_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME, $_DBDEBUG);
        }
        
        return self::$db;
    }

    /**
     * Returns databse object.
     *
     * Tries to connect to specified database and returns connection handler
     * object. If connection cannot be opened or databbase type is unknown
     * throws exception.
     *
     * @param string $dbtype Database engine name
     * @param string $dbhost Database host
     * @param string $dbuser Database user
     * @param string $dbpasswd Database user password
     * @param string $dbname Database name
     * @param boolean $debug Debug flag
     * @return \LMSDBInterface
     * @throws Exception
     */
    public static function getDB($dbtype, $dbhost, $dbuser, $dbpasswd, $dbname, $debug = false)
    {
        $dbtype = strtolower($dbtype);

        $db = null;

        switch ($dbtype) {
            case self::MYSQL:
            case self::MYSQLI:
                $db = new LMSDB_driver_mysqli($dbhost, $dbuser, $dbpasswd, $dbname);
                break;
            case self::POSTGRESQL:
                $db = new LMSDB_driver_postgres($dbhost, $dbuser, $dbpasswd, $dbname);
                break;
            default:
                throw new Exception('Unable to load driver for "' . $dbtype . '" database!');
        }

/*
        if (!$db->IsLoaded()) {
            throw new Exception('PHP Driver for "' . $dbtype . '" database doesn\'t seem to be loaded.');
        }
*/

        if (!$db->GetDbLink()) {
            throw new Exception($db->GetConnectionError());
        }

        $db->SetDebug($debug);

        $db->SetEncoding('UTF8');

        return $db;
    }
    
    /**
     * Destroys database handler and singleton instance.
     *
     * Useful for unit tests.
     * @return null Null database handler
     */
    public static function destroyInstance()
    {
        if (self::$db !== null) {
            self::$db->Destroy();
            self::$db = null;
        }
        return self::$db;
    }
    
    /**
     * Checks if database connection exists
     *
     * @return boolean
     */
    public static function checkIfInstanceExists()
    {
        if (self::$db !== null) {
            return true;
        } else {
            return false;
        }
    }
}

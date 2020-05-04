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

define('DBVERSION', '2020050400'); // here should be always the newest version of database!
                 // it placed here to avoid read disk every time when we call this file.

/**
 *
 * Database access layer abstraction for LMS. LMSDB drivers should extend this
 * class.
 *
 * @package LMS
 */
abstract class LMSDB_common implements LMSDBInterface
{

    /** @var string LMS version * */
    protected $_version = DBVERSION;

    /** @var string LMS revision * */
    protected $_revision = '$Revision$';

    /** @var boolean Driver load state. Should be changed by driver after successful loading. */
    protected $_loaded = false;

    /** @var string Database engine type * */
    protected $_dbtype = 'NONE';

    /** @var resource|null Database link * */
    protected $_dblink = null;

    /** @var string|null Database host * */
    protected $_dbhost = null;

    /** @var string|null Database user * */
    protected $_dbuser = null;

    /** @var string|null Database name * */
    protected $_dbname = null;

    /** @var boolean Query error * */
    protected $_error = false;

    /** @var string|null Database query * */
    protected $_query = null;

    /** @var resource|null Query result * */
    protected $_result = null;

    /** @var array Query errors * */
    protected $errors = array();

    /** @var boolean Debug flag * */
    protected $debug = false;

    protected $_warnings = true;

    /**
     * Connects to database.
     *
     * @param string $dbhost
     * @param string $dbuser
     * @param string $dbpasswd
     * @param string $dbname
     * @return boolean
     */
    public function Connect($dbhost, $dbuser, $dbpasswd, $dbname)
    {

        register_shutdown_function(array($this, '_driver_shutdown'));

        // database initialization
        if ($this->_driver_connect($dbhost, $dbuser, $dbpasswd, $dbname)) {
            return $this->_dblink;
        } else {
            $this->errors[] = array(
                'query' => 'database connect',
                'error' => $this->_driver_geterror(),
            );
            return false;
        }
    }

    /**
     * Disconnects from database.
     *
     * @return bool
     */
    public function Destroy()
    {
        return $this->_driver_disconnect();
    }

    /**
     * Executes sql query.
     *
     * @param string $query
     * @param array $inputarray
     * @return int|false
     */
    public function Execute($query, array $inputarray = null)
    {
        if ($this->debug) {
            $start = microtime(true);
        }

        if (!$this->_driver_execute($this->_query_parser($query, $inputarray))) {
            $this->errors[] = array(
                'query' => $this->_query,
                'error' => $this->_driver_geterror(),
            );
        } elseif ($this->debug) {
            $this->errors[] = array(
                'query' => $this->_query,
                'error' => 'DEBUG: NOERROR',
                'time' => microtime(true) - $start,
            );
        }

        return $this->_driver_affected_rows();
    }

    /**
     * Executes multiple queries delimited by semicollon.
     *
     * @param string $query
     * @param array $inputarray
     * @return int|false
     */
    public function MultiExecute($query, array $inputarray = null)
    {
        if ($this->debug) {
            $start = microtime(true);
        }

        if (!$this->_driver_multi_execute($this->_query_parser($query, $inputarray))) {
            $this->errors[] = array(
                'query' => $this->_query,
                'error' => $this->_driver_geterror(),
            );
        } elseif ($this->debug) {
            $this->errors[] = array(
                'query' => $this->_query,
                'error' => 'DEBUG: NOERROR',
                'time' => microtime(true) - $start,
            );
        }

        return $this->_driver_affected_rows();
    }

    /**
     * Executes query and returns all rows.
     *
     * @param string $query
     * @param array $inputarray
     * @return array
     */
    public function GetAll($query = null, array $inputarray = null)
    {
        if ($query) {
            $this->Execute($query, $inputarray);
        }

        $result = null;

        while ($row = $this->_driver_fetchrow_assoc()) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Executes query and returns results as assciative array where key is
     * row value for given key.
     *
     * @param string $query
     * @param string $key
     * @param array $inputarray
     * @return array
     */
    public function GetAllByKey($query = null, $key = null, array $inputarray = null)
    {
        if ($query) {
            $this->Execute($query, $inputarray);
        }

        $result = null;

        while ($row = $this->_driver_fetchrow_assoc()) {
            $result[$row[$key]] = $row;
        }

        return $result;
    }

    /**
     * Executes query and return single row.
     *
     * @param string $query
     * @param array $inputarray
     * @return array
     */
    public function GetRow($query = null, array $inputarray = null)
    {
        if ($query) {
            $this->Execute($query, $inputarray);
        }

        return $this->_driver_fetchrow_assoc();
    }

    /**
     * Executes query and returns single, first column.
     *
     * @param string $query
     * @param array $inputarray
     * @return array
     */
    public function GetCol($query = null, array $inputarray = null)
    {
        if ($query) {
            $this->Execute($query, $inputarray);
        }

        $result = null;

        while ($row = $this->_driver_fetchrow_num()) {
            $result[] = $row[0];
        }

        return $result;
    }

    /**
     * Executes query and returns single value.
     *
     * @param srting $query
     * @param array $inputarray
     * @return string|int|null
     */
    public function GetOne($query = null, array $inputarray = null)
    {
        if ($query) {
            $this->Execute($query, $inputarray);
        }

        $result = null;

        list($result) = $this->_driver_fetchrow_num();

        return $result;
    }

    /**
     * Executes query in more optimized way.
     *
     * With Exec() & FetchRow() we can do big results looping in less memory
     * consumptive way than using GetAll() & foreach().
     *
     * @param string $query
     * @param array $inputarray
     * @return null
     */
    public function Exec($query, array $inputarray = null)
    {
        if ($this->debug) {
            $start = microtime(true);
        }

        if (!$this->_driver_execute($this->_query_parser($query, $inputarray))) {
            $this->errors[] = array(
                'query' => $this->_query,
                'error' => $this->_driver_geterror()
            );
        } elseif ($this->debug) {
            $this->errors[] = array(
                'query' => $this->_query,
                'error' => 'DEBUG: NOERROR',
                'time' => microtime(true) - $start,
            );
        }

        if ($this->_driver_num_rows()) {
            return $this->_result;
        } else {
            return null;
        }
    }

    /**
     * Fetches single row from result set. Returns it as associative array.
     *
     * @param type $result
     * @return array
     */
    public function FetchRow($result)
    {
        return $this->_driver_fetchrow_assoc($result);
    }

    /**
     * Creates concat statement for query.
     *
     * @return string
     */
    public function Concat()
    {
        return $this->_driver_concat(func_get_args());
    }

    /**
     * Returns name of sql function used to get time.
     *
     * @return string
     */
    public function Now()
    {
        return $this->_driver_now();
    }

    /**
     * Returns list of tables in database.
     *
     * @return array
     */
    public function ListTables()
    {
        return $this->_driver_listtables();
    }

    /**
     * Begins transaction.
     *
     * @return int|false
     */
    public function BeginTrans()
    {
        return $this->_driver_begintrans();
    }

    /**
     * Commits transaction.
     *
     * @return int|false
     */
    public function CommitTrans()
    {
        return $this->_driver_committrans();
    }

    /**
     * Rollbacks transaction.
     *
     * @return int|false
     */
    public function RollbackTrans()
    {
        return $this->_driver_rollbacktrans();
    }

    /**
     * Locks table.
     *
     * @param string $table
     * @param string $locktype
     * @return int|false
     */
    public function LockTables($table, $locktype = null)
    {
        return $this->_driver_locktables($table, $locktype);
    }

    /**
     * Unlocks table.
     *
     * @return int|false
     */
    public function UnLockTables()
    {
        return $this->_driver_unlocktables();
    }

    /**
     * Returns database engine info.
     *
     * @return string
     */
    public function GetDBVersion()
    {
        return $this->_driver_dbversion();
    }

    /**
     * Sets connection encoding.
     *
     * @param string $name
     * @return int|false
     */
    public function SetEncoding($name)
    {
        return $this->_driver_setencoding($name);
    }

    /**
     * Returns id of last inserted element in table.
     *
     * @param string $table
     * @return int
     */
    public function GetLastInsertID($table = null)
    {
        return $this->_driver_lastinsertid($table);
    }

    /**
     * Escapes string for query.
     *
     * @param string $input
     * @return string
     */
    public function Escape($input)
    {
        return $this->_quote_value($input);
    }

    /**
     * Creates group concat string for query.
     *
     * @param string $field
     * @param string $separator
     * @param boolean $distinct
     * @return string
     */
    public function GroupConcat($field, $separator = ',', $distinct = false)
    {
        return $this->_driver_groupconcat($field, $separator, $distinct);
    }

    /**
    * Gets year for date.
    *
    * @param string $date
    * @return year string
    */
    public function Year($date)
    {
        return $this->_driver_year($date);
    }

    /**
    * Gets month for date.
    *
    * @param string $date
    * @return month string
    */
    public function Month($date)
    {
        return $this->_driver_month($date);
    }

    /**
    * Gets day for date.
    *
    * @param string $date
    * @return day string
    */
    public function Day($date)
    {
        return $this->_driver_day($date);
    }

    /**
     * Regular expression match for selected field.
     *
     * @param string $field
     * @param string $regexp
     * @return regexp match string
     */
    public function RegExp($field, $regexp)
    {
        return $this->_driver_regexp($field, $regexp);
    }

    /**
    * Check if database resource exists (table, view)
    *
    * @param string $name
    * @param int $type
    * @return exists boolean
    */
    public function ResourceExists($name, $type)
    {
        return $this->_driver_resourceexists($name, $type);
    }

    public function DisableWarnings()
    {
        $this->_warnings = false;
    }

    public function EnableWarnings()
    {
        $this->_warnings = true;
    }

    /**
     * Prepares query before execution.
     *
     * Replaces metadata and placeholders.
     *
     * @param string $query
     * @param array $inputarray
     * @return string
     */
    protected function _query_parser($query, array $inputarray = null)
    {
        // replace metadata
        $query = str_ireplace('?NOW?', $this->_driver_now(), $query);
        $query = str_ireplace('?LIKE?', $this->_driver_like(), $query);

        if ($this->_warnings) {
            $param_count = substr_count($query, '?');
            $array_count = $inputarray ? count($inputarray) : 0;
            if ($param_count != $array_count) {
                $error = array(
                    'query' => $query,
                    'error' => "SQL query parser error: parameter count differs from passed argument count (${param_count} != ${array_count}): "
                        . ($array_count ? var_export($inputarray, true) : ''),
                );
                $this->errors[] = $error;
                writesyslog($error['error'] . ' (' . str_replace("\t", ' ', $error['query']) . ')', LOG_ERR);
            }
        }

        if ($inputarray) {
            foreach ($inputarray as $k => $v) {
                $inputarray[$k] = $this->_quote_value($v);
            }

            $query = str_replace('%', '%%', $query); //escape params like %some_value%
            $query = vsprintf(str_replace('?', '%s', $query), $inputarray);
            $query = str_replace('%%', '%', $query);
        }

        return $query;
    }

    /**
     * Quotes value.
     *
     * @param string|null $input
     * @return string
     */
    protected function _quote_value($input)
    {
        // override this method in driver class if it requires other
        // escaping technique

        if ($input === null) {
            return 'NULL';
        } elseif (is_string($input)) {
            return '\'' . addcslashes($input, "'\\\0") . '\'';
        } elseif (is_array($input)) {
            return $this->_quote_array($input);
        } else {
            return $input;
        }
    }

    /**
     * Quotes array.
     *
     * @param array $input
     * @return string
     */
    protected function _quote_array(array $input)
    {
        if (!$input) {
            return 'NULL';
        }

        foreach ($input as $k => $v) {
            $input[$k] = $this->_quote_value($v);
        }

        return '(' . implode(',', $input) . ')';
    }

    /**
     * Returns version.
     *
     * @return string
     */
    public function GetVersion()
    {

        return $this->_version;
    }

    /**
     * Returns revision.
     *
     * @return string
     */
    public function GetRevision()
    {

        return $this->_revision;
    }

    /**
     * Returns driver load state.
     *
     * If driver is loaded returns true otherwise returns false.
     *
     * @return boolean
     */
    public function IsLoaded()
    {

        return $this->_loaded;
    }

    /**
     * Returns database engine type.
     *
     * @return string
     */
    public function GetDbType()
    {

        return $this->_dbtype;
    }

    /**
     * Returns database link.
     *
     * @return resource|boolean|null
     */
    public function GetDbLink()
    {

        return $this->_dblink;
    }

    /**
     * Returns query result.
     *
     * @return resource|null
     */
    public function GetResult()
    {

        return $this->_result;
    }

    /**
     * Returns errors.
     *
     * @return array
     */
    public function &GetErrors()
    {

        return $this->errors;
    }

    /**
     * Sets errors.
     *
     * @param array $errors
     */
    public function SetErrors(array $errors = array())
    {

        $this->errors = $errors;
    }

    /**
     * Sets debug flag.
     *
     * @param boolean $debug
     */
    public function SetDebug($debug = true)
    {

        $this->debug = $debug;
    }

    public function UpgradeDb($dbver = DBVERSION, $pluginclass = null, $libdir = null, $docdir = null)
    {
        $this->DisableWarnings();

        $lastupgrade = null;
        if ($dbversion = $this->GetOne(
            'SELECT keyvalue FROM dbinfo WHERE keytype = ?',
            array('dbversion' . (is_null($pluginclass) ? '' : '_' . $pluginclass))
        )) {
            if ($dbver > $dbversion) {
                set_time_limit(0);

                if ($this->_dbtype == LMSDB::POSTGRESQL && $this->GetOne('SELECT COUNT(*) FROM information_schema.routines
					WHERE routine_name = ? AND specific_schema = ?', array('array_agg', 'pg_catalog')) > 1) {
                    $this->Execute('DROP AGGREGATE IF EXISTS array_agg(anyelement)');
                }

                $lastupgrade = $dbversion;

                if (is_null($libdir)) {
                    $libdir = LIB_DIR;
                }

                $filename_prefix = $this->_dbtype == LMSDB::POSTGRESQL ? 'postgres' : 'mysql';

                $pendingupgrades = array();
                $upgradelist = getdir($libdir . DIRECTORY_SEPARATOR . 'upgradedb', '^' . $filename_prefix . '\.[0-9]{10}\.php$');
                if (!empty($upgradelist)) {
                    foreach ($upgradelist as $upgrade) {
                        $upgradeversion = preg_replace('/^' . $filename_prefix . '\.([0-9]{10})\.php$/', '\1', $upgrade);

                        if ($upgradeversion > $dbversion && $upgradeversion <= $dbver) {
                            $pendingupgrades[] = $upgradeversion;
                        }
                    }
                }

                if (!empty($pendingupgrades)) {
                    sort($pendingupgrades);
                    foreach ($pendingupgrades as $upgrade) {
                        include($libdir . DIRECTORY_SEPARATOR . 'upgradedb' . DIRECTORY_SEPARATOR . $filename_prefix . '.' . $upgrade . '.php');
                        if (empty($this->errors)) {
                            $lastupgrade = $upgrade;
                        } else {
                            break;
                        }
                    }
                }
            }
        } else {
            // save current errors
            $err_tmp = $this->errors;
            $this->errors = array();

            if (is_null($pluginclass)) {
                // check if dbinfo table exists (call by name)
                $dbinfo = $this->GetOne('SELECT COUNT(*) FROM information_schema.tables WHERE table_name = ?', array('dbinfo'));
                // check if any tables exists in this database
                $tables = $this->GetOne('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema NOT IN (?, ?)', array('information_schema', 'pg_catalog'));
            } else {
                $dbinfo = $this->GetOne('SELECT keyvalue FROM dbinfo WHERE keytype = ?', array('dbinfo_' . $pluginclass));
                $tables = 0;
            }
            // if there are no tables we can install lms database
            if (empty($dbinfo) && $tables == 0 && empty($this->errors)) {
                // detect database type and select schema dump file to load
                if ($this->_dbtype == LMSDB::POSTGRESQL) {
                    $schema = 'lms.pgsql';
                } elseif ($this->_dbtype == LMSDB::MYSQL || $this->_dbtype == LMSDB::MYSQLI) {
                    $schema = 'lms.mysql';
                } else {
                    die('Could not determine database type!');
                }

                if (is_null($docdir)) {
                    $docdir = SYS_DIR . DIRECTORY_SEPARATOR . 'doc';
                }

                if (!$sql = file_get_contents($docdir . DIRECTORY_SEPARATOR . $schema)) {
                    die('Could not open database schema file ' . $docdir . DIRECTORY_SEPARATOR . $schema);
                }

                if (!$this->MultiExecute($sql)) {    // execute
                    die('Could not load database schema!');
                }
            } else {                 // database might be installed so don't miss any error
                $this->errors = array_merge($err_tmp, $this->errors);
            }
        }

        $this->EnableWarnings();

        return isset($lastupgrade) ? $lastupgrade : $dbver;
    }
}

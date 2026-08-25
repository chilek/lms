<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2026 LMS Developers
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

// here should be always the newest version of database!
define('DBVERSION', '2026081100');

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

    protected $_connection_error = null;

    /** @var array Query errors * */
    protected $errors = array();

    /** @var boolean Debug flag * */
    protected $debug = false;

    protected $_warnings = true;

    private $_upgrade_errors = array();

    /**
     * True while UpgradeDb() holds the migration transaction.
     * BeginTrans() and CommitTrans() from the script are then ignored.
     *
     * @var bool|null
     */
    private $_upgrade_transaction = null;

    protected $sqlQueryTime = 0;

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

        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            $this->_connection_error = $errstr;
        });

        // database initialization
        set_error_handler(null);
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
    public function Execute($query, ?array $inputarray = null)
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
            $sqlQueryTime =  microtime(true) - $start;

            if ($this->debug & LMSDB::DEBUG_DETAILS) {
                $this->errors[] = array(
                    'query' => $this->_query,
                    'error' => 'DEBUG: NOERROR',
                    'time' => $sqlQueryTime,
                );
            }

            $this->sqlQueryTime += $sqlQueryTime;
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
    public function MultiExecute($query, ?array $inputarray = null)
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
            $sqlQueryTime =  microtime(true) - $start;

            if ($this->debug & LMSDB::DEBUG_DETAILS) {
                $this->errors[] = array(
                    'query' => $this->_query,
                    'error' => 'DEBUG: NOERROR',
                    'time' => microtime(true) - $start,
                );
            }

            $this->sqlQueryTime += $sqlQueryTime;
        }

        return $this->_driver_affected_rows();
    }

    public function getSqlQueryTime()
    {
        return $this->sqlQueryTime;
    }

    /**
     * Executes query and returns all rows.
     *
     * @param string $query
     * @param array $inputarray
     * @return array
     */
    public function GetAll($query = null, ?array $inputarray = null)
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
    public function GetAllByKey($query = null, $key = null, ?array $inputarray = null)
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
    public function GetRow($query = null, ?array $inputarray = null)
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
    public function GetCol($query = null, ?array $inputarray = null)
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
    public function GetOne($query = null, ?array $inputarray = null)
    {
        if ($query) {
            $this->Execute($query, $inputarray);
        }

        $result = null;

        [$result] = $this->_driver_fetchrow_num();

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
    public function Exec($query, ?array $inputarray = null)
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
            $sqlQueryTime =  microtime(true) - $start;

            if ($this->debug & LMSDB::DEBUG_DETAILS) {
                $this->errors[] = array(
                    'query' => $this->_query,
                    'error' => 'DEBUG: NOERROR',
                    'time' => $sqlQueryTime,
                );
            }

            $this->sqlQueryTime += $sqlQueryTime;
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
        if ($this->_upgrade_transaction === true) {
            return true;
        }
        return $this->_driver_begintrans();
    }

    /**
     * Commits transaction.
     *
     * @return int|false
     */
    public function CommitTrans()
    {
        if ($this->_upgrade_transaction === true) {
            return true;
        }
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

    public function LockByHandle($handle): mixed
    {
        return $this->_driver_lockbyhandle($handle);
    }

    public function UnLockByHandle($handle): mixed
    {
        return $this->_driver_unlockbyhandle($handle);
    }

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
     * Substring match by regular expression for selected field.
     *
     * @param string $field
     * @param string $regexp
     * @return regexp match string
     */
    public function SubstringByRegExp($field, $regexp)
    {
        return $this->_driver_substringbyregexp($field, $regexp);
    }

    /**
     * Convert field variable to specified type.
     *
     * @param string $field
     * @param string $type
     * @return converted field
     */
    public function Cast($field, $type)
    {
        return $this->_driver_cast($field, $type);
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
    protected function _query_parser($query, ?array $inputarray = null)
    {
        // replace metadata
        $query = str_ireplace('?NOW?', $this->_driver_now(), $query);
        $query = str_ireplace('?LIKE?', $this->_driver_like(), $query);

        if ($this->_warnings) {
            $param_count = substr_count($query, '?');
            $array_count = $inputarray ? count($inputarray) : 0;
            if ($param_count != $array_count) {
                $backtrace = Utils::getDebugBacktrace(15);

                if (empty($backtrace)) {
                    $backtraceText = '';
                } else {
                    $backtraceText = 'Stack trace:' . PHP_EOL
                        . implode(
                            PHP_EOL,
                            array_map(
                                function ($item) {
                                    return '  ' . $item;
                                },
                                $backtrace
                            )
                        );
                }

                $logError = "SQL query parser error: parameter count differs from passed argument count ({$param_count} != {$array_count}): "
                    . ($array_count ? var_export($inputarray, true) : '');

                $error = array(
                    'query' => $query,
                    'error' => $logError . PHP_EOL . $backtraceText,
                );
                $this->errors[] = $error;

                writesyslog(
                    $logError
                        . ' (' . str_replace("\t", ' ', $error['query']) . ')' . PHP_EOL
                        . (empty($backtraceText) ? '' : $backtraceText . PHP_EOL),
                    LOG_ERR
                );
            }
        }

        if ($inputarray) {
            foreach ($inputarray as $k => $v) {
                $inputarray[$k] = $this->_quote_value($v);
            }

            $query = str_replace('%', '[lms_percentage_placeholder]', $query); //escape params like %some_value%
            $query = vsprintf(str_replace('?', '%s', $query), $inputarray);
            $query = str_replace('[lms_percentage_placeholder]', '%', $query);
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

    public function GetConnectionError()
    {
        return $this->_connection_error;
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

    public function GetDebug($debug = true)
    {

        return $this->debug;
    }

    /**
     * Prints an upgrade message on the console. Web requests stay silent
     * so output does not start before HTTP headers.
     *
     * @param string $message
     */
    private function _upgrade_log($message)
    {
        if (PHP_SAPI === 'cli') {
            echo $message . PHP_EOL;
        }
    }

    /**
     * Formats a duration in seconds for upgrade logs.
     *
     * @param float $seconds
     * @return string
     */
    private function _upgrade_format_duration($seconds)
    {
        $seconds = max(0, (float) $seconds);
        if ($seconds >= 3600) {
            $hours = (int) floor($seconds / 3600);
            $minutes = (int) floor(fmod($seconds, 3600) / 60);
            $rest = fmod($seconds, 60);
            return sprintf('%d h %d min %.3f s', $hours, $minutes, $rest);
        }
        if ($seconds >= 60) {
            $minutes = (int) floor($seconds / 60);
            $rest = fmod($seconds, 60);
            return sprintf('%d min %.3f s', $minutes, $rest);
        }
        return sprintf('%.3f s', $seconds);
    }

    /**
     * A migration opts out of the runner transaction with its own line:
     * // LMS-UPGRADE-TRANSACTION: off
     *
     * @param string $fname
     * @return bool
     */
    private function _upgrade_transaction_disabled($fname)
    {
        $source = file_get_contents($fname);
        return is_string($source)
            && preg_match('/^\s*\/\/\s*LMS-UPGRADE-TRANSACTION:\s*off\s*$/m', $source) === 1;
    }

    /**
     * Rolls back the transaction opened by UpgradeDb().
     */
    private function _upgrade_rollback()
    {
        $use_transaction = $this->_upgrade_transaction === true;
        $this->_upgrade_transaction = null;
        if ($use_transaction) {
            $this->_driver_rollbacktrans();
        }
    }

    public function UpgradeDb($dbver = DBVERSION, $pluginclass = null, $libdir = null, $docdir = null)
    {
        static $dbversions = null;

        $this->DisableWarnings();

        if (!isset($dbversions)) {
            $result = $this->GetAll('SELECT * FROM dbinfo WHERE keytype ?LIKE? ?', array('dbversion%'));
            if (empty($result)) {
                $result = array();
            }
            $dbversions = Utils::array_column($result, 'keyvalue', 'keytype');
        }

        $lastupgrade = null;
        $keytype = 'dbversion' . (is_null($pluginclass) ? '' : '_' . $pluginclass);
        if (isset($dbversions[$keytype])) {
            $dbversion = $dbversions[$keytype];
            if ($dbver > $dbversion) {
                if (isset($GLOBALS['CONFIG']['database']['auto_update']) && ConfigHelper::checkValue($GLOBALS['CONFIG']['database']['auto_update'])) {
                    $old_locale = setlocale(LC_NUMERIC, '0');
                    setlocale(LC_NUMERIC, 'C');

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
                    $upgradelist = getdir(
                        $libdir . DIRECTORY_SEPARATOR . 'upgradedb',
                        '^' . $filename_prefix . '\.[0-9]{10}\.php$'
                    );
                    if (!empty($upgradelist)) {
                        foreach ($upgradelist as $upgrade) {
                            $upgradeversion = preg_replace(
                                '/^' . $filename_prefix . '\.([0-9]{10})\.php$/',
                                '\1',
                                $upgrade
                            );

                            if ($upgradeversion > $dbversion && $upgradeversion <= $dbver) {
                                $pendingupgrades[] = $upgradeversion;
                            }
                        }
                    }

                    if (empty($pendingupgrades)) {
                        $this->_upgrade_log('No pending upgrades');
                    } else {
                        sort($pendingupgrades);
                        $core_db_version = null;
                        if ($pluginclass !== null) {
                            $core_db_version = $this->GetOne(
                                'SELECT keyvalue FROM dbinfo WHERE keytype = ?',
                                array('dbversion')
                            );
                        }
                        $schema_upgrade_started_at = microtime(true);
                        $this->_upgrade_log(
                            'Schema upgrade (' . $keytype . ') started at ' . date('Y-m-d H:i:s')
                        );
                        try {
                            foreach ($pendingupgrades as $upgrade) {
                                $fname = $libdir . DIRECTORY_SEPARATOR . 'upgradedb'
                                    . DIRECTORY_SEPARATOR . $filename_prefix . '.' . $upgrade . '.php';
                                $upgrade_version = $upgrade;
                                $use_transaction = !$this->_upgrade_transaction_disabled($fname);
                                $committed = false;
                                $file_started_at = microtime(true);
                                try {
                                    if ($use_transaction) {
                                        $this->_driver_begintrans();
                                        $this->_upgrade_transaction = true;
                                        if ($this->errors) {
                                            $this->_upgrade_log('Error in DB schema upgrade: ' . $fname);
                                            $this->_upgrade_rollback();
                                            break;
                                        }
                                    }
                                    include($fname);
                                    if ($this->errors) {
                                        $this->_upgrade_log('Error in DB schema upgrade: ' . $fname);
                                        $this->_upgrade_rollback();
                                        break;
                                    }
                                    if ($pluginclass !== null
                                        && $core_db_version !== null
                                        && $core_db_version !== false
                                        && $core_db_version !== ''
                                    ) {
                                        $this->Execute(
                                            'UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?',
                                            array($core_db_version, 'dbversion')
                                        );
                                    }
                                    $this->Execute(
                                        'UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?',
                                        array($upgrade_version, $keytype)
                                    );
                                    if ($this->errors) {
                                        $this->_upgrade_log('Error in DB schema upgrade: ' . $fname);
                                        $this->_upgrade_rollback();
                                        break;
                                    }
                                    $this->_upgrade_transaction = null;
                                    if ($use_transaction) {
                                        $this->_driver_committrans();
                                        if ($this->errors) {
                                            $this->_driver_rollbacktrans();
                                            $this->_upgrade_log('Error in DB schema upgrade: ' . $fname);
                                            break;
                                        }
                                    }
                                    $committed = true;
                                    $dbversions[$keytype] = $upgrade_version;
                                    $this->_upgrade_log(
                                        'DB version is now: ' . $upgrade_version
                                        . ', took ' . $this->_upgrade_format_duration(microtime(true) - $file_started_at)
                                    );
                                    $lastupgrade = $upgrade_version;
                                } catch (Throwable $e) {
                                    if ($use_transaction && !$committed) {
                                        $this->_upgrade_transaction = true;
                                        $this->_upgrade_rollback();
                                    }
                                    throw $e;
                                } finally {
                                    $this->_upgrade_transaction = null;
                                }
                            }
                        } finally {
                            $this->_upgrade_log(
                                'Schema upgrade (' . $keytype . ') took '
                                . $this->_upgrade_format_duration(microtime(true) - $schema_upgrade_started_at)
                            );
                        }
                    }

                    setlocale(LC_NUMERIC, $old_locale);
                } else {
                    $lastupgrade = $dbversion;

                    if (empty($pluginclass)) {
                        $error_message = 'CORE';
                    } else {
                        $error_message = "Plugin '" . $pluginclass . "'";
                    }
                    $this->_upgrade_errors[] = $error_message . " database schema could be updated"
                        . " from version '" . $dbversion . "' to '" . $dbver . "'<br>";
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

        return $lastupgrade ?? $dbver;
    }

    public function getUpgradeErrors()
    {
        return $this->_upgrade_errors;
    }
}

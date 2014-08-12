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
 * LMSDB_driver_mysqli
 * 
 * Database access layer abstraction for LMS. LMSDB drivers should extend this
 * class.
 * 
 * @package LMS 
 */
abstract class LMSDB_common implements LMSDBInterface
{

    /** @var string LMS version * */
    protected $_version = '1.11-git';

    /** @var string LMS revision * */
    protected $_revision = '$Revision$';

    /** @var boolean Driver load state. Should be changed by driver after successful loading. */
    protected $_loaded = FALSE;

    /** @var string Database engine type * */
    protected $_dbtype = 'NONE';

    /** @var resource|null Database link * */
    protected $_dblink = NULL;

    /** @var string|null Database host * */
    protected $_dbhost = NULL;

    /** @var string|null Database user * */
    protected $_dbuser = NULL;

    /** @var string|null Database name * */
    protected $_dbname = NULL;

    /** @var boolean Query error * */
    protected $_error = FALSE;

    /** @var string|null Database query * */
    protected $_query = NULL;

    /** @var resource|null Query result * */
    protected $_result = NULL;

    /** @var array Query errors * */
    protected $errors = array();

    /** @var boolean Debug flag * */
    protected $debug = FALSE;

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
            return FALSE;
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
    public function Execute($query, array $inputarray = NULL)
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
    public function MultiExecute($query, array $inputarray = NULL)
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
    public function GetAll($query = NULL, array $inputarray = NULL)
    {
        if ($query) {
            $this->Execute($query, $inputarray);
        }

        $result = NULL;

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
    public function GetAllByKey($query = NULL, $key = NULL, array $inputarray = NULL)
    {
        if ($query) {
            $this->Execute($query, $inputarray);
        }

        $result = NULL;

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
    public function GetRow($query = NULL, array $inputarray = NULL)
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
    public function GetCol($query = NULL, array $inputarray = NULL)
    {
        if ($query) {
            $this->Execute($query, $inputarray);
        }

        $result = NULL;

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
    public function GetOne($query = NULL, array $inputarray = NULL)
    {
        if ($query) {
            $this->Execute($query, $inputarray);
        }

        $result = NULL;

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
    public function Exec($query, array $inputarray = NULL)
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
            return NULL;
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
    public function GetLastInsertID($table = NULL)
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
     * @return string
     */
    public function GroupConcat($field, $separator = ',')
    {
        return $this->_driver_groupconcat($field, $separator);

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
    protected function _query_parser($query, array $inputarray = NULL)
    {
        // replace metadata
        $query = preg_replace('/\?NOW\?/i', $this->_driver_now(), $query);
        $query = preg_replace('/\?LIKE\?/i', $this->_driver_like(), $query);

        if ($inputarray) {
            $queryelements = explode("\0", str_replace('?', "?\0", $query));
            $query = '';
            foreach ($queryelements as $queryelement) {
                if (strpos($queryelement, '?') !== FALSE) {
                    list($key, $value) = each($inputarray);
                    $queryelement = str_replace('?', $this->_quote_value($value), $queryelement);
                }
                $query .= $queryelement;
            }
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

        if ($input === NULL) {
            return 'NULL';
        } elseif (is_string($input)) {
            return '\'' . addcslashes($input, "'\\\0") . '\'';
        } else {
            return $input;
        }

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

}

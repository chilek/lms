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
 * LMSDB_driver_mysqli
 *
 * MySQLi engine driver wrapper for LMS.
 *
 * @package LMS
 */
class LMSDB_driver_mysqli extends LMSDB_common implements LMSDBDriverInterface
{

    /**
     * Constructs driver.
     *
     * Connects to database.
     *
     * @param string $dbhost
     * @param string $dbuser
     * @param string $dbpasswd
     * @param string $dbname
     * @return void
     */
    public function __construct($dbhost, $dbuser, $dbpasswd, $dbname)
    {
        if (!extension_loaded('mysqli')) {
            trigger_error('MySQLi extension not loaded!', E_USER_WARNING);
            $this->_loaded = false;
            return;
        }

        $this->_dbtype = LMSDB::MYSQLI;

        //$this->_version .= ' ('.preg_replace('/^.Revision: ([0-9.]+).*/','\1',$this->_revision).'/'.preg_replace('/^.Revision: ([0-9.]+).*/','\1','$Revision$').'-mysqli)';
        $this->_version .= '';
        $this->Connect($dbhost, $dbuser, $dbpasswd, $dbname);
        $this->Execute('SET SESSION sql_mode = \'\'');
    }

    /**
     * Returns database engine info.
     *
     * @return string
     */
    public function _driver_dbversion()
    {
        return @mysqli_get_server_info($this->_dblink);
    }

    /**
     * Connects to database.
     *
     * @param string $dbhost
     * @param string $dbuser
     * @param string $dbpasswd
     * @param string $dbname
     * @return void
     */
    public function _driver_connect($dbhost, $dbuser, $dbpasswd, $dbname)
    {
        $this->_dblink = @mysqli_connect($dbhost, $dbuser, $dbpasswd, $dbname);
        if ($this->_dblink) {
            $this->_dbhost = $dbhost;
            $this->_dbuser = $dbuser;
            $this->_dbname = $dbname;
            $this->_loaded = true;
        } else {
            $this->_error = true;
        }
        return $this->_dblink;
    }

    /**
     * Closes driver.
     */
    public function _driver_shutdown()
    {
        $this->_loaded = false;
        @mysqli_close($this->_dblink); // apparently, mysqli_close() is automagicly called after end of the script...
    }

    /**
     * Returns errors.
     *
     * @return string
     */
    public function _driver_geterror()
    {
        if ($this->_dblink) {
            return mysqli_error($this->_dblink);
        } elseif ($this->_query) {
            return 'We\'re not connected!';
        } else {
            return mysqli_connect_error();
        }
    }

    /**
     * Disconnects driver from database.
     *
     * @return bool
     */
    public function _driver_disconnect()
    {
        return @mysqli_close($this->_dblink);
    }

    /**
     * Selects database.
     *
     * @param string $dbname
     * @return bool
     */
    public function _driver_selectdb($dbname)
    {
        $result = mysqli_select_db($dbname, $this->_dblink);
        if ($result) {
            $this->_dbname = $dbname;
        }
        return $result;
    }

    /**
     * Executes query.
     *
     * @param string $query
     * @return resource
     */
    public function _driver_execute($query)
    {
        $this->_query = $query;

        $this->_result = @mysqli_query($this->_dblink, $query);
        if ($this->_result) {
            $this->_error = false;
        } else {
            $this->_error = true;
        }
        return $this->_result;
    }

    /**
     * Executes multiple queries at once.
     *
     * @param string $query
     * @return boolean
     */
    public function _driver_multi_execute($query)
    {
        $this->_query = $query;
        $total_result = true;
        $db_errors = array();

        $queries = preg_split("/;+(?=([^'|^\\\']*['|\\\'][^'|^\\\']*['|\\\'])*[^'|^\\\']*[^'|^\\\']$)/", $query);
        foreach ($queries as $q) {
            if (strlen(trim($q)) > 0) {
                $this->_driver_execute($q);           // can not use mysqli_multi_query because it returns 'error 2014 - Commands out of sync; you can't run this command now'
                if ($this->_error == true) {
                    $total_result = false;
                    $db_errors = array_merge($db_errors, $this->errors);
                }
            }
        }
        $this->_error = !$total_result;
        $this->errors = $db_errors;
        return $total_result;
    }

    /**
     * Returns single row from resource as associative array.
     *
     * @param resource $result
     * @return array|boolean
     */
    public function _driver_fetchrow_assoc($result = null)
    {
        if (!$this->_error) {
            return mysqli_fetch_array($result ? $result : $this->_result, MYSQLI_ASSOC);
        } else {
            return false;
        }
    }

    /**
     * Returns single row from resource as array.
     *
     * @return array|boolean
     */
    public function _driver_fetchrow_num()
    {
        if (!$this->_error) {
            return mysqli_fetch_array($this->_result, MYSQLI_NUM);
        } else {
            return false;
        }
    }

    /**
     * Returns number of affected rows or false on query failure.
     *
     * @return int|boolean
     */
    public function _driver_affected_rows()
    {
        if (!$this->_error) {
            return mysqli_affected_rows($this->_dblink);
        } else {
            return false;
        }
    }

    /**
     * Returns number of rows in result reqource or false on failure.
     *
     * @return int|boolean
     */
    public function _driver_num_rows()
    {
        if (!$this->_error) {
            return mysqli_num_rows($this->_result);
        } else {
            return false;
        }
    }

    /**
     * Returns name of sql function used to get time.
     *
     * @return string
     */
    public function _driver_now()
    {
        return 'UNIX_TIMESTAMP()';
    }

    /**
     * Returns name of sql function used for "like" statement.
     *
     * @return string
     */
    public function _driver_like()
    {
        return 'LIKE';
    }

    /**
     * Returns concat sql part.
     *
     * @param string $input
     * @return string
     */
    public function _driver_concat($input)
    {
        $return = implode(', ', $input);
        return 'CONCAT(' . $return . ')';
    }

    /**
     * Returns list of tables in database.
     *
     * @return array
     */
    public function _driver_listtables()
    {
        return $this->GetCol('SELECT table_name FROM information_schema.tables 
				WHERE table_type = ? AND table_schema = ?', array('BASE TABLE', $this->_dbname));
    }

    /**
     * Begins transaction.
     *
     * @return int|false
     */
    public function _driver_begintrans()
    {
        return $this->Execute('BEGIN');
    }

    /**
     * Commits transaction.
     *
     * @return int|false
     */
    public function _driver_committrans()
    {
        return $this->Execute('COMMIT');
    }

    /**
     * Rollbacks transactions.
     *
     * @return int|false
     */
    public function _driver_rollbacktrans()
    {
        return $this->Execute('ROLLBACK');
    }

    /**
     * Locks table.
     *
     * @param string $table
     * @param string $locktype
     */
    public function _driver_locktables($table, $locktype = null)
    {
        $locktype = $locktype ? strtoupper($locktype) : 'WRITE';

        if (is_array($table)) {
            $this->Execute('LOCK TABLES ' . implode(' ' . $locktype . ', ', $table) . ' ' . $locktype);
        } else {
            $this->Execute('LOCK TABLES ' . $table . ' ' . $locktype);
        }
    }

    /**
     * Unlocks tables.
     */
    public function _driver_unlocktables()
    {
        $this->Execute('UNLOCK TABLES');
    }

    /**
     * Returns last inserted element id.
     *
     * @param string $table
     * @return int
     */
    public function _driver_lastinsertid($table = null)
    {
        return $this->GetOne('SELECT LAST_INSERT_ID()');
    }

    /**
     * Creates group concat sql part.
     *
     * @param string $field
     * @param string $separator
     * @param boolean $distinct
     * @return string
     */
    public function _driver_groupconcat($field, $separator = ',', $distinct = false)
    {
        if ($distinct === false) {
            return 'GROUP_CONCAT(' . $field . ' SEPARATOR \'' . $separator . '\')';
        } else {
            return 'GROUP_CONCAT(DISTINCT ' . $field . ' SEPARATOR \'' . $separator . '\')';
        }
    }

    /**
     * Sets connection encoding.
     *
     * @param string $name Connection name
     */
    public function _driver_setencoding($name)
    {
        $this->Execute('SET NAMES ?', array($name));
    }

    /**
    * Gets year for date.
    *
    * @param string $date
    * @return year string
    */
    public function _driver_year($date)
    {
        return 'YEAR(' . $date . ')';
    }

    /**
    * Gets month for date.
    *
    * @param string $date
    * @return month string
    */
    public function _driver_month($date)
    {
        return 'MONTH(' . $date . ')';
    }

    /**
    * Gets day for date.
    *
    * @param string $date
    * @return day string
    */
    public function _driver_day($date)
    {
        return 'DAY(' . $date . ')';
    }

    /**
     * Regular expression match for selected field.
     *
     * @param string $field
     * @param string $regexp
     * @return regexp match string
     */
    public function _driver_regexp($field, $regexp)
    {
        return $field . ' REGEXP \'' . $regexp . '\'';
    }

    /**
    * Check if database resource exists (table, view)
    *
    * @param string $name
    * @param int $type
    * @return exists boolean
    */
    public function _driver_resourceexists($name, $type)
    {
        switch ($type) {
            case LMSDB::RESOURCE_TYPE_TABLE:
            case LMSDB::RESOURCE_TYPE_VIEW:
                if ($type == LMSDB::RESOURCE_TYPE_TABLE) {
                    $type = 'BASE TABLE';
                } else {
                    $type = 'VIEW';
                }
                return $this->GetOne(
                    'SELECT COUNT(*) FROM information_schema.tables
					WHERE table_schema = ? AND table_name = ? AND table_type = ?',
                    array($this->_dbname, $name, $type)
                ) > 0;
                break;
            case LMSDB::RESOURCE_TYPE_COLUMN:
                list ($table_name, $column_name) = explode('.', $name);
                return $this->GetOne(
                    'SELECT COUNT(*) FROM information_schema.columns
					WHERE table_schema = ? AND table_name = ? AND column_name = ?',
                    array($this->_dbname, $table_name, $column_name)
                ) > 0;
                break;
            case LMSDB::RESOURCE_TYPE_CONSTRAINT:
                if (strpos($name, '.') !== false) {
                    list ($table_name, $constraint_name) = explode('.', $name);
                    return $this->GetOne(
                        'SELECT COUNT(*) FROM information_schema.table_constraints
						WHERE table_schema = ? AND table_name = ? AND constraint_name = ?',
                        array($this->_dbname, $table_name, $constraint_name)
                    ) > 0;
                } else {
                    return $this->GetOne(
                        'SELECT COUNT(*) FROM information_schema.table_constraints
						WHERE table_schema = ? AND constraint_name = ?',
                        array($this->_dbname, $name)
                    ) > 0;
                }
                break;
        }
    }
}

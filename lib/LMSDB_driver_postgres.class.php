<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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
 * LMSDB_driver_postgres
 *
 * PostgreSQL engine driver wrapper for LMS.
 *
 * @package LMS
 */
class LMSDB_driver_postgres extends LMSDB_common implements LMSDBDriverInterface
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
        if (!extension_loaded('pgsql')) {
            trigger_error('PostgreSQL extension not loaded!', E_USER_WARNING);
            $this->_loaded = false;
            return;
        }

        $this->_dbtype = LMSDB::POSTGRESQL;

        //$this->_version .= ' ('.preg_replace('/^.Revision: ([0-9.]+).*/','\1',$this->_revision).'/'.preg_replace('/^.Revision: ([0-9.]+).*/','\1','$Revision$').')';
        $this->_version .= '';
        $this->Connect($dbhost, $dbuser, $dbpasswd, $dbname);
        $this->Execute('SELECT set_config(\'lms.current_user\', ?, false)', array('0'));
    }

    /**
     * Returns database engine info.
     *
     * @return string
     */
    public function _driver_dbversion()
    {
        return $this->GetOne("SELECT split_part(version(),' ',2)");
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
        if (strpos($dbhost, ':') !== false) {
            list ($host, $port) = explode(':', $dbhost);
        } else {
            $host = $dbhost;
            $port = '';
        }

        $cstring = implode(' ', array(
            ($host != '' ? 'host=' . $host : ''),
            ($port != '' ? 'port=' . $port : ''),
            ($dbuser != '' ? 'user=' . $dbuser : ''),
            ($dbpasswd != '' ? 'password=' . $dbpasswd : ''),
            ($dbname != '' ? 'dbname=' . $dbname : ''),
            'connect_timeout=10'
        ));

        $this->_dblink = @pg_connect($cstring, PGSQL_CONNECT_FORCE_NEW);
        if ($this->_dblink) {
            $this->_dbhost = $dbhost;
            $this->_dbuser = $dbuser;
            $this->_dbname = $dbname;
            $this->_error = false;
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
        $this->_driver_disconnect();
    }

    /**
     * Disconnects driver from database.
     *
     * @return bool
     */
    public function _driver_disconnect()
    {
        $this->_loaded = false;
        @pg_close($this->_dblink);
    }

    /**
     * Selects database.
     *
     * @param string $dbname
     * @throws Exception
     */
    public function _driver_selectdb($dbname)
    {
        throw new Exception('PostgreSQL driver cannot change dbname. Sorry...');
    }

    /**
     * Returns errors.
     *
     * @return string
     */
    public function _driver_geterror()
    {
        if ($this->_dblink) {
            return pg_last_error($this->_dblink);
        } else {
            return 'We\'re not connected!';
        }
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
        $this->_result = @pg_query($this->_dblink, $query);
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
        return $this->_driver_execute($query);
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
            return @pg_fetch_array($result ? $result : $this->_result, null, PGSQL_ASSOC);
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
            return @pg_fetch_array($this->_result, null, PGSQL_NUM);
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
            return @pg_affected_rows($this->_result);
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
            return @pg_num_rows($this->_result);
        } else {
            return false;
        }
    }

    public function _quote_value($input)
    {
        if ($input === null) {
            return 'NULL';
        } elseif (gettype($input) == 'string') {
            return '\'' . @pg_escape_string($this->_dblink, $input) . '\'';
        } elseif (is_array($input)) {
            return $this->_quote_array($input);
        } else {
            return $input;
        }
    }

    /**
     * Returns name of sql function used to get time.
     *
     * @return string
     */
    public function _driver_now()
    {
        return 'EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer';
    }

    /**
     * Returns name of sql function used for "like" statement.
     *
     * @return string
     */
    public function _driver_like()
    {
        return 'ILIKE';
    }

    /**
     * Returns concat sql part.
     *
     * @param string $input
     * @return string
     */
    public function _driver_concat($input)
    {
        return implode(' || ', $input);
    }

    /**
     * Returns list of tables in database.
     *
     * @return array
     */
    public function _driver_listtables()
    {
        return $this->GetCol('SELECT relname AS name FROM pg_class WHERE relkind = \'r\' and relname !~ \'^pg_\' and relname !~ \'^sql_\'');
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

    private function _driver_locktables_filter_helper($table)
    {
        return !$this->_driver_resourceexists($table, LMSDB::RESOURCE_TYPE_VIEW);
    }

    /**
     * Locks table.
     *
     * @param string $table
     * @param string $locktype
     * @todo: locktype
     */
    public function _driver_locktables($table, $locktype = null)
    {
        if (is_array($table)) {
            $table = array_filter($table, array($this, '_driver_locktables_filter_helper'));
            $this->Execute('LOCK ' . implode(', ', $table));
        } else {
            $this->Execute('LOCK ' . $table);
        }
    }

    /**
     * Unlocks tables.
     *
     * @return boolean
     */
    public function _driver_unlocktables()
    {
        return true;
    }

    /**
     * Returns last inserted element id.
     *
     * @param string $table
     * @return int
     */
    public function _driver_lastinsertid($table = null)
    {
        return $this->GetOne('SELECT currval(\'' . $table . '_id_seq\')');
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
            return 'array_to_string(array_agg(' . $field . '), \'' . $separator . '\')';
        } else {
            return 'array_to_string(array_agg(DISTINCT ' . $field . '), \'' . $separator . '\')';
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
        return 'DATE_PART(\'year\', ' . $date . '::timestamp)';
    }

    /**
    * Gets month for date.
    *
    * @param string $date
    * @return month string
    */
    public function _driver_month($date)
    {
        return 'DATE_PART(\'month\', ' . $date . '::timestamp)';
    }

    /**
    * Gets day for date.
    *
    * @param string $date
    * @return day string
    */
    public function _driver_day($date)
    {
        return 'DATE_PART(\'day\', ' . $date . '::timestamp)';
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
        return $field . ' ~ \'' . $regexp . '\'';
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
                    $table_type = 'BASE TABLE';
                } else {
                    $table_type = 'VIEW';
                }
                return $this->GetOne(
                    'SELECT COUNT(*) FROM information_schema.tables
					WHERE table_catalog=? AND table_name=? AND table_type=?',
                    array($this->_dbname, $name, $table_type)
                ) > 0;
                break;
            case LMSDB::RESOURCE_TYPE_COLUMN:
                list ($table_name, $column_name) = explode('.', $name);
                return $this->GetOne(
                    'SELECT COUNT(*) FROM information_schema.columns
					WHERE table_catalog = ? AND table_name = ? AND column_name = ?',
                    array($this->_dbname, $table_name, $column_name)
                ) > 0;
                break;
            case LMSDB::RESOURCE_TYPE_CONSTRAINT:
                return $this->GetOne(
                    'SELECT COUNT(*) FROM information_schema.table_constraints
					WHERE table_catalog = ? AND constraint_name = ?',
                    array($this->_dbname, $name)
                ) > 0;
                break;
        }
    }
}

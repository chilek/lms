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
 * LMSDBDriverInterface
 *
 * Interface for database drivers.
 *
 * @package LMS
 */
interface LMSDBDriverInterface extends LMSDBInterface
{

    public function _driver_dbversion();

    public function _driver_connect($dbhost, $dbuser, $dbpasswd, $dbname);

    public function _driver_shutdown();

    public function _driver_geterror();

    public function _driver_disconnect();

    public function _driver_selectdb($dbname);

    public function _driver_execute($query);

    public function _driver_multi_execute($query);

    public function _driver_fetchrow_assoc($result = null);

    public function _driver_fetchrow_num();

    public function _driver_affected_rows();

    public function _driver_num_rows();

    public function _driver_now();

    public function _driver_like();

    public function _driver_concat($input);

    public function _driver_listtables();

    public function _driver_begintrans();

    public function _driver_committrans();

    public function _driver_rollbacktrans();

    public function _driver_locktables($table, $locktype = null);

    public function _driver_unlocktables();

    public function _driver_lastinsertid($table = null);

    public function _driver_groupconcat($field, $separator = ',');

    public function _driver_setencoding($name);

    public function _driver_year($date);

    public function _driver_month($date);

    public function _driver_day($date);
}

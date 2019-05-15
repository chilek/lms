<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2013 LMS Developers
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
 * LMSCache
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSCache
{
    protected $cache;
    
    public function __construct()
    {
        $this->cache = array();
    }

    /**
     * Returns cached variable
     *
     * @param string|int $key 1st level key
     * @param string|int $idx 2nd level key
     * @param string|int $name 3rd level key
     * @return mixed Cached variable
     */
    public function getCache($key, $idx = null, $name = null)
    {
        if (array_key_exists($key, $this->cache)) {
            if (!$idx) {
                return $this->cache[$key];
            } elseif (is_array($this->cache[$key]) && array_key_exists($idx, $this->cache[$key])) {
                if (!$name) {
                    return $this->cache[$key][$idx];
                } elseif (is_array($this->cache[$key][$idx]) && array_key_exists($name, $this->cache[$key][$idx])) {
                    return $this->cache[$key][$idx][$name];
                }
            }
        }
        return null;
    }
    
    /**
     * Caches variable
     *
     * @param string|int $key 1st level key
     * @param string|int $idx 2nd level key
     * @param string|int $name 3rd level key
     * @return mixed Cached variable
     */
    public function setCache($key = null, $idx = null, $name = null, $value = null)
    {
        if ($key === null) {
            $this->clearCache();
        } elseif ($idx === null) {
            $this->set1stlevelCache($key, $value);
        } elseif ($name === null) {
            $this->set2ndLevelCache($key, $idx, $value);
        } else {
            $this->set3rdLevelCache($key, $idx, $name, $value);
        }
    }
    
    /**
     * Clears cache
     */
    protected function clearCache()
    {
        $this->cache = array();
    }
    
    /**
     * Caches 1st level variable
     *
     * @param string|int $key 1st level key
     * @return mixed Cached variable
     */
    protected function set1stlevelCache($key, $value)
    {
        $this->cache[$key] = $value;
    }
    
    /**
     * Caches 2nd level variable
     *
     * @param string|int $key 1st level key
     * @param string|int $idx 2nd level key
     * @return mixed Cached variable
     */
    protected function set2ndLevelCache($key, $idx, $value)
    {
        if (key_exists($key, $this->cache)) {
            if (is_array($this->cache[$key])) {
                $this->cache[$key][$idx] = $value;
            } else {
                $this->cache[$key] = array($idx => $value);
            }
        } else {
            $this->cache[$key] = array($idx => $value);
        }
    }
    
    /**
     * Caches 3rd level variable
     *
     * @param string|int $key 1st level key
     * @param string|int $idx 2nd level key
     * @param string|int $name 3rd level key
     * @return mixed Cached variable
     */
    protected function set3rdLevelCache($key, $idx, $name, $value)
    {
        if (key_exists($key, $this->cache)) {
            if (is_array($this->cache[$key])) {
                if (key_exists($idx, $this->cache[$key])) {
                    if (is_array($this->cache[$key][$idx])) {
                        $this->cache[$key][$idx][$name] = $value;
                    } else {
                        $this->cache[$key][$idx] = array($name => $value);
                    }
                } else {
                    $this->cache[$key] = array($idx => array($name => $value));
                }
            } else {
                $this->cache[$key] = array($idx => array($name => $value));
            }
        } else {
            $this->cache[$key] = array($idx => array($name => $value));
        }
    }
}

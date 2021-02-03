<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2018 LMS Developers
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
 * LMSPagination
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
abstract class LMSPagination
{
    /** @var int Current page */
    protected $page;
    
    /** @var int Total records */
    protected $total;
    
    /** @var int Records per page */
    protected $per_page;
    
    /** @var int Total pages */
    protected $pages;

    /** @var string Instance name */
    protected $instance_name;

    /**
     * Constructs pagination
     *
     * @param int $page Current page
     * @param int $total Total records
     * @param int $per_page Records per page
     * @param string @instance_name Instance name
     */
    public function __construct($page, $total, $per_page, $instance_name = null)
    {
        $this->setPage($page);
        $this->setPerPage($per_page, false);
        $this->setTotal($total, false);
        $this->setInstanceName($instance_name);
        $this->calculatePages();
    }

    /**
     * Returns current page
     *
     * @return int Current page
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Returns total records
     *
     * @return int Total records
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Returns records per page
     *
     * @return int Records per page
     */
    public function getPerPage()
    {
        return $this->per_page;
    }

    /**
     * Returns instance name
     *
     * @return string Instance name
     */
    public function getInstanceName()
    {
        return $this->instance_name;
    }

    /**
     * Returns total pages
     *
     * @return int Total pages
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * Returns first record number on current page
     *
     * @return int first record number
     */
    public function getFirstOnPage()
    {
        return ($this->page - 1) * $this->per_page + 1;
    }

    /**
     * Returns last record number on current page
     *
     * @return int last record number
     */
    public function getLastOnPage()
    {
        $recordnr = $this->page * $this->per_page;
        if ($recordnr > $this->total) {
            $recordnr = $this->total;
        }
        return $recordnr;
    }
    
    /**
     * Returns previous page number
     *
     * @return int Previous page
     */
    public function getPreviousPage()
    {
        if ($this->page > 1) {
            return $this->page - 1;
        } else {
            return 1;
        }
    }
    
    /**
     * Returns next page number
     *
     * @return int Next page
     */
    public function getNextPage()
    {
        if ($this->page < $this->getPages()) {
            return $this->page + 1;
        } else {
            return $this->page;
        }
    }

    /**
     * Sets current page
     *
     * @param int $page Current page
     * @throws DomainException if current page is not an integer or is less than 1
     */
    public function setPage($page)
    {
        if (is_integer($page) === false) {
            throw new DomainException('Page must be integer!');
        } elseif ($page < 1) {
            throw new DomainException('Page must be greater than 0!');
        }
        $this->page = $page;
    }

    /**
     * Sets total records
     *
     * @param int $total Total records
     * @param int $recalculate_pages Recalculate pages flag
     * @throws DomainException if total records is not an integer or is less than 0
     */
    public function setTotal($total, $recalculate_pages = true)
    {
        if (is_integer($total) === false) {
            throw new DomainException('Total must be integer!');
        } elseif ($total < 0) {
            throw new DomainException('Total must be greater than or equal to 0!');
        }
        $this->total = $total;
        if ($recalculate_pages === true) {
            $this->calculatePages();
        }
    }

    /**
     * Sets records per page
     *
     * @param int $per_page Records per page
     * @param int $recalculate_pages Recalculate pages flag
     * @throws DomainException if records per page is not an integer or is less than 1
     */
    public function setPerPage($per_page, $recalculate_pages = true)
    {
        if (is_integer($per_page) === false) {
            throw new DomainException('Per page must be integer!');
        } elseif ($per_page < 1) {
            throw new DomainException('Per page must be greater than 0!');
        }
        $this->per_page = $per_page;
        if ($recalculate_pages === true) {
            $this->calculatePages();
        }
    }

    /**
     * Sets pagintation instance name
     * @param string @instance_name Instance name
     */
    public function setInstanceName($instance_name)
    {
        $this->instance_name = $instance_name;
    }

    /**
     * Calculates total pages
     */
    protected function calculatePages()
    {
        $this->pages = intval(ceil($this->total / $this->per_page));
    }
    
    /**
     * Determines if "go to" should be displayed
     *
     * @return boolean True if "go to" should be displated, false otherwise
     */
    abstract public function displayGoTo();
    
    /**
     * Determines if link to given page should be displayed
     *
     * @return boolean True if link should be displated, false otherwise
     */
    abstract public function displayLink($link_page);
}

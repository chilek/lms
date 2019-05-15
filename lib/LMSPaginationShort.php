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
 * LMSPaginationShort
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSPaginationShort extends LMSPagination
{
    
    /**
     * Determines if "go to" should be displayed
     *
     * @return boolean True if "go to" should be displated, false otherwise
     */
    public function displayGoTo()
    {
        if ($this->getPages() > 9) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Determines if link to given page should be displayed
     *
     * @return boolean True if link should be displated, false otherwise
     */
    public function displayLink($link_page)
    {
        if ($link_page < 3) {
            return true;
        } elseif ($link_page > $this->getPages() - 2) {
            return true;
        } elseif ($link_page > $this->getPage() - 3 && $link_page < $this->getPage() + 3) {
            return true;
        } else {
            return false;
        }
    }
}

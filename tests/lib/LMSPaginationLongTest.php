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

namespace LMS\Tests;

/**
 * LMSPaginationLongTest
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSPaginationLongTest extends \PHPUnit_Framework_TestCase
{
    
    public function testDisplayGoToAlwaysReturnsFalse()
    {
        $pagination = new \LMSPaginationLong(1, 1, 1);
        $this->assertEquals($pagination->displayGoTo(), false);
        $pagination->setTotal(100);
        $this->assertEquals($pagination->displayGoTo(), false);
    }
    
    public function testDisplayLinkAlwaysReturnsTrue()
    {
        $pagination = new \LMSPaginationLong(1, 100, 1);
        for ($i = 1; $i <= 100; $i++) {
            $this->assertEquals($pagination->displayGoTo($i), false);
        }
    }
}

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
 * LMSPaginationShortTest
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSPaginationShortTest extends \PHPUnit_Framework_TestCase
{
    
    public function testDisplayGoToReturnsFalseIfNumberOfPagesIsLessThanOrEqualNine()
    {
        $pagination = new \LMSPaginationShort(1, 1, 1);
        $this->assertEquals($pagination->displayGoTo(), false);
        for ($i = 2; $i <= 9; $i++) {
            $pagination->setTotal($i);
            $this->assertEquals(false, $pagination->displayGoTo(), "Fails when i == $i");
        }
    }
    
    public function testDisplayGoToReturnsFalseIfNumberOfPagesIsGreaterThanNine()
    {
        $pagination = new \LMSPaginationShort(1, 1, 1);
        for ($i = 10; $i < 99; $i++) {
            $pagination->setTotal($i++);
            $this->assertEquals(true, $pagination->displayGoTo(), "Fails when i == $i");
        }
    }
    
    public function testDisplayLinkReturnsTrueIfLinkPageIsInRangeOf2FromCurrentPage()
    {
        $pagination = new \LMSPaginationShort(10, 100, 1);
        for ($i = 8; $i <= 12; $i++) {
            $this->assertEquals(true, $pagination->displayLink($i), "Fails when i == $i");
        }
    }
    
    public function testDisplayLinkReturnsTrueIfLinkPageIsInRangeOf1FromFirstPage()
    {
        $pagination = new \LMSPaginationShort(10, 100, 1);
        for ($i = 1; $i <= 2; $i++) {
            $this->assertEquals(true, $pagination->displayLink($i), "Fails when i == $i");
        }
    }
    
    public function testDisplayLinkReturnsTrueIfLinkPageIsInRangeOf1FromLastPage()
    {
        $pagination = new \LMSPaginationShort(10, 100, 1);
        for ($i = $pagination->getPages() - 1; $i <= $pagination->getPages(); $i++) {
            $this->assertEquals(true, $pagination->displayLink($i), "Fails when i == $i");
        }
    }
    
    public function testDisplayLinkReturnsFalseIfLinkPageIsNotInRangeOf3FromFirstOrLastOrCurrentPage()
    {
        $pagination = new \LMSPaginationShort(10, 100, 1);
        for ($i = 3; $i < 8; $i++) {
            $this->assertEquals(false, $pagination->displayLink($i), "Fails when i == $i");
        }
        for ($i = 13; $i < 99; $i++) {
            $this->assertEquals(false, $pagination->displayLink($i), "Fails when i == $i");
        }
    }
}

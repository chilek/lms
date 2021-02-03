<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2015 LMS Developers
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
 * LMSPaginationTest
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSPaginationTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructorThowsDomainExceptionIfPageIsNotAnInteger()
    {
        $this->setExpectedException('DomainException', 'Page must be integer!');
        $page = 'not an integer';
        $total = 1;
        $per_page = 1;
        $pagination = \LMSPaginationFactory::getPagination($page, $total, $per_page);
    }

    public function testConstructorThowsDomainExceptionIfPageIsZero()
    {
        $this->setExpectedException('DomainException', 'Page must be greater than 0!');
        $page = 0;
        $total = 1;
        $per_page = 1;
        $pagination = \LMSPaginationFactory::getPagination($page, $total, $per_page);
    }

    public function testConstructorThowsDomainExceptionIfPageIsNegative()
    {
        $this->setExpectedException('DomainException', 'Page must be greater than 0!');
        $page = -1;
        $total = 1;
        $per_page = 1;
        $pagination = \LMSPaginationFactory::getPagination($page, $total, $per_page);
    }

    public function testConstructorThowsDomainExceptionIfTotalIsNotAnInteger()
    {
        $this->setExpectedException('DomainException', 'Total must be integer!');
        $page = 1;
        $total = 'not an integer';
        $per_page = 1;
        $pagination = \LMSPaginationFactory::getPagination($page, $total, $per_page);
    }

    public function testConstructorThowsDomainExceptionIfTotalNegative()
    {
        $this->setExpectedException('DomainException', 'Total must be greater than or equal to 0!');
        $page = 1;
        $total = -1;
        $per_page = 1;
        $pagination = \LMSPaginationFactory::getPagination($page, $total, $per_page);
    }

    public function testConstructorThowsDomainExceptionIfPerPageIsNotAnInteger()
    {
        $this->setExpectedException('DomainException', 'Per page must be integer!');
        $page = 1;
        $total = 1;
        $per_page = 'not an integer';
        $pagination = \LMSPaginationFactory::getPagination($page, $total, $per_page);
    }

    public function testConstructorThowsDomainExceptionIfPerPageIsZero()
    {
        $this->setExpectedException('DomainException', 'Per page must be greater than 0!');
        $page = 1;
        $total = 1;
        $per_page = 0;
        $pagination = \LMSPaginationFactory::getPagination($page, $total, $per_page);
    }

    public function testConstructorThowsDomainExceptionIfPerPageIsNegative()
    {
        $this->setExpectedException('DomainException', 'Per page must be greater than 0!');
        $page = 1;
        $total = 1;
        $per_page = -1;
        $pagination = \LMSPaginationFactory::getPagination($page, $total, $per_page);
    }

    public function testGetPageReturnsValueThatHasBeenSet()
    {
        $page = 1;
        $total = 1;
        $per_page = 1;
        $pagination = \LMSPaginationFactory::getPagination($page, $total, $per_page);
        $this->assertEquals($page, $pagination->getPage());
        $new_page = 2;
        $pagination->setPage($new_page);
        $this->assertEquals($new_page, $pagination->getPage());
    }

    public function testGetTotalReturnsValueThatHasBeenSet()
    {
        $page = 1;
        $total = 1;
        $per_page = 1;
        $pagination = \LMSPaginationFactory::getPagination($page, $total, $per_page);
        $this->assertEquals($total, $pagination->getTotal());
        $new_total = 2;
        $pagination->setTotal($new_total);
        $this->assertEquals($new_total, $pagination->getTotal());
    }

    public function testGetPerPageReturnsValueThatHasBeenSet()
    {
        $page = 1;
        $total = 1;
        $per_page = 1;
        $pagination = \LMSPaginationFactory::getPagination($page, $total, $per_page);
        $this->assertEquals($per_page, $pagination->getPerPage());
        $new_per_page = 2;
        $pagination->setPerPage($new_per_page);
        $this->assertEquals($new_per_page, $pagination->getPerPage());
    }
    
    public function testGetPagesReturnsDivisionOfTotalAndPerPageRoundedUp()
    {
        $page = 1;
        $total = 1;
        $per_page = 1;
        $pagination = \LMSPaginationFactory::getPagination($page, $total, $per_page);
        $this->assertEquals(1, $pagination->getPages());
        $pagination->setTotal(0);
        $this->assertEquals(0, $pagination->getPages());
        $pagination->setPerPage(10);
        $this->assertEquals(0, $pagination->getPages());
        $pagination->setTotal(35);
        $this->assertEquals(4, $pagination->getPages());
    }
    
    public function testFirstOnPage()
    {
        $page = 1;
        $total = 111;
        $per_page = 10;
        $pagination = \LMSPaginationFactory::getPagination($page, $total, $per_page);
        $this->assertEquals(1, $pagination->getFirstOnPage());
        $pagination->setPage(3);
        $this->assertEquals(21, $pagination->getFirstOnPage());
        $pagination->setPage(12);
        $this->assertEquals(111, $pagination->getFirstOnPage());
    }
    
    public function testLastOnPage()
    {
        $page = 1;
        $total = 111;
        $per_page = 10;
        $pagination = \LMSPaginationFactory::getPagination($page, $total, $per_page);
        $this->assertEquals(10, $pagination->getLastOnPage());
        $pagination->setPage(3);
        $this->assertEquals(30, $pagination->getLastOnPage());
        $pagination->setPage(12);
        $this->assertEquals(111, $pagination->getLastOnPage());
    }
}

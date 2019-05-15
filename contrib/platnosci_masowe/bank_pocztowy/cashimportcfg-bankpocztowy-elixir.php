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

$patterns[] = array(
    'id' => null,       // import source identifier (from 'cashsources' table)
    //    'pattern' => '/(\d{0,33}),(\d{0,8}),(\d{0,15}),(\d{0,8}),(\d{0,8}),"(\d{0,34})","(\d{0,34})","(\D{2,70})\s(\H{1,35})[|]{2,}.{0,140}?","(.{0,140}?)",\d{0,8},\d{0,8},"(.{0,140}?)"/',
    'pattern' => '/(\d{0,33}),(\d{0,8}),(\d{0,15}),(\d{0,8}),(\d{0,8}),"(\d{0,34})","(\d{0,34})","(.*?)","(.{0,140}?)",\d{0,8},\d{0,8},"(.*?)"/',
    'pid' => 0,         // customer ID position in expression
                        // if zero - we try to search ID by regexp,
                        // invoice number or customer name and forename in entire line - nie rozumiem do konca: czy jesli wartosc do przypisania bedzie wynosila 0 czy jesli pozostanie na aktualnym zerowym substringu/submatchu
    'pname' => 4,       // name position  old: 8
    'plastname' => 7,   // forename position  old: 9
    'pvalue' => 3,      // value position
    'pcomment' => 10,   // operation comment position
    'pdate' => 2,       // date position
                                                                                                                                                                                                                   
    'date_regexp' => '/([0-9]{4})([0-9]{2})([0-9]{2})/', // date format CHANGED (yyyymmdd)
    'pday' => 3,
    'pmonth' => 2,
    'pyear' => 1,
                                                                                                                                                                                                                   
    'pid_regexp' => '/","\d*(\d{4})"/',         // if 'pid' is not specified
                                                        // try to find it by regexp
                                                                // Komentarz takze wyzej. Jesli all good to pid = cztery ostatnie cyfry nr nadawcy
    'invoice_regexp' => '(\d{4})(\d{2}).*?(\d{4})"',// format of invoice number
                                                        // default %N/LMS/%Y CHANGED to yyyymmID
    'pinvoice_number' => 3,     // position of invoice number in $invoice_regexp
    'pinvoice_year' => 1,       // year position in $invoice_regexp
    'pinvoice_month' => 2,      // month position in $invoice_regexp

    'encoding' => 'ISO-8859-2', // imported data encoding (for conversion)

    'modvalue' => 0.01,         // if not zero do value = value * modvalue
    'use_line_hash' => true,    // create md5 hash for whole import line instead of
                                // time, value, customer name and comment
    'line_idx_hash' => true,    // include line number into hash data
);

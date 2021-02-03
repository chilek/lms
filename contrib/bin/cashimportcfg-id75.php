<?php

$patterns[] = array(
    'pattern' => '/^[0-9]{1,16}\|([0-9]{8})\|([0-9]+\.[0-9]+)\|[^|]+\|[0-9]{2}[0-9]{19}[0-9]{5}\|'
        .'[0-9]{2}[0-9]{19}([0-9]{5})\|([^|]+)$/',

    'pid' => 3,     // customer ID position in expression
                // if zero - we try to search ID by regexp,
                // invoice number or customer name and forename in entire line
    'pname' => -1,      // name position
    'plastname' => -1,  // forename position
    'pvalue' => 2,      // value position
    'pcomment' => 4,    // operation comment position
    'pdate' => 1,       // date position

    'date_regexp' => '/([0-9]{2})([0-9]{2})([0-9]{4})/', // date format (ddmmyyyy)
    'pday' => 1,
    'pmonth' => 2,
    'pyear' => 3,

    'pid_regexp' => '/.*ID[:\-\/]([0-9]{0,4}).*/i',     // if 'pid' is not specified
                                // try to find it by regexp

    'invoice_regexp' => '/.*(\d+)\/LMS\/([0-9]{4}).*/', // format of invoice number
                                // default %N/LMS/%Y
    'pinvoice_number' => 1,                 // position of invoice number in $invoice_regexp
    'pinvoice_year' => 2,                   // year position in $invoice_regexp
    'pinvoice_month' => 0,                  // month position in $invoice_regexp

    'encoding' => 'WINDOWS-1250',               // imported data encoding (for conversion)

    'modvalue' => 0,                    // if not zero do value = value * modvalue
    'use_line_hash' => true,                // create md5 hash for whole import line instead of
                                // time, value, customer name and comment
    'line_idx_hash' => 0,
    'comment_replace' => array(
        'from'  => '/\|+/',
        'to'    => "\n",
    ),
);

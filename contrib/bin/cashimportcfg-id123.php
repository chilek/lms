<?php

//function bzwbk_check_sum($data, $sum)
//{
//  $positions = intval($sum[1]);
//  $total = floatval($sum[2]);
//  $x = 0;
//  foreach ($data as $rec)
//      $x += floatval($rec['value']);
//  $x *= 100.0;
//  return ($positions == count($data) && $total == $x);
//}
//
//function bzwbk_time($time, $filename)
//{
//  if (preg_match('/.*PZ([0-9]{4}).*/', $filename, $matches)) {
//      $day = substr($matches[1], 0, 2);
//      $month = substr($matches[1], 2, 2);
//      $year = intval(date('Y'));
//      if (intval(date('n')) < $month)
//          $year--;
//      $time = mktime(0, 6, 0, $month, $day, $year);
//  }
//  return $time;
//}

$patterns[] = array(
    'pattern' => '/^[0-9]+,([0-9]+),([0-9]+),[0-9]+,[0-9]+,"[0-9]+","[0-9]{2}[0-9]{19}([0-9]{5})",'
        .'"[^"]*","[^"]*",[0-9]+,[0-9]+,"([^"]*)",.*$/',
    //'pattern_sum' => '/^999,([0-9]+),([0-9]+)$/',
    //'pattern_sum_check' => bzwbk_check_sum,

    'pid' => 3,     // customer ID position in expression
                // if zero - we try to search ID by regexp,
                // invoice number or customer name and forename in entire line
    'pname' => -1,      // name position
    'plastname' => -1,  // forename position
    'pvalue' => 2,      // value position
    'pcomment' => 4,    // operation comment position
    'pdate' => 1,       // date position

    'date_regexp' => '/([0-9]{4})([0-9]{2})([0-9]{2})/', // date format (yyyymmdd)
    'pday' => 3,
    'pmonth' => 2,
    'pyear' => 1,
    //'date_hook' => bzwbk_time,

    'pid_regexp' => '/.*ID[:\-\/]([0-9]{0,4}).*/i',     // if 'pid' is not specified
                                // try to find it by regexp

    'invoice_regexp' => '/.*(\d+)\/LMS\/([0-9]{4}).*/', // format of invoice number
                                // default %N/LMS/%Y
    'pinvoice_number' => 1,                 // position of invoice number in $invoice_regexp
    'pinvoice_year' => 2,                   // year position in $invoice_regexp
    'pinvoice_month' => 0,                  // month position in $invoice_regexp

    'encoding' => 'WINDOWS-1250',               // imported data encoding (for conversion)

    'modvalue' => 0.01,                 // if not zero do value = value * modvalue
    'use_line_hash' => true,                // create md5 hash for whole import line instead of
                                // time, value, customer name and comment
    'line_idx_hash' => 0,
    'comment_replace' => array(
        'from'  => '/\|+/',
        'to'    => "\n",
    ),
);

<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

$from = $_POST['from'];
$to = $_POST['to'];

// date format 'yyyy/mm/dd'
if ($from) {
	list ($year, $month, $day) = explode('/', $from);
	$unixfrom = mktime(0,0,0, $month, $day, $year);
} else {
	$from = date('Y/m/d',time());
	$unixfrom = mktime(0,0,0); //today
}
if ($to) {
	list ($year, $month, $day) = explode('/',$to);
	$unixto = mktime(23,59,59, $month, $day, $year);
} else {
	$to = date('Y/m/d',time());
	$unixto = mktime(23,59,59); //today
}

$income = $DB->GetAll('
	SELECT cash.linktechnology AS technology,
		COUNT(DISTINCT CASE WHEN c.type = 0 THEN c.id ELSE null END) AS privatecount,
		COUNT(DISTINCT CASE WHEN c.type = 1 THEN c.id ELSE null END) AS bussinesscount,
		COUNT(DISTINCT c.id) AS totalcount,
		SUM(CASE WHEN c.type = 0 THEN cash.value ELSE 0 END) * -1 AS privateincome,
		SUM(CASE WHEN c.type = 1 THEN cash.value ELSE 0 END) * -1 AS bussinessincome,
		SUM(cash.value) * -1 AS totalincome
	FROM cash
	JOIN customers c ON c.id = cash.customerid
	WHERE cash.type = 0 AND time >= ? AND time <= ?
	GROUP BY cash.linktechnology
	ORDER BY cash.linktechnology', array($unixfrom, $unixto));

$linktechnologies = array();
foreach ($LINKTECHNOLOGIES as $technologies)
	$linktechnologies = array_merge($linktechnologies, $technologies);

$layout['pagetitle'] = trans('UKE income report for period $a - $b', $from, $to);

$SMARTY->assign('income', $income);
$SMARTY->assign('linktechnologies', $linktechnologies);
$SMARTY->display('print/printukeincome.html');

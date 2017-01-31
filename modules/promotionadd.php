<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

$promotion = isset($_POST['promotion']) ? $_POST['promotion'] : NULL;

if ($promotion)
{
	foreach ($promotion as $key => $value)
		$promotion[$key] = trim($value);

	if ($promotion['name']=='' && $promotion['description']=='')
	{
		$SESSION->redirect('?m=promotionlist');
	}

	if ($promotion['name'] == '')
		$error['name'] = trans('Promotion name is required!');
	else if ($DB->GetOne('SELECT id FROM promotions WHERE name = ?', array($promotion['name'])))
		$error['name'] = trans('Specified name is in use!');

	if ($promotion['datefrom'] == '')
		$promotion['from'] = 0;
	elseif (preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $promotion['datefrom'])) {
		list ($y, $m, $d) = explode('/', $promotion['datefrom']);
		if (checkdate($m, $d, $y))
			$promotion['from'] = mktime(0, 0, 0, $m, $d, $y);
		else
			$error['datefrom'] = trans('Incorrect effective start time!');
	} else
		$error['datefrom'] = trans('Incorrect effective start time!');

	if ($promotion['dateto'] == '')
		$promotion['to'] = 0;
	elseif (preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $promotion['dateto'])) {
		list ($y, $m, $d) = explode('/', $promotion['dateto']);
		if (checkdate($m, $d, $y))
			$promotion['to'] = mktime(23, 59, 59, $m, $d, $y);
		else
			$error['dateto'] = trans('Incorrect effective end time!');
	} else
		$error['dateto'] = trans('Incorrect effective end time!');

	if ($promotion['to'] != 0 && $promotion['from'] != 0 && $promotion['to'] < $promotion['from'])
		$error['dateto'] = trans('Incorrect date range!');

	if (!$error) {
		$args = array(
			'name' => $promotion['name'],
			'description' => $promotion['description'],
			'datefrom' => $promotion['from'],
			'dateto' => $promotion['to'],
		);
		$DB->Execute('INSERT INTO promotions (name, description, datefrom, dateto)
			VALUES (?, ?, ?, ?)', array_values($args));
		$pid = $DB->GetLastInsertId('promotions');

		if ($SYSLOG) {
			$args[SYSLOG::RES_PROMO] = $pid;
			$SYSLOG->AddMessage(SYSLOG::RES_PROMO, SYSLOG::OPER_ADD, $args);
		}

		if (empty($promotion['reuse']))
			$SESSION->redirect('?m=promotioninfo&id=' . $pid);

		unset($promotion);
		$promotion['reuse'] = '1';
	}
}

$layout['pagetitle'] = trans('New Promotion');

$SMARTY->assign('error', $error);
$SMARTY->assign('promotion', $promotion);
$SMARTY->display('promotion/promotionadd.html');

?>

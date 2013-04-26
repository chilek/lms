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

	if (!$error) {
		$args = array(
			'name' => $promotion['name'],
			'description' => $promotion['description']
		);
		$DB->Execute('INSERT INTO promotions (name, description)
			VALUES (?, ?)', array_values($args));
		$pid = $DB->GetLastInsertId('promotions');

		if ($SYSLOG) {
			$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_PROMO]] = $pid;
			$SYSLOG->AddMessage(SYSLOG_RES_PROMO, SYSLOG_OPER_ADD, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_PROMO]));
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
$SMARTY->display('promotionadd.html');

?>

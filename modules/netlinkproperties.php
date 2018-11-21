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

function GetNetLinkRadioSectors($link) {
	global $DB;

	$result = $DB->GetRow('SELECT (CASE src WHEN ? THEN dstradiosector ELSE srcradiosector END) AS srcradiosector,
		(CASE src WHEN ? THEN srcradiosector ELSE dstradiosector END) AS dstradiosector
		FROM netlinks
		WHERE (src = ? AND dst = ?) OR (dst = ? AND src = ?)',
		array($link['id'], $link['id'], $link['id'], $link['devid'], $link['id'], $link['devid']));
	if (empty($result))
		$result = array();

	$result['dst'] = $DB->GetAll('SELECT id, name FROM netradiosectors WHERE netdev = ? '
		. ($link['type'] == 1 && $link['technology'] ? ' AND (technology = 0 OR technology = ' . intval($link['technology']) . ')' : '')
		. ' ORDER BY name', array($link['id']));
	$result['src'] = $DB->GetAll('SELECT id, name FROM netradiosectors WHERE netdev = ? '
		. ($link['type'] == 1 && $link['technology'] ? ' AND (technology = 0 OR technology = ' . intval($link['technology']) . ')' : '')
		. ' ORDER BY name', array($link['devid']));

	return $result;
}

function update_netlink_properties($id, $devid, $link) {
	global $LMS, $DB, $LINKTYPES, $LINKTECHNOLOGIES, $LINKSPEEDS;

	$result = new xajaxResponse();

	$isnetlink = intval($_GET['isnetlink']);
	if ($isnetlink)
		$LMS->SetNetDevLinkType($id, $devid, $link);
	else
		$LMS->SetNodeLinkType($devid, $link);

	switch ($link['type']) {
		case LINKTYPE_WIRE:
			$icon = 'lms-ui-icon-wired';
			break;
		case LINKTYPE_FIBER:
			$icon = 'lms-ui-icon-fiber';
			break;
		case LINKTYPE_WIRELESS:
			$icon = 'lms-ui-icon-wireless';
	}

	if ($isnetlink) {
		$srcradiosectorname = $DB->GetOne('SELECT name FROM netradiosectors WHERE id = ?', array($link['srcradiosector']));
		$dstradiosectorname = $DB->GetOne('SELECT name FROM netradiosectors WHERE id = ?', array($link['dstradiosector']));
	} else
		$radiosectorname = $DB->GetOne('SELECT name FROM netradiosectors WHERE id = ?', array($link['radiosector']));

	$tech_content = ($link['technology'] ? $LINKTECHNOLOGIES[$link['type']][$link['technology']]
			. (!$isnetlink ? ($radiosectorname ? " ($radiosectorname)" : '')
				: ($srcradiosectorname || $dstradiosectorname ? ' ('
					. ($srcradiosectorname ? $srcradiosectorname : '-')
					. '/' . ($dstradiosectorname ? $dstradiosectorname : '-') . ')' : ''))
			: '');

	$speed_content = $LINKSPEEDS[$link['speed']];

	$port_content = '<i class="' . $icon . '" 
			 title="<span class=&quot;nobr;&quot;>' . trans("Link type:") . ' ' . $LINKTYPES[$link['type']] . '<br>'
			. (!$isnetlink ? ($radiosectorname ? trans("Radio sector:") . ' ' . $radiosectorname . '<br>' : '')
				: ($srcradiosectorname ? trans("Radio sector:") . ' ' . $srcradiosectorname . '<br>' : '')
					. ($dstradiosectorname ? trans("Destination radio sector:") . ' ' . $dstradiosectorname . '<br>' : ''))
			. ($link['technology'] ? trans("Link technology:") . ' ' . $LINKTECHNOLOGIES[$link['type']][$link['technology']] . '<br>' : '')
			. trans("Link speed:") . ' ' . $LINKSPEEDS[$link['speed']]
			. '</span>"></i>';

	$result->call('update_netlink_info', $tech_content, $speed_content, $port_content);

	return $result;
}

function get_radio_sectors_for_technology($technology) {
	global $DB;

	$result = new xajaxResponse();

	$isnetlink = intval($_GET['isnetlink']);
	$technology = intval($technology);
	$id = intval($_GET['id']);
	$devid = intval($_GET['devid']);

	$radiosectors = array();
	if ($isnetlink)
		$radiosectors['srcradiosector'] = $DB->GetAll('SELECT id, name FROM netradiosectors WHERE netdev = ?'
			. ($technology ? ' AND (technology = 0 OR technology = ' . $technology . ')' : '')
			. ' ORDER BY name',
			array($devid));
	$radiosectors[$isnetlink ? 'dstradiosector' : 'radiosector'] = $DB->GetAll('SELECT id, name FROM netradiosectors WHERE netdev = ?'
		. ($technology ? ' AND (technology = 0 OR technology = ' . $technology . ')' : '')
		. ' ORDER BY name',
		array($id));
	$result->call('update_radio_sector_list', $radiosectors);

	return $result;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array('update_netlink_properties', 'get_radio_sectors_for_technology'));
$SMARTY->assign('xajax', $LMS->RunXajax());

$layout['pagetitle'] = trans('Select link properties');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$devid = isset($_GET['devid']) ? intval($_GET['devid']) : 0;
$isnetlink = isset($_GET['isnetlink']) ? intval($_GET['isnetlink']) : 0;

if ($isnetlink)
	$link = $LMS->GetNetDevLinkType($id, $devid);
else
	$link = $DB->GetRow("SELECT linktype AS type, linktechnology AS technology,
		linkspeed AS speed, linkradiosector AS radiosector FROM nodes
		WHERE netdev = ? AND id = ?", array($id, $devid));

$link['id'] = $id;
$link['devid'] = $devid;
$link['isnetlink'] = $isnetlink;

$SMARTY->assign('link', $link);
if ($isnetlink)
	$radiosectors = GetNetLinkRadioSectors($link);
else
	$radiosectors = $DB->GetAll('SELECT id, name FROM netradiosectors WHERE netdev = ?'
		. ($link['technology'] ? ' AND (technology = ' . $link['technology'] . ' OR technology = 0)' : '')
		. ' ORDER BY name', array($id));
$SMARTY->assign('radiosectors', $radiosectors);
$SMARTY->display('netdev/netlinkproperties.html');

?>

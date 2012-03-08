<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

function update_netlink_properties($id, $devid, $linktype, $linkspeed)
{
	global $LMS, $LINKSPEEDS;

	$result = new xajaxResponse();

	if ($_GET['isnetlink'])
		$LMS->SetNetDevLinkType($id, $devid, $linktype, $linkspeed);
	else
		$LMS->SetNodeLinkType($devid, $linktype, $linkspeed);

	switch ($linktype) {
		case 0: case 2:
			$bitmap = 'netdev_takenports.gif';
			break;
		case 1:
			$bitmap = 'netdev_takenports.gif';
	}

	$contents = "<IMG src=\"img/".$bitmap
		."\" alt=\"[ ".trans("Change connection type")." ]\" title=\"[ ".trans("Change connection type")." ]\""
		." onmouseover=\"popup('".$LINKSPEEDS[$linkspeed]."');\" onmouseout=\"pophide();\">";
	$result->call('update_netlink_info', $contents);

	return $result;
}

require(LIB_DIR.'/xajax/xajax_core/xajax.inc.php');

$xajax = new xajax();
$xajax->configure('errorHandler', true);
$xajax->configure('javascript URI', 'img');
$xajax->register(XAJAX_FUNCTION, 'update_netlink_properties');
$xajax->processRequest();

$SMARTY->assign('xajax', $xajax->getJavascript());

$layout['pagetitle'] = trans('Select link properties');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$devid = isset($_GET['devid']) ? intval($_GET['devid']) : 0;
$isnetlink = isset($_GET['isnetlink']) ? intval($_GET['isnetlink']) : 0;

if ($isnetlink)
	$link = $LMS->GetNetDevLinkType($id, $devid);
else
	$link = $DB->GetRow("SELECT linktype AS type, linkspeed AS speed FROM nodes WHERE netdev = ?",
		array($id));

$link['id'] = $id;
$link['devid'] = $devid;
$link['isnetlink'] = $isnetlink;

$SMARTY->assign('link', $link);
$SMARTY->display('netlinkproperties.html');

?>

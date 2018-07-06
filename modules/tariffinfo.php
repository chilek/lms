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

$netid = isset($_GET['netid']) ? intval($_GET['netid']) : NULL;
$id = isset($_GET['id']) ? intval($_GET['id']) : NULL;

if(!$LMS->TariffExists($_GET['id']) || ($netid != 0 && !$LMS->NetworkExists($netid))) {
       $SESSION->redirect('?m=tarifflist');
}

$tariff = $LMS->GetTariff($id, $netid);

$tariff['promotions'] = $DB->GetAll('SELECT DISTINCT p.name, p.id
    FROM promotionassignments a
    JOIN promotionschemas s ON (s.id = a.promotionschemaid)
    JOIN promotions p ON (p.id = s.promotionid)
    WHERE a.tariffid = ? OR s.ctariffid = ?
    ORDER BY p.name', array($tariff['id'], $tariff['id']));

if (!empty($tariff['numberplanid']))
	$tariff['numberplan'] = $DB->GetRow('SELECT template, period FROM numberplans WHERE id = ?', array($tariff['numberplanid']));

$layout['pagetitle'] = trans('Subscription Info: $a',$tariff['name']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

// if selected tariff is phone tariff then load prefixes assigned to this tariff
if ($tariff['type'] == SERVICE_PHONE) {
	$SMARTY->assign('voip_fields', $DB->GetRow("SELECT
                                                    vt.name as pricelist, vt.id as pricelist_id,
                                                    vr.name as rule_name, vr.id as rule_name_id
                                                FROM
                                                    tariffs t
                                                    left join voip_tariffs vt on t.voip_tariff_id = vt.id
                                                    left join voip_rule_groups vr on t.voip_tariff_rule_id = vr.id
                                                WHERE
                                                    t.id = ?", array($id)));
}

$SMARTY->assign('netid', $netid);
$SMARTY->assign('tariff',$tariff);
$SMARTY->assign('tariffs',$LMS->GetTariffs());
$SMARTY->assign('networks',$LMS->GetNetworks());
$SMARTY->display('tariff/tariffinfo.html');

?>

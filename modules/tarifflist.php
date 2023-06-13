<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

function GetTariffList($order = 'name,asc', $type = null, $access = 0, $customergroupid = null, $promotionid = null, $state = null, $tags = null, $tax = null, $netflag = null, $flags = null)
{
    $DB = LMSDB::getInstance();
    $LMS = LMS::getInstance();

    if ($order == '') {
        $order = 'name,asc';
    }

    list($order,$direction) = sscanf($order, '%[^,],%s');

    ($direction == 'desc') ? $direction = 'desc' : $direction = 'asc';

    switch ($order) {
        case 'id':
            $sqlord = " ORDER BY id $direction";
            break;
        case 'description':
            $sqlord = " ORDER BY t.description $direction, t.name";
            break;
        case 'value':
            $sqlord = " ORDER BY t.value $direction, t.name";
            break;
        case 'netvalue':
            $sqlord = " ORDER BY t.netvalue $direction, t.name";
            break;
        case 'downrate':
            $sqlord = " ORDER BY t.downrate $direction, t.name";
            break;
        case 'downceil':
            $sqlord = " ORDER BY t.downceil $direction, t.name";
            break;
        case 'uprate':
            $sqlord = " ORDER BY t.uprate $direction, t.name";
            break;
        case 'upceil':
            $sqlord = " ORDER BY t.upceil $direction, t.name";
            break;
        case 'count':
            $sqlord = " ORDER BY customerscount $direction, t.name";
            break;
        default:
            $sqlord = " ORDER BY t.name, t.value DESC";
            break;
    }

    $totalincome = array();
    $totalcustomers = 0;
    $totalcount = 0;
    $totalactivecount = 0;

    if ($tarifflist = $DB->GetAllByKey('SELECT t.id, t.name, t.value, t.currency,
			taxes.label AS tax, taxes.value AS taxvalue, t.datefrom, t.dateto, prodid, t.disabled,
			t.uprate, t.downrate, t.upceil, t.downceil, t.climit, t.plimit,
			t.uprate_n, t.downrate_n, t.upceil_n, t.downceil_n, t.climit_n, t.plimit_n,
			t.description, t.period, a.customerscount, a.count, a.value AS sumval,
            t.netvalue, t.flags,
            (CASE WHEN t.flags & ' . TARIFF_FLAG_NET_ACCOUNT . ' > 0 THEN 1 ELSE 0 END) AS netflag
			FROM tariffs t
			LEFT JOIN (
			    SELECT a.tariffid, COUNT(*) AS count,
				    COUNT(DISTINCT a.customerid) AS customerscount,
				    SUM((((tt.value * (100 - a.pdiscount)) / 100.0) - a.vdiscount) *
						(CASE tt.period
							WHEN '.MONTHLY.' THEN 1
							WHEN '.QUARTERLY.' THEN 1.0 / 3
							WHEN '.YEARLY.' THEN 1.0 / 12
							WHEN '.HALFYEARLY.' THEN 1.0 / 6
							ELSE (CASE a.period
								WHEN '.MONTHLY.' THEN 1
								WHEN '.QUARTERLY.' THEN 1.0 / 3
								WHEN '.YEARLY.' THEN 1.0 / 12
								WHEN '.HALFYEARLY.' THEN 1.0 / 6
								ELSE 0 END)
						END) * a.count
					) AS value
				FROM assignments a
				JOIN tariffs tt ON (tt.id = tariffid)'
                . ($customergroupid ? ' JOIN vcustomerassignments cc ON (cc.customerid = a.customerid)
                    AND cc.customergroupid = ' . intval($customergroupid) : '')
                . ' WHERE a.commited = 1'
                . ($promotionid ? ' AND tt.id IN (SELECT pa.tariffid
                    FROM promotionassignments pa
                    JOIN promotionschemas ps ON (ps.id = pa.promotionschemaid)
                    WHERE ps.promotionid = ' . intval($promotionid) . ')' : '')
                . ' GROUP BY a.tariffid
			) a ON (a.tariffid = t.id)
			LEFT JOIN taxes ON (t.taxid = taxes.id)
			WHERE 1=1'
            . ($customergroupid || $promotionid ? ' AND a.tariffid IS NOT NULL' : '')
            . (!empty($tags) ? ' AND t.id IN (SELECT DISTINCT tariffid FROM tariffassignments WHERE tarifftagid IN (' . implode(',', $tags) . '))' : '')
            .($type ? ' AND t.type = '.intval($type) : '')
            . ($netflag == 1 ? ' AND t.flags & ' . TARIFF_FLAG_NET_ACCOUNT . ' > 0' : '')
            . ($netflag == 2 ? ' AND t.flags & ' . TARIFF_FLAG_NET_ACCOUNT . ' = 0' : '')
            . (empty($flags) ? '' : ' AND t.flags & ' . $flags . ' > 0')
            . ($tax ? ' AND taxes.id = '.intval($tax) : '')
            .($access ? ' AND t.authtype & ' . intval($access) . ' > 0' : '')
            .($promotionid ? ' AND t.id IN (SELECT pa.tariffid
				FROM promotionassignments pa
			JOIN promotionschemas ps ON (ps.id = pa.promotionschemaid)
			WHERE ps.promotionid = ' .intval($promotionid).')' : '')
            .($state==1 ? ' AND t.disabled=0 ' : '')
            .($state==2 ? ' AND t.disabled=1 ' : '')
            .($sqlord != '' ? $sqlord : ''), 'id')) {
        $unactive = $DB->GetAllByKey('SELECT tariffid, COUNT(*) AS count,
				SUM((((x.value * (100 - x.pdiscount)) / 100.0) - x.vdiscount) *
					(CASE x.period
						WHEN '.MONTHLY.' THEN 1
						WHEN '.QUARTERLY.' THEN 1.0 / 3
						WHEN '.YEARLY.' THEN 1.0 / 12
						WHEN '.HALFYEARLY.' THEN 1.0 / 6
						ELSE (CASE x.aperiod
							WHEN '.MONTHLY.' THEN 1
							WHEN '.QUARTERLY.' THEN 1.0 / 3
							WHEN '.YEARLY.' THEN 1.0 / 12
							WHEN '.HALFYEARLY.' THEN 1.0 / 6
							ELSE 0 END)
					END) * x.count
				) AS value
			FROM (
                SELECT a.tariffid, a.count, t.period, a.period AS aperiod, a.pdiscount, a.vdiscount, t.value
                FROM assignments a
                JOIN tariffs t ON (t.id = a.tariffid)'
                . ($customergroupid ? ' JOIN vcustomerassignments cc ON (cc.customerid = a.customerid)' : '')
                . ($tax ? ' JOIN taxes ON (t.taxid = taxes.id)' : '')
                . ' WHERE a.commited = 1 AND (
					a.suspended = 1
					OR a.datefrom > ?NOW?
					OR (a.dateto <= ?NOW? AND a.dateto != 0)
					OR EXISTS (
						SELECT 1 FROM assignments b
						WHERE b.customerid = a.customerid
							AND liabilityid IS NULL AND tariffid IS NULL
							AND b.datefrom <= ?NOW? AND (b.dateto > ?NOW? OR b.dateto = 0)
					)
				)'
                . ($type ? ' AND t.type = '.intval($type) : '')
                . ($netflag == 1 ? ' AND t.flags & ' . TARIFF_FLAG_NET_ACCOUNT . ' > 0' : '')
                . ($netflag == 2 ? ' AND t.flags & ' . TARIFF_FLAG_NET_ACCOUNT . ' = 0' : '')
                . ($tax ? ' AND taxes.id = '.intval($tax) : '')
                . ($customergroupid ? ' AND cc.customergroupid = '.intval($customergroupid) : '')
                . ($promotionid ? ' AND t.id IN (SELECT pa.tariffid
					FROM promotionassignments pa
				JOIN promotionschemas ps ON (ps.id = pa.promotionschemaid)
					WHERE ps.promotionid = ' .intval($promotionid).')' : '')
            .') x GROUP BY tariffid', 'tariffid');

        foreach ($tarifflist as $idx => &$row) {
            // get tariff price variants
            $priceVariants = $LMS->getTariffPriceVariants($row['id']);
            $row['price_variants'] = !empty($priceVariants) ? $priceVariants : array();
            // count of 'active' assignments
            $row['activecount'] = $row['count'] - (isset($unactive[$row['id']]) ? $unactive[$row['id']]['count'] : 0);
            // avg monthly income
            $row['income'] = $row['sumval'] - (isset($unactive[$row['id']]) ? $unactive[$row['id']]['value'] : 0);

            if (!isset($totalincome[$row['currency']])) {
                $totalincome[$row['currency']] = 0;
            }
            $totalincome[$row['currency']] += $row['income'];
            $totalcount += $row['count'];
            $totalcustomers += $row['customerscount'];
            $totalactivecount += $row['activecount'];
        }
        unset($row);

        switch ($order) {
            case 'income':
                foreach ($tarifflist as $idx => $row) {
                    $table['idx'][] = $idx;
                    $table['income'][] = $row['income'];
                }
                if (isset($table)) {
                    array_multisort($table['income'], ($direction == "desc" ? SORT_DESC : SORT_ASC), $table['idx']);
                    foreach ($table['idx'] as $idx) {
                        $ntarifflist[] = $tarifflist[$idx];
                    }

                    $tarifflist = $ntarifflist;
                }
                break;
        }
    }

    if (!empty($tarifflist)) {
        $tarifftags = $DB->GetAll('SELECT t.id AS tariff_id, t.name AS tariff_name, tt.name AS tag_name, tt.id AS tag_id
			FROM tariffs t
			JOIN tariffassignments ta ON (ta.tariffid = t.id)
			JOIN tarifftags tt ON (ta.tarifftagid = tt.id)'
            . (!empty($tags) ? ' WHERE tarifftagid IN (' . implode(',', $tags). ')' : ''));
        if (!empty($tarifftags)) {
            foreach ($tarifftags as $tarifftag) {
                if (isset($tarifflist[$tarifftag['tariff_id']])) {
                    if (!isset($tarifflist[$tarifftag['tariff_id']]['tags'])) {
                        $tarifflist[$tarifftag['tariff_id']]['tags'] = array();
                    }
                    $tarifflist[$tarifftag['tariff_id']]['tags'][] = $tarifftag;
                }
            }
        }
    }

    $tarifflist['total'] = empty($tarifflist) ? 0 : count($tarifflist);
    $tarifflist['totalincome'] = $totalincome;
    $tarifflist['totalcustomers'] = $totalcustomers;
    $tarifflist['totalcount'] = $totalcount;
    $tarifflist['totalactivecount'] = $totalactivecount;
    $tarifflist['order'] = $order;
    $tarifflist['direction'] = $direction;

    return $tarifflist;
}

$LMS = LMS::getInstance();
$DB = LMSDB::getInstance();

if (!isset($_POST['o']) && !isset($_GET['o'])) {
    $SESSION->restore('tlo', $o);
} elseif (isset($_GET['o'])) {
    $o = $_GET['o'];
} else {
    $o = $_POST['o'];
}
$SESSION->save('tlo', $o);

if (!isset($_POST['t']) && !isset($_GET['t'])) {
    $SESSION->restore('tlt', $t);
} elseif (isset($_GET['t'])) {
    $t = $_GET['t'];
} else {
    $t = $_POST['t'];
}
$SESSION->save('tlt', $t);

if (!isset($_POST['a']) && !isset($_GET['a'])) {
    $SESSION->restore('tla', $a);
} elseif (isset($_GET['a'])) {
    $a = $_GET['a'];
} else {
    $a = $_POST['a'];
}
$SESSION->save('tla', $a);

if (!isset($_POST['g'])) {
    $SESSION->restore('tlg', $g);
} else {
    $g = $_POST['g'];
}
$SESSION->save('tlg', $g);

if (!isset($_POST['p'])) {
    $SESSION->restore('tlp', $p);
} else {
    $p = $_POST['p'];
}
$SESSION->save('tlp', $p);

if (!isset($_POST['s'])) {
    $SESSION->restore('tls', $s);
} else {
    $s = $_POST['s'];
}
$SESSION->save('tls', $s);

if (!isset($_POST['tg'])) {
    $SESSION->restore('tltg', $tg);
} else {
    $tg = $_POST['tg'];
}
if (isset($_GET['tag'])) {
    if (!is_array($tg)) {
        $tg = array();
    }
    if ($newtag = intval($_GET['tag'])) {
        array_push($tg, $newtag);
        $tg = array_unique($tg);
    }
}
$SESSION->save('tltg', $tg);

if (!isset($_POST['tax']) && !isset($_GET['tax'])) {
    $SESSION->restore('tltax', $tax);
} elseif (isset($_GET['tax'])) {
    $tax = $_GET['tax'];
} else {
    $tax = $_POST['tax'];
}
$SESSION->save('tltax', $tax);

if (!isset($_POST['netflag']) && !isset($_GET['netflag'])) {
    $SESSION->restore('tlnetflag', $netflag);
} elseif (isset($_GET['netflag'])) {
    $netflag = $_GET['netflag'];
} else {
    $netflag = $_POST['netflag'];
}
$SESSION->save('tlnetflag', $netflag);

if (!isset($_POST['flags']) && !isset($_GET['flags'])) {
    $SESSION->restore('tlflags', $flags);
} elseif (isset($_GET['flags'])) {
    $flags = $_GET['flags'];
} else {
    $flags = $_POST['flags'];
}
$SESSION->save('tllags', $flags);

$tarifflist = GetTariffList($o, $t, $a, $g, $p, $s, $tg, $tax, $netflag, $flags);

$customergroups = $LMS->CustomergroupGetAll();

$promotions = $DB->GetAll('SELECT id, name FROM promotions ORDER BY name');

$listdata['total'] = $tarifflist['total'];
$listdata['totalincome'] = $tarifflist['totalincome'];
$listdata['totalcustomers'] = $tarifflist['totalcustomers'];
$listdata['totalcount'] = $tarifflist['totalcount'];
$listdata['totalactivecount'] = $tarifflist['totalactivecount'];
$listdata['type'] = $t;
$listdata['access'] = $a;
$listdata['customergroupid'] = $g;
$listdata['promotionid'] = $p;
$listdata['state'] = $s;
$listdata['tags'] = $tg;
$listdata['tax'] = $tax;
$listdata['netflag'] = $netflag;
$listdata['flags'] = $flags;
$listdata['order'] = $tarifflist['order'];
$listdata['direction'] = $tarifflist['direction'];

unset($tarifflist['total']);
unset($tarifflist['totalincome']);
unset($tarifflist['totalcustomers']);
unset($tarifflist['totalcount']);
unset($tarifflist['totalactivecount']);
unset($tarifflist['order']);
unset($tarifflist['direction']);

$layout['pagetitle'] = trans('Subscription List');

$SESSION->add_history_entry();

$SMARTY->assign('tarifflist', $tarifflist);
$SMARTY->assign('taxeslist', $LMS->GetTaxes());
$SMARTY->assign('tags', $LMS->TarifftagGetAll());
$SMARTY->assign('customergroups', $customergroups);
$SMARTY->assign('promotions', $promotions);
$SMARTY->assign('listdata', $listdata);

$SMARTY->display('tariff/tarifflist.html');

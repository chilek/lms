<?php

/*
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

function macformat($mac, $escape = false)
{
    global $DB;

    $res = str_replace('-', ':', $mac);

    // allow eg. format "::ab:3::12", only whole addresses
    if (preg_match('/^([0-9a-f]{0,2}):([0-9a-f]{0,2}):([0-9a-f]{0,2}):([0-9a-f]{0,2}):([0-9a-f]{0,2}):([0-9a-f]{0,2})$/i', $mac, $arr)) {
        $res = '';
        for ($i=1; $i<=6; $i++) {
            if ($i > 1) {
                $res .= ':';
            }
            if (strlen($arr[$i]) == 1) {
                $res .= '0';
            }
            if (strlen($arr[$i]) == 0) {
                $res .= '00';
            }

            $res .= $arr[$i];
        }
    } else // other formats eg. cisco xxxx.xxxx.xxxx or parts of addresses
    {
        $tmp = preg_replace('/[^0-9a-f]/i', '', $mac);

        if (strlen($tmp) == 12) { // we've the whole address
            if (check_mac($tmp)) {
                $res = $tmp;
            }
        }
    }

    if ($escape) {
        $res = $DB->Escape("%$res%");
    }
    return $res;
}

$mode = '';

if (!empty($_POST['qs'])) {
    foreach ($_POST['qs'] as $key => $value) {
        if (!empty($value)) {
            $mode = $key;
            $search = $value;
        }
    }
    $search = urldecode(trim($search));
} elseif (!empty($_GET['what'])) {
    $search = urldecode(trim($_GET['what']));
    $mode = $_GET['mode'];
}
$sql_search = $DB->Escape("%$search%");

if (isset($qs_properties[$mode])) {
    $properties = $qs_properties[$mode];
} else {
    $properties = array();
}

switch ($mode) {
    case 'customer':
        if (isset($_GET['ajax'])) { // support for AutoSuggest
            $candidates = $DB->GetAll("SELECT c.id, cc.contact AS email, full_address AS address,
				post_name, post_full_address AS post_address, deleted,
			    " . $DB->Concat('UPPER(lastname)', "' '", 'c.name') . " AS customername,
			    va.name AS location_name, va.address AS location_address,
			    c.status
				FROM customerview c
				LEFT JOIN customer_addresses ca ON ca.customer_id = c.id AND ca.type IN (?, ?)
				LEFT JOIN vaddresses va ON va.id = ca.address_id
				LEFT JOIN customercontacts cc ON cc.customerid = c.id AND (cc.type & ?) > 0
				WHERE " . (empty($properties) || isset($properties['id']) ? (preg_match('/^[0-9]+$/', $search) ? 'c.id = ' . $search : '1=0') : '1=0')
                    . (empty($properties) || isset($properties['name']) ? " OR LOWER(" . $DB->Concat('lastname', "' '", 'c.name') . ") ?LIKE? LOWER($sql_search)" : '')
                    . (empty($properties) || isset($properties['address']) ? " OR LOWER(full_address) ?LIKE? LOWER($sql_search)" : '')
                    . (empty($properties) || isset($properties['post_name']) ? " OR LOWER(post_name) ?LIKE? LOWER($sql_search)" : '')
                    . (empty($properties) || isset($properties['post_address']) ? " OR LOWER(post_full_address) ?LIKE? LOWER($sql_search)" : '')
                    . (empty($properties) || isset($properties['location_name']) ? " OR LOWER(va.name) ?LIKE? LOWER($sql_search)" : '')
                    . (empty($properties) || isset($properties['location_address']) ? " OR LOWER(va.address) ?LIKE? LOWER($sql_search)" : '')
                    . (empty($properties) || isset($properties['email']) ? " OR LOWER(cc.contact) ?LIKE? LOWER($sql_search)" : '') . "
				ORDER by deleted, customername, cc.contact, full_address
				LIMIT ?", array(DEFAULT_LOCATION_ADDRESS, LOCATION_ADDRESS, CONTACT_EMAIL, intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15))));

            $result = array();
            if ($candidates) {
                $customer_count = array();
                foreach ($candidates as $idx => $row) {
                    $customername = $row['customername'];
                    if (!isset($customer_count[$customername])) {
                        $customer_count[$customername] = 0;
                    }
                    $customer_count[$customername]++;
                }
                foreach ($candidates as $idx => $row) {
                    $name = truncate_str($row['customername'], 50);

                    $name_classes = array();
                    if ($row['deleted']) {
                        $name_classes[] = 'blend';
                    }
                    $name_classes[] = 'lms-ui-suggestion-customer-status-' . $CSTATUSES[$row['status']]['alias'];
                    $name_class = implode(' ', $name_classes);

                    $description = '';
                    $description_class = '';
                    $action = '?m=customerinfo&id=' . $row['id'];

                    if ((empty($properties) || isset($properties['name'])) && $customer_count[$row['customername']]) {
                        $description = $row['address'];
                        if (!empty($row['post_address'])) {
                            $description .= '<BR>' . $row['post_address'];
                            if (!empty($row['post_name'])) {
                                $description .= '<BR>' . $row['post_name'];
                            }
                        }
                    } else if ((empty($properties) || isset($properties['id'])) && preg_match("~^$search\$~i", $row['id'])) {
                        $description = trans('Id:') . ' ' . $row['id'];
                    } else if ((empty($properties) || isset($properties['name'])) && preg_match("~$search~i", $row['customername'])) {
                        $description = '';
                    } else if ((empty($properties) || isset($properties['address'])) && preg_match("~$search~i", $row['address'])) {
                        $description = trans('Address:') . ' ' . $row['address'];
                    } else if ((empty($properties) || isset($properties['post_name'])) && preg_match("~$search~i", $row['post_name'])) {
                        $description = trans('Name:') . ' ' . $row['post_name'];
                    } else if ((empty($properties) || isset($properties['post_address'])) && preg_match("~$search~i", $row['post_address'])) {
                        $description = trans('Address:') . ' ' . $row['post_address'];
                    } else if ((empty($properties) || isset($properties['location_name'])) && preg_match("~$search~i", $row['location_name'])) {
                        $description = trans('Name:') . ' ' . $row['location_name'];
                    } else if ((empty($properties) || isset($properties['location_address'])) && preg_match("~$search~i", $row['location_address'])) {
                        $description = trans('Address:') . ' ' . $row['location_address'];
                    } else if ((empty($properties) || isset($properties['email'])) && preg_match("~$search~i", $row['email'])) {
                        $description = trans('E-mail:') . ' ' . $row['email'];
                    }

                    $result[$row['id']] = compact('name', 'name_class', 'description', 'description_class', 'action');
                }
            }
            header('Content-type: application/json');
            if (!empty($result)) {
                echo json_encode(array_values($result));
            }
            $SESSION->close();
            $DB->Destroy();
            exit;
        }

        if (is_numeric($search)) { // maybe it's customer ID
            if ($customerid = $DB->GetOne('SELECT id FROM customerview WHERE id = '.$search)) {
                $target = '?m=customerinfo&id='.$customerid;
                break;
            }
        }

        // use customersearch module to find all customers
        $s['customername'] = $search;
        $s['address'] = $search;
        $s['zip'] = $search;
        $s['city'] = $search;
        $s['email'] = $search;

        $SESSION->save('customersearch', $s);
        $SESSION->save('cslk', 'OR');

        $SESSION->remove('cslp');
        $SESSION->remove('csln');
        $SESSION->remove('cslg');
        $SESSION->remove('csls');

        $target = '?m=customersearch&search=1';
        break;

    case 'customerext':
        if (isset($_GET['ajax'])) { // support for AutoSuggest
            $candidates = $DB->GetAll("SELECT c.id, cc.contact AS email, full_address AS address,
				post_name, post_full_address AS post_address, deleted, c.status,
			    " . $DB->Concat('UPPER(lastname)', "' '", 'c.name') . " AS customername
				FROM customerview c
				LEFT JOIN customercontacts cc ON cc.customerid = c.id AND (cc.type & ?) > 0
				WHERE LOWER(c.extid) ?LIKE? LOWER($sql_search)
				ORDER by deleted, customername, cc.contact, full_address
				LIMIT ?", array(CONTACT_EMAIL, intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15))));

            $result = array();
            if ($candidates) {
                foreach ($candidates as $idx => $row) {
                    $name = truncate_str($row['customername'], 50);

                    $name_classes = array();
                    if ($row['deleted']) {
                        $name_classes[] = 'blend';
                    }
                    $name_classes[] = 'lms-ui-suggestion-customer-status-' . $CSTATUSES[$row['status']]['alias'];
                    $name_class = implode(' ', $name_classes);

                    $description = '';
                    $description_class = '';
                    $action = '?m=customerinfo&id=' . $row['id'];

                    if (preg_match("~^$search\$~i", $row['id'])) {
                        $description = trans('Id:') . ' ' . $row['id'];
                    }

                    $result[$row['id']] = compact('name', 'name_class', 'description', 'description_class', 'action');
                }
            }
            header('Content-type: application/json');
            if (!empty($result)) {
                echo json_encode(array_values($result));
            }
            $SESSION->close();
            $DB->Destroy();
            exit;
        }

        if (($customerids = $DB->GetCol("SELECT id FROM customerview WHERE LOWER(extid) ?LIKE? LOWER($sql_search)"))
            && count($customerids) == 1) {
            $target = '?m=customerinfo&id=' . $customerids[0];
        }
        break;

    case 'phone':
        if (isset($_GET['ajax'])) { // support for AutoSuggest
            $where = array();
            if (empty($properties) || isset($properties['contact'])) {
                $where[] = "REPLACE(REPLACE(cc.contact, '-', ''), ' ', '') ?LIKE? $sql_search";
            }
            if (empty($properties) || isset($properties['account'])) {
                $where[] = "vn.phone ?LIKE? $sql_search" ;
            }

            $candidates = $DB->GetAll("SELECT c.id, "
                    . (empty($properties) || isset($properties['contact']) ? "cc.contact AS phone, " : '')
                    . (empty($properties) || isset($properties['account']) ? "vn.phone AS number, va.id AS voipaccountid, " : '')
                    . "full_address AS address,
				post_name, post_full_address AS post_address, deleted,
			    " . $DB->Concat('UPPER(lastname)', "' '", 'c.name') . " AS customername
				FROM customerview c "
                . (empty($properties) || isset($properties['contact']) ?
                    "LEFT JOIN customercontacts cc ON cc.customerid = c.id AND (cc.type & " . (CONTACT_LANDLINE | CONTACT_MOBILE | CONTACT_FAX) . " > 0)" : '')
                . (empty($properties) || isset($properties['account']) ?
                    "LEFT JOIN voipaccounts va ON va.ownerid = c.id
					LEFT JOIN voip_numbers vn ON vn.voip_account_id = va.id" : '')
                . " WHERE 1=1" . (empty($where) ? '' : ' AND (' . implode(' OR ', $where) . ')')
                . " ORDER by deleted, customername, "
                . (empty($properties) || isset($properties['contact']) ? "cc.contact, " : '')
                . (empty($properties) || isset($properties['account']) ? "vn.phone, " : '')
                . "full_address
				LIMIT ?", array(intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15))));

            $result = array();
            if ($candidates) {
                foreach ($candidates as $idx => $row) {
                    if (isset($row['number'])) {
                        $action = '?m=voipaccountinfo&id=' . $row['voipaccountid'];
                    } else {
                        $action = '?m=customerinfo&id=' . $row['id'];
                    }
                    $name = truncate_str($row['customername'], 50);
                    if (isset($row['number'])) {
                        $description = trans('VoIP number:') . ' ' . $row['number'];
                        $name_class = 'lms-ui-suggestion-phone';
                    } else {
                        $description = trans('Phone:') . ' ' . $row['phone'];
                        $name_class = 'lms-ui-suggestion-customer-status-connected';
                    }
                    $name_class .= $row['deleted'] ? ' blend' : '';

                    $description_class = '';
                    $result[$row['id']] = compact('name', 'name_class', 'description', 'description_class', 'action');
                }
            }
            header('Content-type: application/json');
            if (!empty($result)) {
                echo json_encode(array_values($result));
            }
            $SESSION->close();
            $DB->Destroy();
            exit;
        }

        // use customersearch module to find all customers
        $s['phone'] = $search;

        $SESSION->save('customersearch', $s);
        $SESSION->save('cslk', 'OR');

        $SESSION->remove('cslp');
        $SESSION->remove('csln');
        $SESSION->remove('cslg');
        $SESSION->remove('csls');

        $target = '?m=customersearch&search=1';
        break;


    case 'node':
        if (isset($_GET['ajax'])) { // support for AutoSuggest
        // Build different query for each database engine,
            // MySQL is slow here when vnodes view is used
            if (ConfigHelper::getConfig('database.type') == 'postgres') {
                $sql_query = 'SELECT n.id, n.name, INET_NTOA(ipaddr) as ip,
			        INET_NTOA(ipaddr_pub) AS ip_pub, mac, access, lastonline
				    FROM vnodes n
				    WHERE %where
    				ORDER BY n.name LIMIT ?';
            } else {
                $sql_query = 'SELECT n.id, n.name, INET_NTOA(ipaddr) as ip,
			        INET_NTOA(ipaddr_pub) AS ip_pub, mac, access, lastonline
				    FROM nodes n
				    JOIN (
                        SELECT nodeid, GROUP_CONCAT(mac SEPARATOR \',\') AS mac
                        FROM macs
                        GROUP BY nodeid
                    ) m ON (n.id = m.nodeid)
				    WHERE %where
    				ORDER BY n.name LIMIT ?';
            }

            $sql_where = '('
                . (empty($properties) || isset($properties['id']) ? (preg_match('/^[0-9]+$/', $search) ? "n.id = " . $search : '1=0') : '1=0')
                . (empty($properties) || isset($properties['name']) ? " OR LOWER(n.name) ?LIKE? LOWER($sql_search)" : '')
                . (empty($properties) || isset($properties['ip']) ? " OR INET_NTOA(ipaddr) ?LIKE? $sql_search" : '')
                . (empty($properties) || isset($properties['public_ip']) ? " OR INET_NTOA(ipaddr_pub) ?LIKE? $sql_search" : '')
                . (empty($properties) || isset($properties['mac']) ? " OR LOWER(mac) ?LIKE? LOWER(".macformat($search, true) . ")" : '') . "
				)
			    AND NOT EXISTS (
                    SELECT 1 FROM customerassignments a
                    JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
			        WHERE e.userid = lms_current_user() AND a.customerid = n.ownerid)";

            $candidates = $DB->GetAll(
                str_replace('%where', $sql_where, $sql_query),
                array(intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15)))
            );

            $result = array();
            if ($candidates) {
                $lastonline_limit = ConfigHelper::getConfig('phpui.lastonline_limit');
                foreach ($candidates as $idx => $row) {
                    $name = $row['name'];

                    $name_classes = array();
                    if (!$row['access']) {
                        $name_classes[] = 'blend';
                    }
                    if (!$row['lastonline']) {
                        $name_classes[] = 'lms-ui-suggestion-node-status-unknown';
                    } else if (time() - $row['lastonline'] <= $lastonline_limit) {
                            $name_classes[] = 'lms-ui-suggestion-node-status-online';
                    } else {
                        $name_classes[] = 'lms-ui-suggestion-node-status-offline';
                    }
                    $name_class = implode(' ', $name_classes);

                    $description = '';
                    $description_class = '';
                    $action = '?m=nodeinfo&id=' . $row['id'];

                    if ((empty($properties) || isset($properties['id'])) && preg_match("~^$search\$~i", $row['id'])) {
                        $description = trans('Id') . ': ' . $row['id'];
                    } else if ((empty($properties) || isset($properties['name'])) && preg_match("~$search~i", $row['name'])) {
                        $description = trans('Name') . ': ' . $row['name'];
                    } else if ((empty($properties) || isset($properties['ip'])) && preg_match("~$search~i", $row['ip'])) {
                        $description = trans('IP') . ': ' . $row['ip'];
                    } else if ((empty($properties) || isset($properties['public_ip'])) && preg_match("~$search~i", $row['ip_pub'])) {
                        $description = trans('IP') . ': ' . $row['ip_pub'];
                    } else if ((empty($properties) || isset($properties['mac'])) && preg_match("~" . macformat($search) . "~i", $row['mac'])) {
                        $macs = explode(',', $row['mac']);
                        foreach ($macs as $mac) {
                            if (preg_match("~" . macformat($search) . "~i", $mac)) {
                                $description = trans('MAC') . ': ' . $mac;
                            }
                        }
                        if (count($macs) > 1) {
                            $description .= ',...';
                        }
                    }

                    $result[$row['id']] = compact('name', 'name_class', 'description', 'description_class', 'action');
                }
            }
            header('Content-type: application/json');
            if (!empty($result)) {
                echo json_encode(array_values($result));
            }
            $SESSION->close();
            $DB->Destroy();
            exit;
        }

        if (is_numeric($search) && !strstr($search, '.')) { // maybe it's node ID
            if ($nodeid = $DB->GetOne('SELECT id FROM vnodes WHERE id = '.$search)) {
                $target = '?m=nodeinfo&id='.$nodeid;
                break;
            }
        }

        // use nodesearch module to find all matching nodes
        $s['name'] = $search;
        $s['mac'] = $search;
        $s['ipaddr'] = $search;

        $SESSION->save('nodesearch', $s);
        $SESSION->save('nslk', 'OR');

        $SESSION->remove('nslp');

        $target = '?m=nodesearch&search';
        break;

    case 'netnode':
        if (isset($_GET['ajax'])) { // support for AutoSuggest
            $candidates = $DB->GetAll("SELECT id, name FROM netnodes
                                WHERE ".(preg_match('/^[0-9]+$/', $search) ? 'id = '.intval($search).' OR ' : '')."
				LOWER(name) ?LIKE? LOWER($sql_search) 
                                ORDER by name
                                LIMIT ?", array(intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15))));

                $result = array();
            if ($candidates) {
                foreach ($candidates as $idx => $row) {
                                $name = truncate_str($row['name'], 50);
                                $name_class = 'lms-ui-suggestion-netnode';

                                $description = '';
                                $description_class = '';
                                $action = '?m=netnodeinfo&id=' . $row['id'];

                    if (preg_match("~^$search\$~i", $row['id'])) {
                            $description = trans('Id:') . ' ' . $row['id'];
                    }

                                $result[$row['id']] = compact('name', 'name_class', 'description', 'description_class', 'action');
                }
            }
                    header('Content-type: application/json');
            if (!empty($result)) {
                    echo json_encode(array_values($result));
            }
                    $SESSION->close();
                    $DB->Destroy();
                    exit;
        }

        if (is_numeric($search)) {
            if ($netnodeid = $DB->GetOne('SELECT id FROM netnodes WHERE id = ' . $search)) {
                $target = '?m=netnodeinfo&id=' . $netnodeid;
                break;
            }
        }

        break;

    case 'netdevice':
        if (isset($_GET['ajax'])) { // support for AutoSuggest
            $candidates = $DB->GetAll("SELECT id, name, serialnumber FROM netdevices
				WHERE "
                . (empty($properties) || isset($properties['id']) ? (preg_match('/^[0-9]+$/', $search) ? 'id = ' . $search : '1=0') : '1=0')
                . (empty($properties) || isset($properties['name']) ? " OR LOWER(name) ?LIKE? LOWER($sql_search)" : '')
                . (empty($properties) || isset($properties['serial']) ? " OR LOWER(serialnumber) ?LIKE? LOWER($sql_search)" : '')
                . "	ORDER by name
				LIMIT ?", array(intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15))));

                $result = array();
            if ($candidates) {
                foreach ($candidates as $idx => $row) {
                    $name = truncate_str($row['name'], 50);
                    $name_class = 'lms-ui-suggestion-netdevice';

                    $description = '';
                    $description_class = '';
                    $action = '?m=netdevinfo&id=' . $row['id'];

                    if ((empty($properties) || isset($properties['id'])) && preg_match("~^$search\$~i", $row['id'])) {
                            $description = trans('Id:') . ' ' . $row['id'];
                    } else if ((empty($properties) || isset($properties['name'])) && preg_match("~$search~i", $row['name'])) {
                        $description = trans('Name') . ': ' . $row['name'];
                    } else if ((empty($properties) || isset($properties['serial'])) && preg_match("~$search~i", $row['serialnumber'])) {
                        $description = trans('Serial number:') . ' ' . $row['serialnumber'];
                    }

                    $result[$row['id']] = compact('name', 'name_class', 'description', 'description_class', 'action');
                }
            }
                header('Content-type: application/json');
            if (!empty($result)) {
                    echo json_encode(array_values($result));
            }
                $SESSION->close();
                $DB->Destroy();
                exit;
        }

        if (is_numeric($search)) {
            if ($netdevid = $DB->GetOne('SELECT id FROM netdevices WHERE id = ' . $search)) {
                $target = '?m=netdevinfo&id=' . $netdevid;
                break;
            }
        }

        break;

    case 'ticket':
        if (isset($_GET['ajax'])) { // support for AutoSuggest
            $categories = $LMS->GetUserCategories(Auth::GetCurrentUser());
            foreach ($categories as $category) {
                $catids[] = $category['id'];
            }

            $userid = Auth::GetCurrentUser();
            $user_permission_checks = ConfigHelper::checkConfig('phpui.helpdesk_additional_user_permission_checks');
            $candidates = $DB->GetAll(
                "SELECT t.id, t.subject, t.requestor, t.state, c.name, c.lastname
				FROM rttickets t
				LEFT JOIN rtrights r ON r.queueid = t.queueid AND r.userid = ? AND r.rights & ? > 0
				LEFT JOIN rtticketcategories tc ON t.id = tc.ticketid
				LEFT JOIN customerview c on (t.customerid = c.id)
				WHERE (r.rights IS NOT NULL" . ($user_permission_checks ? ' OR t.owner = ' . $userid . ' OR t.verifierid = ' . $userid : '') . ")
					AND ".(is_array($catids) ? "tc.categoryid IN (".implode(',', $catids).")" : "tc.categoryid IS NULL")
                    . (empty($properties) || isset($properties['unresolvedonly']) ? ' AND t.state <> ' . RT_RESOLVED : '') . " AND ("
                    . (empty($properties) || isset($properties['id']) ? (preg_match('/^[0-9]+$/', $search) ? 't.id = ' . $search : '1=0') : '1=0')
                    . (empty($properties) || isset($properties['subject']) ? " OR LOWER(t.subject) ?LIKE? LOWER($sql_search)" : '')
                    . (empty($properties) || isset($properties['requestor']) ? " OR LOWER(t.requestor) ?LIKE? LOWER($sql_search)" : '')
                    . (empty($properties) || isset($properties['customername']) ? " OR LOWER(c.name) ?LIKE? LOWER($sql_search)
						OR LOWER(c.lastname) ?LIKE? LOWER($sql_search)" : '') . ")
					ORDER BY t.subject, t.id, c.lastname, c.name, t.requestor
					LIMIT ?",
                array(
                        $userid, RT_RIGHT_READ,
                        intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15)),
                    )
            );

            $result = array();
            if ($candidates) {
                foreach ($candidates as $idx => $row) {
                    $name = $row['subject'];

                    $name_classes = array();
                    switch ($row['state']) {
                        case RT_NEW:
                            $name_classes[] = 'lms-ui-suggestion-ticket-state-new';
                            break;
                        case RT_OPEN:
                            $name_classes[] = 'lms-ui-suggestion-ticket-state-open';
                            break;
                        case RT_RESOLVED:
                            $name_classes[] = 'lms-ui-suggestion-ticket-state-resolved';
                            break;
                        case RT_DEAD:
                            $name_classes[] = 'lms-ui-suggestion-ticket-state-dead';
                            break;
                        case RT_SCHEDULED:
                            $name_classes[] = 'lms-ui-suggestion-ticket-state-scheduled';
                            break;
                        case RT_WAITING:
                            $name_classes[] = 'lms-ui-suggestion-ticket-state-waiting';
                            break;
                    }
                    $name_class = implode(' ', $name_classes);

                    $description = '';
                    $description_class = '';
                    $action = '?m=rtticketview&id=' . $row['id'];

                    if ((empty($properties) || isset($properties['id'])) && preg_match("~^$search\$~i", $row['id'])) {
                        $description = trans('Id') . ': ' . $row['id'];
                    } else if ((empty($properties) || isset($properties['subject'])) && preg_match("~$search~i", $row['subject'])) {
                        $description = trans('Subject:') . ' ' . $row['subject'];
                    } else if ((empty($properties) || isset($properties['requestor'])) && preg_match("~$search~i", $row['requestor'])) {
                        $description = trans('First/last name') . ': '
                        . preg_replace('/ <.*/', '', $row['requestor']);
                    } else if ((empty($properties) || isset($properties['customername'])) && preg_match("~^$search~i", $row['name'])) {
                        $description = trans('First/last name') . ': ' . $row['name'];
                    } else if ((empty($properties) || isset($properties['customername'])) && preg_match("~^$search~i", $row['lastname'])) {
                        $description = trans('First/last name') . ': ' . $row['lastname'];
                    }

                    $result[$row['id']] = compact('name', 'name_class', 'description', 'description_class', 'action');
                }
            }
            header('Content-type: application/json');
            if (!empty($result)) {
                echo json_encode(array_values($result));
            }
            $SESSION->close();
            $DB->Destroy();
            exit;
        }

        if (is_numeric($search) && intval($search)>0) {
            $target = '?m=rtticketview&id='.intval($search);
        } else {
            $SESSION->save('rtsearch', array('name' => $search,
                    'subject' => $search,
                    'operator' => 'OR'));

            $target = '?m=rtsearch&s=1';
        }
        break;
    case 'wireless':
        if (isset($_GET['ajax'])) { // support for AutoSuggest
            $candidates = $DB->GetAll("SELECT id, name, type, netdev FROM netradiosectors
                                WHERE " . (preg_match('/^[0-9]+$/', $search) ? 'id = ' . intval($search) . ' OR ' : '') . "
				LOWER(name) ?LIKE? LOWER($sql_search)
                                ORDER by name
                                LIMIT ?", array(intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15))));

            $result = array();
            if ($candidates) {
                foreach ($candidates as $idx => $row) {
                    $name = truncate_str($row['name'], 50);
                    $name_classes[] = 'lms-ui-suggestion-wireless-' . $NETWORK_INTERFACE_TYPES[$row['type']]['alias'];
                    $name_class = implode(' ', $name_classes);

                    $description = '';
                    $description_class = '';
                    $action = '?m=netdevinfo&id=' . $row['netdev'];

                    if (preg_match("~^$search\$~i", $row['id'])) {
                        $description = trans('Id:') . ' ' . $row['id'];
                    }

                    $result[$row['id']] = compact('name', 'name_class', 'description', 'description_class', 'action');
                }
            }
            header('Content-type: application/json');
            if (!empty($result)) {
                echo json_encode(array_values($result));
            }
            $SESSION->close();
            $DB->Destroy();
            exit;
        }

        if (is_numeric($search)) {
            if ($netdevid = $DB->GetOne('SELECT netdev FROM netradiosectors WHERE id = ' . $search)) {
                $target = '?m=netdevinfo&id=' . $netdevid;
                break;
            }
        }
        break;
    case 'network':
        if (isset($_GET['ajax'])) { // support for AutoSuggest
            $candidates = $DB->GetAll("SELECT id, name, address FROM networks
                                WHERE " . (preg_match('/^[0-9]+$/', $search) ? 'id = ' . intval($search) . ' OR ' : '') . "
				LOWER(name) ?LIKE? LOWER($sql_search)
                                ORDER by name
                                LIMIT ?", array(intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15))));

            $result = array();
            if ($candidates) {
                foreach ($candidates as $idx => $row) {
                    $name = truncate_str($row['name'], 50);
                    $name_class = 'lms-ui-suggestion-network';

                    $description = '';
                    $description_class = '';
                    $action = '?m=netinfo&id=' . $row['network'];

                    if (preg_match("~^$search\$~i", $row['id'])) {
                        $description = trans('Id:') . ' ' . $row['id'];
                    }

                    $result[$row['id']] = compact('name', 'name_class', 'description', 'description_class', 'action');
                }
            }
            header('Content-type: application/json');
            if (!empty($result)) {
                echo json_encode(array_values($result));
            }
            $SESSION->close();
            $DB->Destroy();
            exit;
        }

        if (is_numeric($search)) {
            if ($networkid = $DB->GetOne('SELECT id FROM networks WHERE id = ' . $search)) {
                $target = '?m=netinfo&id=' . $networkid;
                break;
            }
        }
        break;
    case 'account':
        $ac = explode('@', $search);

        if (isset($_GET['ajax'])) { // support for AutoSuggest
            $username = $DB->Escape('%'.$ac[0].'%');
            $domain   = $DB->Escape('%'.$ac[1].'%');

            $candidates = $DB->GetAll("(SELECT p.id, p.login, d.name AS domain, 0 AS type
					FROM passwd p
					JOIN domains d ON (p.domainid = d.id)
					WHERE p.login ?LIKE? LOWER($username)
					".($domain ? "AND d.name ?LIKE? LOWER($domain)" : '').")
					UNION 
					(SELECT a.id, a.login, d.name AS domain, 1 AS type 
					FROM aliases a
					JOIN domains d ON (a.domainid = d.id)
					WHERE a.login ?LIKE? LOWER($username)
					".($domain ? "AND d.name ?LIKE? LOWER($domain)" : '').")
					ORDER BY login, domain
					LIMIT ?", array(intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15))));

            $result = array();

            if ($candidates) {
                foreach ($candidates as $idx => $row) {
                    $name = $row['login'] . '@' . $row['domain'];
                    $name_class = '';
                    $description = '';
                    $description_class = '';
                    if ($row['type']) {
                        $action = '?m=aliasinfo&id=' . $row['id'];
                    } else {
                        $action = '?m=accountinfo&id=' . $row['id'];
                    }

                    $result[$row['id']] = compact('name', 'name_class', 'description', 'description_class', 'action');
                }
            }
            header('Content-type: application/json');
            if (!empty($result)) {
                echo json_encode(array_values($result));
            }
            $SESSION->close();
            $DB->Destroy();
            exit;
        }

        $search = array();
        $search['login'] = $ac[0];
        if (!empty($ac[1])) {
            $search['domain'] = $ac[1];
        }

        $SESSION->save('accountsearch', $search);
        $target = '?m=accountsearch&s=1';
        break;

    case 'document':
        if (isset($_GET['ajax'])) {
            $candidates = $DB->GetAll("SELECT d.id, d.type, d.fullnumber,
					d.customerid AS cid, d.name AS customername
				FROM documents d
				JOIN customerview c on d.customerid = c.id
				WHERE (LOWER(d.fullnumber) ?LIKE? LOWER($sql_search)
					OR 1 = 0)
					ORDER BY d.fullnumber
					LIMIT ?", array(intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15))));

            $result = array();

            if ($candidates) {
                foreach ($candidates as $idx => $row) {
    /*
                    switch ($row['type']) {
                    case DOC_INVOICE:
                        $action = '?m=invoice&id=' . $row['id'];
                        break;
                    case DOC_RECEIPT:
                        $action = '?m=receipt&id=' . $row['id'];
                        break;
                    case DOC_CNOTE:
                        $action = '?m=note&id=' . $row['id'];
                        break;
                    default:
                        $action = '?m=documentview&id=' . $row['id'];
                    }
    */
                    $name = $row['fullnumber'];
                    $name_class = '';
                    $description = truncate_str($row['customername'], 35);
                    //$description = trans('Document id:') . ' ' . $row['id'];
                    $description_class = '';
                    $action = '?m=customerinfo&id=' . $row['cid'];

                    $result[$row['id']] = compact('name', 'name_class', 'description', 'description_class', 'action');
                }
            }
            header('Content-type: application/json');
            if (!empty($result)) {
                echo json_encode(array_values($result));
            }
            $SESSION->close();
            $DB->Destroy();
            exit;
        }

        $docs = $DB->GetAll("SELECT d.id, d.type, d.customerid AS cid, d.name AS customername
			FROM documents d
			JOIN customerview c ON c.id = d.customerid
			WHERE LOWER(fullnumber) ?LIKE? LOWER($sql_search)");
        if (count($docs) == 1) {
            $cid = $docs[0]['cid'];
/*
            $docid = $docs[0]['id'];
            $type = $docs[0]['type'];
            switch ($type) {
                case DOC_INVOICE:
                    $target = '?m=invoice&id=' . $docid;
                    break;
                case DOC_RECEIPT:
                    $target = '?m=receipt&id=' . $docid;
                    break;
                case DOC_CNOTE:
                    $target = '?m=note&id=' . $docid;
                    break;
                default:
                    $target = '?m=documentview&id=' . $docid;
            }
*/
            $target = '?m=customerinfo&id=' . $cid;
        }
        break;
}

$quicksearch = $LMS->executeHook(
    'quicksearch_after_submit',
    array(
        'mode' => $mode,
        'search' => $search,
        'sql_search' => $sql_search,
        'session' => $SESSION,
        'target' => '',
    )
);
if (!empty($quicksearch['target'])) {
    $target = $quicksearch['target'];
}

$SESSION->redirect(!empty($target) ? $target : '?'.$SESSION->get('backto'));

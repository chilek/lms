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

function macformat($mac, $escape = false)
{
    $DB = LMSDB::getInstance();

    $mac = preg_replace('/[\-:\.]/', '', $mac);

    if ($escape) {
        return $DB->Escape('%' . $mac . '%');
    } else {
        return $mac;
    }
}

$mode = '';

if (!empty($_POST['qs'])) {
    foreach ($_POST['qs'] as $key => $value) {
        if (!empty($value)) {
            $mode = $key;
            $search = $value;
        }
    }
    $search = isset($search) ? urldecode(trim($search)) : '';
} else {
    $search = urldecode(trim($_GET['what'] ?? ''));
    $mode = $_GET['mode'] ?? '';
}
$sql_search = $DB->Escape("%$search%");

if (isset($_POST['properties']) && is_array($_POST['properties'])) {
    $properties = array_flip($_POST['properties']);
} elseif (isset($_GET['properties']) && is_array($_GET['properties'])) {
    $properties = array_flip($_GET['properties']);
} elseif (isset($qs_properties[$mode])) {
    $properties = $qs_properties[$mode];
} else {
    $properties = array();
}

$resourceIdOnly = preg_match('/^#[0-9]+$/', $search) > 0;
if ($resourceIdOnly) {
    $properties = array('id' => 'id');
    $search = str_replace('#', '', $search);
}

$resourceKeyOnly = preg_match('/^@.+/', $search) > 0;
if ($resourceKeyOnly) {
    $search = str_replace('@', '', $search);
    $sql_search = $DB->Escape("$search%");
}

switch ($mode) {
    case 'customer':
        if (empty($search) || ($module != 'customerselect' && !ConfigHelper::checkPrivilege('customer_management') && !ConfigHelper::checkPrivilege('read_only'))) {
            die;
        }

        if (isset($_GET['ajax'])) { // support for AutoSuggest
            if ($resourceKeyOnly) {
                $properties = array('altname' => 'altname');
            }

            $quicksearch_limit = intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15));

            $candidates = $DB->GetAll(
                "SELECT
                    c.id,
                    cc.contact AS email,
                    cc2.contact AS bankaccount,
                    full_address AS address,
                    post_name,
                    CASE WHEN full_address = post_full_address THEN NULL ELSE post_full_address END AS post_address,
                    deleted,
                    altname,
                    " . $DB->Concat('UPPER(lastname)', "' '", 'c.name') . " AS customername,
                    va.name AS location_name,
                    va.address AS location_address,
                    c.status,
                    c.ten,
                    c.ssn,
                    c.info,
                    c.notes,
                    c.documentmemo
                FROM customerview c
                LEFT JOIN customer_addresses ca ON ca.customer_id = c.id AND ca.type IN ?
                LEFT JOIN vaddresses va ON va.id = ca.address_id
                LEFT JOIN customercontacts cc ON cc.customerid = c.id AND (cc.type & ?) > 0
                LEFT JOIN customercontacts cc2 ON cc2.customerid = c.id AND (cc2.type & ?) > 0
                WHERE " . (empty($properties) || isset($properties['id']) ? (preg_match('/^[0-9]+$/', $search) ? 'c.id = ' . $search : '1=0') : '1=0')
                    . (empty($properties) || isset($properties['name']) ? " OR LOWER(" . $DB->Concat('lastname', "' '", 'c.name') . ") ?LIKE? LOWER($sql_search) OR LOWER(" . $DB->Concat('c.name', "' '", 'lastname') . ") ?LIKE? LOWER($sql_search)" : '')
                    . (empty($properties) || isset($properties['altname']) ? " OR LOWER(c.altname) ?LIKE? LOWER($sql_search)" : '')
                    . (empty($properties) || isset($properties['address']) ? " OR LOWER(full_address) ?LIKE? LOWER($sql_search)" : '')
                    . (empty($properties) || isset($properties['post_name']) ? " OR LOWER(post_name) ?LIKE? LOWER($sql_search)" : '')
                    . (empty($properties) || isset($properties['post_address']) ? " OR LOWER(post_full_address) ?LIKE? LOWER($sql_search)" : '')
                    . (empty($properties) || isset($properties['location_name']) ? " OR LOWER(va.name) ?LIKE? LOWER($sql_search)" : '')
                    . (empty($properties) || isset($properties['location_address']) ? " OR LOWER(va.address) ?LIKE? LOWER($sql_search)" : '')
                    . (empty($properties) || isset($properties['email']) ? " OR LOWER(cc.contact) ?LIKE? LOWER($sql_search)" : '')
                    . (empty($properties) || isset($properties['bankaccount']) ? " OR LOWER(REPLACE(cc2.contact, ' ', '')) ?LIKE? LOWER(REPLACE($sql_search, ' ', ''))" : '')
                    . (empty($properties) || isset($properties['ten']) ? " OR REPLACE(REPLACE(c.ten, '-', ''), ' ', '') ?LIKE? REPLACE(REPLACE($sql_search, '-', ''), ' ', '')" : '')
                    . (empty($properties) || isset($properties['ssn']) ? " OR REPLACE(REPLACE(c.ssn, '-', ''), ' ', '') ?LIKE? REPLACE(REPLACE($sql_search, '-', ''), ' ', '')" : '')
                    . (empty($properties) || isset($properties['additional-info']) ? " OR LOWER(c.info) ?LIKE? LOWER($sql_search)" : '')
                    . (empty($properties) || isset($properties['notes'])
                        ? " OR LOWER(c.notes) ?LIKE? LOWER($sql_search)"
                            . " OR EXISTS (SELECT 1 FROM customernotes cn WHERE cn.customerid = c.id AND LOWER(cn.message) ?LIKE? LOWER($sql_search))"
                        : ''
                    )
                    . (empty($properties) || isset($properties['documentmemo']) ? " OR LOWER(c.documentmemo) ?LIKE? LOWER($sql_search)" : '') . "
                ORDER by deleted, customername, cc.contact, full_address
                LIMIT ?",
                array(
                    array(
                        DEFAULT_LOCATION_ADDRESS,
                        LOCATION_ADDRESS,
                    ),
                    CONTACT_EMAIL,
                    CONTACT_BANKACCOUNT,
                    $quicksearch_limit * 3,
                )
            );

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
                    $icon = 'fa-fw lms-ui-icon-customer-status-' . $CSTATUSES[$row['status']]['alias'];

                    $name = truncate_str('(#' . $row['id'] . ') ' . $row['customername'], 50);

                    $name_classes = array();
                    if ($row['deleted']) {
                        $name_classes[] = 'blend';
                    }
                    $name_class = implode(' ', $name_classes);

                    $description = '';
                    $description_class = '';
                    $action = '?m=customerinfo&id=' . $row['id'];

                    if ((empty($properties) || isset($properties['name'])) && $customer_count[$row['customername']]) {
                        $description = isset($row['address']) ? htmlspecialchars($row['address']) : '';
                        if (!empty($row['post_address'])) {
                            $description .= '<BR>' . htmlspecialchars($row['post_address']);
                            if (!empty($row['post_name'])) {
                                $description .= '<BR>' . htmlspecialchars($row['post_name']);
                            }
                        }
                    } else if ((empty($properties) || isset($properties['id'])) && preg_match("~^$search\$~i", $row['id'])) {
                        $description = trans('Address:') . ' ' . htmlspecialchars($row['address']);
                        //$description = trans('Id:') . ' ' . $row['id'];
                    } else if ((empty($properties) || isset($properties['name'])) && preg_match("~$search~i", $row['customername'])) {
                        $description = '';
                    } else if ((empty($properties) || isset($properties['altname'])) && preg_match("~$search~i", $row['altname'])) {
                        $description = trans('Alternative name:') . ' ' . htmlspecialchars($row['altname']);
                    } else if ((empty($properties) || isset($properties['address'])) && preg_match("~$search~i", $row['address'])) {
                        $description = trans('Address:') . ' ' . htmlspecialchars($row['address']);
                    } else if ((empty($properties) || isset($properties['post_name'])) && preg_match("~$search~i", $row['post_name'])) {
                        $description = trans('Name:') . ' ' . htmlspecialchars($row['post_name']);
                    } else if ((empty($properties) || isset($properties['post_address'])) && preg_match("~$search~i", $row['post_address'])) {
                        $description = trans('Address:') . ' ' . htmlspecialchars($row['post_address']);
                    } else if ((empty($properties) || isset($properties['location_name'])) && preg_match("~$search~i", $row['location_name'])) {
                        $description = trans('Name:') . ' ' . htmlspecialchars($row['location_name']);
                    } else if ((empty($properties) || isset($properties['location_address'])) && preg_match("~$search~i", $row['location_address'])) {
                        $description = trans('Address:') . ' ' . htmlspecialchars($row['location_address']);
                    } else if ((empty($properties) || isset($properties['email'])) && preg_match("~$search~i", $row['email'])) {
                        $description = trans('E-mail:') . ' ' . $row['email'];
                    } else if ((empty($properties) || isset($properties['bankaccount']))
                        && preg_match('~' . preg_replace('/[\- ]/', '', $search) . '~i', preg_replace('/[\- ]/', '', $row['bankaccount']))) {
                        $description = trans('Alternative bank account:') . ' ' . format_bankaccount($row['bankaccount']);
                    } else if ((empty($properties) || isset($properties['ten']))
                        && preg_match('~' . preg_replace('/[\- ]/', '', $search) . '~i', preg_replace('/[\- ]/', '', $row['ten']))) {
                        $description = trans('TEN:') . ' ' . $row['ten'];
                    } else if ((empty($properties) || isset($properties['ssn']))
                        && preg_match('~' . preg_replace('/[\- ]/', '', $search) . '~i', preg_replace('/[\- ]/', '', $row['ssn']))) {
                        $description = trans('SSN:') . ' ' . $row['ssn'];
                    } else if ((empty($properties) || isset($properties['additional-info'])) && preg_match("~$search~i", $row['info'])) {
                        $description = trans('Additional information:') . ' ' . $row['info'];
                    } else if ((empty($properties) || isset($properties['notes'])) && preg_match("~$search~i", $row['notes'])) {
                        $description = trans('Notes:') . ' ' . $row['notes'];
                    } else if ((empty($properties) || isset($properties['documentmemo'])) && preg_match("~$search~i", $row['documentmemo'])) {
                        $description = trans('Document memo:') . ' ' . $row['documentmemo'];
                    }

                    $result[$row['id']] = array_merge(
                        compact('name', 'icon', 'name_class', 'description', 'description_class', 'action'),
                        array('id' => $row['id'])
                    );
                }
                $result = array_slice($result, 0, $quicksearch_limit, true);
            }
            $hook_data = array(
                'search' => $search,
                'sql_search' => $sql_search,
                'properties' => $properties,
                'session' => $SESSION,
                'result' => $result
            );
            $hook_data = $LMS->executeHook('quicksearch_ajax_customer', $hook_data);
            $result = $hook_data['result'];
            header('Content-type: application/json');
            echo json_encode(array_values($result));
            $SESSION->close();
            exit;
        }

        if (is_numeric($search)) { // maybe it's customer ID
            if ($customerid = $DB->GetOne('SELECT id FROM customerview WHERE id = '.$search)) {
                $target = '?m=customerinfo&id='.$customerid;
                break;
            }
        }

        // use customersearch module to find all customers
        $s = array();
        if (empty($properties) || isset($properties['name'])) {
            $s['customername'] = $search;
        }
        if (empty($properties) || isset($properties['altname'])) {
            $s['altname'] = $search;
        }
        if (empty($properties) || isset($properties['address'])) {
            $s['full_address'] = $search;
        }
        if (empty($properties) || isset($properties['post_name'])) {
            $s['post_name'] = $search;
        }
        if (empty($properties) || isset($properties['post_address'])) {
            $s['post_full_address'] = $search;
        }
        if (empty($properties) || isset($properties['location_name'])) {
            $s['location_name'] = $search;
        }
        if (empty($properties) || isset($properties['location_address'])) {
            $s['location_full_address'] = $search;
        }
        if (empty($properties) || isset($properties['email'])) {
            $s['email'] = $search;
        }
        if (empty($properties) || isset($properties['ten'])) {
            $s['ten'] = $search;
        }
        if (empty($properties) || isset($properties['ssn'])) {
            $s['ssn'] = $search;
        }
        if (empty($properties) || isset($properties['additional-info'])) {
            $s['info'] = $search;
        }
        if (empty($properties) || isset($properties['notes'])) {
            $s['notes'] = $search;
        }
        if (empty($properties) || isset($properties['documentmemo'])) {
            $s['documentmemo'] = $search;
        }

        $SESSION->save('customersearch', $s);
        $SESSION->save('cslk', 'OR');

        $SESSION->remove('cslp');
        $SESSION->remove('csln');
        $SESSION->remove('cslg');
        $SESSION->remove('csls');
        $SESSION->remove('cslng');

        $target = '?m=customersearch&search=1';
        break;

    case 'customerext':
        if (empty($search) || (!ConfigHelper::checkPrivilege('customer_management') && !ConfigHelper::checkPrivilege('read_only'))) {
            die;
        }

        if (isset($_GET['ajax'])) { // support for AutoSuggest
            $candidates = $DB->GetAll(
                "SELECT
                    c.id,
                    cc.contact AS email,
                    full_address AS address,
                    post_name,
                    post_full_address AS post_address,
                    deleted,
                    c.status,
                    altname,
                    " . $DB->Concat('UPPER(lastname)', "' '", 'c.name') . " AS customername,
                    extids.extid,
                    sp.name AS serviceprovidername
                FROM customerview c
                LEFT JOIN customercontacts cc ON cc.customerid = c.id AND (cc.type & ?) > 0
                LEFT JOIN customerextids extids ON extids.customerid = c.id
                LEFT JOIN serviceproviders sp ON sp.id = extids.serviceproviderid
                WHERE LOWER(extids.extid) ?LIKE? LOWER($sql_search)
                ORDER by deleted, customername, cc.contact, full_address
                LIMIT ?",
                array(
                    CONTACT_EMAIL,
                    intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15)),
                )
            );

            $result = array();
            if ($candidates) {
                foreach ($candidates as $idx => $row) {
                    if (!empty($properties)
                        && (!isset($properties['default']) && empty($row['serviceprovidername'])
                            || !empty($row['serviceprovidername']) && !isset($properties[$row['serviceprovidername']]))) {
                        continue;
                    }

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

                    if ((empty($properties) || isset($properties['default'])) && empty($row['serviceprovidername'])) {
                        $description = trans('default:') . ' ' . $row['extid'];
                    } elseif (!empty($row['serviceprovidername']) && (empty($properties) || isset($properties[$row['serviceprovidername']]))) {
                        foreach ($serviceproviders as $serviceprovider) {
                            if (empty($properties) || isset($properties[$serviceprovider['name']])) {
                                $description = $serviceprovider['name'] . ': ' . $row['extid'];
                                break;
                            }
                        }
                    }

                    $result[$row['id']] = compact('name', 'name_class', 'description', 'description_class', 'action');
                }
            }
            header('Content-type: application/json');
            echo json_encode(array_values($result));
            $SESSION->close();
            exit;
        }

        if (($customerids = $DB->GetCol("SELECT id FROM customerview WHERE LOWER(extid) ?LIKE? LOWER($sql_search)"))
            && count($customerids) == 1) {
            $target = '?m=customerinfo&id=' . $customerids[0];
        }
        break;

    case 'phone':
        if (empty($search) || (!ConfigHelper::checkPrivilege('customer_management')
                && !ConfigHelper::checkPrivilege('voip_account_management') && !ConfigHelper::checkPrivilege('read_only'))) {
            die;
        }

        if (isset($_GET['ajax'])) { // support for AutoSuggest
            $phone_number = preg_replace('/[^0-9]/', '', $search);

            $sqlContact = "SELECT c.id,
                cc.contact AS phone,
                NULL AS number,
                NULL AS voipaccountid,
                full_address AS address,
                cc.contact AS contact,
                post_name, post_full_address AS post_address, deleted, altname, "
                . $DB->Concat('UPPER(lastname)', "' '", 'c.name') . " AS customername
                FROM customerview c
                LEFT JOIN customercontacts cc ON cc.customerid = c.id AND (cc.type & " . (CONTACT_LANDLINE | CONTACT_MOBILE | CONTACT_FAX) . " > 0)
                WHERE REPLACE(REPLACE(cc.contact, '-', ''), ' ', '') ?LIKE? '%$phone_number%'";

            $sqlAccount = "SELECT c.id,
                NULL AS phone,
                vn.phone AS number, va.id AS voipaccountid,
                NULL AS address,
                NULL AS contact,
                NULL AS post_name, NULL AS post_address, deleted, altname, "
                . $DB->Concat('UPPER(lastname)', "' '", 'c.name') . " AS customername
                FROM customerview c
                LEFT JOIN voipaccounts va ON va.ownerid = c.id
                LEFT JOIN voip_numbers vn ON vn.voip_account_id = va.id
                WHERE vn.phone ?LIKE? '%$phone_number%'";

            $candidatesSql = null;
            if (empty($properties) || isset($properties['contact'], $properties['account'])) {
                $candidatesSql = $sqlContact . " UNION " . $sqlAccount;
            } elseif (isset($properties['contact'])) {
                $candidatesSql = $sqlContact;
            } elseif (isset($properties['account'])) {
                $candidatesSql = $sqlAccount;
            }

            $candidates = array();
            if (!empty($candidatesSql)) {
                $candidates = $DB->GetAll(
                    $candidatesSql
                    . " ORDER BY deleted, customername, contact, phone, number, address"
                    . " LIMIT ?",
                    array(
                        intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15))
                    )
                );
            }

            $result = array();
            if (!empty($candidates)) {
                foreach ($candidates as $idx => $row) {
                    if (isset($row['number'])) {
                        $action = '?m=voipaccountinfo&id=' . $row['voipaccountid'];
                        $number = $row['number'];
                        $voipaccountid = $row['voipaccountid'];
                    } else {
                        $action = '?m=customerinfo&id=' . $row['id'];
                        $number = null;
                        $voipaccountid = null;
                    }

                    $name = truncate_str('(#' . $row['id'] . ') ' . $row['customername'], 50);

                    if (isset($row['number'])) {
                        $description = trans('VoIP number:') . ' ' . htmlspecialchars($row['number']);
                        $name_class = '';
                        $icon = 'fa-fw lms-ui-icon-phone';
                    } elseif (isset($row['phone'])) {
                        $description = trans('Phone:') . ' ' . htmlspecialchars($row['phone']);
                        $name_class = '';
                        $icon = 'fa-fw lms-ui-icon-customer-status-connected';
                    } else {
                        $description = trans('Address:') . ' ' . htmlspecialchars($row['address']);
                        $name_class = '';
                        $icon = 'fa-fw lms-ui-icon-location';
                    }

                    $name_class .= $row['deleted'] ? ' blend' : '';

                    $description_class = '';
                    $result[$idx] = compact('name', 'name_class', 'icon', 'description', 'description_class', 'action', 'number', 'voipaccountid');
                }
            }

            $hook_data = array(
                'search' => $search,
                'sql_search' => $sql_search,
                'properties' => $properties,
                'session' => $SESSION,
                'result' => $result
            );
            $hook_data = $LMS->executeHook('quicksearch_ajax_customerext', $hook_data);
            $result = $hook_data['result'];
            header('Content-type: application/json');
            if (!empty($result)) {
                echo json_encode(array_values($result));
            }
            $SESSION->close();
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
        if (empty($search) || (!ConfigHelper::checkPrivilege('node_management') && !ConfigHelper::checkPrivilege('read_only'))) {
            die;
        }

        if (isset($_GET['ajax'])) { // support for AutoSuggest
        // Build different query for each database engine,
            // MySQL is slow here when vnodes view is used
            if (ConfigHelper::getConfig('database.type') == 'postgres') {
                $sql_query = 'SELECT n.id, n.name, n.login, INET_NTOA(ipaddr) as ip,
                    INET_NTOA(ipaddr_pub) AS ip_pub, mac, location, access, lastonline
                    FROM vnodes n
                    WHERE %where
                    ORDER BY n.ipaddr LIMIT ?';
            } else {
                $sql_query = 'SELECT n.id, n.name, n.login, INET_NTOA(ipaddr) as ip,
                    INET_NTOA(ipaddr_pub) AS ip_pub, mac, va.location, access, lastonline
                    FROM nodes n
                    JOIN (
                        SELECT nodeid, GROUP_CONCAT(mac SEPARATOR \',\') AS mac
                        FROM macs
                        GROUP BY nodeid
                    ) m ON (n.id = m.nodeid)
                    LEFT JOIN vaddresses va ON va.id = n.address_id
                    WHERE %where
                    ORDER BY n.ipaddr LIMIT ?';
            }

            $sql_where = '('
                . (empty($properties) || isset($properties['id']) ? (preg_match('/^[0-9]+$/', $search) ? "n.id = " . $search : '1=0') : '1=0')
                . (empty($properties) || isset($properties['name']) ? " OR LOWER(n.name) ?LIKE? LOWER($sql_search)" : '')
                . (empty($properties) || isset($properties['login']) ? " OR LOWER(n.login) ?LIKE? LOWER($sql_search)" : '')
                . (empty($properties) || isset($properties['ip']) ? " OR INET_NTOA(ipaddr) ?LIKE? $sql_search" : '')
                . (empty($properties) || isset($properties['public_ip']) ? " OR INET_NTOA(ipaddr_pub) ?LIKE? $sql_search" : '')
                . (empty($properties) || isset($properties['mac']) ? " OR LOWER(REPLACE(mac, ':', '')) ?LIKE? LOWER(" . macformat($search, true) . ')' : '')
                . (empty($properties) || isset($properties['location_address']) ? " OR LOWER(location) ?LIKE? LOWER($sql_search)" : '') . "
				)
			    AND NOT EXISTS (
                    SELECT 1 FROM vcustomerassignments a
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
                        $icon = 'fa-fw lms-ui-icon-nodeunk';
                    } else if (time() - $row['lastonline'] <= $lastonline_limit) {
                        $icon = 'fa-fw lms-ui-icon-nodeon';
                    } else {
                        $icon = 'fa-fw lms-ui-icon-nodeoff';
                    }
                    $name_class = implode(' ', $name_classes);

                    $description = '';
                    $description_class = '';
                    $action = '?m=nodeinfo&id=' . $row['id'];

                    if ((empty($properties) || isset($properties['id'])) && preg_match("~^$search\$~i", $row['id'])) {
                        $description = trans('Id') . ': ' . $row['id'];
                    } else if ((empty($properties) || isset($properties['name'])) && preg_match("~$search~i", $row['name'])) {
                        $description = trans('Name') . ': ' . $row['name'];
                    } else if ((empty($properties) || isset($properties['login'])) && isset($row['login']) && preg_match("~$search~i", $row['login'])) {
                        $description = trans('<!node>Login') . ': ' . $row['login'];
                    } else if ((empty($properties) || isset($properties['ip'])) && preg_match("~$search~i", $row['ip'])) {
                        $description = trans('IP') . ': ' . $row['ip'];
                    } else if ((empty($properties) || isset($properties['public_ip'])) && preg_match("~$search~i", $row['ip_pub'])) {
                        $description = trans('IP') . ': ' . $row['ip_pub'];
                    } else if ((empty($properties) || isset($properties['location_address'])) && isset($row['location']) && preg_match("~$search~i", $row['location'])) {
                        $description = trans('Address') . ': ' . htmlspecialchars($row['location']);
                    } else if ((empty($properties) || isset($properties['mac'])) && preg_match('/' . macformat($search) . '/i', str_replace(':', '', $row['mac']))) {
                        $macs = explode(',', $row['mac']);
                        foreach ($macs as $mac) {
                            if (preg_match('/' . macformat($search) . '/i', str_replace(':', '', $mac))) {
                                $description = trans('MAC') . ': ' . $mac;
                            }
                        }
                        if (count($macs) > 1) {
                            $description .= ',...';
                        }
                    }

                    $result[$row['id']] = compact('name', 'name_class', 'icon', 'description', 'description_class', 'action');
                }
            }
            $hook_data = array(
                'search' => $search,
                'sql_search' => $sql_search,
                'properties' => $properties,
                'session' => $SESSION,
                'result' => $result
            );
            $hook_data = $LMS->executeHook('quicksearch_ajax_node', $hook_data);
            $result = $hook_data['result'];
            header('Content-type: application/json');
            echo json_encode(array_values($result));
            $SESSION->close();
            exit;
        }

        if (is_numeric($search) && !strstr($search, '.')) { // maybe it's node ID
            if ($nodeid = $DB->GetOne('SELECT id FROM vnodes WHERE id = '.$search)) {
                $target = '?m=nodeinfo&id='.$nodeid;
                break;
            }
        }

        // use nodesearch module to find all matching nodes
        $s = array();
        if (empty($properties) || isset($properties['name'])) {
            $s['name'] = $search;
        }
        if (empty($properties) || isset($properties['mac'])) {
            $s['mac'] = $search;
        }
        if (empty($properties) || isset($properties['ip'])) {
            $s['ip'] = $search;
        }
        if (empty($properties) || isset($properties['public_ip'])) {
            $s['public_ip'] = $search;
        }
        if (empty($properties) || isset($properties['location_address'])) {
            $s['location'] = $search;
        }
        $SESSION->save('nodesearch', $s);
        $SESSION->save('nslk', 'OR');

        $SESSION->remove('nslp');

        $target = '?m=nodesearch&search';
        break;

    case 'netnode':
        if (empty($search) || (!ConfigHelper::checkPrivilege('network_management') && !ConfigHelper::checkPrivilege('read_only'))) {
            die;
        }

        if (isset($_GET['ajax'])) { // support for AutoSuggest
            $candidates = $DB->GetAll("SELECT id, name, info FROM netnodes
                WHERE " . (empty($properties) || isset($properties['id']) ? (preg_match('/^[0-9]+$/', $search) ? 'id = ' . intval($search) : '1 = 0') : '1 = 0')
                . (empty($properties) || isset($properties['name']) ? " OR LOWER(name) ?LIKE? LOWER($sql_search)" : '')
                . (empty($properties) || isset($properties['additional-info']) ? " OR LOWER(info) ?LIKE? LOWER($sql_search)" : '')
                . " ORDER by name
                LIMIT ?", array(intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15))));

                $result = array();
            if ($candidates) {
                foreach ($candidates as $idx => $row) {
                    $name = truncate_str($row['name'], 50);
                    $name_class = '';

                    $icon = 'fa-fw lms-ui-icon-netnode';

                    $description = '';
                    $description_class = '';
                    $action = '?m=netnodeinfo&id=' . $row['id'];

                    if ((empty($properties) || isset($properties['id'])) && preg_match("~^$search\$~i", $row['id'])) {
                        $description = trans('Id') . ': ' . $row['id'];
                    } else if ((empty($properties) || isset($properties['name'])) && preg_match("~$search~i", $row['name'])) {
                        $description = trans('Name') . ': ' . htmlspecialchars($row['name']);
                    } else if ((empty($properties) || isset($properties['additional-info'])) && preg_match("~$search~i", $row['info'])) {
                        //$description = trans('Additional information:') . ' ' . htmlspecialchars($row['info']);
                        $description = trans('Additional information:') . ' &hellip;';
                    }

                    $result[$row['id']] = compact('name', 'name_class', 'icon', 'description', 'description_class', 'action');
                }
            }
            $hook_data = array(
                'search' => $search,
                'sql_search' => $sql_search,
                'properties' => $properties,
                'session' => $SESSION,
                'result' => $result
            );
            $hook_data = $LMS->executeHook('quicksearch_ajax_netnode', $hook_data);
            $result = $hook_data['result'];
            header('Content-type: application/json');
            echo json_encode(array_values($result));
            $SESSION->close();
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
        if (empty($search) || (!ConfigHelper::checkPrivilege('network_management') && !ConfigHelper::checkPrivilege('read_only'))) {
            die;
        }

        if (isset($_GET['ajax'])) { // support for AutoSuggest
            $candidates = $DB->GetAll(
                "SELECT
                    d.id,
                    d.name,
                    d.serialnumber,
                    d.description,
                    a.location,
                    no.lastonline
                FROM netdevices d
                LEFT JOIN (
                    SELECT netdev AS netdevid, MAX(lastonline) AS lastonline
                    FROM nodes
                    WHERE nodes.netdev IS NOT NULL AND nodes.ownerid IS NULL
                        AND lastonline > 0
                    GROUP BY netdev
                ) no ON no.netdevid = d.id
                LEFT JOIN vaddresses a ON d.address_id = a.id
                WHERE "
                . (empty($properties) || isset($properties['id']) ? (preg_match('/^[0-9]+$/', $search) ? 'd.id = ' . $search : '1=0') : '1=0')
                . (empty($properties) || isset($properties['name']) ? " OR LOWER(d.name) ?LIKE? LOWER($sql_search)" : '')
                . (empty($properties) || isset($properties['serial']) ? " OR LOWER(d.serialnumber) ?LIKE? LOWER($sql_search)" : '')
                . (empty($properties) || isset($properties['description']) ? " OR LOWER(d.description) ?LIKE? LOWER($sql_search)" : '')
                . (empty($properties) || isset($properties['mac']) ? " OR EXISTS (SELECT 1 FROM netdevicemacs WHERE netdevicemacs.netdevid = d.id AND LOWER(netdevicemacs.mac) ?LIKE? LOWER($sql_search))" : '')
                . (empty($properties) || isset($properties['location_address']) ? " OR LOWER(a.location) ?LIKE? LOWER($sql_search)" : '')
                . ' ORDER by name
                LIMIT ?',
                array(
                    intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15)),
                )
            );

                $result = array();
            if ($candidates) {
                $lastonline_limit = ConfigHelper::getConfig('phpui.lastonline_limit');

                foreach ($candidates as $idx => $row) {
                    $name = truncate_str($row['name'], 50);
                    $name_class = '';

                    if (!$row['lastonline']) {
                        $icon = 'fa-fw lms-ui-icon-netdevunk';
                    } else if (time() - $row['lastonline'] <= $lastonline_limit) {
                        $icon = 'fa-fw lms-ui-icon-netdevon';
                    } else {
                        $icon = 'fa-fw lms-ui-icon-netdevoff';
                    }

                    $description = '';
                    $description_class = '';
                    $action = '?m=netdevinfo&id=' . $row['id'];

                    if ((empty($properties) || isset($properties['id'])) && preg_match("~^$search\$~i", $row['id'])) {
                            $description = trans('Id:') . ' ' . $row['id'];
                    } else if ((empty($properties) || isset($properties['name'])) && preg_match("~$search~i", $row['name'])) {
                        $description = trans('Name') . ': ' . htmlspecialchars($row['name']);
                    } else if ((empty($properties) || isset($properties['serial'])) && preg_match("~$search~i", $row['serialnumber'])) {
                        $description = trans('Serial number:') . ' ' . $row['serialnumber'];
                    } else if ((empty($properties) || isset($properties['location_address'])) && preg_match("~$search~i", $row['location'])) {
                        $description = trans('Address') . ': ' . htmlspecialchars($row['location']);
                    } else if ((empty($properties) || isset($properties['description'])) && preg_match("~$search~i", $row['description'])) {
                        //$description = trans('Description:') . ' ' . htmlspecialchars($row['description']);
                        $description = trans('Description:') . ' &hellip;';
                    }

                    $result[$row['id']] = compact('name', 'name_class', 'icon', 'description', 'description_class', 'action');
                }
            }
            $hook_data = array(
                'search' => $search,
                'sql_search' => $sql_search,
                'properties' => $properties,
                'session' => $SESSION,
                'result' => $result
            );
            $hook_data = $LMS->executeHook('quicksearch_ajax_netdevice', $hook_data);
            $result = $hook_data['result'];
            header('Content-type: application/json');
            echo json_encode(array_values($result));
            $SESSION->close();
            exit;
        }

        if (is_numeric($search)) {
            if ($netdevid = $DB->GetOne('SELECT id FROM netdevices WHERE id = ' . $search)) {
                $target = '?m=netdevinfo&id=' . $netdevid;
                break;
            }
        }

        $s = array();
        if (empty($properties) || isset($properties['name'])) {
            $s['name'] = $search;
        }
        if (empty($properties) || isset($properties['serial'])) {
            $s['serialnumber'] = $search;
        }
        if (empty($properties) || isset($properties['location_address'])) {
            $s['location'] = $search;
        }
        $SESSION->save('netdevsearch', $s);
        $SESSION->save('ndlsk', 'OR');

        $target = '?m=netdevsearch&search';

        break;

    case 'ticket':
        if (empty($search) || (!ConfigHelper::checkPrivilege('helpdesk_operation') && !ConfigHelper::checkPrivilege('read_only'))) {
            die;
        }

        if (isset($_GET['ajax'])) { // support for AutoSuggest
            $categories = $LMS->GetUserCategories(Auth::GetCurrentUser());
            foreach ($categories as $category) {
                $catids[] = $category['id'];
            }

            $userid = Auth::GetCurrentUser();

            $user_permission_checks = ConfigHelper::checkConfig('rt.additional_user_permission_checks', ConfigHelper::checkConfig('phpui.helpdesk_additional_user_permission_checks'));
            $allow_empty_categories = ConfigHelper::checkConfig('rt.allow_empty_categories', ConfigHelper::checkConfig('phpui.helpdesk_allow_empty_categories'));

            $candidates = $DB->GetAll(
                "SELECT t.id, t.subject, t.requestor, t.requestor_mail, t.requestor_phone, t.state, c.name, c.lastname
				FROM rttickets t
				LEFT JOIN rtrights r ON r.queueid = t.queueid AND r.userid = ? AND r.rights & ? > 0
				LEFT JOIN rtticketcategories tc ON t.id = tc.ticketid
				LEFT JOIN customerview c on (t.customerid = c.id)
				WHERE (r.rights IS NOT NULL" . ($user_permission_checks ? ' OR t.owner = ' . $userid . ' OR t.verifierid = ' . $userid : '') . ")
					AND " . (is_array($catids) ? '(tc.categoryid IN (' . implode(',', $catids) . ') ' . ($allow_empty_categories ? ' OR tc.categoryid IS NULL' : '') . ')' : 'tc.categoryid IS NULL')
                    . (empty($properties) || isset($properties['unresolvedonly']) ? ' AND t.state <> ' . RT_RESOLVED : '') . " AND ("
                    . (empty($properties) || isset($properties['id']) ? (preg_match('/^[0-9]+$/', $search) ? 't.id = ' . $search : '1=0') : '1=0')
                    . (empty($properties) || isset($properties['subject']) ? " OR LOWER(t.subject) ?LIKE? LOWER($sql_search)" : '')
                    . (empty($properties) || isset($properties['requestor']) ? " OR LOWER(t.requestor) ?LIKE? LOWER($sql_search)" : '')
                    . (empty($properties) || isset($properties['requestor_mail']) ? " OR LOWER(t.requestor_mail) ?LIKE? LOWER($sql_search)" : '')
                    . (empty($properties) || isset($properties['requestor_phone']) ? " OR LOWER(t.requestor_phone) ?LIKE? LOWER($sql_search)" : '')
                    . (empty($properties) || isset($properties['customername']) ? " OR LOWER(c.name) ?LIKE? LOWER($sql_search)
						OR LOWER(c.lastname) ?LIKE? LOWER($sql_search)" : '') . ")
					ORDER BY t.subject, t.id, c.lastname, c.name, t.requestor, t.requestor_mail, t.requestor_phone
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
                        case RT_VERIFIED:
                            $name_classes[] = 'lms-ui-suggestion-ticket-state-verified';
                            break;
                    }
                    $name_class = implode(' ', $name_classes);

                    $description = '';
                    $description_class = '';
                    $action = '?m=rtticketview&id=' . $row['id'];

                    if ((empty($properties) || isset($properties['id'])) && preg_match("~^$search\$~i", $row['id'])) {
                        $description = trans('Id') . ': ' . $row['id'];
                    } else if ((empty($properties) || isset($properties['subject'])) && preg_match("~$search~i", $row['subject'])) {
                        $description = trans('Subject:') . ' ' . htmlspecialchars($row['subject']);
                    } else if ((empty($properties) || isset($properties['requestor'])) && preg_match("~$search~i", $row['requestor'])) {
                        $description = trans('First/last name') . ': '
                        . htmlspecialchars(preg_replace('/ <.*/', '', $row['requestor']));
                    } else if ((empty($properties) || isset($properties['requestor_mail'])) && preg_match("~$search~i", $row['requestor_mail'])) {
                        $description = trans('Email') . ': '
                            . htmlspecialchars(preg_replace('/ <.*/', '', $row['requestor_mail']));
                    } else if ((empty($properties) || isset($properties['requestor_phone'])) && preg_match("~$search~i", $row['requestor_phone'])) {
                        $description = trans('Phone') . ': '
                            . htmlspecialchars(preg_replace('/ <.*/', '', $row['requestor_phone']));
                    } else if ((empty($properties) || isset($properties['customername'])) && preg_match("~^$search~i", $row['name'])) {
                        $description = trans('First/last name') . ': ' . $row['name'];
                    } else if ((empty($properties) || isset($properties['customername'])) && preg_match("~^$search~i", $row['lastname'])) {
                        $description = trans('First/last name') . ': ' . $row['lastname'];
                    }

                    $result[$row['id']] = array_merge(
                        compact('name', 'name_class', 'description', 'description_class', 'action'),
                        array('id' => $row['id'])
                    );
                }
            }
            $hook_data = array(
                'search' => $search,
                'sql_search' => $sql_search,
                'properties' => $properties,
                'session' => $SESSION,
                'result' => $result
            );
            $hook_data = $LMS->executeHook('quicksearch_ajax_ticket', $hook_data);
            $result = $hook_data['result'];
            header('Content-type: application/json');
            echo json_encode(array_values($result));
            $SESSION->close();
            exit;
        }

        if (is_numeric($search) && intval($search)>0) {
            $target = '?m=rtticketview&id='.intval($search);
        } else {
            $params = array('operator' => 'OR');
            if (empty($properties) || isset($properties['subject'])) {
                $params['subject'] = $search;
            }
            if (empty($properties) || isset($properties['requestor'])) {
                $params['name'] = $search;
            }
            if (empty($properties) || isset($properties['unresolvedonly'])) {
                $params['state'] = -2;
            }
            $SESSION->save('rtsearch', $params);

            $target = '?m=rtsearch&s=1&quicksearch=1';
        }
        break;
    case 'wireless':
        if (empty($search) || (!ConfigHelper::checkPrivilege('network_management') && !ConfigHelper::checkPrivilege('read_only'))) {
            die;
        }

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
            $hook_data = array(
                'search' => $search,
                'sql_search' => $sql_search,
                'properties' => $properties,
                'session' => $SESSION,
                'result' => $result
            );
            $hook_data = $LMS->executeHook('quicksearch_ajax_wireless', $hook_data);
            $result = $hook_data['result'];
            header('Content-type: application/json');
            echo json_encode(array_values($result));
            $SESSION->close();
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
        if (empty($search) || (!ConfigHelper::checkPrivilege('network_management') && !ConfigHelper::checkPrivilege('read_only'))) {
            die;
        }

        if (isset($_GET['ajax'])) { // support for AutoSuggest
            $candidates = $DB->GetAll("SELECT id, name, INET_NTOA(address) AS address FROM networks
                WHERE "
                . (empty($properties) || isset($properties['id']) ? (preg_match('/^[0-9]+$/', $search) ? 'id = ' . intval($search) : '1 = 0') : '1 = 0')
                . (empty($properties) || isset($properties['name']) ? " OR LOWER(name) ?LIKE? LOWER($sql_search)" : '')
                . (empty($properties) || isset($properties['address']) ? " OR INET_NTOA(address) ?LIKE? $sql_search" : '')
                . " ORDER by name
                LIMIT ?", array(intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15))));

            $result = array();
            if ($candidates) {
                foreach ($candidates as $idx => $row) {
                    $name = truncate_str($row['name'], 50);
                    $name_class = '';

                    $icon = 'fa-fw lms-ui-icon-network';

                    $description = '';
                    $description_class = '';
                    $action = '?m=netinfo&id=' . $row['id'];

                    if (preg_match("~^$search\$~i", $row['id'])) {
                        $description = trans('Id:') . ' ' . $row['id'];
                    }
                    if ((empty($properties) || isset($properties['id'])) && preg_match("~^$search\$~i", $row['id'])) {
                        $description = trans('Id:') . ' ' . $row['id'];
                    } else if ((empty($properties) || isset($properties['name'])) && preg_match("~$search~i", $row['name'])) {
                        $description = trans('Network name:') . ' ' . $row['name'];
                    } else if ((empty($properties) || isset($properties['address'])) && preg_match("~$search~i", $row['address'])) {
                        $description = trans('Network address:') . ' ' . $row['address'];
                    }

                    $result[$row['id']] = compact('name', 'name_class', 'icon', 'description', 'description_class', 'action');
                }
            }
            $hook_data = array(
                'search' => $search,
                'sql_search' => $sql_search,
                'properties' => $properties,
                'session' => $SESSION,
                'result' => $result
            );
            $hook_data = $LMS->executeHook('quicksearch_ajax_network', $hook_data);
            $result = $hook_data['result'];
            header('Content-type: application/json');
            echo json_encode(array_values($result));
            $SESSION->close();
            exit;
        }

        if (is_numeric($search)) {
            if ($networkid = $DB->GetOne('SELECT id FROM networks WHERE id = ' . $search)) {
                $target = '?m=netinfo&id=' . $networkid;
                break;
            }
        }

        $s['network_name'] = $search;
        $s['compareType'] = 1;

        $SESSION->save('netsearch', $s);

        $target = '?m=netsearch';

        break;
    case 'account':
        if (empty($search) || (!ConfigHelper::checkPrivilege('hosting_management') && !ConfigHelper::checkPrivilege('read_only'))) {
            die;
        }

        $ac = explode('@', $search);

        if (isset($_GET['ajax'])) { // support for AutoSuggest
            $username = $DB->Escape('%'.$ac[0].'%');
            $domain   = isset($ac[1]) ? $DB->Escape('%'.$ac[1].'%') : null;

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
                    $icon = 'fa-fw lms-ui-icon-hosting';
                    $description = '';
                    $description_class = '';
                    if ($row['type']) {
                        $action = '?m=aliasinfo&id=' . $row['id'];
                    } else {
                        $action = '?m=accountinfo&id=' . $row['id'];
                    }

                    $result[$row['id']] = compact('name', 'name_class', 'icon', 'description', 'description_class', 'action');
                }
            }
            $hook_data = array(
                'search' => $search,
                'sql_search' => $sql_search,
                'properties' => $properties,
                'session' => $SESSION,
                'result' => $result
            );
            $hook_data = $LMS->executeHook('quicksearch_ajax_account', $hook_data);
            $result = $hook_data['result'];
            header('Content-type: application/json');
            echo json_encode(array_values($result));
            $SESSION->close();
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
        if (empty($search) || (!ConfigHelper::checkPrivilege('customer_management') && !ConfigHelper::checkPrivilege('read_only'))) {
            die;
        }

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
                    //$description = trans('Document ID:') . ' ' . $row['id'];
                    $description_class = '';
                    $action = '?m=customerinfo&id=' . $row['cid'];

                    $result[$row['id']] = compact('name', 'name_class', 'description', 'description_class', 'action');
                }
            }
            $hook_data = array(
                'search' => $search,
                'sql_search' => $sql_search,
                'properties' => $properties,
                'session' => $SESSION,
                'result' => $result
            );
            $hook_data = $LMS->executeHook('quicksearch_ajax_document', $hook_data);
            $result = $hook_data['result'];
            header('Content-type: application/json');
            echo json_encode(array_values($result));
            $SESSION->close();
            exit;
        }

        $docs = $DB->GetAll("SELECT d.id, d.type, d.customerid AS cid, d.name AS customername
			FROM documents d
			JOIN customerview c ON c.id = d.customerid
			WHERE LOWER(fullnumber) ?LIKE? LOWER($sql_search)");
        if (!empty($docs) && count($docs) == 1) {
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

    case 'config':
        if (isset($_GET['ajax'])) {
            header('Content-type: application/json');

            $markdown_documentation = Utils::LoadMarkdownDocumentation();
            if (empty($markdown_documentation)) {
                die;
            }

            $quicksearch_limit = intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15));
            $i = 1;
            $result = array();
            foreach ($markdown_documentation as $section => $variables) {
                if ($i > $quicksearch_limit) {
                    break;
                }
                if (!empty($_GET['section']) && $section != $_GET['section']) {
                    continue;
                }
                foreach ($variables as $variable => $documentation) {
                    if ($i > $quicksearch_limit) {
                        break;
                    }
                    if (!empty($search) && strpos($variable, $search) === false) {
                        continue;
                    }
                    $name = $variable;
                    $name_class = '';
                    $description = trans('Section:') . ' ' . $section;
                    $description_class = '';
                    $action = '';
                    $tip = Utils::MarkdownToHtml($documentation);
                    $result[$variable . '.' . $section] = compact('name', 'name_class', 'description', 'description_class', 'action', 'section', 'tip');
                    $i++;
                }
            }

            if (!empty($result)) {
                ksort($result);
            }
            echo json_encode(array_values($result));

            $SESSION->close();
            die;
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

$SESSION->redirect(!empty($target) ? $target : '?' . $SESSION->remove_history_entry());

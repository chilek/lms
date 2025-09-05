<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2020 LMS Developers
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

/*
use setasign\Fpdi\Tcpdf\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\FpdiProtection\FpdiProtection;
*/

/**
 * LMSDocumentManager
 *
 */
class LMSDocumentManager extends LMSManager implements LMSDocumentManagerInterface
{

    public function GetDocuments($customerid = null, $limit = null, $all = false)
    {
        if (!$customerid) {
            return null;
        }

        if ($list = $this->db->GetAll(
            'SELECT d.id AS docid, d.number, d.type, c.title, c.fromdate, c.todate,
				c.description, n.template, d.closed, d.confirmdate,
				d.archived, d.adate, u3.name AS ausername, d.senddate,
				d.cdate, u.name AS username, d.sdate, d.cuserid, u2.name AS cusername,
				d.type AS doctype, d.template AS doctemplate, reference
			FROM documents d
			LEFT JOIN documentcontents c ON c.docid = d.id
			LEFT JOIN docrights r ON (d.type = r.doctype AND r.userid = ? AND (r.rights & ?) > 0)
			LEFT JOIN vusers u ON u.id = d.userid
			LEFT JOIN vusers u2 ON u2.id = d.cuserid
			LEFT JOIN vusers u3 ON u3.id = d.auserid
			LEFT JOIN numberplans n ON (d.numberplanid = n.id)
			WHERE d.customerid = ?' . ($all ? '' : ' AND c.docid IS NOT NULL AND r.doctype IS NOT NULL') . '
			ORDER BY cdate',
            array(
                Auth::GetCurrentUser(),
                DOCRIGHT_VIEW,
                $customerid,
            )
        )) {
            foreach ($list as &$doc) {
                $doc['attachments'] = $this->db->GetAll('SELECT * FROM documentattachments
					WHERE docid = ? ORDER BY type DESC, filename', array($doc['docid']));
                if (!empty($doc['reference'])) {
                    $doc['reference'] = $this->db->GetRow('SELECT id, type, fullnumber, cdate FROM documents
						WHERE id = ?', array($doc['reference']));
                }
            }
            if ($limit) {
                $index = (count($list) - $limit) > 0 ? count($list) - $limit : 0;
                $result = array();

                for ($i = $index; $i < count($list); $i++) {
                    $result[] = $list[$i];
                }

                return $result;
            } else {
                return $list;
            }
        }
    }

    /**
     * @param array $params associative array of parameters described below:
     *      type - document type (default: 0 = any), array() or single integer value
     *      service - service type (default: 0 = any), array() or single integer value
     *      customer - document customer (default: null = any): single integer value
     *      numberplan - document numbering plan (default: null = any): single integer value
     *      usertype - document user type (default: creator): supported values:
     *          creator, authorising, archiving
     *      userid - document user (default: 0 = any): array() or single integer value
     *      periodtype - document selection period type (default: creationdate)
     *          supported values: creationdate, confirmationdate, archivizationdate, fromdate, todate
     *      from - document selection period start (default: 0 = any value): single integer value
     *          int unix timestamp format,
     *      to - document selection period end (default: 0 = any value): singe integer value
     *          in unix timestamp format,
     *      status - document status (default: -1 = any): single integer value:
     *          0 - closed document,
     *          1 - open document,
     *      count - count records only or return selected record interval
     *          true - count only,
     *          false - get records,
     *      offset - first returned record (null = 0),
     *      limit - returned record count (null = unlimited),
     *      order - returned records order (default: cdate,asc)
     *          can contain field_name,order pairs,
     *          supported field names:
     *          type, title, customer, user, cuser, sdate
     *          supported orders:
     *          asc = ascending, desc = descending
     * @return mixed
     */
    public function GetDocumentList(array $params)
    {
        $order = $params['order'] ?? 'cdate,asc';
        $type = $params['type'] ?? null;
        $service = $params['service'] ?? null;
        $customer = $params['customer'] ?? null;
        $numberplan = $params['numberplan'] ?? null;
        $usertype = $params['usertype'] ?? 'creator';
        $userid = $params['userid'] ?? null;
        $periodtype = $params['periodtype'] ?? 'creationdate';
        $from = $params['from'] ?? 0;
        $to = $params['to'] ?? 0;
        $status = $params['status'] ?? -1;
        $archived = $params['archived'] ?? -1;
        $limit = $params['limit'] ?? null;
        $offset = $params['offset'] ?? null;
        $count = isset($params['count']) && $params['count'];

        if ($order=='') {
            $order='cdate,asc';
        }

        [$order, $direction] = sscanf($order, '%[^,],%s');
        ($direction=='desc') ? $direction = 'desc' : $direction = 'asc';

        switch ($order) {
            case 'type':
                $sqlord = ' ORDER BY d.type '.$direction.', d.name';
                break;
            case 'title':
                $sqlord = ' ORDER BY title '.$direction.', d.name';
                break;
            case 'customer':
                $sqlord = ' ORDER BY d.name '.$direction.', title';
                break;
            case 'user':
                $sqlord = ' ORDER BY u.lastname '.$direction.', title';
                break;
            case 'cuser':
                $sqlord = ' ORDER BY u2.lastname '.$direction.', title';
                break;
            case 'sdate':
                $sqlord = ' ORDER BY d.sdate '.$direction.', d.name';
                break;
            default:
                $sqlord = ' ORDER BY d.cdate '.$direction.', d.name';
                break;
        }

        switch ($usertype) {
            case 'creator':
                $userfield = 'd.userid';
                break;
            case 'authorising':
                $userfield = 'd.cuserid';
                break;
            case 'archiver':
                $userfield = 'd.auserid';
                break;
            default:
                $userfield = 'd.userid';
        }

        switch ($periodtype) {
            case 'creationdate':
                $datefield = 'd.cdate';
                break;
            case 'confirmationdate':
                $datefield = 'd.sdate';
                break;
            case 'archivizationdate':
                $datefield = 'd.adate';
                break;
            case 'fromdate':
                $datefield = 'documentcontents.fromdate';
                break;
            case 'todate':
                $datefield = 'documentcontents.todate';
                break;
            default:
                $datefield = 'd.cdate';
        }

        switch ($status) {
            case 0:
                $status_sql = ' AND d.closed = ' . DOC_OPEN . ' AND (d.confirmdate = 0 OR d.confirmdate > ?NOW?)';
                break;
            case 1:
                $status_sql = ' AND d.closed > ' . DOC_OPEN;
                break;
            case 2:
                $status_sql = ' AND d.closed = ' . DOC_OPEN . ' AND d.confirmdate = -1';
                break;
            case 3:
                $status_sql = ' AND d.closed = ' . DOC_OPEN . ' AND d.confirmdate > 0 AND d.confirmdate > ?NOW?';
                break;
            case 4:
                $status_sql = ' AND d.closed = ' . DOC_CLOSED_AFTER_CUSTOMER_SMS;
                break;
            case 5:
                $status_sql = ' AND d.closed = ' . DOC_CLOSED_AFTER_CUSTOMER_SCAN;
                break;
            case 6:
                $status_sql = ' AND d.closed = ' . DOC_OPEN . ' AND d.confirmdate > 0 AND d.confirmdate < ?NOW?';
                break;
            default:
                $status_sql = '';
        }

        if ($count) {
            return $this->db->GetOne(
                'SELECT COUNT(documentcontents.docid)
				FROM documentcontents
				JOIN documents d ON (d.id = documentcontents.docid)
				JOIN docrights r ON (d.type = r.doctype AND r.userid = ? AND (r.rights & ?) > 0)
				LEFT JOIN vusers u ON u.id = d.userid
				LEFT JOIN vusers u2 ON u2.id = d.cuserid
				LEFT JOIN numberplans ON (d.numberplanid = numberplans.id)
				LEFT JOIN (
					SELECT DISTINCT c.id AS customerid, 1 AS senddocuments FROM customers c
					JOIN customercontacts cc ON cc.customerid = c.id
					WHERE cc.type & ' . (CONTACT_EMAIL | CONTACT_DOCUMENTS | CONTACT_DISABLED) . ' = ' . (CONTACT_EMAIL | CONTACT_DOCUMENTS) . '
				) i ON i.customerid = d.customerid
				' . ($service ? 'JOIN (
					SELECT DISTINCT a.docid FROM assignments a
						JOIN tariffs t ON t.id = a.tariffid
						WHERE t.type IN (' . implode(',', $service) . ')
					) s ON s.docid = d.id' : '') . '
				LEFT JOIN (
					SELECT DISTINCT a.customerid FROM vcustomerassignments a
					JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
					WHERE e.userid = lms_current_user()
				) e ON (e.customerid = d.customerid)
				WHERE e.customerid IS NULL '
                    .($customer ? 'AND d.customerid = '.intval($customer) : '')
                    .($type ? (is_array($type) ? ' AND d.type IN (' . implode(',', $type) . ')' : ' AND d.type = '.intval($type)) : '')
                    . ($userid ? ' AND ' . $userfield . (is_array($userid) ? ' IN (' . implode(',', $userid) . ')' : ' = ' . intval($userid)) : '')
                    . ($numberplan ? ' AND d.numberplanid = ' . intval($numberplan) : '')
                    .($from ? ' AND ' . $datefield . ' >= '.intval($from) : '')
                    .($to ? ' AND ' . $datefield . ' <= '.intval($to) : '')
                    . $status_sql
                    . ($archived == -1 ? '' : ' AND d.archived = ' . intval($archived)),
                array(
                    Auth::GetCurrentUser(),
                    DOCRIGHT_VIEW,
                )
            );
        }

        $list = $this->db->GetAll(
            'SELECT documentcontents.docid, d.number, d.type, title, d.cdate,
				u.name AS username, u.lastname, fromdate, todate, description,
				numberplans.template, d.closed, d.confirmdate, d.senddate,
				d.archived, d.adate, d.auserid, u3.name AS ausername,
				d.name, d.customerid, d.sdate, d.cuserid, u2.name AS cusername,
				u2.lastname AS clastname, d.reference, i.senddocuments
			FROM documentcontents
			JOIN documents d ON (d.id = documentcontents.docid)
			JOIN docrights r ON (d.type = r.doctype AND r.userid = ? AND (r.rights & ?) > 0)
			LEFT JOIN vusers u ON u.id = d.userid
			LEFT JOIN vusers u2 ON u2.id = d.cuserid
			LEFT JOIN vusers u3 ON u3.id = d.auserid
			LEFT JOIN numberplans ON (d.numberplanid = numberplans.id)
			LEFT JOIN (
				SELECT DISTINCT c.id AS customerid, 1 AS senddocuments FROM customers c
				JOIN customercontacts cc ON cc.customerid = c.id
				WHERE cc.type & ' . (CONTACT_EMAIL | CONTACT_DOCUMENTS | CONTACT_DISABLED) . ' = ' . (CONTACT_EMAIL | CONTACT_DOCUMENTS) . '
			) i ON i.customerid = d.customerid
			' . ($service ? 'JOIN (
				SELECT DISTINCT a.docid FROM assignments a
					JOIN tariffs t ON t.id = a.tariffid
					WHERE t.type IN (' . implode(',', $service) . ')
				) s ON s.docid = d.id' : '') . '
			LEFT JOIN (
				SELECT DISTINCT a.customerid FROM vcustomerassignments a
				JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
				WHERE e.userid = lms_current_user()
			) e ON (e.customerid = d.customerid)
			WHERE e.customerid IS NULL '
            .($customer ? 'AND d.customerid = '.intval($customer) : '')
            .($type ? (is_array($type) ? ' AND d.type IN (' . implode(',', $type) . ')' : ' AND d.type = '.intval($type)) : '')
            . ($userid ? ' AND ' . $userfield . (is_array($userid) ? ' IN (' . implode(',', $userid) . ')' : ' = ' . intval($userid)) : '')
            . ($numberplan ? ' AND d.numberplanid = ' . intval($numberplan) : '')
            .($from ? ' AND ' . $datefield . ' >= '.intval($from) : '')
            .($to ? ' AND ' . $datefield . ' <= '.intval($to) : '')
            . $status_sql
            . ($archived == -1 ? '' : ' AND d.archived = ' . intval($archived))
            .$sqlord
            . (isset($limit) ? ' LIMIT ' . $limit : '')
            . (isset($offset) ? ' OFFSET ' . $offset : ''),
            array(
                Auth::GetCurrentUser(),
                DOCRIGHT_VIEW,
            )
        );

        if (empty($list)) {
            $list = array();
        } else {
            foreach ($list as &$document) {
                $document['attachments'] = $this->db->GetAll('SELECT id, filename, md5sum, contenttype, type, type AS main, cdate
				    FROM documentattachments WHERE docid = ? ORDER BY type DESC, filename', array($document['docid']));
                if (!empty($document['reference'])) {
                    $document['reference'] = $this->db->GetRow('SELECT id, type, fullnumber, cdate FROM documents
					WHERE id = ?', array($document['reference']));
                }
            }
            unset($document);
        }

        $list['total'] = count($list);
        $list['direction'] = $direction;
        $list['order'] = $order;

        return $list;
    }

    /*
     \param array $properties - associative array with function parameters:
        doctype: document type
        cdate: document creation date
        division: id of company/division
        next: flag which tells if next number should be determined
        customerid: customer id for number plans
    */
    public function GetNumberPlans($properties)
    {
        extract($properties);
        if (!isset($doctype)) {
            $doctype = null;
        }
        if (!isset($cdate)) {
            $cdate = time();
        } else {
            $cdate = intval($cdate);
        }
        if (!isset($division)) {
            $division = null;
        }
        if (!isset($next)) {
            $next = true;
        }
        if (!isset($customerid)) {
            $customerid = null;
        }
        if (!isset($customertype)) {
            $customertype = null;
        }
        if (!isset($reference)) {
            $reference = null;
        }

        if (is_array($doctype)) {
            $where[] = 'n.doctype IN (' . implode(',', $doctype) . ')';
        } else if ($doctype) {
            $where[] = 'n.doctype = ' . intval($doctype);
        }

        if ($division) {
            $where[] = 'EXISTS (SELECT 1 FROM numberplanassignments
                WHERE planid = n.id AND divisionid = ' . intval($division) . ')';
        }

        if ($cdate) {
            $where[] = '(n.datefrom <= ' . $cdate . ' AND (n.dateto = 0 OR n.dateto >= ' . $cdate .  '))';
        }

        if (isset($customertype)) {
            $where[] = '(n.customertype IS NULL OR n.customertype = ' . intval($customertype) . ')';
        }

        if (!ConfigHelper::checkPrivilege('superuser')) {
            $userid = Auth::GetCurrentUser();
            $where[] = '(NOT EXISTS (
                    SELECT 1 FROM numberplanassignments WHERE planid = n.id
                )' . ($userid ? ' OR NOT EXISTS (
                    SELECT 1 FROM numberplanusers WHERE planid = n.id
                ) OR EXISTS (
                    SELECT 1 FROM numberplanusers u1
                    JOIN userdivisions u2 ON u2.userid = u1.userid
                    WHERE u1.userid = ' . $userid . '
                )' : '') . ')';
        }

        if (empty($where)) {
            $where = '';
        } else {
            $where = 'WHERE ' . implode(' AND ', $where);
        }

        $list = $this->db->GetAllByKey(
            'SELECT
                n.id, n.template, n.isdefault, n.period, n.doctype,
                n.customertype,
                n.datefrom,
                n.dateto,
                n.refflag,
                ((CASE WHEN n.customertype IS NULL THEN 100 ELSE 0 END)
                    + (CASE WHEN EXISTS (SELECT 1 FROM numberplanusers WHERE planid = n.id) THEN 1 ELSE 2 END)) AS idx
            FROM numberplans n
            ' . $where . '
            ORDER BY idx, n.id',
            'id'
        );

        if ($list && $next) {
            if ($cdate) {
                $curryear = date('Y', $cdate);
                $currmonth = date('n', $cdate);
            } else {
                $curryear = date('Y');
                $currmonth = date('n');
            }
            switch ($currmonth) {
                case 1:
                case 2:
                case 3:
                    $startq = 1;
                        $starthy = 1;
                    break;
                case 4:
                case 5:
                case 6:
                    $startq = 4;
                        $starthy = 1;
                    break;
                case 7:
                case 8:
                case 9:
                    $startq = 7;
                        $starthy = 7;
                    break;
                case 10:
                case 11:
                case 12:
                    $startq = 10;
                        $starthy = 7;
                    break;
            }

            $yearstart = mktime(0, 0, 0, 1, 1, $curryear);
            $yearend = mktime(0, 0, 0, 1, 1, $curryear + 1);
            $halfyearstart = mktime(0, 0, 0, $starthy, 1);
            $halfyearend = mktime(0, 0, 0, $starthy + 3, 1);
            $quarterstart = mktime(0, 0, 0, $startq, 1);
            $quarterend = mktime(0, 0, 0, $startq + 3, 1);
            $monthstart = mktime(0, 0, 0, $currmonth, 1, $curryear);
            $monthend = mktime(0, 0, 0, $currmonth + 1, 1, $curryear);
            $weekstart = mktime(0, 0, 0, $currmonth, date('j') - date('N') + 1);
            $weekend = mktime(0, 0, 0, $currmonth, date('j') - date('N') + 1 + 7);
            $daystart = mktime(0, 0, 0);
            $dayend = mktime(0, 0, 0, date('n'), date('j') + 1);

            foreach ($list as &$item) {
                $max = $this->db->GetOne(
                    'SELECT MAX(number) AS max
					FROM documents
					LEFT JOIN numberplans ON (numberplanid = numberplans.id)
					WHERE numberplanid = ? '
                        . (!preg_match('/%[0-9]*C/', $item['template']) || empty($customerid)
                            ? ''
                            : ' AND customerid = ' . intval($customerid)
                        )
                        . ($doctype ? ' AND numberplanid IN (' . implode(',', array_keys($list)) . ')' : '')
                        . ($reference ? ' AND numberplans.refflag = 1 AND documents.reference = ' . intval($reference) : '')
                    . ' AND cdate >= (CASE period
					WHEN ' . YEARLY . ' THEN ' . $yearstart . '
					WHEN ' . HALFYEARLY . ' THEN ' . $halfyearstart . '
					WHEN ' . QUARTERLY . ' THEN ' . $quarterstart . '
					WHEN ' . MONTHLY . ' THEN ' . $monthstart . '
					WHEN ' . WEEKLY . ' THEN ' . $weekstart . '
					WHEN ' . DAILY . ' THEN ' . $daystart . ' ELSE 0 END)
					AND cdate < (CASE period
					WHEN ' . YEARLY . ' THEN ' . $yearend . '
					WHEN ' . HALFYEARLY . ' THEN ' . $halfyearend . '
					WHEN ' . QUARTERLY . ' THEN ' . $quarterend . '
					WHEN ' . MONTHLY . ' THEN ' . $monthend . '
					WHEN ' . WEEKLY . ' THEN ' . $weekend . '
					WHEN ' . DAILY . ' THEN ' . $dayend . ' ELSE 4294967296 END)',
                    array($item['id'])
                );

                if (empty($max)) {
                    $item['next'] = 1;
                } else {
                    $item['next'] = $max + 1;
                }
            }
            unset($item);
        }

        return $list;
    }

    /*
     \param array $properties - associative array with function parameters:
        doctype: document type
        cdate: document creation date
    */
    public function getSystemDefaultNumberPlan($properties)
    {
        extract($properties);
        if (!isset($doctype)) {
            $doctype = null;
        }
        if (!isset($cdate)) {
            $cdate = null;
        }

        $list[0] = array(
            'doctype' => $doctype,
            'id' => 0,
            'idx' => 1,
            'isDefault' => 0,
            'datefrom' => 0,
            'dateto' => 0,
            'period' => YEARLY,
            'template' => DEFAULT_NUMBER_TEMPLATE,
        );

        if ($cdate) {
            $curryear = date('Y', intval($cdate));
        } else {
            $curryear = date('Y');
        }

        $cdate = mktime(0, 0, 0, 1, 1, $curryear);
        $args = array(
            'doctype' => $doctype,
            'cdate' => $cdate,
        );

        $list[0]['next'] = $this->GetNewDocumentNumber($args);

        return $list;
    }

    public function getDefaultNumberPlanID($doctype, $divisionid = null, $cdate = null)
    {
        if (empty($cdate)) {
            $cdate = time();
        } else {
            $cdate = intval($cdate);
        }

        if (!empty($divisionid)) {
            return $this->db->GetOne(
                'SELECT n.id,
                    (CASE WHEN a.planid IS NULL THEN (CASE WHEN u.planid IS NULL THEN 3 ELSE 1 END) ELSE (CASE WHEN u.planid IS NULL THEN 2 ELSE 0 END) END) AS idx
                FROM numberplans n
                LEFT JOIN numberplanassignments a ON a.planid = n.id
                LEFT JOIN numberplanusers u ON u.planid = n.id
                WHERE n.doctype = ? AND n.isdefault = 1
                    AND (u.planid IS NULL OR u.userid = ?)
                    AND (a.planid IS NULL OR a.divisionid = ?)
                    ' . (empty($cdate)
                        ? ''
                        : ' AND n.datefrom <= ' . $cdate . '
                            AND (n.dateto = 0 OR n.dateto >= ' . $cdate . ')'
                    ) . '
                ORDER BY idx
                LIMIT 1',
                array(
                    $doctype,
                    Auth::getCurrentUser(),
                    $divisionid,
                )
            );
        } else {
            return $this->db->GetOne(
                'SELECT n.id,
                    (CASE WHEN u.planid IS NULL THEN 1 ELSE 0 END) AS idx
                FROM numberplans n
                LEFT JOIN numberplanusers u ON u.planid = n.id
                WHERE n.doctype = ? AND n.isdefault = 1
                    AND (u.userid IS NULL OR u.userid = ?)
                    ' . (empty($cdate)
                        ? ''
                        : ' AND n.datefrom <= ' . $cdate . '
                            AND (n.dateto = 0 OR n.dateto >= ' . $cdate . ')'
                    ) . '
                ORDER BY idx
                LIMIT 1',
                array(
                    $doctype,
                    Auth::getCurrentUser(),
                )
            );
        }
    }

    public function checkNumberPlanAccess($id)
    {
        return $this->db->GetOne(
            'SELECT 1 FROM numberplans n
            WHERE id = ?'
                . (ConfigHelper::checkPrivilege('superuser')
                    ? ''
                    : ' AND (NOT EXISTS (SELECT 1 FROM numberplanassignments WHERE planid = n.id)
                OR EXISTS (
                    SELECT 1 FROM numberplanassignments a
                    JOIN userdivisions u ON u.divisionid = a.divisionid
                    WHERE a.planid = n.id AND u.userid = ' . Auth::GetCurrentUser() . '
                )) AND (NOT EXISTS (SELECT 1 FROM numberplanusers WHERE planid = n.id)
                OR EXISTS (
                    SELECT 1 FROM numberplanusers u2
                    WHERE u2.planid = n.id AND u2.userid = ' . Auth::GetCurrentUser() . '
            ))'),
            array($id)
        ) > 0;
    }

    public function getNumberPlan($id)
    {
        $numberplan = $this->db->GetRow(
            'SELECT id, period, template, doctype, isdefault, customertype, datefrom, dateto, refflag
            FROM numberplans n
            WHERE id = ?'
            . (ConfigHelper::checkPrivilege('superuser')
                ? ''
                : ' AND (NOT EXISTS (SELECT 1 FROM numberplanassignments WHERE planid = n.id)
                    OR EXISTS (
                        SELECT 1 FROM numberplanassignments a
                        JOIN userdivisions u ON u.divisionid = a.divisionid
                        WHERE a.planid = n.id AND u.userid = ' . Auth::GetCurrentUser() . '
                    )) AND (NOT EXISTS (SELECT 1 FROM numberplanusers WHERE planid = n.id)
                    OR EXISTS (
                        SELECT 1 FROM numberplanusers u2
                        WHERE u2.planid = n.id AND u2.userid = ' . Auth::GetCurrentUser() . '
                    ))'),
            array($id)
        );

        $divisions = $this->db->GetCol(
            'SELECT divisionid
            FROM numberplanassignments
            WHERE planid = ?',
            array($id)
        );
        $numberplan['divisions'] = $divisions ? array_flip($divisions) : array();

        $users = $this->db->GetCol(
            'SELECT userid
            FROM numberplanusers
            WHERE planid = ?',
            array($id)
        );
        $numberplan['users'] = $users ? array_flip($users) : array();

        return $numberplan;
    }

    public function getNumberPlanList(array $params)
    {
        $currmonth = date('n');
        switch ($currmonth) {
            case 1:
            case 2:
            case 3:
                $startq = 1;
                break;
            case 4:
            case 5:
            case 6:
                $startq = 4;
                break;
            case 7:
            case 8:
            case 9:
                $startq = 7;
                break;
            case 10:
            case 11:
            case 12:
                $startq = 10;
                break;
        }

        $yearstart = mktime(0, 0, 0, 1, 1);
        $quarterstart = mktime(0, 0, 0, $startq, 1);
        $monthstart = mktime(0, 0, 0, $currmonth, 1);
        $weekstart = mktime(0, 0, 0, $currmonth, date('j') - date('N') + 1);
        $daystart = mktime(0, 0, 0);

        if (!empty($params['count'])) {
            return intval(
                $this->db->GetOne(
                    'SELECT COUNT(n.id) FROM numberplans n
                    WHERE' . (ConfigHelper::checkPrivilege('superuser')
                        ? ' 1 = 1'
                        : ' (NOT EXISTS (SELECT 1 FROM numberplanassignments WHERE planid = n.id)
                        OR EXISTS (
                            SELECT 1 FROM numberplanassignments a
                            JOIN userdivisions ud ON ud.divisionid = a.divisionid
                            WHERE a.planid = n.id
                        )) AND (NOT EXISTS (SELECT 1 FROM numberplanusers WHERE planid = n.id)
                        OR EXISTS (
                            SELECT 1 FROM numberplanusers u WHERE planid = n.id AND u.userid = ' . Auth::GetCurrentUser() . '
                        ))')
                    . (empty($params['userid']) ? '' : ' AND EXISTS (SELECT 1 FROM numberplanusers WHERE planid = n.id AND userid = ' . intval($params['userid']) . ')')
                    . (empty($params['divisionid']) ? '' : ' AND EXISTS (SELECT 1 FROM numberplanassignments WHERE planid = n.id AND divisionid = ' . intval($params['divisionid']) . ')')
                    . (empty($params['type']) ? '' : ' AND n.doctype = ' . intval($params['type']))
                    . (isset($params['customertype']) ? ' AND n.customertype = ' . intval($params['customertype']) : '')
                )
            );
        }

        if ($list = $this->db->GetAllByKey(
            'SELECT n.id, n.template, n.period, n.doctype, n.isdefault, n.customertype, n.datefrom, n.dateto, n.refflag
            FROM numberplans n
            WHERE' . (ConfigHelper::checkPrivilege('superuser')
                ? ' 1 = 1'
                : ' (NOT EXISTS (SELECT 1 FROM numberplanassignments WHERE planid = n.id)
                    OR EXISTS (
                        SELECT 1 FROM numberplanassignments a
                        JOIN userdivisions ud ON ud.divisionid = a.divisionid
                        WHERE a.planid = n.id
                    )) AND (NOT EXISTS (SELECT 1 FROM numberplanusers WHERE planid = n.id)
                    OR EXISTS (
                        SELECT 1 FROM numberplanusers u WHERE planid = n.id AND u.userid = ' . Auth::GetCurrentUser() . '
                    ))')
            . (empty($params['userid']) ? '' : ' AND EXISTS (SELECT 1 FROM numberplanusers WHERE planid = n.id AND userid = ' . intval($params['userid']) . ')')
            . (empty($params['divisionid']) ? '' : ' AND EXISTS (SELECT 1 FROM numberplanassignments WHERE planid = n.id AND divisionid = ' . intval($params['divisionid']) . ')')
            . (empty($params['type']) ? '' : ' AND n.doctype = ' . intval($params['type']))
            . (isset($params['customertype']) ? ' AND n.customertype = ' . intval($params['customertype']) : '')
            . ' ORDER BY n.template'
            . (isset($params['limit']) ? ' LIMIT ' . intval($params['limit']) : '')
            . (isset($params['offset']) ? ' OFFSET ' . intval($params['offset']) : ''),
            'id'
        )) {
            $count = $this->db->GetAllByKey(
                'SELECT numberplanid AS id, COUNT(numberplanid) AS count
                FROM documents
                GROUP BY numberplanid',
                'id'
            );

            $max = $this->db->GetAllByKey(
                'SELECT numberplanid AS id, MAX(number) AS max
                FROM documents
                LEFT JOIN numberplans ON (numberplanid = numberplans.id)
                WHERE cdate >= (CASE period
                    WHEN ' . YEARLY . ' THEN ' . $yearstart . '
                    WHEN ' . QUARTERLY . ' THEN ' . $quarterstart . '
                    WHEN ' . MONTHLY . ' THEN ' . $monthstart . '
                    WHEN ' . WEEKLY . ' THEN ' . $weekstart . '
                    WHEN ' . DAILY . ' THEN ' . $daystart . ' ELSE 0 END)
                GROUP BY numberplanid',
                'id'
            );

            foreach ($list as &$item) {
                $item['next'] = isset($max[$item['id']]['max']) ? $max[$item['id']]['max']+1 : 1;
                $item['issued'] = $count[$item['id']]['count'] ?? 0;
            }
            unset($item);

            $divisions = $this->db->GetAll(
                'SELECT a.planid, d.id, (CASE WHEN d.label <> \'\' THEN d.label ELSE d.shortname END) AS shortname
                FROM numberplanassignments a
                JOIN divisions d ON d.id = a.divisionid
                ORDER BY a.planid'
            );

            if (!empty($divisions)) {
                foreach ($divisions as $division) {
                    $planid = $division['planid'];
                    if (isset($list[$planid])) {
                        $list[$planid]['divisions'][$division['id']] = $division;
                    }
                }
            }

            $users = $this->db->GetAll(
                'SELECT a.planid, u.id, u.rname, u.name, u.login
                FROM numberplanusers a
                JOIN vusers u ON u.id = a.userid
                ORDER BY a.planid'
            );

            if (!empty($users)) {
                foreach ($users as $user) {
                    $planid = $user['planid'];
                    if (isset($list[$planid])) {
                        $list[$planid]['users'][$user['id']] = $user;
                    }
                }
            }
        }

        return $list;
    }

    public function validateNumberPlan(array $numberplan)
    {
        $selecteddivisions = Utils::filterIntegers(empty($numberplan['divisions']) ? array() : $numberplan['divisions']);
        $selectedusers = Utils::filterIntegers(empty($numberplan['users']) ? array() : $numberplan['users']);

        $customertype = isset($numberplan['customertype']) && strlen($numberplan['customertype']) ? intval($numberplan['customertype']) : null;

        $datefrom = empty($numberplan['datefrom']) ? 0 : intval($numberplan['datefrom']);
        $dateto = empty($numberplan['dateto']) ? 0 : strtotime('tomorrow', intval($numberplan['dateto'])) - 1;

        if ($numberplan['doctype'] && !empty($numberplan['isdefault'])) {
            if (empty($datefrom)) {
                if (empty($dateto)) {
                    $date_interval_condition = '';
                } else {
                    $date_interval_condition = ' AND ((n.datefrom <= ' . $dateto . ' AND (n.dateto = 0 OR n.dateto >= ' . $dateto . '))';
                }
            } else {
                if (empty($dateto)) {
                    $date_interval_condition = ' AND ((n.datefrom <= ' . $datefrom . ' AND (n.dateto = 0 OR n.dateto >= ' . $datefrom . '))'
                        . ' OR (n.datefrom >= ' . $datefrom . '))';
                } else {
                    if ($datefrom > $dateto) {
                        return array(
                            'dateto' => trans('End date should be later than start date!'),
                        );
                    }
                    $date_interval_condition = ' AND ((n.datefrom <= ' . $datefrom . ' AND (n.dateto = 0 OR n.dateto >= ' . $datefrom . '))'
                        . ' OR (n.datefrom >= ' . $datefrom . ' AND (n.dateto = 0 OR n.dateto <= ' . $dateto . ')))';
                }
            }

            if (isset($customertype)) {
                $customer_type_condition = ' AND n.customertype IS NOT NULL AND n.customertype = ' . $customertype;
            } else {
                $customer_type_condition = '';
            }

            if (empty($selecteddivisions)) {
                if (empty($selectedusers)) {
                    if ($this->db->GetOne(
                        'SELECT 1 FROM numberplans n
                        WHERE doctype = ? AND isdefault = 1' . (empty($numberplan['id']) ? '' : ' AND n.id <> ' . intval($numberplan['id']))
                        . ' AND NOT EXISTS (SELECT 1 FROM numberplanassignments WHERE planid = n.id)
                        AND NOT EXISTS (SELECT 1 FROM numberplanusers WHERE planid = n.id)'
                        . $date_interval_condition
                        . $customer_type_condition,
                        array($numberplan['doctype'])
                    )) {
                        return array(
                            'doctype' => trans('Selected document type has already defined default plan!'),
                        );
                    }
                } else {
                    if ($this->db->GetOne(
                        'SELECT 1 FROM numberplans n
                        WHERE doctype = ? AND isdefault = 1' . (empty($numberplan['id']) ? '' : ' AND n.id <> ' . intval($numberplan['id']))
                        . ' AND NOT EXISTS (SELECT 1 FROM numberplanassignments WHERE planid = n.id)
                        AND NOT EXISTS (SELECT 1 FROM numberplanusers WHERE planid = n.in AND userid IN ?)'
                        . $date_interval_condition
                        . $customer_type_condition,
                        array($numberplan['doctype'], $selectedusers)
                    )) {
                        return array(
                            'doctype' => trans('Selected document type for some of selected users has already defined default plan!'),
                        );
                    }
                }
            } else {
                if (empty($selectedusers)) {
                    if ($this->db->GetOne(
                        'SELECT 1 FROM numberplans n
                        WHERE doctype = ? AND isdefault = 1' . (empty($numberplan['id']) ? '' : ' AND n.id <> ' . intval($numberplan['id']))
                        . ' AND EXISTS (
                            SELECT 1 FROM numberplanassignments WHERE planid = n.id AND divisionid IN ?
                        ) AND NOT EXISTS (
                            SELECT 1 FROM numberplanusers WHERE planid = n.id
                        )'
                        . $date_interval_condition
                        . $customer_type_condition,
                        array($numberplan['doctype'], $selecteddivisions)
                    )) {
                        return array(
                            'doctype' => trans('Selected document type for some of selected divisions has already defined default plan!'),
                        );
                    }
                } else {
                    if ($this->db->GetOne(
                        'SELECT 1 FROM numberplans n
                        WHERE doctype = ? AND isdefault = 1' . (empty($numberplan['id']) ? '' : ' AND n.id <> ' . intval($numberplan['id']))
                        . ' AND EXISTS (
                            SELECT 1 FROM numberplanassignments WHERE planid = n.id AND divisionid IN ?
                        ) AND EXISTS (
                            SELECT 1 FROM numberplanusers WHERE planid = n.id AND userid IN ?
                        )'
                        . $date_interval_condition
                        . $customer_type_condition,
                        array($numberplan['doctype'], $selecteddivisions, $selectedusers)
                    )) {
                        return array(
                            'doctype' => trans('Selected document type for some of selected divisions and users has already defined default plan!'),
                        );
                    }
                }
            }
        }

        if (!empty($selecteddivisions)) {
            $division_manager = new LMSDivisionManager($this->db, $this->auth, $this->cache, $this->syslog);
            $divisions = $division_manager->GetDivisions();
            if (empty($divisions)) {
                $divisions = array();
            }
            if (count(array_intersect(array_keys($divisions), $selecteddivisions)) != count($selecteddivisions)) {
                return array(
                    'divisions' => trans('Permission denied!'),
                );
            }
        }

        if (!empty($selectedusers)) {
            $user_manager = new LMSUserManager($this->db, $this->auth, $this->cache, $this->syslog);
            $users = $user_manager->GetUsers(array(
                'divisions' => empty($selecteddivisions) ? null : implode(',', $selecteddivisions),
            ));
            if (empty($users)) {
                $users = array();
            }
            if (count(array_diff($selectedusers, array_keys($users)))) {
                return array(
                    'users' => trans('Permission denied!'),
                );
            }
        }

        return array();
    }

    public function addNumberPlan(array $numberplan)
    {
        $this->db->BeginTrans();

        $args = array(
            'template' => $numberplan['template'],
            'doctype' => $numberplan['doctype'],
            'period' => $numberplan['period'],
            'datefrom' => empty($numberplan['datefrom']) ? 0 : $numberplan['datefrom'],
            'dateto' => empty($numberplan['dateto']) ? 0 : strtotime('tomorrow', $numberplan['dateto']) - 1,
            'customertype' => isset($numberplan['customertype']) && strlen($numberplan['customertype']) ? intval($numberplan['customertype']) : null,
            'isdefault' => isset($numberplan['isdefault']) ? 1 : 0,
            'refflag' => empty($numberplan['refflag']) ? 0 : 1,
        );
        $this->db->Execute(
            'INSERT INTO numberplans (template, doctype, period, datefrom, dateto, customertype, isdefault, refflag)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            array_values($args)
        );

        $id = $this->db->GetLastInsertID('numberplans');

        if ($id && $this->syslog) {
            $args[SYSLOG::RES_NUMPLAN] = $id;
            $this->syslog->AddMessage(SYSLOG::RES_NUMPLAN, SYSLOG::OPER_ADD, $args);
        }

        if (!empty($numberplan['divisions'])) {
            foreach ($numberplan['divisions'] as $divisionid) {
                $res = $this->db->Execute(
                    'INSERT INTO numberplanassignments (planid, divisionid)
                    VALUES (?, ?)',
                    array($id, $divisionid)
                );
                if ($res && $this->syslog) {
                    $args = array(
                        SYSLOG::RES_NUMPLANASSIGN => $this->db->GetLastInsertID('numberplanassignments'),
                        SYSLOG::RES_NUMPLAN => $id,
                        SYSLOG::RES_DIV => $divisionid
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_NUMPLANASSIGN, SYSLOG::OPER_ADD, $args);
                }
            }
        }

        if (!empty($numberplan['users'])) {
            foreach ($numberplan['users'] as $userid) {
                $res = $this->db->Execute(
                    'INSERT INTO numberplanusers (planid, userid)
                    VALUES (?, ?)',
                    array($id, $userid)
                );
                if ($res && $this->syslog) {
                    $args = array(
                        SYSLOG::RES_NUMPLAN => $id,
                        SYSLOG::RES_USER => $userid
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_NUMPLANUSER, SYSLOG::OPER_ADD, $args);
                }
            }
        }

        $this->db->CommitTrans();
    }

    public function updateNumberPlan(array $numberplan)
    {
        $this->db->BeginTrans();

        $args = array(
            'template' => $numberplan['template'],
            'doctype' => $numberplan['doctype'],
            'period' => $numberplan['period'],
            'datefrom' => empty($numberplan['datefrom']) ? 0 : $numberplan['datefrom'],
            'dateto' => empty($numberplan['dateto']) ? 0 : strtotime('tomorrow', $numberplan['dateto']) - 1,
            'customertype' => isset($numberplan['customertype']) && strlen($numberplan['customertype']) ? intval($numberplan['customertype']) : null,
            'isdefault' => $numberplan['isdefault'],
            'refflag' => empty($numberplan['refflag']) ? 0 : 1,
            SYSLOG::RES_NUMPLAN => $numberplan['id']
        );
        $res = $this->db->Execute(
            'UPDATE numberplans
            SET template = ?, doctype = ?, period = ?, datefrom = ?, dateto = ?, customertype = ?, isdefault = ?, refflag = ?
            WHERE id = ?',
            array_values($args)
        );
        if ($res && $this->syslog) {
            $this->syslog->AddMessage(SYSLOG::RES_NUMPLAN, SYSLOG::OPER_UPDATE, $args);
        }

        $old_divisions = $this->db->GetCol(
            'SELECT d.id
            FROM divisions d
            JOIN numberplanassignments a ON a.divisionid = d.id
            WHERE a.planid = ?',
            array($numberplan['id'])
        );
        if (empty($old_divisions)) {
            $old_divisions = array();
        }

        if (empty($numberplan['divisions'])) {
            $numberplan['divisions'] = array();
        }

        $divisions_to_add = array_diff($numberplan['divisions'], $old_divisions);
        $divisions_to_remove = array_diff($old_divisions, $numberplan['divisions']);

        if (!empty($divisions_to_add)) {
            foreach ($divisions_to_add as $divisionid) {
                $res = $this->db->Execute(
                    'INSERT INTO numberplanassignments (planid, divisionid) VALUES (?, ?)',
                    array($numberplan['id'], $divisionid)
                );

                if ($res && $this->syslog) {
                    $args = array(
                        SYSLOG::RES_NUMPLANASSIGN => $this->db->GetLastInsertID('numberplanassignments'),
                        SYSLOG::RES_NUMPLAN => $numberplan['id'],
                        SYSLOG::RES_DIV => $divisionid,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_NUMPLANASSIGN, SYSLOG::OPER_ADD, $args);
                }
            }
        }

        if (!empty($divisions_to_remove)) {
            foreach ($divisions_to_remove as $divisionid) {
                if ($this->syslog) {
                    $assignid = $this->db->GetOne(
                        'SELECT id FROM numberplanassignments WHERE planid = ? AND divisionid = ?',
                        array($numberplan['id'], $divisionid)
                    );
                }

                $res = $this->db->Execute(
                    'DELETE FROM numberplanassignments WHERE planid = ? AND divisionid = ?',
                    array($numberplan['id'], $divisionid)
                );

                if ($res && $assignid && $this->syslog) {
                    $args = array(
                        SYSLOG::RES_NUMPLANASSIGN => $assignid,
                        SYSLOG::RES_NUMPLAN => $numberplan['id'],
                        SYSLOG::RES_DIV => $divisionid,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_NUMPLANASSIGN, SYSLOG::OPER_DELETE, $args);
                }
            }
        }

        $old_users = $this->db->GetCol(
            'SELECT u.id
            FROM users u
            JOIN numberplanusers a ON a.userid = u.id
            WHERE a.planid = ?',
            array($numberplan['id'])
        );
        if (empty($old_users)) {
            $old_users = array();
        }

        if (empty($numberplan['users'])) {
            $numberplan['users'] = array();
        }

        $users_to_add = array_diff($numberplan['users'], $old_users);
        $users_to_remove = array_diff($old_users, $numberplan['users']);

        if (!empty($users_to_add)) {
            foreach ($users_to_add as $userid) {
                $res = $this->db->Execute(
                    'INSERT INTO numberplanusers (planid, userid) VALUES (?, ?)',
                    array($numberplan['id'], $userid)
                );

                if ($res && $this->syslog) {
                    $args = array(
                        SYSLOG::RES_NUMPLAN => $numberplan['id'],
                        SYSLOG::RES_USER => $userid,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_NUMPLANUSER, SYSLOG::OPER_ADD, $args);
                }
            }
        }

        if (!empty($users_to_remove)) {
            foreach ($users_to_remove as $userid) {
                $res = $this->db->Execute(
                    'DELETE FROM numberplanusers WHERE planid = ? AND userid = ?',
                    array($numberplan['id'], $userid)
                );

                if ($res && $this->syslog) {
                    $args = array(
                        SYSLOG::RES_NUMPLAN => $numberplan['id'],
                        SYSLOG::RES_USER => $userid,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_NUMPLANUSER, SYSLOG::OPER_DELETE, $args);
                }
            }
        }

        $this->db->CommitTrans();
    }

    public function deleteNumberPlan($id)
    {
        $this->db->BeginTrans();

        if ($this->syslog) {
            $args = array(SYSLOG::RES_NUMPLAN => $id);
            $this->syslog->AddMessage(SYSLOG::RES_NUMPLAN, SYSLOG::OPER_DELETE, $args);

            $assigns = $this->db->GetAll('SELECT * FROM numberplanassignments WHERE planid = ?', array($id));
            if (!empty($assigns)) {
                foreach ($assigns as $assign) {
                    $args = array(
                        SYSLOG::RES_NUMPLANASSIGN => $assign['id'],
                        SYSLOG::RES_NUMPLAN => $id,
                        SYSLOG::RES_DIV => $assign['divisionid'],
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_NUMPLANASSIGN, SYSLOG::OPER_DELETE, $args);
                }
            }
            $users = $this->db->GetAll('SELECT * FROM numberplanusers WHERE planid = ?', array($id));
            if (!empty($users)) {
                foreach ($users as $user) {
                    $args = array(
                        SYSLOG::RES_NUMPLAN => $id,
                        SYSLOG::RES_USER => $user['userid'],
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_NUMPLANUSER, SYSLOG::OPER_DELETE, $args);
                }
            }
        }

        $this->db->Execute('DELETE FROM numberplans WHERE id = ?', array($id));

        $this->db->CommitTrans();
    }

    /*
     \param array $properties - associative array with function parameters:
        doctype: document type
        planid: id of number plan
        cdate: document creation date
    */
    public function GetNewDocumentNumber($properties)
    {
        extract($properties);
        if (!isset($doctype)) {
            $doctype = null;
        }
        if (!isset($planid)) {
            $planid = null;
        }
        if (!isset($cdate)) {
            $cdate = null;
        }
        if (!isset($customerid)) {
            $customerid = null;
        }
        if (!isset($reference)) {
            $reference = null;
        }

        if ($planid) {
            $numplan = $this->db->GetRow('SELECT template, period FROM numberplans WHERE id=?', array($planid));
            $numtemplate = $numplan['template'];
            $period = $numplan['period'];
        } else {
            $planid = null;
        }

        $period = $period ?? YEARLY;
        $cdate = $cdate ?: time();

        switch ($period) {
            case DAILY:
                $start = mktime(0, 0, 0, date('n', $cdate), date('j', $cdate), date('Y', $cdate));
                $end = mktime(0, 0, 0, date('n', $cdate), date('j', $cdate) + 1, date('Y', $cdate));
                break;
            case WEEKLY:
                $weekstart = date('j', $cdate) - date('N', $cdate) + 1;
                $start = mktime(0, 0, 0, date('n', $cdate), $weekstart, date('Y', $cdate));
                $end = mktime(0, 0, 0, date('n', $cdate), $weekstart + 7, date('Y', $cdate));
                break;
            case MONTHLY:
                $start = mktime(0, 0, 0, date('n', $cdate), 1, date('Y', $cdate));
                $end = mktime(0, 0, 0, date('n', $cdate) + 1, 1, date('Y', $cdate));
                break;
            case QUARTERLY:
                switch (date('n')) {
                    case 1:
                    case 2:
                    case 3:
                        $startq = 1;
                        break;
                    case 4:
                    case 5:
                    case 6:
                        $startq = 4;
                        break;
                    case 7:
                    case 8:
                    case 9:
                        $startq = 7;
                        break;
                    case 10:
                    case 11:
                    case 12:
                        $startq = 10;
                        break;
                }
                $start = mktime(0, 0, 0, $startq, 1, date('Y', $cdate));
                $end = mktime(0, 0, 0, $startq + 3, 1, date('Y', $cdate));
                break;
            case HALFYEARLY:
                switch (date('n')) {
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                        $startq = 1;
                        break;
                    case 7:
                    case 8:
                    case 9:
                    case 10:
                    case 11:
                    case 12:
                        $startq = 7;
                        break;
                }
                $start = mktime(0, 0, 0, $starthy, 1, date('Y', $cdate));
                $end = mktime(0, 0, 0, $starthy + 6, 1, date('Y', $cdate));
                break;
            case YEARLY:
                $start = mktime(0, 0, 0, 1, 1, date('Y', $cdate));
                $end = mktime(0, 0, 0, 1, 1, date('Y', $cdate) + 1);
                break;
            case CONTINUOUS:
                $number = $this->db->GetOne(
                    'SELECT MAX(documents.number)
                    FROM documents
                    LEFT JOIN numberplans ON numberplans.id = documents.numberplanid
                    WHERE documents.type = ? AND '
                    . ($planid ? 'documents.numberplanid = ' . intval($planid) : 'documents.numberplanid IS NULL')
                    . ($reference
                        ? ' AND (numberplans.refflag = 0 OR numberplans.refflag IS NULL OR numberplans.refflag = 1 AND documents.reference = ' . intval($reference) . ')'
                        : ''
                    )
                    . (!isset($numtemplate) || !preg_match('/%[0-9]*C/', $numtemplate) || empty($customerid)
                        ? ''
                        : ' AND documents.customerid = ' . intval($customerid)
                    ),
                    array($doctype)
                );

                return $number ? ++$number : 1;
                break;
        }

        $number = $this->db->GetOne(
            'SELECT MAX(documents.number)
                FROM documents
                LEFT JOIN numberplans ON numberplans.id = documents.numberplanid
                WHERE documents.cdate >= ? AND documents.cdate < ? AND documents.type = ? AND '
                . ($planid ? 'documents.numberplanid = ' . intval($planid) : 'documents.numberplanid IS NULL')
                . ($reference
                    ? ' AND (numberplans.refflag = 0 OR numberplans.refflag IS NULL OR numberplans.refflag = 1 AND documents.reference = ' . intval($reference) . ')'
                    : ''
                )
                . (!isset($numtemplate) || !preg_match('/%[0-9]*C/', $numtemplate) || empty($customerid)
                    ? ''
                    : ' AND documents.customerid = ' . intval($customerid)
                ),
            array($start, $end, $doctype)
        );

        return $number ? ++$number : 1;
    }

    /*
     \param array $properties - associative array with function parameters:
        number: document number
        doctype: document type
        planid: id of number plan
        cdate: document creation date
    */
    public function DocumentExists($properties)
    {
        if (!is_array($properties)) {
            if (preg_match('/^[0-9]+$/', $properties)) {
                return $this->db->GetOne('SELECT 1 FROM documents WHERE id = ?', array($properties)) > 0;
            } else {
                return false;
            }
        }

        extract($properties);

        if (!isset($doctype)) {
            $doctype = null;
        }
        if (!isset($planid)) {
            $planid = null;
        }
        if (!isset($cdate)) {
            $cdate = null;
        }
        if (!isset($customerid)) {
            $customerid = null;
        }
        if (!isset($reference)) {
            $reference = null;
        }

        if ($planid) {
            $numplan = $this->db->GetRow('SELECT template, period FROM numberplans WHERE id=?', array($planid));
            $numtemplate = $numplan['template'];
            $period = $numplan['period'];
        } else {
            $planid = null;
        }

        $period = $period ?? YEARLY;
        $cdate = $cdate ?: time();

        switch ($period) {
            case DAILY:
                $start = mktime(0, 0, 0, date('n', $cdate), date('j', $cdate), date('Y', $cdate));
                $end = mktime(0, 0, 0, date('n', $cdate), date('j', $cdate) + 1, date('Y', $cdate));
                break;
            case WEEKLY:
                $weekstart = date('j', $cdate) - date('N', $cdate) + 1;
                $start = mktime(0, 0, 0, date('n', $cdate), $weekstart, date('Y', $cdate));
                $end = mktime(0, 0, 0, date('n', $cdate), $weekstart + 7, date('Y', $cdate));
                break;
            case MONTHLY:
                $start = mktime(0, 0, 0, date('n', $cdate), 1, date('Y', $cdate));
                $end = mktime(0, 0, 0, date('n', $cdate) + 1, 1, date('Y', $cdate));
                break;
            case QUARTERLY:
                switch (date('n')) {
                    case 1:
                    case 2:
                    case 3:
                        $startq = 1;
                        break;
                    case 4:
                    case 5:
                    case 6:
                        $startq = 4;
                        break;
                    case 7:
                    case 8:
                    case 9:
                        $startq = 7;
                        break;
                    case 10:
                    case 11:
                    case 12:
                        $startq = 10;
                        break;
                }
                $start = mktime(0, 0, 0, $startq, 1, date('Y', $cdate));
                $end = mktime(0, 0, 0, $startq + 3, 1, date('Y', $cdate));
                break;
            case HALFYEARLY:
                switch (date('n')) {
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                        $startq = 1;
                        break;
                    case 7:
                    case 8:
                    case 9:
                    case 10:
                    case 11:
                    case 12:
                        $startq = 7;
                        break;
                }
                $start = mktime(0, 0, 0, $startq, 1, date('Y', $cdate));
                $end = mktime(0, 0, 0, $startq + 6, 1, date('Y', $cdate));
                break;
            case YEARLY:
                $start = mktime(0, 0, 0, 1, 1, date('Y', $cdate));
                $end = mktime(0, 0, 0, 1, 1, date('Y', $cdate) + 1);
                break;
            case CONTINUOUS:
                return $this->db->GetOne(
                    'SELECT documents.id
                    FROM documents
                    LEFT JOIN numberplans ON numberplans.id = documents.numberplanid
                    WHERE documents.type = ? AND documents.number = ? AND '
                    . ($planid ? 'documents.numberplanid = ' . intval($planid) : 'documents.numberplanid IS NULL')
                    . ($reference
                        ? ' AND (numberplans.refflag = 0 OR numberplans.refflag IS NULL OR numberplans.refflag = 1 AND documents.reference = ' . intval($reference) . ')'
                        : ''
                    )
                    . (!isset($numtemplate) || !preg_match('/%[0-9]*C/', $numtemplate) || empty($customerid)
                        ? ''
                        : ' AND documents.customerid = ' . intval($customerid)
                    ),
                    array($doctype, $number)
                );
        }

        return $this->db->GetOne(
            'SELECT documents.id
            FROM documents
            LEFT JOIN numberplans ON numberplans.id = documents.numberplanid
            WHERE documents.cdate >= ? AND documents.cdate < ? AND documents.type = ? AND documents.number = ? AND '
            . ($planid ? 'documents.numberplanid = ' . intval($planid) : 'documents.numberplanid IS NULL')
            . ($reference
                ? ' AND (numberplans.refflag = 0 OR numberplans.refflag IS NULL OR numberplans.refflag = 1 AND documents.reference = ' . intval($reference) . ')'
                : ''
            )
            . (!isset($numtemplate) || !preg_match('/%[0-9]*C/', $numtemplate) || empty($customerid)
                ? ''
                : ' AND documents.customerid = ' . intval($customerid)
            ),
            array($start, $end, $doctype, $number)
        );
    }

    public function documentCommitParseNotificationMail($string, $data)
    {
        $customerinfo = $data['customerinfo'];
        $document = $data['document'];
        return str_replace(
            array(
                '%cid%',
                '%extid%',
                '%customername%',
                '%docid%',
                '%document%',
                '%creatorname%',
                '%approvername%',
            ),
            array(
                $customerinfo['id'],
                $customerinfo['login'],
                $customerinfo['customername'],
                $document['id'],
                $document['fullnumber'],
                $document['creatorname'],
                $document['approvername'],
            ),
            $string
        );
    }

    public function documentCommitParseNotificationRecipient($string, $data)
    {
        return str_replace(
            array(
                '%creatoremail%',
            ),
            array(
                strlen($data['creatoremail']) ? $data['creatoremail'] : '',
            ),
            $string
        );
    }

    public function CommitDocuments(array $ids, $userpanel = false, $check_close_flag = true)
    {
        global $DOCTYPE_ALIASES;

        $userid = Auth::GetCurrentUser();

        $ids = Utils::filterIntegers($ids);
        if (empty($ids)) {
            return;
        }

        $docs = $this->db->GetAllByKey(
            'SELECT d.id, d.customerid, d.fullnumber,
                dc.fromdate AS datefrom,
                dc.todate AS dateto,
                d.type AS doctype,
                d.reference, d.commitflags, d.confirmdate, d.closed,
                (CASE WHEN (u.ntype & ?) > 0 AND u.email <> ? THEN u.email ELSE ? END) AS creatoremail,
                u.name AS creatorname,
                (CASE WHEN d.confirmdate = -1 AND a.customerdocuments IS NOT NULL THEN 1 ELSE 0 END) AS customerawaits,
                (CASE WHEN d.confirmdate > 0 AND d.confirmdate > ?NOW? THEN 1 ELSE 0 END) AS operatorawaits,
                dc.dynamicperiod,
                dc.attributes,
                n.nodeids,
                vn.numberids
            FROM documents d
            JOIN documentcontents dc ON dc.docid = d.id
            LEFT JOIN docrights r ON r.doctype = d.type
            LEFT JOIN (
                SELECT da.docid, COUNT(*) AS customerdocuments
                FROM documentattachments da
                WHERE da.type = -1
                GROUP BY da.docid
            ) a ON a.docid = d.id
            LEFT JOIN (
                SELECT
                    nodes.ownerid AS customerid,
                    ' . $this->db->GroupConcat('nodes.id') . ' AS nodeids
                FROM nodes
                GROUP BY nodes.ownerid
            ) n ON n.customerid = d.customerid
            LEFT JOIN (
                SELECT
                    voipaccounts.ownerid AS customerid,
                    ' . $this->db->GroupConcat('voip_numbers.id') . ' AS numberids
                FROM voip_numbers
                JOIN voipaccounts ON voipaccounts.id = voip_numbers.voip_account_id
                GROUP BY voipaccounts.ownerid
            ) vn ON vn.customerid = d.customerid
            LEFT JOIN vusers u ON u.id = d.userid
            WHERE ' . ($check_close_flag ? 'd.closed = ' . DOC_OPEN : '1 = 1')
                . ' AND d.type < 0 AND d.id IN (' . implode(',', $ids) . ')' . ($userid ? ' AND r.userid = ' . intval($userid) . ' AND (r.rights & ' . DOCRIGHT_CONFIRM . ') > 0' : ''),
            'id',
            array(
                MSG_MAIL,
                '',
                '',
            )
        );
        if (empty($docs)) {
            return;
        }

        $userpanel_enabled_modules = ConfigHelper::getConfig('userpanel.enabled_modules');
        $userpanel = $userpanel && (empty($userpanel_enabled_modules) || strpos($userpanel_enabled_modules, 'documents') !== false);

        $finance_manager = new LMSFinanceManager($this->db, $this->auth, $this->cache, $this->syslog);

        $this->db->BeginTrans();

        $doctype_aliases = array_flip($DOCTYPE_ALIASES);

        if ($userpanel) {
            $mail_dsn = ConfigHelper::getConfig('userpanel.document_notification_mail_dsn_address', '', true);
            $mail_mdn = ConfigHelper::getConfig('userpanel.document_notification_mail_mdn_address', '', true);
            $mail_sender_name = ConfigHelper::getConfig('userpanel.document_notification_mail_sender_name', '', true);
            $mail_sender_address = ConfigHelper::getConfig('userpanel.document_notification_mail_sender_address', ConfigHelper::getConfig('mail.smtp_username'));
            $mail_reply_address = ConfigHelper::getConfig('userpanel.document_notification_mail_reply_address', '', true);
            $operator_document_types = '';
            $operator_mail_recipient = ConfigHelper::getConfig('userpanel.document_approval_operator_notification_mail_recipient', '');
            $operator_mail_format = ConfigHelper::getConfig('userpanel.document_approval_operator_notification_mail_format', 'text');
            $operator_mail_subject = ConfigHelper::getConfig('userpanel.document_approval_operator_notification_mail_subject');
            $operator_mail_body = ConfigHelper::getConfig('userpanel.document_approval_operator_notification_mail_body');
            $customer_document_types = '';
            $customer_mail_format = ConfigHelper::getConfig('userpanel.document_approval_customer_notification_mail_format', 'text');
            $customer_mail_subject = ConfigHelper::getConfig('userpanel.document_approval_customer_notification_mail_subject');
            $customer_mail_body = ConfigHelper::getConfig('userpanel.document_approval_customer_notification_mail_body');
            $customer_mail_attachments = ConfigHelper::checkConfig('userpanel.document_approval_customer_notification_attachments');
        } else {
            $mail_dsn = ConfigHelper::getConfig('documents.notification_mail_dsn_address', '', true);
            $mail_mdn = ConfigHelper::getConfig('documents.notification_mail_mdn_address', '', true);
            $mail_sender_name = ConfigHelper::getConfig('documents.notification_mail_sender_name', '', true);
            $mail_sender_address = ConfigHelper::getConfig('documents.notification_mail_sender_address', ConfigHelper::getConfig('mail.smtp_username'));
            $mail_reply_address = ConfigHelper::getConfig('documents.notification_mail_reply_address', '', true);
            $operator_document_types = ConfigHelper::getConfig('documents.approval_operator_notification_document_types', '', true);
            $operator_mail_recipient = ConfigHelper::getConfig('documents.approval_operator_notification_mail_recipient', '');
            $operator_mail_format = ConfigHelper::getConfig('documents.approval_operator_notification_mail_format', 'text');
            $operator_mail_subject = ConfigHelper::getConfig('documents.approval_operator_notification_mail_subject');
            $operator_mail_body = ConfigHelper::getConfig('documents.approval_operator_notification_mail_body');
            $customer_document_types = ConfigHelper::getConfig('documents.approval_customer_notification_document_types', '', true);
            $customer_mail_format = ConfigHelper::getConfig('documents.approval_customer_notification_mail_format', 'text');
            $customer_mail_subject = ConfigHelper::getConfig('documents.approval_customer_notification_mail_subject');
            $customer_mail_body = ConfigHelper::getConfig('documents.approval_customer_notification_mail_body');
            $customer_mail_attachments = ConfigHelper::checkConfig('documents.approval_customer_notification_attachments');
        }

        if (strlen($operator_document_types)) {
            $doc_types = preg_split(
                '/\s*[,;]\s*/',
                preg_replace('/\s+/', ',', $operator_document_types),
                -1,
                PREG_SPLIT_NO_EMPTY
            );
            $operator_document_types = array_flip(
                array_map(
                    function ($doctype) use ($doctype_aliases) {
                        return $doctype_aliases[$doctype];
                    },
                    array_filter($doc_types, function ($doctype) use ($doctype_aliases) {
                        return isset($doctype_aliases[$doctype]);
                    })
                )
            );
        } else {
            $operator_document_types = $DOCTYPE_ALIASES;
        }

        if (strlen($customer_document_types)) {
            $doc_types = preg_split(
                '/\s*[,;]\s*/',
                preg_replace('/\s+/', ',', $customer_document_types),
                -1,
                PREG_SPLIT_NO_EMPTY
            );
            $customer_document_types = array_flip(
                array_map(
                    function ($doctype) use ($doctype_aliases) {
                        return $doctype_aliases[$doctype];
                    },
                    array_filter($doc_types, function ($doctype) use ($doctype_aliases) {
                        return isset($doctype_aliases[$doctype]);
                    })
                )
            );
        } else {
            $customer_document_types = $DOCTYPE_ALIASES;
        }

        $customerinfos = array();
        $mail_contacts = array();

        $errors = array();
        $info = array();

        if ($userpanel) {
            $approvername = '-';
        } else {
            $user_manager = new LMSUserManager($this->db, $this->auth, $this->cache, $this->syslog);
            $approvername = $user_manager->GetUserName();
        }

        foreach ($docs as $docid => $doc) {
            $this->db->Execute(
                'UPDATE documents SET sdate = ?NOW?, cuserid = ?, closed = ?, confirmdate = ?,
                adate = ?, auserid = ? WHERE id = ?',
                array(
                    $userid,
                    empty($doc['customerawaits']) ? ($userpanel ? DOC_CLOSED_AFTER_CUSTOMER_SMS : DOC_CLOSED) : DOC_CLOSED_AFTER_CUSTOMER_SCAN,
                    $doc['customerawaits'] ? 0 : $doc['confirmdate'],
                    0,
                    null,
                    $docid
                )
            );

            $args = array(
                'reference' => $doc['reference'],
                'datefrom' => $doc['datefrom'],
                'customerid' => $doc['customerid'],
                'existing_assignments' => array(
                    'operation' => $doc['commitflags'] & 15,
                    'reference_document_limit' => $doc['commitflags'] & 16 ? 1 : null,
                ),
            );
            $finance_manager->UpdateExistingAssignments($args);

            $this->db->Execute(
                'UPDATE assignments SET commited = 1 WHERE docid = ? AND commited = 0',
                array($docid)
            );

            if (isset($doc['attributes']) && strlen($doc['attributes'])) {
                $selected_assignment = unserialize($doc['attributes']);

                $nodeids = isset($doc['nodeids']) ? explode(',', $doc['nodeids']) : array();
                $numberids = isset($doc['numberids']) ? explode(',', $doc['numberids']) : array();

                foreach ($selected_assignment['snodes'] as $snodeids) {
                    $snodeids = array_intersect($snodeids, $nodeids);
                }
                unset($snodeids);

                foreach ($selected_assignment['sphones'] as &$snumberids) {
                    $snumberids = array_intersect($snumberids, $numberids);
                }
                unset($snumberids);

                if (isset($selected_assignment['consents'])) {
                    if (!isset($customer_manager)) {
                        $customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);
                    }

                    $customer_manager->updateCustomerConsents(
                        $doc['customerid'],
                        array_keys($customer_manager->getCustomerConsents($doc['customerid'])),
                        array_keys($selected_assignment['consents']),
                        empty($selected_assignment['supported-customer-consents']) ? null : $selected_assignment['supported-customer-consents']
                    );
                }

                if (!empty($doc['dynamicperiod'])) {
                    $finance_manager->addAssignmentsForSchema($selected_assignment);

                    $datefrom = $doc['datefrom'];
                    $dateto = $doc['dateto'];

                    $diff_days = intval(round((strtotime('today') - $datefrom) / 86400));

                    $datefrom = empty($datefrom) ? 0 : strtotime($diff_days . ' days', $datefrom);
                    if ($dateto) {
                        if (!empty($selected_assignment['align-periods'])) {
                            $monthfrom = date('Y', $doc['datefrom']) * 12 + date('n', $doc['datefrom']);
                            $new_monthfrom = date('Y', $datefrom) * 12 + date('n', $datefrom);

                            if ($new_monthfrom > $monthfrom) {
                                $dateto = mktime(0, 0, 0, date('n', $dateto) + ($new_monthfrom - $monthfrom) + 1, 1, date('Y', $dateto)) - 1;
                            }
                        } else {
                            $dateto = strtotime($diff_days . ' days', $dateto);
                        }
                    }

                    $this->db->Execute(
                        'UPDATE documentcontents SET fromdate = ?, todate = ? WHERE docid = ?',
                        array(
                            $datefrom,
                            $dateto,
                            $docid,
                        )
                    );
                }
            }

            if ($userpanel && empty($doc['customerawaits']) && empty($doc['operatorawaits'])) {
                continue;
            }

            if (!empty($mail_sender_address)) {
                // notify operator about document confirmation
                if (!empty($operator_mail_recipient) && !empty($operator_mail_subject) && !empty($operator_mail_body)
                    && isset($operator_document_types[$doc['doctype']])) {
                    if (!isset($customer_manager)) {
                        $customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);
                    }

                    if (!isset($customerinfos[$doc['customerid']])) {
                        $customerinfos[$doc['customerid']] = $customer_manager->GetCustomer($doc['customerid']);
                    }
                    $customerinfo = $customerinfos[$doc['customerid']];

                    $operator_mail_subject = $this->documentCommitParseNotificationMail(
                        $operator_mail_subject,
                        array(
                            'customerinfo' => $customerinfo,
                            'document' => array(
                                'id' => $docid,
                                'fullnumber' => $doc['fullnumber'],
                                'creatorname' => $doc['creatorname'],
                                'approvername' => $approvername,
                            ),
                        )
                    );
                    $operator_mail_body = $this->documentCommitParseNotificationMail(
                        $operator_mail_body,
                        array(
                            'customerinfo' => $customerinfo,
                            'document' => array(
                                'id' => $docid,
                                'fullnumber' => $doc['fullnumber'],
                                'creatorname' => $doc['creatorname'],
                                'approvername' => $approvername,
                            ),
                        )
                    );

                    $sender = ($mail_sender_name ? '"' . $mail_sender_name . '" ' : '') . '<' . $mail_sender_address . '>';
                    $headers = array(
                        'From' => $sender,
                        'Subject' => $operator_mail_subject,
                        'X-LMS-Format' => $operator_mail_format,
                    );
                    if (!isset($lms)) {
                        $lms = LMS::getInstance();
                    }

                    foreach (explode(',', $this->documentCommitParseNotificationRecipient($operator_mail_recipient, $doc)) as $recipient) {
                        if (check_email($recipient)) {
                            $headers['To'] = $recipient;
                            $lms->SendMail($recipient, $headers, $operator_mail_body);
                        }
                    }
                }

                // customer awaits for signed document scan approval
                // so we should probably notify him about document confirmation
                if (!empty($customer_mail_subject) && !empty($customer_mail_body) && isset($customer_document_types[$doc['doctype']])) {
                    if (!isset($customer_manager)) {
                        $customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);
                    }

                    if (!isset($customerinfos[$doc['customerid']])) {
                        $customerinfos[$doc['customerid']] = $customer_manager->GetCustomer($doc['customerid']);
                    }
                    if (!isset($mail_contacts[$doc['customerid']])) {
                        $mail_contacts[$doc['customerid']] = $customer_manager->GetCustomerContacts($doc['customerid'], CONTACT_EMAIL);
                    }
                    $customerinfo = $customerinfos[$doc['customerid']];
                    $mail_recipients = $mail_contacts[$doc['customerid']];

                    if ($customer_mail_attachments) {
                        $docs = $this->db->GetAll(
                            "SELECT
                                    d.id,
                                    d.type,
                                    d.customerid,
                                    d.name,
                                    m.email
                                FROM documents d
                                JOIN (
                                    SELECT customerid, " . $this->db->GroupConcat('contact') . " AS email
                                    FROM customercontacts
                                    WHERE (type & ?) = ?
                                    GROUP BY customerid
                                ) m ON m.customerid = d.customerid
                                WHERE d.id IN ?
                                ORDER BY d.id",
                            array(
                                CONTACT_EMAIL | CONTACT_DOCUMENTS | CONTACT_DISABLED,
                                CONTACT_EMAIL | CONTACT_DOCUMENTS,
                                $ids,
                            )
                        );

                        if (!empty($docs)) {
                            $currtime = time();
                            if (!isset($lms)) {
                                $lms = LMS::getInstance();
                            }
                            $result = $lms->SendDocuments(
                                $docs,
                                $userpanel ? 'userpanel' : 'frontend',
                                compact(
                                    'currtime'
                                )
                            );
                            if ($userpanel) {
                                $info = array_merge($info, $result['info']);
                                $errors = array_merge($errors, $result['errors']);
                                if (!empty($errors)) {
                                    return compact('info', 'errors');
                                }
                            }
                        }
                    }

                    $customer_mail_subject = $this->documentCommitParseNotificationMail(
                        $customer_mail_subject,
                        array(
                            'customerinfo' => $customerinfo,
                            'document' => array(
                                'id' => $docid,
                                'fullnumber' => $doc['fullnumber'],
                                'creatorname' => $doc['creatorname'],
                                'approvername' => $approvername,
                            ),
                        )
                    );
                    $customer_mail_body = $this->documentCommitParseNotificationMail(
                        $customer_mail_body,
                        array(
                            'customerinfo' => $customerinfo,
                            'document' => array(
                                'id' => $docid,
                                'fullnumber' => $doc['fullnumber'],
                                'creatorname' => $doc['creatorname'],
                                'approvername' => $approvername,
                            ),
                        )
                    );

                    if (!empty($mail_recipients)) {
                        $destinations = array();
                        foreach ($mail_recipients as $mail_recipient) {
                            if (($mail_recipient['type'] & (CONTACT_NOTIFICATIONS | CONTACT_DISABLED)) == CONTACT_NOTIFICATIONS) {
                                $destinations[] = $mail_recipient['contact'];
                            }
                        }
                        if (!empty($destinations)) {
                            $recipients = array(
                                array(
                                    'id' => $doc['customerid'],
                                    'email' => implode(',', $destinations),
                                )
                            );
                            $sender = ($mail_sender_name ? '"' . $mail_sender_name . '" ' : '') . '<' . $mail_sender_address . '>';
                            if (!isset($message_manager)) {
                                $message_manager = new LMSMessageManager($this->db, $this->auth, $this->cache, $this->syslog);
                            }
                            $message = $message_manager->addMessage(array(
                                'type' => MSG_MAIL,
                                'subject' => $customer_mail_subject,
                                'body' => $customer_mail_body,
                                'sender' => array(
                                    'name' => $mail_sender_name,
                                    'mail' => $mail_sender_address,
                                ),
                                'contenttype' => $customer_mail_format == 'text' ? 'text/plain' : 'text/html',
                                'recipients' => $recipients,
                            ));

                            $msgid = $message['id'];
                            $msgitems = $message['items'];

                            $headers = array(
                                'From' => $sender,
                                'Recipient-Name' => $customerinfo['customername'],
                                'Subject' => $customer_mail_subject,
                                'X-LMS-Format' => $customer_mail_format,
                            );
                            if (!empty($mail_reply_address) && $mail_reply_address != $mail_sender_address) {
                                $headers['Reply-To'] = $mail_reply_address;
                            }
                            if (!empty($mail_mdn)) {
                                $headers['Return-Receipt-To'] = $mail_mdn;
                                $headers['Disposition-Notification-To'] = $mail_mdn;
                            }
                            if (!empty($mail_dsn)) {
                                $headers['Delivery-Status-Notification-To'] = true;
                            }
                            foreach ($destinations as $destination) {
                                if (!empty($mail_dsn) || !empty($mail_mdn)) {
                                    $headers['X-LMS-Message-Item-Id'] = $message['items'][$doc['customerid']][$destination];
                                    $headers['Message-ID'] = '<messageitem-' . $message['items'][$doc['customerid']][$destination] . '@rtsystem.' . gethostname() . '>';
                                }

                                if (!isset($lms)) {
                                    $lms = LMS::getInstance();
                                }

                                $res = $lms->SendMail($destination, $headers, $customer_mail_body);

                                if (is_int($res)) {
                                    $status = $res;
                                    $send_errors = array();
                                } elseif (is_string($res)) {
                                    $status = MSG_ERROR;
                                    $send_errors = array($res);
                                } else {
                                    $status = $res['status'];
                                    $send_errors = $res['errors'] ?? array();
                                }

                                if ($status == MSG_SENT || isset($res['id']) || !empty($send_errors)) {
                                    $this->db->Execute(
                                        'UPDATE messageitems SET status = ?, lastdate = ?NOW?,
                                            error = ?, externalmsgid = ?
                                        WHERE messageid = ?
                                            AND customerid = ?
                                            AND destination = ?',
                                        array(
                                            $status,
                                            empty($send_errors) ? null : implode(', ', $send_errors),
                                            !is_array($res) || empty($res['id']) ? null : $res['id'],
                                            $msgid,
                                            $doc['customerid'],
                                            $destination,
                                        )
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }

        $this->db->CommitTrans();

        if ($userpanel) {
            return compact('info', 'errors');
        }
    }

    public function newDocumentParseNotification($string, $data)
    {
        $customerinfo = $data['customerinfo'];
        $string = str_replace(
            array(
                '%cid%',
                '%extid%',
                '%pin%',
                '%customername%',
            ),
            array(
                $customerinfo['id'],
                $customerinfo['login'],
                $customerinfo['pin'],
                $customerinfo['customername'],
            ),
            $string
        );

        $document = $data['document'];
        $string = str_replace(
            array(
                '%docid%',
                '%document%',
                '%date-y%',
                '%date-m%',
                '%date-d%',
            ),
            array(
                $document['id'],
                $document['fullnumber'],
                date('Y', $document['confirmdate']),
                date('m', $document['confirmdate']),
                date('d', $document['confirmdate']),
            ),
            $string
        );

        return $string;
    }

    public function NewDocumentCustomerNotifications(array $document)
    {
        global $LMS;

        if (!$LMS->checkCustomerConsent($document['customerid'], CCONSENT_USERPANEL_SCAN)
            && !$LMS->checkCustomerConsent($document['customerid'], CCONSENT_USERPANEL_SMS)) {
            return;
        }

        $mail_dsn = ConfigHelper::getConfig('userpanel.document_notification_mail_dsn_address', '', true);
        $mail_mdn = ConfigHelper::getConfig('userpanel.document_notification_mail_mdn_address', '', true);
        $mail_sender_name = ConfigHelper::getConfig('userpanel.document_notification_mail_sender_name', '', true);
        $mail_sender_address = ConfigHelper::getConfig('userpanel.document_notification_mail_sender_address', ConfigHelper::getConfig('mail.smtp_username'));
        $mail_reply_address = ConfigHelper::getConfig('userpanel.document_notification_mail_reply_address', '', true);

        $new_document_mail_subject = ConfigHelper::getConfig('userpanel.new_document_customer_notification_mail_subject', '', true);
        $new_document_mail_body = ConfigHelper::getConfig('userpanel.new_document_customer_notification_mail_body', '', true);
        $new_document_mail_format = ConfigHelper::getConfig('userpanel.new_document_customer_notification_mail_format', '', true);

        if (!empty($mail_sender_address) && !empty($new_document_mail_subject) && !empty($new_document_mail_body)) {
            $customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);
            $message_manager = new LMSMessageManager($this->db, $this->auth, $this->cache, $this->syslog);

            $customerinfo = $customer_manager->GetCustomer($document['customerid']);
            $mail_recipients = $customer_manager->GetCustomerContacts($document['customerid'], CONTACT_EMAIL);

            if (!empty($mail_recipients)) {
                $destinations = array();
                foreach ($mail_recipients as $mail_recipient) {
                    if (($mail_recipient['type'] & (CONTACT_NOTIFICATIONS | CONTACT_DISABLED)) == CONTACT_NOTIFICATIONS) {
                        $destinations[] = $mail_recipient['contact'];
                    }
                }
                if (!empty($destinations)) {
                    $mail_subject = $this->newDocumentParseNotification(
                        $new_document_mail_subject,
                        array(
                            'customerinfo' => $customerinfo,
                            'document' => $document,
                        )
                    );
                    $mail_body = $this->newDocumentParseNotification(
                        $new_document_mail_body,
                        array(
                            'customerinfo' => $customerinfo,
                            'document' => $document,
                        )
                    );

                    $recipients = array(
                        array(
                            'id' => $document['customerid'],
                            'email' => implode(',', $destinations),
                        )
                    );
                    $message = $message_manager->addMessage(array(
                        'type' => MSG_MAIL,
                        'subject' => $mail_subject,
                        'body' => $mail_body,
                        'sender' => array(
                            'name' => $mail_sender_name,
                            'mail' => $mail_sender_address,
                        ),
                        'contenttype' => $new_document_mail_format == 'text' ? 'text/plain' : 'text/html',
                        'recipients' => $recipients,
                    ));
                    $msgid = $message['id'];
                    $msgitems = $message['items'];

                    $sender = ($mail_sender_name ? '"' . $mail_sender_name . '" ' : '') . '<' . $mail_sender_address . '>';
                    $headers = array(
                        'From' => $sender,
                        'Recipient-Name' => $customerinfo['customername'],
                        'Subject' => $mail_subject,
                        'X-LMS-Format' => $new_document_mail_format,
                    );
                    if (!empty($mail_reply_address) && $mail_reply_address != $mail_sender_address) {
                        $headers['Reply-To'] = $mail_reply_address;
                    }
                    if (!empty($mail_mdn)) {
                        $headers['Return-Receipt-To'] = $mail_mdn;
                        $headers['Disposition-Notification-To'] = $mail_mdn;
                    }
                    if (!empty($mail_dsn)) {
                        $headers['Delivery-Status-Notification-To'] = true;
                    }
                    foreach ($destinations as $destination) {
                        if (!empty($mail_dsn) || !empty($mail_mdn)) {
                            $headers['X-LMS-Message-Item-Id'] = $msgitems[$document['customerid']][$destination];
                            $headers['Message-ID'] = '<messageitem-' . $msgitems[$document['customerid']][$destination] . '@rtsystem.' . gethostname() . '>';
                        }

                        $res = $LMS->SendMail($destination, $headers, $mail_body);

                        if (is_int($res)) {
                            $status = $res;
                            $send_errors = array();
                        } elseif (is_string($res)) {
                            $status = MSG_ERROR;
                            $send_errors = array($res);
                        } else {
                            $status = $res['status'];
                            $send_errors = $res['errors'] ?? array();
                        }

                        if ($status == MSG_SENT || isset($res['id']) || !empty($send_errors)) {
                            $this->db->Execute(
                                'UPDATE messageitems SET status = ?, lastdate = ?NOW?,
                                    error = ?, externalmsgid = ?
                                WHERE messageid = ?
                                    AND customerid = ?
                                    AND destination = ?',
                                array(
                                    $status,
                                    empty($send_errors) ? null : implode(', ', $send_errors),
                                    !is_array($res) || empty($res['id']) ? null : $res['id'],
                                    $msgid,
                                    $document['customerid'],
                                    $destination,
                                )
                            );
                        }
                    }
                }
            }
        }

        $new_document_sms_body = ConfigHelper::getConfig('userpanel.new_document_customer_notification_sms_body', '', true);

        if (!empty($new_document_sms_body)) {
            $sms_options = $LMS->getCustomerSMSOptions();
            $sms_active = !empty($sms_options) && !empty($sms_options['service']);
            if (!$sms_active) {
                $sms_service = ConfigHelper::getConfig('sms.service', '', true);
                $sms_active = !empty($sms_service);
            }

            if ($sms_active) {
                if (!isset($customer_manager)) {
                    $customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);
                    $message_manager = new LMSMessageManager($this->db, $this->auth, $this->cache, $this->syslog);

                    $customerinfo = $customer_manager->GetCustomer($document['customerid']);
                }

                $phone_recipients = $customer_manager->GetCustomerContacts($document['customerid'], CONTACT_MOBILE);
                if (!empty($phone_recipients)) {
                    $destinations = array();
                    foreach ($phone_recipients as $phone_recipient) {
                        if (($phone_recipient['type'] & (CONTACT_NOTIFICATIONS | CONTACT_DISABLED)) == CONTACT_NOTIFICATIONS) {
                            $destinations[] = $phone_recipient['contact'];
                        }
                    }
                }

                if (!empty($destinations)) {
                    $sms_body = $this->newDocumentParseNotification(
                        $new_document_sms_body,
                        array(
                            'customerinfo' => $customerinfo,
                            'document' => $document,
                        )
                    );

                    $recipients = array(
                        array(
                            'id' => $document['customerid'],
                            'phone' => implode(',', $destinations),
                        )
                    );

                    $message = $message_manager->addMessage(array(
                        'type' => MSG_SMS,
                        'subject' => trans('new document customer notification'),
                        'body' => $sms_body,
                        'recipients' => $recipients,
                    ));
                    $msgid = $message['id'];
                    $msgitems = $message['items'];

                    $error = array();
                    foreach ($destinations as $destination) {
                        $res = $LMS->SendSMS($destination, $sms_body, $msgitems[$document['customerid']][$destination], $sms_options);

                        if (is_int($res)) {
                            $status = $res;
                            $send_errors = array();
                        } elseif (is_string($res)) {
                            $status = MSG_ERROR;
                            $send_errors = array($res);
                        } else {
                            $status = $res['status'];
                            $send_errors = $res['errors'] ?? array();
                        }

                        if ($status == MSG_ERROR) {
                            $error[] = array_merge($error, $res['errors']);
                        }

                        if ($status == MSG_SENT || isset($res['id']) || !empty($send_errors)) {
                            $this->db->Execute(
                                'UPDATE messageitems SET status = ?, lastdate = ?NOW?,
                                    error = ?, externalmsgid = ?
                                WHERE messageid = ?
                                    AND customerid = ?
                                    AND destination = ?',
                                array(
                                    $status,
                                    empty($send_errors) ? null : implode(', ', $send_errors),
                                    !is_array($res) || empty($res['id']) ? null : $res['id'],
                                    $msgid,
                                    $document['customerid'],
                                    $destination,
                                )
                            );
                        }
                    }
                }
            }
        }
    }

    public function ArchiveDocuments(array $ids)
    {
        $userid = Auth::GetCurrentUser();

        $ids = Utils::filterIntegers($ids);
        if (empty($ids)) {
            return;
        }

        $allowUnapprovedDocumentArchiving = ConfigHelper::checkConfig('documents.allow_unapproved_document_archiving');

        $docs = $this->db->GetCol(
            'SELECT d.id
            FROM documents d
            ' . ($userid ? ' JOIN docrights r ON r.doctype = d.type' : '') . '
            WHERE ' . $allowUnapprovedDocumentArchiving ? '1 = 1' : 'd.closed > ' . DOC_OPEN . '
                AND d.archived = 0
                AND d.id IN (' . implode(',', $ids) . ')
                ' . ($userid ? ' AND r.userid = ' . $userid . ' AND (r.rights & ' . DOCRIGHT_ARCHIVE . ') > 0' : '')
        );
        if (empty($docs)) {
            return;
        }

        $this->db->BeginTrans();

        $this->db->Execute(
            'UPDATE documents
            SET archived = 1, adate = ?NOW?, auserid = ?
            WHERE id IN (' . implode(',', $docs) . ')',
            array($userid)
        );

        $this->db->CommitTrans();
    }

    public function UpdateDocumentPostAddress($docid, $customerid)
    {
        $location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);

        $post_addr = $location_manager->GetCustomerAddress($customerid, POSTAL_ADDRESS);

        if (empty($post_addr)) {
            $post_addr = $location_manager->GetCustomerAddress($customerid);
        }

        $old_post_addr = $this->db->GetOne('SELECT post_address_id FROM documents WHERE id = ?', array($docid));
        if ($old_post_addr) {
            $address = $location_manager->GetAddress($post_addr);
            $address['address_id'] = $old_post_addr;
            $location_manager->SetAddress($address);
        } else {
            $this->db->Execute(
                'UPDATE documents SET post_address_id = ? WHERE id = ?',
                array($location_manager->CopyAddress($post_addr), $docid)
            );
        }
    }

    public function DeleteDocumentAddresses($docid)
    {
        // deletes addresses' records which are bound to given document
        $addresses = $this->db->GetRow(
            'SELECT recipient_address_id, post_address_id FROM documents WHERE id = ?',
            array($docid)
        );
        foreach ($addresses as $address_id) {
            if (!empty($address_id)) {
                $this->db->Execute('DELETE FROM addresses WHERE id = ?', array($address_id));
            }
        }
    }

    public function isArchiveDocument($id)
    {
        return $this->db->GetOne('SELECT archived FROM documents WHERE id  = ?', array($id));
    }

    public function AddArchiveDocument($docid, $file)
    {
        $error = null;
        $file_manager = new LMSFileManager($this->db, $this->auth, $this->cache, $this->syslog);

        $file['md5sum'] = md5($file['data']);
        $file['path'] = DOC_DIR . DIRECTORY_SEPARATOR . substr($file['md5sum'], 0, 2);
        $file['newfile'] = $file['path'] . DIRECTORY_SEPARATOR . $file['md5sum'];

        // If we have a file with specified md5sum, we assume
        // it's here because of some error. We can replace it with
        // the new document file
        // why? document attachment can be shared between different documents.
        // we should rather use the other message digest in such case!
        if (($this->DocumentAttachmentExists($file['md5sum'])
                || $file_manager->FileExists($file['md5sum']))
            && (filesize($file['newfile']) != strlen($file['data'])
                || hash_file('sha256', $file['newfile']) != hash('sha256', $file['data']))) {
            $error = trans('Specified file exists in database!');
        }

        if (empty($error)) {
            @mkdir($file['path'], 0700);
            $fh = fopen($file['newfile'], 'w');
            if (!empty($fh)) {
                fwrite($fh, $file['data']);
                fclose($fh);
            } else {
                $error = trans('Cannot write new archived document!');
            }
        }

        if (empty($error)
            && !$this->db->Execute(
                'INSERT INTO documentattachments (docid, filename, contenttype, md5sum, type, cdate)
				VALUES (?, ?, ?, ?, ?, ?NOW?)',
                array($docid, $file['filename'], $file['content-type'], $file['md5sum'], 1)
            )) {
            $error = trans('Cannot create database record for archived document!');
        }

        return $error;
    }

    public function GetArchiveDocument($docid)
    {
        $document = $this->db->GetRow('SELECT d.type AS doctype, filename, contenttype, md5sum, a.cdate
			FROM documents d
			JOIN documentattachments a ON a.docid = d.id
			WHERE docid = ? AND a.type = ?', array($docid, 1));

        $filename = DOC_DIR . DIRECTORY_SEPARATOR . substr($document['md5sum'], 0, 2)
            . DIRECTORY_SEPARATOR . $document['md5sum'];
        if (!file_exists($filename)) {
            return null;
        }

        $finance_manager = new LMSFinanceManager($this->db, $this->auth, $this->cache, $this->syslog);
        if ($document['doctype'] == DOC_CNOTE) {
            $data = $finance_manager->GetNoteContent($docid);
        } else {
            $data = $finance_manager->GetInvoiceContent($docid);
        }
        $data['type'] = trans('ORIGINAL');

        return array(
            'filename' => $document['filename'],
            'data' => file_get_contents($filename),
            'document' => $data,
            'content-type' => $document['contenttype'],
        );
    }

    public function AddDocumentFileAttachments(array $files)
    {
        $error = array();
        $file_manager = new LMSFileManager($this->db, $this->auth, $this->cache, $this->syslog);

        $stat = stat(DOC_DIR);

        foreach ($files as &$file) {
            $file['path'] = DOC_DIR . DIRECTORY_SEPARATOR . substr($file['md5sum'], 0, 2);
            $file['newfile'] = $file['path'] . DIRECTORY_SEPARATOR . $file['md5sum'];

            // If we have a file with specified md5sum, we assume
            // it's here because of some error. We can replace it with
            // the new document file
            // why? document attachment can be shared between different documents.
            // we should rather use the other message digest in such case!
            $filename = empty($file['tmpname']) ? $file['name'] : $file['tmpname'];
            if (($this->DocumentAttachmentExists($file['md5sum'])
                    || $file_manager->FileExists($file['md5sum']))
                && (filesize($file['newfile']) != filesize($filename)
                    || hash_file('sha256', $file['newfile']) != hash_file('sha256', $filename))) {
                $error['files'] = trans('Specified file exists in database!');
                break;
            }
        }
        unset($file);

        if (empty($error)) {
            foreach ($files as $file) {
                if (!is_dir($file['path']) && !file_exists($file['path'])) {
                    @mkdir($file['path'], 0700);
                }
                chown($file['path'], $stat['uid']);
                chgrp($file['path'], $stat['gid']);
                if (empty($file['tmpname'])) {
                    if (!@copy($file['name'], $file['newfile'])) {
                        $error['files'] = trans('Can\'t save file in "$a" directory!', $file['path']);
                        break;
                    }
                } elseif (!file_exists($file['newfile']) && !@rename($file['tmpname'], $file['newfile'])) {
                    $error['files'] = trans('Can\'t save file in "$a" directory!', $file['path']);
                    break;
                }
                if (file_exists($file['tmpname'])) {
                    @unlink($file['tmpname']);
                }
                chown($file['newfile'], $stat['uid']);
                chgrp($file['newfile'], $stat['gid']);
            }
        }

        return $error;
    }

    public function AddDocumentAttachments($documentid, array $files)
    {
        $attachmentids = array();

        foreach ($files as $file) {
            if (!$this->db->GetOne(
                'SELECT id FROM documentattachments WHERE docid = ? AND md5sum = ?',
                array($documentid, $file['md5sum'])
            )) {
                if ($this->db->Execute(
                    'INSERT INTO documentattachments (docid, filename, contenttype, md5sum, type, cdate)
					VALUES (?, ?, ?, ?, ?, ?NOW?)',
                    array(
                        $documentid,
                        $file['filename'],
                        $file['type'],
                        $file['md5sum'],
                        $file['attachmenttype'] ?? 0,
                    )
                )) {
                    $attachmentids[] = $this->db->GetLastInsertID('documentattachments');
                }
            }
        }

        return $attachmentids;
    }

    public function AddDocumentScans($documentid, array $files)
    {
        $this->db->Execute('UPDATE documents SET confirmdate = ? WHERE id = ?', array(-1, $documentid));

        return $this->AddDocumentAttachments($documentid, $files);
    }

    public function DocumentAttachmentExists($md5sum)
    {
        return $this->db->GetOne(
            'SELECT COUNT(docid) FROM documentattachments WHERE md5sum = ?',
            array($md5sum)
        );
    }

    private function prepareDocumentAuthCode($authcodeSources, $data)
    {
        $authcode = null;

        foreach ($authcodeSources as $source) {
            if (strpos($source, 'random') === 0) {
                if (!empty($data['phone']) && preg_match('/^random(?<length>\d*)$/', $source, $matches)) {
                    $min_value = pow(10, empty($matches['length']) ? 8 : $matches['length']);
                    $authcode = mt_rand($min_value, $min_value * 10 - 1);
                }
            } else {
                switch ($source) {
                    case 'ssn':
                        if (!empty($data['ssn'])) {
                            $authcode = $data['ssn'];
                            break 2;
                        }
                        break;
                    case 'ten':
                        if (!empty($data['ten'])) {
                            $authcode = $data['ten'];
                            break 2;
                        }
                        break;
                    case 'pin':
                        if (!empty($data['pin'])) {
                            $authcode = $data['pin'];
                            break 2;
                        }
                        break;
                }
            }
        }

        return $authcode;
    }

    public function GetDocumentFullContents($id, $with_reference_document = false)
    {
        global $DOCTYPES, $DOCTYPE_ALIASES;

        $userid = Auth::GetCurrentUser();

        if ($userid) {
            $document = $this->db->GetRow(
                'SELECT d.id, d.number, d.cdate, d.type,
                    d.customerid,
                    c.pin,
                    d.fullnumber, n.template, d.ssn, d.ten, d.name, d.reference,
                    d2.number AS ref_number,
                    d2.cdate AS ref_date,
                    d2.fullnumber AS ref_fullnumber,
                    d2.type AS ref_type,
                    n2.template AS ref_template,
                    dc.title AS content_title,
                    p.phone
                FROM documents d
                JOIN documentcontents dc ON dc.docid = d.id
                JOIN customers c ON c.id = d.customerid
                LEFT JOIN (
                    SELECT
                        customerid,
                        ' . $this->db->GroupConcat('contact') . ' AS phone
                    FROM customercontacts
                    WHERE (type & ?) = ?
                    GROUP BY customerid
                ) p ON p.customerid = c.id
                LEFT JOIN numberplans n ON (d.numberplanid = n.id)
                JOIN docrights r ON (r.doctype = d.type)
                LEFT JOIN documents d2 ON d2.id = d.reference
                LEFT JOIN numberplans n2 ON n2.id = d2.numberplanid
                WHERE d.id = ? AND r.userid = ? AND (r.rights & ?) > 0',
                array(
                    CONTACT_MOBILE | CONTACT_DOCUMENTS | CONTACT_DISABLED,
                    CONTACT_MOBILE | CONTACT_DOCUMENTS,
                    $id,
                    $userid,
                    DOCRIGHT_VIEW,
                )
            );
        } else {
            $document = $this->db->GetRow(
                'SELECT d.id, d.number, d.cdate, d.type,
                    d.customerid,
                    c.pin,
                    d.fullnumber, n.template, d.ssn, d.ten, d.name, d.reference,
                    d2.number AS ref_number,
                    d2.cdate AS ref_date,
                    d2.fullnumber AS ref_fullnumber,
                    d2.type AS ref_type,
                    n2.template AS ref_template,
                    dc.title AS content_title,
                    p.phone
                FROM documents d
                JOIN documentcontents dc ON dc.docid = d.id
                JOIN customers c ON c.id = d.customerid
                LEFT JOIN (
                    SELECT
                        customerid,
                        ' . $this->db->GroupConcat('contact') . ' AS phone
                    FROM customercontacts
                    WHERE (type & ?) = ?
                    GROUP BY customerid
                ) p ON p.customerid = c.id
                LEFT JOIN numberplans n ON (d.numberplanid = n.id)
                JOIN docrights r ON (r.doctype = d.type)
                LEFT JOIN documents d2 ON d2.id = d.reference
                LEFT JOIN numberplans n2 ON n2.id = d2.numberplanid
                WHERE d.id = ?',
                array(
                    CONTACT_MOBILE | CONTACT_DOCUMENTS | CONTACT_DISABLED,
                    CONTACT_MOBILE | CONTACT_DOCUMENTS,
                    $id,
                )
            );
        }

        if ($document) {
            $document['fullnumber'] = docnumber(array(
                'number' => $document['number'],
                'template' => $document['template'],
                'cdate' => $document['cdate'],
                'customerid' => $document['customerid'],
            ));

            if (!empty($document['reference'])) {
                $document['ref_fullnumber'] = docnumber(array(
                    'number' => $document['ref_number'],
                    'template' => $document['ref_template'],
                    'cdate' => $document['ref_cdate'],
                    'customerid' => $document['customerid'],
                ));
            }

            $document['title'] = trans(
                '$a no. $b issued on $c',
                $DOCTYPES[$document['type']],
                $document['fullnumber'],
                date('Y/m/d', $document['cdate'])
            );

            $args = array($id);
            if ($with_reference_document && !empty($document['reference'])) {
                $args[] = $document['reference'];
            }

            $document['attachments'] = $this->db->GetAllByKey(
                '(
                    SELECT
                        a.*,
                        a.type AS main,
                        0 AS reference_document,
                        c.pin
                    FROM documentattachments a
                    JOIN documents d ON d.id = a.docid
                    JOIN customers c ON c.id = d.customerid
                    WHERE a.docid = ?
                    ORDER BY a.type DESC
                )'
                . (count($args) > 1
                    ? ' UNION (
                        SELECT
                            a.*,
                            a.type AS main,
                            1 AS reference_document,
                            c.pin
                        FROM documentattachments a
                        JOIN documents d ON d.id = a.docid
                        JOIN customers c ON c.id = d.customerid
                        WHERE a.docid = ?
                        ORDER BY a.type DESC
                    ) ORDER BY reference_document, type DESC'
                    : ''),
                'id',
                $args
            );

            $document_protection_password = ConfigHelper::getConfig('documents.protection_password', ConfigHelper::getConfig('phpui.document_password', '', true), true);
            $document_protection_command = ConfigHelper::getConfig(
                'documents.protection_command',
                ConfigHelper::getConfig(
                    'phpui.document_protection_command',
                    'qpdf --encrypt %password %password 256 -- %in-file -'
                )
            );
            $document_protected_document_types = ConfigHelper::getConfig(
                'documents.protected_document_types',
                '',
                true
            );
            $document_protection_password_authcode_sources = preg_split(
                '/([\s]+|[\s]*[,;][\s]*)/',
                strtolower(ConfigHelper::getConfig('documents.protection_password_authcode_source', 'random8')),
                -1,
                PREG_SPLIT_NO_EMPTY
            );

            if (strlen($document_protected_document_types)) {
                $protected_document_types = preg_split('/([\s]+|[\s]*,[\s]*)/', $document_protected_document_types, -1, PREG_SPLIT_NO_EMPTY);
                $document_protected_document_types = array();
                $doctype_aliases = array_flip($DOCTYPE_ALIASES);
                foreach ($protected_document_types as $protected_document_type) {
                    if (isset($doctype_aliases[$protected_document_type])) {
                        $document_protected_document_types[$doctype_aliases[$protected_document_type]] = $protected_document_type;
                    }
                }
            } else {
                $document_protected_document_types = $DOCTYPE_ALIASES;
            }

            $ssn_is_present = strpos($document_protection_password, '%ssn') !== false;
            $authcode_is_present = strpos($document_protection_password, '%authcode') !== false;

            $document_type = ConfigHelper::getConfig('documents.type', ConfigHelper::getConfig('phpui.document_type'));
            $cache_pdf = ConfigHelper::checkConfig('documents.cache', ConfigHelper::checkConfig('phpui.cache_documents'));
            $margins = explode(',', ConfigHelper::getConfig('documents.margins', ConfigHelper::getConfig('phpui.document_margins', '10,5,15,5')));

            foreach ($document['attachments'] as &$attachment) {
                $filename = DOC_DIR . DIRECTORY_SEPARATOR . substr($attachment['md5sum'], 0, 2)
                    . DIRECTORY_SEPARATOR . $attachment['md5sum'];
                $pdf = false;

                $doctype = Utils::docTypeByMimeType($attachment['contenttype']);

                if (file_exists($filename . '.pdf')) {
                    // try to get file from pdf document cache
                    $contents = file_get_contents($filename . '.pdf');
                    $contenttype = 'application/pdf';
                    if (empty($doctype)) {
                        $contentname = str_replace('.html', '.pdf', $attachment['filename']);
                    } else {
                        $contentname = preg_replace('/\.[[:alnum:]]+$/', '.pdf', $attachment['filename']);
                    }
                    $pdf = true;
                } else {
                    $contents = file_get_contents($filename);
                    if (preg_match('/html/i', $attachment['contenttype'])
                        && !empty($document_type)
                        && strtolower($document_type) == 'pdf') {
                        if ($cache_pdf) {
                            $contents = Utils::html2pdf(array(
                                'content' => $contents,
                                'subject' => $document['title'],
                                'title' => $document['title'],
                                'type' => $document['type'],
                                'id' => $id,
                                'margins' => $margins,
                                'dest' => 'S',
                                'md5sum' => $attachment['md5sum'],
                            ));
                        } else {
                            $contents = Utils::html2pdf(array(
                                'content' => $contents,
                                'subject' => $document['title'],
                                'title' => $document['title'],
                                'type' => $document['type'],
                                'id' => $id,
                                'margins' => $margins,
                                'dest' => 'S',
                            ));
                        }
                        $pdf = true;
                        $contenttype = 'application/pdf';
                        $contentname = str_replace('.html', '.pdf', $attachment['filename']);
                    } elseif (preg_match('#^application/(rtf|.+(oasis|opendocument|openxml).+)$#i', $attachment['contenttype'])
                        && !empty($document_type)
                        && strtolower($document_type) == 'pdf') {
                        $contents = Utils::office2pdf(array(
                            'content' => $contents,
                            'title' => $document['title'],
                            'dest' => 'S',
                            'md5sum' => $cache_pdf ? $attachment['md5sum'] : null,
                        ));

                        $pdf = true;
                        $contenttype = 'application/pdf';
                        $doctype = Utils::docTypeByFileName($attachment['filename']);
                        $contentname = preg_replace('/\.[[:alnum:]]+$/', '.' . $doctype, $attachment['filename']);
                    } else {
                        $contenttype = $attachment['contenttype'];
                        $contentname = $attachment['filename'];
                        if ($contenttype == 'application/pdf') {
                            $pdf = true;
                        }
                    }
                }

                if ($pdf) {
                    if (!empty($document_protection_password) && !empty($document_protection_command) && isset($document_protected_document_types[$document['type']])
                        && (!$ssn_is_present || strlen($document['ssn']) || $authcode_is_present)) {
                        $customer_data = array(
                            'ssn' => $document['ssn'],
                            'ten' => $document['ten'],
                            'pin' => preg_match('/^\$[0-9]+\$/', $attachment['pin'])
                                ? ''
                                : $attachment['pin'],
                            'phone' => $document['phone'],
                        );

                        if ($authcode_is_present && empty($document['authcode'])) {
                            $document['authcode'] = $this->prepareDocumentAuthCode($document_protection_password_authcode_sources, $customer_data);
                        }

                        $password = trim(str_replace(
                            array(
                                '%ssn',
                                '%pin',
                                '%authcode',
                            ),
                            array(
                                $document['ssn'],
                                $customer_data['pin'],
                                empty($document['authcode']) ? '' : $document['authcode'],
                            ),
                            $document_protection_password
                        ));

                        if (!empty($password)) {
                            $pdf_file_name = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lms-document-attachment-' . uniqid('', true);
                            file_put_contents($pdf_file_name, $contents);
                            $protection_command = str_replace(
                                array(
                                    '%in-file',
                                    '%password'
                                ),
                                array(
                                    $pdf_file_name,
                                    $password,
                                ),
                                $document_protection_command
                            );
                            $pipes = null;
                            $process = proc_open(
                                $protection_command,
                                array(
                                    0 => array('pipe', 'r'),
                                    1 => array('pipe', 'w'),
                                    2 => array('pipe', 'w'),
                                ),
                                $pipes
                            );
                            if (is_resource($process)) {
                                $output = stream_get_contents($pipes[1]);
                                fclose($pipes[1]);

                                $error = stream_get_contents($pipes[2]);
                                fclose($pipes[2]);

                                $result = proc_close($process);

                                if (empty($result)) {
                                    $contents = $output;
                                }
                            }
                            @unlink($pdf_file_name);
                        }
                    }
                }

                $attachment['contents'] = $contents;
                $attachment['contenttype'] = $contenttype;
                $attachment['filename'] = $contentname;
            }
            unset($attachment);
        }
        return $document;
    }

    public function SendDocuments($docs, $type, $params)
    {
        global $LMS, $DOCTYPES, $DOCTYPE_ALIASES;

        switch ($type) {
            case 'frontend':
                $eol = '<br>';
                break;
            default:
                $eol = PHP_EOL;
                break;
        }

        if (!isset($currtime)) {
            $currtime = time();
        }

        $month = date('m', $currtime);
        $day = date('d', $currtime);
        $year = date('Y', $currtime);

        if (!empty($docs)) {
            $doc = reset($docs);

            if (empty($doc['divisionid'])) {
                $documentDivisions = $this->db->GetAll(
                    'SELECT
                        d.id,
                        d.divisionid
                    FROM documents d
                    WHERE d.id IN ?',
                    array(
                        Utils::array_column($docs, 'id'),
                    )
                );
                $documentDivisions = Utils::array_column($documentDivisions, 'divisionid', 'id');

                foreach ($docs as &$doc) {
                    $doc['divisionid'] = $documentDivisions[$doc['id']];
                }
                unset($doc);

                uasort(
                    $docs,
                    function ($doc1, $doc2) {
                        return $doc1['divisionid'] <=> $doc2['divisionid'];
                    }
                );
            }
        }

        $currentDivisionId = LMSDivisionManager::getCurrentDivision();

        extract($params);

        $errors = array();
        $info = array();

        $currentDocumentDivisionId = null;

        foreach ($docs as $doc) {
            if ($currentDocumentDivisionId != $doc['divisionid']) {
                $currentDocumentDivisionId = $doc['divisionid'];

                if (!isset($smtp_options_by_division_ids[$currentDocumentDivisionId])) {
                    if (isset($smtp_options_by_division_ids[0]) || empty($smtp_options_by_division_ids)) {
                        ConfigHelper::setFilter($currentDocumentDivisionId, Auth::GetCurrentUser());
                    }

                    $smtp_options = array(
                        'host' => ConfigHelper::getConfig('documents.smtp_host', ConfigHelper::getConfig('mail.smtp_host')),
                        'port' => ConfigHelper::getConfig('documents.smtp_port', ConfigHelper::getConfig('mail.smtp_port')),
                        'user' => ConfigHelper::getConfig('documents.smtp_user', ConfigHelper::getConfig('mail.smtp_username', ConfigHelper::getConfig('mail.smtp_user'))),
                        'pass' => ConfigHelper::getConfig('documents.smtp_pass', ConfigHelper::getConfig('mail.smtp_password', ConfigHelper::getConfig('mail.smtp_pass'))),
                        'auth' => ConfigHelper::getConfig('documents.smtp_auth', ConfigHelper::getConfig('mail.smtp_auth_type')),
                        'ssl_verify_peer' => ConfigHelper::checkConfig('documents.smtp_ssl_verify_peer', ConfigHelper::checkConfig('mail.smtp_ssl_verify_peer', true)),
                        'ssl_verify_peer_name' => ConfigHelper::checkConfig('documents.smtp_ssl_verify_peer_name', ConfigHelper::checkConfig('mail.smtp_ssl_verify_peer_name', true)),
                        'ssl_allow_self_signed' => ConfigHelper::checkConfig('documents.smtp_ssl_allow_self_signed', ConfigHelper::checkConfig('mail.smtp_ssl_allow_self_signed')),
                    );

                    if (!isset($smtp_options_by_division_ids[0])) {
                        $smtp_options_by_division_ids[0] = $smtp_options;
                        if (!empty($currentDivisionId)) {
                            $smtp_options_by_division_ids[$currentDivisionId] = $smtp_options;
                        }
                    }
                    if (!isset($smtp_options_by_division_ids[$currentDocumentDivisionId])) {
                        $smtp_options_by_division_ids[$currentDocumentDivisionId] = $smtp_options;
                    }

                    $options = array(
                        'debug_email' => ConfigHelper::getConfig('documents.debug_email', '', true),
                        'sender_name' => ConfigHelper::getConfig('documents.sender_name', '', true),
                        'sender_email' => ConfigHelper::getConfig('documents.sender_email', '', true),
                        'mail_subject' => ConfigHelper::getConfig('documents.mail_subject', '%document'),
                        'mail_body' => ConfigHelper::getConfig('documents.mail_body', '%document'),
                        'mail_format' => ConfigHelper::getConfig('documents.mail_format', 'text'),
                        'notify_email' => ConfigHelper::getConfig('documents.notify_email', '', true),
                        'reply_email' => ConfigHelper::getConfig('documents.reply_email', '', true),
                        'add_message' => ConfigHelper::checkConfig('documents.add_message'),
                        'message_attachments' => ConfigHelper::checkConfig('documents.message_attachments'),
                        'dsn_email' => ConfigHelper::getConfig('documents.dsn_email', '', true),
                        'mdn_email' => ConfigHelper::getConfig('documents.mdn_email', '', true),

                        'smtp_auth' => empty($smtp_auth) ? ConfigHelper::getConfig('mail.smtp_auth_type') : $smtp_auth,

                        'attachment_filename' => ConfigHelper::getConfig('documents.attachment_filename', '%filename'),

                        'aggregate_reference_document_email' => ConfigHelper::checkConfig('documents.aggregate_reference_document_email'),

                        'send_zip_filename' => ConfigHelper::getConfig('documents.send_zip_filename'),
                        'send_zip_protection_password' => ConfigHelper::getConfig('documents.send_zip_protection_password'),
                        'send_zip_protection_method' => ConfigHelper::getConfig('documents.send_zip_protection_method'),

                        'document_protected_document_types' => ConfigHelper::getConfig(
                            'documents.protected_document_types',
                            '',
                            true
                        ),

                        'document_protection_password_authcode_sources' => preg_split(
                            '/([\s]+|[\s]*,[\s]*)/',
                            strtolower(ConfigHelper::getConfig('documents.protection_password_authcode_source', 'random8')),
                            -1,
                            PREG_SPLIT_NO_EMPTY
                        ),

                        'document_protection_password_authcode_message' => ConfigHelper::getConfig(
                            'documents.protection_password_authcode_message',
                            '%authcode'
                        ),
                    );

                    if (strlen($options['document_protected_document_types'])) {
                        $protected_document_types = preg_split('/([\s]+|[\s]*,[\s]*)/', $options['document_protected_document_types'], -1, PREG_SPLIT_NO_EMPTY);
                        $document_protected_document_types = array();
                        $doctype_aliases = array_flip($DOCTYPE_ALIASES);
                        foreach ($protected_document_types as $protected_document_type) {
                            if (isset($doctype_aliases[$protected_document_type])) {
                                $document_protected_document_types[$doctype_aliases[$protected_document_type]] = $protected_document_type;
                            }
                        }
                    } else {
                        $document_protected_document_types = $DOCTYPE_ALIASES;
                    }

                    $options['document_protected_document_types'] = $document_protected_document_types;

                    $em_constant_name = null;
                    if (!empty($options['send_zip_protection_password'])) {
                        if (!empty($options['send_zip_protection_method'])) {
                            $em_constant_name = 'ZipArchive::EM_' . strtoupper($options['send_zip_protection_method']);
                            if (!defined($em_constant_name)) {
                                $em_constant_name = 'ZipArchive::EM_TRAD_PKWARE';
                            }
                        } else {
                            $em_constant_name = 'ZipArchive::EM_TRAD_PKWARE';
                        }
                    }

                    $options['send_zip_protection_method'] = $em_constant_name;

                    if (!isset($options_by_division_ids[0])) {
                        $options_by_division_ids[0] = $options;
                        if (!empty($currentDivisionId)) {
                            $options_by_division_ids[$currentDivisionId] = $options;
                        }
                    }
                    if (!isset($options_by_division_ids[$currentDocumentDivisionId])) {
                        $options_by_division_ids[$currentDocumentDivisionId] = $options;
                    }
                }

                extract($smtp_options_by_division_ids[$currentDocumentDivisionId]);
                extract($options_by_division_ids[$currentDocumentDivisionId]);

                if (empty($sender_email)) {
                    switch ($type) {
                        case 'frontend':
                            die('<span class="red">' . trans("Fatal error: sender_email unset! Can't continue, exiting.") . '</span>' . $eol);
                            break;
                        case 'backend':
                            die(trans("Fatal error: sender_email unset! Can't continue, exiting.") . $eol);
                            break;
                        default:
                            $errors[] = trans("Fatal error: sender_email unset! Can't continue, exiting.");
                    }
                }

                if (!empty($smtp_auth) && !preg_match('/^LOGIN|PLAIN|CRAM-MD5|NTLM$/i', $smtp_auth)) {
                    switch ($type) {
                        case 'frontend':
                            die('<span class="red">' . trans("Fatal error: smtp_auth value not supported! Can't continue, exiting.") . '</span>' . $eol);
                            break;
                        case 'backend':
                            die(trans("Fatal error: smtp_auth value not supported! Can't continue, exiting.") . $eol);
                            break;
                        default:
                            $errors[] = trans("Fatal error: smtp_auth value not supported! Can't continue, exiting.");
                    }
                }

                if ($type == 'userpanel' && !empty($errors)) {
                    return compact('info', 'errors');
                }

                if (empty($dsn_email)) {
                    $from = $sender_email;
                } else {
                    $from = $dsn_email;
                }

                if (!empty($sender_name)) {
                    $from = $sender_name . ' <' . $from . '>';
                }

                $mail_bodies = array();
                $mail_subjects = array();
            }

            $document = $this->GetDocumentFullContents($doc['id'], !empty($reference_document) && $aggregate_reference_document_email);
            if (empty($document)) {
                continue;
            }

            $custemail = (!empty($debug_email) ? $debug_email : $doc['email']);

            if (!array_key_exists($document['type'], $mail_bodies)) {
                if (ConfigHelper::variableExists('documents-' . $DOCTYPE_ALIASES[$document['type']] . '.mail_body')) {
                    $mail_bodies[$document['type']] = ConfigHelper::getConfig('documents-' . $DOCTYPE_ALIASES[$document['type']] . '.mail_body');
                } else {
                    $mail_bodies[$document['type']] = null;
                }
            }

            if (!array_key_exists($document['type'], $mail_subjects)) {
                if (ConfigHelper::variableExists('documents-' . $DOCTYPE_ALIASES[$document['type']] . '.mail_subject')) {
                    $mail_subjects[$document['type']] = ConfigHelper::getConfig('documents-' . $DOCTYPE_ALIASES[$document['type']] . '.mail_subject');
                } else {
                    $mail_subjects[$document['type']] = null;
                }
            }

            $body = isset($mail_bodies[$document['type']]) ? $mail_bodies[$document['type']] : $mail_body;
            $subject = isset($mail_subjects[$document['type']]) ? $mail_subjects[$document['type']] : $mail_subject;

            $body = str_replace(
                array(
                    '%document',
                    '%cdate-y',
                    '%cdate-m',
                    '%cdate-d',
                    '%type',
                    '%title',
                    '%today',
                    '%cid',
                    '%pin',
                    '%customer_name',
                    '\n',
                ),
                array(
                    $document['fullnumber'],
                    date('Y', $document['cdate']),
                    date('m', $document['cdate']),
                    date('d', $document['cdate']),
                    $DOCTYPES[$document['type']],
                    $document['content_title'],
                    $year . '-' . $month . '-' . $day,
                    $document['customerid'],
                    $document['pin'],
                    $document['name'],
                    "\n",
                ),
                $body
            );

            $subject = str_replace(
                array(
                    '%document',
                    '%type',
                    '%title',
                ),
                array(
                    $document['fullnumber'],
                    $DOCTYPES[$document['type']],
                    $document['content_title'],
                ),
                $subject
            );

            $mailto = array();
            $mailto_qp_encoded = array();
            foreach (explode(',', $custemail) as $email) {
                $mailto[] = $doc['name'] . " <$email>";
                $mailto_qp_encoded[] = qp_encode($document['name']) . " <$email>";
            }
            $mailto = implode(', ', $mailto);
            $mailto_qp_encoded = implode(', ', $mailto_qp_encoded);

            if (empty($quiet) || !empty($test)) {
                $msg = $document['title'] . ': ' . $mailto ;
                switch ($type) {
                    case 'frontend':
                        echo htmlspecialchars($msg) . $eol;
                        flush();
                        ob_flush();
                        break;
                    case 'backend':
                        echo $msg . $eol;
                        break;
                    case 'userpanel':
                        $info[] = $msg;
                        break;
                }
            }

            if (empty($test)) {
                $files = array();
                $first = true;

                if (empty($document['attachments'])) {
                    $document['attachments'] = array();
                }

                $all_attachment_filenames = array();
                $filename_duplicates = false;

                foreach ($document['attachments'] as $attachment) {
                    $extension = '';

                    if ($attachment['type'] == 1) {
                        if (empty($attachment['reference_document'])) {
                            $replacements = array(
                                $attachment['filename'],
                                $DOCTYPES[$document['type']],
                                $document['fullnumber'],
                                $doc['id'],
                            );
                        } else {
                            $replacements = array(
                                $attachment['filename'],
                                $DOCTYPES[$document['ref_type']],
                                $document['ref_fullnumber'],
                                $document['reference'],
                            );
                        }
                        $filename = str_replace(
                            array(
                                '%filename',
                                '%type',
                                '%document',
                                '%docid'
                            ),
                            $replacements,
                            $attachment_filename
                        );

                        if (!preg_match('/\.[[:alnum:]]+$/i', $filename)) {
                            if (preg_match('/(?<extension>\.[[:alnum:]]+)$/i', $attachment['filename'], $m)) {
                                $extension = $m['extension'];
                            } elseif (preg_match('#/(?<extension>[[:alnum:]]+)$#i', $attachment['contenttype'], $m)) {
                                $extension = '.' . $m['extension'];
                            }
                        }
                    } else {
                        $filename = $attachment['filename'];
                    }

                    if (!empty($send_zip_filename) && $first) {
                        $first = false;

                        $zip_filename = $attachment['filename'];

                        $i = strpos($zip_filename, '.');
                        if ($i !== false) {
                            $zip_filename = mb_substr($zip_filename, 0, $i);
                        }

                        $zip_filename = preg_replace(
                            '/[^[:alnum:]_\.\-]/iu',
                            '_',
                            str_replace(
                                array(
                                    '%filename',
                                    '%type',
                                    '%document',
                                    '%docid',
                                ),
                                array(
                                    $zip_filename,
                                    $DOCTYPES[$document['type']],
                                    $document['fullnumber'],
                                    $doc['id'],
                                ),
                                $send_zip_filename
                            )
                        );

                        if (!class_exists('ZipArchive')) {
                            die('Error: ZipArchive class not found!');
                        }

                        $zip_temp_filename = tempnam(sys_get_temp_dir(), 'lms-documentsend-');
                        @unlink($zip_temp_filename);

                        $zip = new ZipArchive;
                        $zip->open($zip_temp_filename, ZipArchive::CREATE);
                        if (empty($zip)) {
                            die('Error: cannot create temporary ZipArchive: \'' . $zip_temp_filename . '\'!');
                        }

                        if (!empty($send_zip_protection_password)) {
                            $ssn_is_present = strpos($send_zip_protection_password, '%ssn') !== false;
                            $authcode_is_present = strpos($send_zip_protection_password, '%authcode') !== false;

                            if (isset($document_protected_document_types[$document['type']])
                                && (!$ssn_is_present || strlen($document['ssn']) || $authcode_is_present)) {
                                $customer_data = array(
                                    'ssn' => $document['ssn'],
                                    'ten' => $document['ten'],
                                    'pin' => preg_match('/^\$[0-9]+\$/', $attachment['pin'])
                                        ? ''
                                        : $attachment['pin'],
                                    'phone' => $doc['phone'],
                                );

                                if ($authcode_is_present && empty($document['authcode'])) {
                                    $document['authcode'] = $this->prepareDocumentAuthCode($document_protection_password_authcode_sources, $customer_data);
                                }

                                $zip_password = trim(str_replace(
                                    array(
                                        '%ssn',
                                        '%pin',
                                        '%authcode',
                                    ),
                                    array(
                                        $document['ssn'],
                                        $customer_data['pin'],
                                        empty($document['authcode']) ? '' : $document['authcode'],
                                    ),
                                    $send_zip_protection_password
                                ));
                                if (!empty($zip_password)) {
                                    $zip->setPassword($zip_password);
                                }
                            }
                        }
                    }

                    if (empty($send_zip_filename)) {
                        $output_filename = preg_replace('/[^[:alnum:]_\.]/iu', '_', $filename) . $extension;
                        if (isset($all_attachment_filenames[$output_filename])) {
                            $filename_duplicates = true;
                            break;
                        }

                        $all_attachment_filenames[$output_filename] = true;

                        $files[] = array(
                            'content_type' => $attachment['contenttype'],
                            'filename' => $output_filename,
                            'data' => $attachment['contents'],
                        );
                    } else {
                        $zip_archived_filename = preg_replace('/[^[:alnum:]_\.]/iu', '_', $filename) . $extension;
                        if (isset($all_attachment_filenames[$zip_archived_filename])) {
                            $filename_duplicates = true;
                            break;
                        }

                        $all_attachment_filenames[$zip_archived_filename] = true;

                        $zip->addFromString($zip_archived_filename, $attachment['contents']);
                        if (!empty($zip_password) && $attachment['type'] == 1) {
                            $zip->setEncryptionName($zip_archived_filename, constant($send_zip_protection_method));
                        }
                    }
                }

                if (!empty($extrafile)) {
                    if (empty($send_zip_filename)) {
                        $files[] = array(
                            'content_type' => mime_content_type($extrafile),
                            'filename' => basename($extrafile),
                            'data' => file_get_contents($extrafile)
                        );
                    } else {
                        $zip_archived_filename = basename($extrafile);
                        $zip->addFromString($zip_archived_filename, file_get_contents($extrafile));
                    }
                }

                if (!empty($send_zip_filename)) {
                    $zip->close();

                    $files[] = array(
                        'content_type' => 'application/zip',
                        'filename' => $zip_filename . '.zip',
                        'data' => file_get_contents($zip_temp_filename),
                    );
                    unlink($zip_temp_filename);
                }

                $headers = array(
                    'From' => $from,
                    'To' => $mailto_qp_encoded,
                    'Recipient-Name' => $document['name'],
                    'Subject' => $subject,
                    'Reply-To' => empty($reply_email) ? $sender_email : $reply_email,
                );

                if (!empty($mdn_email)) {
                    $headers['Return-Receipt-To'] = $mdn_email;
                    $headers['Disposition-Notification-To'] = $mdn_email;
                }

                if (!empty($dsn_email)) {
                    $headers['Delivery-Status-Notification-To'] = $dsn_email;
                }

                if (!empty($notify_email)) {
                    $headers['Cc'] = $notify_email;
                }

                if (isset($mail_format) && $mail_format == 'html') {
                    $headers['X-LMS-Format'] = 'html';
                }

                if ($add_message) {
                    $this->db->Execute(
                        'INSERT INTO messages (subject, body, cdate, type, userid)
						VALUES (?, ?, ?NOW?, ?, ?)',
                        array($subject, $body, MSG_MAIL, Auth::GetCurrentUser())
                    );
                    $msgid = $this->db->GetLastInsertID('messages');

                    if ($message_attachments) {
                        if (!empty($files)) {
                            foreach ($files as &$file) {
                                $file['name'] = $file['filename'];
                                $file['type'] = $file['content_type'];
                            }
                            unset($file);
                            if (!isset($file_manager)) {
                                $file_manager = new LMSFileManager($this->db, $this->auth, $this->cache, $this->syslog);
                            }
                            $file_manager->AddFileContainer(array(
                                'description' => 'message-' . $msgid,
                                'files' => $files,
                                'type' => 'messageid',
                                'resourceid' => $msgid,
                            ));
                        }
                    }

                    foreach (explode(',', $custemail) as $email) {
                        $this->db->Execute(
                            'INSERT INTO messageitems (messageid, customerid, destination, lastdate, status)
							VALUES (?, ?, ?, ?NOW?, ?)',
                            array($msgid, $doc['customerid'], $email, MSG_NEW)
                        );
                        $msgitemid = $this->db->GetLastInsertID('messageitems');
                        if (!isset($msgitems[$doc['customerid']])) {
                            $msgitems[$doc['customerid']] = array();
                        }
                        $msgitems[$doc['customerid']][$email] = $msgitemid;
                    }
                }

                if (!empty($document['authcode']) && !empty($doc['phone'])) {
                    $phones = explode(',', $doc['phone']);
                    foreach ($phones as $phone) {
                        $LMS->SendSMS(
                            $phone,
                            str_replace(
                                '%authcode',
                                $document['authcode'],
                                $document_protection_password_authcode_message
                            )
                        );
                    }
                }

                foreach (explode(',', $custemail) as $email) {
                    if ($add_message && (!empty($dsn_email) || !empty($mdn_email))) {
                        $headers['X-LMS-Message-Item-Id'] = $msgitems[$doc['customerid']][$email];
                        $headers['Message-ID'] = '<messageitem-' . $headers['X-LMS-Message-Item-Id'] . '@rtsystem.' . gethostname() . '>';
                    }

                    $res = $LMS->SendMail(
                        $email . ',' . $notify_email,
                        $headers,
                        $body,
                        $files,
                        null,
                        ($smtp_options ?? null)
                    );

                    if (is_string($res)) {
                        $msg = trans('Error sending mail: $a', $res);
                        switch ($type) {
                            case 'backend':
                                fprintf(STDERR, $msg . $eol);
                                break;
                            case 'frontend':
                                echo '<span class="red">' . htmlspecialchars($msg) . '</span>' . $eol;
                                flush();
                                break;
                            case 'userpanel':
                                $errors[] = htmlspecialchars($msg);
                                break;
                        }
                        $status = MSG_ERROR;
                    } else {
                        $status = MSG_SENT;
                        $res = null;
                    }

                    if ($status == MSG_SENT) {
                        $this->db->Execute('UPDATE documents SET published = 1, senddate = ?NOW? WHERE id = ?', array($doc['id']));
                    }

                    if ($add_message) {
                        $this->db->Execute('UPDATE messageitems SET status = ?, error = ?
							WHERE id = ?', array($status, $res, $msgitems[$doc['customerid']][$email]));
                    }
                }
            }

            if (!empty($reference_document) && !empty($document['reference']) && (!$aggregate_reference_document_email || $filename_duplicates)) {
                $this->SendDocuments(
                    array(
                        array(
                            'id' => $document['reference'],
                            'email' => $doc['email'],
                            'name' => $doc['name'],
                            'customerid' => $doc['customerid'],
                        ),
                    ),
                    $type,
                    compact('currtime')
                );
            }
        }

        if ($type == 'userpanel') {
            return compact('info', 'errors');
        }
    }

    public function deleteDocumentAttachments($docid)
    {
        $attachments = $this->db->GetAll(
            'SELECT
                id,
                md5sum
            FROM documentattachments
            WHERE docid = ?',
            array($docid)
        );

        foreach ($attachments as $attachment) {
            $md5sum = $attachment['md5sum'];
            if ($this->db->GetOne('SELECT COUNT(*) FROM documentattachments WHERE md5sum = ?', array((string)$md5sum)) == 1) {
                $filename_pdf = DOC_DIR . DIRECTORY_SEPARATOR . substr($md5sum, 0, 2) . DIRECTORY_SEPARATOR . $md5sum . '.pdf';
                if (file_exists($filename_pdf)) {
                    @unlink($filename_pdf);
                }

                if (!isset($file_manager)) {
                    $file_manager = new LMSFileManager($this->db, $this->auth, $this->cache, $this->syslog);
                }
                if (!$file_manager->FileExists($md5sum)) {
                    @unlink(DOC_DIR . DIRECTORY_SEPARATOR . substr($md5sum, 0, 2) . DIRECTORY_SEPARATOR . $md5sum);
                }
            }

            if ($this->syslog) {
                $args = array(
                    SYSLOG::RES_DOCATTACH => $attachment['id'],
                    SYSLOG::RES_DOC => $docid,
                    'md5sum' => $attachment['md5sum'],
                );
                $this->syslog->AddMessage(SYSLOG::RES_DOCATTACH, SYSLOG::OPER_DELETE, $args);
            }
        }
    }

    public function DeleteDocument($docid)
    {
        $document = $this->db->GetRow(
            'SELECT d.id, d.type, d.customerid FROM documents d
			JOIN docrights r ON (r.doctype = d.type)
			WHERE d.id = ? AND r.userid = ? AND (r.rights & ?) > 0',
            array($docid, Auth::GetCurrentUser(), DOCRIGHT_DELETE)
        );
        if (!$document) {
            return false;
        }

        $this->deleteDocumentAttachments($docid);

        $this->db->Execute('DELETE FROM documents WHERE id = ?', array($docid));
        if ($this->syslog) {
            $args = array(
                SYSLOG::RES_DOC => $docid,
                SYSLOG::RES_CUST => $document['customerid'],
                'type' => $document['type'],
            );
            $this->syslog->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_DELETE, $args);
        }

        return true;
    }

    public function CopyDocumentPermissions($src_userid, $dst_userid)
    {
        $this->db->Execute('DELETE FROM docrights WHERE userid = ?', array($dst_userid));
        return $this->db->Execute(
            'INSERT INTO docrights (userid, doctype, rights)
            (SELECT ?, doctype, rights FROM docrights WHERE userid = ?)',
            array($dst_userid, $src_userid)
        );
    }

    public function getDocumentsByFullNumber($full_number, $all_types = false)
    {
        return $this->db->GetAllByKey(
            'SELECT d.* FROM documents d
            JOIN customerview c ON c.id = d.customerid
            WHERE d.fullnumber = ?'
            . ($all_types ? '' : ' AND d.type < 0'),
            'id',
            array($full_number)
        );
    }

    public function getDocumentsByChecksum($checksum, $all_types = false)
    {
        return $this->db->GetAllByKey(
            'SELECT d.* FROM documents d
            JOIN docrights r ON (d.type = r.doctype AND r.userid = ? AND r.rights & ' . DOCRIGHT_EDIT . ' > 0)
            JOIN customerview c ON c.id = d.customerid
            WHERE EXISTS (SELECT a.id FROM documentattachments a WHERE a.docid = d.id AND a.md5sum = ?)'
            . ($all_types ? '' : ' AND d.type < 0'),
            'id',
            array(Auth::GetCurrentUser(), $checksum)
        );
    }

    public function isDocumentAccessible($docid)
    {
        return $this->db->GetOne(
            'SELECT d.id FROM documents d
            JOIN docrights r ON (d.type = r.doctype AND r.userid = ? AND r.rights & ' . DOCRIGHT_EDIT . ' > 0)
            JOIN customerview c ON c.id = d.customerid
            WHERE d.id = ?',
            array(Auth::GetCurrentUser(), $docid)
        ) > 0;
    }

    public function getDocumentReferences($docid, $cashid = null)
    {
        $userid = Auth::GetCurrentUser();

        $documents = array();

        if (!empty($cashid)) {
            $docid = $this->db->GetOne('SELECT docid FROM cash WHERE id = ?', array($cashid));
            if (empty($docid)) {
                return $documents;
            }
        }

        if (empty($userid)) {
            $attachments = $this->db->GetAll(
                'SELECT
                    d.id AS docid,
                    d.cdate,
                    d.fullnumber,
                    d.type AS doctype,
                    a.id AS attachmentid,
                    a.filename,
                    a.contenttype,
                    a.md5sum,
                    a.type AS attachmenttype,
                    a.cdate AS attachmentcdate
                FROM documents d
                JOIN documentattachments a ON a.docid = d.id
                WHERE d.reference = ?
                ORDER BY d.id, a.type DESC',
                array(
                    $docid,
                )
            );
        } else {
            $attachments = $this->db->GetAll(
                'SELECT
                    d.id AS docid,
                    d.cdate,
                    d.fullnumber,
                    d.type AS doctype,
                    a.id AS attachmentid,
                    a.filename,
                    a.contenttype,
                    a.md5sum,
                    a.type AS attachmenttype,
                    a.cdate AS attachmentcdate
                FROM documents d
                JOIN docrights r ON r.doctype = d.type AND r.userid = ? AND (r.rights & ?) > 0
                JOIN documentattachments a ON a.docid = d.id
                WHERE d.reference = ?
                ORDER BY d.id, a.type DESC',
                array(
                    $userid,
                    DOCRIGHT_VIEW,
                    $docid,
                )
            );
        }

        if (empty($attachments)) {
            return $documents;
        }

        foreach ($attachments as $attachment) {
            $docid = $attachment['docid'];
            if (!isset($documents[$docid])) {
                $documents[$docid] = array(
                    'docid' => $docid,
                    'cdate' => $attachment['cdate'],
                    'fullnumber' => $attachment['fullnumber'],
                    'type' => $attachment['doctype'],
                    'attachments' => array(),
                );
            }
            $attachmentid = $attachment['attachmentid'];
            $documents[$docid]['attachments'][$attachmentid] = array(
                'type' => $attachment['attachmenttype'],
                'filename' => $attachment['filename'],
                'contenttype' => $attachment['contenttype'],
                'md5sum' => $attachment['md5sum'],
                'cdate' => $attachment['attachmentcdate'],
            );
        }

        return $documents;
    }

    public function getReferencedDocument($docid)
    {
        $userid = Auth::GetCurrentUser();

        return $this->db->GetRow(
            'SELECT d.* 
            FROM documents d
            JOIN docrights r ON r.doctype = d.type AND r.userid = ? AND (r.rights & ?) > 0
            JOIN documentattachments a ON a.docid = d.id
            WHERE d.id = (SELECT documents.reference FROM documents WHERE documents.id = ?)
            ORDER BY d.id, a.type DESC',
            array(
                $userid,
                DOCRIGHT_VIEW,
                $docid,
            )
        );
    }

    public function getReferencingDocuments($docid)
    {
        $userid = Auth::GetCurrentUser();

        return $this->db->GetAllByKey(
            'SELECT d.*
            FROM documents d
            JOIN docrights r ON r.doctype = d.type AND r.userid = ? AND (r.rights & ?) > 0
            JOIN documentattachments a ON a.docid = d.id
            WHERE d.reference = ?
            ORDER BY d.id, a.type DESC',
            'id',
            array(
                $userid,
                DOCRIGHT_VIEW,
                $docid,
            )
        );
    }

    public function getDocumentType($docid)
    {
        return $this->db->GetOne('SELECT type FROM documents WHERE id = ?', array($docid));
    }

    public function getDocumentFullNumber($docid)
    {
        return $this->db->GetOne('SELECT fullnumber FROM documents WHERE id = ?', array($docid));
    }
}

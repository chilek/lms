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

/**
 * LMSDocumentManager
 *
 */
class LMSDocumentManager extends LMSManager implements LMSDocumentManagerInterface
{

    public function GetDocuments($customerid = null, $limit = null)
    {
        if (!$customerid) {
            return null;
        }

        if ($list = $this->db->GetAll('SELECT c.docid, d.number, d.type, c.title, c.fromdate, c.todate,
				c.description, n.template, d.closed, d.confirmdate,
				d.archived, d.adate, u3.name AS ausername, d.senddate,
				d.cdate, u.name AS username, d.sdate, u2.name AS cusername,
				d.type AS doctype, d.template AS doctemplate, reference
			FROM documentcontents c
			JOIN documents d ON (c.docid = d.id)
			JOIN docrights r ON (d.type = r.doctype AND r.userid = ? AND r.rights & ' . DOCRIGHT_VIEW . ' > 0)
			JOIN vusers u ON u.id = d.userid
			LEFT JOIN vusers u2 ON u2.id = d.cuserid
			LEFT JOIN vusers u3 ON u3.id = d.auserid
			LEFT JOIN numberplans n ON (d.numberplanid = n.id)
			WHERE d.customerid = ?
			ORDER BY cdate', array(Auth::GetCurrentUser(), $customerid))) {
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
        $order = isset($params['order']) ? $params['order'] : 'cdate,asc';
        $type = isset($params['type']) ? $params['type'] : null;
        $service = isset($params['service']) ? $params['service'] : null;
        $customer = isset($params['customer']) ? $params['customer'] : null;
        $numberplan = isset($params['numberplan']) ? $params['numberplan'] : null;
        $usertype = isset($params['usertype']) ? $params['usertype'] : 'creator';
        $userid = isset($params['userid']) ? $params['userid'] : null;
        $periodtype = isset($params['periodtype']) ? $params['periodtype'] : 'creationdate';
        $from = isset($params['from']) ? $params['from'] : 0;
        $to = isset($params['to']) ? $params['to'] : 0;
        $status = isset($params['status']) ? $params['status'] : -1;
        $archived = isset($params['archived']) ? $params['archived'] : -1;
        $limit = isset($params['limit']) ? $params['limit'] : null;
        $offset = isset($params['offset']) ? $params['offset'] : null;
        $count = isset($params['count']) ? $params['count'] : false;

        if ($order=='') {
            $order='cdate,asc';
        }

        list($order,$direction) = sscanf($order, '%[^,],%s');
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
            case 'archivizator':
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
                $status_sql = ' AND d.closed = 0 AND d.confirmdate >= 0 AND (d.confirmdate = 0 OR d.confirmdate < ?NOW?)';
                break;
            case 1:
                $status_sql = ' AND d.closed > 0';
                break;
            case 2:
                $status_sql = ' AND d.closed = 0 AND d.confirmdate = -1';
                break;
            case 3:
                $status_sql = ' AND d.closed = 0 AND d.confirmdate > 0 AND d.confirmdate > ?NOW?';
                break;
            case 4:
                $status_sql = ' AND d.closed = 2';
                break;
            case 5:
                $status_sql = ' AND d.closed = 3';
                break;
            default:
                $status_sql = '';
        }

        if ($count) {
            return $this->db->GetOne(
                'SELECT COUNT(documentcontents.docid)
				FROM documentcontents
				JOIN documents d ON (d.id = documentcontents.docid)
				JOIN docrights r ON (d.type = r.doctype AND r.userid = ? AND (r.rights & 1) = 1)
				JOIN vusers u ON u.id = d.userid
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
					SELECT DISTINCT a.customerid FROM customerassignments a
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
                array(Auth::GetCurrentUser())
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
			JOIN docrights r ON (d.type = r.doctype AND r.userid = ? AND (r.rights & 1) = 1)
			JOIN vusers u ON u.id = d.userid
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
				SELECT DISTINCT a.customerid FROM customerassignments a
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
            array(Auth::GetCurrentUser())
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
            $cdate = null;
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

        if (is_array($doctype)) {
            $where[] = 'doctype IN (' . implode(',', $doctype) . ')';
        } else if ($doctype) {
            $where[] = 'doctype = ' . intval($doctype);
        }

        if ($division) {
            $where[] = 'id IN (SELECT planid FROM numberplanassignments
                WHERE divisionid = ' . intval($division) . ')';
        }

        if (!empty($where)) {
            $where = ' WHERE ' . implode(' AND ', $where);
        }

        $list = $this->db->GetAllByKey('
				SELECT id, template, isdefault, period, doctype
				FROM numberplans' . $where . '
				ORDER BY id', 'id');

        if ($list && $next) {
            if ($cdate) {
                list($curryear, $currmonth) = explode('/', $cdate);
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
            $weekstart = mktime(0, 0, 0, $currmonth, date('j') - strftime('%u') + 1);
            $weekend = mktime(0, 0, 0, $currmonth, date('j') - strftime('%u') + 1 + 7);
            $daystart = mktime(0, 0, 0);
            $dayend = mktime(0, 0, 0, date('n'), date('j') + 1);

            foreach ($list as &$item) {
                $max = $this->db->GetOne(
                    'SELECT MAX(number) AS max 
					FROM documents
					LEFT JOIN numberplans ON (numberplanid = numberplans.id)
					WHERE numberplanid = ? AND ' . (!preg_match('/%[0-9]*C/', $item['template']) || empty($customerid)
                        ? '' : 'customerid = ' . intval($customerid) . ' AND ')
                    . ($doctype ? 'numberplanid IN (' . implode(',', array_keys($list)) . ') AND ' : '')
                    . ' cdate >= (CASE period
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

        if ($planid) {
            $numplan = $this->db->GetRow('SELECT template, period FROM numberplans WHERE id=?', array($planid));
            $numtemplate = $numplan['template'];
            $period = $numplan['period'];
        } else {
            $planid = null;
        }

        $period = isset($period) ? $period : YEARLY;
        $cdate = $cdate ? $cdate : time();

        switch ($period) {
            case DAILY:
                $start = mktime(0, 0, 0, date('n', $cdate), date('j', $cdate), date('Y', $cdate));
                $end = mktime(0, 0, 0, date('n', $cdate), date('j', $cdate) + 1, date('Y', $cdate));
                break;
            case WEEKLY:
                $weekstart = date('j', $cdate) - strftime('%u', $cdate) + 1;
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
                    'SELECT MAX(number) FROM documents
					WHERE type = ? AND ' . ($planid ? 'numberplanid = ' . intval($planid) : 'numberplanid IS NULL')
                    . (!isset($numtemplate) || !preg_match('/%[0-9]*C/', $numtemplate) || empty($customerid)
                        ? '' : ' AND customerid = ' . intval($customerid)),
                    array($doctype)
                );

                return $number ? ++$number : 1;
                break;
        }

        $number = $this->db->GetOne(
            '
				SELECT MAX(number) 
				FROM documents 
				WHERE cdate >= ? AND cdate < ? AND type = ? AND ' . ($planid ? 'numberplanid = ' . intval($planid) : 'numberplanid IS NULL')
                . (!isset($numtemplate) || !preg_match('/%[0-9]*C/', $numtemplate) || empty($customerid)
                    ? '' : ' AND customerid = ' . intval($customerid)),
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
            $planid = 0;
        }
        if (!isset($cdate)) {
            $cdate = null;
        }
        if (!isset($customerid)) {
            $customerid = null;
        }

        if ($planid) {
            $numplan = $this->db->GetRow('SELECT template, period FROM numberplans WHERE id=?', array($planid));
            $numtemplate = $numplan['template'];
            $period = $numplan['period'];
        }

        $period = isset($period) ? $period : YEARLY;
        $cdate = $cdate ? $cdate : time();

        switch ($period) {
            case DAILY:
                $start = mktime(0, 0, 0, date('n', $cdate), date('j', $cdate), date('Y', $cdate));
                $end = mktime(0, 0, 0, date('n', $cdate), date('j', $cdate) + 1, date('Y', $cdate));
                break;
            case WEEKLY:
                $weekstart = date('j', $cdate) - strftime('%u', $cdate) + 1;
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
                    'SELECT id FROM documents
					WHERE type = ? AND number = ? AND numberplanid = ?'
                    . (!isset($numtemplate) || !preg_match('/%[0-9]*C/', $numtemplate) || empty($customerid)
                        ? '' : ' AND customerid = ' . intval($customerid)),
                    array($doctype, $number, $planid)
                );
                break;
        }

        return $this->db->GetOne(
            'SELECT id FROM documents
			WHERE cdate >= ? AND cdate < ? AND type = ? AND number = ? AND numberplanid = ?'
            . (!isset($numtemplate) || !preg_match('/%[0-9]*C/', $numtemplate) || empty($customerid)
                ? '' : ' AND customerid = ' . intval($customerid)),
            array($start, $end, $doctype, $number, $planid)
        );
    }

    public function CommitDocuments(array $ids, $userpanel = false)
    {
        function parse_notification_mail($string, $data)
        {
            $customerinfo = $data['customerinfo'];
            $string = str_replace('%cid%', $customerinfo['id'], $string);
            $string = str_replace('%customername%', $customerinfo['customername'], $string);
            $document = $data['document'];
            $string = str_replace('%docid%', $document['id'], $string);
            return $string;
        }

        $userid = Auth::GetCurrentUser();

        $ids = Utils::filterIntegers($ids);
        if (empty($ids)) {
            return;
        }

        $docs = $this->db->GetAllByKey(
            'SELECT d.id, d.customerid, dc.fromdate AS datefrom,
					d.reference, d.commitflags, d.confirmdate, d.closed,
					(CASE WHEN d.confirmdate = -1 AND a.customerdocuments IS NOT NULL THEN 1 ELSE 0 END) AS customerawaits
				FROM documents d
                JOIN documentcontents dc ON dc.docid = d.id
				LEFT JOIN docrights r ON r.doctype = d.type
				LEFT JOIN (
                    SELECT da.docid, COUNT(*) AS customerdocuments
                    FROM documentattachments da
                    WHERE da.type = -1
                    GROUP BY da.docid
				) a ON a.docid = d.id
				WHERE d.closed = 0 AND d.type < 0 AND d.id IN (' . implode(',', $ids) . ')' . ($userid ? ' AND r.userid = ' . intval($userid) . ' AND (r.rights & ' . DOCRIGHT_CONFIRM . ') > 0' : ''),
            'id'
        );
        if (empty($docs)) {
            return;
        }

        $finance_manager = new LMSFinanceManager($this->db, $this->auth, $this->cache, $this->syslog);

        $this->db->BeginTrans();

        $mail_dsn = ConfigHelper::getConfig('userpanel.document_notification_mail_dsn_address', '', true);
        $mail_mdn = ConfigHelper::getConfig('userpanel.document_notification_mail_mdn_address', '', true);
        $mail_sender_name = ConfigHelper::getConfig('userpanel.document_notification_mail_sender_name', '', true);
        $mail_sender_address = ConfigHelper::getConfig('userpanel.document_notification_mail_sender_address', ConfigHelper::getConfig('mail.smtp_username'));
        $mail_reply_address = ConfigHelper::getConfig('userpanel.document_notification_mail_reply_address', '', true);
        $mail_format = ConfigHelper::getConfig('userpanel.document_approval_customer_notification_mail_format', 'text');
        $mail_subject = ConfigHelper::getConfig('userpanel.document_approval_customer_notification_mail_subject');
        $mail_body = ConfigHelper::getConfig('userpanel.document_approval_customer_notification_mail_body');

        $customerinfos = array();
        $mail_contacts = array();

        foreach ($docs as $docid => $doc) {
            $this->db->Execute(
                'UPDATE documents SET sdate = ?NOW?, cuserid = ?, closed = ?, confirmdate = ?,
 				adate = ?, auserid = ? WHERE id = ?',
                array(
                    $userid,
                    empty($doc['customerawaits']) ? ($userpanel ? 3 : 1) : 2,
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

            // customer awaits for signed document scan approval
            // so we should probably notify him about document confirmation
            if (!empty($mail_sender_address) && !empty($mail_subject) && !empty($mail_body)) {
                if (!isset($customer_manager)) {
                    $customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);
                }

                if (!isset($customerinfos[$doc['customerid']])) {
                    $customerinfos[$doc['customerid']] = $customer_manager->GetCustomer($doc['customerid']);
                    $mail_contacts[$doc['customerid']] = $customer_manager->GetCustomerContacts($doc['customerid'], CONTACT_EMAIL);
                }
                $customerinfo = $customerinfos[$doc['customerid']];
                $mail_recipients = $mail_contacts[$doc['customerid']];

                $mail_subject = parse_notification_mail(
                    $mail_subject,
                    array(
                        'customerinfo' => $customerinfo,
                        'document' => array(
                            'id' => $docid,
                        ),
                    )
                );
                $mail_body = parse_notification_mail(
                    $mail_body,
                    array(
                        'customerinfo' => $customerinfo,
                        'document' => array(
                            'id' => $docid,
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
                            'subject' => $mail_subject,
                            'body' => $mail_body,
                            'sender' => array(
                                'name' => $mail_sender_name,
                                'mail' => $mail_sender_address,
                            ),
                            'contenttype' => $mail_format == 'text' ? 'text/plain' : 'text/html',
                            'recipients' => $recipients,
                        ));
                        $headers = array(
                            'From' => $sender,
                            'Recipient-Name' => $customerinfo['customername'],
                            'Subject' => $mail_subject,
                            'X-LMS-Format' => $mail_format,
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
                            $lms->SendMail($destination, $headers, $mail_body);
                        }
                    }
                }
            }
        }

        $this->db->CommitTrans();
    }

    public function ArchiveDocuments(array $ids)
    {
        $userid = Auth::GetCurrentUser();

        $ids = Utils::filterIntegers($ids);
        if (empty($ids)) {
            return;
        }

        $docs = $this->db->GetCol(
            'SELECT d.id
				FROM documents d
				' . ($userid ? ' JOIN docrights r ON r.doctype = d.type' : '') . '
				WHERE d.closed > 0 AND d.archived = 0 AND d.id IN (' . implode(',', $ids) . ')
					' . ($userid ? ' AND r.userid = ' . $userid . ' AND (r.rights & ' . DOCRIGHT_ARCHIVE . ') > 0' : '')
        );
        if (empty($docs)) {
            return;
        }

        $this->db->BeginTrans();

        $this->db->Execute(
            'UPDATE documents SET archived = 1, adate = ?NOW?, auserid = ?
			WHERE id IN (' . implode(',', $docs) . ')',
            array($userid)
        );

        $this->db->CommitTrans();
    }

    public function UpdateDocumentPostAddress($docid, $customerid)
    {
        $post_addr = $this->db->GetOne('SELECT post_address_id FROM documents WHERE id = ?', array($docid));
        if ($post_addr) {
            $this->db->Execute('DELETE FROM addresses WHERE id = ?', array($post_addr));
        }

        $location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);

        $post_addr = $location_manager->GetCustomerAddress($customerid, POSTAL_ADDRESS);
        if (empty($post_addr)) {
            $this->db->Execute(
                "UPDATE documents SET post_address_id = NULL WHERE id = ?",
                array($docid)
            );
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
                @mkdir($file['path'], 0700);
                if (empty($file['tmpname'])) {
                    if (!@copy($file['name'], $file['newfile'])) {
                        $error['files'] = trans('Can\'t save file in "$a" directory!', $file['path']);
                        break;
                    }
                } elseif (!file_exists($file['newfile']) && !@rename($file['tmpname'], $file['newfile'])) {
                    $error['files'] = trans('Can\'t save file in "$a" directory!', $file['path']);
                    break;
                }
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
                        isset($file['attachmenttype']) ? $file['attachmenttype'] : 0,
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

    public function GetDocumentFullContents($id)
    {
        global $DOCTYPES;

        if ($document = $this->db->GetRow('SELECT d.id, d.number, d.cdate, d.type, d.customerid,
				d.fullnumber, n.template
			FROM documents d
			LEFT JOIN numberplans n ON (d.numberplanid = n.id)
			JOIN docrights r ON (r.doctype = d.type)
			WHERE d.id = ? AND r.userid = ? AND (r.rights & 1) = 1', array($id, Auth::GetCurrentUser()))) {
            $document['fullnumber'] = docnumber(array(
                'number' => $document['number'],
                'template' => $document['template'],
                'cdate' => $document['cdate'],
                'customerid' => $document['customerid'],
            ));

            $document['title'] = trans(
                '$a no. $b issued on $c',
                $DOCTYPES[$document['type']],
                $document['fullnumber'],
                date('Y/m/d', $document['cdate'])
            );

            $document['attachments'] = $this->db->GetAllByKey('SELECT *, type AS main FROM documentattachments WHERE docid = ?
				ORDER BY type DESC', 'id', array($id));

            foreach ($document['attachments'] as &$attachment) {
                $filename = DOC_DIR . DIRECTORY_SEPARATOR . substr($attachment['md5sum'], 0, 2)
                    . DIRECTORY_SEPARATOR . $attachment['md5sum'];
                if (file_exists($filename . '.pdf')) {
                    // try to get file from pdf document cache
                    $contents = file_get_contents($filename . '.pdf');
                    $contenttype = 'application/pdf';
                    $contentname = str_replace('.html', '.pdf', $attachment['filename']);
                } else {
                    $contents = file_get_contents($filename);
                    if (preg_match('/html/i', $attachment['contenttype'])
                        && strtolower(ConfigHelper::getConfig('phpui.document_type')) == 'pdf') {
                        $margins = explode(",", ConfigHelper::getConfig('phpui.document_margins', '10,5,15,5'));
                        if (ConfigHelper::getConfig('phpui.cache_documents')) {
                            $contents = html2pdf(
                                $contents,
                                $document['title'],
                                $document['title'],
                                $document['type'],
                                $id,
                                'P',
                                $margins,
                                'S',
                                false,
                                $attachment['md5sum']
                            );
                        } else {
                            $contents = html2pdf(
                                $contents,
                                $document['title'],
                                $document['title'],
                                $document['type'],
                                $id,
                                'P',
                                $margins,
                                'S'
                            );
                        }
                        $contenttype = 'application/pdf';
                        $contentname = str_replace('.html', '.pdf', $attachment['filename']);
                    } else {
                        $contenttype = $attachment['contenttype'];
                        $contentname = $attachment['filename'];
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
        global $LMS, $DOCTYPES;

        extract($params);

        if ($type == 'frontend') {
            $eol = '<br>';
        } else {
            $eol = PHP_EOL;
        }

        $month = sprintf('%02d', intval(date('m', $currtime)));
        $day = sprintf('%02d', intval(date('d', $currtime)));
        $year = sprintf('%04d', intval(date('Y', $currtime)));

        $from = $sender_email;

        if (!empty($sender_name)) {
            $from = "$sender_name <$from>";
        }

        foreach ($docs as $doc) {
            $document = $this->GetDocumentFullContents($doc['id']);
            if (empty($document)) {
                continue;
            }

            $custemail = (!empty($debug_email) ? $debug_email : $doc['email']);
            $body = $mail_body;
            $subject = $mail_subject;

            $body = preg_replace('/%document/', $document['fullnumber'], $body);
            $body = preg_replace('/%cdate-y/', strftime("%Y", $document['cdate']), $body);
            $body = preg_replace('/%cdate-m/', strftime("%m", $document['cdate']), $body);
            $body = preg_replace('/%cdate-d/', strftime("%d", $document['cdate']), $body);
            $body = preg_replace('/%type/', $DOCTYPES[$document['type']], $body);
            $body = preg_replace('/%today/', $year . '-' . $month . '-' . $day, $body);
            $body = str_replace('\n', "\n", $body);

            $subject = preg_replace('/%document/', $document['title'], $subject);

            $doc['name'] = '"' . $doc['name'] . '"';

            $mailto = array();
            $mailto_qp_encoded = array();
            foreach (explode(',', $custemail) as $email) {
                $mailto[] = $doc['name'] . " <$email>";
                $mailto_qp_encoded[] = qp_encode($document['name']) . " <$email>";
            }
            $mailto = implode(', ', $mailto);
            $mailto_qp_encoded = implode(', ', $mailto_qp_encoded);

            if (!$quiet || $test) {
                $msg = $document['title'] . ': ' . $mailto ;
                if ($type == 'frontend') {
                    echo htmlspecialchars($msg) . $eol;
                    flush();
                    ob_flush();
                } else {
                    echo $msg . $eol;
                }
            }

            if (!$test) {
                $files = array();
                foreach ($document['attachments'] as $attachment) {
                    $files[] = array(
                        'content_type' => $attachment['contenttype'],
                        'filename' => $attachment['filename'],
                        'data' => $attachment['contents'],
                    );
                }

                if ($extrafile) {
                    $files[] = array(
                        'content_type' => mime_content_type($extrafile),
                        'filename' => basename($extrafile),
                        'data' => file_get_contents($extrafile)
                    );
                }

                $headers = array(
                    'From' => empty($dsn_email) ? $from : $dsn_email,
                    'To' => $mailto_qp_encoded,
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
                        (isset($smtp_options) ? $smtp_options : null)
                    );

                    if (is_string($res)) {
                        $msg = trans('Error sending mail: $a', $res);
                        if ($type == 'backend') {
                            fprintf(STDERR, $msg . $eol);
                        } else {
                            echo '<span class="red">' . htmlspecialchars($msg) . '</span>' . $eol;
                            flush();
                        }
                        $status = MSG_ERROR;
                    } else {
                        $status = MSG_SENT;
                        $res = null;
                    }

                    if ($status == MSG_SENT) {
                        $this->db->Execute('UPDATE documents SET published = 1, senddate = ?NOW? WHERE id = ?', array($doc['id']));
                        $published = true;
                    }

                    if ($add_message) {
                        $this->db->Execute('UPDATE messageitems SET status = ?, error = ?
							WHERE id = ?', array($status, $res, $msgitems[$doc['customerid']][$email]));
                    }
                }
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

        $attachments = $this->db->GetAll('SELECT id, md5sum FROM documentattachments
			WHERE docid = ?', array($docid));
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
        }

        $this->db->Execute('DELETE FROM documents WHERE id = ?', array($docid));
        if ($this->syslog) {
            $args = array(
                SYSLOG::RES_DOC => $docid,
                SYSLOG::RES_CUST => $document['customerid'],
                'type' => $document['type'],
            );
            $this->syslog->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_DELETE, $args);

            foreach ($attachments as $attachment) {
                $args = array(
                    SYSLOG::RES_DOCATTACH => $attachment['id'],
                    SYSLOG::RES_DOC => $docid,
                    'md5sum' => $attachment['md5sum'],
                );
                $this->syslog->AddMessage(SYSLOG::RES_DOCATTACH, SYSLOG::OPER_DELETE, $args);
            }
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
}

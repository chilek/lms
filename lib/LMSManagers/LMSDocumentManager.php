<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2013 LMS Developers
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
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSDocumentManager extends LMSManager implements LMSDocumentManagerInterface
{

    public function GetDocuments($customerid = NULL, $limit = NULL)
    {
        if (!$customerid)
            return NULL;

        if ($list = $this->db->GetAll('SELECT c.docid, d.number, d.type, c.title, c.fromdate, c.todate, 
			c.description, c.filename, c.md5sum, c.contenttype, n.template, d.closed, d.cdate
			FROM documentcontents c
			JOIN documents d ON (c.docid = d.id)
			JOIN docrights r ON (d.type = r.doctype AND r.userid = ? AND (r.rights & 1) = 1)
			LEFT JOIN numberplans n ON (d.numberplanid = n.id)
			WHERE d.customerid = ?
			ORDER BY cdate', array($this->auth->id, $customerid))) {
            if ($limit) {
                $index = (sizeof($list) - $limit) > 0 ? sizeof($list) - $limit : 0;
                for ($i = $index; $i < sizeof($list); $i++)
                    $result[] = $list[$i];

                return $result;
            } else
                return $list;
        }
    }

    public function GetNumberPlans($doctype = NULL, $cdate = NULL, $division = NULL, $next = true)
    {
        if (is_array($doctype))
            $where[] = 'doctype IN (' . implode(',', $doctype) . ')';
        else if ($doctype)
            $where[] = 'doctype = ' . intval($doctype);

        if ($division)
            $where[] = 'id IN (SELECT planid FROM numberplanassignments
                WHERE divisionid = ' . intval($division) . ')';

        if (!empty($where))
            $where = ' WHERE ' . implode(' AND ', $where);

        $list = $this->db->GetAllByKey('
				SELECT id, template, isdefault, period, doctype
				FROM numberplans' . $where . '
				ORDER BY id', 'id');

        if ($list && $next) {
            if ($cdate)
                list($curryear, $currmonth) = explode('/', $cdate);
            else {
                $curryear = date('Y');
                $currmonth = date('n');
            }
            switch ($currmonth) {
                case 1: case 2: case 3: $startq = 1;
                    $starthy = 1;
                    break;
                case 4: case 5: case 6: $startq = 4;
                    $starthy = 1;
                    break;
                case 7: case 8: case 9: $startq = 7;
                    $starthy = 7;
                    break;
                case 10: case 11: case 12: $startq = 10;
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

            $max = $this->db->GetAllByKey('SELECT numberplanid AS id, MAX(number) AS max 
					    FROM documents LEFT JOIN numberplans ON (numberplanid = numberplans.id)
					    WHERE '
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
						WHEN ' . DAILY . ' THEN ' . $dayend . ' ELSE 4294967296 END)
					    GROUP BY numberplanid', 'id');

            foreach ($list as $idx => $item)
                if (isset($max[$item['id']]['max']))
                    $list[$idx]['next'] = $max[$item['id']]['max'] + 1;
                else
                    $list[$idx]['next'] = 1;
        }

        return $list;
    }

    public function GetNewDocumentNumber($doctype = NULL, $planid = NULL, $cdate = NULL)
    {
        if ($planid)
            $period = $this->db->GetOne('SELECT period FROM numberplans WHERE id=?', array($planid));
        else
            $planid = 0;

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
                $currmonth = date('n');
                switch (date('n')) {
                    case 1: case 2: case 3: $startq = 1;
                        break;
                    case 4: case 5: case 6: $startq = 4;
                        break;
                    case 7: case 8: case 9: $startq = 7;
                        break;
                    case 10: case 11: case 12: $startq = 10;
                        break;
                }
                $start = mktime(0, 0, 0, $startq, 1, date('Y', $cdate));
                $end = mktime(0, 0, 0, $startq + 3, 1, date('Y', $cdate));
                break;
            case HALFYEARLY:
                $currmonth = date('n');
                switch (date('n')) {
                    case 1: case 2: case 3: case 4: case 5: case 6: $startq = 1;
                        break;
                    case 7: case 8: case 9: case 10: case 11: case 12: $startq = 7;
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
                $number = $this->db->GetOne('SELECT MAX(number) FROM documents 
						WHERE type = ? AND numberplanid = ?', array($doctype, $planid));

                return $number ? ++$number : 1;
                break;
        }

        $number = $this->db->GetOne('
				SELECT MAX(number) 
				FROM documents 
				WHERE cdate >= ? AND cdate < ? AND type = ? AND numberplanid = ?', array($start, $end, $doctype, $planid));

        return $number ? ++$number : 1;
    }

    public function DocumentExists($number, $doctype = NULL, $planid = 0, $cdate = NULL)
    {
        if ($planid)
            $period = $this->db->GetOne('SELECT period FROM numberplans WHERE id=?', array($planid));

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
                $currmonth = date('n');
                switch (date('n')) {
                    case 1: case 2: case 3: $startq = 1;
                        break;
                    case 4: case 5: case 6: $startq = 4;
                        break;
                    case 7: case 8: case 9: $startq = 7;
                        break;
                    case 10: case 11: case 12: $startq = 10;
                        break;
                }
                $start = mktime(0, 0, 0, $startq, 1, date('Y', $cdate));
                $end = mktime(0, 0, 0, $startq + 3, 1, date('Y', $cdate));
                break;
            case HALFYEARLY:
                $currmonth = date('n');
                switch (date('n')) {
                    case 1: case 2: case 3: case 4: case 5: case 6: $startq = 1;
                        break;
                    case 7: case 8: case 9: case 10: case 11: case 12: $startq = 7;
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
                return $this->db->GetOne('SELECT number FROM documents 
						WHERE type = ? AND number = ? AND numberplanid = ?', array($doctype, $number, $planid)) ? TRUE : FALSE;
                break;
        }

        return $this->db->GetOne('SELECT number FROM documents 
				WHERE cdate >= ? AND cdate < ? AND type = ? AND number = ? AND numberplanid = ?', array($start, $end, $doctype, $number, $planid)) ? TRUE : FALSE;
    }

}

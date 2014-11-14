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
 * LMSEventManager
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSEventManager extends LMSManager implements LMSEventManagerInterface
{

    public function EventSearch($search, $order = 'date,asc', $simple = false)
    {
        list($order, $direction) = sscanf($order, '%[^,],%s');

        (strtolower($direction) != 'desc') ? $direction = 'ASC' : $direction = 'DESC';

        switch ($order) {
            default:
                $sqlord = ' ORDER BY date ' . $direction . ', begintime ' . $direction;
                break;
        }

        $datefrom = intval($search['datefrom']);
        $dateto = intval($search['dateto']);

        $list = $this->db->GetAll(
                'SELECT events.id AS id, title, description, date, begintime, enddate, endtime, customerid, closed, '
                . $this->db->Concat('customers.lastname', "' '", 'customers.name') . ' AS customername
			FROM events
			LEFT JOIN customers ON (customerid = customers.id)
			WHERE (private = 0 OR (private = 1 AND userid = ?)) '
                . ($datefrom ? " AND (date >= $datefrom OR (enddate <> 0 AND enddate >= $datefrom))" : '')
                . ($dateto ? " AND (date <= $dateto OR (enddate <> 0 AND enddate <= $dateto))" : '')
                . (!empty($search['customerid']) ? ' AND customerid = ' . intval($search['customerid']) : '')
                . (!empty($search['title']) ? ' AND title ?LIKE? ' . $this->db->Escape('%' . $search['title'] . '%') : '')
                . (!empty($search['description']) ? ' AND description ?LIKE? ' . $this->db->Escape('%' . $search['description'] . '%') : '')
                . (!empty($search['note']) ? ' AND note ?LIKE? ' . $this->db->Escape('%' . $search['note'] . '%') : '')
                . $sqlord, array($this->auth->id));

        if ($search['userid'])
            if (is_array($search['userid']))
                $users = array_filter($search['userid'], 'is_natural');
            else
                $users = array(intval($search['userid']));
        else
            $users = array();

        $list2 = $list3 = array();
        if ($list) {
            foreach ($list as $idx => $row) {
                if (!$simple)
                    $row['userlist'] = $this->db->GetAll('SELECT userid AS id, users.name
						FROM eventassignments, users
						WHERE userid = users.id AND eventid = ? ', array($row['id']));
                $endtime = $row['endtime'];

                $userfilter = false;
                if (!empty($users) && !empty($row['userlist']))
                    foreach ($row['userlist'] as $user)
                        if (in_array($user['id'], $users))
                            $userfilter = true;

                if ($row['enddate']) {
                    $days = ($row['enddate'] - $row['date']) / 86400;
                    $row['endtime'] = 0;
                    if ((!$datefrom || $row['date'] >= $datefrom) &&
                            (!$dateto || $row['date'] <= $dateto)) {
                        $list2[] = $row;
                        if ($userfilter)
                            $list3[] = $row;
                    }

                    while ($days) {
                        if ($days == 1)
                            $row['endtime'] = $endtime;
                        $row['date'] += 86400;

                        if ((!$datefrom || $row['date'] >= $datefrom) &&
                                (!$dateto || $row['date'] <= $dateto)) {
                            $list2[] = $row;
                            if ($userfilter)
                                $list3[] = $row;
                        }

                        $days--;
                    }
                } else
                if ((!$datefrom || $row['date'] >= $datefrom) &&
                        (!$dateto || $row['date'] <= $dateto)) {
                    $list2[] = $row;
                    if ($userfilter)
                        $list3[] = $row;
                }
            }

            if ($search['userid'])
                return $list3;
            else
                return $list2;
        }
    }

}

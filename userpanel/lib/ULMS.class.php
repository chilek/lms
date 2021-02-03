<?php

/*
 *  LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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
 *
*/

// Extending LMS class for Userpanel-specific functions
class ULMS extends LMS
{
    public function docnumber($id)
    {
        if ($doc = $this->DB->GetRow('SELECT number, cdate, numberplans.template
					FROM documents
					LEFT JOIN numberplans ON (numberplanid = numberplans.id)
					WHERE documents.id = ?', array($id))) {
            return docnumber(array(
                'number' => $doc['number'],
                'template' => $doc['template'],
                'cdate' => $doc['cdate'],
            ));
        } else {
            return null;
        }
    }

    public function GetCustomer($id, $short = false)
    {
        if (($result = $this->DB->GetRow('SELECT c.*, '.$this->DB->Concat('UPPER(c.lastname)', "' '", 'c.name').' AS customername
			FROM customeraddressview c WHERE c.id = ?', array($id)))) {
            if (!$short) {
                $result['balance'] = $this->GetCustomerBalance($result['id']);
                $result['bankaccount'] = bankaccount($result['id']);

                $result['contacts'] = $this->DB->GetAllByKey(
                    'SELECT id, contact AS phone, name
					FROM customercontacts WHERE customerid = ? AND (type & ?) > 0 AND (type & ?) = 0
					ORDER BY id',
                    'id',
                    array($id, CONTACT_MOBILE | CONTACT_FAX | CONTACT_LANDLINE, CONTACT_DISABLED)
                );
                $result['emails'] = $this->DB->GetAllByKey(
                    'SELECT id, contact AS email, name
					FROM customercontacts WHERE customerid = ? AND (type & ?) > 0 AND (type & ?) = 0
					ORDER BY id',
                    'id',
                    array($id, CONTACT_EMAIL | CONTACT_DISABLED, CONTACT_DISABLED)
                );
                $result['ims'] = $this->DB->GetAllByKey(
                    'SELECT id, contact AS uid, name, type
					FROM customercontacts WHERE customerid = ? AND (type & ?) > 0 AND (type & ?) = 0
					ORDER BY id',
                    'id',
                    array($id, CONTACT_IM | CONTACT_DISABLED, CONTACT_DISABLED)
                );
                $result['accounts'] = $this->DB->GetAllByKey(
                    'SELECT id, contact AS account, name
					FROM customercontacts WHERE customerid = ? AND (type & ?) = ? ORDER BY id',
                    'id',
                    array($id, CONTACT_BANKACCOUNT | CONTACT_INVOICES | CONTACT_DISABLED, CONTACT_BANKACCOUNT | CONTACT_INVOICES)
                );
            }

            return $result;
        } else {
            return null;
        }
    }

    public function UpdateCustomerPIN($id, $pin)
    {
        $res = $this->DB->Execute(
            'UPDATE customers SET pin = ? WHERE id = ?',
            array($pin, $id)
        );
        $_SESSION['session_passwd'] = $pin;
        return $res;
    }

    public function GetCustomerMessage($id)
    {
        return $this->DB->GetOne('SELECT message FROM customers WHERE id=?', array($id));
    }

    public function GetCustomerTickets($id)
    {
        $queues = array();
        if (ConfigHelper::getConfig('userpanel.tickets_from_selected_queues')) {
            $queues = $this->DB->GetCol('SELECT id FROM rtqueues
				WHERE id IN (' . str_replace(';', ',', ConfigHelper::getConfig('userpanel.queues')) . ')');
        }
        $sources = str_replace(';', ',', ConfigHelper::getConfig('userpanel.visible_ticket_sources'));
        $tickets = $this->DB->GetAll('SELECT * FROM rttickets WHERE customerid=?'
            . (isset($queues) && !empty($queues) ? ' AND queueid IN (' . implode(',', $queues) . ')' : '')
            . ('AND deleted = 0')
            . ' AND source IN (' . $sources . ')'
            . ' ORDER BY createtime DESC', array($id));
        if (!empty($tickets)) {
            foreach ($tickets as &$ticket) {
                $ticket['queuename'] = $this->DB->GetOne('SELECT name FROM rtqueues WHERE id = ?', array($ticket['queueid']));
                $ticket['lastmod'] = $this->DB->GetOne(
                    'SELECT MAX(createtime) FROM rtmessages WHERE ticketid = ?',
                    array($ticket['queueid'])
                );
            }
        }
        return $tickets;
    }

    public function GetTicketContents($id, $short = false)
    {
        global $RT_STATES;

        $ticket = $this->DB->GetRow('SELECT rttickets.id AS ticketid, queueid, rtqueues.name AS queuename,
				    requestor, state, owner, customerid, cause, source, '
                    .$this->DB->Concat('UPPER(customers.lastname)', "' '", 'customers.name').' AS customername,
				    vusers.name AS ownername, createtime, resolvetime, subject
				FROM rttickets
				LEFT JOIN rtqueues ON (queueid = rtqueues.id)
				LEFT JOIN vusers ON (owner = vusers.id)
				LEFT JOIN customers ON (customers.id = customerid)
				WHERE rttickets.id = ?', array($id));

        $ticket['categories'] = $this->DB->GetAllByKey('SELECT categoryid AS id, c.name
			FROM rtticketcategories tc
			JOIN rtcategories c ON c.id = tc.categoryid
			WHERE ticketid = ?', 'id', array($id));
        $ticket['categorynames'] = empty($ticket['categories']) ? array() : array_map(function ($elem) {
                return $elem['name'];
        }, $ticket['categories']);

        $ticket['messages'] = $this->DB->GetAll('SELECT rtmessages.id AS id, mailfrom, subject, body, createtime, '
                    .$this->DB->Concat('UPPER(customers.lastname)', "' '", 'customers.name').' AS customername,
				    userid, vusers.name AS username, customerid
				FROM rtmessages
				LEFT JOIN customers ON (customers.id = customerid)
				LEFT JOIN vusers ON (vusers.id = userid)
				WHERE ticketid = ? AND rtmessages.type = ? AND rtmessages.deleted = 0
				ORDER BY createtime DESC', array($id, RTMESSAGE_REGULAR));

        foreach ($ticket['messages'] as &$message) {
            $message['attachments'] = $this->DB->GetAll(
                'SELECT filename, contenttype FROM rtattachments WHERE messageid = ?',
                array($message['id'])
            );
        }

        $ticket['status'] = $RT_STATES[$ticket['state']];
        $ticket['queuename'] = $this->DB->GetOne('SELECT name FROM rtqueues WHERE id = ?', array($ticket['queueid']));
        $ticket['lastmod'] = $this->DB->GetOne('SELECT MAX(createtime) FROM rtmessages WHERE ticketid = ?', array($id));

        list($ticket['requestoremail']) = sscanf($ticket['requestor'], "<%[^>]");

        return $ticket;
    }
}

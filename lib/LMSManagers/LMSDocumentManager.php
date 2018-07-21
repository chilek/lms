<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2017 LMS Developers
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

    public function GetDocuments($customerid = NULL, $limit = NULL)
    {
        if (!$customerid)
            return NULL;

        if ($list = $this->db->GetAll('SELECT c.docid, d.number, d.type, c.title, c.fromdate, c.todate,
			c.description, n.template, d.closed, d.cdate, u.name AS username, d.sdate, u2.name AS cusername,
			d.type AS doctype, d.template AS doctemplate, reference
			FROM documentcontents c
			JOIN documents d ON (c.docid = d.id)
			JOIN docrights r ON (d.type = r.doctype AND r.userid = ? AND (r.rights & 1) = 1)
			JOIN vusers u ON u.id = d.userid
			LEFT JOIN vusers u2 ON u2.id = d.cuserid
			LEFT JOIN numberplans n ON (d.numberplanid = n.id)
			WHERE d.customerid = ?
			ORDER BY cdate', array(Auth::GetCurrentUser(), $customerid))) {
			foreach ($list as &$doc) {
				$doc['attachments'] = $this->db->GetAll('SELECT * FROM documentattachments
					WHERE docid = ? ORDER BY main DESC, filename', array($doc['docid']));
				if (!empty($doc['reference']))
					$doc['reference'] = $this->db->GetRow('SELECT id, type, fullnumber, cdate FROM documents
						WHERE id = ?', array($doc['reference']));
			}
            if ($limit) {
                $index = (sizeof($list) - $limit) > 0 ? sizeof($list) - $limit : 0;
                $result = array();

                for ($i = $index; $i < sizeof($list); $i++)
                    $result[] = $list[$i];

                return $result;
            } else
                return $list;
        }
    }

	public function GetDocumentList($order='cdate,asc', $search) {
		$type = isset($search['type']) ? $search['type'] : NULL;
		$customer = isset($search['customer']) ? $search['customer'] : NULL;
		$numberplan = isset($search['numberplan']) ? $search['numberplan'] : NULL;
		$usertype = isset($search['usertype']) ? $search['usertype'] : 'creator';
		$userid = isset($search['userid']) ? $search['userid'] : NULL;
		$periodtype = isset($search['periodtype']) ? $search['periodtype'] : 'creationdate';
		$from = isset($search['from']) ? $search['from'] : 0;
		$to = isset($search['to']) ? $search['to'] : 0;
		$status = isset($search['status']) ? $search['status'] : -1;

		if($order=='')
			$order='cdate,asc';

		list($order,$direction) = sscanf($order, '%[^,],%s');
		($direction=='desc') ? $direction = 'desc' : $direction = 'asc';

		switch($order)
		{
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
			case 'fromdate':
				$datefield = 'documentcontents.fromdate';
				break;
			case 'todate':
				$datefield = 'documentcontents.todate';
				break;
			default:
				$datefield = 'd.cdate';
		}

		$list = $this->db->GetAll('SELECT docid, d.number, d.type, title, d.cdate, u.name AS username, u.lastname, fromdate, todate, description, 
				numberplans.template, d.closed, d.name, d.customerid, d.sdate, d.cuserid, u2.name AS cusername, u2.lastname AS clastname,
				d.reference, i.senddocuments
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
			LEFT JOIN (
				SELECT DISTINCT a.customerid FROM customerassignments a
				JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
				WHERE e.userid = lms_current_user()
			) e ON (e.customerid = d.customerid)
			WHERE e.customerid IS NULL '
			.($customer ? 'AND d.customerid = '.intval($customer) : '')
			.($type ? ' AND d.type = '.intval($type) : '')
			. ($userid ? ' AND ' . $userfield . ' = ' . intval($userid) : '')
			. ($numberplan ? ' AND d.numberplanid = ' . intval($numberplan) : '')
			.($from ? ' AND ' . $datefield . ' >= '.intval($from) : '')
			.($to ? ' AND ' . $datefield . ' <= '.intval($to) : '')
			.($status == -1 ? '' : ' AND d.closed = ' . intval($status))
			.$sqlord, array(Auth::GetCurrentUser()));

		if (empty($list))
			$list = array();
		else
			foreach ($list as &$document) {
				$document['attachments'] = $this->db->GetAll('SELECT id, filename, md5sum, contenttype, main
				FROM documentattachments WHERE docid = ? ORDER BY main DESC, filename', array($document['docid']));
				if (!empty($document['reference'])) {
					$document['reference'] = $this->db->GetRow('SELECT id, type, fullnumber, cdate FROM documents
					WHERE id = ?', array($document['reference']));
				}
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
	public function GetNumberPlans($properties) {
		extract($properties);
		if (!isset($doctype))
			$doctype = null;
		if (!isset($cdate))
			$cdate = null;
		if (!isset($division))
			$division = null;
		if (!isset($next))
			$next = true;
		if (!isset($customerid))
			$customerid = null;

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

			foreach ($list as &$item) {
				$max = $this->db->GetOne('SELECT MAX(number) AS max 
					FROM documents
					LEFT JOIN numberplans ON (numberplanid = numberplans.id)
					WHERE numberplanid = ? AND ' . (strpos($item['template'], '%C') === false || empty($customerid)
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
					array($item['id']));

				if (empty($max))
					$item['next'] = 1;
				else
					$item['next'] = $max + 1;
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
	public function GetNewDocumentNumber($properties) {
		extract($properties);
		if (!isset($doctype))
			$doctype = null;
		if (!isset($planid))
			$planid = null;
		if (!isset($cdate))
			$cdate = null;
		if (!isset($customerid))
			$customerid = null;

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
					WHERE type = ? AND ' . ($planid ? 'numberplanid = ' . intval($planid) : 'numberplanid IS NULL')
					. (!isset($numtemplate) || strpos($numtemplate, '%C') === false || empty($customerid)
						? '' : ' AND customerid = ' . intval($customerid)),
					array($doctype));

                return $number ? ++$number : 1;
                break;
        }

        $number = $this->db->GetOne('
				SELECT MAX(number) 
				FROM documents 
				WHERE cdate >= ? AND cdate < ? AND type = ? AND ' . ($planid ? 'numberplanid = ' . intval($planid) : 'numberplanid IS NULL')
				. (!isset($numtemplate) || strpos($numtemplate, '%C') === false || empty($customerid)
					? '' : ' AND customerid = ' . intval($customerid)),
				array($start, $end, $doctype, $planid));

        return $number ? ++$number : 1;
    }

	/*
	 \param array $properties - associative array with function parameters:
		number: document number
		doctype: document type
		planid: id of number plan
		cdate: document creation date
	*/
	public function DocumentExists($properties) {
		extract($properties);
		if (!isset($doctype))
			$doctype = null;
		if (!isset($planid))
			$planid = 0;
		if (!isset($cdate))
			$cdate = null;
		if (!isset($customerid))
			$customerid = null;

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
                switch (date('n')) {
                    case 1: case 2: case 3: case 4: case 5: case 6: $startq = 1;
                        break;
                    case 7: case 8: case 9: case 10: case 11: case 12: $startq = 7;
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
                return $this->db->GetOne('SELECT id FROM documents
					WHERE type = ? AND number = ? AND numberplanid = ?'
					. (!isset($numtemplate) || strpos($numtemplate, '%C') === false || empty($customerid)
						? '' : ' AND customerid = ' . intval($customerid)),
					array($doctype, $number, $planid));
                break;
        }

		return $this->db->GetOne('SELECT id FROM documents
			WHERE cdate >= ? AND cdate < ? AND type = ? AND number = ? AND numberplanid = ?'
			. (!isset($numtemplate) || strpos($numtemplate, '%C') === false || empty($customerid)
				? '' : ' AND customerid = ' . intval($customerid)),
			array($start, $end, $doctype, $number, $planid));
    }

    public function CommitDocuments(array $ids) {
		$userid = Auth::GetCurrentUser();

		$ids = array_filter($ids, 'intval');
		if (empty($ids))
			return;

		$docs = $this->db->GetAllByKey('SELECT d.id, d.customerid, dc.fromdate AS datefrom,
					d.reference, d.commitflags
				FROM documents d
				JOIN documentcontents dc ON dc.docid = d.id
				JOIN docrights r ON r.doctype = d.type
				WHERE d.closed = 0 AND d.id IN (' . implode(',', $ids) . ') AND r.userid = ? AND (r.rights & 4) > 0',
			'id', array($userid));
		if (empty($docs))
			return;

		$document_manager = new LMSFinanceManager($this->db, $this->auth, $this->cache, $this->syslog);

		$this->db->BeginTrans();

		foreach ($docs as $docid => $doc) {
			$this->db->Execute('UPDATE documents SET sdate=?NOW?, cuserid=?, closed=1 WHERE id=?',
				array($userid, $docid));

			$args = array(
				'reference' => $doc['reference'],
				'datefrom' => $doc['datefrom'],
				'customerid' => $doc['customerid'],
				'existing_assignments' => array(
					'operation' => $doc['commitflags'] & 15,
					'reference_document_limit' => $doc['commitflags'] & 16 ? 1 : null,
				),
			);
			$document_manager->UpdateExistingAssignments($args);

			$this->db->Execute('UPDATE assignments SET commited = 1 WHERE docid = ? AND commited = 0',
				array($docid));
		}

		$this->db->CommitTrans();
	}

	public function UpdateDocumentPostAddress($docid, $customerid) {
		$post_addr = $this->db->GetOne('SELECT post_address_id FROM documents WHERE id = ?', array($docid));
		if ($post_addr)
			$this->db->Execute('DELETE FROM addresses WHERE id = ?', array($post_addr));

		$location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);

		$post_addr = $location_manager->GetCustomerAddress($customerid, POSTAL_ADDRESS);
		if (empty($post_addr))
			$this->db->Execute("UPDATE documents SET post_address_id = NULL WHERE id = ?",
				array($docid));
		else
			$this->db->Execute('UPDATE documents SET post_address_id = ? WHERE id = ?',
				array($location_manager->CopyAddress($post_addr), $docid));
	}

	public function DeleteDocumentAddresses($docid) {
		// deletes addresses' records which are bound to given document
		$addresses = $this->db->GetRow('SELECT recipient_address_id, post_address_id FROM documents WHERE id = ?',
			array($docid));
		foreach ($addresses as $address_id)
			if (!empty($address_id))
				$this->db->Execute('DELETE FROM addresses WHERE id = ?', array($address_id));
	}

	public function DocumentAttachmentExists($md5sum) {
		return $this->db->GetOne('SELECT docid FROM documentattachments WHERE md5sum = ?',
			array($md5sum));
	}

	public function GetDocumentFullContents($id) {
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

			$document['title'] = trans('$a no. $b issued on $c',
				$DOCTYPES[$document['type']], $document['fullnumber'], date('Y/m/d', $document['cdate']));

			$document['attachments'] = $this->db->GetAllByKey('SELECT * FROM documentattachments WHERE docid = ?
				ORDER BY main DESC', 'id', array($id));

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
						if (ConfigHelper::getConfig('phpui.cache_documents'))
							$contents = html2pdf($contents, $document['title'], $document['title'], $document['type'], $id,
								'P', $margins, 'S', false, $attachment['md5sum']);
						else
							$contents = html2pdf($contents, $document['title'], $document['title'], $document['type'], $id,
								'P', $margins, 'S');
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

	public function SendDocuments($docs, $type, $params) {
		global $LMS;

		extract($params);

		if ($type == 'frontend')
			$eol = '<br>';
		else
			$eol = PHP_EOL;

		$month = sprintf('%02d', intval(date('m', $currtime)));
		$day = sprintf('%02d', intval(date('d', $currtime)));
		$year = sprintf('%04d', intval(date('Y', $currtime)));

		$from = $sender_email;

		if (!empty($sender_name))
			$from = "$sender_name <$from>";

		foreach ($docs as $doc) {
			$document = $this->GetDocumentFullContents($doc['id']);
			if (empty($document))
				continue;

			$custemail = (!empty($debug_email) ? $debug_email : $doc['email']);
			$body = $mail_body;
			$subject = $mail_subject;

			$body = preg_replace('/%document/', $document['title'], $body);
			$body = str_replace('\n', "\n", $body);
			$body = preg_replace('/%today/', $year . '-' . $month . '-' . $day, $body);
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
				} else
					echo $msg . $eol;
			}

			if (!$test) {
				$files = array();
				foreach ($document['attachments'] as $attachment)
					$files[] = array(
						'content_type' => $attachment['contenttype'],
						'filename' => $attachment['filename'],
						'data' => $attachment['contents'],
					);

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

				if (!empty($dsn_email))
					$headers['Delivery-Status-Notification-To'] = $dsn_email;

				if (!empty($notify_email))
					$headers['Cc'] = $notify_email;

				if (isset($mail_format) && $mail_format == 'html')
					$headers['X-LMS-Format'] = 'html';

				if ($add_message) {
					$this->db->Execute('INSERT INTO messages (subject, body, cdate, type, userid)
						VALUES (?, ?, ?NOW?, ?, ?)',
						array($subject, $body, MSG_MAIL, Auth::GetCurrentUser()));
					$msgid = $this->db->GetLastInsertID('messages');
					foreach (explode(',', $custemail) as $email) {
						$this->db->Execute('INSERT INTO messageitems (messageid, customerid, destination, lastdate, status)
							VALUES (?, ?, ?, ?NOW?, ?)',
							array($msgid, $doc['customerid'], $email, MSG_NEW));
						$msgitemid = $this->db->GetLastInsertID('messageitems');
						if (!isset($msgitems[$doc['customerid']]))
							$msgitems[$doc['customerid']] = array();
						$msgitems[$doc['customerid']][$email] = $msgitemid;
					}
				}

				foreach (explode(',', $custemail) as $email) {
					if ($add_message && (!empty($dsn_email) || !empty($mdn_email))) {
						$headers['X-LMS-Message-Item-Id'] = $msgitems[$doc['customerid']][$email];
						$headers['Message-ID'] = '<messageitem-' . $headers['X-LMS-Message-Item-Id'] . '@rtsystem.' . gethostname() . '>';
					}

					$res = $LMS->SendMail($email . ',' . $notify_email, $headers, $body,
						$files, null, (isset($smtp_options) ? $smtp_options : null));

					if (is_string($res)) {
						$msg = trans('Error sending mail: $a', $res);
						if ($type == 'backend')
							fprintf(STDERR, $msg . $eol);
						else {
							echo '<span class="red">' . htmlspecialchars($msg) . '</span>' . $eol;
							flush();
						}
						$status = MSG_ERROR;
					} else {
						$status = MSG_SENT;
						$res = NULL;
					}

					if ($status == MSG_SENT) {
						$this->db->Execute('UPDATE documents SET published = 1 WHERE id = ?', array($doc['id']));
						$published = true;
					}

					if ($add_message)
						$this->db->Execute('UPDATE messageitems SET status = ?, error = ?
							WHERE id = ?', array($status, $res, $msgitems[$doc['customerid']][$email]));
				}
			}
		}
	}
}

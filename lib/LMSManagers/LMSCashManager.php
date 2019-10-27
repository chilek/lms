<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2019 LMS Developers
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
 * LMSCashManager
 *
 */
class LMSCashManager extends LMSManager implements LMSCashManagerInterface
{
    /**
     * Returns cash
     *
     * @param int $id Cash id
     * @return array Cash data
     */
    public function GetCashByID($id)
    {
        return $this->db->GetRow('SELECT time, userid, value, taxid, customerid, comment
			FROM cash WHERE id=?', array($id));
    }

    /**
     * Parses import file
     *
     * @global LMS $LMS
     * @param array $file Import file information
     * @return array Invalid import file rows
     */
    public function CashImportParseFile($filename, $contents, $patterns, $quiet = true)
    {
        global $LMS;

        $file = preg_split('/\r?\n/', $contents);
        $patterns_cnt = isset($patterns) ? count($patterns) : 0;
        $ln = 0;
        $sum = array();
        $data = array();
        $syslog_records = array();

        foreach ($file as $line) {
            $id = null;
            $count = 0;
            $ln++;
            $is_sum = false;

            if ($patterns_cnt) {
                foreach ($patterns as $idx => $pattern) {
                    $theline = $line;

                    if (strtoupper($pattern['encoding']) != 'UTF-8') {
                        if (strtoupper($pattern['encoding']) == 'MAZOVIA') {
                            $theline = mazovia_to_utf8($theline);
                        } else {
                            $theline = @iconv($pattern['encoding'], 'UTF-8//TRANSLIT', $theline);
                        }
                    }

                    if (preg_match($pattern['pattern'], $theline, $matches)) {
                        break;
                    }
                    if (isset($pattern['pattern_sum']) && preg_match($pattern['pattern_sum'], $theline, $matches)) {
                        $is_sum = true;
                        break;
                    }
                    $count++;
                }
            }

            $hook_data = $LMS->executeHook(
                'cashimport_error_before_submit',
                compact("pattern", "count", "patterns_cnt", "error", "line", "theline", "ln")
            );
            extract($hook_data);

            // line isn't matching to any pattern
            if ($count == $patterns_cnt) {
                if (trim($line) != '') {
                    $error['lines'][$ln] = $patterns_cnt == 1 ? $theline : $line;
                }
                continue; // go to next line
            }

            if ($is_sum) {
                $sum = $matches;
                continue;
            }

            $name = isset($matches[$pattern['pname']]) ? trim($matches[$pattern['pname']]) : '';
            $customername = preg_replace('/[\s]{2,}/', ' ', $name);
            $lastname = isset($matches[$pattern['plastname']]) ? trim($matches[$pattern['plastname']]) : '';
            $comment = isset($matches[$pattern['pcomment']]) ? trim($matches[$pattern['pcomment']]) : '';
            $time = isset($matches[$pattern['pdate']]) ? trim($matches[$pattern['pdate']]) : '';
            $value = str_replace(',', '.', isset($matches[$pattern['pvalue']]) ? preg_replace('/[\s]/', '', $matches[$pattern['pvalue']]) : '');
            $srcaccount = isset($matches[$pattern['srcaccount']]) ? preg_replace('/[\s]/', '', $matches[$pattern['srcaccount']]) : '';
            $dstaccount = isset($matches[$pattern['dstaccount']]) ? preg_replace('/[\s]/', '', $matches[$pattern['dstaccount']]) : '';

            if (!$pattern['pid']) {
                if (!empty($pattern['pid_regexp'])) {
                    $regexp = $pattern['pid_regexp'];
                } else {
                    $regexp = '/.*ID[:\-\/]([0-9]{0,4}).*/i';
                }

                if (preg_match($regexp, $theline, $matches)) {
                    $id = $matches[1];
                }
            } else {
                $id = isset($matches[$pattern['pid']]) ? intval(preg_replace('/\s+/', '', $matches[$pattern['pid']])) : null;
            }

            // seek invoice number
            if (!$id && !empty($pattern['invoice_regexp'])) {
                if (preg_match($pattern['invoice_regexp'], $theline, $matches)) {
                    $invid = $matches[$pattern['pinvoice_number']];
                    $invyear = $matches[$pattern['pinvoice_year']];
                    $invmonth = !empty($pattern['pinvoice_month']) && $pattern['pinvoice_month'] > 0 ? intval($matches[$pattern['pinvoice_month']]) : 1;

                    if ($invid && $invyear) {
                        $from = mktime(0, 0, 0, $invmonth, 1, $invyear);
                        $to = mktime(0, 0, 0, !empty($pattern['pinvoice_month']) && $pattern['pinvoice_month'] > 0 ? $invmonth + 1 : 13, 1, $invyear);
                        $id = $this->db->GetOne(
                            'SELECT customerid FROM documents
								WHERE number=? AND cdate>? AND cdate<? AND type IN (?,?)',
                            array($invid, $from, $to, DOC_INVOICE, DOC_CNOTE)
                        );
                    }
                }
            }

            // seek by explicitly given source or destination customer account numbers
            if (!$id) {
                if (!empty($dstaccount)) {
                    $id = $this->db->GetOne(
                        'SELECT customerid FROM customercontacts
						WHERE contact = ? AND (type & ?) = ?',
                        array($dstaccount, CONTACT_BANKACCOUNT | CONTACT_INVOICES | CONTACT_DISABLED,
                        CONTACT_BANKACCOUNT | CONTACT_INVOICES)
                    );
                } elseif (!empty($srcaccount)) {
                    $id = $this->db->GetOne(
                        'SELECT customerid FROM customercontacts
						WHERE contact = ? AND (type & ?) = ?',
                        array($srcaccount, CONTACT_BANKACCOUNT | CONTACT_INVOICES | CONTACT_DISABLED,
                        CONTACT_BANKACCOUNT)
                    );
                }
            }

            if (!$id && $name && $lastname) {
                $uids = $this->db->GetCol(
                    'SELECT id FROM customers WHERE UPPER(lastname)=UPPER(?) and UPPER(name)=UPPER(?)',
                    array($lastname, $name)
                );
                if (count($uids) == 1) {
                    $id = $uids[0];
                }
            } elseif ($id && (!$name || !$lastname)) {
                if ($tmp = $this->db->GetRow('SELECT id, lastname, name FROM customers WHERE '
                    . (isset($pattern['extid']) && $pattern['extid'] ? 'ext' : '') . 'id = ?', array($id))) {
                    if (isset($pattern['extid']) && $pattern['extid']) {
                        $id = $tmp['id'];
                    }
                    $lastname = $tmp['lastname'];
                    $name = $tmp['name'];
                } else {
                    $id = null;
                }
            }

            if ($id && !$this->db->GetOne('SELECT id FROM customers WHERE id = ?', array($id))) {
                $id = null;
            }

            if ($time) {
                if (preg_match($pattern['date_regexp'], $time, $date)) {
                    $time = mktime(
                        0,
                        0,
                        0,
                        $date[$pattern['pmonth']],
                        $date[$pattern['pday']],
                        $date[$pattern['pyear']]
                    );
                } elseif (!is_numeric($time)) {
                    $time = time();
                }
                if (isset($pattern['date_hook'])) {
                    $time = $pattern['date_hook']($time, $_FILES['file']['name']);
                }
            } else {
                $time = time();
            }

            $hook_data = $LMS->executeHook(
                'cashimport_extra_filter_before_submit',
                compact("id", "pattern", "comment", "theline", "ln", "patterns_cnt", "error", "line", "time")
            );
            extract($hook_data);

            if (!strlen($comment)) {
                $comment = trans('(payment without title)');
            }
            if (!empty($pattern['comment_replace'])) {
                $comment = preg_replace($pattern['comment_replace']['from'], $pattern['comment_replace']['to'], $comment);
            }
            $cid = $id;
            if (empty($cid)) {
                $cid = '-';
            }
            foreach (array('srcaccount', 'dstaccount', 'customername', 'cid') as $replace_symbol) {
                $variable = $$replace_symbol;
                $variable = empty($variable) ? trans('none') : $variable;
                $comment = str_replace('%'. $replace_symbol . '%', $variable, $comment);
            }

            $customer = trim($lastname.' '.$name);
            $comment = trim($comment);

            if (!empty($pattern['use_line_hash'])) {
                $hash = md5($theline.(!empty($pattern['line_idx_hash']) ? $ln : ''));
            } else {
                $hash = md5($time.$value.$customer.$comment.(!empty($pattern['line_idx_hash']) ? $ln : ''));
            }

            if (is_numeric($value)) {
                if (isset($pattern['modvalue']) && $pattern['modvalue']) {
                    $value = str_replace(',', '.', $value * $pattern['modvalue']);
                }

                if (!$this->db->GetOne('SELECT id FROM cashimport WHERE hash = ?', array($hash))) {
                    // Add file
                    if (!$sourcefileid) {
                        $args = array(
                            'name' => $filename,
                            'idate' => time(),
                            SYSLOG::RES_USER => Auth::GetCurrentUser(),
                        );
                        $this->db->Execute('INSERT INTO sourcefiles (name, idate, userid)
							VALUES (?, ?, ?)', array_values($args));
                        $sourcefileid = $this->db->GetLastInsertID('sourcefiles');
                        if ($sourcefileid && $this->syslog) {
                            $args[SYSLOG::RES_SOURCEFILE] = $sourcefileid;
                            $syslog_records[] = array(
                                'resource' => SYSLOG::RES_SOURCEFILE,
                                'operation' => SYSLOG::OPER_ADD,
                                'args' => $args,
                            );
                        }
                    }

                    if (!empty($_POST['source'])) {
                        $sourceid = intval($_POST['source']);
                    } elseif (!empty($pattern['id'])) {
                        $sourceid = intval($pattern['id']);
                    } else {
                        $sourceid = null;
                    }

                    $args = array(
                        'time' => $time,
                        'value' => $value,
                        'customer' => $customer,
                        SYSLOG::RES_CUST => $id,
                        'comment' => $comment,
                        'hash' => $hash,
                        SYSLOG::RES_CASHSOURCE => $sourceid,
                        SYSLOG::RES_SOURCEFILE => $sourcefileid,
                    );
                    $res = $this->db->Execute('INSERT INTO cashimport (date, value, customer,
						customerid, description, hash, sourceid, sourcefileid)
						VALUES (?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
                    if ($res && $this->sylog) {
                        $args[SYSLOG::RES_CASHIMPORT] = $this->db->GetLastInsertID('cashimport');
                        $syslog_records[] = array(
                            'resource' => SYSLOG::RES_CASHIMPORT,
                            'operation' => SYSLOG::OPER_ADD,
                            'args' => $args,
                        );
                    }

                    $data[] = $args;
                } else {
                    $error['lines'][$ln] = array(
                        'customer' => $customer,
                        'customerid' => $id,
                        'date' => $time,
                        'value' => $value,
                        'comment' => $comment
                    );
                }
            }
        }

        if ($patterns_cnt && !empty($sum)) {
            foreach ($patterns as $idx => $pattern) {
                if (isset($pattern['pattern_sum']) && isset($pattern['pattern_sum_check']) && !$pattern['pattern_sum_check']($data, $sum)) {
                    $error['sum'] = true;
                }
            }
        }

        if ($sourcefileid) {
            if ($error['sum']) {
                $this->db->Execute('DELETE FROM cashimport WHERE sourcefileid = ?', array($sourcefileid));
                $this->db->Execute('DELETE FROM sourcefiles WHERE id = ?', array($sourcefileid));
            } else {
                if (!$quiet) {
                    printf("File %s: %d records." . PHP_EOL, $filename, count($data));
                }
                if (!empty($syslog_records)) {
                    foreach ($syslog_records as $syslog_record) {
                        $this->syslog->AddMessage(
                            $syslog_record['resource'],
                            $syslog_record['operation'],
                            $syslog_record['args']
                        );
                    }
                }
            }
        }

        return $error;
    }

    /**
     * Commits cash imports located in database
     */
    public function CashImportCommit()
    {
        global $LMS;

        $imports = $this->db->GetAll('SELECT i.*, f.idate
			FROM cashimport i
			LEFT JOIN sourcefiles f ON (f.id = i.sourcefileid)
			WHERE i.closed = 0 AND i.customerid IS NOT NULL');

        if (!empty($imports)) {
            $idate  = ConfigHelper::checkConfig('finances.cashimport_use_idate');
            $icheck = ConfigHelper::checkConfig('finances.cashimport_checkinvoices');

            $finance_manager = new LMSFinanceManager($this->db, $this->auth, $this->cache, $this->syslog);
            $customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);

            $cashimports = array();
            foreach ($imports as $import) {
                $this->db->BeginTrans();

                $balance['time'] = $idate ? $import['idate'] : $import['date'];
                $balance['type'] = 1;
                $balance['value'] = $import['value'];
                $balance['customerid'] = $import['customerid'];
                $balance['comment'] = $import['description'];
                $balance['importid'] = $import['id'];
                $balance['sourceid'] = $import['sourceid'];
                $balance['userid'] = null;

                if ($import['value'] > 0 && $icheck) {
                    if ($invoices = $this->db->GetAll(
                        'SELECT x.id, x.value FROM (
						SELECT d.id,
							(SELECT SUM(value*count) FROM invoicecontents WHERE docid = d.id) +
								COALESCE((
									SELECT SUM((a.value+b.value)*(a.count+b.count)) - SUM(b.value*b.count)
									FROM documents dd
									JOIN invoicecontents a ON (a.docid = dd.id)
									JOIN invoicecontents b ON (dd.reference = b.docid AND a.itemid = b.itemid)
									WHERE dd.reference = d.id
								GROUP BY dd.reference), 0) AS value,
								d.cdate
								FROM documents d
								WHERE d.customerid = ? AND d.type = ? AND d.closed = 0
								GROUP BY d.id, d.cdate
								UNION
								SELECT d.id, dn.value, d.cdate
								FROM documents d
								JOIN debitnotecontents dn ON dn.docid = d.id
								WHERE d.customerid = ?
						) x ORDER BY x.cdate',
                        array($balance['customerid'], DOC_INVOICE, $balance['customerid'])
                    )) {
                        $sum = 0;
                        foreach ($invoices as $inv) {
                            $sum += $inv['value'];
                        }

                        $bval = $customer_manager->GetCustomerBalance($balance['customerid']);
                        $value = f_round($bval + $import['value'] + $sum);

                        foreach ($invoices as $inv) {
                            $inv['value'] = f_round($inv['value']);
                            if ($inv['value'] > $value) {
                                break;
                            } else {
                                // close invoice and assigned credit notes
                                $this->db->Execute(
                                    'UPDATE documents SET closed = 1
									WHERE id = ? OR reference = ?',
                                    array($inv['id'], $inv['id'])
                                );
                                if ($this->syslog) {
                                    foreach (array('id', 'reference') as $key) {
                                        $docids = $this->db->GetCol('SELECT id FROM documents WHERE ' . $key . ' = ?', array($inv['id']));
                                        if (!empty($docids)) {
                                            foreach ($docids as $docid) {
                                                $args = array(
                                                SYSLOG::RES_DOC,
                                                SYSLOG::RES_CUST,
                                                'closed' => 1,
                                                );
                                                $this->syslog->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_UPDATE, $args);
                                            }
                                        }
                                    }
                                }

                                $value -= $inv['value'];
                            }
                        }
                    }
                }

                $this->db->Execute('UPDATE cashimport SET closed = 1 WHERE id = ?', array($import['id']));

                if ($this->syslog) {
                    $args = array(
                        SYSLOG::RES_CASHIMPORT => $import['id'],
                        SYSLOG::RES_CASHSOURCE => $import['sourceid'],
                        SYSLOG::RES_SOURCEFILE => $import['sourcefileid'],
                        SYSLOG::RES_CUST => $import['customerid'],
                        'closed' => 1,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_CASHIMPORT, SYSLOG::OPER_UPDATE, $args);
                }
                $balance['currency'] = LMS::$currency;
                $finance_manager->AddBalance($balance);

                $this->db->CommitTrans();

                if ($this->db->GetOne('SELECT closed FROM cashimport WHERE id = ?', array($import['id']))) {
                    $cashimports[] = $import;
                }
            }
            $LMS->executeHook('cashimport_after_commit', array('cashimports' => $cashimports));
        }
    }
}

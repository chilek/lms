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
    public function CashImportParseFile($filename, $contents, $patterns, $quiet = true, $filemtime = null, $config_section = 'cashimport')
    {
        global $LMS;

        static $unique_source_accounts;

        $file = preg_split('/\r?\n/', $contents);
        $patterns_cnt = isset($patterns) ? count($patterns) : 0;
        $ln = 0;
        $sum = array();
        $data = array();
        $error = array();
        $syslog_records = array();

        $sourcefileid = null;

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

            if (isset($matches['name'])) {
                $name = trim($matches['name']);
            } elseif (isset($pattern['pname'], $matches[$pattern['pname']])) {
                $name = trim($matches[$pattern['pname']]);
            } elseif (isset($pattern['name'], $matches[$pattern['name']])) {
                $name = trim($matches[$pattern['name']]);
            } else {
                $name = '';
            }

            if (isset($matches['lastname'])) {
                $lastname = trim($matches['lastname']);
            } elseif (isset($pattern['plastname'], $matches[$pattern['plastname']])) {
                $lastname = trim($matches[$pattern['plastname']]);
            } elseif (isset($pattern['lastname'], $matches[$pattern['lastname']])) {
                $lastname = trim($matches[$pattern['lastname']]);
            } else {
                $lastname = '';
            }

            $customername = preg_replace('/[\s]{2,}/', ' ', (empty($lastname) ? '' : $lastname . ' ') . $name);

            if (isset($matches['comment'])) {
                $comment = trim($matches['comment']);
            } elseif (isset($pattern['pcomment'], $matches[$pattern['pcomment']])) {
                $comment = trim($matches[$pattern['pcomment']]);
            } elseif (isset($pattern['comment'], $matches[$pattern['comment']])) {
                $comment = trim($matches[$pattern['comment']]);
            } else {
                $comment = '';
            }

            if (isset($matches['date'])) {
                $time = trim($matches['date']);
            } elseif (isset($pattern['pdate'], $matches[$pattern['pdate']])) {
                $time = trim($matches[$pattern['pdate']]);
            } elseif (isset($pattern['date'], $matches[$pattern['date']])) {
                $time = trim($matches[$pattern['date']]);
            } else {
                $time = '';
            }

            if (isset($matches['operdate'])) {
                $operdate = trim($matches['operdate']);
            } elseif (isset($pattern['poperdate'], $matches[$pattern['poperdate']])) {
                $operdate = trim($matches[$pattern['poperdate']]);
            } else {
                $operdate = '';
            }
            if (!strlen($operdate)) {
                $operdate = null;
            }

            if (isset($matches['value'])) {
                $value = str_replace(',', '.', preg_replace('/[\s]/', '', $matches['value']));
            } elseif (isset($pattern['pvalue'], $matches[$pattern['pvalue']])) {
                $value = str_replace(',', '.', preg_replace('/[\s]/', '', $matches[$pattern['pvalue']]));
            } elseif (isset($pattern['value'], $matches[$pattern['value']])) {
                $value = str_replace(',', '.', preg_replace('/[\s]/', '', $matches[$pattern['value']]));
            } else {
                $value = '';
            }

            if (isset($matches['srcaccount'])) {
                $srcaccount = preg_replace('/[\s]/', '', $matches['srcaccount']);
            } elseif (isset($pattern['srcaccount'], $matches[$pattern['srcaccount']])) {
                $srcaccount = preg_replace('/[\s]/', '', $matches[$pattern['srcaccount']]);
            } else {
                $srcaccount = '';
            }

            if (isset($matches['dstaccount'])) {
                $dstaccount = preg_replace('/[\s]/', '', $matches['dstaccount']);
            } elseif (isset($pattern['dstaccount'], $matches[$pattern['dstaccount']])) {
                $dstaccount = preg_replace('/[\s]/', '', $matches[$pattern['dstaccount']]);
            } else {
                $dstaccount = '';
            }

            if (isset($matches['option_string'])) {
                $optional_string = trim($matches['optional_string']);
            } elseif (isset($pattern['optional_string'], $matches[$pattern['optional_string']])) {
                $optional_string = trim($matches[$pattern['optional_string']]);
            } else {
                $optional_string = '';
            }

            if (empty($matches['id']) && empty($pattern['pid'])) {
                if (isset($pattern['pid_regexp'])) {
                    if (is_array($pattern['pid_regexp'])) {
                        $regexps = array_filter($pattern['pid_regexp'], function ($regexp) {
                            return strlen($regexp) > 2;
                        });
                    } elseif (strlen($pattern['pid_regexp']) > 2) {
                        $regexps = array($pattern['pid_regexp']);
                    }
                } else {
                    $regexps = array('/.*ID[:\-\/]([0-9]{0,4}).*/i');
                }

                foreach ($regexps as $regexp) {
                    if (preg_match($regexp, $theline, $matches)) {
                        $id = $matches[1];
                        break;
                    }
                }
            } elseif (isset($matches['id'])) {
                $id = intval(preg_replace('/\s+/', '', $matches['id']));
            } elseif (isset($pattern['pid'], $matches[$pattern['pid']])) {
                $id = intval(preg_replace('/\s+/', '', $matches[$pattern['pid']]));
            } else {
                $id = null;
            }

            if (isset($matches['extid'])) {
                $extid = trim($matches['extid']);
                if (!strlen($extid)) {
                    $extid = null;
                }
            } elseif (isset($pattern['pextid'], $matches[$pattern['pextid']])) {
                $extid = trim($matches[$pattern['pextid']]);
                if (!strlen($extid)) {
                    $extid = null;
                }
            } else {
                $extid = null;
            }

            // seek invoice number
            if (!$id && !empty($pattern['invoice_regexp'])) {
                if (preg_match($pattern['invoice_regexp'], $theline, $matches)) {
                    if (!isset($pattern['pinvoice_year'], $pattern['pinvoice_month'], $pattern['pinvoice_number'])
                        && !isset($matches['invoice_year'], $matches['invoice_month'], $matches['invoice_number'])) {
                        $id = $this->db->GetOne(
                            'SELECT customerid
                            FROM documents
                            WHERE LOWER(fullnumber) = LOWER(?)
                                AND type IN ?',
                            array(
                                $matches[1],
                                array(DOC_INVOICE, DOC_CNOTE)
                            )
                        );
                    } else {
                        if (isset($matches['invoice_number'])) {
                            $invnumber = $matches['invoice_number'];
                        } elseif (isset($pattern['pinvoice_number'], $matches[$pattern['pinvoice_number']])) {
                            $invnumber = $matches[$pattern['pinvoice_number']];
                        } else {
                            $invnumber = null;
                        }
                        if (isset($matches['invoice_year'])) {
                            $invyear = $matches['invoice_year'];
                        } elseif (isset($pattern['pinvoice_year'], $matches[$pattern['pinvoice_year']])) {
                            $invyear = $matches[$pattern['pinvoice_year']];
                        } else {
                            $invyear = null;
                        }
                        if (isset($matches['invoice_month']) && $matches['invoice_month'] > 0) {
                            $invmonth = intval($matches['invoice_month']);
                        } elseif (isset($pattern['pinvoice_month'], $matches[$pattern['pinvoice_month']])
                            && $matches[$pattern['pinvoice_month']] > 0) {
                            $invmonth = intval($matches[$pattern['pinvoice_month']]);
                        } else {
                            $invmonth = 1;
                        }

                        if ($invnumber && $invyear) {
                            $from = mktime(0, 0, 0, $invmonth, 1, $invyear);

                            if (isset($matches['invoice_month']) && $matches['invoice_month'] > 0) {
                                $to_month = $invmonth + 1;
                            } elseif (isset($pattern['pinvoice_month']) && $pattern['pinvoice_month'] > 0) {
                                $to_month = $invmonth + 1;
                            } else {
                                $to_month = 13;
                            }
                            $to = mktime(0, 0, 0, $to_month, 1, $invyear);

                            $id = $this->db->GetOne(
                                'SELECT customerid
                                FROM documents
                                WHERE number = ?
                                    AND cdate > ?
                                    AND cdate < ?
                                    AND type IN ?',
                                array(
                                    $invnumber,
                                    $from,
                                    $to,
                                    array(DOC_INVOICE, DOC_CNOTE)
                                )
                            );
                        }
                    }
                }
            }

            // seek by explicitly given source or destination customer account numbers
            if (!$id) {
                if (strlen($dstaccount)) {
                    $id = $this->db->GetOne(
                        'SELECT customerid FROM customercontacts
                        WHERE contact = ? AND (type & ?) = ?',
                        array(
                            $dstaccount,
                            CONTACT_BANKACCOUNT | CONTACT_INVOICES | CONTACT_DISABLED,
                            CONTACT_BANKACCOUNT | CONTACT_INVOICES,
                        )
                    );
                } elseif (strlen($srcaccount)) {
                    $id = $this->db->GetOne(
                        'SELECT customerid FROM customercontacts
                        WHERE contact = ? AND (type & ?) = ?',
                        array(
                            $srcaccount,
                            CONTACT_BANKACCOUNT | CONTACT_INVOICES | CONTACT_DISABLED,
                            CONTACT_BANKACCOUNT,
                        )
                    );
                    if (empty($id)) {
                        // find customer by source accounts stored in cash import record;
                        // if customer has unique source account assigned to all his cash import records
                        // then we matched customer by source account
                        if (!isset($unique_source_accounts)) {
                            $days = intval(ConfigHelper::getConfig($config_section . '.source_account_match_threshold_days'));
                            $unique_source_accounts = $this->db->GetALl(
                                'SELECT i.customerid, i.srcaccount
                                FROM cashimport i
                                JOIN (
                                    SELECT i2.srcaccount
                                    FROM cashimport i2
                                    WHERE i2.customerid IS NOT NULL AND i2.srcaccount IS NOT NULL
                                        ' . ($days ? ' AND i2.date >= ?NOW? - ' . $days . ' * 86400' : '') . '
                                    GROUP BY i2.srcaccount
                                    HAVING COUNT(DISTINCT i2.customerid) = 1
                                ) i3 ON i3.srcaccount = i.srcaccount
                                WHERE i.customerid IS NOT NULL AND i.srcaccount IS NOT NULL
                                    ' . ($days ? ' AND i.date >= ?NOW? - ' . $days . ' * 86400' : '')
                            );
                            if (empty($unique_source_accounts)) {
                                $unique_source_accounts = array();
                            } else {
                                $unique_source_accounts = Utils::array_column($unique_source_accounts, 'customerid', 'srcaccount');
                            }
                        }
                        if (!empty($unique_source_accounts) && isset($unique_source_accounts[$srcaccount])) {
                            $id = $unique_source_accounts[$srcaccount];
                        }
                    }
                }
            }

            $found_by_name = false;

            if (!$id && strlen($name)) {
                $customer_name_parts = array(
                    'name' => $name,
                );
                if (strlen($lastname)) {
                    $customer_name_parts['lastname'] = $lastname;
                }
                $customer_names = array(
                    implode(' ', $customer_name_parts),
                );
                if (count($customer_name_parts) > 1) {
                    $customer_names[] = implode(' ', array_reverse($customer_name_parts));
                }

                if (!empty($pattern['customer_replace'])) {
                    foreach ($customer_names as &$customer_name) {
                        $customer_name = preg_replace($pattern['customer_replace']['from'], $pattern['customer_replace']['to'], $customer_name);
                    }
                    unset($customer_name);
                }
                $customer_names = array_filter($customer_names, function ($customer_name) {
                    return strlen($customer_name);
                });

                if (!empty($customer_names)) {
                    if (count($customer_names) > 1) {
                        $uids = $this->db->GetCol(
                            'SELECT id
                            FROM customers
                            WHERE UPPER(' . $this->db->Concat('lastname', "(CASE WHEN name <> '' THEN ' ' ELSE '' END)", 'name') . ') = UPPER(?)
                                OR UPPER(' . $this->db->Concat('lastname', "(CASE WHEN name <> '' THEN ' ' ELSE '' END)", 'name') . ') = UPPER(?)
                                OR UPPER(' . $this->db->Concat('name', "(CASE WHEN name <> '' THEN ' ' ELSE '' END)", 'lastname') . ') = UPPER(?)
                                OR UPPER(' . $this->db->Concat('name', "(CASE WHEN name <> '' THEN ' ' ELSE '' END)", 'lastname') . ') = UPPER(?)',
                            array(
                                reset($customer_names),
                                end($customer_names),
                                reset($customer_names),
                                end($customer_names),
                            )
                        );
                    } else {
                        $uids = $this->db->GetCol(
                            'SELECT id
                            FROM customers
                            WHERE UPPER(' . $this->db->Concat('lastname', "(CASE WHEN name <> '' THEN ' ' ELSE '' END)", 'name') . ') = UPPER(?)
                                OR UPPER(' . $this->db->Concat('name', "(CASE WHEN name <> '' THEN ' ' ELSE '' END)", 'lastname') . ') = UPPER(?)',
                            array(
                                reset($customer_names),
                                reset($customer_names),
                            )
                        );
                    }

                    if (!empty($uids) && count($uids) == 1) {
                        $id = $uids[0];
                        $found_by_name = true;
                    }
                }
            }

            if ($time) {
                if (preg_match($pattern['date_regexp'], $time, $date)) {
                    if (isset($date['month'], $date['day'], $date['year'])) {
                        $time = mktime(
                            0,
                            0,
                            0,
                            $date['month'],
                            $date['day'],
                            $date['year']
                        );
                    } elseif (isset($pattern['pmonth'], $pattern['pday'], $pattern['pyear'])) {
                        $time = mktime(
                            0,
                            0,
                            0,
                            $date[$pattern['pmonth']],
                            $date[$pattern['pday']],
                            $date[$pattern['pyear']]
                        );
                    }
                    if (empty($time)) {
                        $time = time();
                    }
                } elseif (!is_numeric($time)) {
                    $time = time();
                }
                if (isset($pattern['date_hook'])) {
                    $time = $pattern['date_hook']($time, $_FILES['file']['name']);
                }
            } else {
                $time = time();
            }

            if (isset($operdate)) {
                if (isset($pattern['operdate_regexp'])
                    && preg_match($pattern['operdate_regexp'], $operdate, $date)) {
                    if (isset($date['month'], $date['day'], $date['year'])) {
                        $operdate = mktime(
                            0,
                            0,
                            0,
                            $date['month'],
                            $date['day'],
                            $date['year']
                        );
                    } elseif (isset(
                        $pattern['p_operdate_month'],
                        $date[$pattern['p_operdate_month']],
                        $pattern['p_operdate_day'],
                        $date[$pattern['p_operdate_day']],
                        $pattern['p_operdate_year'],
                        $date[$pattern['p_operdate_year']]
                    )) {
                        $operdate = mktime(
                            0,
                            0,
                            0,
                            $date[$pattern['p_operdate_month']],
                            $date[$pattern['p_operdate_day']],
                            $date[$pattern['p_operdate_year']]
                        );
                    } else {
                        $operdate = null;
                    }
                    if (empty($operdate)) {
                        $operdate = null;
                    }
                } elseif (!is_numeric($operdate)) {
                    $operdate = null;
                }
            }

            $hook_data = $LMS->executeHook(
                'cashimport_extra_filter_before_submit',
                compact("id", "pattern", "comment", "theline", "ln", "patterns_cnt", "error", "line", "time")
            );
            extract($hook_data);

            if (!$found_by_name && $id && (!$name || !$lastname)) {
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

            if (!strlen($comment)) {
                $comment = trans('(payment without title)');
            }
            if (!empty($pattern['comment_replace'])) {
                $comment = preg_replace($pattern['comment_replace']['from'], $pattern['comment_replace']['to'], $comment);
            }
            if (!empty($pattern['customer_replace'])) {
                $customername = preg_replace($pattern['customer_replace']['from'], $pattern['customer_replace']['to'], $customername);
            }

            $cid = $id;
            if (empty($cid)) {
                $cid = '-';
            }
            foreach (array('srcaccount', 'dstaccount', 'customername', 'cid', 'extid') as $replace_symbol) {
                $variable = $$replace_symbol;
                $variable = strlen($variable) ? $variable : trans('none');
                $comment = str_replace('%'. $replace_symbol . '%', $variable, $comment);
            }

            // insert optional string here (for now?) so we can easily see it in GUI
            $customer = trim($lastname . ' ' . $name)
                . (empty($optional_string) ? '' : ' [[<-- Customer | Oprional_string -->]]: ' . $optional_string);
            $comment = trim($comment);

            $hash = md5(
                (empty($pattern['use_line_hash']) ? $time . $value . $customer . $comment : $theline)
                    . (!empty($pattern['line_idx_hash']) ? $ln : '')
                    . (!empty($pattern['filename_hash']) ? $filename : '')
            );

            if (is_numeric($value)) {
                if (isset($pattern['modvalue']) && $pattern['modvalue']) {
                    $value = str_replace(',', '.', $value * $pattern['modvalue']);
                }

                if (!$this->db->GetOne('SELECT id FROM cashimport WHERE hash = ?', array($hash))) {
                    // Add file
                    if (!isset($sourcefileid)) {
                        $args = array(
                            'name' => $filename,
                            'idate' => isset($filemtime) ? $filemtime : time(),
                            SYSLOG::RES_USER => Auth::GetCurrentUser(),
                        );
                        $this->db->Execute(
                            'INSERT INTO sourcefiles (name, idate, userid)
                            VALUES (?, ?, ?)',
                            array_values($args)
                        );
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
                        'operdate' => $operdate,
                        'value' => $value,
                        'customer' => $customer,
                        SYSLOG::RES_CUST => $id,
                        'comment' => $comment,
                        'hash' => $hash,
                        SYSLOG::RES_CASHSOURCE => $sourceid,
                        SYSLOG::RES_SOURCEFILE => $sourcefileid,
                        'srcaccount' => isset($srcaccount) && strlen($srcaccount) ? $srcaccount : null,
                        'extid' => $extid,
                    );
                    $res = $this->db->Execute(
                        'INSERT INTO cashimport (date, operdate, value, customer,
                        customerid, description, hash, sourceid, sourcefileid, srcaccount, extid)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        array_values($args)
                    );
                    if ($res && $this->syslog) {
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
                        'operdate' => $operdate,
                        'value' => $value,
                        'comment' => $comment,
                        'extid' => $extid,
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
            if (isset($error['sum'])) {
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
                $balance['currency'] = Localisation::getCurrentCurrency();
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

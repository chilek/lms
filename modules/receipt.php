<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

function GetReceipt($id)
{
    $db = LMSDB::getInstance();

    if ($receipt = $db->GetRow('SELECT d.*, cc.name AS country, cd.name AS div_country,
					u.name AS user, n.template,
					ds.name AS d_name, ds.address AS d_address,
					ds.zip AS d_zip, ds.city AS d_city, cds.name AS d_country
				FROM documents d
				LEFT JOIN countries cc ON cc.id = d.countryid
				LEFT JOIN countries cd ON cd.id = d.div_countryid
				LEFT JOIN vusers u ON (d.userid = u.id)
				LEFT JOIN numberplans n ON (d.numberplanid = n.id)
				LEFT JOIN customers c ON (d.customerid = c.id)
				LEFT JOIN vdivisions ds ON (ds.id = c.divisionid)
				LEFT JOIN countries cds ON cds.id = ds.countryid
				WHERE d.type = 2 AND d.id = ?', array($id))) {
        // if division for receipt is not defined and there is only one division in database
        // we try to use this division
        if (!empty($receipt['divisionid'])) {
            $receipt['d_name'] = $receipt['div_name'];
            $receipt['d_address'] = $receipt['div_address'];
            $receipt['d_zip'] = $receipt['div_zip'];
            $receipt['d_city'] = $receipt['div_city'];
            $receipt['d_countryid'] = $receipt['div_countryid'];
            $receipt['d_country'] = $receipt['div_country'];
        }
        if (empty($receipt['d_name']) && $db->GetOne('SELECT COUNT(*) FROM divisions') == 1) {
            $receipt = array_merge($receipt, $db->GetRow('SELECT d.name AS d_name, address AS d_address,
					zip AS d_zip, city AS d_city, countryid AS d_countryid, c.name AS d_country
				FROM vdivisions d
				LEFT JOIN countries c ON c.id = d.countryid'));
        }

        $receipt['contents'] = $db->GetAll(
            'SELECT * FROM receiptcontents c
            WHERE docid = ? ORDER BY itemid',
            array($id)
        );
        $receipt['total'] = 0;

        foreach ($receipt['contents'] as $row) {
            $receipt['total'] += $row['value'];
        }

        $receipt['number'] = docnumber(array(
            'number' => $receipt['number'],
            'template' => $receipt['template'],
            'cdate' => $receipt['cdate'],
            'ext_num' => $receipt['extnumber'],
            'customerid' => $receipt['customerid'],
        ));

        if ($receipt['total'] < 0) {
            $receipt['type'] = 'out';
            // change values sign
            foreach ($receipt['contents'] as $idx => $row) {
                $receipt['contents'][$idx]['value'] *= -1;
            }
            $receipt['total'] *= -1;
        } else {
            $receipt['type'] = 'in';
        }

        $receipt['totalg'] = round($receipt['total'] * 100 - ((int) $receipt['total']) * 100);

        return $receipt;
    }
}

$attachment_name = ConfigHelper::getConfig('receipts.attachment_name');
$receipt_type = strtolower(ConfigHelper::getConfig('receipts.type'));

if ($receipt_type == 'pdf') {
    $template = ConfigHelper::getConfig('receipts.template_file', 'standard');
    if ($template == 'standard') {
        $classname = 'LMSEzpdfReceipt';
    } else {
        $classname = 'LMS' . ucwords($template) . 'Receipt';
    }
    $document = new $classname(trans('Receipts'));
} else {
    $document = new LMSHtmlReceipt($SMARTY);
}

if (isset($_GET['print']) && $_GET['print'] == 'cached' && count($_POST['marks'])) {
    $SESSION->restore('rlm', $rlm);
    $SESSION->remove('rlm');

    if (isset($_POST['marks'])) {
        if (isset($_POST['marks']['receipt'])) {
            $marks = $_POST['marks']['receipt'];
        } else {
            $marks = $_POST['marks'];
        }
    } else {
        $marks = array();
    }

    $ids = Utils::filterIntegers($marks);

    if (empty($ids)) {
        $SESSION->close();
        die;
    }

    if (isset($_GET['cash'])) {
        $ids = $LMS->GetDocumentsForBalanceRecords($ids, array(DOC_RECEIPT));
    }

    if (!empty($ids)) {
        sort($ids);
    }

    $layout['pagetitle'] = trans('Cash Receipts');

    $which = isset($_GET['which']) ? intval($_GET['which']) : 0;
    if (!$which) {
        foreach (explode(',', ConfigHelper::getConfig('receipts.default_printpage', 'original,copy')) as $t) {
            if (trim($t) == 'original') {
                $which |= DOC_ENTITY_ORIGINAL;
            } elseif (trim($t) == 'copy') {
                $which |= DOC_ENTITY_COPY;
            }
        }
    }

    $i = 0;
    $count = count($ids);
    foreach ($ids as $idx => $receiptid) {
        if ($receipt = GetReceipt($receiptid)) {
            if ($count == 1) {
                $docnumber = $receipt['number'];
            }
            $i++;
            if ($i == $count) {
                $receipt['last'] = true;
            }
            $receipt['first'] = $i <= 1;
            $receipt['which'] = $which;
            $document->Draw($receipt);
        }
    }
} elseif ($receipt = GetReceipt($_GET['id'])) {
    $regid = $DB->GetOne('SELECT DISTINCT regid FROM receiptcontents WHERE docid=?', array($_GET['id']));
    if (!$DB->GetOne('SELECT rights FROM cashrights WHERE userid=? AND regid=?', array(Auth::GetCurrentUser(), $regid))) {
        $SMARTY->display('noaccess.html');
        $SESSION->close();
        die;
    }

    $docnumber = $receipt['number'];

    $layout['pagetitle'] = trans('Cash Receipt No. $a', $receipt['number']);

    $receipt['last'] = true;
    $receipt['first'] = true;

    $which = isset($_GET['which']) ? intval($_GET['which']) : 0;
    if (!$which) {
        foreach (explode(',', ConfigHelper::getConfig('receipts.default_printpage', 'original,copy')) as $t) {
            if (trim($t) == 'original') {
                $which |= DOC_ENTITY_ORIGINAL;
            } elseif (trim($t) == 'copy') {
                $which |= DOC_ENTITY_COPY;
            }
        }
    }

    $receipt['which'] = $which;

    $document->Draw($receipt);
}

if (!is_null($attachment_name) && isset($docnumber)) {
    $attachment_name = str_replace('%number', $docnumber, $attachment_name);
    $attachment_name = preg_replace('/[^[:alnum:]_\.]/i', '_', $attachment_name);
} else {
    $attachment_name = 'receipts.' . ($receipt_type == 'pdf' ? 'pdf' : 'html');
}

$document->WriteToBrowser($attachment_name);

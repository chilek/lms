<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

include(!empty($CONFIG['phpui']['import_config']) ? $CONFIG['phpui']['import_config'] : 'cashimportcfg.php');

if(!isset($patterns) || !is_array($patterns))
{
	$error['file'] = trans('Configuration error. Patterns array not found!');
}
elseif(isset($_FILES['file']) && is_uploaded_file($_FILES['file']['tmp_name']) && $_FILES['file']['size'])
{
	$file         = file($_FILES['file']['tmp_name']);
	$filename     = $_FILES['file']['name'];
	$patterns_cnt = isset($patterns) ? sizeof($patterns) : 0;
	$ln           = 0;

	foreach($file as $line)
	{
		$id = NULL;
		$count = 0;
		$ln++;

		if($patterns_cnt) foreach($patterns as $idx => $pattern)
		{
			$theline = $line;

			if(strtoupper($pattern['encoding']) != 'UTF-8')
			{
				$theline = @iconv($pattern['encoding'], 'UTF-8//TRANSLIT', $theline);
			}

			if(!preg_match($pattern['pattern'], $theline, $matches))
				$count++;
			else
				break;
		}

		// line isn't matching to any pattern
		if($count == $patterns_cnt)
		{
			if(trim($line) != '') 
				$error['lines'][$ln] = $patterns_cnt == 1 ? $theline : $line;
			continue; // go to next line
		}

		$name = isset($matches[$pattern['pname']]) ? trim($matches[$pattern['pname']]) : '';
		$lastname = isset($matches[$pattern['plastname']]) ? trim($matches[$pattern['plastname']]) : '';
		$comment = isset($matches[$pattern['pcomment']]) ? trim($matches[$pattern['pcomment']]) : '';
		$time = isset($matches[$pattern['pdate']]) ? trim($matches[$pattern['pdate']]) : '';
		$value = str_replace(',','.', isset($matches[$pattern['pvalue']]) ? trim($matches[$pattern['pvalue']]) : '');

		if(!$pattern['pid'])
		{
			if(!empty($pattern['pid_regexp'])) 
				$regexp = $pattern['pid_regexp'];
			else
				$regexp = '/.*ID[:\-\/]([0-9]{0,4}).*/i';

			if(preg_match($regexp, $theline, $matches))
				$id = $matches[1];
		}
		else
			$id = isset($matches[$pattern['pid']]) ? intval($matches[$pattern['pid']]) : NULL;

		// seek invoice number
		if(!$id && !empty($pattern['invoice_regexp']))
		{
			if(preg_match($pattern['invoice_regexp'], $theline, $matches)) 
			{
				$invid = $matches[$pattern['pinvoice_number']];
				$invyear = $matches[$pattern['pinvoice_year']];
				$invmonth = !empty($pattern['pinvoice_month']) && $pattern['pinvoice_month'] > 0 ? intval($matches[$pattern['pinvoice_month']]) : 1;

				if($invid && $invyear)
				{
					$from = mktime(0,0,0, $invmonth, 1, $invyear);
					$to = mktime(0,0,0, !empty($pattern['pinvoice_month']) && $pattern['pinvoice_month'] > 0 ? $invmonth + 1 : 13, 1, $invyear);
					$id = $DB->GetOne('SELECT customerid FROM documents 
							WHERE number=? AND cdate>=? AND cdate<? AND type IN (?,?)', 
							array($invid, $from, $to, DOC_INVOICE, DOC_CNOTE));
				}
			}
		}

		if(!$id && $name && $lastname)
		{
			$uids = $DB->GetCol('SELECT id FROM customers WHERE UPPER(lastname)=UPPER(?) and UPPER(name)=UPPER(?)', array($lastname, $name));
			if(sizeof($uids)==1)
				$id = $uids[0];
		}
		elseif($id && (!$name || !$lastname))
		{
			if($tmp = $DB->GetRow('SELECT lastname, name FROM customers WHERE id = ?', array($id)))
			{
				$lastname = $tmp['lastname'];
				$name = $tmp['name'];
			}
			else
				$id = NULL;
		}

		if($time)
		{
			if(preg_match($pattern['date_regexp'], $time, $date))
			{
				$time = mktime(0,0,0, 
					$date[$pattern['pmonth']], 
					$date[$pattern['pday']], 
					$date[$pattern['pyear']]);
			}
			elseif(!is_numeric($time))
				$time = time();
		}
		else
			$time = time();

		if(!empty($pattern['comment_replace']))
			$comment = preg_replace($pattern['comment_replace']['from'], $pattern['comment_replace']['to'], $comment);

		// remove unneeded spaces and cut $customer and $comment to fit into database (150 chars limit)
		$customer = trim($lastname.' '.$name);                                                                                                                                           
		$customer = preg_replace('/[ ]+/',' ',$customer);
		$customer = substr($customer,0,150);

		$comment = trim($comment);
		$comment = preg_replace('/[ ]+/',' ',$comment);
		$comment = substr($comment,0,150);

		if(!empty($pattern['use_line_hash']))
			$hash = md5($theline.(!empty($pattern['line_idx_hash']) ? $ln : ''));
		else
			$hash = md5($time.$value.$customer.$comment.(!empty($pattern['line_idx_hash']) ? $ln : ''));

		if(is_numeric($value))
		{
			if(isset($pattern['modvalue']) && $pattern['modvalue'])
			{
				$value = str_replace(',','.', $value * $pattern['modvalue']);
			}

			if(!$DB->GetOne('SELECT id FROM cashimport WHERE hash = ?', array($hash)))
			{
                // Add file
                if (!$sourcefileid) {
                    $DB->Execute('INSERT INTO sourcefiles (name, idate, userid)
                        VALUES (?, ?NOW?, ?)',
                        array($filename, $AUTH->id));

                    $sourcefileid = $DB->GetLastInsertId('sourcefiles');
                }

				if(!empty($_POST['source']))
					$sourceid = intval($_POST['source']);
				elseif(!empty($pattern['id']))
					$sourceid = intval($pattern['id']);
				else
					$sourceid = NULL;

				$DB->Execute('INSERT INTO cashimport (date, value, customer,
					customerid, description, hash, sourceid, sourcefileid)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
					array(
					    $time,
					    $value,
					    $customer,
					    $id,
					    $comment,
					    $hash,
					    $sourceid,
					    $sourcefileid,
					));
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

	include(MODULES_DIR.'/cashimport.php');
	die;
}
elseif(isset($_FILES['file'])) // upload errors
	switch($_FILES['file']['error'])
	{
		case 1: 
		case 2: $error['file'] = trans('File is too large.'); break;
		case 3: $error['file'] = trans('File upload has finished prematurely.'); break;
		case 4: $error['file'] = trans('Path to file was not specified.'); break;
		default: $error['file'] = trans('Problem during file upload.'); break;
	}

$layout['pagetitle'] = trans('Cash Operations Import');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$sourcefiles = $DB->GetAll('SELECT s.*, u.name AS username,
    (SELECT COUNT(*) FROM cashimport WHERE sourcefileid = s.id) AS count
    FROM sourcefiles s
    LEFT JOIN users u ON (u.id = s.userid)
    ORDER BY s.idate DESC LIMIT 10');

$SMARTY->assign('error', $error);
$SMARTY->assign('customerlist', $LMS->GetCustomerNames());
$SMARTY->assign('sourcelist', $DB->GetAll('SELECT id, name FROM cashsources ORDER BY name'));
$SMARTY->assign('sourcefiles', $sourcefiles);
$SMARTY->display('cashimport.html');

?>

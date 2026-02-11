<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2011 LMS Developers
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
 *  $Id: print.php,v 1.143 2011/12/01 17:10:46 chilek Exp $
 */

$type = isset($_GET['type']) ? $_GET['type'] : '';

$SMARTY->assign('type', $type);

switch($type)
{
	case 'inventory':
	
	$error = NULL;

	if (isset($_POST['params'])) {
		$params = $_POST['params'];
		
		if(preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $params['day'])) {
			list($y, $m, $d) = explode('/', $params['day']);
			if(checkdate($m, $d, $y)) {
				$id = mktime(24, 0, 0, $m, $d, $y);
			} else
				$id = time();

		} else
			$id = time();

		$params['date'] = $id;
		$params['rdate'] = $id - 1;
		
		($params['direction']=='desc') ? $direction = 'desc' : $direction = 'asc';

		switch($params['order']) {
			case 'id':
				$sqlord = ' ORDER BY p.id';
				break;
			case 'pname':
				$sqlord = ' ORDER BY p.name';
				break;
			case 'mname':
				$sqlord = ' ORDER BY m.name';
				break;
			case 'gname':
				$sqlord = ' ORDER BY g.name';
				break;
			default:
				$sqlord = ' ORDER BY p.name';
				break;
		}
	}
	if ($pgl = $DB->GetAll('SELECT m.name as mname, m.id as mid, CONCAT(m.name, \' \', p.name) as pname, p.id, p.quantity, g.name as gname, g.id as gid, COALESCE(SUM(s.pricebuynet), 0) as valuenet, s.pricebuynet, COUNT(s.id) as count, t.name as type
		FROM stck_products p
		LEFT JOIN stck_manufacturers m ON p.manufacturerid = m.id
		LEFT JOIN stck_groups g ON p.groupid = g.id
		LEFT JOIN stck_stock s ON s.productid = p.id
		LEFT JOIN stck_types t ON p.typeid = t.id
		WHERE p.deleted = 0
		AND s.bdate < '.$params['date'].' AND (s.leavedate > '.$params['date'].' OR s.leavedate = 0 OR s.sold = 0) '
		.($params['warehouse'] ? ' AND s.warehouseid = '.$params['warehouse'] : '').'
		GROUP BY p.id, s.pricebuynet'
		.($sqlord != '' ? $sqlord.' '.$direction : ''))) {
		
		foreach($pgl as $p) {
			$params['totalvn'] += $p['valuenet'];
			$params['totalvg'] += $p['valuegross'];
		}
		
		$SMARTY->assign('params', $params);
		$SMARTY->assign('productlist', $pgl);

		$SMARTY->display('stck/printstocklist.html');
	}

	break;

	case 'brep':
	
	if (isset($_POST['params'])) {
		$params = $_POST['params'];
		
		if(preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $params['sday'])) {
			list($y, $m, $d) = explode('/', $params['sday']);
			if(checkdate($m, $d, $y)) {
				$id = mktime(24, 0, 0, $m, $d, $y);
			} else
				$id = mktime(24, 0, 0, date('j'), 1, date('Y'));

		} else
			$id = time();

		$params['sdate'] = $id;
		
		if(preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $params['eday'])) {
			list($y, $m, $d) = explode('/', $params['eday']);
			if(checkdate($m, $d, $y)) {
				$id = mktime(24, 0, 0, $m, $d, $y);
			} else
				$id = time();

		} else
			$id = time();

		$params['edate'] = $id; 

		if ($pgl = $DB->GetAll('SELECT m.name as mname, m.id as mid, CONCAT(m.name, \' \', p.name) as pname, p.id, p.quantity, g.name as gname, g.id as gid, COALESCE(SUM(s.pricebuynet), 0) as valuenet, COUNT(s.id) as count, t.name as type
			FROM stck_products p
			LEFT JOIN stck_manufacturers m ON p.manufacturerid = m.id
			LEFT JOIN stck_groups g ON p.groupid = g.id
			LEFT JOIN stck_stock s ON s.productid = p.id
			LEFT JOIN stck_types t ON p.typeid = t.id
			WHERE p.deleted = 0
			AND s.creationdate < '.$params['edate'].' AND s.creationdate > '.$params['sdate'].
			($params['manufacturer'] ? ' AND m.id = '.$params['manufacturer'] : '').
			($params['group'] ? ' AND g.id = '.$params['group'] : '').
			' GROUP BY p.id')) {
			
			foreach($pgl as $p) {
				$params['totalvn'] += $p['valuenet'];
				$params['totalvg'] += $p['valuegross'];
			}

			$SMARTY->assign('params', $params);
			$SMARTY->assign('productlist', $pgl);
			
			$SMARTY->display('stck/printstocklist.html');
		}

		//print_r($params);
	}

	break;

	case 'bdocrep':

        if (isset($_POST['params'])) {
                $params = $_POST['params'];

                if(preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $params['sday'])) {
                        list($y, $m, $d) = explode('/', $params['sday']);
                        if(checkdate($m, $d, $y)) {
                                $id = mktime(24, 0, 0, $m, $d, $y);
                        } else
                                $id = mktime(24, 0, 0, date('j'), 1, date('Y'));

                } else
                        $id = time();

                $params['sdate'] = $id;

                if(preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $params['eday'])) {
                        list($y, $m, $d) = explode('/', $params['eday']);
                        if(checkdate($m, $d, $y)) {
                                $id = mktime(24, 0, 0, $m, $d, $y);
                        } else
                                $id = time();

                } else
                        $id = time();

                $params['edate'] = $id;
		$params['colscount'] = 1;

		foreach ($params['cols'] as $k => $v) {
			$params['cols'][$v] = true;
			unset($params['cols'][$k]);
			$params['colscount']++;
		}

		if ($pgl = $DB->GetAll('SELECT srn.*, CONCAT(c.lastname, " ", c.name) as supplier
			FROM stck_receivenotes srn
			LEFT JOIN customers c ON c.id = srn.supplierid
			WHERE srn.deleted = 0
                        AND srn.creationdate < '.$params['edate'].' AND srn.creationdate > '.$params['sdate'].
                        ($params['customerid'] ? ' AND c.id = '.$params['customerid'] : ''))) {
                        foreach($pgl as $p) {
                                $params['totalvn'] += $p['netvalue'];
                                $params['totalvg'] += $p['grossvalue'];
                        }

                        $SMARTY->assign('params', $params);
                        $SMARTY->assign('productlist', $pgl);

                        $SMARTY->display('stck/printstockbdoclist.html');
                }

                print_r($params);
        }

        break;

	case "productlist":
	if (isset($_POST['params'])) {
		$params = $_POST['params'];

		$productlist = $LMSST->ProductGetList();

		$listdata['total'] = $productlist['total'];
		$listdata['direction'] = $productlist['direction'];
		$listdata['order'] = $productlist['order'];
		unset($productlist['total']);
		unset($productlist['direction']);
		unset($productlist['order']);

		$SMARTY->assign('productlist', $productlist);
		$SMARTY->assign('listdata', $listdata);
		if ($params['format'] == 'epp') {
			header('Content-Type: application/octet-stream');
			header("Content-Transfer-Encoding: Binary");
			header("Content-disposition: attachment; filename=\"products.epp\"");
			header('Content-Type: text/html; charset=windows-1250');
			$SMARTY->display('stck/printproductlist-epp.html');
		} elseif ($params['format'] == 'csv_optima_twr') {
			header('Content-Type: application/octet-stream');
                        header("Content-Transfer-Encoding: Binary");
                        header("Content-disposition: attachment; filename=\"products.csv\"");
                        header('Content-Type: text/html; charset=windows-1250');
                        $SMARTY->display('stck/printproductlist-csv_optima.html');
		}
	}
	break;
	case "manufacturerlist":
		if (isset($_POST['params'])) {
			$params = $_POST['params'];

			$manulist = $LMSST->ManufacturerGetList();
			$listdata['total'] = $productlist['total'];
                	$listdata['direction'] = $productlist['direction'];
                	$listdata['order'] = $productlist['order'];
                	unset($productlist['total']);
                	unset($productlist['direction']);
                	unset($productlist['order']);

			$SMARTY->assign('manulist', $manulist);
			$SMARTY->assign('listdata', $listdata);
			if ($params['format'] == 'csv_optima_manu') {
				header('Content-Type: application/octet-stream');
				header("Content-Transfer-Encoding: Binary");
                        	header("Content-disposition: attachment; filename=\"manufacturers.csv\"");
                        	header('Content-Type: text/html; charset=windows-1250');
                        	$SMARTY->display('stck/printproductlist-csv_optima_producenci.html');
			}
		}
	break;

	default: /*******************************************************/
	
		$layout['pagetitle'] = trans('Reports');
		
		$warehouselist = $LMSST->WarehouseGetList();
		unset($warehouselist['total']);
		unset($warehouselist['direction']);
		unset($warehouselist['order']);

		$manufacturerlist = $LMSST->ManufacturerGetList($o);
		unset($manufacturerlist['total']);
		unset($manufacturerlist['direction']);
		unset($manufacturerlist['order']);

		$grouplist = $LMSST->GroupGetList($o);
		unset($grouplist['total']);
		unset($grouplist['direction']);
		unset($grouplist['order']);

		$SMARTY->assign('warehouses', $warehouselist);
		$SMARTY->assign('manufacturers', $manufacturerlist);
		$SMARTY->assign('groups', $grouplist);
		$SMARTY->assign('customers', $LMS->getAllCustomerNames());
		$SMARTY->assign('printmenu', 'stock');
		$SMARTY->display('stck/printstockindex.html');
	break;
}

?>

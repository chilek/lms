<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

$layout['pagetitle'] = trans('Promotions List');

$promolist = $DB->GetAll('SELECT p.id, p.name, p.description, disabled,
			(SELECT COUNT(*) FROM promotionschemas
				WHERE p.id = promotionid) AS scs,
			(SELECT COUNT(DISTINCT a.tariffid)
			    FROM promotionassignments a
				JOIN promotionschemas s ON (s.id = a.promotionschemaid)
				WHERE p.id = s.promotionid
		    ) AS tariffs
		FROM promotions p
		ORDER BY p.name');

$listdata['total'] = count($promolist);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('promotionlist', $promolist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->display('promotionlist.html');

?>

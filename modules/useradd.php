<?php

/*
 * LMS version 1.1-cvs
 *
 *  (C) Copyright 2001-2003 LMS Developers
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

$useradd = $_POST['useradd'];

if(sizeof($useradd))
	foreach($useradd as $key=>$value)
		$useradd[$key] = trim($value);

if($useradd['name']=='' && $useradd['lastname']=='' && $useradd[phone1]=='' && $useradd['address']=='' && $useradd['email']=='' && isset($useradd))
{
	header('Location: ?m=useradd');
	die;
}
elseif(isset($useradd))
{

	if($useradd['lastname']=='')
		$error['username']='Pola \'nazwisko/nazwa\' oraz \'imiê\' nie mog± byæ puste!';
	
	if($useradd['address']=='')
		$error['address']='Proszê podaæ adres!';
	
	if($useradd['nip'] !='' && !eregi('^[0-9]{3}-[0-9]{3}-[0-9]{2}-[0-9]{2}$',$useradd['nip']) && !eregi('^[0-9]{3}-[0-9]{2}-[0-9]{2}-[0-9]{3}$',$useradd['nip']))
		$error['nip'] = 'Podany NIP jest b³êdny!';

	if($useradd['pesel'] != '' && !check_pesel($useradd['pesel']))
		$error['pesel'] = 'Podany PESEL jest b³êdny!';
		
	if($useradd['zip'] !='' && !eregi('^[0-9]{2}-[0-9]{3}$',$useradd['zip']))
		$error['zip'] = 'Podany kod pocztowy jest b³êdny!';

	if($useradd['gguin'] == 0)
		unset($useradd['gguin']);

	if($useradd['gguin'] !='' && !eregi('^[0-9]{4,}$',$useradd['gguin']))
		$error['gguin'] = 'Podany numer GG jest niepoprawny!';
	
	if(!$error)
	{
		$id = $LMS->UserAdd($useradd);
		if($useradd['reuse'] =='')
		{
			header('Location: ?m=userinfo&id='.$id);
			die;
		}
		$reuse['status'] = $useradd['status'];
		unset($useradd);
		$useradd = $reuse;
		$useradd['reuse'] = '1';
	}
}

if(!isset($useradd['zip']))	
	$useradd['zip'] = $_CONFIG['phpui']['default_zip'];
if(!isset($useradd['city']))	
	$useradd['city'] = $_CONFIG['phpui']['default_city'];
if(!isset($useradd['address']))	
	$useradd['address'] = $_CONFIG['phpui']['default_address'];

$layout['pagetitle']='Nowy u¿ytkownik';

$SMARTY->assign('layout',$layout);
$SMARTY->assign('useradd',$useradd);
$SMARTY->assign('error',$error);
$SMARTY->display('useradd.html');

/*
 * $Log$
 * Revision 1.40  2003/12/04 03:43:51  lukasz
 * - dodany PESEL do rekordu u¿ytkownika, upgrade bazy
 *   Je¿eli u¿ytkownik nie posiada NIPu, to wtedy na fakturze umieszczany jest
 *   PESEL.
 * - do faktur zosta³o dodane miejsce wystawienia
 * - zamiana nazewctwa w tabelach z 'sww' na 'pkwiu'
 * - przegenerowane doce
 * - TODO: je¿eli na fakturze nie ma pozycji z pkwiu, to usun±æ t± kolumenê
 *   z faktury.
 * - w cholerê kosmetyki
 *
 * Revision 1.39  2003/11/22 17:32:12  alec
 * poprawka http://lists.rulez.pl/lms/1482.html
 *
 * Revision 1.38  2003/11/22 17:11:58  alec
 * po co tu GetTariffs?
 *
 * Revision 1.37  2003/09/09 01:22:28  lukasz
 * - nowe finanse
 * - kosmetyka
 * - bugfixy
 * - i inne rzeczy o których aktualnie nie pamiêtam
 *
 * Revision 1.36  2003/09/05 13:11:24  lukasz
 * - nowy sposób wy¶wietlania informacji o b³êdach
 *
 * Revision 1.35  2003/08/24 13:12:54  lukasz
 * - massive attack: s/<?/<?php/g - that was causing problems on some fucked
 *   redhat's :>
 *
 * Revision 1.34  2003/08/18 16:52:19  lukasz
 * - added CVS Log tags
 *
 */
?>

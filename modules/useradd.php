<?php

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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

	if($useradd['gguin'] == '')
		$useradd['gguin'] = 0;
	
	if($useradd['pin'] == '')
		$useradd['pin'] = 0;

	if($useradd['gguin'] !=0 && !eregi('^[0-9]{4,}$',$useradd['gguin']))
		$error['gguin'] = 'Podany numer GG jest niepoprawny!';

        if($userdata['pin']!=0 && !eregi('^[0-9]{4,6}$',$useradd['pin']))
	        $error['pin'] = 'Podany numer PIN jest niepoprawny!';

	if($userdata['email']!='' && !check_email($userdata['email']))
		$error['email'] = 'Podany email nie wydaje siê byæ poprawny!';

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
	$useradd['zip'] = $LMS->CONFIG['phpui']['default_zip'];
if(!isset($useradd['city']))	
	$useradd['city'] = $LMS->CONFIG['phpui']['default_city'];
if(!isset($useradd['address']))	
	$useradd['address'] = $LMS->CONFIG['phpui']['default_address'];

$layout['pagetitle'] = 'Nowy u¿ytkownik';

$SMARTY->assign('useradd',$useradd);
$SMARTY->assign('error',$error);
$SMARTY->display('useradd.html');

?>

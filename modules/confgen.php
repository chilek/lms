<?

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

function GetTemplatesList()
{
	global $LMS;
	if($handle = @opendir($LMS->CONFIG['directories']['config_templates_dir']))
	{
		while(FALSE !== ($file = readdir($handle)))
			if(ereg('^.*\.tpl$',$file))
				$templates[] = ereg_replace('\.tpl$','',$file);
		closedir($handle);
	}
	return $templates;
}

function GetTemplateArrays()
{
	global $LMS;

	$result['users'] = $LMS->DB->GetAllByKey('SELECT * FROM users','id');
	$result['nodes'] = $LMS->DB->GetAllByKey('SELECT * FROM nodes ORDER BY ipaddr ASC','id');
	$result['tariffs'] = $LMS->DB->GetAllByKey('SELECT * FROM tariffs','id');
	$result['networks'] = $LMS->DB->GetAllByKey('SELECT *, address AS addresslong, inet_ntoa(address) AS address FROM networks','id');
		
	$temp['balance'] = $LMS->DB->GetAllByKey('SELECT users.id AS id, SUM((type * -2 + 7) * cash.value) AS balance FROM users LEFT JOIN cash ON users.id = cash.userid GROUP BY users.id','id');
	$temp['finances'] = $LMS->DB->GetAllByKey('SELECT userid, SUM(value) AS value, SUM(uprate) AS uprate, SUM(downrate) AS downrate FROM assignments LEFT JOIN tariffs ON tariffs.id = assignments.tariffid WHERE (datefrom <= ?NOW? OR datefrom = 0) AND (dateto > ?NOW? OR dateto = 0) GROUP BY userid','userid');

	foreach($temp['balance'] as $balance)
		$result['users'][$balance['id']]['balance'] = $balance['balance'];
		
	foreach($temp['finances'] as $userid => $financesrecord)
	{
		$result['users'][$userid]['uprate'] = $financesrecord['uprate'];
		$result['users'][$userid]['downrate'] = $financesrecord['downrate'];
		$result['users'][$userid]['value'] = $financesrecord['value'];
	}

	foreach($result['nodes'] as $nodeid => $noderecord)
	{
		$result['users'][$noderecord['ownerid']]['nodes'][$nodeid] = &$result['nodes'][$nodeid];
		$result['nodes'][$nodeid]['owner'] = &$result['users'][$noderecord['ownerid']];
		$result['nodes'][$nodeid]['iplong'] = $noderecord['ipaddr'];
		$result['nodes'][$nodeid]['ipaddr'] = long2ip($noderecord['ipaddr']);
	}

	foreach($result['networks'] as $networkid => $networkrecord)
	{
		$result['networks'][$networkid]['addresslong'] = $networkrecord['addresslong'];
		$result['networks'][$networkid]['endaddresslong'] = ip_long(getbraddr($networkrecord['address'],$networkrecord['mask']));
		$result['networks'][$networkid]['prefix'] = mask2prefix($networkrecord['mask']);
		if($networknodes = $LMS->DB->GetCol('SELECT id FROM nodes WHERE ipaddr >= ? AND ipaddr <= ?', array($result['networks'][$networkid]['addresslong'],$result['networks'][$networkid]['endaddresslong'])))
			foreach($networknodes as $nodeid)
			{
				$result['networks'][$networkid]['nodes'][$nodeid] = &$result['nodes'][$nodeid];
				$result['nodes'][$nodeid]['network'] = &$result['networks'][$networkid];
			}
	}

	return $result;
}

// zacznijmy od budowania listy hostów:

if($_GET['tpl'] == '')
{
	$layout['pagetitle'] = 'Configuration files';
	$templateslist = GetTemplatesList();
	$SMARTY->assign('templateslist',$templateslist);
	$SMARTY->display('confgen.html');
}
else
{
	echo '<PRE>';
	$arrays = GetTemplateArrays();
	foreach($arrays as $arrayname => $array)
		$SMARTY->assign($arrayname, &$array);
	$SMARTY->template_dir = $LMS->CONFIG['directories']['config_templates_dir'];
	$SMARTY->left_delimiter = '<?';
	$SMARTY->right_delimiter = '?>';
	$SMARTY->display($_GET['tpl'].'.tpl');
}

?>

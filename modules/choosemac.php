<?php

$p = $_GET[p];
$netid = $_POST[netid];

if(!isset($p))
	$js = "var targetfield = window.opener.targetfield;";
if($p == "main")
	$js = "var targetfield = parent.targetfield;";

$layout[pagetitle] = "Wybierz adres MAC";

$SMARTY->assign("layout",$layout);
$SMARTY->assign("part",$p);
$SMARTY->assign("js",$js);
if($p == "main")
{
	$maclist = $LMS->GetMACs();
	if($LMS->CONFIG[phpui][arpd_servers])
	{
		$servers = split(' ',eregi_replace("[\t ]+"," ",$LMS->CONFIG[phpui][arpd_servers]));
		foreach($servers as $server)
		{
			list($addr,$port) = split(':',$server);
			if($port == "")
				$port = 1029;
			$maclist = array_merge($maclist,$LMS->GetRemoteMACs($addr,$port));
		}
	}
	$SMARTY->assign("maclist",$maclist);
}
$SMARTY->display("choosemac.html");

/*
 * $Log$
 * Revision 1.10  2003/12/04 04:39:14  lukasz
 * - porz±dki
 * - trochê pod³ubane przy parsowaniu pliku konfiguracyjnego
 *
 * Revision 1.9  2003/09/17 03:10:39  lukasz
 * - very experimental support for lms-arpd
 *
 * Revision 1.8  2003/08/24 13:12:54  lukasz
 * - massive attack: s/<?/<?php/g - that was causing problems on some fucked
 *   redhat's :>
 *
 * Revision 1.7  2003/08/18 16:52:19  lukasz
 * - added CVS Log tags
 *
 */
?>

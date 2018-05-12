<?php

/*
 * LMS version 1.11.8 Belus
 *
 *  (C) Copyright 2001-2009 LMS Developers
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
 *  $Id: plugin.php,v 1.8 2009/01/13 07:45:33 alec Exp $
 */

global $SMARTY,$CONFIG,$LMS;

//global $SMARTY;

/* short example of errors handling

if(isset($_POST['document']))
{
	$error['notes'] = 'Error';
	$result = 'Error';
	return;
}

*/

// Notice: $customer consist selected customer ID

$result = $SMARTY->fetch(DOC_DIR.'/templates/'.$engine['name'].'/plugin.html');

if(isset($document['kwota'])) {
$kwota = $document['kwota'];
}
else
{
$kwota = trim($customer['balance']*-1);
}



//$kwota = $document['kwota'];

$KWOTA_NR = str_replace(',','.',$kwota);  // na wszelki wypadek
$KWOTA_GR = sprintf('%02d',round(($KWOTA_NR - floor($KWOTA_NR))*100));

$SHORT_TO_WORDS = 0;
//$SHORT_TO_WORDS = chkconfig($CONFIG['phpui']['to_words_short_version']);

if($SHORT_TO_WORDS)
{
        $KWOTA_ZL = to_words(floor($KWOTA_NR), 0, '', 1);
//        $KWOTA_ZL = iconv('UTF-8','ISO-8859-2',$KWOTA_ZL);
        $KWOTA_X = $KWOTA_ZL .' '. $KWOTA_GR. '/100';
}
else
{
        $KWOTA_ZL = to_words(floor($KWOTA_NR));
//      $KWOTA_ZL = iconv('UTF-8','ISO-8859-2',$KWOTA_ZL);
        $KWOTA_X = $KWOTA_ZL .' '. $KWOTA_GR. '/100 złotych';
}

$SMARTY->assign( 'kwota',$kwota);
$SMARTY->assign( 'kwota_x',$KWOTA_X);

?>

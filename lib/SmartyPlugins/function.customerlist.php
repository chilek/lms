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

function smarty_function_customerlist($params, $template)
{
        $result = '';
        $result = '<SCRIPT type="text/javascript">'
                . 'function reset_customer_select(){'
                . 'if (document.forms[\''.$params['form'].'\'].elements[\''.$params['inputname'].'\'].value)'
                . '        document.forms[\''.$params['form'].'\'].elements[\''.$params['selectname'].'\'].value = document.forms[\''.$params['form'].'\'].elements[\''.$params['inputname'].'\'].value;}'
                . 'function reset_customer_input(){'
                . 'if (document.forms[\''.$params['form'].'\'].elements[\''.$params['selectname'].'\'].value)'
                . '        document.forms[\''.$params['form'].'\'].elements[\''.$params['inputname'].'\'].value = document.forms[\''.$params['form'].'\'].elements[\''.$params['selectname'].'\'].value;}'
		. '</SCRIPT>';
                
        if(!empty($params['customers'])){
                $result .= '<SELECT name="'.$params['selectname'].'" value="'.$params['selected'].'"'
                        . smarty_function_tip(array('text'=>'Select customer (optional)'), $template)
                        . 'onChange="reset_customer_input(); '
                        . (!empty($params['customOnChange']) ? $params['customOnChange'] : '')
                        . '"><OPTION value="0"';
                if(empty($params['selected'])) 
                        $result .= 'SELECTED';
                $result .= '>'.trans("- select customer -").'</OPTION>';
                foreach($params['customers'] as $customer){
                        $result .= '<OPTION value="'.$customer['id'].'"';
                        if($customer['id'] == $params['selected'])
                                $result .= 'SELECTED';
                        $result .= '>'.substr($customer['customername'], 0 , 40).' ('.sprintf("%04d", $customer['id']).')</OPTION>';                        
                }
                $result .= '</SELECT>&nbsp;'
                        . trans("or Customer ID:");
        } else {
	        $result = trans("ID:");
        }
	$result .= '&nbsp;<INPUT TYPE="TEXT" NAME="'.$params['inputname'].'" VALUE="'.$params['selected'].'" SIZE="5" ';
                if(!empty($params['customers'])){
	                $result .= 'onChange="reset_customer_select(); '
			. (!empty($params['customOnChange']) ? $params['customOnChange'] : '')
			. '" onfocus="reset_customer_select(); '
			. (!empty($params['customOnChange']) ? $params['customOnChange'] : '')
			. '" ';
		} else {
                        $result .= ' onblur="'
				. (!empty($params['customOnChange']) ? $params['customOnChange'] : '')
				. '" onfocus="'
				. (!empty($params['customOnChange']) ? $params['customOnChange'] : '')
				. '" oninput="'
				. (!empty($params['customOnChange']) ? $params['customOnChange'] : '')
				. '" ';
		}
	        $result .= smarty_function_tip(array('text'=>'Enter customer ID', 'trigger'=>'customerid'), $template)
	                . '><a href="javascript: void(0);" onClick="return customerchoosewin(document.forms[\''.$params['form'].'\'].elements[\''.$params['inputname'].'\']);" '
	                . smarty_function_tip(array('text'=>'Click to search customer'), $template).'>&nbsp;'
	                . trans("Search").'&nbsp;&raquo;&raquo;&raquo;</A>';
        
	return $result;
}

?>

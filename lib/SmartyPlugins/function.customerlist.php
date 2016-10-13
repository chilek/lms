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

function smarty_function_customerlist($params, $template) {
	$result = '';

	if (!empty($params['customers'])) {

		$result .= sprintf('<SELECT name="%s" value="%s" ', $params['selectname'], $params['selected']);

		if (!empty($params['selecttip']))
			$result .= smarty_function_tip(array('text' => $params['selecttip']), $template);
		else
			$result .= smarty_function_tip(array('text' => 'Select customer (optional)'), $template);

		$result .= sprintf('onChange="reset_customer(\'%s\', \'%s\', \'%s\'); ', $params['form'], $params['selectname'], $params['inputname']);

		if (!empty($params['customOnChange']))
			$result .= $params['customOnChange'];

		$result .= '">';

		if (array_key_exists('firstoption', $params)) {
			if (!empty($params['firstoption'])) {
				$result .= '<OPTION value="0"';
				if (empty($params['selected']))
					$result .= 'selected';
				$result .= '>' . trans($params['firstoption']) . '</OPTION>';
			}
		} else {
			$result .= '<OPTION value="0"';
			if (empty($params['selected']))
				$result .= 'selected';
			$result .= '>' . trans("- select customer -") . '</OPTION>';
		}
		foreach ($params['customers'] as $customer) {
			$result .= '<OPTION value="' . $customer['id'] . '"';
			if ($customer['id'] == $params['selected'])
				$result .= 'selected';
			$result .= '>' . mb_substr($customer['customername'], 0 , 40) . ' (' . sprintf("%04d", $customer['id']) . ')</OPTION>';
		}
		$result .= '</SELECT>&nbsp;' . trans("or Customer ID:");
	} else
		$result .= trans("ID:");
	$result .= '<INPUT type="text" name="' . $params['inputname'] . '" value="' . $params['selected'] . '" size="5" ';

	$on_change = !empty($params['customOnChange']) ? $params['customOnChange'] : '';

	if (!empty($params['customers'])) {
		$reset_customer = "reset_customer('${params['form']}', '${params['inputname']}', '${params['selectname']}'); ${on_change}";
		$result .= "onChange=\"${reset_customer}\" onFocus=\"${reset_customer}\"";
	} else
		$result .= sprintf(' onblur="%1$s" onfocus="%1$s" oninput="%1$s" ', $on_change . ';getCustomerName(this)');

	if (!empty($params['inputtip']))
		$result .= smarty_function_tip(array('text' => $params['inputtip']), $template);
	else
		$result .= smarty_function_tip(array('text' => 'Enter customer ID', 'trigger' => 'customerid'), $template);

	$result .= '>';
	if (empty($params['customers']))
		$result .= '<script type="text/javascript">getCustomerName($(\'[name="' . $params['inputname']. '"]\').get(0));</script>';
	$result .= '<a href="javascript: void(0);" onClick="return customerchoosewin(document.forms[\'' . $params['form'] . '\'].elements[\'' . $params['inputname'] . '\']);" ';
	$result .= smarty_function_tip(array('text' => 'Click to search customer'), $template) . '>&nbsp;';
	$result .= trans("Search") . '&nbsp;&raquo;&raquo;&raquo;</A>';

	if (empty($params['customers']))
		$result .= '&nbsp;&nbsp;&nbsp;<span></span>';

	return $result;
}

?>

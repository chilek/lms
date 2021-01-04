<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

class LMSSmartyPlugins
{
    public static function buttonFunction(array $params, $template)
    {
        // optional - we want buttons without icon
        $icon = isset($params['icon']) ? $params['icon'] : null;
        // optional - button by default,
        $type = isset($params['type']) ? $params['type'] : 'button';
        // optional - text tip,
        $tip = isset($params['tip']) ? trans($params['tip']) : null;
        // optional - button with icon only don't use label
        $label = isset($params['label']) ? trans($params['label']) : null;
        // optional - href attribute of link type button
        $href = isset($params['href']) ? trans($params['href']) : null;
        // optional - allow to easily attach event handler in jquery,
        $id = isset($params['id']) ? $params['id'] : null;
        // optional - additional css classes which are appended to class attribute
        $class = isset($params['class']) ? $params['class'] : null;
        // optional - allow to specify javascript code lauched after click,
        $onclick = isset($params['onclick']) && !empty($params['onclick']) ? $params['onclick'] : null;
        // optional - if open in new window after click
        $external = isset($params['external']) && $params['external'];
        // optional - data-resourceid attribute value
        $resourceid = isset($params['resourceid']) ? $params['resourceid'] : null;
        // optional - if element should be initially visible
        $visible = isset($params['visible']) ? $params['visible'] : true;
        // optional - keyboard shortcut
        $accesskey = isset($params['accesskey']) ? $params['accesskey'] : null;
        // optional - contents copied to clipboard
        $clipboard = isset($params['clipboard']) ? $params['clipboard'] : null;
        // optional - form id
        $form = isset($params['form']) ? $params['form'] : null;

        $data_attributes = '';
        foreach ($params as $name => $value) {
            if (strpos($name, 'data_') === 0) {
                $data_attributes .= ' ' . str_replace('_', '-', $name) . '=\'' . $value . '\'';
            }
        }

        return '<' . ($type == 'link' || $type == 'link-button' ? 'a' : 'button type="' . $type . '"')
            . ($type == 'link' || $type == 'link-button'
                ? ($href ? ' href="' . $href . '"' : '')
                : ($onclick || !$href ? '' : ' onclick="location.href = \'' . $href . '\';"'))
            . ' class="lms-ui-button' . ($type == 'link-button' ? ' lms-ui-link-button ' : '')
            . ($class ? ' ' . $class : '') . '"'
            . ($id ? ' id="' . $id . '"' : '') . ((($type == 'button' && empty($href)) || $type != 'button') && $onclick ? ' onclick="' . $onclick . '"' : '')
            . ($form ? ' form="' . $form . '"' : '')
            . ($tip ? ' title="' . $tip . '" data-title="' . $tip . '"' : '')
            . ($external ? ' rel="external"' : '')
            . ($resourceid ? ' data-resourceid="' . $resourceid . '"' : '')
            . ($clipboard ? ' data-clipboard-text="' . $clipboard . '"' : '')
            . $data_attributes
            . ($visible ? '' : ' style="display: none;"')
            . ($accesskey ? ' accesskey="' . $accesskey . '"' : '') . '>'
            . ($icon ? '<i class="' . (strpos($icon, 'lms-ui-icon-') === 0 || strpos($icon, 'fa') === 0 ? $icon : 'lms-ui-icon-' . $icon) . '"></i>' : '')
            . ($label ? '<span class="lms-ui-label">' . $label . '</span>' : '') . '
		</' . ($type == 'link' || $type == 'link-button' ? 'a' : 'button') . '>';
    }

    public static function currencySelectionFunction(array $params, $template)
    {
        $elementname = isset($params['elementname']) ? $params['elementname'] : 'currency';
        $selected = isset($params['selected']) && isset($GLOBALS['CURRENCIES'][$params['selected']])
            ? $params['selected'] : null;
        $locked = isset($params['locked']) && $params['locked'];
        if (function_exists('get_currency_value') && !$locked) {
            $result = '<select name="' . $elementname . '" ' . self::tipFunction(array('text' => 'Select currency'), $template)
                . (isset($params['form']) ? ' form="' . $params['form'] . '"' : '') . '>';
            foreach ($GLOBALS['CURRENCIES'] as $currency) {
                $result .= '<option value="' . $currency . '"'
                    . ($currency == $selected ? ' selected' : '') . '>' . $currency . '</option>';
            }
            $result .= '</select>';
        } else {
            $result = Localisation::getCurrentCurrency() . '<input type="hidden" name="' . $elementname . '"'
                . (isset($params['form']) ? ' form="' . $params['form'] . '"' : '') . ' value="'
                . Localisation::getCurrentCurrency() . '">';
        }

        return $result;
    }

    public static function divisionSelectionFunction(array $params, $template)
    {
        static $user_divisions = array();
        $lms = LMS::getInstance();
        $layout = $template->getTemplateVars('layout');
        $force_global_division_context = ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.force_global_division_context'), false);

        if (empty($params)) {
            $params = array();
        }

        $label = isset($params['label']) ? $params['label'] : null;
        $name = isset($params['name']) ? $params['name'] : 'division';
        $id = isset($params['id']) ? $params['id'] : $name;
        $selected = isset($params['selected']) ? $params['selected'] : null;
        $superuser = isset($params['superuser']) && !empty($params['superuser']) ? $params['superuser'] : null;
        $onchange = isset($params['onchange']) && !empty($params['onchange']) ? $params['onchange'] : null;

        if (isset($user_divisions) && empty($user_divisions)) {
            if ($force_global_division_context) {
                if (!empty($layout['division'])) {
                    $user_divisions = $lms->GetDivision($layout['division']);
                }
            } else {
                $user_divisions = (empty($superuser) ? $lms->GetDivisions(array('userid' => Auth::GetCurrentUser())) : $lms->GetDivisions());
            }
        }

        $result = '';

        if ($force_global_division_context) {
            $result .= ($label ? '<label>' : '') . ($label ? trans($label) : '');
            $result .= '<span class="division-context bold">' . (!empty($user_divisions) ? $user_divisions['shortname'] : trans("all")) . '</span>';
            $result .= ($label ? '</label>' : '');
            $result .= '<input type="hidden" class="division-context-selected" name="' . $name . '"'
                . (isset($params['form']) ? ' form="' . $params['form'] . '"' : '') . ' value="'
                . $layout['division'] . '">';
        } else {
            if (!empty($user_divisions) && count($user_divisions) > 1) {
                $result .= ($label ? '<label for="' . $name . '">' : '') . ($label ? trans($label) : '') . ($label ? '&nbsp;' : '');
                $result .= '<select class="division-context" id="' . $id . '" name="' . $name . '" ' . self::tipFunction(array('text' => 'Select division'), $template)
                    . (isset($params['form']) ? ' form="' . $params['form'] . '"' : '')
                    . ($onchange ? ' onchange="' . $onchange . '"' : '')
                    . '>';
                $result .= '<option VALUE=""' . (!$selected ? ' selected' : '') . '>- ' . trans("all") . ' -</option>';
                foreach ($user_divisions as $division) {
                    $result .= '<option value="' . $division['id'] . '"'
                        . ($selected == $division['id'] ? ' selected' : '') . '>' . $division['label'] . '</option>';
                }
                $result .= '</select>';
                $result .= ($label ? '</label>' : '');
            } else {
                $user_division = reset($user_divisions);
                $result .= ($label ? '<label>' : '') . ($label ? trans($label) : '');
                $result .= '<span class="division-context bold">' . (!empty($user_divisions) ? $user_division['shortname'] : trans("all")) . '</span>';
                $result .= ($label ? '</label>' : '');
                $result .= '<input type="hidden" class="division-context-selected" name="' . $name . '"'
                    . (isset($params['form']) ? ' form="' . $params['form'] . '"' : '') . ' value="'
                    . $user_division['id'] . '">';
            }
        }

        return $result;
    }

    public static function customerListFunction(array $params, $template)
    {
        $result = '';

        $version = isset($params['version']) && intval($params['version']) ? intval($params['version']) : 1;

        $customername = !isset($params['customername']) || $params['customername'];

        if (isset($params['selected']) && !preg_match('/^[0-9]+$/', $params['selected'])) {
            $params['selected'] = '';
        }

        $result .= '<div class="lms-ui-customer-select-container" data-version="' . $version . '"'
            . ($version == 2 ? ' data-show-id="1"' : '') . '>' . PHP_EOL;

        if (!empty($params['customers'])) {
            $result .= sprintf('<select name="%s" value="%s" ', $params['selectname'], $params['selected']);

            if (!empty($params['select_id'])) {
                $result .= 'id="' . $params['select_id'] . '" ';
            }

            if (!empty($params['selecttip'])) {
                $result .= self::tipFunction(array('text' => $params['selecttip']), $template);
            } else {
                $result .= self::tipFunction(array('text' => 'Select customer (optional)'), $template);
            }

            if (!empty($params['customOnChange'])) {
                $result .= ' onChange="' . $params['customOnChange'] . '"';
            }

            $result .= '">' . PHP_EOL;

            if (isset($params['firstoption'])) {
                if (!empty($params['firstoption'])) {
                    $result .= '<option value="0"';
                    if (empty($params['selected'])) {
                        $result .= ' selected';
                    }
                    $result .= '>' . trans($params['firstoption']) . '</option>';
                }
            } else {
                $result .= '<option value="0"';
                if (empty($params['selected'])) {
                    $result .= ' selected';
                }
                $result .= '>' . trans("- select customer -") . '</option>';
            }
            foreach ($params['customers'] as $customer) {
                $result .= '<option value="' . $customer['id'] . '"';
                if ($customer['id'] == $params['selected']) {
                    $result .= ' selected';
                }
                $result .= '>' . mb_substr($customer['customername'], 0, 40) . '</option>' . PHP_EOL;
            }
            $result .= '</select>' . PHP_EOL
                . '<div class="lms-ui-customer-select">' . PHP_EOL
                . '<span>' . trans("or Customer ID:") . '</span>' . PHP_EOL;
        } else {
            $result .=  '<div class="lms-ui-customer-select">' . PHP_EOL;
            if ($version < 2) {
                $result .= '<span>' . trans('ID') . '</span>' . PHP_EOL;
            }
        }

        if ($version == 2) {
            $result .= '<div class="lms-ui-customer-select-suggestion-container"></div>' . PHP_EOL;
        }

        $result .= '<input type="text" name="' . $params['inputname'] . '"' . (empty($params['selected']) ? '' : ' value="'
            . $params['selected'] . '"') . ' class="lms-ui-customer-select-customerid" data-prev-value="' . $params['selected'] . '" size="5"';

        if (!empty($params['input_id'])) {
            $result .= ' id="' . $params['input_id'] . '"';
        }

        if (isset($params['required']) && $params['required']) {
            $result .= ' required';
        }

        if (!empty($params['customOnChange'])) {
            $result .= ' onChange="' . $params['customOnChange'] . '"';
        }

        $result .= empty($params['customers']) && $customername ? ' data-customer-name="1"' : '';

        if ($version < 2) {
            if (!empty($params['inputtip'])) {
                $result .= ' ' . self::tipFunction(array('text' => $params['inputtip']), $template);
            } else {
                $result .= ' ' . self::tipFunction(array('text' => 'Enter customer ID', 'trigger' => 'customerid'), $template);
            }
        }

        $result .= '>' . PHP_EOL;

        if ($version == 2) {
            $result .= '<input type="text"'
                . ' placeholder="' . trans('Search for customer')
                . '" ' . self::tipFunction(
                    array(
                        'text' => 'Search for customer',
                        'trigger' => 'customerid',
                        'class' => 'lms-ui-customer-select-suggestion-input lms-ui-autogrow'
                    ),
                    $template
                )
                . '">' . PHP_EOL;
            $result .= '<div ' . self::tipFunction(array('text' => 'Click to reset customer selection', 'class' => 'lms-ui-customer-function-button'), $template) . '>' . PHP_EOL
                . '<i class="lms-ui-icon-clear fa-fw"></i>' . PHP_EOL . '</div>' . PHP_EOL;
        } else {
            $result .= '<div ' . self::tipFunction(array('text' => 'Click to search customer', 'class' => 'lms-ui-customer-function-button'), $template) . '>' . PHP_EOL
                . '<i class="lms-ui-icon-search fa-fw"></i>' . PHP_EOL . '</div>' . PHP_EOL;
        }

        $result .= '</div>' . PHP_EOL;

        if (empty($params['customers'])) {
            $result .= '<span class="lms-ui-customer-select-name">' . PHP_EOL
                . ($version == 2 ? '<a href=""></a>' : '') . '</span>' . PHP_EOL;
        }

        $result .= '</div>' . PHP_EOL;

        return $result;
    }

    public static function fileUploadFunction(array $params, $template)
    {
        static $vars = array('id', 'fileupload');

        $result = '';
        foreach ($vars as $var) {
            if (array_key_exists($var, $params)) {
                $$var = $params[$var];
            } else {
                return $result;
            }
        }

        $form = isset($params['form']) ? $params['form'] : null;

        // special treatment of file upload errors marked in error associative array
        $tmpl = $template->getTemplateVars('error');
        if (isset($tmpl[$id . '_button'])) {
            $error_variable = $id . '_button';
        } elseif (isset($tmpl['files'])) {
            $error_variable = 'files';
        }
        if (isset($error_variable)) {
            $error_tip_params = array(
                'text' => $tmpl[$error_variable],
                'trigger' => $id . '_button'
            );
        }

        $result = '<div class="lms-ui-fileupload" id="' . $id . '">
			<div class="fileupload" id="' . $id . '-progress-dialog" title="' . trans("Uploading files ...") . '" style="display: none;">
				<div style="padding: 10px;">' . trans("Uploading files to server ...") . '</div>
				<div class="fileupload-progressbar"><div class="fileupload-progress-label"></div></div>
			</div>
			<div class="lms-ui-button-fileupload-container">
				<button type="button" class="lms-ui-button-fileupload lms-ui-button' . (isset($error_tip_params) ? ' lms-ui-error' : '') . '" id="' . $id . '_button" '
            . (isset($error_tip_params) ? self::tipFunction($error_tip_params, $template) : '') . '><i class="lms-ui-icon-fileupload"></i><span class="lms-ui-label">' . trans("Select files") . '</span></button>
				<INPUT name="' . $id . '[]" type="file" multiple class="fileupload-select-btn" style="display: none;" ' . ($form ? ' form="' . $form . '"' : '') . '>
				' . (ConfigHelper::getConfig('phpui.uploaded_image_max_size', 0)
                    ? '<label><input type="checkbox" class="dont-scale-images" value="1">' . trans("don't scale images") . '</label>'
                    : '') . '
			</div>
			<div class="fileupload-files">';
        if (!empty($fileupload) && isset($fileupload[$id])) {
            foreach ($fileupload[$id] as $fileidx => $file) {
                $result .= '<div>
					<a href="#" class="fileupload-file"><i class="fas fa-trash"></i>
						' . $file['name'] . ' (' . $file['sizestr'] . ')
					</a>
					<input type="hidden" name="fileupload[' . $id . '][' . $fileidx . '][name]" value="' . $file['name'] . '" ' . ($form ? ' form="' . $form . '"' : '') . '>
					<input type="hidden" class="fileupload-file-size" name="fileupload[' . $id . '][' . $fileidx . '][size]" value="' . $file['size'] . '" ' . ($form ? ' form="' . $form . '"' : '') . '>
					<input type="hidden" name="fileupload[' . $id . '][' . $fileidx . '][type]" value="' . $file['type'] . '" ' . ($form ? ' form="' . $form . '"' : '') . '>
				</div>';
            }
        }
        $result .= '</div>
			<div class="fileupload-status lms-ui-error bold">
			</div>
			<input type="hidden" class="fileupload-tmpdir" name="fileupload[' . $id . '-tmpdir]" value="' . $fileupload[$id . '-tmpdir'] . '" ' . ($form ? ' form="' . $form . '"' : '') . '>
		</div>';
        $result .= '<script>
			$(function() {
				new lmsFileUpload("' . $id . '"' . ($form ? ', "' . $form . '"' : '') . ');
			});
		</script>';

        return $result;
    }

    public static function locationBoxFunction(array $params, $template)
    {
        static $countries = array();
        static $states = array();

        $lms = LMS::getInstance();

        if (empty($params)) {
            $params = array();
        }

        // generate unique id for location box
        $LOCATION_ID = 'lmsui-' . uniqid();

        // input size
        define('INPUT_SIZE', 30);

        // base name for localization inputs
        $input_name             = 'location';
        $input_name_country_id  = 'location_country_id';
        $input_name_state       = 'location_state_name';
        $input_name_state_id    = 'location_state';
        $input_name_city        = 'location_city_name';
        $input_name_city_id     = 'location_city';
        $input_name_street      = 'location_street_name';
        $input_name_street_id   = 'location_street';
        $input_name_house       = 'location_house';
        $input_name_flat        = 'location_flat';
        $input_name_location    = 'location_name';
        $input_name_zip         = 'location_zip';
        $input_name_postoffice  = 'location_postoffice';
        $input_name_teryt       = 'teryt';
        $input_name_def_address = 'location_def_address';
        $input_name_address_id  = 'address_id';

        // check if prefix for input names is set
        if (isset($params['prefix']) && mb_strlen(trim($params['prefix'])) > 0) {
            $p = trim($params['prefix']);

            $input_name             = $p . '[' . $input_name             . ']';
            $input_name_country_id  = $p . '[' . $input_name_country_id  . ']';
            $input_name_state       = $p . '[' . $input_name_state       . ']';
            $input_name_state_id    = $p . '[' . $input_name_state_id    . ']';
            $input_name_city        = $p . '[' . $input_name_city        . ']';
            $input_name_city_id     = $p . '[' . $input_name_city_id     . ']';
            $input_name_street      = $p . '[' . $input_name_street      . ']';
            $input_name_street_id   = $p . '[' . $input_name_street_id   . ']';
            $input_name_house       = $p . '[' . $input_name_house       . ']';
            $input_name_flat        = $p . '[' . $input_name_flat        . ']';
            $input_name_location    = $p . '[' . $input_name_location    . ']';
            $input_name_zip         = $p . '[' . $input_name_zip         . ']';
            $input_name_postoffice  = $p . '[' . $input_name_postoffice  . ']';
            $input_name_teryt       = $p . '[' . $input_name_teryt       . ']';
            $input_name_def_address = $p . '[' . $input_name_def_address . ']';
            $input_name_address_id  = $p . '[' . $input_name_address_id  . ']';

            unset($prefix);
        }

        echo '<div class="location-box">';

        echo '<fieldset class="lms-ui-address-box" id="' . $LOCATION_ID . '">';

        if (isset($params['address_id']) && $params['address_id'] != null) {
            echo '<input type="hidden" value="' . $params['address_id']  . '" name="' . $input_name_address_id . '">';
        }

        echo '<div class="lms-ui-address-box-container">';
        echo '<div class="lms-ui-address-box-properties">';

        echo '<table>';

        echo '<tr' . (isset($params['hide_name']) ? ' style="display: none;"' : '') . '>
              <td>' . trans('Name') . '</td>
              <td>
                  <input type="text"   value="' . (!empty($params['location_name']) ? htmlspecialchars($params['location_name']) : '' ) . '" name="' . $input_name_location . '" size="' . INPUT_SIZE . '" data-address="location-name">
                  <input type="hidden" value="' . (($params['location'])            ? $params['location']      : '')  . '" name="' . $input_name . '" data-address="location">
              </td>
          </tr>';

        echo '<tr>
              <td>' . trans('State') . '</td>
              <td>';

        if (isset($states) && empty($states)) {
            $states = $lms->GetCountryStates();
        }

        if ($states) {
            echo '<select name="' . $input_name_state . '" style="height: 16px;';
            if (!empty($params['teryt'])) {
                echo 'display: none;';
            }
            echo '" data-address="state-select">';
            echo '<option></option>';

            $tmp_state = mb_strtolower($params['location_state_name']);

            foreach ($states as $v) {
                echo '<option ' . (!empty($v) && mb_strtolower($v['name']) == $tmp_state ? 'selected' : '')  . '>' . $v['name'] . '</option>';
            }

            unset($tmp_state);

            echo '</select>';
        }

        echo '<input type="text"
                 value="' . (!empty($params['location_state_name']) ? htmlspecialchars($params['location_state_name']) : '' ) . '"
                 size="' . INPUT_SIZE . '"
                 data-address="state"
                 name="' . $input_name_state . '"
                 ' . (empty($params['teryt']) ? 'style="display:none;"' : '') . '
                 maxlength="64">

          <input type="hidden" value="' . (!empty($params['location_state']) ? $params['location_state'] : '' ) . '" data-address="state-hidden" name="' . $input_name_state_id . '">
          </td>
          </tr>';

        echo '<tr>
              <td>' . trans('City') . '</td>
              <td>
                  <input type="text"   value="'
                    . (!empty($params['location_city_name']) ? htmlspecialchars($params['location_city_name']) : '' )
                    . '" size="' . INPUT_SIZE . '" data-address="city" name="' . $input_name_city . '" maxlength="32"'
                    . ($params['location_address_type'] == BILLING_ADDRESS ? ' required' : '') . '>
                  <input type="hidden" value="' . (!empty($params['location_city'])      ? $params['location_city']      : '' ) . '" data-address="city-hidden" name="' . $input_name_city_id . '">
              </td>
          </tr>';

        echo '<tr>
              <td>' . trans('Street') . '</td>
              <td>
                  <input type="text"   value="' . (!empty($params['location_street_name']) ? htmlspecialchars($params['location_street_name']) : '' ) . '" size="' . INPUT_SIZE . '" data-address="street" name="' . $input_name_street . '" maxlength="255">
                  <input type="hidden" value="' . (!empty($params['location_street'])      ? $params['location_street']      : '' ) . '" data-address="street-hidden" name="' . $input_name_street_id . '">
              </td>
          </tr>';

        echo '<tr>
              <td class="nobr">' . trans('House No.') . '</td>
              <td><input type="text"   value="' . (!empty($params['location_house']) ? htmlspecialchars($params['location_house']) : '' ) . '" name="' . $input_name_house . '" data-address="house" size="7" maxlength="20"></td>
          </tr>';

        echo '<tr>
              <td class="nobr">' . trans('Flat No.') . '</td>
              <td><input type="text"   value="' . (!empty($params['location_flat']) ? htmlspecialchars($params['location_flat']) : '' ) . '" name="' . $input_name_flat . '" data-address="flat" size="7" maxlength="20"></td>
          </tr>';

        echo '<tr>
              <td class="nobr">' . trans('Postcode:') . '</td>
              <td>
                <input type="text"   value="' . (!empty($params['location_zip']) ? $params['location_zip'] : '' ) . '" name="' . $input_name_zip . '" data-address="zip" size="7" maxlength="10">
                <a class="zip-code-button" href="#" title="' . trans('Click here to autocomplete zip code') . '">&raquo;&raquo;&raquo;</a>
              </td>
          </tr>';

        echo '<tr>
              <td class="nobr">' . trans('Post office:') . '</td>
              <td><input type="text"   value="' . (!empty($params['location_postoffice']) ? htmlspecialchars($params['location_postoffice']) : '' ) . '" size="' . INPUT_SIZE . '" name="' . $input_name_postoffice . '" data-address="postoffice" maxlength="32"></td>
          </tr>';

        if (empty($params['countryid'])) {
            $params['countryid'] = -1;
        }

        if (empty($countries)) {
            $countries = $lms->GetCountries();
            $countries = Localisation::arraySort($countries, 'name');
        }

        if ($countries) {
            echo '<tr><td>' . trans('Country:') . '</td><td>
                <select name="' . $input_name_country_id . '" data-address="country">
                <option value="">---</option>';

            foreach ($countries as $v) {
                if ($v['id'] == $params['location_country_id']) {
                    echo '<option value="'.$v['id'].'" selected>' . trans($v['name']) . '</option>' ;
                } else {
                    echo '<option value="'.$v['id'].'">' . trans($v['name']) . '</option>' ;
                }
            }

            echo '</select></td></tr>';
        }

        if (isset($params['default_type'])) {
            if ($params['location_address_type'] == null) {
                $params['location_address_type'] = -1;
            }

            echo '<tr>
                 <td colspan="2">
                     <label>
                         <input type="checkbox" class="lms-ui-address-box-def-address" name="' . $input_name_def_address . '"' . ($params['location_address_type'] == DEFAULT_LOCATION_ADDRESS || isset($params['location_def_address']) ? 'checked' : '') . '>
                         ' . trans('default location address') . '
                     </label>
                 </td>
              </tr>';
        }

        echo '<tr>
              <td colspan="2">
                  <label><input type="checkbox" name="' . $input_name_teryt . '" class="lms-ui-address-teryt-checkbox" ' . (!empty($params['teryt']) ? 'checked' : '') . ' data-address="teryt-checkbox">' . trans("TERRIT-DB") . '</label>
                  ' . self::buttonFunction(array('icon' => 'popup', 'class' => 'teryt-address-button'), $template) . '
              </td>
          </tr>';

        echo '</table>';

        echo '</div>';

        echo '<div class="lms-ui-address-box-buttons">';
        if (isset($params['buttons'])) {
            echo $params['buttons'];
        }
        echo '</div>';
        echo '</div>';

        echo '</fieldset>';

        echo '</div>';
    }

    public static function locationBoxExpandableFunction(array $params, $template)
    {
        if (empty($params)) {
            $params = array();
        }

        // set default prefix
        if (empty($params['data']['prefix'])) {
            $params['data']['prefix'] = 'address';
        }

        echo '<div class="location-box-expandable"'
            . ' data-node-use-counter="' . (isset($params['data']['node_use_counter']) ? $params['data']['node_use_counter'] : '0') . '"'
            . ' data-netdev-use-counter="' . (isset($params['data']['netdev_use_counter']) ? $params['data']['netdev_use_counter'] : '0') . '"'
            . ' data-netnode-use-counter="' . (isset($params['data']['netnode_use_counter']) ? $params['data']['netnode_use_counter'] : '0') . '">';

        $uid = uniqid();
        $location_str = $params['data']['location_address_type'] == BILLING_ADDRESS ? ''
            : (empty($params['data']['location_name']) ? '' : $params['data']['location_name'] . ', ');

        $location_str .= $params['data']['location']
            ? ($params['data']['teryt']
                ? trans('$a (TERRIT)', $params['data']['location']) : $params['data']['location'])
            : '...';

        $title = '';

        switch ($params['data']['location_address_type']) {
            case POSTAL_ADDRESS:
                $title = trans('postal address');
                break;
            case BILLING_ADDRESS:
                $title = trans('billing address');
                break;
            case LOCATION_ADDRESS:
                $title = trans('location/recipient address');
                break;
            case DEFAULT_LOCATION_ADDRESS:
                $title = trans('default location address');
                break;
        }

        echo '<div class="address-full"
                title="' . $title . '"
                data-target="' . $uid . '"
                data-state="' . (isset($params['data']['show']) ? 'opened' : 'closed') . '">' .
            $location_str . '</div>';

        if (isset($params['data']['show'])) {
            echo '<div id="' . $uid . '">';
        } else {
            echo '<div id="' . $uid . '" style="display: none;">';
        }

        echo '<div style="padding: 3px 0; position: relative;">';

        static $usage_messages = array(
            'node_use_counter' => 'assigned to <strong>$a</strong> nodes',
            'netdev_use_counter' => 'assigned to <strong>$a</strong> network devices',
            'netnode_use_counter' => 'assigned to <strong>$a</strong> network nodes',
        );
        if (isset($params['data']['use_counter'])) {
            echo '<div>';
            $usages = array();
            foreach (array('node_use_counter', 'netdev_use_counter', 'netnode_use_counter') as $field_name) {
                if (!empty($params['data'][$field_name])) {
                    $usages[] = trans($usage_messages[$field_name], $params['data'][$field_name]);
                }
            }
            if (!empty($usages)) {
                echo implode(', ', $usages);
            }
            echo '</div>';
        }

        echo '</div>';

        if (isset($params['data']['location_address_type'])) {
            echo '<input type="hidden" value="' . $params['data']['location_address_type']  . '" name="' . $params['data']['prefix'] . '[location_address_type]" data-address="address_type">';
        } else {
            echo '<input type="hidden" value="' . LOCATION_ADDRESS .                          '" name="' . $params['data']['prefix'] . '[location_address_type]" data-address="address_type">';
        }

        $params['data']['buttons'] = self::buttonFunction(array('icon' => 'clear', 'tip' => 'Clear', 'class' => 'clear-location-box'), $template);
        if (isset($params['data']['delete_button'])) {
            $params['data']['buttons'] .= self::buttonFunction(array('icon' => 'trash', 'tip' => 'Delete', 'class' => 'delete-location-box'), $template);
        }
        if (isset($params['data']['billing_address_button'])) {
            $params['data']['buttons'] .= self::buttonFunction(array('icon' => 'home', 'tip' => 'Copy from billing address', 'class' => 'copy-address', 'data_type' => BILLING_ADDRESS), $template);
        }
        if (isset($params['data']['post_address_button'])) {
            $params['data']['buttons'] .= self::buttonFunction(array('icon' => 'mail', 'tip' => 'Copy from post address', 'class' => 'copy-address', 'data_type' => POSTAL_ADDRESS), $template);
        }

        self::locationBoxFunction($params['data'], $template);

        echo '</div>';
        echo '</div>';
    }

    public static function macAddressSelectionFunction(array $params, $template)
    {
        $result = '<table style="width: 100%;" class="lms-ui-mac-address-selection">';

        $form = $params['form'];
        $i = 0;
        foreach ($params['macs'] as $key => $mac) {
            $result .= '<tr id="mac' . $key . '" class="mac">
			<td style="width: 100%;">
				<input type="text" name="' . $form . '[macs][' . $key . ']" value="' . $mac . '" ' . (!$i ? 'required ' : '')
                . self::tipFunction(array(
                    'text' => "Enter MAC address",
                    'trigger' => 'mac' . $key
                ), $template) . '>
				<span class="ui-icon ui-icon-closethick remove-mac"></span>
				<a href="#" class="mac-selector"
					' . self::tipFunction(array(
                    'text' => "Click to select MAC from the list",
                ), $template) . '>&raquo;&raquo;&raquo;</a>
			</td>
		</tr>';
            $i++;
        }

        $result .= '</table>
		<a href="#" id="add-mac" data-field-prefix="' . $form
            . '"><span class="ui-icon ui-icon-plusthick"></span> ' . trans("Add MAC address") . '</a>
		<script src="js/lms-ui-mac-address-selection.js"></script>';

        return $result;
    }

    public static function tipFunction(array $params, $template)
    {
        $result = '';

        if (isset($params['popup']) && $popup = $params['popup']) {
            if (is_array($params)) {
                foreach ($params as $paramid => $paramval) {
                    $popup = str_replace('$'.$paramid, $paramval, $popup);
                }
            }

            $text = " onclick=\"popup('$popup',1," . (isset($params['sticky']) && $params['sticky'] ? 1 : 0) . ",10,10)\" onmouseout=\"pophide();\"";
            return $text;
        } else {
            if (isset($params['class'])) {
                $class = $params['class'];
                unset($params['class']);
            } else {
                $class = '';
            }
            $errors = $template->getTemplateVars('error');
            if (isset($params['trigger']) && isset($errors[$params['trigger']])) {
                $error = str_replace("'", '\\\'', $errors[$params['trigger']]);
                $error = str_replace('"', '&quot;', $error);
                $error = str_replace("\r", '', $error);
                $error = str_replace("\n", '<BR>', $error);

                $result .= ' title="' . $error . '" ';
                $result .= ' class="' . (empty($class) ? '' : $class) . ($params['bold'] ? ' lms-ui-error bold" ' : ' lms-ui-error" ');
            } else {
                $warnings = $template->getTemplateVars('warning');
                if (isset($params['trigger']) && isset($warnings[$params['trigger']])) {
                    $error = str_replace("'", '\\\'', $warnings[$params['trigger']]);
                    $error = str_replace('"', '&quot;', $error);
                    $error = str_replace("\r", '', $error);
                    $error = str_replace("\n", '<BR>', $error);

                    $result .= ' title="' . $error . '" ';
                    $result .= ' class="' . (empty($class) ? '' : $class) . ($params['bold'] ? ' lms-ui-warning bold" ' : ' lms-ui-warning" ');
                } else {
                    if ($params['text'] != '') {
                        $text = $params['text'];
                        unset($params['text']);
                        $text = trans(array_merge((array)$text, $params));

                        //$text = str_replace('\'', '\\\'', $text);
                        $text = str_replace('"', '&quot;', $text);
                        $text = str_replace("\r", '', $text);
                        $text = str_replace("\n", '<BR>', $text);

                        $result .= ' title="' . $text . '" ';
                    }
                    $result .= ' class="' . (empty($class) ? '' : $class) . (isset($params['bold']) && $params['bold'] ? ' bold' : '') . '" ';
                }
            }

            return $result;
        }
    }

    public static function boxRowBlock($params, $content, $template, $repeat)
    {
        if (!$repeat) {
            $id = isset($params['id']) ? $params['id'] : null;
            $icon = isset($params['icon']) ? $params['icon'] : null;
            // optional - text tip,
            $tip = isset($params['tip']) ? trans($params['tip']) : null;
            $label = isset($params['label']) ? $params['label'] : null;
            $labelid = isset($params['labelid']) ? $params['labelid'] : null;
            $visible = (isset($params['visible']) && $params['visible']) || !isset($params['visible']);
            $class = isset($params['class']) ? $params['class'] : null;
            $icon_class = isset($params['icon_class']) ? $params['icon_class'] : null;
            $label_class = isset($params['label_class']) ? $params['label_class'] : null;
            $field_id = isset($params['field_id']) ? $params['field_id'] : null;
            $field_class = isset($params['field_class']) ? $params['field_class'] : null;

            return '
			<div' . ($id ? ' id="' . $id . '"' : '') . ' class="lms-ui-box-row' . ($class ? ' ' . $class : '') . '"'
                . ($visible ? '' : ' style="display: none;"') . ($tip ? ' title="' . trans($tip) . '"' : '') . '>
				<div class="lms-ui-box-row-icon' . '">
					' . ($icon ? (strpos($icon, '/') !== false ? '<IMG src="' . $icon . '" alt="">'
                    : '<i class="'
                    . ($icon_class ? $icon_class . ' ' : '')
                    . (strpos($icon, 'lms-ui-icon-') === 0 || strpos($icon, 'fa') === 0 ? $icon : 'lms-ui-icon-' . $icon)
                    . '"></i>') : '') . '
				</div>
				<div class="lms-ui-box-row-label' . ($label_class ? ' ' . $label_class : '') . '">
					' . ($labelid ? '<label for="' . $labelid . '">' : '')
                . ($label ? trans($label) : '') . ($labelid ? '</label>' : '') . '
				</div>
				<div' . ($field_id ? ' id="' . $field_id . '"' : '')
                . ' class="lms-ui-box-row-field' . ($field_class ? ' ' . $field_class : '') . '">
					' . $content . '
				</div>
			</div>';
        } else {
            return '';
        }
    }

    public static function buttonsBlock($params, $content, $template, $repeat)
    {
        if (!$repeat) {
            return '<div class="lms-ui-responsive-buttons">' . self::buttonFunction(
                array(
                    'type' => 'link',
                    'icon' => isset($params['icon']) ? $params['icon'] : 'additional-selection',
                    'class' => 'lms-ui-dropdown-toggle',
                ),
                $template
            ) . (isset($params['secondary']) && $params['secondary'] ?
                self::buttonFunction(
                    array(
                        'type' => 'link',
                        'icon' => isset($params['icon']) ? $params['icon'] : 'additional-selection',
                        'class' => 'lms-ui-dropdown-toggle secondary',
                        'tip' => trans('more actions'),
                    ),
                    $template
                ) : '')
                . '<div class="lms-ui-dropdown-buttons">'
                . $content
                . '</div></div>';
        } else {
            return '';
        }
    }

    public static function boxContainerBlock($params, $content, $template, $repeat)
    {
        if (!$repeat) {
            $id = isset($params['id']) ? $params['id'] : null;

            $data_attributes = '';
            foreach ($params as $name => $value) {
                if (strpos($name, 'data_') === 0) {
                    $data_attributes .= ' ' . str_replace('_', '-', $name) . '=\'' . $value . '\'';
                }
            }

            return '
                <div' . ($id ? ' id="' . $id . '"' : '')
                    . $data_attributes
                    . ' class="lms-ui-box-container">'
                    . $content . '
                </div>';
        } else {
            return '';
        }
    }


    public static function tabContainerBlock($params, $content, $template, $repeat)
    {
        if (!$repeat) {
            $id = isset($params['id']) ? $params['id'] : null;
            $label = isset($params['label']) ? trans($params['label']) : null;

            $data_attributes = '';
            foreach ($params as $name => $value) {
                if (strpos($name, 'data_') === 0) {
                    $data_attributes .= ' ' . str_replace('_', '-', $name) . '=\'' . $value . '\'';
                }
            }

            return '
                <div' . ($id ? ' id="' . $id . '"' : '')
                    . (isset($label) ? ' data-label="' . $label . '"' : '')
                    . $data_attributes
                    . ' class="lms-ui-tab-container lms-ui-sortable">'
                    . $content . '
                </div>';
        }
    }

    public static function resourceTabSelectorFunction($params, $template)
    {
        $layout = $template->getTemplateVars('layout');
        $resource_tabs = $template->getTemplateVars('serialized_resource_tabs');

        return '
            <form name="resource-tab-selector-form" id="resource-tab-selector-form">
                <input type="hidden" id="resource-tab-module" value="' . $layout['module'] . '">'
                . (isset($resource_tabs)
                    ? '<input type="hidden" id="resource-tab-states" value="' . $resource_tabs . '">'
                    : '') . '
            </form>
            <div id="lms-ui-resource-tab-selector-container">
                <div>
                    ' . trans("Visible tabs:") . '
                </div>
                <select id="resource-tab-selector" name="resource-tabs[]" form="resource-tab-selector-form"
                    data-default-value="' . trans("- none -") . '"
                    data-shorten-to-default-value="false"
                    onchange="resourceTabSelectorChanged()" multiple>
                </select>
            </div>
            <script src="js/lms-ui-resource-tab-selector.js"></script>';
    }

    public static function iconFunction(array $params, $template)
    {
        // optional - allow to easily attach event handler in jquery,
        $id = isset($params['id']) ? $params['id'] : null;
        // optional - additional css classes which are appended to class attribute
        $class = isset($params['class']) && !empty($params['class']) ? $params['class'] : null;
        // optional - icon selection transformed to css class
        $name = isset($params['name']) ? $params['name'] : null;
        // optional - text tip,
        $tip = isset($params['tip']) ? trans($params['tip']) : null;
        // optional - text label
        $label = isset($params['label']) ? trans($params['label']) : null;

        $data_attributes = '';
        foreach ($params as $key => $value) {
            if (strpos($key, 'data_') === 0) {
                $data_attributes .= ' ' . str_replace('_', '-', $key) . '=\'' . $value . '\'';
            }
        }

        return '<i'
            . (isset($id) ? ' id="' . $id . '"' : '')
            . ' class="'
            . (isset($name) ? (strpos($name, 'lms-ui-icon-') === 0 || strpos($name, 'fa') === 0
                ? $name : 'lms-ui-icon-' . $name) : '')
            . (isset($class) ? ' ' . $class : '')
            . '"'
            . (isset($tip) ? ' title="' . $tip . '"' : '')
            . $data_attributes
        . '></i>'
            . (isset($label) ? ' ' . $label : '');
    }

    public static function paytypesFunction(array $params, $template)
    {
        static $paytypes = array();

        if (empty($paytypes)) {
            $paytypes = Localisation::arraySort($GLOBALS['PAYTYPES']);
        }

        $elemname = $params['elemname'];
        $selected = isset($params['selected']) && !empty($params['selected']) ? $params['selected'] : 0;
        $tip = isset($params['tip']) ? $params['tip'] : trans('Select payment type');
        $trigger = isset($params['trigger']) ? $params['trigger'] : 'paytype';

        $options = '';
        foreach ($paytypes as $key => $item) {
            $item = trans($item);
            $options .= '<option value="' . $key . '"' . ($selected == $key ? ' selected' : '') . '>' . $item . '</option>';
        }
        return '<select name="' . $elemname . '" ' . self::tipFunction(array('text' => $tip, 'trigger' => $trigger), $template) . '>
			<option value=""' . (!$selected ? ' selected' : '') . '>- ' . trans("default") . '-</option>'
            . $options
            . '</select>';
    }

    public static function karmaFunction(array $params, $template)
    {
        $id = isset($params['id']) ? $params['id'] : 'id';
        $value = isset($params['value']) ? intval($params['value']) : 0;
        $title = Localisation::trans(isset($params['title']) ? $params['title'] : 'Counter');
        $handler = isset($params['handler']) ? $params['handler'] : '';
        return '
            <div class="lms-ui-karma-container" data-handler="' . $handler . '" data-id="' . $id . '">
                <i class="lms-ui-icon-star'  . ($value > 0 ? ' green' : ($value < 0 ? ' red' : '')) . '" title="' . $title . '"></i>
                (<span class="lms-ui-counter">' . $value . '</span>)
                <i class="lms-ui-karma-button lms-ui-karma-raise lms-ui-icon-finger-up" title="' . Localisation::trans('Raise') . '"></i>
                <i class="lms-ui-karma-button lms-ui-karma-lower lms-ui-icon-finger-down" title="' . Localisation::trans('Lower') . '"></i>
            </div>
        ';
    }

    public static function deadlineSelectionFunction(array $params, $template)
    {
        $name = $params['name'];
        $cdate_selector = isset($params['cdate_selector']) ? $params['cdate_selector'] : '#cdate';
        $value = isset($params['value']) ? $params['value'] : '';
        if (!empty($params['value']) && preg_match('/^[0-9]+$/', $value)) {
            $value = date('Y/m/d', $value);
        }

        return '
            <div class="lms-ui-deadline-selection" data-cdate-selector="' . $cdate_selector . '">
                <input type="text" class="lms-ui-deadline-selection-date" name="' . $name . '" value="' . $value . '"
                    size="12" placeholder="' . trans('yyyy/mm/dd') . '"
                    ' . self::tipFunction(
                        array(
                            'class' => 'lms-ui-date',
                            'text' => 'Enter deadline date in YYYY/MM/DD format (empty field means default deadline) or click to select it from calendar',
                            'trigger' => $name,
                        ),
                        $template
                    ) . '>
                ' . trans('days') . '
                <select class="lms-ui-deadline-selection-days" lms-ui-combobox">
                    <option value="7">7</option>
                    <option value="14">14</option>
                    <option value="21">21</option>
                    <option value="31">31</option>
                    <option value="60">60</option>
                    <option value="90">90</option>
                </select>
			</div>
        ';
    }

    public static function networkDeviceTypesFunction(array $params, $template)
    {
        static $types = array();

        if (empty($types)) {
            $DB = LMSDB::getInstance();
            $types = Localisation::arraySort(
                $DB->GetAll('SELECT id, name FROM netdevicetypes'),
                'name'
            );
        }

        $elemname = $params['elemname'];
        $selected = isset($params['selected']) && !empty($params['selected']) ? $params['selected'] : 0;
        $tip = isset($params['tip']) ? $params['tip'] : trans('Select network device type');
        $trigger = isset($params['trigger']) ? $params['trigger'] : 'netdevtype';

        $options = '';
        foreach ($types as $item) {
            $options .= '<option value="' . $item['id'] . '"' . ($selected == $item['id'] ? ' selected' : '') . '>' . trans($item['name']) . '</option>';
        }
        return '<select name="' . $elemname . '"' . (isset($params['id']) ? ' id="' . $params['id'] . '"' : '')
            . ' ' . self::tipFunction(array('text' => $tip, 'trigger' => $trigger), $template)
            . (isset($params['onchange']) ? ' onChange="' . $params['onchange'] . '"' : '') . '>
			<option value=""' . (!$selected ? ' selected' : '') . '> ' . trans('<!netdevtype>- undefined -') . '</option>'
            . $options
            . '</select>';
    }
}

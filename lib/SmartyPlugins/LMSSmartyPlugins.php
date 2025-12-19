<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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
    public const HINT_TYPE_ROLLOVER = 'rollover';
    public const HINT_TYPE_TOGGLE = 'toggle';

    public const LOCATION_BOX_INPUT_SIZE = 30;

    public static function buttonFunction(array $params, $template)
    {
        static $defaults = array(
            'icon' => null,
            'type' => 'button',
            'tip' => null,
            'label' => null,
            'text' => null,
            'href' => null,
            'class' => null,
            'icon_class' => null,
            'onclick' => null,
            'external' => false,
            'resourceid' => null,
            'visible' => true,
            'disabled' => false,
            'clipboard' => null,
            'data_url' => null,
        );

        extract($defaults);

        $other_attributes = '';

        foreach ($params as $name => $value) {
            switch ($name) {
                // optional - we want buttons without icon
                case 'icon':
                // optional - button by default,
                case 'type':
                // optional - href attribute of link type button
                case 'href':
                // optional - additional css classes which are appended to class attribute
                case 'class':
                // optional - additional css classes which are appended to icon class attribute
                case 'icon_class':
                // optional - data-resourceid attribute value
                case 'resourceid':
                // optional - contents copied to clipboard
                case 'clipboard':
                    ${$name} = $value;
                    break;
                // optional - text tip,
                case 'tip':
                // optional - button with icon only don't use label
                case 'label':
                case 'text':
                    if (isset($value)) {
                        ${$name} = trans($value);
                    }
                    break;
                // optional - allow to specify javascript code lauched after click,
                case 'onclick':
                    $onclick = !empty($value) ? $value : null;
                    break;
                // optional - if open in new window after click
                case 'external':
                // optional - if element should be initially visible
                case 'visible':
                // optional - if element should be initially disabled
                case 'disabled':
                    ${$name} = !empty($value);
                    break;
                default:
                    $other_attributes .= ' ' . str_replace('_', '-', $name) . '="' . $value . '"';
                    break;
            }
        }

        return '<' . ($type == 'link' || $type == 'link-button' ? 'a' : 'button type="' . $type . '"')
            . ($type == 'link' || $type == 'link-button'
                ? ($href ? ' href="' . $href . '"' : '')
                : ($onclick || !$href ? '' : ' onclick="location.href = \'' . $href . '\';"'))
            . ' class="lms-ui-button' . ($type == 'link-button' ? ' lms-ui-link-button ' : '')
            . ($class ? ' ' . $class : '') . '"'
            . ((($type == 'button' && empty($href)) || $type != 'button') && $onclick ? ' onclick="' . $onclick . '"' : '')
            . ($tip ? ' title="' . $tip . '" data-title="' . $tip . '"' : '')
            . ($external ? ' rel="external"' : '')
            . ($resourceid ? ' data-resourceid="' . $resourceid . '"' : '')
            . ($data_url ? ' data-url="' . $data_url . '"' : '')
            . ($clipboard ? ' data-clipboard-text="' . $clipboard . '"' : '')
            . $other_attributes
            . ($visible ? '' : ' style="display: none;"')
            . ($disabled ? ' disabled' : '') . '>'
            . ($icon ? '<i class="' . (strpos($icon, 'lms-ui-icon-') === 0 || strpos($icon, 'fa') === 0 ? $icon : 'lms-ui-icon-' . $icon)
            . ($icon_class ? ' ' . $icon_class : '') . '"></i>' : '')
            . ($label ? '<span class="lms-ui-label">' . htmlspecialchars($label) . '</span>' : '')
            . ($text ? '<span class="lms-ui-label">' . $text . '</span>' : '') . '
		</' . ($type == 'link' || $type == 'link-button' ? 'a' : 'button') . '>';
    }

    public static function currencySelectionFunction(array $params, $template)
    {
        $elemid = isset($params['elemid']) ? 'id="' . $params['elemid'] . '"' : null;
        $elementname = $params['elementname'] ?? 'currency';
        $selected = isset($params['selected']) && isset($GLOBALS['CURRENCIES'][$params['selected']])
            ? $params['selected'] : null;
        $defaultSelected = Localisation::getCurrentCurrency();
        $locked = isset($params['locked']) && $params['locked'];

        $currencies = $GLOBALS['CURRENCIES'];
        $phpuiSupportedCurrencies = ConfigHelper::getConfig('phpui.supported_currencies', false);
        if ($phpuiSupportedCurrencies) {
            $supportedCurrencies = array_flip(explode(',', $phpuiSupportedCurrencies));
            $currencies = array_intersect_key($currencies, $supportedCurrencies);
            if ($selected) {
                $currencies[$selected] = $selected;
            }
        }

        if (function_exists('get_currency_value') && !$locked) {
            $result = '<select class="' . (!$selected ? 'lms-ui-warning' : '')
                .'" name="' . $elementname . '" ' . $elemid
                . self::tipFunction(array('text' => !$selected ? 'Select currency and save' : 'Select currency'), $template)
                . (isset($params['form']) ? ' form="' . $params['form'] . '"' : '') . '>';
            foreach ($currencies as $currency) {
                $result .= '<option value="' . $currency . '"'
                . (($selected && $currency == $selected) || (!$selected && $currency == $defaultSelected)  ? ' selected' : '') . '>' . $currency . '</option>';
            }
            $result .= '</select>';
        } else {
            $lockedSelected = ($selected ?: $defaultSelected);
            $result = $lockedSelected . '<input type="hidden" name="' . $elementname . '"'
                . (isset($params['form']) ? ' form="' . $params['form'] . '"' : '') . ' value="'
                . $lockedSelected . '">';
        }

        return $result;
    }

    public static function divisionSelectionFunction(array $params, $template)
    {
        static $user_divisions = array();
        $lms = LMS::getInstance();
        $layout = $template->getTemplateVars('layout');
        $force_global_division_context = ConfigHelper::checkConfig('phpui.force_global_division_context');

        if (empty($params)) {
            $params = array();
        }

        $label = $params['label'] ?? null;
        $name = $params['name'] ?? 'division';
        $shortname = !empty($params['shortname']);
        $id = $params['id'] ?? $name;
        $icon = empty($params['icon']) ? null : $params['icon'];
        $selected = $params['selected'] ?? null;
        $superuser = !empty($params['superuser']) ? $params['superuser'] : null;
        $onchange = !empty($params['onchange']) ? $params['onchange'] : null;
        $disabled = !empty($params['disabled']);
        $tip = trans($params['tip'] ?? 'Select division');

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
            $result .= ($label ? '<label>' : '') . ($label ? trans($label) : '')
                . '<span class="division-context bold">' . (!empty($user_divisions) ? htmlspecialchars($user_divisions['label']) : trans("all"))
                . (empty($icon) ? '' : '<i class="' . (strpos($icon, 'lms-ui-icon-') === 0
                    || strpos($icon, 'fa') === 0 ? $icon : 'lms-ui-icon-' . $icon) . '"></i>&nbsp;') . '</span>'
                . ($label ? '</label>' : '')
                . '<input type="hidden" class="division-context-selected" name="' . $name . '"'
                . (isset($params['form']) ? ' form="' . $params['form'] . '"' : '') . ' value="'
                . $layout['division'] . '">';
        } else {
            if (!empty($user_divisions) && count($user_divisions) > 1) {
                $result .= ($label ? '<label for="' . $name . '">' : '') . ($label ? trans($label) : '') . ($label ? '&nbsp;' : '')
                    . (empty($icon) ? '' : '<i class="' . (strpos($icon, 'lms-ui-icon-') === 0
                    || strpos($icon, 'fa') === 0 ? $icon : 'lms-ui-icon-' . $icon) . '"></i>&nbsp;')
                    . '<select class="division-context" id="' . $id . '" name="' . ($shortname ? $user_divisions['shortname'] : $name) . '" '
                    . (empty($tip) ? '' : ' title="' . $tip . '"')
                    . (isset($params['form']) ? ' form="' . $params['form'] . '"' : '')
                    . ($onchange ? ' onchange="' . $onchange . '"' : '')
                    . ($disabled ? ' disabled' : '') . '>'
                    . '<option value=""' . (!$selected ? ' selected' : '') . '>' . trans("— all —") . '</option>';
                foreach ($user_divisions as $division) {
                    $result .= '<option value="' . $division['id'] . '"'
                        . ($selected == $division['id'] ? ' selected' : '') . '>' . htmlspecialchars($division['label']) . '</option>';
                }
                $result .= '</select>' . ($label ? '</label>' : '');
            } else {
                $user_division = reset($user_divisions);
                $result .= ($label ? '<label>' : '') . ($label ? trans($label) : '')
                    . '<span class="division-context bold">'
                    . (empty($user_divisions) ? trans("all") : htmlspecialchars($shortname ? $user_division['label'] : $user_division['name']))
                    . (empty($icon) ? '' : '<i class="' . (strpos($icon, 'lms-ui-icon-') === 0
                      || strpos($icon, 'fa') === 0 ? $icon : 'lms-ui-icon-' . $icon) . '"></i>&nbsp;') . '</span>'
                    . ($label ? '</label>' : '')
                    . '<input type="hidden" class="division-context-selected" name="' . $name . '"'
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

        $form = $params['form'] ?? null;
        $icon = empty($params['icon']) ? null : $params['icon'];
        $trigger = $params['trigger'] ?? 'customerid';

        if (isset($params['selected']) && !preg_match('/^[0-9]+$/', $params['selected'])) {
            $params['selected'] = '';
        }

        $result .= '<div class="lms-ui-customer-select-container" data-version="' . $version . '"'
            . ($version == 2 ? ' data-show-id="1"' : '') . '>' . PHP_EOL;

        $result .= (!empty($icon) ? '<i class="' . (strpos($icon, 'lms-ui-icon-') === 0
            || strpos($icon, 'fa') === 0 ? $icon : 'lms-ui-icon-' . $icon) . '"></i>' : '');

        if (!empty($params['customers'])) {
            $result .= sprintf('<select name="%s" value="%s"', $params['selectname'], $params['selected']);

            if (isset($form)) {
                $result .= ' form="' . $form . '"';
            }

            if (!empty($params['select_id'])) {
                $result .= ' id="' . $params['select_id'] . '"';
            }

            if (isset($params['selecttip'])) {
                $result .= ' ' . self::tipFunction(array('text' => $params['selecttip']), $template);
            } else {
                $result .= ' ' . self::tipFunction(array('text' => 'Select customer (optional)'), $template);
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
                $result .= '>' . trans("— select customer —") . '</option>';
            }
            foreach ($params['customers'] as $customer) {
                $result .= '<option value="' . $customer['id'] . '"';
                if ($customer['id'] == $params['selected']) {
                    $result .= ' selected';
                }
                $result .= '>' . mb_substr($customer['customername'], 0, 40) . ' (' . sprintf("%04d", $customer['id']) . ')</option>' . PHP_EOL;
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
            . ($params['selected'] ?? '') . '"')
            . ' class="lms-ui-customer-select-customerid"'
            . ' data-default-value="' . (isset($params['default_value']) ? htmlspecialchars($params['default_value']) : '') . '"'
            . ' data-prev-value="' . ($params['selected'] ?? '')
            . '" size="5"';

        if (isset($form)) {
            $result .= ' form="' . $form . '"';
        }

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
                . ' placeholder="' . trans('Search for customer') . '"'
                . (isset($form) ? ' form="' . $form . '"' : '')
                . ' ' . self::tipFunction(
                    array(
                        'text' => $params['inputtip'] ?? 'Search for customer',
                        'trigger' => $trigger,
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
                ${$var} = $params[$var];
            } else {
                return $result;
            }
        }

        $item_custom_contents = array();
        $new_item_custom_content = '';
        foreach ($params as $key => $value) {
            switch ($key) {
                case 'item_custom_contents':
                case 'new_item_custom_content':
                    ${$key} = $value;
                    break;
            }
        }

        $form = $params['form'] ?? null;
        $accept = !empty($params['accept']) ? $params['accept'] : null;
        $multiple = !isset($params['multiple']) || ConfigHelper::checkValue($params['multiple']);

        $image_resize = !isset($params['image_resize']) || !empty($params['image_resize']);

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
            . (isset($error_tip_params) ? self::tipFunction($error_tip_params, $template) : '') . '><i class="lms-ui-icon-upload"></i><span class="lms-ui-label">' . trans("Select files") . '</span></button>
                <input name="' . $id . '[]" type="file" class="fileupload-select-btn" style="display: none;" '
                  . ($multiple ? ' multiple' : '')
                  . ($form ? ' form="' . $form . '"' : '')
                  . ($accept ? ' accept="' . $accept . '"' : '') . '>'
                  . (ConfigHelper::getConfig('phpui.uploaded_image_max_size', 0) && $image_resize
                    ? '<label><input type="checkbox" class="dont-scale-images" value="1">' . trans("don't scale images") . '</label>'
                    : '') . '
			</div>
			<div class="fileupload-files">';
        if (!empty($fileupload) && isset($fileupload[$id])) {
            foreach ($fileupload[$id] as $fileidx => $file) {
                $result .= '<div class="fileupload-file">
                    <div class="fileupload-file-info">
                        <a href="#" class="file-delete"><i class="fas fa-trash"></i></a>
                            <span>' . $file['name'] . ' (' . $file['sizestr'] . ')</span>
                        <input type="hidden" name="fileupload[' . $id . '][' . $fileidx . '][name]" value="' . $file['name'] . '" ' . ($form ? ' form="' . $form . '"' : '') . '>
                        <input type="hidden" class="fileupload-file-size" name="fileupload[' . $id . '][' . $fileidx . '][size]" value="' . $file['size'] . '" ' . ($form ? ' form="' . $form . '"' : '') . '>
                        <input type="hidden" name="fileupload[' . $id . '][' . $fileidx . '][type]" value="' . $file['type'] . '" ' . ($form ? ' form="' . $form . '"' : '') . '>
                    </div>
                    ' . (isset($item_custom_contents[$fileidx]) ? '<div class="fileupload-file-options">' . $item_custom_contents[$fileidx] . '</div>' : '') . '
                </div>';
            }
        }
        $result .= '</div>
			<div class="fileupload-status lms-ui-error bold">
			</div>
			<input type="hidden" class="fileupload-tmpdir" name="fileupload[' . $id . '-tmpdir]" value="'
            . ($fileupload[$id . '-tmpdir'] ?? '')
            . '" ' . ($form ? ' form="' . $form . '"' : '') . '>
		</div>';
        $result .= '<script>
			$(function() {
				new lmsFileUpload(
                    "' . $id . '", "' . ($form ?: '') . '"'
                    . ', "' . (strlen($new_item_custom_content) ? base64_encode($new_item_custom_content) : '') . '");
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

        $zipcode_required = ConfigHelper::checkConfig('phpui.zipcode_required');
        // generate unique id for location box
        $LOCATION_ID = 'lmsui-' . uniqid();


        // base name for localization inputs
        $input_name             = 'location';
        $input_name_ten         = 'location_ten';
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
            $input_name_ten         = $p . '[' . $input_name_ten         . ']';
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

        echo '<div class="location-box"';
        if (!empty($params['allow_empty_streets'])) {
            echo ' data-allow-empty-streets="true"';
        }
        if (!empty($params['allow_empty_building_numbers'])) {
            echo ' data-allow-empty-building-numbers="true"';
        }
        echo '>';

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
                  <input type="text"   value="' . (!empty($params['location_name']) ? htmlspecialchars($params['location_name']) : '' ) . '" name="' . $input_name_location . '" size="' . self::LOCATION_BOX_INPUT_SIZE . '" data-address="location-name">
                  <input type="hidden" value="' . ($params['location'] ?? '') . '" name="' . $input_name . '" data-address="location">
              </td>
          </tr>';

        if ($params['location_address_type'] == LOCATION_ADDRESS || $params['location_address_type'] == DEFAULT_LOCATION_ADDRESS) {
            echo '<tr>
                    <td>' . trans('TEN') . '</td>
                    <td>
                        <input type="text" value="' . (!empty($params['location_ten']) ? htmlspecialchars($params['location_ten']) : '')
                            . '" name="' . $input_name_ten . '" size="' . self::LOCATION_BOX_INPUT_SIZE . '" data-address="ten"
                    </td>
                </tr>';
        }

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

            $tmp_state = isset($params['location_state_name']) ? mb_strtolower($params['location_state_name']) : '';

            foreach ($states as $v) {
                echo '<option ' . (!empty($v) && mb_strtolower($v['name']) == $tmp_state ? 'selected' : '')  . '>' . $v['name'] . '</option>';
            }

            unset($tmp_state);

            echo '</select>';
        }

        echo '<input type="text"
                 value="' . (!empty($params['location_state_name']) ? htmlspecialchars($params['location_state_name']) : '' ) . '"
                 size="' . self::LOCATION_BOX_INPUT_SIZE . '"
                 data-address="state"
                 name="' . $input_name_state . '"
                 ' . (empty($params['teryt']) ? 'style="display:none;"' : '') . '
                 maxlength="64">

          <input type="hidden" value="' . (!empty($params['location_state']) ? $params['location_state'] : '' ) . '" data-address="state-hidden" name="' . $input_name_state_id . '">
          <input type="hidden" value="' . (!empty($params['location_state']) ? $params['terc'] : '' ) . '" data-address="terc">
          </td>
          </tr>';

        echo '<tr>
              <td>' . trans('City') . '</td>
              <td>
                  <input type="text"   value="'
                    . (!empty($params['location_city_name']) ? htmlspecialchars($params['location_city_name']) : '' )
                    . '" size="' . self::LOCATION_BOX_INPUT_SIZE . '" data-address="city" name="' . $input_name_city . '" maxlength="32"'
                    . (isset($params['location_address_type']) && $params['location_address_type'] == BILLING_ADDRESS ? ' required' : '') . '>
                  <input type="hidden" value="' . (!empty($params['location_city'])      ? $params['location_city']      : '' ) . '" data-address="city-hidden" name="' . $input_name_city_id . '">
                  <input type="hidden" value="' . (!empty($params['location_city']) ? $params['simc'] : '' ) . '" data-address="simc">
              </td>
          </tr>';

        echo '<tr>
              <td>' . trans('Street') . '</td>
              <td>
                  <input type="text"   value="' . (!empty($params['location_street_name']) ? htmlspecialchars($params['location_street_name']) : '' ) . '" size="' . self::LOCATION_BOX_INPUT_SIZE . '" data-address="street" name="' . $input_name_street . '" maxlength="255">
                  <input type="hidden" value="' . (!empty($params['location_street'])      ? $params['location_street']      : '' ) . '" data-address="street-hidden" name="' . $input_name_street_id . '">
                  <input type="hidden" value="' . (!empty($params['location_street']) ? $params['ulic'] : '' ) . '" data-address="ulic">
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
              <td class="nobr">' . trans('Postcode') . '</td>
              <td>
                <input type="text" value="' . (!empty($params['location_zip']) ? $params['location_zip'] : '' ) . '" name="' . $input_name_zip
                    . '" data-address="zip" size="7" maxlength="10"' . ($zipcode_required ? ' required' : '') . '>
                <a class="zip-code-button" href="#" title="' . trans('Click here to autocomplete zip code') . '">&raquo;&raquo;&raquo;</a>
              </td>
          </tr>';

        echo '<tr>
              <td class="nobr">' . trans('Post office') . '</td>
              <td><input type="text"   value="' . (!empty($params['location_postoffice']) ? htmlspecialchars($params['location_postoffice']) : '' ) . '" size="' . self::LOCATION_BOX_INPUT_SIZE . '" name="' . $input_name_postoffice . '" data-address="postoffice" maxlength="32"></td>
          </tr>';

        if (empty($params['countryid'])) {
            $params['countryid'] = -1;
        }

        if (empty($countries)) {
            $countries = $lms->GetCountries();
            $countries = Localisation::arraySort($countries, 'name');
        }

        if ($countries) {
            echo '<tr><td>' . trans('Country') . '</td><td>
                <select name="' . $input_name_country_id . '" data-address="country">
                <option value="">—</option>';

            foreach ($countries as $v) {
                if (isset($params['location_country_id']) && $v['id'] == $params['location_country_id']) {
                    echo '<option value="'.$v['id'].'" data-ccode="' . $v['ccode'] . '" selected>' . trans($v['name']) . '</option>' ;
                } else {
                    echo '<option value="'.$v['id'].'" data-ccode="' . $v['ccode'] . '">' . trans($v['name']) . '</option>' ;
                }
            }

            echo '</select></td></tr>';
        }

        if (isset($params['default_type'])) {
            if (!isset($params['location_address_type'])) {
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
                  <label><input type="checkbox" name="' . $input_name_teryt . '" class="lms-ui-address-teryt-checkbox" ' . (!empty($params['teryt']) ? 'checked' : '') . ' data-address="teryt-checkbox">' . trans('TERYT base') . '</label>
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
        static $show_numeric_identifiers = null;

        if (!isset($show_numeric_identifiers)) {
            $show_numeric_identifiers = ConfigHelper::checkConfig('teryt.show_numeric_identifiers');
        }

        if (empty($params)) {
            $params = array();
        }

        // set default prefix
        if (empty($params['data']['prefix'])) {
            $params['data']['prefix'] = 'address';
        }

        echo '<div class="location-box-expandable"'
            . ' data-node-use-counter="' . ($params['data']['node_use_counter'] ?? '0') . '"'
            . ' data-netdev-use-counter="' . ($params['data']['netdev_use_counter'] ?? '0') . '"'
            . ' data-netnode-use-counter="' . ($params['data']['netnode_use_counter'] ?? '0') . '">';

        $uid = uniqid();
        $location_str = isset($params['data']['location_address_type']) && $params['data']['location_address_type'] == BILLING_ADDRESS ? ''
            : (empty($params['data']['location_name']) ? '' : htmlspecialchars($params['data']['location_name']) . ', ');

        $location_str .= isset($params['data']['location_address_type'])
            && ($params['data']['location_address_type'] == LOCATION_ADDRESS || $params['data']['location_address_type'] == DEFAULT_LOCATION_ADDRESS)
            && !empty($params['data']['location_ten'])
                ? trans('TEN') . ' ' . htmlspecialchars($params['data']['location_ten']) . ', '
                : '';

        $location_str .= isset($params['data']['location'])
            ? (
                isset($params['data']['teryt']) && $params['data']['teryt']
                ? (
                    $show_numeric_identifiers
                    ? (
                        empty($params['data']['location_street'])
                        ? trans(
                            '$a ($b)',
                            htmlspecialchars($params['data']['location']),
                            '<span class="nobr">TERC: ' . $params['data']['terc'] . ',</span> <span class="nobr">SIMC: ' . $params['data']['simc'] . '</span>'
                        )
                        : trans(
                            '$a ($b)',
                            htmlspecialchars($params['data']['location']),
                            '<span class="nobr">TERC: ' . $params['data']['terc'] . ',</span> <span class="nobr">SIMC: ' . $params['data']['simc'] . ',</span> <span class="nobr">ULIC: ' . $params['data']['ulic'] . '</span>'
                        )
                    )
                    : trans('$a (TERYT)', htmlspecialchars($params['data']['location']))
                )
                : htmlspecialchars($params['data']['location'])
            )
            : '...';

        $title = '';

        if (isset($params['data']['location_address_type'])) {
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
        } else {
            $title = '';
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
            $params['data']['buttons'] .= self::buttonFunction(array('icon' => 'message', 'tip' => 'Copy from post address', 'class' => 'copy-address', 'data_type' => POSTAL_ADDRESS), $template);
        }

        self::locationBoxFunction($params['data'], $template);

        echo '</div>';
        echo '</div>';
    }

    public static function macAddressSelectionFunction(array $params, $template)
    {
        $node_empty_mac = isset($params['node_empty_mac']) && strlen($params['node_empty_mac']) ? $params['node_empty_mac'] : '';

        $result = '<table style="width: 100%;" class="lms-ui-mac-address-selection" data-node-empty-mac="' . $node_empty_mac . '">';

        if (empty($params['macs'])) {
            $params['mac'] = array();
        }

        $form = $params['form'];
        $i = 0;
        foreach ($params['macs'] as $key => $mac) {
            $result .= '<tr id="mac' . $key . '" class="mac">
			<td style="width: 100%;">
				<input type="text" name="' . $form . '[macs][' . $key . ']" value="' . $mac . '" '
                . 'id="mac-input-' . $key . '" ' . (!$i && !strlen($node_empty_mac) ? 'required ' : '')
                . ' placeholder="' . trans('MAC address') . '" '  . self::tipFunction(array(
                    'trigger' => 'mac-input-' . $key,
                ), $template) . '>
				<span class="ui-icon ui-icon-closethick remove-mac"></span>
				<a class="lms-ui-button mac-selector"
                ' . self::tipFunction(array(
                    'text' => "Click to select MAC from the list",
                ), $template) . '>' . self::iconFunction(array(
                    'name' => 'next',
                ), $template) . '</a>
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
                    $popup = str_replace('$'.$paramid, $paramval ?? '', $popup);
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
                $result .= ' class="' . (empty($class) ? '' : $class) . (isset($params['bold']) && $params['bold'] ? ' lms-ui-error bold" ' : ' lms-ui-error" ');
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
                    if (isset($params['text']) && $params['text'] != '') {
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
            $id = $params['id'] ?? null;
            $icon = $params['icon'] ?? null;
            // optional - text tip,
            $tip = isset($params['tip']) ? trans($params['tip']) : null;
            $label = $params['label'] ?? null;
            $labelid = $params['labelid'] ?? null;
            $visible = !isset($params['visible']) || !empty($params['visible']);
            $class = $params['class'] ?? null;
            $icon_class = $params['icon_class'] ?? null;
            $label_class = $params['label_class'] ?? null;
            $field_id = $params['field_id'] ?? null;
            $field_class = $params['field_class'] ?? null;

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
                    'type' => $params['type'] ?? 'link',
                    'icon' => $params['icon'] ?? 'additional-selection',
                    'class' => $params['class'] ?? 'lms-ui-dropdown-toggle',
                    'label' => $params['label'] ?? '',
                ),
                $template
            ) . (isset($params['secondary']) && $params['secondary'] ?
                self::buttonFunction(
                    array(
                        'type' => 'link',
                        'icon' => $params['icon'] ?? 'additional-selection',
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
            $id = $params['id'] ?? null;

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
            $id = $params['id'] ?? null;
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
                    data-default-value="' . trans("— none —") . '"
                    data-shorten-to-default-value="false"
                    onchange="resourceTabSelectorChanged()" multiple>
                </select>
            </div>
            <script src="js/lms-ui-resource-tab-selector.js"></script>';
    }

    public static function iconFunction(array $params, $template)
    {
        // optional - allow to easily attach event handler in jquery,
        $id = $params['id'] ?? null;
        // optional - additional css classes which are appended to class attribute
        $class = !empty($params['class']) ? $params['class'] : null;
        // optional - icon selection transformed to css class
        $name = $params['name'] ?? null;
        // optional - text tip,
        $tip = isset($params['tip']) ? trans($params['tip']) : null;
        // optional - text label
        $label = isset($params['label']) ? htmlspecialchars(trans($params['label'])) : null;
        // optional - if icon should have fixed width
        $fw = !isset($params['fw']) || !empty($params['fw']);

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
            . ($fw ? ' fa-fw' : '')
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
            $paytypes = Localisation::arraySort(Utils::array_column($GLOBALS['PAYTYPES'], 'label'));
        }

        $elemname = $params['elemname'];
        $id = !empty($params['id']) ? $params['id'] : null;
        $selected = !empty($params['selected']) ? $params['selected'] : 0;
        $tip = $params['tip'] ?? trans('Select payment type');
        $trigger = $params['trigger'] ?? 'paytype';
        $form = $params['form'] ?? null;

        $options = '';
        foreach ($paytypes as $key => $item) {
            $item = trans($item);
            $options .= '<option value="' . $key . '"' . ($selected == $key ? ' selected' : '') . '>' . $item . '</option>';
        }
        return '<select' . (isset($id) ? ' id="' . $id . '"' : '')
            . ' name="' . $elemname . '" ' . self::tipFunction(array('text' => $tip, 'trigger' => $trigger), $template)
            . (isset($form) ? ' form="' . $form . '"' : '') . '>
			<option value=""' . (!$selected ? ' selected' : '') . '>— ' . trans("default") . '—</option>'
            . $options
            . '</select>';
    }

    public static function karmaFunction(array $params, $template)
    {
        $id = $params['id'] ?? 'id';
        $value = isset($params['value']) ? intval($params['value']) : 0;
        $title = Localisation::trans($params['title'] ?? 'Counter');
        $handler = $params['handler'] ?? '';
        return '
            <div class="lms-ui-karma-container" data-handler="' . $handler . '" data-id="' . $id . '">
                <i class="lms-ui-icon-star'  . ($value > 0 ? ' green' : ($value < 0 ? ' red' : '')) . '" title="' . $title . '"></i>
                (<span class="lms-ui-counter">' . $value . '</span>)
                <i class="lms-ui-karma-button lms-ui-karma-raise lms-ui-icon-finger-up" title="' . Localisation::trans('Raise') . '"></i>
                <i class="lms-ui-karma-button lms-ui-karma-lower lms-ui-icon-finger-down" title="' . Localisation::trans('Lower') . '"></i>
            </div>
        ';
    }

    public static function showOnMapButtonFunction(array $params, $template)
    {
        static $loaded = false;

        $latitude = $params['latitude'] ?? null;
        $longitude = $params['longitude'] ?? null;
        $type = $params['type'] ?? null;
        $nodeid = empty($params['nodeid']) ? null : intval($params['nodeid']);
        $netdevid = empty($params['netdevid']) ? null : intval($params['netdevid']);
        $external = !empty($params['external']);

        $disabled = false;
        if (empty($nodeid) && empty($netdevid) && !isset($latitude, $longitude)) {
            if (empty($params['cityid'])) {
                return '';
            }
            $disabled = isset($params['building_num']) && strlen($params['building_num']);
        }

        $script = '';
        if ($disabled) {
            $address = array(
                'city_id' => $params['cityid'],
                'street_id' => empty($params['streetid']) ? null : $params['streetid'],
                'building_num' => $params['building_num'],
            );
            $address = base64_encode(json_encode($address));
            $class = 'lms-ui-geocoding';
            if (!$loaded) {
                $script = '<script src="js/lms-ui-geocoding.js"></script>';
                $loaded = true;
            }
        } else {
            $address = '';
            $class = '';
        }

        switch ($type) {
            case 'geoportal':
                $url = '?m=maplink&action=get-geoportal-link&latitude=%latitude&longitude=%longitude';
                $icon = 'lms-ui-icon-location-geoportal';
                $tip = trans('Show in GeoPortal');
                break;
            case 'default':
                $url = ConfigHelper::getConfig('phpui.gps_coordinate_url', 'https://www.google.com/maps/search/?api=1&query=%latitude,%longitude');
                $icon = 'lms-ui-icon-map-pin';
                $tip = trans('Show on default external map');
                break;
            case 'netstork':
                $url = ConfigHelper::getConfig('netstork.map_url', '', true);
                if (!strlen($url)) {
                    return '';
                }
                $defaultMapZoom = ConfigHelper::getConfig('netstork.default_map_zoom', 18);
                $url .= '#%longitude,%latitude,' . $defaultMapZoom;
                $icon = 'lms-ui-icon-location-netstork';
                $tip = trans('Show on NetStorkWeb Maps');
                break;
            case 'sidusis':
                $url = '?m=maplink&action=get-sidusis-link&latitude=%latitude&longitude=%longitude';
                $icon = 'lms-ui-icon-location-sidusis';
                $tip = trans('Show on SIDUSIS Maps');
                break;
            default:
                if (!empty($nodeid)) {
                    $url = '?m=netdevmap&nodeid=' . $nodeid;
                    $icon = 'lms-ui-icon-map';
                    $tip = trans('Show on map');
                } elseif (!empty($netdevid)) {
                    $url = '?m=netdevmap&netdevid=' . $netdevid;
                    $icon = 'lms-ui-icon-map';
                    $tip = trans('Show on map');
                } elseif (!$disabled) {
                    return '';
                }
                break;
        }

        if (!$disabled) {
            $url = str_replace(
                array(
                    '%longitude',
                    '%latitude',
                ),
                array(
                    $longitude,
                    $latitude,
                ),
                $url
            );
        }

        if ($disabled) {
            $data_tip = $tip ?? null;
            $tip = trans('No GPS coordinates for this address');
        }

        $args = array(
            'href' => $url ?? null,
            'type' => 'link',
            'external' => $external,
            'disabled' => $disabled,
            'icon' => $icon ?? null,
            'tip' => $tip,
        );

        if (strlen($address)) {
            $args['data_address'] = $address;
        }
        if (strlen($class)) {
            $args['class'] = $class;
        }
        if (isset($data_tip)) {
            $args['data_tip'] = $data_tip;
        }
        if (isset($params['label'])) {
            if (is_string($params['label'])) {
                $args['label'] = $params['label'];
            } elseif (!empty($params['label']) && isset($tip)) {
                $args['label'] = $tip;
            }
        }

        return self::buttonFunction($args, $template) . $script;
    }

    public static function deadlineSelectionFunction(array $params, $template)
    {
        $name = $params['name'];
        $id = $params['id'] ?? null;
        $cdate_selector = $params['cdate_selector'] ?? '#cdate';
        $value = $params['value'] ?? '';
        if (!empty($params['value']) && preg_match('/^[0-9]+$/', $value)) {
            $value = date('Y/m/d', $value);
        }

        return '
            <div class="lms-ui-deadline-selection" data-cdate-selector="' . $cdate_selector . '">
                <input type="text" name="' . $name . '" value="' . $value . '"
                    size="12" placeholder="' . trans('yyyy/mm/dd') . '"' . (isset($id) ? ' id="' . $id . '"' : '') . '
                    ' . self::tipFunction(
                        array(
                            'class' => 'lms-ui-deadline-selection-date lms-ui-date',
                            'text' => 'Enter deadline date in YYYY/MM/DD format (empty field means default deadline) or click to select it from calendar',
                            'trigger' => $id ?? $name,
                        ),
                        $template
                    ) . '>
                ' . trans('days') . '
                <select class="lms-ui-deadline-selection-days lms-ui-combobox">
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
        $selected = !empty($params['selected']) ? $params['selected'] : 0;
        $tip = $params['tip'] ?? trans('Select network device type');
        $trigger = $params['trigger'] ?? 'netdevtype';
        $form = $params['form'] ?? null;

        $options = '';
        foreach ($types as $item) {
            $options .= '<option value="' . $item['id'] . '"' . ($selected == $item['id'] ? ' selected' : '') . '>' . trans($item['name']) . '</option>';
        }
        return '<select name="' . $elemname . '"' . (isset($params['id']) ? ' id="' . $params['id'] . '"' : '')
            . (isset($form) ? ' form="' . $form . '"' : '')
            . ' ' . self::tipFunction(array('text' => $tip, 'trigger' => $trigger), $template)
            . (isset($params['onchange']) ? ' onChange="' . $params['onchange'] . '"' : '') . '>
			<option value=""' . (!$selected ? ' selected' : '') . '> ' . trans('<!netdevtype>— undefined —') . '</option>'
            . $options
            . '</select>';
    }

    public static function userSelectionFunction(array $params, $template)
    {
        static $userlist = array();
        $LMS = LMS::getInstance();

        $argv = array(
            'userAccess' => !empty($params['hide_disabled']),
            'hideDeleted' => !empty($params['hide_deleted']),
            'order' => 'rname,asc',
            'short' => true,
        );
        $userlist = $LMS->getUserList($argv);

        $elemid = $params['elemid'] ?? false;
        $elemname = $params['elemname'] ?? false;
        $class = $params['class'] ?? false;
        $selected = empty($params['selected']) ? array() : (is_array($params['selected']) ? $params['selected'] : array($params['selected']));
        $placeholder = empty($params['placeholder']) ? trans('Select users') : trans($params['placeholder']);
        $tip = empty($params['tip']) ? trans('Select user(s) (optional)') : $params['tip'];
        $trigger = $params['trigger'] ?? $elemname;
        $form = $params['form'] ?? null;
        $multiple = !empty($params['multiple']);
        $onChange = empty($params['onchange']) ? 'document.filter.submit();' : $params['onchange'];
        $required = !empty($params['required']);

        $selected = array_combine($selected, $selected);

        $visible = $params['visible'] ?? null;
        if (!empty($visible)) {
            $visible = array_combine($visible, $visible);
            foreach ($userlist as &$user) {
                $user['hidden'] = !isset($visible[$user['id']]);
                if ($user['hidden'] && isset($selected[$user['id']])) {
                    unset($selected[$user['id']]);
                }
            }
            unset($user);
        }

        $options = '';

        foreach ($userlist as $item) {
            $classes = array();
            if (empty($item['accessinfo'])) {
                $classes[] = 'blend';
            }
            if (!empty($item['deleted'])) {
                $classes[] = 'crossed';
            }
            $options .= '<option value="' . $item['id'] . '"'
                . (isset($selected[$item['id']]) ? ' selected' : '')
                . (empty($classes) ? '' : ' class="' . implode(' ', $classes) . '"')
                . (empty($item['hidden']) ? '' : ' style="display: none;"')
                . '>' . htmlspecialchars(substr(trans($item['rname']), 0, 40)) . ' (' . $item['login'] . ')</option>';
        }
        $options .= '<option value="-1"' . (isset($selected['-1']) ? ' selected' : '') . ' data-exclusive> ' . trans('— unassigned —') . '</option>';

        return '<select data-placeholder="' . $placeholder . '"'
            . ($elemname ? ' name="' . $elemname . '"' : '')
            . ($elemid ? ' id="' . $elemid . '"' : '')
            . ($form ? ' form="' . $form . '"' : '')
            . ($multiple ? ' multiple' : '')
            . ($required ? ' required' : '')
            . ' class="lms-ui-advanced-select-test' . ($class ? ' ' . $class : '') . '"'
            . ' onChange="' . $onChange . '"'
            . ' ' . self::tipFunction(array('text' => $tip, 'trigger' => $trigger), $template) . '>'
            . $options
            . '</select>';
    }

    public static function networkDeviceSelectionFunction(array $params, $template)
    {
        $LMS = LMS::getInstance();

        static $netdevicelist = array();

        if (empty($params['list'])) {
            if (empty($netdevicelist)) {
                $netdevicelist = $LMS->GetNetDevList();
            }
        } else {
            $netdevicelist = $params['list'];
        }

        unset($netdevicelist['total'], $netdevicelist['order'], $netdevicelist['direction']);

        $elemname = empty($params['elemname']) ? null : 'name="' . $params['elemname'] . '"';
        $onchange = empty($params['onchange']) ? null : 'onchange="' . $params['onchange'] . '"';
        $id = empty($params['id']) ? null : 'id="' . $params['id'] . '"';
        $selected = intval($params['selected']) ?: null;

        $tip = self::tipFunction(
            array(
                'text' => (empty($params['tip']) ? trans('Select network device') : $params['tip']),
                'trigger' => $params['elemname'],
            ),
            $template
        );

        $class = 'class="netdev-list lms-ui-advanced-select-test ' . (!empty($params['class']) ? $params['class'] : null) . '"';

        $options = '<option value=""' . (!$selected ? ' selected' : '') . '> ' . trans("— none —") . '</option>';
        foreach ($netdevicelist as $item) {
            $options .= '<option value="' . $item['id'] . '"' . ($selected == $item['id'] ? ' selected' : '') . '>'
                . trans($item['name']) . ' (#' . $item['id'] . ')</option>';
        }

        return '<select ' . $elemname . $onchange . $id . $class . $tip . '>' . $options . '</select>';
    }

    public static function networkNodeSelectionFunction(
        array $params,
        $template
    ) {
        $LMS = LMS::getInstance();

        static $netnodelist = array();
        if (empty($netnodelist)) {
            $netnodelist = $LMS->GetNetNodeList();
            unset($netnodelist['total'], $netnodelist['order'], $netnodelist['direction']);
        }

        $elemname = empty($params['elemname']) ? null : 'name="' . $params['elemname'] . '"';
        $onchange = empty($params['onchange']) ? null : 'onchange="' . $params['onchange'] . '"';
        $id = empty($params['id']) ? null : 'id="' . $params['id'] . '"';
        $selected = intval($params['selected']) ?: null;

        $tip = self::tipFunction(
            array(
                'text' => (empty($params['tip']) ?
                    trans('Select network node') : $params['tip']),
                'trigger' => $params['elemname'],
            ),
            $template
        );

        $class = 'class="netnode-list lms-ui-advanced-select-test ' . (!empty($params['class']) ? $params['class'] : null) . '"';

        $options = '<option value=""' . (!$selected ? ' selected' : '') . '> ' . trans("— none —") . '</option>';
        foreach ($netnodelist as $item) {
            $options .= '<option value="' . $item['id'] . '"' . ($selected == $item['id'] ? ' selected' : '') . '>'
                . trans($item['name']) . ' (#' . $item['id'] . ')</option>';
        }

        return '<select data-placeholder="' . trans("— none —") . '" data-allow-clear="true" '
            . $elemname . $onchange . $id . $class . $tip . '>' . $options . '</select>';
    }

    public static function identityTypesFunction(array $params, $template)
    {
        static $identityTypes = array();

        if (empty($identityTypes)) {
            $identityTypes = Localisation::arraySort($GLOBALS['IDENTITY_TYPES']);
        }

        $elemname = $params['elemname'];
        $selected = !empty($params['selected']) ? intval($params['selected']) : null;
        $tip = $params['tip'] ?? trans('Select identity type');
        $trigger = $params['trigger'] ?? 'ict';

        $options = '<option value="0">' . trans('— select —') . '</option>';
        foreach ($identityTypes as $key => $item) {
            $item = trans($item);
            $options .= '<option value="' . $key . '"' . ($selected === $key ? ' selected' : '') . '>' . $item . '</option>';
        }
        return '<select name="' . $elemname . '" ' . self::tipFunction(array('text' => $tip, 'trigger' => $trigger), $template) . '>'
            . $options
            . '</select>';
    }

    public static function hintFunction(array $params, $template)
    {
        $mode = isset($params['mode']) && $params['mode'] == self::HINT_TYPE_ROLLOVER
            ? self::HINT_TYPE_ROLLOVER : self::HINT_TYPE_TOGGLE;

        return '<a class="lms-ui-button lms-ui-hint-' . $mode
            . (isset($params['class']) ? ' ' . $params['class'] : '') . '"'
            . (isset($params['tooltip_class']) ? ' data-tooltip-class="' . $params['tooltip_class'] . '"' : '')
            . (isset($params['content']) ? ' data-hint="' . htmlspecialchars(trans($params['content'])) . '"' : '')
            . (isset($params['text']) ? ' data-hint="' . htmlspecialchars($params['text']) . '"' : '')
            . (isset($params['url']) ? ' data-url="' . $params['url'] . '"' : '')
            . (isset($params['style']) ? ' style="' . $params['style'] . '"' : '')
            . '><i class="lms-ui-icon-' . ($params['icon'] ?? 'hint') . ' fa-fw"></i></a>';
    }

    public static function speechRecognitionFunction(array $params, $template)
    {
        if (!isset($params['target'])) {
            return '';
        }

        return self::buttonFunction(
            array(
                'type' => 'link',
                'icon' => 'microphone',
                'tip' => 'use speech recognition',
                'class' => 'lms-ui-button-speech-recognition',
                'icon_class' => 'fa-fw',
                'data_target' => $params['target'],
            ),
            $template
        );
    }

    public static function numberplanSelectionFunction(array $params, $template)
    {
        static $numberplan_js = 0;

        $result = '';

        $form = $params['form'] ?? null;

        if (isset($params['selected']) && !preg_match('/^[0-9]+$/', $params['selected'])) {
            $params['selected'] = '';
        }

        //<editor-fold desc="numberplan container">
        $result .= '<div class="lms-ui-numberplan-container" style="display: flex;"'
                . (isset($params['doctype_selector']) ? ' data-doctype-selector="' . $params['doctype_selector'] . '"' : '')
                . (isset($params['customer_selector']) ? ' data-customer-selector="' . $params['customer_selector'] . '"' : '')
                . (isset($params['cdate_selector']) ? ' data-cdate-selector="' . $params['cdate_selector'] . '"' : '')
                . (isset($params['reference_selector']) ? ' data-reference-selector="' . $params['reference_selector'] . '"' : '')
                . ' data-plan-document-type="'. $params['planDocumentType'] .'"'
                . ' data-plan-customer-id="'. $params['customer_id'] .'"'
                . '>' . PHP_EOL;

        //<editor-fold desc="number">
        $result .= '<div class="lms-ui-numberplan-number">' . PHP_EOL;
            $result .= '<input type="text" size="12"'
                . ' name="' . $params['input_name'] . '"'
                . (empty($params['input_id']) ? '' : ' id="' . $params['input_id'] . '"')
                . (empty($params['input_value']) ? '' : ' value="' . $params['input_value'] . '"')
                . ' placeholder="— auto —"'
                . (isset($form) ? ' form="' . $form . '"' : '')
                . ' ' . self::tipFunction(
                    array(
                        'text' => 'Enter document number. WARNING! Changing this number can be DANGEROUS! (leave this field empty to obtain next number)',
                        'trigger' => $params['number_trigger'],
                    ),
                    $template
                )
                . '">&nbsp;' . PHP_EOL;
        $result .= '</div>' . PHP_EOL;
        //</editor-fold>

        //<editor-fold desc="plan">
        $result .= '<div class="lms-ui-numberplan-plan">' . PHP_EOL;
        $result .= sprintf('<select name="%s" value="%s" required', $params['select_name'], $params['selected']);
        if (!empty($params['planOnChange'])) {
            $result .= ' onChange="' . $params['planOnChange'] . '"';
        }

        if (isset($form)) {
            $result .= ' form="' . $form . '"';
        }

        if (!empty($params['select_id'])) {
            $result .= ' id="' . $params['select_id'] . '"';
        }
        $result .= ' ' . self::tipFunction(
            array(
                'text' => 'Select numbering plan',
                'trigger' => $params['plan_trigger'],
            ),
            $template
        );
        $result .= '">' . PHP_EOL;

        if (!empty($params['numberplanlist'])) {
            if (count($params['numberplanlist']) > 1) {
                $result .= '<option value="" disabled selected hidden>' . trans("— select —") . '</option>';
            }
            foreach ($params['numberplanlist'] as $plan) {
                $result .= '<option value="' . $plan['id'] . '"';
                if ($plan['id'] == $params['selected']) {
                    $result .= ' selected';
                }
                $result .= '>' . PHP_EOL;
                $result .= ' ' . docnumber(
                    array(
                        'number' => $plan['next'],
                        'template' => $plan['template'],
                        'time' => $params['time'],
                        'customerid' => $params['customer_id']
                    ),
                    $template
                );
                $result .= ' (' . $GLOBALS['NUM_PERIODS'][$plan['period']] . ')';
                $result .= '</option>' . PHP_EOL;
            }
        } else {
            $result .= '<option value="">' . trans("— select —") . '</option>';
        }
        $result .= '</select>' . PHP_EOL;
        $result .= '</div>' . PHP_EOL;
        //</editor-fold>

        $result .= '</div>' . PHP_EOL;
        //</editor-fold>

        if (empty($numberplan_js)) {
            $result .= '<script src="js/lms-ui-numberplan-select.js"></script>';
            $numberplan_js = 1;
        }

        return $result;
    }

    public static function daySelectionFunction(array $params, Smarty_Internal_Template $template)
    {
        static $loaded = false;

        $elem_selector = $params['elem'] ?? null;
        $days = $params['days'] ?? '7,14,21,30';

        if (!isset($elem_selector)) {
            return;
        }

        $days = preg_split('/\s*[ ,|]\s*/', $days);

        $result = $script = '';
        if (!$loaded) {
            $script = '<script src="js/lms-ui-day-selection.js"></script>';
            $loaded = true;
        }

        foreach ($days as $day) {
            $result .= '<button type="button" class="lms-ui-button lms-ui-button-day-selection" data-elem="'
                . htmlspecialchars($elem_selector) . '" data-days="' . $day . '"><span class="lms-ui-label">'
                . ($day > 0 ? '+' : '') . ($day == 0 ? trans("Today") : $day) . '</span></button>&nbsp;';
        }

        return $script . '<div class="lms-ui-day-selection-wrapper">' . $result . '</div>';
    }

    public static function taxrateSelectionFunction(array $params, Smarty_Internal_Template $template)
    {
        $default_taxrate = ConfigHelper::getConfig('phpui.default_taxrate', 23.00);
        $default_taxlabel = ConfigHelper::getConfig('phpui.default_taxlabel');

        $lms = LMS::getInstance();
        $taxratelist = $lms->GetTaxes();

        if (isset($default_taxlabel)) {
            // search taxid using tax label
            foreach ($taxratelist as $idx => $tr) {
                if ($tr['label'] === $default_taxlabel) {
                    $default_taxid = $idx;
                    break;
                }
            }
        } else {
            // search taxid using tax value
            foreach ($taxratelist as $idx => $tr) {
                if ($tr['value'] === $default_taxrate) {
                    $default_taxid = $idx;
                    break;
                }
            }
        }

        $id = isset($params['id']) ? ' id="' . $params['id'] . '"' : null;
        $name = isset($params['name']) ? ' name="' . $params['name'] . '"' : null;
        $selected = $params['selected'] ?? $default_taxid;
        $value = empty($selected) ? null : ' value="' . $selected . '"';
        $class = isset($params['class']) ? ' class="'. $params['class'] . '"' : null;
        $customonchange = isset($params['customonchange']) ? ' onchange="'. $params['customonchange'] . '"' : null;
        $form = isset($params['form']) ? ' form="'. $params['form'] . '"' : null;
        $trigger = $params['trigger'] ?? null;
        $tip = $params['tip'] ?? '— select tax rate —';
        $tip_text = self::tipFunction(
            array(
                'text' => trans($tip),
                'trigger' => $trigger,
            ),
            $template
        );
        $visible = isset($params['visible']) && !$params['visible'] ? ' style="display: none;"' : '';
        $required = isset($params['required']) ? ' required' : null;

        $icon = '<i class="' . (empty($params['icon']) ? 'lms-ui-icon-taxrate'
                : (strpos($params['icon'], 'lms-ui-icon-') === 0 ? $params['icon'] : 'lms-ui-icon-' . $params['icon'])
            ) . '"></i>';

        $data_attributes = '';
        foreach ($params as $attname => $attvalue) {
            if (strpos($attname, 'data_') === 0) {
                $data_attributes .= ' ' . str_replace('_', '-', $attname) . '="' . $attvalue . '"';
            }
        }

        $data_attributes .= ' data-default-value="' . $selected . '"';

        $options = '';
        if (empty($taxratelist)) {
            $options .= '<option selected value="">' . trans('— no tax rates defined —') . '</option>';
        } else {
            foreach ($taxratelist as $tax) {
                $options .= '<option value="' . $tax['id'] . '" data-taxrate-value="' . $tax['value'] . '"'
                    . self::tipFunction(array('text' => $tax['label']), $template)
                    . ($tax['id'] == $selected ? ' selected' : null)
                    . '>' . $tax['label'] . ' (' . $tax['value'] . '%)</option>';
            }
        }

        return $icon . '<select ' . $id . $name . $value . $class . $form . $tip_text . $visible . $required
            . $data_attributes . $customonchange . '>' . $options . '</select>';
    }

    public static function resetToDefaultsFunction(array $params, Smarty_Internal_Template $template)
    {
        static $loaded = false;

        $icon = $params['icon'] ?? 'lms-ui-icon-clear';
        $tip = isset($params['tip']) ? trans($params['tip']) : null;
        $target = $params['target'] ?? '[data-default-value]';

        $result = $script = '';
        if (!$loaded) {
            $script = '<script src="js/lms-ui-reset-to-defaults.js"></script>';
            $loaded = true;
        }

        return $script . self::buttonFunction(
            array(
                'type' => 'link',
                'class' => 'lms-ui-reset-to-defaults',
                'icon' => $icon,
                'tip' => $tip,
                'data_target' => $target,
            ),
            $template
        );
    }

    public static function sizeModifier($array, $default = 0)
    {
        if (is_array($array)) {
            $count = count($array);
            return $count ?: $default;
        } else {
            return $default;
        }
    }

    public static function imageDataFunction(array $params, Smarty_Internal_Template $template)
    {
        if (!isset($params['file'])) {
            return '';
        }

        if (strpos($params['file'], DIRECTORY_SEPARATOR) !== 0) {
            $params['file'] = SYS_DIR . DIRECTORY_SEPARATOR . $params['file'];
        }

        if (!is_file($params['file'])) {
            return '';
        }

        $result = 'data:' . mime_content_type($params['file'])
            . ';base64,' . base64_encode(file_get_contents($params['file']));

        if (isset($params['assign'])) {
            $template->assign($params['assign'], $result);
            return '';
        } else {
            return $result;
        }
    }

    public static function imageFunction(array $params, Smarty_Internal_Template $template)
    {
        if (!isset($params['file'])) {
            return '';
        }

        if (strpos($params['file'], DIRECTORY_SEPARATOR) !== 0) {
            $params['file'] = SYS_DIR . DIRECTORY_SEPARATOR . $params['file'];
        }

        if (!is_file($params['file'])) {
            return '';
        }

        $image_data = self::imageDataFunction(
            array_filter(
                $params,
                function ($value, $key) {
                    return $key != 'assign';
                },
                ARRAY_FILTER_USE_BOTH
            ),
            $template
        );
        if (empty($image_data)) {
            return '';
        }

        $result = '<img src="' . $image_data . '" style="margin: 0 auto;'
            . ' width: ' . ($params['width'] ?? '600') . 'px;'
            . (isset($params['height']) ? ' height: ' . $params['height'] . 'px;' : '')
            . (isset($params['style']) ? ' ' . $params['style'] : '') . '"'
            . (isset($params['class']) ? ' class="' . $params['class'] . '"' : '')
            . (isset($params['id']) ? ' id="' . $params['id'] . '"' : '')
            . '>';

        if (isset($params['assign'])) {
            $template->assign($params['assign'], $result);
            return '';
        } else {
            return $result;
        }
    }

    public static function barCodeFunction($params, $template)
    {
        static $barcode = null;
        static $types = array();

        if (!isset($barcode)) {
            $barcode = new \Com\Tecnick\Barcode\Barcode();
            $types = array_flip($barcode->getTypes());
        }

        $transliterate = !isset($params['transliterate']) || ConfigHelper::checkValue($params['transliterate']);
        $text = $params['text'] ?? 'text not set';
        $type = isset($params['type']) && isset($types[$params['type']]) ? $params['type'] : 'C128';
        $show_text = isset($params['show_text']) ? ConfigHelper::checkValue($params['show_text']) : true;
        $scale = isset($params['scale']) ? filter_var($params['scale'], FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) : null;
        if (!isset($scale)) {
            $scale = 1;
        }
        $width = isset($params['width']) ? filter_var($params['width'], FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) : null;
        $height = isset($params['height']) ? filter_var($params['height'], FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) : null;
        $color = $params['color'] ?? 'black';
        $padding = isset($params['padding']) && is_array($params['padding']) && count($params['padding']) == 4
            ? $params['padding']
            : array(0, 0, 0, 0);

        $bobj = $barcode->getBarcodeObj($type, $transliterate ? iconv('UTF-8', 'ASCII//TRANSLIT', $text) : $text, $width ?? $scale * -1, $height ?? $scale * -1 * 30, $color, $padding);

        $img_element = '<img src="data:image/png;base64,' . base64_encode($bobj->getPngData()) . '">';
        if ($show_text) {
            return '<div style="display: flex; flex-direction: column; padding-top: 0.2cm; padding-bottom: 0.2cm; font-size: 12pt; justify-content: center; align-items: center; font-weight: bold;">'
                . $img_element
                . '<span style="widht: 100%; text-align: center;">' . $text . '</span></div>';
        } else {
            return $img_element;
        }
    }

    public static function contactFunction($params, $template)
    {
        $content = $params['content'] ?? null;
        $text = $params['text'] ?? null;
        $type = $params['type'] ?? null;
        $clipboard_button = !empty($params['clipboard_button']) && ConfigHelper::checkValue($params['clipboard_button']);
        $qrcode_button = !empty($params['qrcode_button']) && ConfigHelper::checkValue($params['qrcode_button']);

        if (!isset($content, $type)) {
            return '';
        }

        if ($type & (CONTACT_LANDLINE | CONTACT_MOBILE | CONTACT_EMAIL)) {
            if ($clipboard_button) {
                $content .= self::iconFunction(
                    array(
                        'name' => 'copy',
                        'class' => 'lms-ui-button-clipboard',
                        'data_clipboard_text' => $text,
                    ),
                    $template
                );
            }

            if ($qrcode_button) {
                $content .= self::hintFunction(
                    array(
                        'icon' => 'qrcode',
                        'tooltip_class' => 'lms-ui-qrcode',
                        'text' => self::barcodeFunction(
                            array(
                                'type' => 'QRCODE',
                                'text' => ($type & CONTACT_EMAIL ? 'mailto:' : 'tel:') . $text,
                                'show_text' => false,
                                'width' => -5,
                                'height' => -5,
                            ),
                            $template
                        ),
                    ),
                    $template
                );
            }
        }

        return $content;
    }
}

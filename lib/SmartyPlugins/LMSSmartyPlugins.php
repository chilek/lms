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
        $custom_icon = isset($icon) && (strpos($icon, 'lms-ui-icon-') === 0 || strpos($icon, 'fa') === 0);
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
                $data_attributes .= ' ' . str_replace('_', '-', $name) . '="' . $value . '"';
            }
        }

        return '<' . ($type == 'link' || $type == 'link-button' ? 'a' : 'button type="' . $type . '"') . ($href ? ' href="' . $href . '"' : '')
            . ' class="' . ($type == 'link' ? '' : ($type == 'link-button' ? 'lms-ui-link-button ' : '') . 'lms-ui-button')
            . ($icon && !$custom_icon ? ' lms-ui-button-' . $icon : '')
            . ($class ? ' ' . $class : '') . '"'
            . ($id ? ' id="' . $id . '"' : '') . ($onclick ? ' onclick="' . $onclick . '"' : '')
            . ($form ? ' form="' . $form . '"' : '')
            . ($tip ? ' title="' . $tip . '"' : '')
            . ($external ? ' rel="external"' : '')
            . ($resourceid ? ' data-resourceid="' . $resourceid . '"' : '')
            . ($clipboard ? ' data-clipboard-text="' . $clipboard . '"' : '')
            . $data_attributes
            . ($visible ? '' : ' style="display: none;"')
            . ($accesskey ? ' accesskey="' . $accesskey . '"' : '') . '>'
            . ($icon ? '<i' . ($custom_icon ? ' class="' . $icon . '"' : '') . '></i>' : '')
            . ($label ? '<span class="lms-ui-label">' . $label . '</span>' : '') . '
		</' . ($type == 'link' || $type == 'link-button' ? 'a' : 'button') . '>';
    }

    public static function locationBoxFunction(array $params, $template)
    {
        $DB = LMSDB::getInstance();

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

        if ($template->getTemplateVars('__states')) {
            $states = $template->getTemplateVars('__states');
        } else {
            $states = $DB->GetCol('SELECT name FROM states;');
            $template->assign('__states', $states);
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
                echo '<option ' . (mb_strtolower($v) == $tmp_state ? 'selected' : '')  . '>' . $v . '</option>';
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
                  <input type="text"   value="' . (!empty($params['location_city_name']) ? htmlspecialchars($params['location_city_name']) : '' ) . '" size="' . INPUT_SIZE . '" data-address="city" name="' . $input_name_city . '" maxlength="32">
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

        if ($template->getTemplateVars('__countries')) {
            $countries = $template->getTemplateVars('__countries');
        } else {
            $countries = $DB->GetAll('SELECT id, name FROM countries;');
            $template->assign('__countries', $countries);
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
                  ' . LMSSmartyPlugins::buttonFunction(array('icon' => 'popup', 'class' => 'teryt-address-button'), $template) . '
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

        $params['data']['buttons'] = LMSSmartyPlugins::buttonFunction(array('icon' => 'clear', 'tip' => 'Clear', 'class' => 'clear-location-box'), $template);
        if (isset($params['data']['delete_button'])) {
            $params['data']['buttons'] .= LMSSmartyPlugins::buttonFunction(array('icon' => 'trash', 'tip' => 'Delete', 'class' => 'delete-location-box'), $template);
        }
        if (isset($params['data']['billing_address_button'])) {
            $params['data']['buttons'] .= LMSSmartyPlugins::buttonFunction(array('icon' => 'home', 'tip' => 'Copy from billing address', 'class' => 'copy-address', 'data_type' => BILLING_ADDRESS), $template);
        }
        if (isset($params['data']['post_address_button'])) {
            $params['data']['buttons'] .= LMSSmartyPlugins::buttonFunction(array('icon' => 'mail', 'tip' => 'Copy from post address', 'class' => 'copy-address', 'data_type' => POSTAL_ADDRESS), $template);
        }

        self::locationBoxFunction($params['data'], $template);

        echo '</div>';
        echo '</div>';
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
}

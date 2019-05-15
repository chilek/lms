<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

function smarty_function_location_box($params = array(), $template)
{
    global $DB;

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
                  <span class="lms-ui-button teryt-address-button">&raquo;&raquo;&raquo;</span>
              </td>
          </tr>';

    echo '</table>';

    echo '</fieldset>';

    echo '</div>';
}

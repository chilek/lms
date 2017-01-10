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

function smarty_function_location_dialog( $params = array(), $template )
{
    // generate unique id for location box
    define('LOCATION_ID', 'lmsui-' . uniqid() );

    // input size
    define('INPUT_SIZE' , 30);

    // base name for localization inputs
    $input_name_country_id = 'location_country';
    $input_name_state      = 'location_state_name';
    $input_name_state_id   = 'location_state';
    $input_name_city       = 'location_city_name';
    $input_name_city_id    = 'location_city';
    $input_name_street     = 'location_street_name';
    $input_name_street_id  = 'location_street';
    $input_name_house      = 'location_house';
    $input_name_flat       = 'location_flat';
    $input_name_location   = 'location_name';
    $input_name_zip        = 'location_zip';
    $input_name_teryt      = 'teryt';

    // check if prefix for input names is set
    if ( isset($params['prefix']) && strlen(trim($params['prefix'])) > 0 ) {
        $prefix = trim( $params['prefix'] );

        $input_name_country_id = $prefix . '[' . $input_name_country_id . ']';
        $input_name_state      = $prefix . '[' . $input_name_state      . ']';
        $input_name_state_id   = $prefix . '[' . $input_name_state_id   . ']';
        $input_name_city       = $prefix . '[' . $input_name_city       . ']';
        $input_name_city_id    = $prefix . '[' . $input_name_city_id    . ']';
        $input_name_street     = $prefix . '[' . $input_name_street     . ']';
        $input_name_street_id  = $prefix . '[' . $input_name_street_id  . ']';
        $input_name_house      = $prefix . '[' . $input_name_house      . ']';
        $input_name_flat       = $prefix . '[' . $input_name_flat       . ']';
        $input_name_location   = $prefix . '[' . $input_name_location   . ']';
        $input_name_zip        = $prefix . '[' . $input_name_zip        . ']';
        $input_name_teryt      = $prefix . '[' . $input_name_teryt      . ']';

        unset( $prefix );
    }

    $default_city = ConfigHelper::getConfig('phpui.default_teryt_city');

    echo '<fieldset style="max-width: 1px;" class="lmsui-address-box" id="' . LOCATION_ID . '">';
    echo '<legend class="bold"><img src="img/home.gif" alt=""> ' . trans('Address') . '</legend>';

    echo '<table>';

    echo '<tr>
              <td>' . trans('Name') . '</td>
              <td>
                  <input type="text" value="' . (!empty($params['name']) ? $params['name'] : '' ) . '" name="' . $input_name_location . '" size="' . INPUT_SIZE . '" data-address="location-name">
              </td>
          </tr>';

    echo '<tr>
              <td>' . trans('State') . '</td>
              <td>
                  <input type="text"   value="' . (!empty($params['state'])   ? $params['state']   : '' ) . '" size="' . INPUT_SIZE . '" data-address="state" name="' . $input_name_state . '">
                  <input type="hidden" value="' . (!empty($params['stateid']) ? $params['stateid'] : '' ) . '" data-address="state-hidden" name="' . $input_name_state_id . '">
              </td>
          </tr>';

    echo '<tr>
              <td>' . trans('City') . '</td>
              <td>
                  <input type="text"   value="' . (!empty($params['city'])   ? $params['city']   : '' ) . '" size="' . INPUT_SIZE . '" data-address="city" name="' . $input_name_city . '">
                  <input type="hidden" value="' . (!empty($params['cityid']) ? $params['cityid'] : '' ) . '" data-address="city-hidden" name="' . $input_name_city_id . '">
              </td>
          </tr>';

    echo '<tr>
              <td>' . trans('Street') . '</td>
              <td>
                  <input type="text"   value="' . (!empty($params['street'])   ? $params['street']   : '' ) . '" size="' . INPUT_SIZE . '" data-address="street" name="' . $input_name_street . '">
                  <input type="hidden" value="' . (!empty($params['streetid']) ? $params['streetid'] : '' ) . '" data-address="street-hidden" name="' . $input_name_street_id . '">
              </td>
          </tr>';

    echo '<tr>
              <td class="nobr">' . trans('House No.') . '</td>
              <td><input type="text"   value="' . (!empty($params['house']) ? $params['house'] : '' ) . '" name="' . $input_name_house . '" data-address="house" size="7"></td>
          </tr>';

    echo '<tr>
              <td class="nobr">' . trans('Flat No.') . '</td>
              <td><input type="text"   value="' . (!empty($params['flat']) ? $params['flat'] : '' ) . '" name="' . $input_name_flat . '" data-address="flat" size="7"></td>
          </tr>';

    echo '<tr>
              <td class="nobr">' . trans('Postcode:') . '</td>
              <td><input type="text"   value="' . (!empty($params['zip']) ? $params['zip'] : '' ) . '" name="' . $input_name_zip . '" data-address="zip" size="7"></td>
          </tr>';

    global $DB;

    if ( empty($params['countryid']) ) {
        $params['countryid'] = -1;
    }

    $countries = $DB->GetAll('SELECT id, name FROM countries;');
    if ( $countries ) {
        echo '<tr><td>' . trans('Country:') . '</td><td>
              <select name="' . $input_name_country_id . '" data-address="country">';

        foreach ($countries as $v) {
            if ( $v['id'] == $params['countryid'] ) {
                echo '<option value="'.$v['id'].'" selected>' . trans($v['name']) . '</option>' ;
            } else {
                echo '<option value="'.$v['id'].'">' . trans($v['name']) . '</option>' ;
            }
        }

        echo '</select></td></tr>';
    }

    echo '<tr>
              <td colspan="2">
                  <label><input type="checkbox" name="' . $input_name_teryt . '" class="lmsui-address-teryt-checkbox" ' . (!empty($params['teryt']) ? 'checked' : '') . ' data-address="teryt-checkbox">' . trans("TERRIT-DB") . '</label>
                  <span class="lms-ui-button teryt-address-button">&raquo;&raquo;&raquo;</span>
              </td>
          </tr>';

    echo '</table>';

    echo '</fieldset>';
}

?>

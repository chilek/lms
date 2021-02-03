<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2020 LMS Developers
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

/**
 * UiConfigProvider
 */
class UiConfigProvider implements ConfigProviderInterface
{
    const NAME = 'UI_CONFIG_PROVIDER';

    /**
     * Return uiconfig database table
     *
     * @param array $options Associative array of options
     * @return array
     */
    public function load(array $options = array())
    {
        static $ui_config_cache = array();

        $db = LMSDB::getInstance();
        $userid = strval(isset($options['user_id']) ? $options['user_id'] : 0);
        $divisionid = strval(isset($options['division_id']) ? $options['division_id'] : 0);

        $configs = array();
        if (empty($ui_config_cache) || (isset($options['invalidate_cache']) && !empty($options['invalidate_cache']))) {
            $results = $db->GetAll('SELECT * FROM uiconfig WHERE disabled = 0');

            if (!empty($results)) {
                foreach ($results as $result) {
                    if (empty($result['divisionid']) && empty($result['userid'])) {
                        $ui_config_cache['0']['0'][$result['section']][$result['var']] = $result;
                    } elseif (!empty($result['divisionid']) && empty($result['userid'])) {
                        $ui_config_cache[$result['divisionid']]['0'][$result['section']][$result['var']] = $result;
                    } elseif (empty($result['divisionid']) && !empty($result['userid'])) {
                        $ui_config_cache['0'][$result['userid']][$result['section']][$result['var']] = $result;
                    } else {
                        $ui_config_cache[$result['divisionid']][$result['userid']][$result['section']][$result['var']] = $result;
                    }
                }
                $configs = $ui_config_cache['0']['0'];
            }
        } else {
            // Nie może być zmiennej konfiguracyjnej dla użytkownika lub dywizji jeśli
            // nie ma zmiennej konfiguracyjnej globalnej.
            // Zmienne konfiguracyjne globalne są bazą.
            $arrays = array($ui_config_cache['0']['0']);

            if (!empty($divisionid)) {
                // overwrite global configs with division configs
                if (isset($ui_config_cache[$divisionid]['0'])) {
                    $arrays[] = $ui_config_cache[$divisionid]['0'];
                }

                // overwrite global configs with user in division configs
                if (!empty($userid) && isset($ui_config_cache[$divisionid][$userid])) {
                    $arrays[] = $ui_config_cache[$divisionid][$userid];
                }
            }

            if (!empty($userid) && isset($ui_config_cache['0'][$userid])) {
                // overwrite global configs with user configs
                $arrays[] = $ui_config_cache['0'][$userid];
            }

            $configs = call_user_func_array('array_merge_recursive', $arrays);

            foreach ($configs as $section => &$variables) {
                foreach ($variables as $variable_name => &$variable) {
                    foreach ($variable as $property_name => $property_value) {
                        if (is_array($property_value)) {
                            $variable[$property_name] = array_pop($property_value);
                        }
                    }
                }
                unset($variable);
            }
            unset($variables);
        }

        return $configs;
    }
}

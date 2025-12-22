<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2025 LMS Developers
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

class LMSSmarty extends \Smarty\Smarty
{
    private $plugin_manager;
    private static $__smarty = null;

    public function __construct()
    {
        parent::__construct();
        self::$__smarty = $this;

        // add LMS's custom plugins directory
        $functions = [
            'bankaccount' => [\Lms\Smarty\Plugins::class, 'bankaccountFunction'],
            'barcode' => [\Lms\Smarty\Plugins::class, 'barCodeFunction'],
            'button' => [\Lms\Smarty\Plugins::class, 'buttonFunction'],
            'contact' => [\Lms\Smarty\Plugins::class, 'contactFunction'],
            'css' => [\Lms\Smarty\Plugins::class, 'cssFunction'],
            'currency_selection' => [\Lms\Smarty\Plugins::class, 'currencySelectionFunction'],
            'customerlist' => [\Lms\Smarty\Plugins::class, 'customerListFunction'],
            'day_selection' => [\Lms\Smarty\Plugins::class, 'daySelectionFunction'],
            'date_period_preset' => [\Lms\Smarty\Plugins::class, 'datePeriodPresetFunction'],
            'deadline_selection' => [\Lms\Smarty\Plugins::class, 'deadlineSelectionFunction'],
            'division_selection' => [\Lms\Smarty\Plugins::class, 'divisionSelectionFunction'],
            'document_address' => [\Lms\Smarty\Plugins::class, 'documentAddressFunction'],
            'documentview' => [\Lms\Smarty\Plugins::class, 'documentViewFunction'],
            'event_time_selection' => [\Lms\Smarty\Plugins::class, 'eventTimeSelectionFunction'],
            'fileupload' => [\Lms\Smarty\Plugins::class, 'fileUploadFunction'],
            'gentime' => [\Lms\Smarty\Plugins::class, 'genTimeFunction'],
            'handle' => [\Lms\Smarty\Plugins::class, 'handleFunction'],
            'hint' => [\Lms\Smarty\Plugins::class, 'hintFunction'],
            'icon' => [\Lms\Smarty\Plugins::class, 'iconFunction'],
            'identitytypes' => [\Lms\Smarty\Plugins::class, 'identityTypesFunction'],
            'image_data' => [\Lms\Smarty\Plugins::class, 'imageDataFunction'],
            'image' => [\Lms\Smarty\Plugins::class, 'imageFunction'],
            'js' => [\Lms\Smarty\Plugins::class, 'jsFunction'],
            'karma' => [\Lms\Smarty\Plugins::class, 'karmaFunction'],
            'list' => [\Lms\Smarty\Plugins::class, 'listFunction'],
            'location_box' => [\Lms\Smarty\Plugins::class, 'locationBoxFunction'],
            'location_box_expandable' => [\Lms\Smarty\Plugins::class, 'locationBoxExpandableFunction'],
            'mac_address_selection' => [\Lms\Smarty\Plugins::class, 'macAddressSelectionFunction'],
            'memory' => [\Lms\Smarty\Plugins::class, 'memoryFunction'],
            'multi_location_box' => [\Lms\Smarty\Plugins::class, 'multiLocationBoxFunction'],
            'network_container' => [\Lms\Smarty\Plugins::class, 'networkContainerFunction'],
            'network_device_selection' => [\Lms\Smarty\Plugins::class, 'networkDeviceSelectionFunction'],
            'network_device_types' => [\Lms\Smarty\Plugins::class, 'networkDeviceTypesFunction'],
            'network_node_selection' => [\Lms\Smarty\Plugins::class, 'networkNodeSelectionFunction'],
            'number' => [\Lms\Smarty\Plugins::class, 'numberFunction'],
            'numberplan_selection' => [\Lms\Smarty\Plugins::class, 'numberplanSelectionFunction'],
            'paytypes' => [\Lms\Smarty\Plugins::class, 'paytypesFunction'],
            'persistent_filter' => [\Lms\Smarty\Plugins::class, 'persistentFilterFunction'],
            'reset_to_defaults' => [\Lms\Smarty\Plugins::class, 'resetToDefaultsFunction'],
            'resource_tab_selector' => [\Lms\Smarty\Plugins::class, 'resourceTabSelectorFunction'],
            'show_on_map_button' => [\Lms\Smarty\Plugins::class, 'showOnMapButtonFunction'],
            'speech_recognition' => [\Lms\Smarty\Plugins::class, 'speechRecognitionFunction'],
            'sql_query_time' => [\Lms\Smarty\Plugins::class, 'sqlQueryTimeFunction'],
            'sum' => [\Lms\Smarty\Plugins::class, 'sumFunction'],
            'tax_category_selection' => [\Lms\Smarty\Plugins::class, 'taxCategorySelectionFunction'],
            'tax_rate_selection' => [\Lms\Smarty\Plugins::class, 'taxRateSelectionFunction'],
            'tip' => [\Lms\Smarty\Plugins::class, 'tipFunction'],
            'user_selection' => [\Lms\Smarty\Plugins::class, 'userSelectionFunction'],
        ];

        foreach ($functions as $name => $callback) {
            $this->registerPlugin(self::PLUGIN_FUNCTION, $name, $callback);
        }

        $modifiers = [
            'donthyphenate' => [\Lms\Smarty\Plugins::class, 'dontHyphenateModifier'],
            'duration_format' => [\Lms\Smarty\Plugins::class, 'durationFormatModifier'],
            'message_quote' => [\Lms\Smarty\Plugins::class, 'messageQuoteModifier'],
            'money_format' => [\Lms\Smarty\Plugins::class, 'moneyFormatModifier'],
            'size' => [\Lms\Smarty\Plugins::class, 'sizeModifier'],
            'size_format' => [\Lms\Smarty\Plugins::class, 'sizeFormatModifier'],
            'striphtml' => [\Lms\Smarty\Plugins::class, 'stripHtmlModifier'],
            'to_words' => [\Lms\Smarty\Plugins::class, 'toWordsModifier'],
            'trunescape' => [\Lms\Smarty\Plugins::class, 'trunEscapeModifier'],
        ];

        foreach ($modifiers as $name => $callback) {
            $this->registerPlugin(self::PLUGIN_MODIFIER, $name, $callback);
        }

        $blocks = [
            'box_buttons' => [\Lms\Smarty\Plugins::class, 'boxButtonsBlock'],
            'box_container' => [\Lms\Smarty\Plugins::class, 'boxContainerBlock'],
            'box_contents' => [\Lms\Smarty\Plugins::class, 'boxContentsBlock'],
            'box_header' => [\Lms\Smarty\Plugins::class, 'boxHeaderBlock'],
            'box_panel' => [\Lms\Smarty\Plugins::class, 'boxPanelBlock'],
            'box_row' => [\Lms\Smarty\Plugins::class, 'boxRowBlock'],
            'buttons' => [\Lms\Smarty\Plugins::class, 'buttonsBlock'],
            'donthyphenate' => [\Lms\Smarty\Plugins::class, 'dontHyphenateBlock'],
            't' => [\Lms\Smarty\Plugins::class, 'transBlock'],
            'tab_button_panel' => [\Lms\Smarty\Plugins::class, 'tabButtonPanelBlock'],
            'tab_buttons' => [\Lms\Smarty\Plugins::class, 'tabButtonsBlock'],
            'tab_container' => [\Lms\Smarty\Plugins::class, 'tabContainerBlock'],
            'tab_contents' => [\Lms\Smarty\Plugins::class, 'tabContentsBlock'],
            'tab_header_cell' => [\Lms\Smarty\Plugins::class, 'tabHeaderCellBlock'],
            'tab_header' => [\Lms\Smarty\Plugins::class, 'tabHeaderBlock'],
            'tab_hourglass' => [\Lms\Smarty\Plugins::class, 'tabHourglassBlock'],
            'tab_table' => [\Lms\Smarty\Plugins::class, 'tabTableBlock'],
        ];

        foreach ($blocks as $name => $callback) {
            $this->registerPlugin(self::PLUGIN_BLOCK, $name, $callback);
        }

        $this->registerResource('extendsall', new \Lms\Smarty\ExtendsAllResource($this));

        $this->muteUndefinedOrNullWarnings();

        $this->registerClass('ConfigHelper', 'ConfigHelper');
        $this->registerClass('Localisation', 'Localisation');
        $this->registerClass('Session', 'Session');
        $this->registerClass('LMS', 'LMS');
        $this->registerClass('Auth', 'Auth');
        $this->registerClass('Utils', 'Utils');
        $this->registerClass('EtherCodes', 'EtherCodes');
        $this->registerClass('LMSTcpdfTransferForm', 'LMSTcpdfTransferForm');

        $this->registerPlugin(self::PLUGIN_MODIFIER, 'trans', 'trans');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'date', 'date');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'count', 'count');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'key', 'key');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'strtolower', 'strtolower');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'strposr', 'strpos');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'str_replace', 'str_replace');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'preg_split', 'preg_split');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'preg_match', 'preg_match');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'preg_replace', 'preg_replace');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'moneyf', 'moneyf');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'moneyf_in_words', 'moneyf_in_words');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'intval', 'intval');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'sprintf', 'sprintf');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'array_keys', 'array_keys');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'array_merge', 'array_merge');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'floor', 'floor');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'pow', 'pow');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'implode', 'implode');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'htmlspecialchars', 'htmlspecialchars');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'call_user_func', 'call_user_func');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'trim', 'trim');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'rtrim', 'rtrim');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'strtotime', 'strtotime');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'mb_strlen', 'mb_strlen');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'base64_encode', 'base64_encode');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'urlencode', 'urlencode');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'addslashes', 'addslashes');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'json_decode', 'json_decode');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'iconv', 'iconv');

        $this->registerPlugin(self::PLUGIN_MODIFIER, 'format_bankaccount', 'format_bankaccount');

        $this->registerPlugin(self::PLUGIN_MODIFIER, 'bankaccount', 'bankaccount');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'convert_to_units', 'convert_to_units');

        $this->registerPlugin(self::PLUGIN_MODIFIER, 'time', 'time');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'reset', 'reset');

        $this->registerPlugin(self::PLUGIN_MODIFIER, 'is_null', 'is_null');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'strpos', 'strpos');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'array_values', 'array_values');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'is_numeric', 'is_numeric');
    }

    public static function getInstance()
    {
        return self::$__smarty;
    }

    public function setPluginManager(LMSPluginManager $plugin_manager)
    {
        $this->plugin_manager = $plugin_manager;
    }

    public function display($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        foreach (array('layout', 'error', 'warning', 'newmenu', 'filter') as $name) {
            if (!empty($GLOBALS[$name])) {
                $this->assign($name, $GLOBALS[$name]);
            }
        }

        if (!empty($GLOBALS['userpanel']->MODULES)) {
            $this->assign('modules', $GLOBALS['userpanel']->MODULES);
        }

        $layout = $this->getTemplateVars('layout');

        if (!empty($layout)) {
            if (array_key_exists('module', $layout)) {
                $this->plugin_manager->ExecuteHook(
                    $layout['module'] . '_before_module_display',
                    array('smarty' => $this)
                );
            } elseif (array_key_exists('userpanel_module', $layout) && array_key_exists('userpanel_function', $layout)) {
                $this->plugin_manager->ExecuteHook(
                    'userpanel_' . $layout['userpanel_module'] . '_' . $layout['userpanel_function'] . '_before_module_display',
                    array('smarty' => $this)
                );
            }
        }
        parent::display($template, $cache_id, $compile_id, $parent);
    }
}

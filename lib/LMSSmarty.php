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

class LMSSmarty extends Smarty
{
    private $plugin_manager;
    private static $smarty = null;

    public function __construct()
    {
        parent::__construct();
        self::$smarty = $this;

        // add LMS's custom plugins directory
        $this->addPluginsDir(LIB_DIR . DIRECTORY_SEPARATOR . 'SmartyPlugins');

        $this->muteUndefinedOrNullWarnings();

        $this->registerClass('ConfigHelper', 'ConfigHelper');
        $this->registerClass('Localisation', 'Localisation');
        $this->registerClass('Session', 'Session');
        $this->registerClass('LMS', 'LMS');
        $this->registerClass('Auth', 'Auth');
        $this->registerClass('Utils', 'Utils');
        $this->registerClass('EtherCodes', 'EtherCodes');

        $this->registerPlugin(self::PLUGIN_MODIFIER, 'trans', 'trans');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'date', 'date');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'count', 'count');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'key', 'key');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'strtolower', 'strtolower');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'str_replace', 'str_replace');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'preg_split', 'preg_split');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'preg_match', 'preg_match');
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

        $this->registerPlugin(self::PLUGIN_MODIFIER, 'format_bankaccount', 'format_bankaccount');

        $this->registerPlugin(self::PLUGIN_MODIFIER, 'bankaccount', 'bankaccount');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'size', 'LMSSmartyPlugins::sizeModifier');
        $this->registerPlugin(self::PLUGIN_MODIFIER, 'convert_to_units', 'convert_to_units');
    }

    public static function getInstance()
    {
        return self::$smarty;
    }

    public function setPluginManager(LMSPluginManager $plugin_manager)
    {
        $this->plugin_manager = $plugin_manager;
    }

    public function display($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
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

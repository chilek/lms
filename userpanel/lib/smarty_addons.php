<?php

/*
 *  LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

// Smarty extensions

function _smarty_block_box($params, $content, $template, &$repeat)
{
    if (!$repeat && isset($content)) {
        $title = trans(array_merge(array($params['title']), $params));

        $style = ConfigHelper::getConfig('userpanel.style', 'default');

        if (file_exists('style/'.$style.'/box.html')) {
            $file = 'style/'.$style.'/box.html';
        } elseif (file_exists('style/default/box.html')) {
            $file = 'style/default/box.html';
        }

        $template->assignGlobal('boxtitle', $title);
        $template->assignGlobal('boxcontent', $content);

        return $template->smarty->fetch(USERPANEL_DIR . DIRECTORY_SEPARATOR . $file);
    }
}

function _smarty_function_stylefile($params, $template)
{
    $style = ConfigHelper::getConfig('userpanel.style', 'default');

    if (file_exists('style/'.$style.'/style.css')) {
        return 'style/'.$style.'/style.css';
    }

    if (file_exists('style/default/style.css')) {
            return 'style/default/style.css';
    }
}

function _smarty_function_body($params, $template)
{
    $style = ConfigHelper::getConfig('userpanel.style', 'default');

    if (file_exists('style/'.$style.'/body.html')) {
        $file = 'style/'.$style.'/body.html';
    } elseif (file_exists('style/default/body.html')) {
        $file = 'style/default/body.html';
    }

    return $template->smarty->fetch(USERPANEL_DIR . DIRECTORY_SEPARATOR . $file);
}

function _smarty_function_userpaneltip($params, $template)
{
    $repeat = false;
    $text = trans(array_merge(array($params['text']), $params));

    $tpl = $template->getTemplateVars('error');
    if (isset($params['trigger']) && isset($tpl[$params['trigger']])) {
        $error = str_replace("'", '\\\'', $tpl[$params['trigger']]);
        $error = str_replace('"', '&quot;', $error);
        $error = str_replace("\r", '', $error);
        $error = str_replace("\n", '<BR>', $error);
    }

    $text = str_replace('\'', '\\\'', $text);
    $text = str_replace('"', '&quot;', $text);
    $text = str_replace("\r", '', $text);
    $text = str_replace("\n", '<BR>', $text);

    if (isset($params['class'])) {
        $class = $params['class'];
        unset($params['class']);
    } else {
        $class = '';
    }

    if (ConfigHelper::getConfig('userpanel.style') == 'bclean') {
        $result = ' class="' . (empty($class) ? '' : $class)
            . (isset($params['trigger']) && isset($tpl[$params['trigger']]) ? ($params['bold'] ? ' alert bold' : ' alert') : ($params['bold'] ? ' bold' : ''))
            . '" ';
    } elseif (ConfigHelper::getConfig('userpanel.hint') == 'classic') {
        if (isset($params['trigger']) && isset($tpl[$params['trigger']])) {
            $result = ' onmouseover="return overlib(\'<b><font color=red>' . $error . '</font></b>\',HAUTO,VAUTO,OFFSETX,15,OFFSETY,15);" onmouseout="nd();" ';
        } elseif ($params['text'] != '') {
            $result = 'onmouseover="return overlib(\'' . $text . '\',HAUTO,VAUTO,OFFSETX,15,OFFSETY,15);" onmouseout="nd();"';
        }
        $result .= ' class="' . (empty($class) ? '' : $class)
            . (isset($params['trigger']) && isset($tpl[$params['trigger']]) ? ($params['bold'] ? ' alert bold' : ' alert') : ($params['bold'] ? ' bold' : ''))
            . '" ';
    } elseif (ConfigHelper::getConfig('userpanel.hint') == 'none') {
        $result = "";
    } else {
        if (isset($params['trigger']) && isset($tpl[$params['trigger']])) {
            $result = "onmouseover=\"javascript:displayhint('<font style=&quot;color: red&quot;>" . $error . "</font>')\" onmouseout=\"javascript:hidehint()\" ";
        } else {
            $result = "onmouseover=\"javascript:displayhint('" . $text . "')\" onmouseout=\"javascript:hidehint()\" ";
        }
        $result .= ' class="' . (empty($class) ? '' : $class)
            . (isset($params['trigger']) && isset($tpl[$params['trigger']]) ? ($params['bold'] ? ' alert bold' : ' alert') : ($params['bold'] ? ' bold' : ''))
            . '" ';
    }

    return $result;
}

function _smarty_function_img($params, $template)
{
    global $_GET;

    $style = ConfigHelper::getConfig('userpanel.style', 'default');

    if (file_exists('modules/'.$_GET['m'].'/style/'.$style.'/'.$params['src'])) {
        $file = 'modules/'.$_GET['m'].'/style/'.$style.'/'.$params['src'];
    } elseif (file_exists('modules/'.$_GET['m'].'/style/default/'.$params['src'])) {
            $file = 'modules/'.$_GET['m'].'/style/default/'.$params['src'];
    } elseif (file_exists('style/'.$style.'/'.$params['src'])) {
        $file = 'style/'.$style.'/'.$params['src'];
    } elseif (file_exists('style/default/'.$params['src'])) {
            $file = 'style/default/'.$params['src'];
    } elseif (preg_match('/^https?:\/\//i', $params['src'])) {
        $file = $params['src'];
    } else {
        $file = 'img/' . $params['src'];
    }

    $result  = '<img ';
    $result .= 'src="'.$file.'" ';

    $repeat = false;
    if ($alt = $params['alt']) {
        $result .= 'alt="'.trans($alt).'" ';
    } else {
        $result .= 'alt="" ';
    }

    if ($text = $params['text']) {
        $text = trans(array_merge(array($text), $params));

        $tpl = $template->getTemplateVars('error');
        $error = str_replace("'", '\\\'', $tpl[$params['trigger']]);
        $error = str_replace('"', '&quot;', $error);
        $error = str_replace("\r", '', $error);
        $error = str_replace("\n", '<BR>', $error);

        $text = str_replace('\'', '\\\'', $text);
        $text = str_replace('"', '&quot;', $text);
        $text = str_replace("\r", '', $text);
        $text = str_replace("\n", '<BR>', $text);

        if (ConfigHelper::getConfig('userpanel.hint')=='classic') {
            if ($tpl[$params['trigger']]) {
                $result .= 'onmouseover="return overlib(\'<b><font color=red>'.$error.'</font></b>\',HAUTO,VAUTO,OFFSETX,15,OFFSETY,15);" onmouseout="nd();" ';
            } elseif ($params['text'] != '') {
                $result .= 'onmouseover="return overlib(\''.$text.'\',HAUTO,VAUTO,OFFSETX,15,OFFSETY,15);" onmouseout="nd();" ';
            }
            $result .= ($tpl[$params['trigger']] ? ($params['bold'] ? ' class="alert bold" ' : ' class="alert" ') : ($params['bold'] ? ' class="bold" ' : ''));
        } elseif (ConfigHelper::getConfig('userpanel.hint')=='none') {
            $result .= ' ';
        } else {
            $result .= "onmouseover=\"javascript:displayhint('".$text."')\" onmouseout=\"javascript:hidehint()\" ";
        }
    }

    if ($params['width']) {
        $result .= 'width="'.$params['width'].'" ';
    }
    if ($params['height']) {
        $result .= 'height="'.$params['height'].'" ';
    }
    if ($params['style']) {
        $result .= 'style="'.$params['style'].'" ';
    }
    if ($params['border']) {
        $result .= 'border="'.$params['border'].'" ';
    }

    $result .= '/>';

    return $result;
}

// register the resource name "module"
$SMARTY->registerResource('module', new Smarty_Resource_Userpanel_Module());

$SMARTY->registerPlugin('block', 'box', '_smarty_block_box');
$SMARTY->registerPlugin('function', 'userpaneltip', '_smarty_function_userpaneltip');
$SMARTY->registerPlugin('function', 'img', '_smarty_function_img');
$SMARTY->registerPlugin('function', 'body', '_smarty_function_body');
$SMARTY->registerPlugin('function', 'stylefile', '_smarty_function_stylefile');

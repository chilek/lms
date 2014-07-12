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

function _smarty_block_box($params, $content, &$template, &$repeat)
{
	if (!empty($content))
	{
		$title = trans(array_merge(array($params['title']), $params));

		$style = ConfigHelper::getConfig('userpanel.style', 'default');

		if(file_exists('style/'.$style.'/box.html'))
			$file = 'style/'.$style.'/box.html';
		elseif(file_exists('style/default/box.html'))
			$file = 'style/default/box.html';

		$template->assignGlobal('boxtitle', $title);
		$template->assignGlobal('boxcontent', $content);

		return $template->fetch(ConfigHelper::getConfig('directories.userpanel_dir').'/'.$file);
	}
}

function _smarty_function_stylefile($params, $template)
{
	$style = ConfigHelper::getConfig('userpanel.style', 'default');

        if(file_exists('style/'.$style.'/style.css'))
	        return 'style/'.$style.'/style.css';

	if(file_exists('style/default/style.css'))
	        return 'style/default/style.css';
}

function _smarty_function_body($params, $template)
{
	$style = ConfigHelper::getConfig('userpanel.style', 'default');

        if(file_exists('style/'.$style.'/body.html'))
	        $file = 'style/'.$style.'/body.html';
        elseif(file_exists('style/default/body.html'))
	        $file = 'style/default/body.html';

	return $template->fetch(ConfigHelper::getConfig('directories.userpanel_dir').'/'.$file);
}

function _smarty_function_userpaneltip($params, $template)
{
    $repeat = FALSE;
    $text = trans(array_merge(array($params['text']), $params));

    $tpl = $template->getTemplateVars('error');
    $error = str_replace("'", '\\\'', $tpl[$params['trigger']]);
    $error = str_replace('"', '&quot;', $error);
    $error = str_replace("\r", '', $error);
    $error = str_replace("\n", '<BR>', $error);

    $text = str_replace('\'', '\\\'', $text);
    $text = str_replace('"', '&quot;', $text);
    $text = str_replace("\r", '', $text);
    $text = str_replace("\n", '<BR>', $text);

    if (ConfigHelper::getConfig('userpanel.hint')=='classic')
    {
	if($tpl[$params['trigger']])
	    $result = ' onmouseover="return overlib(\'<b><font color=red>'.$error.'</font></b>\',HAUTO,VAUTO,OFFSETX,15,OFFSETY,15);" onmouseout="nd();" ';
	elseif($params['text'] != '')
	    $result = 'onmouseover="return overlib(\''.$text.'\',HAUTO,VAUTO,OFFSETX,15,OFFSETY,15);" onmouseout="nd();"';
	$result .= ($tpl[$params['trigger']] ? ($params['bold'] ? ' CLASS="alert bold" ' : ' CLASS="alert" ') : ($params['bold'] ? ' CLASS="bold" ' : ''));
    } 
    elseif (ConfigHelper::getConfig('userpanel.hint')=='none')
    {
	$result = "";
    }
    else
    {
    	if($tpl[$params['trigger']])
		$result = "onmouseover=\"javascript:displayhint('<font style=&quot;color: red&quot;>".$error."</font>')\" onmouseout=\"javascript:hidehint()\" ";
	else
		$result = "onmouseover=\"javascript:displayhint('".$text."')\" onmouseout=\"javascript:hidehint()\" ";
	$result .= ($tpl[$params['trigger']] ? ($params['bold'] ? ' class="alert bold" ' : ' class="alert" ') : ($params['bold'] ? ' class="bold" ' : ''));
    }
    return $result;
}

function _smarty_function_img($params, $template)
{
    global $_GET;

    $style = ConfigHelper::getConfig('userpanel.style', 'default');

    if(file_exists('modules/'.$_GET['m'].'/style/'.$style.'/'.$params['src']))
	    $file = 'modules/'.$_GET['m'].'/style/'.$style.'/'.$params['src'];
    elseif(file_exists('modules/'.$_GET['m'].'/style/default/'.$params['src']))
    	    $file = 'modules/'.$_GET['m'].'/style/default/'.$params['src'];
    elseif(file_exists('style/'.$style.'/'.$params['src']))
	    $file = 'style/'.$style.'/'.$params['src'];
    elseif(file_exists('style/default/'.$params['src']))
    	    $file = 'style/default/'.$params['src'];

    $result  = '<img ';
    $result .= 'src="'.$file.'" ';

    $repeat = FALSE;
    if($alt = $params['alt'])
	    $result .= 'alt="'.trans($alt).'" ';
    else
	    $result .= 'alt="" ';

    if($text = $params['text'])
    {
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

	    if (ConfigHelper::getConfig('userpanel.hint')=='classic')
	    {
		if($tpl[$params['trigger']])
		    $result .= 'onmouseover="return overlib(\'<b><font color=red>'.$error.'</font></b>\',HAUTO,VAUTO,OFFSETX,15,OFFSETY,15);" onmouseout="nd();" ';
		elseif($params['text'] != '')
		    $result .= 'onmouseover="return overlib(\''.$text.'\',HAUTO,VAUTO,OFFSETX,15,OFFSETY,15);" onmouseout="nd();" ';
		$result .= ($tpl[$params['trigger']] ? ($params['bold'] ? ' class="alert bold" ' : ' class="alert" ') : ($params['bold'] ? ' class="bold" ' : ''));
	    } 
	    elseif (ConfigHelper::getConfig('userpanel.hint')=='none')
	    {
		    $result .= ' ';
	    }
	    else
	    {
		    $result .= "onmouseover=\"javascript:displayhint('".$text."')\" onmouseout=\"javascript:hidehint()\" ";
	    }
    }

    if($params['width']) $result .= 'width="'.$params['width'].'" ';
    if($params['height']) $result .= 'height="'.$params['height'].'" ';
    if($params['style']) $result .= 'style="'.$params['style'].'" ';
    if($params['border']) $result .= 'border="'.$params['border'].'" ';

    $result .= '/>';

    return $result;
}

function module_get_template($tpl_name, &$tpl_source, $template)
{
	$module = $_GET['m'];
	$style = ConfigHelper::getConfig('userpanel.style', 'default');
	$template_path = ConfigHelper::getConfig('directories.userpanel_dir') . '/modules/' . $module . '/style/' . $style . '/templates/' . $tpl_name;
	if (file_exists($template_path))
	{
		$tpl_source = file_get_contents($template_path);
		return true;
	} else {
		$template_path = ConfigHelper::getConfig('directories.userpanel_dir').'/modules/'.$module.'/templates/'.$tpl_name;
		if (file_exists($template_path)) {
			$tpl_source = file_get_contents($template_path);
			return true;
		} else
			return false;
	}
}

function module_get_timestamp($tpl_name, &$tpl_timestamp, $template)
{
	$module = $_GET['m'];
	$style = ConfigHelper::getConfig('userpanel.style', 'default');
	$template_path = ConfigHelper::getConfig('directories.userpanel_dir') . '/modules/' . $module . '/style/' . $style . '/templates/' . $tpl_name;
	if (file_exists($template_path))
	{
		$tpl_timestamp = filectime($template_path);
		return true;
	} else {
		$template_path = ConfigHelper::getConfig('directories.userpanel_dir').'/modules/'.$module.'/templates/'.$tpl_name;
		if (file_exists($template_path)) {
			$tpl_timestamp = filectime($template_path);
			return true;
		} else
			return false;
	}
}

function module_get_secure($tpl_name, $template)
{
	// assume all templates are secure
	return true;
}

function module_get_trusted($tpl_name, $template)
{
	// not used for templates
}

// register the resource name "module"
$SMARTY->registerResource("module", array("module_get_template",
					"module_get_timestamp",
					"module_get_secure",
					"module_get_trusted"));

$SMARTY->registerPlugin('block', 'box', '_smarty_block_box');
$SMARTY->registerPlugin('function', 'userpaneltip','_smarty_function_userpaneltip');
$SMARTY->registerPlugin('function', 'img','_smarty_function_img');
$SMARTY->registerPlugin('function', 'body','_smarty_function_body');
$SMARTY->registerPlugin('function', 'stylefile','_smarty_function_stylefile');

?>

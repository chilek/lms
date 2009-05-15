<?php

/*
 *  LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2009 LMS Developers
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

function _smarty_block_box($args, $content, &$SMARTY)
{
	global $CONFIG;

	$title = smarty_block_t($args, $args['title'], $SMARTY);
	
	$style = $CONFIG['userpanel']['style'] ? $CONFIG['userpanel']['style'] : 'default';

        if(file_exists('style/'.$style.'/box.html'))
	        $file = 'style/'.$style.'/box.html';
        elseif(file_exists('style/default/box.html'))
	        $file = 'style/default/box.html';

	$SMARTY->assign('boxtitle', $title);
	$SMARTY->assign('boxcontent', $content);
	return $SMARTY->fetch('../'.$file);	
}

function _smarty_function_stylefile($args, &$SMARTY)
{
	global $CONFIG;
	
	$style = $CONFIG['userpanel']['style'] ? $CONFIG['userpanel']['style'] : 'default';

        if(file_exists('style/'.$style.'/style.css'))
	        return 'style/'.$style.'/style.css';
        
	if(file_exists('style/default/style.css'))
	        return 'style/default/style.css';
}

function _smarty_function_body($args, &$SMARTY)
{
	global $CONFIG;
	
	$style = $CONFIG['userpanel']['style'] ? $CONFIG['userpanel']['style'] : 'default';

        if(file_exists('style/'.$style.'/body.html'))
	        $file = 'style/'.$style.'/body.html';
        elseif(file_exists('style/default/body.html'))
	        $file = 'style/default/body.html';

	return $SMARTY->fetch('../'.$file);	
}

function _smarty_function_userpaneltip($args, &$SMARTY)
{
    global $CONFIG;

    $text = smarty_block_t($args, $args['text'], $SMARTY);
    
    $error = str_replace("'",'\\\'',$SMARTY->_tpl_vars['error'][$args['trigger']]);
    $error = str_replace('"','&quot;',$error);
    $error = str_replace("\r",'',$error);
    $error = str_replace("\n",'<BR>',$error);
	
    $text = str_replace('\'','\\\'',$text);
    $text = str_replace('"','&quot;',$text);
    $text = str_replace("\r",'',$text);
    $text = str_replace("\n",'<BR>',$text);
	
    if ($CONFIG['userpanel']['hint']=='classic')
    {
	if($SMARTY->_tpl_vars['error'][$args['trigger']])
	    $result = ' onmouseover="return overlib(\'<b><font color=red>'.$error.'</font></b>\',HAUTO,VAUTO,OFFSETX,15,OFFSETY,15);" onmouseout="nd();" ';
	elseif($args['text'] != '')
	    $result = 'onmouseover="return overlib(\''.$text.'\',HAUTO,VAUTO,OFFSETX,15,OFFSETY,15);" onmouseout="nd();"';
	$result .= ($SMARTY->_tpl_vars['error'][$args['trigger']] ? ($args['bold'] ? ' CLASS="alert bold" ' : ' CLASS="alert" ') : ($args['bold'] ? ' CLASS="bold" ' : ''));
    } 
    elseif ($CONFIG['userpanel']['hint']=='none')
    {
	$result = "";
    }
    else
    {
    	if($SMARTY->_tpl_vars['error'][$args['trigger']])
		$result = "onmouseover=\"javascript:displayhint('<font style=&quot;color: red&quot;>".$error."</font>')\" onmouseout=\"javascript:hidehint()\" ";
	else
		$result = "onmouseover=\"javascript:displayhint('".$text."')\" onmouseout=\"javascript:hidehint()\" ";
	$result .= ($SMARTY->_tpl_vars['error'][$args['trigger']] ? ($args['bold'] ? ' class="alert bold" ' : ' class="alert" ') : ($args['bold'] ? ' class="bold" ' : ''));
    }
    return $result;
}

function _smarty_function_img($args, &$SMARTY)
{
    global $CONFIG, $_GET;

    $style = $CONFIG['userpanel']['style'] ? $CONFIG['userpanel']['style'] : 'default';

    if(file_exists('modules/'.$_GET['m'].'/style/'.$style.'/'.$args['src']))
	    $file = 'modules/'.$_GET['m'].'/style/'.$style.'/'.$args['src'];
    elseif(file_exists('modules/'.$_GET['m'].'/style/default/'.$args['src']))
    	    $file = 'modules/'.$_GET['m'].'/style/default/'.$args['src'];
    elseif(file_exists('style/'.$style.'/'.$args['src']))
	    $file = 'style/'.$style.'/'.$args['src'];
    elseif(file_exists('style/default/'.$args['src']))
    	    $file = 'style/default/'.$args['src'];

    $result  = '<img ';
    $result .= 'src="'.$file.'" ';
    
    if($alt = $args['alt'])
	    $result .= 'alt="'.smarty_block_t(NULL, $alt, $SMARTY).'" ';
    else
	    $result .= 'alt="" ';
	    
    if($text = $args['text'])
    {
	    $text = smarty_block_t($args, $text, $SMARTY);
	    
	    $error = str_replace("'",'\\\'',$SMARTY->_tpl_vars['error'][$args['trigger']]);
	    $error = str_replace('"','&quot;',$error);
	    $error = str_replace("\r",'',$error);
	    $error = str_replace("\n",'<BR>',$error);
	
	    $text = str_replace('\'','\\\'',$text);
	    $text = str_replace('"','&quot;',$text);
	    $text = str_replace("\r",'',$text);
	    $text = str_replace("\n",'<BR>',$text);
	
	    if ($CONFIG['userpanel']['hint']=='classic')
	    {
		if($SMARTY->_tpl_vars['error'][$args['trigger']])
		    $result .= 'onmouseover="return overlib(\'<b><font color=red>'.$error.'</font></b>\',HAUTO,VAUTO,OFFSETX,15,OFFSETY,15);" onmouseout="nd();" ';
		elseif($args['text'] != '')
		    $result .= 'onmouseover="return overlib(\''.$text.'\',HAUTO,VAUTO,OFFSETX,15,OFFSETY,15);" onmouseout="nd();" ';
		$result .= ($SMARTY->_tpl_vars['error'][$args['trigger']] ? ($args['bold'] ? ' class="alert bold" ' : ' class="alert" ') : ($args['bold'] ? ' class="bold" ' : ''));
	    } 
	    elseif ($CONFIG['userpanel']['hint']=='none')
	    {
		    $result .= ' ';
	    }
	    else
	    {
		    $result .= "onmouseover=\"javascript:displayhint('".$text."')\" onmouseout=\"javascript:hidehint()\" ";
	    }
    }
    
    if($args['width']) $result .= 'width="'.$args['width'].'" ';
    if($args['height']) $result .= 'height="'.$args['height'].'" ';
    if($args['style']) $result .= 'style="'.$args['style'].'" ';
    if($args['border']) $result .= 'border="'.$args['border'].'" ';
    
    $result .= '/>';

    return $result;
}

function module_get_template($tpl_name, &$tpl_source, &$smarty_obj)
{
    global $CONFIG;
    $module = $_GET['m'];
    $template_path = $CONFIG["directories"]["userpanel_dir"]."/modules/".$module."/templates/".$tpl_name;
    if (file_exists($template_path))
    {    
	$tpl_source = file_get_contents($template_path);
        return true;
    } else {
        return false;
    }
}

function module_get_timestamp($tpl_name, &$tpl_timestamp, &$smarty_obj)
{
    global $CONFIG;
    $module = $_GET['m'];
    $template_path = $CONFIG["directories"]["userpanel_dir"]."/modules/".$module."/templates/".$tpl_name;
    if (file_exists($template_path))
    {    
	$tpl_timestamp = filectime($template_path);
        return true;
    } else {
        return false;
    }
}

function module_get_secure($tpl_name, &$smarty_obj)
{
    // assume all templates are secure
    return true;
}

function module_get_trusted($tpl_name, &$smarty_obj)
{
    // not used for templates
}

// register the resource name "module"
$SMARTY->register_resource("module", array("module_get_template",
                                       "module_get_timestamp",
                                       "module_get_secure",
                                       "module_get_trusted"));
 
$SMARTY->register_block('box', '_smarty_block_box');
$SMARTY->register_function('userpaneltip','_smarty_function_userpaneltip');
$SMARTY->register_function('img','_smarty_function_img');
$SMARTY->register_function('body','_smarty_function_body');
$SMARTY->register_function('stylefile','_smarty_function_stylefile');

?>

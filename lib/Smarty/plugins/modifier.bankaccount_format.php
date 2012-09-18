<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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
 *  $Id: modifier.bankaccount_format.php,v 1.000 2012/09/18 23:19:28 sylwek Exp $
 */

/*
    rozbija nam numer konta do postaci XX XXXX XXXX XXXX XXXX XXXX XXXX
*/

function smarty_modifier_bankaccount_format($string)
{
    $str = '';
    $t = array();
    $string = str_replace(' ','',$string);
    $string=str_replace('-','',$string);
    $t[0]=substr($string,0,2);
    $t[1]=substr($string,2,4);
    $t[2]=substr($string,6,4);
    $t[3]=substr($string,10,4);
    $t[4]=substr($string,14,4);
    $t[5]=substr($string,18,4);
    $t[6]=substr($string,22,4);
    $str=implode(' ',$t);
    return $str;
}
?>
<?php

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

$LMS->DB->BeginTrans();

foreach($LMS->CONFIG['phpui'] as $key => $val)
{
    switch($key)
    {
     case 'allow_from':
         $desc = trans('List of networks and IP addresses, which have access to LMS. If empty, every IP address has access to LMS. When you write here list of addresses or address classes, LMS dismiss every unwanted user with HTTP 403 error.');
         break;
     case 'lang':
         $desc = trans('User interface language symbol. When is not set, language will be based on html browser settings. Default: en.');
         break;
     case 'timeout':
         $desc = trans('Timeout of www session. After that time (in seconds) user will be log out if not take some action. Default: 600.');
         break;
     case 'userlist_pagelimit':
         $desc = trans('Limit of displayed positions on one page on users list. Default: no limit.');
         break;
     case 'nodelist_pagelimit':
         $desc = trans('Limit of displayed records on page on nodes list. Default: no limit.');
         break;
     case 'balancelist_pagelimit':
         $desc = trans('Limit of displayed records on page on user\'s balance. Default: 100.');
         break;
     case 'configlist_pagelimit':
         $desc = trans('Limit of displayed records on page on UI config options list. Default: 100.');
         break;
     case 'invoicelist_pagelimit':
         $desc = trans('Limit of displayed records on page on invoices list. Default: 100.');
         break;
     case 'ticketlist_pagelimit':
         $desc = trans('Limit of displayed records on page on tickets (requests) list. Default: 100.');
         break;
     case 'accountlist_pagelimit':
         $desc = trans('Limit of displayed records on page on accounts list. Default: 100.');
         break;
     case 'domainlist_pagelimit':
         $desc = trans('Limit of displayed records on page on domains list. Default: 100.');
         break;
     case 'aliaslist_pagelimit':
         $desc = trans('Limit of displayed records on page on aliases list. Default: 100.');
         break;
     case 'networkhosts_pagelimit':
         $desc = trans('Limit of displayed nodes on one page in Network Information. Default: 256. With 0, this informations are ommited (page is displaying faster).');
         break;
     case 'force_ssl':
         $desc = trans('SSL Enforcing. Setting this option to 1 will make that LMS will enforce SSL connection doing redirect to \'https://\'.$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI] at every access without SSL. Default: 0 (off).');
         break;
     case 'reload_type':
         $desc = trans('Reload type. Allowed values: exec - calling of some command (most often with sudo, some script or something else, configurable below); sql - doing SQL writes (also can be set concrete query).');
         break;
     case 'reload_execcmd':
         $desc = trans('Command to run during reload, if reload_type is set to \'exec\'. By default /bin/true. That string is send to command system(), so I propose consideration what you do and how :) Altogether, semicolons should be parsed by bash, but LMS splits that string and execute commands singly.');
         break;
     case 'reload_sqlquery':
         $desc = trans('SQL query executed while reload, if reload_type = sql. By default, query inserts into table \'timestamps\' value \'_force\'. In query can be used \'%TIME%\' as replacement to current unix timestamp. WARNING! Semicolon is handled by a queries separator, that means you can enter couple of SQL queries separate them by semicolon sign.');
         break;
     case 'allow_mac_sharing':
         $desc = trans('Permission for addition of nodes with duplicated MAC address (not checking that some computer have that MAC yet). Default: 0 (off).');
         break;
     case 'default_zip':
     case 'default_city':
     case 'default_address':
         $desc = trans('Default zip code, city, street, used while inserting of new user. Useful when we have many users on the same street.');
         break;
     case 'lastonline_limit':
         $desc = trans('Specify time (in seconds), after which node will be treated as inactive. It should match with frequency of running script inspecting nodes activity (i.e. lms-fping). Default: 600.');
         break;
     case 'use_current_payday':
         $desc = trans('Qualify to use current day of month for payment day instead of most often used day. Default: 0 (off).');
         break;
     case 'smarty_debug':
         $desc = trans('Enable Smarty\'s debug console. Usefull for tracking values passed from PHP to Smarty. Default: 0 (off).');
         break;
     case 'debug_email':
         $desc = trans('E-mail address for debugging - at this address will goes messages sended from madule \'Mailing\', instead of proper users.');
         break;
     case 'arpd_servers':
         $desc = trans('List of arpd servers for reading of MAC addresses from remote networks. That list should include items IP[:port] separated with spaces. Default: empty.');
         break;
     case 'helpdesk_backend_mode':
         $desc = trans('When enabled, all messages in helpdesk system (except sended to requestor) will be send to mail server at address of right queue. On server should be running script lms-rtparser, which will write messages to database. Default: disabled.');
         break;
     case 'contract_template':
         $desc = trans('Specific customer contract template. Default template can be adapted. Default: contract.html.');
         break;
     case 'to_words_short_version':
         $desc = trans('Specify format of verbal amounts representation (on invoices). For value "1" verbal expand of 123,15 will be "one two thr 15/100". Default: 0.');
         break;
     case 'lang_debug':
         $desc = trans('Enable LMS language console. Usefull for tracking missing translation strings. Default: 0 (off).');
         break;
     default:
         $desc = trans('Unknown option. No description.');
         break;
    }

    $DB->Execute('INSERT INTO uiconfig(section, var, value, description) VALUES(?,?,?,?)',
          array('phpui', $key, $val, $desc)
          );
}

/*
foreach($LMS->CONFIG['directories'] as $key => $val)
{
    switch($key)
    {
     case 'sys_dir':
         $desc = 'Katalog systemowy. Jest to miejsce gdzie jest ca�a zawarto�� UI LMS\'a, czyli index.php, grafiki, templejty i reszta. Domy�lnie index.php stara si� sam odnale�� w filesystemie u�ywaj�c getcwd(), ale lepiej by by�o gdyby mu powiedzie� gdzie jest';
         break;
     case 'modules_dir':
         $desc = 'Katalog z "modu�ami" LMS\'a - kawa�kami kodu kt�re szumnie kto� (czyli Baseciq) nazwa� modu�ami. Domy�lnie jest to podkatalog modules w sys_dir';
         break;
     case 'lib_dir':
         $desc = 'Katalog z "bibliotekami" LMS\'a. Czyli zawarto�� katalogu lib. Domy�lnie to podkatalog lib w sys_dir';
         break;
     case 'backup_dir':
         $desc = 'Katalog z backupami SQL\'owymi - miejsce gdzie LMS zapisuje dumpy z bazy. Domy�lnie jest to podkatalog "backups". Naprawd� dobrze by by�o go przenie�� poza miejsce osi�galne przez przegl�dark�';
         break;
     case 'config_templates_dir':
         $desc = 'Katalog z templejtami plik�w konfiguracyjnych. Nieu�ywana';
         break;
     case 'smarty_dir':
         $desc = 'Katalog z bibliotek� Smarty - domy�lnie podkatalog Smarty w lib_dir';
         break;
     case 'smarty_compile_dir':
         $desc = 'Katalog kompilacji Smartyego. Miejsce gdzie Smarty psuje nasze templejty. Domy�lnie to templates_c w katalogu sysdir';
         break;
     case 'smarty_templates_dir':
         $desc = 'Katalog z templejtami kt�rymi karmimy Smartiego. Domy�lnie to podkatalog templates z sys_dir';
         break;
     default:
         $desc = 'Nieznana opcja. Brak opisu';
         break;
    }    
    
    $DB->Execute('INSERT INTO uiconfig(section, var, value, description) VALUES(?,?,?,?)',
          array('directories', $key, $val, $desc)
          );
}
*/

foreach($LMS->CONFIG['invoices'] as $key => $val)
{
    switch($key)
    {
     case 'header':
         $desc = trans('It is a seller data. A new line replacement is "\n" sign, e.g. header = SuperNet ISP\n00-950 Warsaw\nWiosenna 52\n0 49 3883838\n\nksiegowosc@supernet.pl\n\nNIP: 123-123-12-23');
         break;
     case 'footer':
         $desc = trans('Small font footer will be at the bottom of page, e.g. footer = Nasz Bank: Sratytaty, nazwa r-ku: SNETISP, nr r-ku: 828823917293871928371\nBiuro obs�ug klienta 329 29 29. Dzia� windykacji: 329 28 28');
         break;
     case 'default_author':
         $desc = trans('Default person makeing invoice');
         break;
     case 'number_template':
         $desc = trans('Invoice number template. Default: number/LMS/year, i.e. %N/LMS/%Y. Allowed variables: %N - successive number in year, %M - drow-up month, %Y - drow-up year.');
         break;
     case 'cplace':
         $desc = trans('Invoice draw-up place.');
         break;
     case 'template_file':
         $desc = trans('Invoice template file. Default: "invoice.html". Should be placed in templates directory.');
         break;
     case 'content_type':
         $desc = trans('Content-type for invoice. If you enter "application/octet-stream", browser will send file for save on disk, instead of display it. It\'s useful if you use your own template which generate e.g. rtf or xls file. Default: "text/html; charset=UTF-8".');
         break;
     case 'attachment_name':
         $desc = trans('File name for save finished invoice printout. WARNING: Setting attachment_name with default content_type will (in case of MSIE) invoices printing, and prompt for save on disk + bonus browser crash (6.0SP1 on WinXP).');
         break;
     default:
         $desc = trans('Unknown option. No description.');
         break;
    }

    $DB->Execute('INSERT INTO uiconfig(section, var, value, description) VALUES(?,?,?,?)',
          array('invoices', $key, $val, $desc)
          );
}

$LMS->DB->CommitTrans();

header('Location: ?m=configlist');

?>

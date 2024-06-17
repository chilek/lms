<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

// Common functions, that making it in class would be nonsense :)

// Execute a system program. return a trim()'d result.
// does very crude pipe checking.  you need ' | ' for it to work
// ie $program = execute_program('netstat', '-anp | grep LIST');
// NOT $program = execute_program('netstat', '-anp|grep LIST');

function bsd_grab_key($key)
{
    return execute_program('sysctl', '-n '.$key);
}

function find_program($program)
{
    $path = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin', '/usr/local/sbin');
    while ($this_path = current($path)) {
        if (is_executable($this_path.'/'.$program)) {
            return $this_path.'/'.$program;
        }
        next($path);
    }
}

function execute_program($program, $args = '')
{
    $buffer = '';
    $program = find_program($program);

    if (!$program) {
        return;
    }

    // see if we've gotten a |, if we have we need to do patch checking on the cmd

    if ($args) {
        $args_list = preg_split('/ /', $args);
        for ($i = 0; $i < count($args_list); $i++) {
            if ($args_list[$i] == '|') {
                $cmd = $args_list[$i + 1];
                $new_cmd = find_program($cmd);
                $args = preg_replace('/\| '.preg_quote($cmd, '/').'/', '| '.$new_cmd, $args);
            }
        }
    }

    // we've finally got a good cmd line.. execute it

    if ($fp = popen($program.' '.$args, 'r')) {
        while (!feof($fp)) {
            $buffer .= fgets($fp, 4096);
        }
        return trim($buffer);
    }
}

function hostname()
{
    switch (PHP_OS) {
        case 'Linux':
            exec('hostname -f', $return);
            $hostname=$return[0];
            break;
        case 'Darwin':
        case 'FreeBSD':
        case 'OpenBSD':
        case 'NetBSD':
        case 'WinNT':
            exec('hostname', $return);
            $hostname=$return[0];
            break;
        default:
            $return = trans('unknown OS ($a)', PHP_OS);
    }

    if (!$hostname) {
        $hostname = $_ENV['HOSTNAME'] ?: $_SERVER['SERVER_NAME'];
    }
    if (!$hostname) {
        $hostname='N.A.';
    }

    return $hostname;
}

function long_ip($ip)
{
    $ip = (float) $ip;
    if ($ip > PHP_INT_MAX) {
        $ip = $ip - 2 - ((float) PHP_INT_MAX) * 2;
    }
    return long2ip($ip);
}

function ip_long($sip)
{
    if (check_ip($sip)) {
        return sprintf('%u', ip2long($sip));
    } else {
        return 0;
    }
}

function check_ip($ip)
{
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
}

function check_ipv6($ip)
{
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
}

/**
 * Validates the format of a CIDR notation string
 *
 * @param string $cidr
 * @return bool
 */
function check_cidr($cidr)
{
    $parts = explode('/', $cidr);
    if (count($parts) != 2) {
        return false;
    }

    $ip = $parts[0];
    $netmask = intval($parts[1]);

    if ($netmask < 0) {
        return false;
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return $netmask <= 32;
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return $netmask <= 128;
    }

    return false;
}

function check_mask($mask)
{
    $i=0;
    $j=0;
    $maskb=decbin(ip2long($mask));
    if (strlen($maskb) < 32) {
        return false;
    } else {
        while (($i<32) && ($maskb[$i] == '1')) {
            $i++;
        }
        $j=$i+1;
        while (($j<32) && ($maskb[$j] == '0')) {
            $j++;
        }
        if ($j<32) {
            return false;
        } else {
            return true;
        }
    }
}

/*!
 * \brief Returns network broadcast address by IP and mask.
 *
 * \param  string  $ip   IP address 192.168.0.0, 10.0.0.4, etc.
 * \param  string  $mask Network mask 255.255.255.000, etc.
 * \return longint       Network broadcast address
 * \return false         Incorrect IP or Mask
 */
function getbraddr($ip, $mask)
{
    if (check_ip($ip) && check_mask($mask)) {
        $net = ip2long(getnetaddr($ip, $mask));
        $mask = ip2long($mask);

        return long2ip($net | (~$mask));
    } else {
        return false;
    }
}

/*!
 * \brief Returns network address by IP and mask.
 *
 * \param  string  $ip   IP address 192.168.0.0, 10.0.0.4, etc.
 * \param  string  $mask Network mask 255.255.255.000, etc.
 * \return longint       Network IP
 * \return false         Incorrect IP or Mask
 */
function getnetaddr($ip, $mask)
{
    if (check_ip($ip) && check_mask($mask)) {
        $ip = ip2long($ip);
        $mask = ip2long($mask);

        return long2ip($ip & $mask);
    } else {
        return false;
    }
}

function prefix2mask($prefix)
{
    $prefix = intval($prefix);
    if ($prefix >= 0 && $prefix <= 32) {
        return long2ip(-1 << (32 - $prefix));
    } else {
        return false;
    }
}

function mask2prefix($mask)
{
    if (check_mask($mask)) {
        return strlen(str_replace('0', '', decbin(ip2long($mask))));
    } else {
        return -1;
    }
}

/*
 * mac checking function - requires macaddr passed as reference,
 * so it can fix mac address instantly to valid string
 */

function check_mac(&$macaddr)
{
    // save passed macaddr for future use

    $oldmac = $macaddr;

    // strip EVERYTHING that doesnt match 0-9 and a-f,
    // so $macaddr should contains 12 hex digits, and that's
    // will be base for our test

    $macaddr = preg_replace('/[^0-9a-f]/i', '', $macaddr);

    if (!preg_match('/^[0-9a-f]{12}$/i', $macaddr)) {
        // mac address isn't valid, restore it (cause we working on
        // reference) and return false

        $macaddr = $oldmac;

        return false;
    } else {
        // mac address is valid, return nice mac address that LMS
        // uses.

        $macaddr = $macaddr[0].$macaddr[1].':'.
            $macaddr[2].$macaddr[3].':'.
            $macaddr[4].$macaddr[5].':'.
            $macaddr[6].$macaddr[7].':'.
            $macaddr[8].$macaddr[9].':'.
            $macaddr[10].$macaddr[11];
        return true;
    }
}

function textwrap($text, $wrap = 76, $break = "\n")
{
    // This function is takem from newsportal

    $len = strlen($text);
    if ($len > $wrap) {
        $h = '';    // massaged text
        $lastWhite = 0; // position of last whitespace char
        $lastChar = 0;  // position of last char
        $lastBreak = 0; // position of last break
        // while there is text to process
        while ($lastChar < $len) {
            $char = substr($text, $lastChar, 1); // get the next character
            // if we are beyond the wrap boundry and there is a place to break
            if (($lastChar - $lastBreak > $wrap) && ($lastWhite > $lastBreak)) {
                $h .= substr($text, $lastBreak, ($lastWhite - $lastBreak)) . $break;
                $lastChar = $lastWhite + 1;
                $lastBreak = $lastChar;
            }
            // You may wish to include other characters as valid whitespace...
            if ($char == ' ' || $char == chr(13) || $char == chr(10)) {
                $lastWhite = $lastChar; // note the position of the last whitespace
            }

            $lastChar = $lastChar + 1; // advance the last character position by one
        }

        $h .= substr($text, $lastBreak); // build line
    } else {
        $h = $text; // in this case everything can fit on one line
    }
    return $h;
}

function isipin($ip, $net, $mask)
{
    if (ip_long($ip) > ip_long(getnetaddr($net, $mask)) && ip_long($ip) < ip_long(getbraddr($net, $mask))) {
        return true;
    } else {
        return false;
    }
}

function isipinstrict($ip, $net, $mask)
{
    if (ip_long($ip) >= ip_long(getnetaddr($net, $mask)) && ip_long($ip) <= ip_long(getbraddr($net, $mask))) {
        return true;
    } else {
        return false;
    }
}

function getmicrotime()
{
    // This function has been taken from PHP manual

    [$usec, $sec] = explode(' ', microtime());
    return ((float)$usec + (float)$sec);
}

function writesyslog($message, $type)
{
    // Untested on *BSD. Can anyone chek this out on *BSD machine? Thanx.

    switch (PHP_OS) {
        case 'FreeBSD':
        case 'OpenBSD':
        case 'Linux':
            $access = date('Y/m/d H:i:s');
            // open syslog, include the process ID and also send
            // the log to standard error, and use a user defined
            // logging mechanism
            openlog('lms-php', LOG_PID | LOG_NDELAY, LOG_AUTH);
            syslog($type, $message . ' (at ' . $access . ' from '
                . (empty($_SERVER['REMOTE_ADDR']) ? 'backend' : $_SERVER['REMOTE_ADDR'])
                . (isset($_SERVER['HTTP_USER_AGENT']) ? ' (' . $_SERVER['HTTP_USER_AGENT'] . '))' : ''));
            closelog();
            break;
        default:
            return false;
        break;
    }

    return true;
}

// Creates directories tree
function rmkdir($dir)
{
    if ($dir[0]!= DIRECTORY_SEPARATOR) {
        $dir = getcwd() . DIRECTORY_SEPARATOR . $dir;
    }
    $directories = explode(DIRECTORY_SEPARATOR, $dir);
    $makedirs = 0;
    for ($i=1; $i<count($directories); $i++) {
        $cdir = '';
        for ($j=1; $j<$i+1; $j++) {
            $cdir .= DIRECTORY_SEPARATOR . $directories[$j];
        }
        if (!is_dir($cdir)) {
            $result = mkdir($cdir);
            $makedirs ++;
        }
    }
    if (!$result && $makedirs) {
        return $result;
    } else {
        return $makedirs;
    }
}

// Deletes directory and all subdirs and files in it
function rrmdir($dir)
{
    $files = glob($dir . DIRECTORY_SEPARATOR . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            rrmdir($file);
        } else {
            unlink($file);
        }
    }
    if (is_dir($dir)) {
        rmdir($dir);
    }
}

function striphtml($text)
{
    $search = array ("'<script[^>]*?>.*?</script>'si",  // Strip out javascript
    "'<[\/\!]*?[^<>]*?>'si",        // Strip out html tags
    "'([\r\n])[\s]+'",          // Strip out white space
    "'&(quot|#34);'i",          // Replace html entities
    "'&(amp|#38);'i",
    "'&(lt|#60);'i",
    "'&(gt|#62);'i",
    "'&(nbsp|#160);'i",
    "'&(iexcl|#161);'i",
    "'&(cent|#162);'i",
    "'&(pound|#163);'i",
    "'&(copy|#169);'i",
    "'&#(\d+);'e");             // evaluate as php

    $replace = array ('',
    '',
    "\\1",
    '"',
    '&',
    '<',
    '>',
    ' ',
    chr(161),
    chr(162),
    chr(163),
    chr(169),
    "chr(\\1)");

    return preg_replace($search, $replace, $text);
}

function check_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function get_producer($mac)
{
    return EtherCodes::GetProducer($mac);
}

function setunits($data)  // for traffic data
{
    if ($data >= (1024*1024*1024*1024)) {
        $number = $data / (1024*1024*1024*1024);
        $unit = "TiB";
    } elseif ($data >= (1024*1024*1024)) {
        $number = $data / (1024*1024*1024);
        $unit = "GiB";
    } elseif ($data >= (1024*1024)) {
        $number = $data / (1024*1024);
        $unit = "MiB";
    } else {
        $number = $data / 1024;
        $unit = "KiB";
    }
    return array($number, $unit);
}

function convert_to_units($value, $threshold = 5, $multiplier = 1000, $unit_suffix = 'bit')
{
    $unit_suffix = ($multiplier == 1024 ? 'i' : '') . $unit_suffix;
    $threshold = floatval($threshold);
    $multiplier = floatval($multiplier);
    if ($value < $multiplier * $multiplier * $threshold) {
        $result = round($value / $multiplier, 2) . ' k' . $unit_suffix;
    } elseif ($value < $multiplier * $multiplier * $multiplier * $threshold) {
        $result = round($value / $multiplier / $multiplier, 2) . ' M' . $unit_suffix;
    } else {
        $result = round($value / $multiplier / $multiplier / $multiplier, 2) . ' G' . $unit_suffix;
    }
    return str_replace(',', '.', $result);
}

function r_trim($array)
{
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $array[$key] = r_trim($value);
        } elseif (!is_null($value)) {
            $array[$key] = trim($value);
        }
    }

    return $array;
}

// config value testers
function isboolean($value)
{
    if (preg_match('/^(1|y|on|yes|true|tak|t|0|n|no|off|false|nie|enabled|disabled)$/i', $value)) {
        return true;
    } else {
        return false;
    }
}

function moneyf($value, $currency = null, $precision = 2)
{
    if (empty($currency)) {
        $currency = Localisation::getCurrentCurrency();
    }
    return sprintf('%01.'.$precision.'f %s', $value, $currency);
}

function moneyf_in_words($value, $currency = null)
{
    if (empty($currency) || $currency == Localisation::getCurrentCurrency()) {
        $currency = Localisation::getCurrentCurrency();
    }
    return sprintf(
        Localisation::getCurrentMoneyFormatInWords(),
        to_words(floor($value)),
        $currency,
        round(($value - floor($value)) * 100)
    );
}

if (!function_exists('bcmod')) {
    function bcmod($x, $y)
    {
    // how many numbers to take at once? carefull not to exceed (int)
        $take = 5;
        $mod = '';
        do {
            $a = (int)$mod.substr($x, 0, $take);
            $x = substr($x, $take);
            $mod = $a % $y;
        } while (strlen($x));
        return (int)$mod;
    }
}

function docnumber($number = null, $template = null, $cdate = null, $ext_num = '')
{
    if (is_array($number)) {
        unset($template, $cdate, $ext_num);
        extract($number);
        if (!isset($number)) {
            $number = null;
        }
        if (!isset($template)) {
            $template = null;
        }
        if (!isset($cdate)) {
            $cdate = null;
        }
        if (!isset($ext_num)) {
            $ext_num = '';
        }
        if (!isset($customerid)) {
            $customerid = null;
        }
    }

    $number = $number ?: 1;
    $template = $template ?: DEFAULT_NUMBER_TEMPLATE;
    $cdate = $cdate ?: time();

    // customer id support
    if (empty($customerid)) {
        $result = preg_replace('/%\\d*C/', trans('customer ID'), $template);
    } else {
        $result = preg_replace_callback(
            '/%(\\d*)C/',
            function ($m) use ($customerid) {
                return sprintf('%0' . $m[1] . 'd', $customerid);
            },
            $template
        );
    }

    // extended number part
    $result = str_replace('%I', $ext_num, $result);

    // main document number
    $result = preg_replace_callback(
        '/%(\\d*)N/',
        function ($m) use ($number) {
            return sprintf('%0' . $m[1] . 'd', $number);
        },
        $result
    );

    // time conversion specifiers
    return Utils::strftime($result, $cdate);
}

// our finance round
function f_round($value, $precision = 2)
{
    $value = str_replace(',', '.', $value);
    $value = round((float) $value, $precision);
    return $value;
}

function fetch_url($url)
{
    $url_parsed = parse_url($url);
    $host = $url_parsed['host'];
    $path = $url_parsed['path'];
    $port = $url_parsed['port'] ?? 0; //sometimes port is undefined

    if ($port==0) {
        $port = 80;
    }
    if ($url_parsed['query'] != '') {
        $path .= '?'.$url_parsed['query'];
    }

    $request = "GET $path HTTP/1.0\r\nHost: $host\r\n\r\n";

    $fp = @fsockopen($host, $port, $errno, $errstr, 5);

    if (!$fp) {
        return false;
    }

    if (!stream_set_timeout($fp, 3)) {
        return false;
    }

    fwrite($fp, $request);
    $info = stream_get_meta_data($fp);
    if ($info['timed_out']) {
        return false;
    }

    $body = false;
    $out = '';

    while (!feof($fp)) {
        $s = fgets($fp, 1024);
        $info = stream_get_meta_data($fp);
        if ($info['timed_out']) {
            return false;
        }

        if ($body) {
            $out .= $s;
        }
        if ($s == "\r\n") {
            $body = true;
        }
    }

    fclose($fp);
    return $out;
}

// quoted-printable encoding
function qp_encode($string)
{
    // ASCII only - don't encode
    if (!preg_match('#[\x80-\xFF]{1}#', $string)) {
        return $string;
    }

    $encoded = preg_replace_callback(
        '/([\x2C\x3F\x80-\xFF])/',
        function ($m) {
            return '=' . sprintf("%02X", ord($m[1]));
        },
        $string
    );

    // replace spaces with _
    $encoded = str_replace(' ', '_', $encoded);

    return '=?UTF-8?Q?'.$encoded.'?=';
}

// escape quotes and backslashes, newlines, etc.
function escape_js($string)
{
        return strtr($string, array('\\'=>'\\\\',"'"=>"\\'",'"'=>'\\"',"\r"=>'\\r',"\n"=>'\\n','</'=>'<\/'));
}

function lms_ucwords($str)
{
    $result = array();
    $arr = preg_split('/\s+/', $str);

    foreach ($arr as $word) {
        $result[] = mb_strlen($word) > 1 ? mb_convert_case($word, MB_CASE_TITLE) : $word;
    }

    return implode(' ', $result);
}

// replace national character with ASCII equivalent
function clear_utf($str)
{
    $r = '';
        $s = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
    for ($i=0, $len=strlen($s); $i<$len; $i++) {
        $ch1 = $s[$i];
        $ch2 = mb_substr($str, $i, 1);
        $r .= $ch1=='?' ? $ch2 : $ch1;
    }
    return $r;
}

function mazovia_to_utf8($text)
{
    static $mazovia_regexp = array(
        '/\x86/', // ą
        '/\x92/', // ł
        '/\x9e/', // ś
        '/\x8d/', // ć
        '/\xa4/', // ń
        '/\xa6/', // ź
        '/\x91/', // ę
        '/\xa2/', // ó
        '/\xa7/', // ż
        '/\x8f/', // Ą
        '/\x9c/', // Ł
        '/\x98/', // Ś
        '/\x95/', // Ć
        '/\xa5/', // Ń
        '/\xa0/', // Ź
        '/\x90/', // Ę
        '/\xa3/', // Ó
        '/\xa1/', // Ż
    );

    static $utf8_codes = array(
        'ą', 'ł', 'ś', 'ć', 'ń', 'ź', 'ę', 'ó', 'ż',
        'Ą', 'Ł', 'Ś', 'Ć', 'Ń', 'Ź', 'Ę', 'Ó', 'Ż',
    );

    return preg_replace($mazovia_regexp, $utf8_codes, $text);
}

function lastonline_date($timestamp)
{
    if (!$timestamp) {
        return null;
    }

    $delta = time()-$timestamp;
    if ($delta > ConfigHelper::getConfig('phpui.lastonline_limit')) {
        if ($delta>59) {
            return trans('$a ago ($b)', uptimef($delta), date('Y/m/d, H:i', $timestamp));
        } else {
            return date('(Y/m/d, H:i)', $timestamp);
        }
    }

    return trans('online');
}

function is_leap_year($year)
{
    if ($year % 4) {
        return false;
    }
    if ($year % 100) {
        return true;
    }
    if ($year % 400) {
        return false;
    }
    return true;
}

function truncate_str($string, $length, $etc = '...')
{
    if ($length == 0) {
        return '';
    }

    if (mb_strlen($string) > $length) {
        $length -= min($length, mb_strlen($etc));
        return mb_substr($string, 0, $length) . $etc;
    } else {
        return $string;
    }
}

function location_str($data)
{
    $location = $data['city_name'];

    if ($data['location_flat']) {
        $h = ConfigHelper::getConfig('phpui.house_template', '%h/%f');
        $h = str_replace('%h', $data['location_house'], $h);
        $h = str_replace('%f', $data['location_flat'], $h);
    } else {
        $h = $data['location_house'];
    }

    if ($data['street_name']) {
        $street = (isset($data['street_type']) ? $data['street_type'] . ' ' : '') . $data['street_name'];
        $location .= ($location ? ',' : '') . $street;
    }

    if ($h) {
        $location .= ' ' . $h;
    }

    return $location;
}

function document_address($data)
{
    $lines = array();

    if ($data['name']) {
        $lines[] = $data['name'];
    }

    if ($data['postoffice'] && $data['postoffice'] != $data['city']) {
        $lines[] = ($data['street'] ? $data['city'] . ', ' : '') . $data['address'];
        $lines[] .= $data['zip'] . ' ' . $data['postoffice'];
    } else {
        $lines[] = $data['address'];
        $lines[] = $data['zip'] . ' ' . $data['city'];
    }

    return $lines;
}

function set_timer($label = 0)
{
    $GLOBALS['lms_timer'][$label] = microtime(true);
}

function get_timer($label = 0)
{
    return sprintf('%.4f', microtime(true) - $GLOBALS['lms_timer'][$label]);
}

/* Functions for modularized LMS */
function plugin_handle($name)
{
    global $PLUGINS;

    if (isset($PLUGINS[$name])) {
        foreach ($PLUGINS[$name] as $plugin) {
            include($plugin);
        }
    }
}

function clearheader()
{
    global $ExecStack, $layout;

    $ExecStack->replaceTemplate('core', 'header', 'core', 'clearheader');
    //$ExecStack->dropTemplate('core', 'menu');
}

/*
    Registering "plugin" function is for use in actions.
    $handle - handle name
    $plugin - template or action for including in handle. Example of use:

    register_plugin('nodeinfobox-end', '/path/sometemplate.html');
*/
function register_plugin($handle, $plugin)
{
        global $PLUGINS;

        $PLUGINS[$handle][] = $plugin;
}

function html2pdf($content, $subject = null, $title = null, $type = null, $id = null, $orientation = 'P', $margins = array(5, 10, 5, 10), $dest = 'I', $copy = false, $md5sum = '')
{
    return Utils::html2pdf(compact(
        'content',
        'subject',
        'title',
        'type',
        'id',
        'orientation',
        'margins',
        'dest',
        'copy',
        'md5sum'
    ));
}

function is_natural($var)
{
    return preg_match('/^[1-9][0-9]*$/', $var);
}

function check_password_strength($password)
{
    return (preg_match('/[a-z]/', $password) && preg_match('/[A-Z]/', $password)
        && preg_match('/[0-9]/', $password) && mb_strlen($password) >= 8);
}

function access_denied($message = null)
{
    global $SMARTY, $SESSION;

    if (isset($message)) {
        $SMARTY->assign('message', trans($message));
    }
    $SMARTY->display('noaccess.html');
    $SESSION->close();
    die;
}

function check_date($date)
{
    return preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $date);
}

function date_to_timestamp($date)
{
    if (!preg_match('/^(?<year>[0-9]{4})\/(?<month>[0-9]{2})\/(?<day>[0-9]{2})$/', $date, $m)
        || !checkdate($m['month'], $m['day'], $m['year'])) {
        return null;
    }
    return mktime(0, 0, 0, $m['month'], $m['day'], $m['year']);
}

function datetime_to_timestamp($datetime, $midnight = false)
{
    if (!preg_match('/^(?<year>[0-9]{4})\/(?<month>[0-9]{2})\/(?<day>[0-9]{2})\s+(?<hour>[0-9]{2}):(?<minute>[0-9]{2})(?::(?<second>[0-9]{2}))?$/', $datetime, $m)
        || !checkdate($m['month'], $m['day'], $m['year']) || $m['hour'] > 23 || $m['minute'] > 59 || (isset($m['second']) && $m['second'] > 59)) {
        return null;
    }
    if (!isset($m['second'])) {
        $m['second'] = 0;
    }
    if ($midnight) {
        return mktime(0, 0, 0, $m['month'], $m['day'], $m['year']);
    } else {
        return mktime($m['hour'], $m['minute'], $m['second'], $m['month'], $m['day'], $m['year']);
    }
}

function getdir($pwd = './', $pattern = '^.*$')
{
    $files = array();
    if ($handle = @opendir($pwd)) {
        while (($file = readdir($handle)) !== false) {
            if (preg_match('/' . $pattern . '/', $file)) {
                $files[] = $file;
            }
        }
        closedir($handle);
    }
    return $files;
}

function iban_account($country, $length, $id, $account = null)
{
    if ($account === null) {
        $DB = LMSDB::getInstance();
        $account = $DB->GetOne('SELECT account FROM divisions WHERE id IN (SELECT divisionid
			FROM customers WHERE id = ?)', array($id));
    }

    if (!empty($account)) {
        $acclen = strlen($account);

        if ($acclen <= $length - 6) {
            $format = '%0' . ($length - 2 - $acclen) . 'd';
            $account .= sprintf($format, $id);
            $checkaccount = $account . $country . '00';
            $numericaccount = '';
            for ($i = 0; $i < strlen($checkaccount); $i++) {
                $ch = strtoupper($checkaccount[$i]);
                $numericaccount .= ctype_alpha($ch) ? ord($ch) - 55 : $ch;
            }
            $account = sprintf('%02d', 98 - bcmod($numericaccount, 97)) . $account;
        }
    }

    return $account;
}

function iban_check_account($country, $length, $account)
{
    $account = preg_replace('/[^a-zA-Z0-9]/', '', $account);
    if (strlen($account) != $length) {
        return false;
    }
    $checkaccount = substr($account, 2, $length - 2) . $country. '00';
    $numericaccount = '';
    for ($i = 0; $i < strlen($checkaccount); $i++) {
        $ch = strtoupper($checkaccount[$i]);
        $numericaccount .= ctype_alpha($ch) ? ord($ch) - 55 : $ch;
    }
    return sprintf('%02d', 98 - bcmod($numericaccount, 97)) == substr($account, 0, 2);
}

function generate_random_string($length = 10, $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
{
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}

function validate_random_string($string, $min_size, $max_size, $characters)
{
    if (strlen($string) < $min_size || strlen($string) > $max_size) {
        return false;
    }
    for ($i = 0; $i < strlen($characters); $i++) {
        $string = str_replace($characters[$i], '', $string);
    }
    return !strlen($string);
}

function trans()
{
    return call_user_func_array('Localisation::trans', func_get_args());
}

function check_url($url)
{
    $components = parse_url($url);
    if ($components === false) {
        return false;
    }
    if (!isset($components['host']) || !isset($components['scheme'])) {
        return false;
    }
    return true;
}

function check_file_uploads()
{
    if (isset($_GET['fileupload']) && !isset($_POST['fileupload'])) {
        $result = array(
            'error' => trans('General file upload error - files are too large probably!'),
        );
        header('Content-type: application/json');
        print json_encode($result);
        die;
    }
}

function handle_file_uploads($elemid, &$error)
{
    $tmpdir = $tmppath = '';
    $fileupload = array();
    if (isset($_POST['fileupload'])) {
        $fileupload = $_POST['fileupload'];
        $tmpdir = $fileupload[$elemid . '-tmpdir'];
        if (empty($tmpdir)) {
            $tmpdir = uniqid('lms-fileupload-');
            $tmppath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $tmpdir;
            if (isset($_FILES[$elemid]) && !empty($_FILES[$elemid]['tmp_name'][0])
                && (is_dir($tmppath) || !@mkdir($tmppath))) {
                $tmpdir = '';
            }
        } elseif (preg_match('/^lms-fileupload-[0-9a-f]+$/', $tmpdir)) {
            $tmppath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $tmpdir;
            if (!file_exists($tmppath)) {
                @mkdir($tmppath);
            }
        } else {
            $tmpdir = '';
        }

        if (isset($_GET['ajax'])) {
            $files = array();
            if (isset($_FILES[$elemid])) {
                foreach ($_FILES[$elemid]['name'] as $fileidx => $filename) {
                    if (preg_match('/(\/\.\.|^\.\.$|\.\.\/|\/)/', $filename)) {
                        continue;
                    }
                    if (!empty($filename)) {
                        if (is_uploaded_file($_FILES[$elemid]['tmp_name'][$fileidx]) && $_FILES[$elemid]['size'][$fileidx]) {
                            $files[] = array(
                            'name' => $filename,
                            'tmp_name' => $_FILES[$elemid]['tmp_name'][$fileidx],
                            'type' => $_FILES[$elemid]['type'][$fileidx],
                            'size' => $_FILES[$elemid]['size'][$fileidx],
                            );
                        } else { // upload errors
                            if (isset($error[$elemid])) {
                                $error[$elemid] .= "\n";
                            } else {
                                $error[$elemid] = '';
                            }
                            switch ($_FILES[$elemid]['error'][$fileidx]) {
                                case UPLOAD_ERR_INI_SIZE:
                                case UPLOAD_ERR_FORM_SIZE:
                                    $error[$elemid] .= trans('File is too large: $a', $filename);
                                    break;
                                case UPLOAD_ERR_PARTIAL:
                                    $error[$elemid] .= trans('File upload has finished prematurely: $a', $filename);
                                    break;
                                case UPLOAD_ERR_NO_FILE:
                                    $error[$elemid] .= trans('Path to file was not specified: $a', $filename);
                                    break;
                                case UPLOAD_ERR_NO_TMP_DIR:
                                    $error[$elemid] .= trans('No temporary directory for file: $a', $filename);
                                    break;
                                case UPLOAD_ERR_CANT_WRITE:
                                    $error[$elemid] .= trans('Unable to write file: $a', $filename);
                                    break;
                                case UPLOAD_ERR_EXTENSION:
                                    $error[$elemid] .= trans('File upload has finished unexpectedly: $a', $filename);
                                    break;
                                default:
                                    $error[$elemid] .= trans('Problem during file upload: $a', $filename);
                                    break;
                            }
                        }
                    }
                }
            }

            if ($error && isset($error[$elemid])) {
                $result = array(
                    'error' => $error[$elemid],
                );
            } else {
                $errors = array();
                if (isset($fileupload) && !empty($tmpdir)) {
                    $files2 = array();
                    foreach ($files as &$file) {
                        unset($file2);
                        if (isset($fileupload[$elemid])) {
                            foreach ($fileupload[$elemid] as &$file2) {
                                if ($file['name'] == $file2['name']) {
                                    continue 2;
                                }
                            }
                        }
                        if (!file_exists($tmppath . DIRECTORY_SEPARATOR . $file['name'])) {
                            if (!@move_uploaded_file($file['tmp_name'], $tmppath . DIRECTORY_SEPARATOR . $file['name'])) {
                                $errors[] = trans('Unable to write file: $a', $file['name']);
                            }
                            unset($file['tmp_name']);
                        }
                        $files2[] = $file;
                    }
                    unset($file);
                    $files = $files2;
                    unset($files2, $file2);
                }
                if (!empty($errors)) {
                    $result = array(
                        'error' => implode('<br>', $errors),
                    );
                } else {
                    $result = array(
                        'error' => '',
                        'tmpdir' => $tmpdir,
                        'files' => $files,
                    );
                }
            }
            header('Content-type: application/json');
            print json_encode($result);
            die;
        } elseif (isset($fileupload[$elemid])) {
            foreach ($fileupload[$elemid] as &$file) {
                [$size, $unit] = setunits($file['size']);
                $file['sizestr'] = sprintf("%.02f", $size) . ' ' . $unit;
            }
            unset($file);
            ${$elemid} = $fileupload[$elemid];
        } else {
            ${$elemid} = array();
        }
    } else {
        ${$elemid} = array();
    }
    return compact('fileupload', 'tmppath', $elemid);
}

function check_gg($im)
{
    return preg_match('/^[0-9]{0,32}$/', $im);
}

function check_skype($im)
{
    return preg_match('/^[-_.a-z0-9]{0,32}$/i', $im);
}

function check_yahoo($im)
{
    return preg_match('/^[-_.a-z0-9]{0,32}$/i', $im);
}

function check_facebook($im)
{
    return preg_match('/^[.a-z0-9]{5,}$/i', $im);
}

/*!
 * \brief Recursive trim function.
 *
 * \param $data mixed
 */
function trim_rec($data)
{

    if (is_array($data)) {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = trim_rec($v);
            } else {
                $data[$k] = trim($v);
            }
        }

        return $data;
    } else {
        return trim($data);
    }
}

/*!
 * \brief Google Maps Geocode service function
 *
 * \param $location string location formatted human-friendly
 * \return mixed Result in associative array or null on error
 */

function geocode($location)
{
    $api_key = ConfigHelper::getConfig(
        'google.geocode_api_key',
        ConfigHelper::getConfig('phpui.googlemaps_api_key', '', true),
        true
    );
    $address = urlencode($location);
    $link = "https://maps.googleapis.com/maps/api/geocode/json?address=" . $address . "&sensor=false"
        . (empty($api_key) ? '' : '&key=' . $api_key);
    if (($res = @file_get_contents($link)) === false) {
        return null;
    }

    $page = json_decode($res, true);

    $latitude = empty($page['results']) ? null : str_replace(',', '.', $page["results"][0]["geometry"]["location"]["lat"]);
    $longitude = empty($page['results']) ? null : str_replace(',', '.', $page["results"][0]["geometry"]["location"]["lng"]);
    $accuracy = empty($page['results']) ? null : $page["results"][0]["geometry"]["location_type"];
    $status = $page["status"];
    return array(
        'status' => $status,
        'error' => $page['error_message'] ?? '',
        'accuracy' => $accuracy,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'raw-result' => $page,
    );
}

function osm_geocode($params)
{
    $address = array();
    foreach ($params as $name => $value) {
        $address[] = $name . '=' . urlencode($value);
    }
    $link = "https://nominatim.openstreetmap.org/search?" . implode('&', $address) . '&format=json&limit=1';

    $context = stream_context_create(array(
        'http' => array(
            'header' => array(
                'User-Agent: PHP/' . PHP_VERSION,
            ),
        ),
    ));

    if (($res = @file_get_contents($link, false, $context)) === false) {
        return null;
    }

    $page = json_decode($res, true);
    $latitude = isset($page[0]) ? str_replace(',', '.', $page[0]['lat']) : '';
    $longitude = isset($page[0]) ? str_replace(',', '.', $page[0]['lon']) : '';
    return array(
        'latitude' => $latitude,
        'longitude' => $longitude,
        'raw-result' => $page,
    );
}

function exchangeratesapi_get_currency_value($currency, $date = null)
{
    $result = file_get_contents('https://api.exchangeratesapi.io/'
        . (empty($date) ? 'latest' : date('Y-m-d', $date)) . '?base=' . $currency . '&symbols=' . Localisation::getCurrentCurrency());
    if ($result === false) {
        return null;
    }

    $result = json_decode($result, true);
    if ($result === null) {
        return null;
    }

    if (!isset($result['rates'][Localisation::getCurrentCurrency()])) {
        return null;
    }
    return $result['rates'][Localisation::getCurrentCurrency()];
}

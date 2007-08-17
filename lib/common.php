<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2007 LMS Developers
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

function bsd_grab_key ($key)
{
	return execute_program('sysctl', '-n '.$key);
}

function find_program ($program)
{
	$path = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin', '/usr/local/sbin');
	while ($this_path = current($path))
	{
		if (is_executable($this_path.'/'.$program))
		{
			return $this_path.'/'.$program;
		}
		next($path);
	}
	return;
}

function execute_program ($program, $args = '')
{
	$buffer = '';
	$program = find_program($program);

	if (!$program) { return; }

	// see if we've gotten a |, if we have we need to do patch checking on the cmd

	if ($args)
	{
		$args_list = split(' ', $args);
		for ($i = 0; $i < count($args_list); $i++)
		{
			if ($args_list[$i] == '|')
			{
				$cmd = $args_list[$i + 1];
				$new_cmd = find_program($cmd);
				$args = ereg_replace('\| '.$cmd, '| '.$new_cmd, $args);
			}
		}
	}

	// we've finally got a good cmd line.. execute it

	if ($fp = popen($program.' '.$args, 'r'))
	{
		while (!feof($fp))
		{
			$buffer .= fgets($fp, 4096);
		}
		return trim($buffer);
	}
}

function hostname()
{
	switch(PHP_OS)
	{
		case 'Linux':
			exec('hostname -f',$return);
			$hostname=$return[0];
			break;
		case 'Darwin':
		case 'FreeBSD':
		case 'OpenBSD':
		case 'NetBSD':
		case 'WinNT':
			exec('hostname',$return);
			$hostname=$return[0];
			break;
		default:
			$return = trans('unknown OS ($0)', PHP_OS);
	}
	
	if(!$hostname)
		$hostname = $_ENV['HOSTNAME'] ? $_ENV['HOSTNAME'] : $_SERVER['SERVER_NAME'];
	if(!$hostname)
		$hostname='N.A.';
		
	return $hostname;
}

function ip_long($sip)
{
	if(check_ip($sip)){
		return sprintf('%u',ip2long($sip));
	}else{
		return 0;
	}
}

function check_ip($ip)
{
	$count = 0;
	$x = explode('.', $ip);
	$max = count($x);
	for ($i = 0; $i < $max; $i++)
		if ($x[$i] >= 0 && $x[$i] <= 255 && preg_match('/^\d{1,3}$/', $x[$i]))
			$count++;
	if ($count == 4 && $max == 4)
		return true;
	else
		return false;
}

function check_mask($mask)
{
	$i=0;
	$j=0;
	$maskb=decbin(ip2long($mask));
	if (strlen($maskb) < 32)
		return FALSE;
	else
	{
		while (($i<32) && ($maskb[$i] == '1'))
		{
			$i++;
		}
		$j=$i+1;
		while (($j<32) && ($maskb[$j] == '0'))
		{
			$j++;
		}
		if ($j<32)
			return FALSE;
		else
			return TRUE;
	}
}

function getbraddr($ip,$mask)
{
	if(check_ip($ip) && check_mask($mask))
	{
		$net = ip2long(getnetaddr($ip, $mask));
		$mask = ip2long($mask);

		return long2ip($net | (~$mask));
	}
	else
		return false;
}

function getnetaddr($ip,$mask)
{
	if(check_ip($ip) && check_mask($mask))
	{
		$ip = ip2long($ip);
		$mask = ip2long($mask);
		
		return long2ip($ip & $mask);
	}
	else
		return false;
}

function prefix2mask($prefix)
{
	if($prefix>=0&&$prefix<=32)
	{	
		$out = '';
		for($ti=0;$ti<$prefix;$ti++)
			$out .= '1';
		for($ti=$prefix;$ti<32;$ti++)
			$out .= '0';
		return long2ip(bindec($out));
	}
	else
		return false;
}

function mask2prefix($mask)
{
	if(check_mask($mask))
	{
		return strlen(str_replace('0','',decbin(ip2long($mask))));
	}
	else
	{
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

	$macaddr = eregi_replace('[^0-9a-f]', '', $macaddr);

	if(! eregi('^[0-9a-f]{12}$', $macaddr))
	{
		// mac address isn't valid, restore it (cause we working on
		// reference) and return false
	
		$macaddr = $oldmac;
	
		return FALSE;
	}
	else
	{
		// mac address is valid, return nice mac address that LMS
		// uses.

		$macaddr = $macaddr[0].$macaddr[1].':'.
			$macaddr[2].$macaddr[3].':'.
			$macaddr[4].$macaddr[5].':'.
			$macaddr[6].$macaddr[7].':'.
			$macaddr[8].$macaddr[9].':'.
			$macaddr[10].$macaddr[11];
		return TRUE;
	}
}

function textwrap($text, $wrap=76, $break = "\n")
{
	// This function is takem from newsportal

	$len = strlen($text);
	if ($len > $wrap)
	{
		$h = '';	// massaged text
		$lastWhite = 0; // position of last whitespace char
		$lastChar = 0;  // position of last char
		$lastBreak = 0; // position of last break
		// while there is text to process
		while ($lastChar < $len)
		{
			$char = substr($text, $lastChar, 1); // get the next character
			// if we are beyond the wrap boundry and there is a place to break
			if (($lastChar - $lastBreak > $wrap) && ($lastWhite > $lastBreak))
			{
				$h .= substr($text, $lastBreak, ($lastWhite - $lastBreak)) . $break;
				$lastChar = $lastWhite + 1;
				$lastBreak = $lastChar;
			}
			// You may wish to include other characters as valid whitespace...
			if ($char == ' ' || $char == chr(13) || $char == chr(10))
				$lastWhite = $lastChar; // note the position of the last whitespace
			
			$lastChar = $lastChar + 1; // advance the last character position by one
		}
		
		$h .= substr($text, $lastBreak); // build line
	} 
	else 
	{
		$h = $text; // in this case everything can fit on one line
	}
	return $h;
}

function isipin($ip,$net,$mask)
{
	if(ip_long($ip) > ip_long(getnetaddr($net,$mask)) && ip_long($ip) < ip_long(getbraddr($net,$mask)))
		return true;
	else
		return false;
}

function isipinstrict($ip,$net,$mask)
{
	if(ip_long($ip) >= ip_long(getnetaddr($net,$mask)) && ip_long($ip) <= ip_long(getbraddr($net,$mask)))
		return true;
	else
		return false;
}

function getmicrotime()
{
	// This function has been taken from PHP manual

	list($usec, $sec) = explode(' ',microtime());
	return ((float)$usec + (float)$sec);
}

function writesyslog($message,$type)
{
	// Untested on *BSD. Can anyone chek this out on *BSD machine? Thanx.

	switch(PHP_OS)
	{
		case 'OpenBSD':
		case 'Linux':
			define_syslog_variables();
			// Taken from PHP manual. On my WinXP box with Easy PHP it's fuck's up
			// system

			// open syslog, include the process ID and also send
			// the log to standard error, and use a user defined
			// logging mechanism

			openlog('lms-php', LOG_PID | LOG_NDELAY, LOG_AUTH);

			$access = date('Y/m/d H:i:s');

			syslog($type,$message.' (at '.$access.' from '.$_SERVER['REMOTE_ADDR'].' ('.$_SERVER['HTTP_USER_AGENT'].'))');

			closelog();
		break;
		default:
			return FALSE;
		break;
	}

	return TRUE;
}

function rmkdir($dir)
{
	if($dir[0]!='/')
		$dir = getcwd() . '/' . $dir;
	$directories = explode('/',$dir);
	$makedirs = 0;
	for($i=1;$i<sizeof($directories);$i++)
	{
		$cdir = '';
		for($j=1;$j<$i+1;$j++)
			$cdir .= '/'.$directories[$j];
		if(!is_dir($cdir))
		{
			$result = mkdir($cdir,0777);
			$makedirs ++;
		}
	}
	if(!$result && $makedirs)
		return $result;
	else
		return $makedirs;
}

function striphtml($text)
{
	$search = array ("'<script[^>]*?>.*?</script>'si",  // Strip out javascript
	"'<[\/\!]*?[^<>]*?>'si",		// Strip out html tags
	"'([\r\n])[\s]+'",			// Strip out white space
	"'&(quot|#34);'i",			// Replace html entities
	"'&(amp|#38);'i",
	"'&(lt|#60);'i",
	"'&(gt|#62);'i",
	"'&(nbsp|#160);'i",
	"'&(iexcl|#161);'i",
	"'&(cent|#162);'i",
	"'&(pound|#163);'i",
	"'&(copy|#169);'i",
	"'&#(\d+);'e");				// evaluate as php

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

	return preg_replace ($search, $replace, $text);
}

function check_email( $email )
{
	$length = strlen( $email );

	if(
			!$email
			|| substr($email,0,1) == '@'
			|| substr($email,0,1) == '.'
			|| strrpos($email,'@') == ($length - 1)
			|| strrpos($email,'.') == ($length - 1)
			|| substr_count($email,'@') != 1
			|| !substr_count(substr($email,strpos($email,'@')),'.')
			|| substr_count($email,'..')
			|| ($length-strrpos($email,'.'))<3
	)
		return FALSE;

	$email_charset = 'qwertyuiopasdfghjklzxcvbnm1234567890@-._';
	$i = 0;
	while ( $i < $length )
	{
		$char = $email[$i++];
		if ( stristr( $email_charset, $char ) === false )
			return FALSE;
	}

	return TRUE;
}

function get_producer($mac)
{
	$mac = strtoupper(str_replace(':','-',substr($mac,0,8)));
	if($macfile = fopen(LIB_DIR.'/ethercodes.txt','r'))
		while(!feof($macfile))
		{
			$line = trim(fgets($macfile,4096));
			if($line)
				list($prefix,$producer) = explode(':', $line);
			if($mac == $prefix)
				break;
		}
	fclose($macfile);
	return $producer;
}

function setunits($data)  // for traffic data
{
	if ( !($data < (1024*1024*1000)) )
	{
		$number = $data / (1024*1024*1024);
		$unit = "GB";
	}
	elseif ( !($data < (1024*1000) ) )
	{
		$number = $data / (1024*1024);
		$unit = "MB";
	} 
	else
	{
		$number = $data / 1024;
		$unit = "KB";
	}
	return array($number, $unit);
}

function r_trim($array)
{
	foreach($array as $key => $value)
		if(is_array($value))
			$array[$key] = r_trim($value);
		else
			$array[$key] = trim($value);
	
	return $array;
}

// config value testers
function isboolean($value)
{
	if(eregi('^(1|y|on|yes|true|tak|t|0|n|no|off|false|nie)$', $value))
		return TRUE;
	else
		return FALSE;
}

function moneyf($value)
{
	global $LANGDEFS, $_language;
	return sprintf($LANGDEFS[$_language]['money_format'],$value);
}

if (!function_exists('bcmod'))
{
    function bcmod( $x, $y )
    {
	// how many numbers to take at once? carefull not to exceed (int)
        $take = 5;   
	$mod = '';
        do
	{
	    $a = (int)$mod.substr( $x, 0, $take );
	    $x = substr( $x, $take );
	    $mod = $a % $y;   
	}
	while ( strlen($x) );
	    return (int)$mod;
    }
}					     

function docnumber($number=NULL, $template=NULL, $time=NULL, $ext_num='')
{
	$number = $number ? $number : 1;
	$template = $template ? $template : DEFAULT_NUMBER_TEMPLATE;
	$time = $time ? $time : time();
	
	// extended number part
	$result = str_replace('%I', $ext_num, $template);

	// main document number
	$result = preg_replace('/%(\\d*)N/e', "sprintf('%0\\1d', $number)", $result);
	
	// time conversion specifiers
	return strftime($result, $time);
}

// our finance round
function f_round($value)
{
	$value = str_replace(',','.', $value);
	$value = round ( (float) $value, 2);
	return $value;
}

function fetch_url($url)
{
	$url_parsed = parse_url($url);
	$host = $url_parsed['host'];
	$path = $url_parsed['path'];
        $port = isset($url_parsed['port']) ? $url_parsed['port'] : 0; //sometimes port is undefined

        if ($port==0)
	        $port = 80;
	if ($url_parsed['query'] != '')
	         $path .= '?'.$url_parsed['query'];
		 
	$request = "GET $path HTTP/1.0\r\nHost: $host\r\n\r\n";

	$fp = @fsockopen($host, $port, $errno, $errstr, 5);

	if(!$fp) return FALSE;
	
	fwrite($fp, $request);
	$body = FALSE;
	$out = '';
	
	while(!feof($fp))
	{
		$s = fgets($fp, 1024);
		if($body)
		        $out .= $s;
		if($s == "\r\n")
			$body = TRUE;
	}

	fclose($fp);
	return $out;
}

function plugin_handle($name)
{
        global $PLUGINS;
	
	if(isset($PLUGINS[$name]))
		foreach($PLUGINS[$name] as $plugin)
			include($plugin);
}

function clearheader()
{
        global $ExecStack, $layout;
	
	$ExecStack->replaceTemplate('core', 'header', 'core', 'clearheader');
	//$ExecStack->dropTemplate('core', 'menu');
	$layout['nomenu'] = TRUE;
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

?>

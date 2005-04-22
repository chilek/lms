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

function uptime()
{
	// Uptime function. Taken afair from PHPSysinfo.

	switch (PHP_OS)
	{
		case 'Linux':
			$fd = fopen('/proc/uptime', 'r');
			$ar_buf = split(' ', fgets($fd, 4096));
			fclose($fd);
			$sys_ticks = trim($ar_buf[0]);
			$result = uptimef($sys_ticks);
		break;
		case 'FreeBSD':
			$s = explode(' ', bsd_grab_key('kern.boottime'));
			$a = ereg_replace('{ ', '', $s[3]);
			$sys_ticks = time() - $a;
			$result = uptimef($sys_ticks);
		break;
		case 'NetBSD':
			$a = bsd_grab_key('kern.boottime');
			$sys_ticks = time() - $a;
			$result = uptimef($sys_ticks);
		break;
		case 'OpenBSD':
			$a = bsd_grab_key('kern.boottime');
			$sys_ticks = time() - $a;
			$result = uptimef($sys_ticks);
		break;
		case 'WINNT':
//			dl("php_w32api.dll");
			if(function_exists('w32api_register_function'))
			{
				w32api_register_function('kernel32.dll','GetTickCount','long');
				$ticks = GetTickCount();
				$secs  = floor($ticks / 1000);
				$mins  = floor($secs / 60);
				$hours = floor($mins / 60);
				$str = sprintf('You have been using your computer for: %d Milliseconds, or %d Seconds or %d mins or %d hours %d mins.',	$ticks,	$secs, $mins, $hours, $mins - ($hours*60));
			}else{
				$result = trans('unknown (no w32api)');
			}
		break;
		default:
			$result = trans('unknown OS ($0)', PHP_OS);
		break;
	}

	return $result;
}

function redir($url)
{
	if($url)
	{
		header('Location: '.$url);
		die;
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

function getbraddr($ip,$mask)
{
	if(check_ip($ip) && check_mask($mask))
	{
		$ipa=ip2long($ip);
		$maska=ip2long($mask);
		$ipb = decbin($ipa);
		while (strlen($ipb) != 32)
		{
			$ipb = '0'.$ipb;
		}
		$maskb = decbin($maska);
		$i=0;
		$out = '';
		while (($i<32) && ($maskb[$i]=='1'))
		{
			$out .= $ipb[$i];
			$i++;
		}
		while(strlen($out) != 32)
			$out.='1';
		return long2ip(bindec($out));
	}
	else
		return false;
}

function getnetaddr($ip,$mask)
{
	if(check_ip($ip))
	{
		$ipa=ip2long($ip);
		$maska=ip2long($mask);
		$ipb = decbin($ipa);
		while (strlen($ipb) != 32)
			$ipb = '0'.$ipb;
		$maskb = decbin($maska);
		while (strlen($maskb) != 32)
			$maskb = '0'.$maskb;
		$out = '00000000000000000000000000000000';
		for ($i=0; $i<32; $i++)
			if ($maskb[$i] == '1')
				$out[$i]=$ipb[$i];
		return long2ip(bindec($out));
	}
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

function check_mac($macaddr)
{
	$macaddr = str_replace('-',':',$macaddr);
	return eregi('^[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}$',$macaddr);
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
	global $_LIB_DIR;
	$mac = strtoupper(str_replace(':','-',substr($mac,0,8)));
	if($macfile = fopen($_LIB_DIR.'/ethercodes.txt','r'))
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

function sql_random()
{
	return ( rand()/getrandmax() );
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

?>

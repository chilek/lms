<?

/*
 * LMS version 1.0-cvs
 *
 *  (C) Copyright 2001-2003 LMS Developers
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

/*
 * System information - u�ywane przez welcome.php
 * Bazowane na projekcie phpsysinfo - http://phpsysinfo.sourceforge.net/
 */

class Sysinfo {
	
	function get_sysinfo()
	{
		$return[hostname] = $this->hostname();
		$return[uptime] = $this->uptime();
		$return[kernel] = $this->kernel();
		$return[users] = $this->users();
		$return[loadavg] = $this->loadavg();
		$return[phpversion] = phpversion();
		return $return;
	}

	function bsd_grab_key ($key)
	{
		return $this->execute_program('sysctl', "-n $key");
	}

	function execute_program ($program, $args = '')
	{
		$buffer = '';
		$program = $this->find_program($program);
	
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
					$new_cmd = $this->find_program($cmd);
					$args = ereg_replace("\| $cmd", "| $new_cmd", $args);
				}
			}
		}

		// we've finally got a good cmd line.. execute it

		if ($fp = popen("$program $args", 'r'))
		{
			while (!feof($fp))
			{
				$buffer .= fgets($fp, 4096);
			}
			return trim($buffer);
		}
	}

	function find_program ($program)
	{
		$path = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin', '/usr/local/sbin');
		while ($this_path = current($path))
		{
			if (is_executable("$this_path/$program"))
			{
				return "$this_path/$program";
			}
		next($path);
		}
		return;
	}

	function hostname()
	{
		switch(PHP_OS)
		{
			case "Linux":
			case "FreeBSD":
			case "OpenBSD":
			case "NetBSD":
				exec("hostname -f",$return);
				$hostname=$return[0];
				break;
			case "WinNT":
				exec("hostname",$return);
				$hostname=$return[0];
				break;
			default:
				$return = "nieznany, ".PHP_OS;
		}
		if($hostname=="")
			$hostname="N.A.";
		return $hostname;
	}
		
	function uptime()
	{
		// Uptime function.
		// Taken afair from PHPSysinfo
		// Untested on *BSD. Can anyone chek this out on *BSD machine? Thanx.
		
		switch (PHP_OS)
		{
			
			case "Linux":
				$fd = fopen('/proc/uptime', 'r');
				$ar_buf = split(' ', fgets($fd, 4096));
				fclose($fd);
				$sys_ticks = trim($ar_buf[0]);
				$min   = $sys_ticks / 60;
				$hours = $min / 60;
				$days  = floor($hours / 24);
				$hours = floor($hours - ($days * 24));
				$min   = floor($min - ($days * 60 * 24) - ($hours * 60));
				if ($days != 0)
					$result = "$days dni ";
				if ($hours != 0)
					$result .= "$hours godzin ";
				$result .= "$min minut";
			break;
			case "FreeBSD":
				$s = explode(' ', $this->bsd_grab_key('kern.boottime'));
				$a = ereg_replace('{ ', '', $s[3]);
				$sys_ticks = time() - $a;
				$min   = $sys_ticks / 60;
				$hours = $min / 60;
				$days  = floor($hours / 24);
				$hours = floor($hours - ($days * 24));
				$min   = floor($min - ($days * 60 * 24) - ($hours * 60));
				
				if ( $days != 0 )
					$result = "$days dni ";
				if ( $hours != 0 )
					$result .= "$hours godzin ";
				$result .= "$min minut";
			break;
			case "NetBSD":
				$a = $this->bsd_grab_key('kern.boottime');
				$sys_ticks = time() - $a;
				$min   = $sys_ticks / 60;
				$hours = $min / 60;
				$days  = floor($hours / 24);
				$hours = floor($hours - ($days * 24));
				$min   = floor($min - ($days * 60 * 24) - ($hours * 60));

				if ( $days != 0 )
					$result = "$days dni ";
				if ( $hours != 0 )
					$result .= "$hours godzin ";
				$result .= "$min minut";
			break;
			case "OpenBSD":
				$a = $this->bsd_grab_key('kern.boottime');
				$sys_ticks = time() - $a;
				$min   = $sys_ticks / 60;
				$hours = $min / 60;
				$days  = floor($hours / 24);
				$hours = floor($hours - ($days * 24));
				$min   = floor($min - ($days * 60 * 24) - ($hours * 60));

				if ( $days != 0 )
					$result = "$days dni ";
				if ( $hours != 0 )
					$result .= "$hours godzin ";
				$result .= "$min minut";
			break;
			default:
				$result = "nieznany os (".PHP_OS.")";
			break;
		}
		return $result;
	}

	function kernel()
	{
		switch(PHP_OS)
		{
			
			case "WinNT":
				$result = 'Windows NT/2000/XP N.A.';
			break;
			case "Win32":
				$result = 'Windows 95/98/ME N.A.';
			break;
			default:
				$result = $this->execute_program('uname','-s -r');
			break;
		}
		if($result=='')
			$result = 'N.A.';
		$result .= ' ('.PHP_OS.')';
		return $result;
	}

	function users()
	{
		switch(PHP_OS)
		{
			case "WinNT":
			case "Win32":
				$result = "N.A.";
			default:
				$who = split('=', execute_program('who', '-q'));
				$result = $who[1];
			break;
		}
		if($result=='')
			$result = 'N.A.';
		return $result;

	}

	function loadavg()
	{
		switch(PHP_OS)
		{
			case "Linux":
				if ($fd = fopen('/proc/loadavg', 'r'))
				{
					$results = split(' ', fgets($fd, 4096));
					fclose($fd);
				}
				else
					$results = array('N.A.','N.A.','N.A.');
			break;
			default:
				$results = array('N.A.','N.A.','N.A.');
			break;
		}
		return $results;
	}
											    
}
?>

<?php

/*
 * LMS version 1.11-git
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

$valid_tlds = array(
  "ac", "ad", "ae", "aero", "af", "ag", "ai", "al", "am", "an", "ao", "aq", "ar",
  "arpa", "as", "asia", "at", "au", "aw", "ax", "az", "ba", "bb", "bd", "be",
  "bf", "bg", "bh", "bi", "biz", "bj", "bm", "bn", "bo", "br", "bs", "bt", "bv",
  "bw", "by", "bz", "ca", "cat", "cc", "cd", "cf", "cg", "ch", "ci", "ck", "cl",
  "cm", "cn", "co", "com", "coop", "cr", "cu", "cv", "cx", "cy", "cz", "de", "dj",
  "dk", "dm", "do", "dz", "ec", "edu", "ee", "eg", "er", "es", "et", "eu", "fi",
  "fj", "fk", "fm", "fo", "fr", "ga", "gb", "gd", "ge", "gf", "gg", "gh", "gi",
  "gl", "gm", "gn", "gov", "gp", "gq", "gr", "gs", "gt", "gu", "gw", "gy", "hk",
  "hm", "hn", "hr", "ht", "hu", "id", "ie", "il", "im", "in", "info", "int", "io",
  "iq", "ir", "is", "it", "je", "jm", "jo", "jobs", "jp", "ke", "kg", "kh", "ki",
  "km", "kn", "kp", "kr", "kw", "ky", "kz", "la", "lb", "lc", "li", "lk", "lr",
  "ls", "lt", "lu", "lv", "ly", "ma", "mc", "md", "me", "mg", "mh", "mil", "mk",
  "ml", "mm", "mn", "mo", "mobi", "mp", "mq", "mr", "ms", "mt", "mu", "museum",
  "mv", "mw", "mx", "my", "mz", "na", "name", "nc", "ne", "net", "nf", "ng", "ni",
  "nl", "no", "np", "nr", "nu", "nz", "om", "org", "pa", "pe", "pf", "pg", "ph",
  "pk", "pl", "pm", "pn", "pr", "pro", "ps", "pt", "pw", "py", "qa", "re", "ro",
  "rs", "ru", "rw", "sa", "sb", "sc", "sd", "se", "sg", "sh", "si", "sj", "sk",
  "sl", "sm", "sn", "so", "sr", "st", "su", "sv", "sy", "sz", "tc", "td", "tel",
  "tf", "tg", "th", "tj", "tk", "tl", "tm", "tn", "to", "tp", "tr", "travel",
  "tt", "tv", "tw", "tz", "ua", "ug", "uk", "um", "us", "uy", "uz", "va", "vc",
  "ve", "vg", "vi", "vn", "vu", "wf", "ws", "xn--0zwm56d", "xn--11b5bs3a9aj6g",
  "xn--80akhbyknj4f", "xn--9t4b11yi5a", "xn--deba0ad", "xn--g6w251d",
  "xn--hgbk6aj7f53bba", "xn--hlcj6aya9esc7a", "xn--jxalpdlp", "xn--kgbechtv",
  "xn--zckzah", "ye", "yt", "yu", "za", "zm", "zw"
);


function check_hostname_fqdn($hostname, $wildcard=false, $dns_strict_tld_check=false) {

        global $valid_tlds;
        $hostname = trim($hostname, '.');

        if (strlen($hostname) > 255) {            
                return trans('The hostname is too long!');
        }

        $hostname_labels = explode ('.', $hostname);
        $label_count = count($hostname_labels);

        foreach ($hostname_labels as $hostname_label) {
                if ($wildcard && !isset($first)) {
                        if (!preg_match('/^(\*|[a-zA-Z0-9-\/]+)$/',$hostname_label)) {
				return trans('You have invalid characters in your hostname!');
			}
                        $first = 1;
                } else {
                        if (!preg_match('/^[a-zA-Z0-9-\/_]+$/',$hostname_label)) {
				return trans('You have invalid characters in your hostname!');
			}
                }
                if ($hostname_label[0] == '-' || substr($hostname_label, -1, 1) == '-') {
			return trans('A hostname can not start or end with a dash!');
		}
                if (strlen($hostname_label) < 1 || strlen($hostname_label) > 63) {
			return trans('Given hostname or one of the labels is too short or too long!');
		}
        }

        if ($hostname_labels[$label_count-1] == 'arpa'
		&& (substr_count($hostname_labels[0], '/') == 1 XOR substr_count($hostname_labels[1], '/') == 1)
	) {
                if (substr_count($hostname_labels[0], '/') == 1) {
                        $array = explode ('/', $hostname_labels[0]);
                } else {
                        $array = explode ('/', $hostname_labels[1]);
                }
                if (count($array) != 2) {
			return trans('Invalid hostname!');
		}
                if (!is_numeric($array[0]) || $array[0] < 0 || $array[0] > 255) {
			return trans('Invalid hostname!');
		}
                if (!is_numeric($array[1]) || $array[1] < 25 || $array[1] > 31) {
			return trans('Invalid hostname!');
		}
        } else {
                if (substr_count($hostname, '/') > 0) {
			return trans('Given hostname has too many slashes!');
		}
        }

        if ($dns_strict_tld_check && !in_array($hostname_labels[$label_count-1], $valid_tlds)) {
               return trans('You are using an invalid top level domain!');
        }

        return false;
}


function update_soa_serial($did)
{
	global $DB;

	$record = $DB->GetRow("SELECT * from records where domain_id = ? and type='SOA'", array($did));

	$soa = explode(' ', $record['content']);

	if ($soa[2] == '0') {
                return true;
        } elseif ($soa[2] == date('Ymd') . '99') {
                return true;
        } else {
                $today = date('Ymd');

                // Determine revision.
                if (strncmp($today, $soa[2], 8) === 0) {
                        // Current serial starts with date of today, so we need to update
                        // the revision only. To do so, determine current revision first,
                        // then update counter.
                        $revision = (int) substr($soa[2], -2);
                        ++$revision;
                } else {
                        // Current serial did not start of today, so it's either an older
                        // serial or a serial that does not adhere the recommended syntax
                        // of RFC-1912. In either way, set a fresh serial
                        $revision = '00';
                }

                $serial = $today . str_pad($revision, 2, '0', STR_PAD_LEFT);;

                // Change serial in SOA array.
                $soa[2] = $serial;

                // Build new SOA record content and update the database
		$DB->Execute('UPDATE records SET content = ? WHERE id = ?',
            		array(implode(' ', $soa), $record['id']));
        }
}

/*
 * Parses record content (from DB) into separate form fields
 */
function parse_dns_record(&$record)
{
	$record['name'] = substr($record['name'], 0, -(strlen($record['domainname']) + 1));

	switch ($record['type'])
	{
		case 'A':
		case 'AAAA':
			$record['ipdst'] = $record['content'];
		break;
		case 'NS':
			$record['ns'] = $record['content'];
		break;
		case 'MX':
			$record['mailserver'] = $record['content'];
		break;
		case 'CNAME':
			$record['alias'] = $record['name'];
			$record['domain'] = $record['content'];
		break;
		case 'TXT':
		case 'SPF':
			$record['desc'] = $record['content'];
		break;
		case 'PTR':
			$record['domain'] = $record['content'];
		break;
		case 'SOA':
			$cnt = preg_split('/[\s\t]+/', $record['content']);
			$record['ns'] = $cnt[0];
			$record['email'] = $cnt[1];
			$record['serial'] = $cnt[2];
			$record['refresh'] = $cnt[3];
			$record['retry'] = $cnt[4];
			$record['expire'] = $cnt[5];
			$record['minttl'] = $cnt[6];
		break;
		case 'SSHFP':
			$cnt = preg_split('/[\s\t]+/', $record['content']);
			$record['algo'] = $cnt[0];
			$record['ftype'] = $cnt[1];
			$record['fingerprint'] = $cnt[2];
		break;
		case 'SRV':
			$cnt = preg_split('/[\s\t]+/', $record['content']);
			$record['weight'] = $cnt[0];
			$record['port'] = $cnt[1];
			$record['domain'] = $cnt[2];
		break;
		case 'HINFO':
			$cnt = preg_split('/[\s\t]+/', $record['content']);
			$record['cpu'] = $cnt[0];
			$record['os'] = $cnt[1];
		break;
	}
}

/*
 * Validates record data (from html form)
 * Errors are returned by reference in 4th argument
 */
function validate_dns_record(&$record, &$error)
{
	$arpa_records = array('PTR','SOA','NS','TXT','CNAME','MX','SPF','NAPTR','URL','MBOXFW','CURL','SSHFP');

	// domena in-addr.arpa
        if (preg_match('/in-addr\.arpa$/', $record['domainname']))
	{
	        if (!in_array($record['type'], $arpa_records))
			$error['type'] = trans('Wrong record type!');
	}

	if ($error)
		return;

	if (!in_array($record['type'], array('SOA', 'CNAME')) && !empty($record['name']))
                if ($errorname = check_hostname_fqdn($record['name'], true, false))
		        $error['name'] = $errorname;

	switch ($record['type'])
	{
		case 'A':
			if (empty($record['ipdst']))
				$error['ipdst'] = trans('Field cannot be empty!');
			else if (!check_ip($record['ipdst']))
				$error['ipdst'] = trans('Invalid IP address!');
		break;
		case 'AAAA':
			if (empty($record['ipdst']))
				$error['ipdst'] = trans('Field cannot be empty!');
			else if (!check_ipv6($record['ipdst']))
				$error['ipdst'] = trans('Invalid IP address!');
		break;
		case 'NS':
                        if ($errorcontent = check_hostname_fqdn($record['ns'], false, true))
                                $error['ns'] = $errorcontent;
    			if (preg_match('/in-addr\.arpa$/', $record['domainname']))
			{
		    		if ($errorcontent = check_hostname_fqdn($record['ns'], false, true))
		            		$error['ns'] = $errorcontent;
			}
		break;
		case 'MX':
			if (empty($record['mailserver']))
				$error['mailserver'] = trans('Field cannot be empty!');
                        else if ($errorcontent = check_hostname_fqdn($record['mailserver'], false, true))
                                $error['mailserver'] = $errorcontent;

			if (empty($record['prio']))
				$error['prio'] = trans('Field cannot be empty!');
			else if (!preg_match('/^[0-9]+$/', $record['prio']))
				$error['prio'] = trans('Invalid format!');
		break;
		case 'CNAME':
            		if ($errorname = check_hostname_fqdn($record['alias'], true, false))
		    		$error['alias'] = $errorname;
		break;
		case 'TXT':
		case 'SPF':
			if (empty($record['desc']))
				$error['desc'] = trans('Field cannot be empty!');
		break;
		case 'PTR':
			if ($errorcontent = check_hostname_fqdn($record['domain'], false, true))
				$error['domain'] = $errorcontent;
		break;
		case 'SOA':
			foreach (array('serial', 'refresh', 'retry', 'expire', 'minttl') as $idx)
			{
				if (empty($record[$idx]))
					$error[$idx] = trans('Field cannot be empty!');
				else if (!preg_match('/^[0-9]+$/', $record[$idx]))
					$error[$idx] = trans('Invalid format!');
			}
		break;
		case 'SSHFP':
			foreach (array('algo', 'ftype', 'fingerprint') as $idx)
			{
				if (empty($record[$idx]))
					$error[$idx] = trans('Field cannot be empty!');
				else if ($idx != 'fingerprint' && !preg_match('/^[0-9]+$/', $record[$idx]))
					$error[$idx] = trans('Invalid format!');
			}
		break;
		case 'HINFO':
			foreach (array('cpu', 'os') as $idx)
			{
				if (empty($record[$idx]))
					$error[$idx] = trans('Field cannot be empty!');
				// @TODO: RFC1010 data format checking
			}
		break;
		case 'SRV':
			foreach (array('port', 'weight') as $idx)
			{
				if (empty($record[$idx]))
					$error[$idx] = trans('Field cannot be empty!');
				else if (!preg_match('/^[0-9]+$/', $record[$idx]))
					$error[$idx] = trans('Invalid format!');
			}
		break;
		default: // NAPTR
			if (empty($record['content']))
				$error['content'] = trans('Field cannot be empty!');
	}

	if ($error)
		return;

	// set 'name' and 'content', 'prio' fields to write into DB
	switch ($record['type'])
	{
		case 'A':
		case 'AAAA':
			$record['content'] = $record['ipdst'];
		break;
		case 'NS':
			$record['content'] = $record['ns'];
		break;
		case 'MX':
			$record['content'] = $record['mailserver'];
		break;
		case 'CNAME':
			$record['name'] = $record['alias'];
			$record['content'] = $record['domain'];
		break;
		case 'TXT':
		case 'SPF':
			$record['content'] = $record['desc'];
		break;
		case 'PTR':
			$record['content'] = $record['domain'];
		break;
		case 'SOA':
			$record['name'] = '';
			$record['content'] = $record['ns'].' '.str_replace('@', '.', $record['email'])
				.' '.$record['serial'].' '.$record['refresh'].' '.$record['retry']
				.' '.$record['expire'].' '.$record['minttl'];
		break;
		case 'SSHFP':
			$record['content'] = $record['algo'].' '.$record['ftype'].' '.$record['fingerprint'];
		break;
		case 'HINFO':
			$record['content'] = $record['cpu'].' '.$record['os'];
		break;
		case 'SRV':
			$record['content'] = $record['weight'].' '.$record['port'].' '.$record['domain'];
		break;
	}

	if ($record['type'] != 'MX')
		$record['prio'] = 0;
}

?>

<?

/*                                                                                                                                                            
 * LMS version 1.11-cvs                                                                                                                                       
 *                                                                                                                                                            
 *  Part of Poweradmin toolkit.inc.php (with small modification by Webvisor Sp. z o.o.),
 *  a friendly web-based admin tool for PowerDNS.
 *  See <https://rejo.zenger.nl/poweradmin> for more details.
 *
 *  Copyright 2007-2009  Rejo Zenger <rejo@zenger.nl>
 *                                                                                                                                                            
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
  "xn--zckzah", "ye", "yt", "yu", "za", "zm", "zw");


function is_not_valid_hostname_fqdn($hostname, $wildcard, $dns_strict_tld_check) {

        global $valid_tlds;        
        $hostname = ereg_replace("\.$","",$hostname);

        if (strlen($hostname) > 255) {            
                return "The hostname is too long";
        }

        $hostname_labels = explode ('.', $hostname);
        $label_count = count($hostname_labels);

        foreach ($hostname_labels as $hostname_label) {
                if ($wildcard == 1 && !isset($first)) {
                        if (!preg_match('/^(\*|[a-zA-Z0-9-\/]+)$/',$hostname_label)) { return "You have invalid characters in your hostname"; }
                        $first = 1;
                } else {
                        if (!preg_match('/^[a-zA-Z0-9-\/]+$/',$hostname_label)) { return "You have invalid characters in your hostname"; }
                }
                if (substr($hostname_label, 0, 1) == "-") { return "A hostname can not start or end with a dash"; }
                if (substr($hostname_label, -1, 1) == "-") { return "A hostname can not start or end with a dash"; }
                if (strlen($hostname_label) < 1 || strlen($hostname_label) > 63) { return "Given hostname or one of the labels is too short or too long"; }
        }

        if ($hostname_labels[$label_count-1] == "arpa" && (substr_count($hostname_labels[0], "/") == 1 XOR substr_count($hostname_labels[1], "/") == 1)) {
                if (substr_count($hostname_labels[0], "/") == 1) {
                        $array = explode ("/", $hostname_labels[0]);
                } else {
                        $array = explode ("/", $hostname_labels[1]);
                }
                if (count($array) != 2) {  return "Invalid hostname" ;}
                if (!is_numeric($array[0]) || $array[0] < 0 || $array[0] > 255) { return "Invalid hostname"; }
                if (!is_numeric($array[1]) || $array[1] < 25 || $array[1] > 31) {  return "Invalid hostname"; }
        } else {
                if (substr_count($hostname, "/") > 0) { return "Given hostname has too many slashes"; }
        }

        if ($dns_strict_tld_check == "1" && !in_array($hostname_labels[$label_count-1], $valid_tlds)) {
               return "You are using an invalid top level domain";
        }

        return false;
}


function update_soa_serial($did)
{
	global $DB;

	$soarecordq = $DB->GetRow("SELECT * from records where domain_id = ? and type='SOA'", array($did));

	$soa = explode(" ", $soarecordq['content']);


	if ($soa[2] == "0") {
                return true;
        } elseif ($soa[2] == date('Ymd') . "99") {
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
                        $revision = "00";
                }

                $serial = $today . str_pad($revision, 2, "0", STR_PAD_LEFT);;

                // Change serial in SOA array.
                $soa[2] = $serial;

                // Build new SOA record content and update the database.
                $content = "";
                for ($i = 0; $i < count($soa); $i++) {
                        $content .= $soa[$i] . " ";
                }
        }

	$DB->Execute('UPDATE records SET content = ? WHERE id = ?',
                array($content, $soarecordq['id']));
}

?>

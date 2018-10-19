#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

ini_set('error_reporting', E_ALL&~E_NOTICE);

$parameters = array(
    'C:' => 'config-file:',
    'q'  => 'quiet',
    'h'  => 'help',
    'v'  => 'version',
    'f'  => 'fetch',
    'l:' => 'list:',
    'u'  => 'update',
    'm'  => 'merge',
    'd'  => 'delete',
    'b'  => 'buildings',
    'o'  => 'only-unique-city-matches',
	'e'  => 'explicit-node-locations',
);

foreach ($parameters as $key => $val) {
    $val = preg_replace('/:/', '', $val);
    $newkey = preg_replace('/:/', '', $key);
    $short_to_longs[$newkey] = $val;
}
$options = getopt(implode('', array_keys($parameters)), $parameters);
foreach ($short_to_longs as $short => $long)
    if (array_key_exists($short, $options)) {
        $options[$long] = $options[$short];
        unset($options[$short]);
    }

if (array_key_exists('version', $options)) {
    print <<<EOF
lms-teryt.php
(C) 2001-2018 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-teryt.php
(C) 2001-2018 LMS Developers

-C, --config-file=/etc/lms/lms.ini alternate config file (default: /etc/lms/lms.ini);
-h, --help                         print this help and exit;
-v, --version                      print version info and exit;
-q, --quiet                        suppress any output, except errors
-f, --fetch                        download teryt files
-u, --update                       update LMS database
-m, --merge                        try join current addresses with teryt locations
-d, --delete                       delete downloaded teryt files after merge/update
-b, --buildings                    analyze building base and load it into database
-l, --list                         state names or ids which will be taken into account
-o, --only-unique-city-matches     update TERYT location only if city matches uniquely
-e, --explicit-node-locations      set explicit TERYT locations for nodes

EOF;
    exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
    print <<<EOF
lms-teryt.php
(C) 2001-2018 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options))
    $CONFIG_FILE = $options['config-file'];
else
    $CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

if (!$quiet)
    echo "Using file ".$CONFIG_FILE." as config." . PHP_EOL;

if (!is_readable($CONFIG_FILE))
    die("Unable to read configuration file [".$CONFIG_FILE."]!" . PHP_EOL);

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);

// Load autoloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/");
}

// Init database

$DB = null;

try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    // can't working without database
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

/* ****************************************
   Good place for config value analysis
   ****************************************/

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$SYSLOG = null;
$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

/* ********************************************************************
   We should have all hard work here which is being done by our script!
   ********************************************************************/

/*!
 * \brief Change array to asociative array.
 *
 * \param  array $row single row to parse
 * \return array       associative array with paremeters
 */
function parse_teryt_building_row($row) {
	static $column_names = array('woj', 'powiat', 'gmina', 'terc', 'miejscowosc',
		'simc', 'ulica', 'ulic', 'building_num', 'longitude', 'latitude');

	if (count($column_names) == count($row)) {
		$result = array_combine($column_names, $row);
		$result['longitude'] = str_replace(',', '.', $result['longitude']);
		$result['latitude'] = str_replace(',', '.', $result['latitude']);
		return $result;
	} else
		return null;
}

/*!
 * \brief Translate XML element to asociative array.
 *
 * \param  XMLReader $xml
 * \return array
 */
function parse_teryt_xml_row($xml) {
	$row = array();
	$node = $xml->expand();
	foreach ($node->childNodes as $childNode) {
		if (empty($childNode->tagName))
			continue;
		$value = trim($childNode->nodeValue);
		$row[strtolower($childNode->tagName)] = empty($value) ? '0' : $value;
	}
	return $row;
}

function getIdentsWithSubcities($subcities, $street, $only_unique_city_matches) {
	$street = trim( preg_replace('/^(ul\.|pl\.|al\.|bulw\.|os\.|wyb\.|plac|skwer|rondo|park|rynek|szosa|droga|ogród|wyspa)/i', '', $street) );

	$DB = LMSDB::getInstance();

	$idents = $DB->GetAll("
		SELECT s.id as streetid, " . $subcities['cityid'] . " AS cityid,
			(" . $DB->Concat('t.name', "' '", '(CASE WHEN s.name2 IS NULL THEN s.name ELSE ' . $DB->Concat('s.name2', "' '", 's.name') . ' END)') . ") AS streetname
		FROM location_streets s
		JOIN location_street_types t ON t.id = s.typeid
		WHERE
			((CASE WHEN s.name2 IS NULL THEN s.name ELSE " . $DB->Concat('s.name2', "' '", 's.name') . " END) ?LIKE? ? OR s.name ?LIKE? ? )
			AND s.cityid IN (" . $subcities['cities'] . ")",
		array($street, $street));

	if (empty($idents))
		return array();
	if (($only_unique_city_matches && count($idents) == 1) || !$only_unique_city_matches)
		return $idents[0];
	else
		return array();
}

/*
 * \brief Find TERYT location for city/street.
 *
 * \param  string $city   city name
 * \param  string $street street name
 * \return array  $ident  LMS location id's
 */
function getIdents( $city = null, $street = null, $only_unique_city_matches = false ) {
    $street = trim( preg_replace('/^(ul\.|pl\.|al\.|bulw\.|os\.|wyb\.|plac|skwer|rondo|park|rynek|szosa|droga|ogród|wyspa)/i', '', $street) );

	$DB = LMSDB::getInstance();

    if ( $city && $street ) {
		$idents = $DB->GetAll("
			SELECT s.id as streetid, s.cityid
				(" . $DB->Concat('t.name', "' '", '(CASE WHEN s.name2 IS NULL THEN s.name ELSE ' . $DB->Concat('s.name2', "' '", 's.name') . ' END)') . ") AS streetname
			FROM location_streets s
			JOIN location_street_types t ON t.id = s.typeid
			JOIN location_cities c ON (s.cityid = c.id)
			WHERE
				((CASE WHEN s.name2 IS NULL THEN s.name ELSE " . $DB->Concat('s.name2', "' '", 's.name') . " END) ?LIKE? ? OR s.name ?LIKE? ? )
				AND c.name ?LIKE? ?
			ORDER BY c.cityid", array($street, $street, $city));

		if (empty($idents))
			return array();
		if (($only_unique_city_matches && count($idents) == 1) || !$only_unique_city_matches)
			return $idents[0];
		else
			return array();
	} elseif ( $city ) {
		$cityids = $DB->GetCol("SELECT id FROM location_cities WHERE name ?LIKE? ?", array($city));
		if (empty($cityids))
			return array();
		if (($only_unique_city_matches && count($cityids) == 1) || !$only_unique_city_matches)
			return array(
				'cityid' => $cityids[0],
			);
		else
			return array();
	} else
		return array();
}

function GetDefaultCustomerTerytAddress($customerid) {
	global $LMS;

	$addresses = $LMS->getCustomerAddresses($customerid);
	if (count($addresses) == 1) {
		$address = reset($addresses);
		if (empty($address['teryt']))
			return null;
		else
			return $address;
	}

	foreach ($addresses as $address)
		if ($address['location_address_type'] == DEFAULT_LOCATION_ADDRESS
			&& !empty($address['teryt']))
			return $address;

	return null;
}

ini_set('memory_limit', '512M');
$stderr = fopen('php://stderr', 'w');

define('PROGRESS_ROW_COUNT', 1000);
define('BUILDING_BASE_ZIP_NAME', 'baza_punktow_adresowych_2017.zip');
define('BUILDING_BASE_ZIP_URL', 'https://form.teleinfrastruktura.gov.pl/help-files/baza_punktow_adresowych_2017.zip');

$only_unique_city_matches = isset($options['only-unique-city-matches']);

$all_states = array(
	'dolnoslaskie' => 2,
	'kujawsko-pomorskie' => 4,
	'lubelskie' => 6,
	'lubuskie' => 8,
	'lodzkie' => 10,
	'malopolskie' => 12,
	'mazowieckie' => 14,
	'opolskie' => 16,
	'podkarpackie' => 18,
	'podlaskie' => 20,
	'pomorskie' => 22,
	'slaskie' => 24,
	'swietokrzyskie' => 26,
	'warminsko-mazurskie' => 28,
	'wielkopolskie' => 30,
	'zachodniopomorskie' => 32,
);

$states = ConfigHelper::getConfig('teryt.state_list', '', true);
$teryt_dir = ConfigHelper::getConfig('teryt.dir', '', true);

$state_lists = array();
if (!empty($states))
	$state_lists[$states] = "Invalid state list format in ini file!";
if (isset($options['list']))
	$state_lists[$options['list']] = "Invalid state list format entered in command line!";
foreach ($state_lists as $states => $error_message) {
	$states = explode(',', $states);
	foreach ($states as &$state) {
		if (preg_match('/^[0-9]+$/', $state))
			continue;
		$state = iconv('UTF-8', 'ASCII//TRANSLIT', $state);
		if (!isset($all_states[$state])) {
			fwrite($stderr,  $error_message . PHP_EOL);
			die;
		}
		$state = $all_states[$state];
	}
	unset($state);
	$state_list = array_combine($states, array_fill(0, count($states), '1'));
}

if (empty($teryt_dir))
	$teryt_dir = getcwd();
else
	if (!is_dir($teryt_dir)) {
		fwrite($stderr, "Output directory specified in ini file does not exist!" . PHP_EOL);
		die;
	}

$building_base_name = $teryt_dir . DIRECTORY_SEPARATOR . 'siis_adresy_2018_v2.csv';

//==============================================================================
// Download required files
//
// -f, --fetch
//==============================================================================

function get_teryt_file($ch, $type, $outfile) {
	static $month_names = array(
		1 => 'stycznia',
		2 => 'lutego',
		3 => 'marca',
		4 => 'kwietnia',
		5 => 'maja',
		6 => 'czerwca',
		7 => 'lipca',
		8 => 'sierpnia',
		9 => 'września',
		10 => 'października',
		11 => 'listopada',
		12 => 'grudnia',
	);
	$date = strftime('%d') . ' ' . $month_names[intval(strftime('%m'))] . ' ' . strftime('%Y');

	$continue = false;
	do {
		curl_setopt_array($ch, array(
			CURLOPT_URL => 'http://eteryt.stat.gov.pl/eTeryt/rejestr_teryt/udostepnianie_danych/baza_teryt/uzytkownicy_indywidualni/pobieranie/pliki_pelne.aspx',
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POSTFIELDS => array(
				'__EVENTTARGET' => 'ctl00$body$B' . $type . 'UrzedowyPobierz',
				'ctl00$body$TBData' => $date,
			),
		));
		$res = curl_exec($ch);
		if (empty($res))
			return false;

		if (strlen($res) < 100000) {
			if (strpos($res, 'body_B' . $type . 'UrzedowyGeneruj') === false)
				return false;
			else {
				curl_setopt_array($ch, array(
					CURLOPT_URL => 'http://eteryt.stat.gov.pl/eTeryt/rejestr_teryt/udostepnianie_danych/baza_teryt/uzytkownicy_indywidualni/pobieranie/pliki_pelne.aspx',
					CURLOPT_POST => true,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_POSTFIELDS => array(
						'__EVENTTARGET' => 'ctl00$body$B' . $type . 'UrzedowyGeneruj',
						'ctl00$body$TBData' => $date,
					),
				));
				$res = curl_exec($ch);
				if (empty($res))
					return false;

				$continue = true;
			}
		}
	} while ($continue);

	$fh = fopen($outfile, 'w');
	fwrite($fh, $res, strlen($res));
	fclose($fh);
	return true;
}

if ( isset($options['fetch']) ) {
	if (!function_exists('curl_init'))
		die('PHP CURL extension required!' . PHP_EOL);

	if (!$quiet) {
		echo 'Downloading TERYT files...' . PHP_EOL;
	}

	$teryt_files = array('ULIC', 'TERC', 'SIMC');

	$ch = curl_init();

	$file_counter = 0;
	$teryt_filename_suffix = '_' . strftime('%d%m%Y');
	foreach ($teryt_files as $file) {
		$res = get_teryt_file($ch, $file, $teryt_dir . DIRECTORY_SEPARATOR . $file . $teryt_filename_suffix . '.zip');
		if ($res)
			$file_counter++;
	}

	curl_close($ch);

    if ( $file_counter != 3 ) {
        fwrite($stderr, 'Error: Downloaded files: ' . $file_counter . '. Three expected.' . PHP_EOL);
        die;
    }

    unset($file_counter);

	if ( ! class_exists('ZipArchive') ) {
		fwrite($stderr, "Error: ZipArchive class not found." . PHP_EOL);
		die;
	}

	//==============================================================================
	// Unzip teryt files
	//==============================================================================
	$zip = new ZipArchive;

	if (!$quiet)
		echo 'Unzipping TERYT files...' . PHP_EOL;

	foreach ( $teryt_files as $file ) {
		$filename = $teryt_dir . DIRECTORY_SEPARATOR . $file . $teryt_filename_suffix . '.zip';
	    if ($zip->open($filename) === TRUE) {
	        $zip->extractTo($teryt_dir . DIRECTORY_SEPARATOR, array($file . '_Urzedowy_' . date('Y-m-d') . '.xml'));
	        rename($teryt_dir . DIRECTORY_SEPARATOR . $file . '_Urzedowy_' . date('Y-m-d') . '.xml',
	        	$teryt_dir . DIRECTORY_SEPARATOR . $file . '.xml');
	    } else {
	        fwrite($stderr, "Error: Can't unzip $file or file doesn't exist." . PHP_EOL);
	        die;
	    }
	}

	unset( $zip, $teryt_files );

	 // download point address base (pobranie bazy punktów adresowych)
	function stream_notification_callback($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max) {
		static $filesize = null;

		switch ($notification_code) {
			case STREAM_NOTIFY_CONNECT:
				$filesize = null;
				break;
			case STREAM_NOTIFY_FILE_SIZE_IS:
				$filesize = $bytes_max;
				break;
			case STREAM_NOTIFY_PROGRESS:
				if (isset($filesize))
					printf("%d%%         \r", ($bytes_transferred * 100) / $filesize);
				break;
		}
	}

	$ctx = stream_context_create();

	if (!$quiet) {
		echo 'Downloading ' . BUILDING_BASE_ZIP_URL . ' file...' . PHP_EOL;
		stream_context_set_params($ctx, array("notification" => "stream_notification_callback"));
	}

	file_put_contents($teryt_dir . DIRECTORY_SEPARATOR . BUILDING_BASE_ZIP_NAME, fopen(BUILDING_BASE_ZIP_URL, 'r', false, $ctx));

	if (!$quiet)
		echo "\rUnzipping " . BUILDING_BASE_ZIP_NAME . ' file...' . PHP_EOL;
	$zip = new ZipArchive;

	if ($zip->open($teryt_dir . DIRECTORY_SEPARATOR . BUILDING_BASE_ZIP_NAME) === TRUE) {
	    $numFiles = $zip->numFiles;

	    if ( $numFiles == 1 ) {
	        $building_base_name = $zip->getNameIndex(0);
	    } else if ( $numFiles > 1 ) {
	        for ($i = 0; $i < $numFiles; ++$i) {
	            if ( preg_match('/siis_adresy/', $v) ) {
	                $building_base_name = $v;
	                break;
	            }
	        }
	    }

	    $zip->extractTo($teryt_dir . DIRECTORY_SEPARATOR);
	    unset( $numFiles );
	} else {
	    fprintf($stderr, "Error: Can't unzip %s or file doesn't exist." . PHP_EOL, BUILDING_BASE_ZIP_NAME);
	    die;
	}

	unset($zip);
}

if ( isset($options['update']) ) {
	//==============================================================================
	// Get current TERC from database
	//==============================================================================

	if (!$quiet)
		echo 'Creating TERC cache' . PHP_EOL;

	$tmp_terc_data = $DB->GetAll("
	    SELECT ident AS woj, '0' AS pow, '0' AS gmi, 0 AS rodz,
	        UPPER(name) AS nazwa, id, 'WOJ' AS type
	    FROM location_states
	    UNION
	    SELECT s.ident AS woj, d.ident AS pow, '0' AS gmi, 0 AS rodz,
	        d.name AS nazwa, d.id, 'POW' AS type
	    FROM location_districts d
	    JOIN location_states s ON (d.stateid = s.id)
	    UNION
	    SELECT s.ident AS woj, d.ident AS pow, b.ident AS gmi, b.type AS rodz,
	        b.name AS nazwa, b.id, 'GMI' AS type
	    FROM location_boroughs b
	    JOIN location_districts d ON (b.districtid = d.id)
	    JOIN location_states s ON (d.stateid = s.id);");

	$terc = array();

	if ( $tmp_terc_data ) {
	    foreach ( $tmp_terc_data as $k=>$v ) {
	        $key = $v['woj'].':'.$v['pow'].':'.$v['gmi'].':'.$v['rodz'];

	        $terc[ $key ] = array(
	            'id'    => $v['id'],
	            'nazwa' => $v['nazwa'],
	            'type'  => $v['type']
	        );

	        unset( $tmp_terc_data[$k] );
	    }
	}

	unset( $tmp_terc_data );

	//==============================================================================
	// Create object to read XML files
	//
	// DOCUMENTATION
	// http://php.net/manual/en/book.xmlreader.php
	//
	// INSTALATION
	// http://php.net/manual/en/xmlreader.setup.php
	//==============================================================================

	if ( ! class_exists('XMLReader') ) {
	    fwrite($stderr, "Error: XMLReader class not found." . PHP_EOL);
	    die;
	}

	$xml = new XMLReader();

	//==============================================================================
	// Read TERC xml file
	//==============================================================================

	if (@$xml->open($teryt_dir . DIRECTORY_SEPARATOR . 'TERC.xml') === false) {
		fwrite($stderr, "Error: can't open TERC.xml file." . PHP_EOL);
		die;
	}

	if (!$quiet)
		echo 'Parsing TERC.xml' . PHP_EOL;

	$i = 0;
	$terc_insert = 0;
	$terc_update = 0;
	$terc_delete = 0;

	while( $xml->read() ) {
	    if ( $xml->nodeType != XMLReader::ELEMENT || $xml->name != 'row' ) {
	        continue;
	    }

		if ( !(++$i % PROGRESS_ROW_COUNT) && !$quiet) {
			echo 'Loaded ' . $i . PHP_EOL;
		}

		$row = parse_teryt_xml_row($xml);

	    if ( isset($state_list) && !isset($state_list[intval($row['woj'])]) ) {
	        continue;
	    }

	    $key  = $row['woj'].':'.$row['pow'].':'.$row['gmi'].':'.$row['rodz'];
	    $data = $terc[$key];

	    // if $row['pow'] is empty then this row contains state
	    if (empty($row['pow'])) {

	        // if state already exists then try update
	        if ( $data ) {
	            if ( $data['nazwa'] != $row['nazwa'] ) {
	                $DB->Execute('UPDATE location_states SET name = ? WHERE id = ?',
	                              array(mb_strtolower($row['nazwa']), $data['id']));

	                ++$terc_update;
	            }

	            $terc[$key]['valid'] = 1;
	        }
	        // else insert new state
	        else {
	            $DB->Execute('INSERT INTO location_states (name,ident) VALUES (?,?)',
	                          array(mb_strtolower($row['nazwa']), $row['woj']));

	            ++$terc_insert;
	            $insertid = $DB->GetLastInsertID('location_states');
	            $terc[$key] = array(
	                'id'    => $insertid,
	                'nazwa' => $row['nazwa'],
	                'type'  => 'WOJ',
	                'valid' => 1
	            );
	        }
	    }
	    // if $row['gmi'] is empty then this row contains district
	    else if (empty($row['gmi'])) {
	        $statekey = $row['woj'] . ':0:0:0';

	        // if district already exists then try update
	        if ( $data ) {
	            if ( $data['nazwa'] != $row['nazwa'] ) {
	                $DB->Execute('UPDATE location_districts SET stateid=?, name=? WHERE id=?',
	                              array($terc[$statekey]['id'], $row['nazwa'], $data['id']));

	                ++$terc_update;
	            }

	            $terc[$key]['valid'] = 1;
	        }
	        // else insert new state
	        else {
	            $DB->Execute('INSERT INTO location_districts (stateid, name, ident) VALUES (?,?,?)',
	                          array($terc[$statekey]['id'], $row['nazwa'], $row['pow']));

	            ++$terc_insert;
	            $insertid = $DB->GetLastInsertID('location_districts');
	            $terc[$key] = array(
	                'id'    => $insertid,
	                'nazwa' => $row['nazwa'],
	                'type'  => 'POW',
	                'valid' => 1
	            );
	        }
	    }
	    // else row contains brough
	    else {
	        $districtkey = $row['woj'] . ':' . $row['pow'] . ':0:0';

	        // if district already exists then try update
	        if ( $data ) {
	            if ( $data['nazwa'] != $row['nazwa'] ) {
	                $DB->Execute('UPDATE location_boroughs SET districtid=?, name=? WHERE id=?',
	                              array($terc[$districtkey]['id'], $row['nazwa'], $data['id']));

	                ++$terc_update;
	            }

	            $terc[$key]['valid'] = 1;
	        }
	        // else insert new state
	        else {
	            $DB->Execute('INSERT INTO location_boroughs (districtid, name, ident, type) VALUES (?,?,?,?)',
	                          array($terc[$districtkey]['id'], $row['nazwa'], $row['gmi'], $row['rodz']));

	            ++$terc_insert;
	            $insertid = $DB->GetLastInsertID('location_boroughs');
	            $terc[$key] = array(
	                'id'    => $insertid,
	                'nazwa' => $row['nazwa'],
	                'type'  => 'GMI',
	                'valid' => 1
	            );
	        }
	    }
	}

	if ( $i % PROGRESS_ROW_COUNT && !$quiet) {
	    echo 'Loaded ' . $i . PHP_EOL;
	}

	foreach ( $terc as $k=>$v ) {
	    if ( $v['valid'] ) {
	        continue;
	    }

	    ++$terc_delete;

	    switch ( strtolower($v['type']) ) {
	        case 'gmi':
	            $DB->Execute('DELETE FROM location_boroughs WHERE id=?', array($v['id']));
	        break;

	        case 'pow':
	            $DB->Execute('DELETE FROM location_districts WHERE id=?', array($v['id']));
	        break;

	        case 'woj':
	            $DB->Execute('DELETE FROM location_states WHERE id=?', array($v['id']));
	        break;

	        default:
	            --$terc_delete;
	    }
	}

	//==============================================================================
	// Print TERC stats
	//==============================================================================

	if (!$quiet) {
		echo 'TERC inserted/updated/deleted = '.$terc_insert.'/'.$terc_update.'/'.$terc_delete . PHP_EOL;
		echo '---' . PHP_EOL;
	}

	unset( $terc_insert, $terc_update, $terc_delete );

	//==============================================================================
	// Get current SIMC from database
	//==============================================================================

	if (!$quiet)
		echo 'Creating SIMC cache' . PHP_EOL;

	$tmp_simc_data = $DB->GetAll("
	    SELECT s.ident AS woj, d.ident AS pow, b.ident AS gmi, b.type AS rodz_gmi,
	        c.ident AS sym, c.name AS nazwa, c.id,
	       (CASE WHEN cc.ident IS NOT NULL THEN cc.ident ELSE c.ident END) AS sympod
	    FROM location_cities c
	        JOIN location_boroughs b ON (c.boroughid = b.id)
	        JOIN location_districts d ON (b.districtid = d.id)
	        JOIN location_states s ON (d.stateid = s.id)
	        LEFT JOIN location_cities cc ON (c.cityid = cc.id)");

	$simc = array();

	if ( $tmp_simc_data ) {
	    foreach ( $tmp_simc_data as $k=>$v ) {
	        $simc[$v['sym']] = array(
	            'id'     => $v['id'],
	            'key'    => $v['woj'].':'.$v['pow'].':'.$v['gmi'].':'.$v['rodz_gmi'],
	            'nazwa'  => $v['nazwa'],
	            'sym'    => $v['sym'],
	            'sympod' => $v['sympod'],
	        );

	        unset( $tmp_simc_data[$k] );
	    }
	}

	unset( $tmp_simc_data );

	//==============================================================================
	// Read SIMC xml file
	//==============================================================================

	if (!$quiet)
		echo 'Parsing SIMC.xml' . PHP_EOL;

	if (@$xml->open($teryt_dir . DIRECTORY_SEPARATOR . 'SIMC.xml') === false) {
		fwrite($stderr, "Error: can't open SIMC.xml file." . PHP_EOL);
		die;
	}

	$i = 0;
	$cities_r    = array();
	$cities      = array();
	$simc_insert = 0;
	$simc_update = 0;
	$simc_delete = 0;

	while( $xml->read() ) {
	    if ( $xml->nodeType != XMLReader::ELEMENT || $xml->name != 'row' ) {
	        continue;
	    }

	    if ( !(++$i % PROGRESS_ROW_COUNT) && !$quiet) {
	        echo 'Loaded ' . $i . PHP_EOL;
	    }

	    $row = parse_teryt_xml_row($xml);

	    if ( isset($state_list) && !isset($state_list[intval($row['woj'])]) ) {
	        continue;
	    }

	    $key   = $row['woj'].':'.$row['pow'].':'.$row['gmi'].':'.$row['rodz_gmi'];
	    $data  = $simc[$row['sym']];
	    $refid = $row['sympod'];
	    $id    = $row['sym'];

	    if ( !$terc[$key] && !$quiet) {
	        echo 'Not recognised TERYT-TERC key: ' . $key . PHP_EOL;
	    }

	    if ( $refid == $id ) {
	        $refid = null;
	    }
	    // refid not found (refered city is below this one), process later
	    else if ( !$simc[$refid] ) {
	        $cities_r[$refid][] = array(
	            'key'   => $key,
	            'nazwa' => $row['nazwa'],
	            'sym'   => $row['sym']
	        );
	    } else {
	        $refid = $simc[$refid]['id'];
	    }

	    // entry exists
	    if ( $data ) {
	        if ( $data['nazwa'] != $row['nazwa'] || $data['sympod'] != $row['sympod'] || $data['key'] != $key ) {
	            $DB->Execute('UPDATE location_cities SET boroughid=?, name=?, cityid=? WHERE id=?',
	                          array($terc[$key]['id'], $row['nazwa'], $refid, $data['id']));

	            ++$simc_update;
	        }

	        // mark data as valid
	        $simc[$id]['valid'] = 1;
	        $cities[$id] = $data['id'];
	    }
	    // add new city
	    else if ( !$refid || $simc[$row['sympod']] ) {
	        $DB->Execute('INSERT INTO location_cities (boroughid, name, cityid, ident) VALUES (?,?,?,?)',
	                      array($terc[$key]['id'], $row['nazwa'], $refid, $id));

	        ++$simc_insert;
	        $insertid = $DB->GetLastInsertID('location_cities');

	        $simc[$id] = array(
	             'key'    => $key,
	             'nazwa'  => $row['nazwa'],
	             'sym'    => $id,
	             'sympod' => $refid,
	             'id'     => $insertid,
	             'valid'  => 1,
	        );

	        $cities[$row['sym']] = $insertid;
	    }

	    // process references
	    if ( isset($cities_r[$id]) ) {
	        while ( $elem = array_pop($cities_r[$id]) ) {
	            $rid  = $elem['sym'];
	            $data = $simc[$rid];

	            // entry exists
	            if ( $data ) {
	                if ( $data['nazwa'] != $elem['nazwa'] || $data['sympod'] != $id || $data['key'] != $key ) {
	                    $DB->Execute('UPDATE location_cities SET boroughid=?, name=?, cityid=? WHERE id=?',
	                                  array($terc[$key]['id'], $elem['nazwa'], $cities[$id], $data['id']));

	                    ++$simc_update;
	                }

	                // mark data as valid
	                $simc[$rid]['valid'] = 1;
	                $cities[$rid] = $rid;
	            }
	            // add new city
	            else {
	                $DB->Execute('INSERT INTO location_cities (boroughid, name, cityid, ident) VALUES (?,?,?,?)',
	                              array($terc[$key]['id'], $elem['nazwa'], $cities[$id], $rid));

	                ++$simc_insert;
	                $insertid = $DB->GetLastInsertID('location_cities');
	                $cities[$rid] = $insertid;
	            }
	        }
	    }
	}

	if ( $i % PROGRESS_ROW_COUNT && !$quiet) {
	    echo 'Loaded ' . $i . PHP_EOL;
	}

	foreach ( $simc as $k=>$v ) {
	    if ( !$v['valid'] ) {
	        $DB->Execute('DELETE FROM location_cities WHERE id=?', array($v['id']));
	        ++$simc_delete;
	    }
	}

	unset( $terc, $simc, $cities_r );

	//==============================================================================
	// Print SIMC stats
	//==============================================================================

	if (!$quiet) {
		echo 'SIMC inserted/updated/deleted = '.$simc_insert.'/'.$simc_update.'/'.$simc_delete . PHP_EOL;
		echo '---' . PHP_EOL;
	}

	unset( $simc_insert, $simc_update, $simc_delete );

	//==============================================================================
	// Get current ULIC from database
	//==============================================================================

	$str_types = $DB->GetAllByKey('SELECT id, name FROM location_street_types', 'name');

	$tmp_ulic = $DB->GetAll("
	    SELECT s.id, s.ident, s.name, s.name2, s.typeid, c.ident AS city
	    FROM location_streets s
	    JOIN location_cities c ON (s.cityid = c.id)");

	$ulic = array();

	if ( $tmp_ulic ) {
	    foreach ( $tmp_ulic as $k=>$v ) {
	        $ulic[$v['ident'] . ':' . $v['city']] = array(
	            'id'     => $v['id'],
	            'nazwa'  => $v['name'],
	            'nazwa2' => $v['name2'],
	            'typeid' => $v['typeid']
	        );
	    }
	}

	unset( $tmp_ulic );

	//==============================================================================
	// Read ULIC xml file
	//==============================================================================

	if (!$quiet)
		echo 'Parsing ULIC.xml' . PHP_EOL;

	if (@$xml->open($teryt_dir . DIRECTORY_SEPARATOR . 'ULIC.xml') === false) {
		fwrite($stderr, "Error: can't open ULIC.xml file." . PHP_EOL);
		die;
	}

	$i = 0;
	$ulic_insert = 0;
	$ulic_update = 0;
	$ulic_delete = 0;

	while( $xml->read() ) {
	    if ( $xml->nodeType != XMLReader::ELEMENT || $xml->name != 'row' ) {
	        continue;
	    }

	    if ( !(++$i % PROGRESS_ROW_COUNT) && !$quiet) {
	        echo 'Loaded ' . $i . PHP_EOL;
	    }

	    $row = parse_teryt_xml_row($xml);

	    if ( isset($state_list) && !isset($state_list[intval($row['woj'])]) || !isset($row['nazwa_1']) ) {
	        continue;
	    }

        $row['nazwa_1'] = trim($row['nazwa_1']);
        $row['nazwa_2'] = trim($row['nazwa_2']);
	    $key    = $row['sym_ul'].':'.$row['sym'];
	    $data   = $ulic[$key];
		$row['cecha'] = mb_strtolower($row['cecha']);

		if ( isset($str_types[$row['cecha']]) )
			$typeid = intval( $str_types[$row['cecha']]['id'] );
		else {
	         $DB->Execute('INSERT INTO location_street_types (name) VALUES (?)',
	                       array( $row['cecha'] ));

			$typeid = $DB->GetLastInsertID('location_street_types');
			$str_types[$row['cecha']] = array(
				'id' => $typeid,
				'name' => $row['cecha'],
			);
	    }

	    // entry exists
	    if ( $data ) {
	        if ( $data['nazwa'] != $row['nazwa_1'] || (isset($row['nazwa_2']) && (!isset($data['nazwa2']) || $data['nazwa2'] != $row['nazwa_2'])) || $data['typeid'] != $typeid ) {
	            $DB->Execute('UPDATE location_streets
	                          SET cityid = ?, name = ?, name2 = ?, typeid = ?
	                          WHERE id = ?',
	                          array($cities[$row['sym']], $row['nazwa_1'], empty($row['nazwa_2']) ? null : $row['nazwa_2'], $typeid, $data['id']));

	            ++$ulic_update;
	        }

	        // mark data as valid
	        $ulic[$key]['valid'] = 1;
	    }
	    // add new street
	    else {
	        $DB->Execute('INSERT INTO location_streets (cityid, name, name2, typeid, ident) VALUES (?,?,?,?,?)',
	                      array($cities[$row['sym']], $row['nazwa_1'], empty($row['nazwa_2']) ? null : $row['nazwa_2'], $typeid, $row['sym_ul']));

	        ++$ulic_insert;
	    }
	}

	if ( $i % PROGRESS_ROW_COUNT && !$quiet) {
	    echo 'Loaded ' . $i . PHP_EOL;
	}

	foreach ( $ulic as $k=>$v ) {
	    if ( !$v['valid'] ) {
	        $DB->Execute('DELETE FROM location_streets WHERE id=?', array($v['id']));
	    }
	}

	//==============================================================================
	// Print ULIC stats
	//==============================================================================

	if (!$quiet) {
		echo 'ULIC inserted/updated/deleted = '.$ulic_insert.'/'.$ulic_update.'/'.$ulic_delete . PHP_EOL;
		echo '---' . PHP_EOL;
	}

	unset( $ulic, $str_types, $cities, $ulic_insert, $ulic_update, $ulic_delete );

	$xml->close();
	unset( $xml );

} // close if ( isset($option['update']) )

//==============================================================================
// Read address point csv file
//
// -b, --buildings
//==============================================================================

if ( isset($options['buildings']) ) {

	$fh = fopen($building_base_name, "r");
	if ($fh === null) {
		fprintf($stderr, "Error: can't open %s file." . PHP_EOL, $building_base_name);
		die;
	}

	if ( isset($state_list) ) {
		$state_name_to_ident = $DB->GetAllByKey('SELECT ident, name FROM location_states', 'name');

		foreach ( $state_name_to_ident as $k=>$v ) {
			$state_name_to_ident[ mb_strtoupper($k) ] = $v['ident'];
		}
	}

	$steps = ceil( filesize($building_base_name) / 4096 );
	$i = 0;
	$to_update = array();
	$to_insert = array();
	$previous_line = '';

    // create location cache
	$location_cache = new LocationCache(LocationCache::LOAD_FULL);

	if (!$quiet)
		echo 'Parsing file...' . PHP_EOL;

	while (!feof($fh)) {
		$lines = preg_split('/\r?\n/', fread($fh, 4096));
		$lines = str_replace("'", '', $lines);

		// try to join previous line
		if ( substr_count($lines[0], ';') == 11 && substr_count($previous_line, ';') == 11 ) {
			array_unshift($lines, $previous_line);
		} else {
			$lines[0] = $previous_line . $lines[0];
		}

		end($lines);
		$k = key($lines);
		$previous_line = $lines[ $k ];
		unset($lines[ $k ]);

		// insert loaded data to database
		foreach ($lines as $line) {
			$l = str_getcsv($line, ';');
			if (empty($l)) {
				fwrite($stderr, 'error: can\'t parse row '. $line . PHP_EOL);
				continue;
			}

			$v = parse_teryt_building_row($l);

			if (empty($v)) {
				fwrite($stderr, 'error: can\'t parse row '. $line . PHP_EOL);
				continue;
			}

			if ($v['id'] == 'ID')
				continue;

			if ( isset($state_list) ) {
				$state_ident = $state_name_to_ident[$v['woj']];

				if ( !isset($state_list[intval($state_ident)]) ) {
				continue;
				}
			}

			if ( !preg_match('#^[0-9a-zA-Z-, /łŁ]*$#', $v['building_num']) ) {
				fwrite($stderr, 'warning: house number contains incorrect characters in row ' . $line . PHP_EOL);
				continue;
			}

			$terc = $v['terc'];
			$simc = $v['simc'];
			$ulic = $v['ulic'];

			$city = $location_cache->getCityByIdent($terc, $simc);

			if ( !$city ) {
				fwrite($stderr, 'warning: teryt terc ' . $terc . ', simc ' . $simc . ' wasn\'t found in database in row ' . $line . PHP_EOL);
				continue;
			}

			if ($ulic == '99999')
				$street = array('id' => '0');
			else {
				$street = $location_cache->getStreetByIdent( $city['id'], $ulic );
				if (empty($street)) {
					fwrite($stderr, 'warning: teryt terc ' . $terc . ', simc ' . $simc . ', ulic ' . $ulic . ' wasn\'t found in database in row ' . $line . PHP_EOL);
					continue;
				}
			}
			$building = $location_cache->buildingExists( $city['id'], $street['id'], $v['building_num'] );

			if ( $building ) {
				$fields_to_update = array();

				if ( $building['latitude'] != $v['latitude'] ) {
					$fields_to_update[] = 'latitude = ' . ($v['latitude'] ? $v['latitude'] : 'null');
				}

				if ( $building['longitude'] != $v['longitude'] ) {
					$fields_to_update[] = 'longitude = ' . ($v['longitude'] ? $v['longitude'] : 'null');
				}

				if ($fields_to_update) {
					$DB->Execute('UPDATE location_buildings SET updated = 1, '.implode(',', $fields_to_update).'WHERE id = '.$building['id']);
				} else {
					$to_update[] = $building['id'];
				}
			} else {
				$data = array();
				$data[] = $city['id'];
				$data[] = $street['id']      ? $street['id']              : 'null';
				$data[] = $v['building_num'] ? "'".$v['building_num']."'" : 'null';
				$data[] = $v['latitude']     ? $v['latitude']             : 'null';
				$data[] = $v['longitude']    ? $v['longitude']            : 'null';
				$data[] = 1;

				$to_insert[] = '('.implode(',', $data).')';
			}
		}

		if ( $to_insert ) {
			$DB->Execute('INSERT INTO location_buildings (city_id,street_id,building_num,latitude,longitude,updated) VALUES '.implode(',', $to_insert));
			$to_insert = array();
		}

		if ( $to_update ) {
			$DB->Execute('UPDATE location_buildings SET updated = 1 WHERE id in ('.implode(',', $to_update).')');
			$to_update = array();
		}

		// location building database creation progress
		if (!$quiet)
			printf("%.2f%%\r", ($i * 100) / $steps);
		$i++;
	}

	echo "\r";

	if (!$quiet)
		echo 'Removing old buildings...' . PHP_EOL;

	$DB->Execute('DELETE FROM location_buildings WHERE updated = 0');
	$DB->Execute('UPDATE location_buildings SET updated = 0');

	fclose($fh);
	unset(
		$to_insert, $to_update, $data, $location_cache, $fields_to_update, $i,
		$street, $building, $simc, $city, $previous_line, $lines, $steps,
		$state_name_to_ident, $state_ident
	);

} // close if ( isset($options['buildings']) ) {

//==============================================================================
// Merge TERYT with LMS database.
// Find addresses without city_id and street_id and try match TERYT location.
//
// -m --merge
//==============================================================================

if ( isset($options['merge']) ) {
	if (!$quiet)
		echo 'Merging TERYT with LMS database...' . PHP_EOL;
    $updated = 0;

	$addresses = $DB->GetAll("SELECT a.id, a.city, a.street
		FROM addresses a
		LEFT JOIN documents d ON (d.recipient_address_id = a.id OR d.post_address_id = a.id)
		WHERE a.city IS NOT NULL
			AND d.id IS NULL
			AND (a.city_id IS NULL OR (a.street IS NOT NULL AND a.street_id IS NULL))");

    if ( !$addresses ) {
        $addresses = array();
    }

    $location_cache = array();

	$cities_with_sections = $LMS->GetCitiesWithSections();

	foreach ( $addresses as $a ) {
		$city = empty($a['city']) ? '-' : $a['city'];
		$street = empty($a['street']) ? '-' : $a['street'];

		if (!$quiet)
			printf("City '%s', Street: '%s': ", $city, $street);

		$city = mb_strtolower($city);
		$street = mb_strtolower($street);
		$key = $city . ':' . $street;

		if ( isset($location_cache[$key]) ) {
			$idents = $location_cache[$key];
		} else {
			if (isset($cities_with_sections[$city]) && $city != '-' && $street != '-')
				$idents = getIdentsWithSubcities($cities_with_sections[$city], $street, $only_unique_city_matches);
			else
				$idents = getIdents($city == '-' ? null : $city, $street == '-' ? null : $street, $only_unique_city_matches );
			$location_cache[$key] = $idents;
		}

		if (empty($idents)) {
			if (!$quiet)
				echo 'not found' . PHP_EOL;
			continue;
		}

		if (!$quiet)
			echo 'found' . PHP_EOL;

		$DB->Execute("UPDATE addresses SET city_id = ?, street_id = ?, street_name = ? WHERE id = ?",
			array($idents['cityid'], $idents['streetid'], $idents['streetname'], $a['id']));

		$updated++;
	}

	if (!$quiet)
		echo 'Matched TERYT addresses: ' . $updated . PHP_EOL;
    unset( $addresses, $updated, $location_cache );
}

//==============================================================================
// Determine explicit node TERYT locations
//
// -e --explicit-node-locations
//==============================================================================

if ( isset($options['explicit-node-locations']) ) {
	if (!$quiet)
		echo 'Determining explicit TERYT node locations...' . PHP_EOL;

	$nodes = $DB->GetAll('SELECT id, ownerid FROM nodes
		WHERE ownerid IS NOT NULL AND address_id IS NULL');
	if (!empty($nodes))
		foreach ($nodes as $node) {
			$address = GetDefaultCustomerTerytAddress($node['ownerid']);
			if (empty($address))
				continue;

			if (!$quiet)
				printf('Setting explicit TERYT location address for node: %d (city: %s, street: %s, house: %s, flat: %s)' . PHP_EOL,
					$node['id'], $address['location_city_name'], $address['location_street_name'],
					$address['location_house'], $address['location_flat']);

			$DB->Execute('UPDATE nodes SET address_id = ? WHERE id = ?',
				array($address['address_id'], $node['id']));
		}
}

//==============================================================================
// Delete downloaded files
//
// -d --delete
//==============================================================================

if ( isset($options['delete']) ) {
	if (!$quiet)
		echo 'Deleting downloaded files...' . PHP_EOL;

    if ( !empty($building_base_name) && file_exists($building_base_name)) {
        unlink($building_base_name);
    }

    unlink($teryt_dir . DIRECTORY_SEPARATOR . BUILDING_BASE_ZIP_NAME);
    unlink($teryt_dir . DIRECTORY_SEPARATOR . 'SIMC.xml');
    unlink($teryt_dir . DIRECTORY_SEPARATOR . 'ULIC.xml');
    unlink($teryt_dir . DIRECTORY_SEPARATOR . 'TERC.xml');
}

fclose($stderr);

?>

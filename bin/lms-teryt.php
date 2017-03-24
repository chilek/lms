#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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
    'b'  => 'basepoint'
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
(C) 2001-2017 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-teryt.php
(C) 2001-2017 LMS Developers

-C, --config-file=/etc/lms/lms.ini alternate config file (default: /etc/lms/lms.ini);
-h, --help                         print this help and exit;
-v, --version                      print version info and exit;
-q, --quiet                        suppress any output, except errors
-f, --fetch                        download teryt files
-u, --update                       update LMS database
-m, --merge                        try join current addresses with teryt locations
-d, --delete                       delete downloaded teryt files after merge/update
-l, --list                         state ids who will be taken into account

EOF;
    exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
    print <<<EOF
lms-teryt.php
(C) 2001-2017 LMS Developers

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

$SYSLOG = SYSLOG::getInstance();

/* ********************************************************************
   We should have all hard work here which is being done by our script!
   ********************************************************************/

/*!
 * \brief Change text to asociative array.
 *
 * \param  string $row single row to parse
 * \return array       associative array with paremeters
 */
function parse_teryt_building_row( $row ) {
    $pattern = '(?<id>.*);(?<woj>.*);(?<powiat>.*);(?<gmina>.*);' .
               '(?<terc>.*);(?<miejscowosc>.*);(?<simc>.*);' .
               '(?<ulica>.*);(?<ulic>.*);(?<building_num>.*);' .
               '(?<longitude>.*);(?<latitude>.*)';

    $row = str_replace("\r", '', $row);
    preg_match('/^'.$pattern.'$/', $row, $matches);

    foreach ( $matches as $k=>$v ) {
        if ( is_numeric($k) ) {
            unset( $matches[$k] );
        }
    }

    return $matches;
}

/*!
 * \brief Translate XML element to asociative array.
 *
 * \param  string $xml_string
 * \return array
 */
function parse_teryt_xml_row( $xml_string ) {
    $row = array();
    $tmp = explode( "\n", trim($xml_string) );

    foreach ( $tmp as $col ) {
        if ( preg_match('/^<col name="(?<key>[_a-zA-Z0-9]+)"\/?>((?<val>[^<]+)<\/col>)?/', $col, $matches) ) {
            if ( in_array( $matches['key'], array('WOJ','POW','GMI','RODZ','RODZ_GMI','SYM','SYMPOD','SYM_UL') ) ) {
                $matches['val'] = intval($matches['val']);
            }

            $row[ strtolower($matches['key']) ] = $matches['val'];
        }
    }

    return $row;
}

/*
 * \brief Find TERYT location for city/street.
 *
 * \param  string $city   city name
 * \param  string $street street name
 * \return array  $ident  LMS location id's
 */
function getIdents( $city = null, $street = null ) {
    $street = trim( preg_replace('/$(ul\.|pl\.|al\.|bulw\.|os\.|wyb\.|plac|skwer|rondo|park|rynek|szosa|droga|ogród|wyspa)/i', '', $street) );
    $idents = array();

    global $DB;

    if ( $city && $street ) {
        switch ( strtolower($DB->GetDbType()) ) {
            case 'postgres':
                $condition = "CASE WHEN s.name2 IS NULL THEN s.name ELSE s.name2 || ' ' || s.name END";
            break;

            case 'mysql':
                $condition = "CASE WHEN s.name2 IS NULL THEN s.name ELSE CONCAT(s.name2, ' ', s.name) END";
            break;

            default:
                return array();
        }

        $idents = $DB->GetRow("
            SELECT s.id as streetid, s.cityid
            FROM location_streets s
                JOIN location_cities c ON (s.cityid = c.id)
            WHERE
                (" . $condition . " ?LIKE? ? OR s.name ?LIKE? ? ) AND
                c.name ?LIKE? ?
            ORDER BY c.cityid", array($street, $street, $city));

    } else if ( $city ) {
        $idents['cityid'] = $DB->GetOne('SELECT name FROM location_cities WHERE name ?LIKE? ?;', array($city));
    }

    if ( isset($idents['cityid']) && !is_numeric($idents['cityid']) ) {
        $idents['cityid'] = null;
    }

    if ( isset($idents['streetid']) && !is_numeric($idents['streetid']) ) {
        $idents['streetid'] = null;
    }

    return $idents;
}

ini_set('memory_limit', '512M');
$stderr = fopen('php://stderr', 'w');

define('PROGRESS_ROW_COUNT', 1000);
define('BASEPOINT_ZIP_NAME', 'baza_punktow_adresowych_2016.zip');
define('BASEPOINT_ZIP_URL', 'https://form.teleinfrastruktura.gov.pl/help-files/baza_punktow_adresowych_2016.zip');
$basepoint_name = 'baza_punktow_adresowych_2016.csv';

$states = ConfigHelper::getConfig('teryt.state_list', '', true);
$teryt_dir = ConfigHelper::getConfig('teryt.dir', '', true);
if (!empty($states)) {
	if (!preg_match('/^([0-9]+,?)+$/', $states)) {
		fwrite($stderr, "Invalid state list format in ini file!" . PHP_EOL);
		die;
	}
	$states = explode(',', $states);
	$state_list = array_combine($states, array_fill(0, count($states), '1'));
}

if ( isset($options['list']) ) {
	if (!preg_match('/^([0-9]+,?)+$/', $options['list'])) {
		fwrite($stderr, "Invalid state list format entered in command line!" . PHP_EOL);
		die;
	}
	$states = explode(',', $options['list']);
	$state_list = array_combine($states, array_fill(0, count($states), '1'));
}

if (empty($teryt_dir))
	$teryt_dir = getcwd();
else
	if (!is_dir($teryt_dir)) {
		fwrite($stderr, "Output directory specified in ini file does not exist!" . PHP_EOL);
		die;
	}

//==============================================================================
// Download required files
//
// -f, --fetch
//==============================================================================

if ( isset($options['fetch']) ) {
	if (!$quiet)
		echo 'Downloading TERYT files...' . PHP_EOL;

    $page_content = file_get_contents('http://www.stat.gov.pl/broker/access/prefile/listPreFiles.jspa', 'r');
    $file_counter = 0;
	$teryt_filename_suffix = '_' . strftime('%d%m%Y');

    foreach (preg_split("/((\r?\n)|(\r\n?))/", $page_content) as $line){
        if ( preg_match('/downloadPreFile\.jspa;.*id=(?<file_id>[0-9]*)"/', $line, $matches) && $matches['file_id'] ) {

            $headers = get_headers('http://www.stat.gov.pl/broker/access/prefile/downloadPreFile.jspa?id=' . $matches['file_id']);

            foreach ($headers as $tag) {
                if ( preg_match('/filename=(?<full_name>(?<name>.*)_.*)/', $tag, $file) ) {
                    switch ( strtolower($file['name']) ) {
                        case 'ulic':
                        case 'terc':
                        case 'simc':
                            // inserase counter
                            ++$file_counter;

                            // save file
                            file_put_contents($teryt_dir . DIRECTORY_SEPARATOR . $file['name'] . $teryt_filename_suffix . '.zip',
                                fopen('http://www.stat.gov.pl/broker/access/prefile/downloadPreFile.jspa?id=' . $matches['file_id'], 'r'));
                        break;
                    }
                }
            }
        }
    }

    if ( $file_counter != 3 ) {
        fwrite($stderr, 'Error: Downloaded files: ' . $file_counter . '. Three expected.' . PHP_EOL);
        die;
    }

    unset( $page_content, $file_counter, $headers, $matches, $file );

	if ( ! class_exists('ZipArchive') ) {
		fwrite($stderr, "Error: ZipArchive class not found." . PHP_EOL);
		die;
	}

	//==============================================================================
	// Unzip teryt files
	//==============================================================================
	$zip = new ZipArchive;
	$teryt_files = array('ULIC', 'TERC', 'SIMC');

	if (!$quiet)
		echo 'Unzipping TERYT files...' . PHP_EOL;

	foreach ( $teryt_files as $file ) {
		$file = $teryt_dir . DIRECTORY_SEPARATOR . $file . $teryt_filename_suffix . '.zip';
	    if ($zip->open($file) === TRUE) {
	        $zip->extractTo($teryt_dir . DIRECTORY_SEPARATOR);
	    } else {
	        fwrite($stderr, "Error: Can't unzip $file or file doesn't exist." . PHP_EOL);
	        die;
	    }
	}

	unset( $zip, $teryt_files );

	 // download point address base (pobranie bazy punktów adresowych)
	if (!$quiet)
		echo 'Downloading ' . BASEPOINT_ZIP_URL . ' file...' . PHP_EOL;
	file_put_contents($teryt_dir . DIRECTORY_SEPARATOR . BASEPOINT_ZIP_NAME, fopen(BASEPOINT_ZIP_URL, 'r'));

	if (!$quiet)
		echo 'Unzipping ' . BASEPOINT_ZIP_NAME . ' file...' . PHP_EOL;
	$zip = new ZipArchive;

	if ($zip->open($teryt_dir . DIRECTORY_SEPARATOR . BASEPOINT_ZIP_NAME) === TRUE) {
	    $numFiles = $zip->numFiles;

	    if ( $numFiles == 1 ) {
	        $basepoint_name = $zip->getNameIndex(0);
	    } else if ( $numFiles > 1 ) {
	        for ($i = 0; $i < $numFiles; ++$i) {
	            if ( preg_match('/baza_punktow_adresowych/', $v) ) {
	                $basepoint_name = $v;
	                break;
	            }
	        }
	    }

	    $zip->extractTo($teryt_dir . DIRECTORY_SEPARATOR);
	    unset( $numFiles );
	} else {
	    fprintf($stderr, "Error: Can't unzip %s or file doesn't exist." . PHP_EOL, BASEPOINT_ZIP_NAME);
	    die;
	}
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

	if ($xml->open($teryt_dir . DIRECTORY_SEPARATOR . 'TERC.xml') === false) {
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

	    $row = parse_teryt_xml_row( $xml->readInnerXML() );

	    if ( isset($state_list) && !isset($state_list[$row['woj']]) ) {
	        continue;
	    }

	    $key  = $row['woj'].':'.$row['pow'].':'.$row['gmi'].':'.$row['rodz'];
	    $data = $terc[$key];

	    // if $row['pow'] is empty then this row contains state
	    if ( !$row['pow'] ) {

	        // if state already exists then try update
	        if ( $data ) {
	            if ( $data['nazwa'] != $row['nazwa'] ) {
	                $DB->Execute('UPDATE location_states SET name = ? WHERE id = ?;',
	                              array(strtolower($row['nazwa']), $data['id']));

	                ++$terc_update;
	            }

	            $terc[$key]['valid'] = 1;
	        }
	        // else insert new state
	        else {
	            $DB->Execute('INSERT INTO location_states (name,ident) VALUES (?,?);',
	                          array(strtolower($row['nazwa']), $row['woj']));

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
	    else if ( !$row['gmi'] ) {
	        $statekey = $row['woj'] . ':0:0:0';

	        // if district already exists then try update
	        if ( $data ) {
	            if ( $data['nazwa'] != $row['nazwa'] ) {
	                $DB->Execute('UPDATE location_districts SET stateid=?, name=? WHERE id=?;',
	                              array($terc[$statekey]['id'], $row['nazwa'], $data['id']));

	                ++$terc_update;
	            }

	            $terc[$key]['valid'] = 1;
	        }
	        // else insert new state
	        else {
	            $DB->Execute('INSERT INTO location_districts (stateid, name, ident) VALUES (?,?,?);',
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
	                $DB->Execute('UPDATE location_boroughs SET districtid=?, name=? WHERE id=?;',
	                              array($terc[$districtkey]['id'], $row['nazwa'], $data['id']));

	                ++$terc_update;
	            }

	            $terc[$key]['valid'] = 1;
	        }
	        // else insert new state
	        else {
	            $DB->Execute('INSERT INTO location_boroughs (districtid, name, ident, type) VALUES (?,?,?,?);',
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
	            $DB->Execute('DELETE FROM location_boroughs WHERE id=?;', array($v['id']));
	        break;

	        case 'pow':
	            $DB->Execute('DELETE FROM location_districts WHERE id=?;', array($v['id']));
	        break;

	        case 'woj':
	            $DB->Execute('DELETE FROM location_states WHERE id=?;', array($v['id']));
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

	if ($xml->open($teryt_dir . DIRECTORY_SEPARATOR . 'SIMC.xml') === false) {
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

	    $row = parse_teryt_xml_row( $xml->readInnerXML() );

	    if ( isset($state_list) && !isset($state_list[$row['woj']]) ) {
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
	        $DB->Execute('DELETE FROM location_cities WHERE id=?;', array($v['id']));
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

	if ($xml->open($teryt_dir . DIRECTORY_SEPARATOR . 'ULIC.xml') === false) {
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

	    $row = parse_teryt_xml_row( $xml->readInnerXML() );

	    if ( isset($state_list) && !isset($state_list[$row['woj']]) || !isset($row['nazwa_1']) ) {
	        continue;
	    }

	    $key    = $row['sym_ul'].':'.$row['sym'];
	    $data   = $ulic[$key];
	    $typeid = intval( $str_types[$row['cecha']] );

	    if ( !$str_types[$row['cecha']] ) {
	         $DB->Execute('INSERT INTO location_street_types (name) VALUES (?);',
	                       array( strtolower($row['cecha']) ));

	         $insertid = $DB->GetLastInsertID('location_street_types');
	         $str_types[$row['cecha']] = $typeid = $insertid;
	         unset($insertid);
	         ++$ulic_insert;
	    }

	    // entry exists
	    if ( $data ) {
	        if ( $data['nazwa'] != $row['nazwa_1'] || (isset($row['nazwa_2']) && (!isset($data['nazwa2']) || $data['nazwa2'] != $row['nazwa_2'])) || $data['typeid'] != $typeid ) {
	            $DB->Execute('UPDATE location_streets
	                          SET cityid = ?, name = ?, name2 = ?, typeid = ?
	                          WHERE id = ?',
	                          array($cities[$row['sym']], $row['nazwa_1'], $row['nazwa_2'], $typeid, $data['id']));

	            ++$ulic_update;
	        }

	        // mark data as valid
	        $ulic[$key]['valid'] = 1;
	    }
	    // add new street
	    else {
	        $DB->Execute('INSERT INTO location_streets (cityid, name, name2, typeid, ident) VALUES (?,?,?,?,?)',
	                      array($cities[$row['sym']], $row['nazwa_1'], $row['nazwa_2'], $typeid, $row['sym_ul']));

	        ++$ulic_insert;
	    }
	}

	if ( $i % PROGRESS_ROW_COUNT && !$quiet) {
	    echo 'Loaded ' . $i . PHP_EOL;
	}

	foreach ( $ulic as $k=>$v ) {
	    if ( !$v['valid'] ) {
	        $DB->Execute('DELETE FROM location_streets WHERE id=?;', array($v['id']));
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
// -b, --basepoint
//==============================================================================

if ( isset($options['basepoint']) ) {
	$fh = fopen($basepoint_name, "r");
	if ($fh === null) {
		fprintf($stderr, "Error: can't open %s file." . PHP_EOL, $basepoint_name);
		die;
	}

    if ( isset($state_list) ) {
        $state_name_to_ident = $DB->GetAllByKey('SELECT ident, name FROM location_states', 'name');

        foreach ( $state_name_to_ident as $k=>$v ) {
            $state_name_to_ident[ strtoupper($k) ] = $v['ident'];
        }
    }

    $steps = ceil( filesize($basepoint_name) / 4096 );
    $i = 1;
    $to_update = array();
    $to_insert = array();
    $previous_line = '';

    // create location cache
    $location_cache = new LocationCache('full');

	if (!$quiet)
		echo 'Parsing file' . PHP_EOL;

    while ( !feof($fh) ) {
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
        foreach ( $lines as $k=>$l ) {
            $v = parse_teryt_building_row( $l );

            if ( isset($state_list) ) {
                $state_ident = $state_name_to_ident[$v['woj']];

                if ( !isset($state_list[$state_ident]) ) {
                    continue;
                }
            }

            if ( !$v ) {
                fwrite($stderr, 'error: can\'t parse row '.$l . PHP_EOL);
                continue;
            }

            if ( !preg_match('/^[0-9a-zA-Z \/łŁ]*$/', $v['building_num']) ) {
                fwrite($stderr, 'warning: house number contains incorrect characters in row '.$l . PHP_EOL);
                continue;
            }

            $v['simc'] = ltrim($v['simc'], '0');
            $simc      = $v['simc'];
            $city      = $location_cache->getCityByIdent( $simc );

            if ( !$city ) {
                fwrite($stderr, 'warning: teryt city id '.$v['simc'].' was\'t found in database' . PHP_EOL);
                continue;
            }

            $street   = $location_cache->getStreetByIdent( $city['id'], ltrim($v['ulic'],'0') );
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
            $DB->Execute('INSERT INTO location_buildings (city_id,street_id,building_num,latitude,longitude,updated) VALUES '.implode(',', $to_insert).';');
            $to_insert = array();
        }

        if ( $to_update ) {
            $DB->Execute('UPDATE location_buildings SET updated = 1 WHERE id in ('.implode(',', $to_update).')');
            $to_update = array();
        }

        // location buildings progres
		if (!$quiet)
			echo "$i/$steps" . PHP_EOL;
        ++$i;
    }

	if (!$quiet)
		echo 'Removing old buildings' . PHP_EOL;

    $DB->Execute('DELETE FROM location_buildings WHERE updated = 0;');
    $DB->Execute('UPDATE location_buildings SET updated = 0;');

    fclose($fh);
    unset(
        $to_insert, $to_update, $data, $location_cache, $fields_to_update, $i,
        $street, $building, $simc, $city, $k, $previous_line, $lines, $steps,
        $state_name_to_ident, $state_ident
    );

} // close if ( isset($options['basepoint']) ) {

//==============================================================================
// Merge TERYT with LMS database.
// Find addresses without city_id and street_id and try match TERYT location.
//
// -m --merge
//==============================================================================

if ( isset($options['merge']) ) {
	if (!$quiet)
		echo 'Merging TERYT with LMS database' . PHP_EOL;
    $merge_update = 0;

    $addresses = $DB->GetAll("
        SELECT id, city, city_id, street, street_id
        FROM addresses
        WHERE
            city_id IS NULL AND street_id IS NULL AND
            (city IS NOT NULL OR street IS NOT NULL);");

    if ( !$addresses ) {
        $addresses = array();
    }

    $location_cache = array();

    foreach ( $addresses as $a ) {
        if ( empty($ident['streetid']) && empty($ident['cityid']) ) {
            continue;
        }

        $key = strtolower($a['city']).':'.strtolower($a['street']);

        if ( isset($location_cache[$key]) ) {
            $ident = $location_cache[$key];
        } else {
            $ident = getIdents( $a['city'], $a['street'] );
            $location_cache[$key] = $ident;
        }

        $DB->Execute('UPDATE addresses SET city_id=?, street_id=? WHERE id=?;',
                      array($ident['cityid'], $ident['streetid'], $a['id']));

        ++$merge_update;
    }

	if (!$quiet)
		echo 'Matched TERYT addresses = ' . $merge_update . PHP_EOL;
    unset( $addresses, $merge_update, $location_cache );
}

//==============================================================================
// Delete downloaded files
//
// -d --delete
//==============================================================================

if ( isset($options['delete']) ) {
	if (!$quiet)
		echo 'Deleting downloaded files' . PHP_EOL;

    if ( !empty($basepoint_name) && file_exists($basepoint_name)) {
        unlink($teryt_dir . DIRECTORY_SEPARATOR . $basepoint_name );
    }

    unlink($teryt_dir . DIRECTORY_SEPARATOR . BASEPOINT_ZIP_NAME);
    unlink($teryt_dir . DIRECTORY_SEPARATOR . 'simc.zip');
    unlink($teryt_dir . DIRECTORY_SEPARATOR . 'SIMC.xml');
    unlink($teryt_dir . DIRECTORY_SEPARATOR . 'ulic.zip');
    unlink($teryt_dir . DIRECTORY_SEPARATOR . 'ULIC.xml');
    unlink($teryt_dir . DIRECTORY_SEPARATOR . 'terc.zip');
    unlink($teryt_dir . DIRECTORY_SEPARATOR . 'TERC.xml');
}

fclose($stderr);

?>

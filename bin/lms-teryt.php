#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2024 LMS Developers
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

$script_parameters = array(
    'fetch'  => 'f',
    'list:' => 'l:',
    'update'  => 'u',
    'merge'  => 'm',
    'delete'  => 'd',
    'buildings'  => 'b',
    'building-base-provider:' => null,
    'building-base-archived-filename-pattern:' => null,
    'allowed-building-operations:' => null,
    'only-unique-city-matches'  => 'o',
    'explicit-node-locations'  => 'e',
    'reverse'  => 'r',
);

$script_help = <<<EOF
-f, --fetch                        download teryt files
-u, --update                       update LMS database
-m, --merge                        try join current addresses with teryt locations
-d, --delete                       delete downloaded teryt files after merge/update
-b, --buildings                    analyze building base and load it into database
    --building-base-provider=<gugik|sidusis>
                                   specify which building base provider should be used
    --building-base-archived-filename-pattern=<...>
                                   regular expression to which files in download directory
                                   are matched as building database
    --allowed-building-operations=add,update,delete
                                   specify which building base operations are allowed
                                   when we load new building base
-l, --list                         state names or ids which will be taken into account
-o, --only-unique-city-matches     update TERYT location only if city matches uniquely
-e, --explicit-node-locations      set explicit TERYT locations for nodes
-r, --reverse                      reverse TERYT identifiers to textual representation
EOF;

require_once('script-options.php');

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
function parse_teryt_building_row($row)
{
    static $column_names = array('woj', 'powiat', 'gmina', 'terc', 'miejscowosc',
        'simc', 'ulica', 'ulic', 'building_num', 'longitude', 'latitude');

    if (count($column_names) == count($row)) {
        $result = array_combine($column_names, $row);
        $result['longitude'] = str_replace(',', '.', $result['longitude']);
        $result['latitude'] = str_replace(',', '.', $result['latitude']);
        return $result;
    } else {
        return null;
    }
}

function dbf_to_utf8($src)
{
    return str_replace(
        array(
            "\xC2\xB9",
            "\xC3\xA6",
            "\xC3\xAA",
            "\xC4\x99",
            "\xC2\xB3",
            "\xC3\xB1",
            "\xC3\xB3",
            "\xC2\x9C",
            "\xC2\x9F",
            "\xC2\xBF",
            "\xC2\x8C",
            "\xC2\xA3",
            "\xC2\x8F",
            "\xC2\xAF",
        ),
        array(
            'ą',
            'ć',
            'ę',
            'ę',
            'ł',
            'ń',
            'ó',
            'ś',
            'ź',
            'ż',
            'Ś',
            'Ł',
            'Ź',
            'Ż',
        ),
        $src
    );
}

/*!
 * \brief Translate XML element to asociative array.
 *
 * \param  XMLReader $xml
 * \return array
 */
function parse_teryt_xml_row($xml)
{
    $row = array();
    $node = $xml->expand();
    foreach ($node->childNodes as $childNode) {
        if (empty($childNode->tagName)) {
            continue;
        }
        $value = trim($childNode->nodeValue);
        $row[strtolower($childNode->tagName)] = empty($value) ? '0' : $value;
    }
    return $row;
}

function getIdentsWithSubcities($subcities, $street, $only_unique_city_matches)
{
    $street = trim(preg_replace('/^(ul\.|pl\.|al\.|bulw\.|os\.|wyb\.|plac|skwer|rondo|park|rynek|szosa|droga|ogród|wyspa)/i', '', $street));

    $DB = LMSDB::getInstance();

    $idents = $DB->GetAll(
        "SELECT s.id as streetid, ld.stateid, " . $subcities['cityid'] . " AS cityid,
			(" . $DB->Concat('t.name', "' '", '(CASE WHEN s.name2 IS NULL THEN s.name ELSE ' . $DB->Concat('s.name2', "' '", 's.name') . ' END)') . ") AS streetname
		FROM location_streets s
		JOIN location_street_types t ON t.id = s.typeid
		JOIN location_cities c ON c.id = " . $subcities['cityid'] . "
		JOIN location_boroughs lb ON lb.id = c.boroughid
		JOIN location_districts ld ON ld.id = lb.districtid
		WHERE
			((CASE WHEN s.name2 IS NULL THEN s.name ELSE " . $DB->Concat('s.name2', "' '", 's.name') . " END) ?LIKE? ? OR s.name ?LIKE? ? )
			AND s.cityid IN (" . $subcities['cities'] . ")",
        array($street, $street)
    );

    if (empty($idents)) {
        return array();
    }
    if (($only_unique_city_matches && count($idents) == 1) || !$only_unique_city_matches) {
        return $idents[0];
    } else {
        return array();
    }
}

/*
 * \brief Find TERYT location for city/street.
 *
 * \param  string $city   city name
 * \param  string $street street name
 * \return array  $ident  LMS location id's
 */
function getIdents($city = null, $street = null, $only_unique_city_matches = false)
{
    $street = trim(preg_replace('/^(ul\.|pl\.|al\.|bulw\.|os\.|wyb\.|plac|skwer|rondo|park|rynek|szosa|droga|ogród|wyspa)/i', '', $street));

    $DB = LMSDB::getInstance();

    if ($city && $street) {
        $idents = $DB->GetAll("
			SELECT s.id as streetid, ld.stateid, s.cityid,
				(" . $DB->Concat('t.name', "' '", '(CASE WHEN s.name2 IS NULL THEN s.name ELSE ' . $DB->Concat('s.name2', "' '", 's.name') . ' END)') . ") AS streetname
			FROM location_streets s
			JOIN location_street_types t ON t.id = s.typeid
			JOIN location_cities c ON (s.cityid = c.id)
			JOIN location_boroughs lb ON lb.id = c.boroughid
			JOIN location_districts ld ON ld.id = lb.districtid
			WHERE
				((CASE WHEN s.name2 IS NULL THEN s.name ELSE " . $DB->Concat('s.name2', "' '", 's.name') . " END) ?LIKE? ? OR s.name ?LIKE? ? )
				AND c.name ?LIKE? ?
			ORDER BY c.cityid", array($street, $street, $city));

        if (empty($idents)) {
            return array();
        }
        if (($only_unique_city_matches && count($idents) == 1) || !$only_unique_city_matches) {
            return $idents[0];
        } else {
            return array();
        }
    } elseif ($city) {
        $cities = $DB->GetAll(
            "SELECT c.id, ld.stateid
            FROM location_cities c
            JOIN location_boroughs lb ON lb.id = c.boroughid
            JOIN location_districts ld ON ld.id = lb.districtid
            WHERE c.name ?LIKE? ?",
            array($city)
        );
        if (empty($cities)) {
            return array();
        }
        if (($only_unique_city_matches && count($cities) == 1) || !$only_unique_city_matches) {
            return array(
                'cityid' => $cities[0]['id'],
                'stateid' => $cities[0]['stateid'],
            );
        } else {
            return array();
        }
    } else {
        return array();
    }
}

function getNamesWithSubcities($subcities, $street_id)
{
    $DB = LMSDB::getInstance();

    $result = $DB->GetRow(
        "SELECT t.name AS streettype, s.name AS street, s.name2 AS street2
        FROM location_streets s
        JOIN location_street_types t ON t.id = s.typeid
        WHERE s.cityid IN (" . $subcities['cities'] . ")
            AND s.id = ?",
        array($street_id)
    );
    $result['city'] = $DB->GetOne(
        "SELECT name FROM location_cities WHERE id = ?",
        array($subcities['cityid'])
    );

    return $result;
}

function getNames($city_id, $street_id)
{
    $DB = LMSDB::getInstance();

    if (empty($street_id)) {
        return $DB->GetRow(
            "SELECT c.name AS city
			FROM location_cities c
			WHERE c.id = ?
			LIMIT 1",
            array($city_id)
        );
    } else {
        return $DB->GetRow(
            "SELECT c.name AS city, t.name AS streettype, s.name AS street, s.name2 AS street2
			FROM location_cities c
			JOIN location_streets s ON s.cityid = c.id
			JOIN location_street_types t ON t.id = s.typeid
			WHERE c.id = ? AND s.id = ?",
            array($city_id, $street_id)
        );
    }
}

ini_set('memory_limit', '512M');
$stderr = fopen('php://stderr', 'w');

define('PROGRESS_ROW_COUNT', 1000);

$supported_building_base_providers = array(
    'gugik' => array(
        'url' => 'https://integracja.gugik.gov.pl/PRG/pobierz.php?adresy_zbiorcze_shp',
        'filename' => 'PRG-punkty_adresowe_shp.zip',
        'archived_filename_pattern' => '^PRG_PunktyAdresowe_POLSKA\.[[:alnum:]]{3}$',
    ),
    'sidusis' => array(
        'url' => 'https://internet.gov.pl/media/public/docs/address_points.zip',
        'filename' => 'address_points.zip',
        'archived_filename_pattern' => '^[0-9]{2}\.csv$',
    ),
);

setlocale(LC_NUMERIC, 'C');

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
if (!empty($states)) {
    $state_lists[$states] = "Invalid state list format in ini file!";
}
if (isset($options['list'])) {
    $state_lists[$options['list']] = "Invalid state list format entered in command line!";
}

$oldlocale = setlocale(LC_CTYPE, '0');
setlocale(LC_CTYPE, 'en_US.UTF-8');
foreach ($state_lists as $states => $error_message) {
    $states = explode(',', $states);
    foreach ($states as &$state) {
        if (preg_match('/^[0-9]+$/', $state)) {
            continue;
        }
        $state = iconv('UTF-8', 'ASCII//TRANSLIT', $state);
        if (!isset($all_states[$state])) {
            fwrite($stderr, $error_message . PHP_EOL);
            die;
        }
        $state = $all_states[$state];
    }
    unset($state);
    $state_list = array_combine($states, array_fill(0, count($states), '1'));
}
setlocale(LC_CTYPE, $oldlocale);

if (empty($teryt_dir)) {
    $teryt_dir = getcwd();
} else if (!is_dir($teryt_dir)) {
    fwrite($stderr, "Output directory specified in ini file does not exist!" . PHP_EOL);
    die;
}

//==============================================================================
// Download required files
//
// -f, --fetch
//==============================================================================

function get_teryt_file($ch, $type, $outfile)
{
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
    $date = date('d') . ' ' . $month_names[intval(date('m'))] . ' ' . date('Y');

    $continue = false;
    do {
        curl_setopt_array($ch, array(
            CURLOPT_URL => 'https://eteryt.stat.gov.pl/eTeryt/rejestr_teryt/udostepnianie_danych/baza_teryt/uzytkownicy_indywidualni/pobieranie/pliki_pelne.aspx',
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => array(
                '__EVENTTARGET' => 'ctl00$body$B' . $type . 'Pobierz',
                'ctl00$body$TBData' => $date,
            ),
        ));
        $res = curl_exec($ch);
        if (empty($res)) {
            return false;
        }

        if (strpos($res, 'PK') !== 0) {
            if (strpos($res, 'body_B' . $type . 'Generuj') === false) {
                return false;
            } else {
                curl_setopt_array($ch, array(
                    CURLOPT_URL => 'https://eteryt.stat.gov.pl/eTeryt/rejestr_teryt/udostepnianie_danych/baza_teryt/uzytkownicy_indywidualni/pobieranie/pliki_pelne.aspx',
                    CURLOPT_REFERER => 'https://eteryt.stat.gov.pl/eTeryt/rejestr_teryt/udostepnianie_danych/baza_teryt/uzytkownicy_indywidualni/pobieranie/pliki_pelne.aspx',
                    CURLOPT_POST => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POSTFIELDS => array(
                        '__EVENTTARGET' => 'ctl00$body$B' . $type . 'Generuj',
                        'ctl00$body$TBData' => $date,
                    ),
                ));
                $res = curl_exec($ch);
                if (empty($res)) {
                    return false;
                }

                $continue = true;
            }
        }
    } while ($continue);

    $fh = fopen($outfile, 'w');
    fwrite($fh, $res, strlen($res));
    fclose($fh);
    return true;
}

$building_base_provider_type = ConfigHelper::getConfig('teryt.building_base_provider', 'gugik');
if (isset($options['building-base-provider'])) {
    $building_base_provider_type = $options['building-base-provider'];
}
if (!isset($supported_building_base_providers[$building_base_provider_type])) {
    die('Building base provider \'' . $building_base_provider_type . '\' is not supported!' . PHP_EOL);
}
$building_base_provider = $supported_building_base_providers[$building_base_provider_type];

define('BUILDING_BASE_OPERATION_ADD', 1);
define('BUILDING_BASE_OPERATION_UPDATE', 2);
define('BUILDING_BASE_OPERATION_DELETE', 3);

$allowed_building_operations_map = array(
    'add' => BUILDING_BASE_OPERATION_ADD,
    'update' => BUILDING_BASE_OPERATION_UPDATE,
    'delete' => BUILDING_BASE_OPERATION_DELETE,
);
$allowed_building_operations = array();
if (isset($options['allowed-building-operations'])) {
    if (!isset($options['buildings'])) {
        die('Fatal error: option \'--allowed-building-operations\' can be used only with \'--buildings\' option!' . PHP_EOL);
    }
    $allowed_building_operations = array_filter(
        explode(',', $options['allowed-building-operations']),
        function ($operation) use ($allowed_building_operations_map) {
            if (!isset($allowed_building_operations_map[$operation])) {
                die('Fatal error: invalid building operation name \'' . $operation . '\'!' . PHP_EOL);
            }
            return true;
        }
    );
} else {
    $allowed_building_operations = array_flip($allowed_building_operations_map);
}
$allowed_building_operations = array_flip(
    array_map(
        function ($operation) use ($allowed_building_operations_map) {
            return $allowed_building_operations_map[$operation];
        },
        $allowed_building_operations
    )
);

if (isset($options['fetch'])) {
    if (!function_exists('curl_init')) {
        die('PHP CURL extension required!' . PHP_EOL);
    }

    if (!$quiet) {
        echo 'Downloading TERYT files...' . PHP_EOL;
    }

    $teryt_files = array(
        array(
            'name' => 'ULIC',
            'type' => 'ULICUrzedowy',
            'archived_name' => 'ULIC_Urzedowy',
        ),
        array(
            'name' => 'TERC',
            'type' => 'TERCUrzedowy',
            'archived_name' => 'TERC_Urzedowy',
        ),
        array(
            'name' => 'SIMC',
            'type' => 'SIMCUrzedowy',
            'archived_name' => 'SIMC_Urzedowy',
        ),
        array(
            'name' => 'WMRODZ',
            'type' => 'RodzMiej',
            'archived_name' => 'WMRODZ',
        ),
    );

    $ch = curl_init();

    $file_counter = 0;
    $teryt_filename_suffix = '_' . date('dmY');
    foreach ($teryt_files as $file) {
        $res = get_teryt_file($ch, $file['type'], $teryt_dir . DIRECTORY_SEPARATOR . $file['name'] . $teryt_filename_suffix . '.zip');
        if ($res) {
            $file_counter++;
        }
    }

    curl_close($ch);

    if ($file_counter != 4) {
        fwrite($stderr, 'Error: Downloaded ' . $file_counter . ' files, but 4 expected.' . PHP_EOL);
        die;
    }

    unset($file_counter);

    if (! class_exists('ZipArchive')) {
        fwrite($stderr, "Error: ZipArchive class not found." . PHP_EOL);
        die;
    }

    //==============================================================================
    // Unzip teryt files
    //==============================================================================
    $zip = new ZipArchive;

    if (!$quiet) {
        echo 'Unzipping TERYT files...' . PHP_EOL;
    }

    foreach ($teryt_files as $file) {
        $filename = $teryt_dir . DIRECTORY_SEPARATOR . $file['name'] . $teryt_filename_suffix . '.zip';
        echo $file['name'] . $teryt_filename_suffix . '.zip' . PHP_EOL;
        if ($zip->open($filename) === true) {
            $zip->extractTo($teryt_dir . DIRECTORY_SEPARATOR, array($file['archived_name'] . '_' . date('Y-m-d') . '.xml'));
            rename(
                $teryt_dir . DIRECTORY_SEPARATOR . $file['archived_name'] . '_' . date('Y-m-d') . '.xml',
                $teryt_dir . DIRECTORY_SEPARATOR . $file['name'] . '.xml'
            );
        } else {
            fwrite($stderr, 'Error: Can\'t unzip ' . $file['name'] . ' or file doesn\'t exist.' . PHP_EOL);
            die;
        }
    }

    unset($zip, $teryt_files);

     // download point address base (pobranie bazy punktów adresowych)
    function stream_notification_callback($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max)
    {
        static $filesize = null;

        switch ($notification_code) {
            case STREAM_NOTIFY_CONNECT:
                $filesize = null;
                break;
            case STREAM_NOTIFY_FILE_SIZE_IS:
                $filesize = $bytes_max;
                break;
            case STREAM_NOTIFY_PROGRESS:
                if (isset($filesize)) {
                    printf("%d%%         \r", ($bytes_transferred * 100) / $filesize);
                }
                break;
        }
    }

    $ctx = stream_context_create(
        array(
            'ssl'  => array(
                'verify_peer' => false,
            ),
        )
    );

    if (!$quiet) {
        echo 'Downloading ' . $building_base_provider['url'] . ' file...' . PHP_EOL;
        stream_context_set_params($ctx, array("notification" => "stream_notification_callback"));
    }

    file_put_contents($teryt_dir . DIRECTORY_SEPARATOR . $building_base_provider['filename'], fopen($building_base_provider['url'], 'r', false, $ctx));

    if (!$quiet) {
        echo "\rUnzipping " . $building_base_provider['filename'] . ' file...' . PHP_EOL;
    }
    $zip = new ZipArchive;

    if ($zip->open($teryt_dir . DIRECTORY_SEPARATOR . $building_base_provider['filename']) === true) {
        $numFiles = $zip->numFiles;

        for ($i = 0; $i < $numFiles; ++$i) {
            $filename = $zip->getNameIndex($i);
            if (preg_match('/' . $building_base_provider['archived_filename_pattern'] . '/', $filename)) {
                $st = $zip->statIndex($i);
                if ($st['comp_method'] === ZipArchive::CM_DEFLATE64) {
                    $output = array();
                    $result = null;
                    exec('unzip -d ' . $teryt_dir . ' ' . $building_base_provider['filename'] . ' ' . $filename . ' 2>&1', $output, $result);
                    if (!empty($result)) {
                        die('Fatal error: failed to run \'unzip\' command! Maybe \'unzip\' utility is not installed in your system?' . PHP_EOL);
                    }
                } else {
                    $zip->extractTo($teryt_dir, $filename);
                }
            }
        }

        unset($numFiles);
    } else {
        fprintf($stderr, "Error: Can't unzip %s or file doesn't exist." . PHP_EOL, $building_base_provider['filename']);
        die;
    }

    unset($zip);
}

if (isset($options['update'])) {
    //==============================================================================
    // Get current TERC from database
    //==============================================================================

    if (!$quiet) {
        echo 'Creating TERC cache' . PHP_EOL;
    }

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

    if ($tmp_terc_data) {
        foreach ($tmp_terc_data as $k => $v) {
            $key = $v['woj'].':'.$v['pow'].':'.$v['gmi'].':'.$v['rodz'];

            $terc[ $key ] = array(
                'id'    => $v['id'],
                'nazwa' => $v['nazwa'],
                'type'  => $v['type']
            );

            unset($tmp_terc_data[$k]);
        }
    }

    unset($tmp_terc_data);

    //==============================================================================
    // Create object to read XML files
    //
    // DOCUMENTATION
    // http://php.net/manual/en/book.xmlreader.php
    //
    // INSTALATION
    // http://php.net/manual/en/xmlreader.setup.php
    //==============================================================================

    if (! class_exists('XMLReader')) {
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

    if (!$quiet) {
        echo 'Parsing TERC.xml' . PHP_EOL;
    }

    $i = 0;
    $terc_insert = 0;
    $terc_update = 0;
    $terc_delete = 0;

    while ($xml->read()) {
        if ($xml->nodeType != XMLReader::ELEMENT || $xml->name != 'row') {
            continue;
        }

        if (!(++$i % PROGRESS_ROW_COUNT) && !$quiet) {
            echo 'Loaded ' . $i . PHP_EOL;
        }

        $row = parse_teryt_xml_row($xml);

        if (isset($state_list) && !isset($state_list[intval($row['woj'])])) {
            continue;
        }

        $statekey = $row['woj'] . ':0:0:0';
        $districtkey = $row['woj'] . ':' . $row['pow'] . ':0:0';
        $key = $row['woj'] . ':' . $row['pow'] . ':' . $row['gmi'] . ':' . $row['rodz'];

        // if $row['pow'] is empty then this row contains state
        if (empty($row['pow'])) {
            $data = $terc[$statekey] ?? null;

            // if state already exists then try update
            if ($data) {
                if ($data['nazwa'] != $row['nazwa']) {
                    $DB->Execute(
                        'UPDATE location_states SET name = ? WHERE id = ?',
                        array(mb_strtolower($row['nazwa']), $data['id'])
                    );

                    ++$terc_update;
                }

                $terc[$statekey]['valid'] = 1;
            } else {
                // else insert new state
                $DB->Execute(
                    'INSERT INTO location_states (name,ident) VALUES (?, ?)',
                    array(mb_strtolower($row['nazwa']), $row['woj'])
                );

                ++$terc_insert;
                $insertid = $DB->GetLastInsertID('location_states');
                $terc[$statekey] = array(
                    'id'    => $insertid,
                    'nazwa' => $row['nazwa'],
                    'type'  => 'WOJ',
                    'valid' => 1
                );
            }
        } elseif (empty($row['gmi'])) {
            // if $row['gmi'] is empty then this row contains district
            $data = $terc[$districtkey] ?? null;

            // if district already exists then try update
            if ($data) {
                if ($data['nazwa'] != $row['nazwa']) {
                    $DB->Execute(
                        'UPDATE location_districts SET stateid = ?, name = ? WHERE id = ?',
                        array($terc[$statekey]['id'], $row['nazwa'], $data['id'])
                    );

                    ++$terc_update;
                }

                $terc[$districtkey]['valid'] = 1;
            } else {
                // else insert new state
                $DB->Execute(
                    'INSERT INTO location_districts (stateid, name, ident) VALUES (?, ?, ?)',
                    array($terc[$statekey]['id'], $row['nazwa'], $row['pow'])
                );

                ++$terc_insert;
                $insertid = $DB->GetLastInsertID('location_districts');
                $terc[$districtkey] = array(
                    'id'    => $insertid,
                    'nazwa' => $row['nazwa'],
                    'type'  => 'POW',
                    'valid' => 1
                );
            }
        } else {
            // else row contains brough
            $data = $terc[$key] ?? null;

            // if district already exists then try update
            if ($data) {
                if ($data['nazwa'] != $row['nazwa']) {
                    $DB->Execute(
                        'UPDATE location_boroughs SET districtid=?, name=? WHERE id=?',
                        array($terc[$districtkey]['id'], $row['nazwa'], $data['id'])
                    );

                    ++$terc_update;
                }

                $terc[$key]['valid'] = 1;
            } else {
                // else insert new state
                if (isset($terc[$districtkey])) {
                    $DB->Execute(
                        'INSERT INTO location_boroughs (districtid, name, ident, type) VALUES (?,?,?,?)',
                        array($terc[$districtkey]['id'], $row['nazwa'], $row['gmi'], $row['rodz'])
                    );

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
    }

    if ($i % PROGRESS_ROW_COUNT && !$quiet) {
        echo 'Loaded ' . $i . PHP_EOL;
    }

    foreach ($terc as $k => $v) {
        if (!empty($v['valid'])) {
            continue;
        }

        ++$terc_delete;

        switch (strtolower($v['type'])) {
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

    unset($terc_insert, $terc_update, $terc_delete);

    //==============================================================================
    // Get current WMRODZ from database
    //==============================================================================
    if (!$quiet) {
        echo 'Creating WMRODZ cache' . PHP_EOL;
    }

    $wmrodz = $DB->GetAllByKey(
        "SELECT * FROM location_city_types",
        'ident'
    );
    if (empty($wmrodz)) {
        $wmrodz = array();
    }

    //==============================================================================
    // Read WMRODZ xml file
    //==============================================================================

    if (!$quiet) {
        echo 'Parsing WMRODZ.xml' . PHP_EOL;
    }

    if (@$xml->open($teryt_dir . DIRECTORY_SEPARATOR . 'WMRODZ.xml') === false) {
        fwrite($stderr, "Error: can't open WMRODZ.xml file." . PHP_EOL);
        die;
    }

    $i = 0;
    $wmrodz_insert = 0;
    $wmrodz_update = 0;
    $wmrodz_delete = 0;

    while ($xml->read()) {
        if ($xml->nodeType != XMLReader::ELEMENT || $xml->name != 'row') {
            continue;
        }

        if (!(++$i % PROGRESS_ROW_COUNT) && !$quiet) {
            echo 'Loaded ' . $i . PHP_EOL;
        }

        $row = parse_teryt_xml_row($xml);

        if (isset($wmrodz[$row['rm']])) {
            $wmrodz_rec = $wmrodz[$row['rm']];
            if ($wmrodz_rec['name'] != $row['nazwa_rm']) {
                $DB->Execute(
                    "UPDATE location_city_types
                        SET name = ?
                        WHERE id = ?",
                    array(
                        $row['nazwa_rm'],
                        $wmrodz_rec['id'],
                    )
                );
                $wmrodz_update++;
            }
            $wmrodz[$row['rm']]['valid'] = 1;
        } else {
            $DB->Execute(
                "INSERT INTO location_city_types
                    (ident, name) VALUES (?, ?)",
                array(
                    $row['rm'],
                    $row['nazwa_rm'],
                )
            );

            $wmrodz[$row['rm']]['id'] = $DB->GetLastInsertID('location_city_types');
            $wmrodz[$row['rm']]['valid'] = 1;

            $wmrodz_insert++;
        }
    }

    if ($i % PROGRESS_ROW_COUNT && !$quiet) {
        echo 'Loaded ' . $i . PHP_EOL;
    }

    foreach ($wmrodz as $k => $v) {
        if (!empty($v['valid'])) {
            continue;
        }

        $wmrodz_delete++;

        $DB->Execute('DELETE FROM location_city_types WHERE id = ?', array($v['id']));
    }

    //==============================================================================
    // Print WMRODZ stats
    //==============================================================================

    if (!$quiet) {
        echo 'WMRODZ inserted/updated/deleted = ' . $wmrodz_insert . '/' . $wmrodz_update . '/' . $wmrodz_delete . PHP_EOL;
        echo '---' . PHP_EOL;
    }

    unset($wmrodz_insert, $wmrodz_update, $wmrodz_delete);

    //==============================================================================
    // Get current SIMC from database
    //==============================================================================

    if (!$quiet) {
        echo 'Creating SIMC cache' . PHP_EOL;
    }

    $tmp_simc_data = $DB->GetAll("
	    SELECT s.ident AS woj, d.ident AS pow, b.ident AS gmi, b.type AS rodz_gmi,
	        c.ident AS sym, c.name AS nazwa, c.id, ct.ident AS rodz_mi,
	       (CASE WHEN cc.ident IS NOT NULL THEN cc.ident ELSE c.ident END) AS sympod
	    FROM location_cities c
	        JOIN location_boroughs b ON (c.boroughid = b.id)
	        JOIN location_districts d ON (b.districtid = d.id)
	        JOIN location_states s ON (d.stateid = s.id)
	        LEFT JOIN location_city_types ct ON (ct.id = c.type)
	        LEFT JOIN location_cities cc ON (c.cityid = cc.id)");

    $simc = array();

    if ($tmp_simc_data) {
        foreach ($tmp_simc_data as $k => $v) {
            $simc[$v['sym']] = array(
                'id'     => $v['id'],
                'key'    => $v['woj'].':'.$v['pow'].':'.$v['gmi'].':'.$v['rodz_gmi'],
                'nazwa'  => $v['nazwa'],
                'rodz_mi' => $v['rodz_mi'],
                'sym'    => $v['sym'],
                'sympod' => $v['sympod'],
            );

            unset($tmp_simc_data[$k]);
        }
    }

    unset($tmp_simc_data);

    //==============================================================================
    // Read SIMC xml file
    //==============================================================================

    if (!$quiet) {
        echo 'Parsing SIMC.xml' . PHP_EOL;
    }

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

    while ($xml->read()) {
        if ($xml->nodeType != XMLReader::ELEMENT || $xml->name != 'row') {
            continue;
        }

        if (!(++$i % PROGRESS_ROW_COUNT) && !$quiet) {
            echo 'Loaded ' . $i . PHP_EOL;
        }

        $row = parse_teryt_xml_row($xml);

        if (isset($state_list) && !isset($state_list[intval($row['woj'])])) {
            continue;
        }

        $key   = $row['woj'].':'.$row['pow'].':'.$row['gmi'].':'.$row['rodz_gmi'];
        $rodz_mi = $row['rm'];
        $id    = $row['sym'];
        $data  = $simc[$id] ?? null;
        $refid = $row['sympod'];

        if (!isset($terc[$key]) && !$quiet) {
            echo 'Not recognised TERYT-TERC key: ' . $key . PHP_EOL;
        }

        if ($refid == $id) {
            $refid = null;
        } elseif (!isset($simc[$refid])) {
            // refid not found (refered city is below this one), process later
            $cities_r[$refid][] = array(
                'key'   => $key,
                'nazwa' => $row['nazwa'],
                'rodz_mi' => $rodz_mi,
                'sym'   => $row['sym']
            );
        } else {
            $refid = $simc[$refid]['id'];
        }

        if (isset($terc[$key])) {
            // entry exists
            if ($data) {
                if ($data['nazwa'] != $row['nazwa'] || $data['sympod'] != $row['sympod'] || $data['key'] != $key || $data['rodz_mi'] != $rodz_mi) {
                    $DB->Execute(
                        'UPDATE location_cities SET boroughid=?, name=?, type = ?, cityid=? WHERE id=?',
                        array($terc[$key]['id'], $row['nazwa'], $wmrodz[$rodz_mi]['id'], $refid, $data['id'])
                    );

                    ++$simc_update;
                }

                // mark data as valid
                $simc[$id]['valid'] = 1;
                $cities[$id] = $data['id'];
            } elseif (!$refid || isset($simc[$row['sympod']])) {
                // add new city
                if (isset($terc[$key])) {
                    $DB->Execute(
                        'INSERT INTO location_cities (boroughid, name, type, cityid, ident) VALUES (?, ?, ?, ?, ?)',
                        array($terc[$key]['id'], $row['nazwa'], $wmrodz[$rodz_mi]['id'], $refid, $id)
                    );

                    ++$simc_insert;
                    $insertid = $DB->GetLastInsertID('location_cities');

                    $simc[$id] = array(
                         'key'    => $key,
                         'nazwa'  => $row['nazwa'],
                         'rodz_mi' => $rodz_mi,
                         'sym'    => $id,
                         'sympod' => $refid,
                         'id'     => $insertid,
                         'valid'  => 1,
                    );

                    $cities[$row['sym']] = $insertid;
                }
            }
        }

        // process references
        if (isset($cities_r[$id])) {
            while ($elem = array_pop($cities_r[$id])) {
                $rid  = $elem['sym'];
                $data = $simc[$rid] ?? null;

                // entry exists
                if ($data) {
                    if ($data['nazwa'] != $elem['nazwa'] || $data['sympod'] != $id || $data['key'] != $key || $data['rodz_mi'] != $elem['rodz_mi']) {
                        $DB->Execute(
                            'UPDATE location_cities SET boroughid=?, name=?, type = ?, cityid=? WHERE id=?',
                            array($terc[$key]['id'], $elem['nazwa'], $wmrodz[$elem['rodz_mi']]['id'], $cities[$id], $data['id'])
                        );

                        ++$simc_update;
                    }

                    // mark data as valid
                    $simc[$rid]['valid'] = 1;
                    $cities[$rid] = $rid;
                } else {
                    if (isset($terc[$key])) {
                        // add new city
                        $DB->Execute(
                            'INSERT INTO location_cities (boroughid, name, type, cityid, ident) VALUES (?, ?, ?, ?, ?)',
                            array($terc[$key]['id'], $elem['nazwa'], $wmrodz[$elem['rodz_mi']]['id'], $cities[$id], $rid)
                        );

                        ++$simc_insert;
                        $insertid = $DB->GetLastInsertID('location_cities');
                        $cities[$rid] = $insertid;
                    }
                }
            }
        }
    }

    if ($i % PROGRESS_ROW_COUNT && !$quiet) {
        echo 'Loaded ' . $i . PHP_EOL;
    }

    foreach ($simc as $k => $v) {
        if (empty($v['valid'])) {
            $DB->Execute('DELETE FROM location_cities WHERE id=?', array($v['id']));
            ++$simc_delete;
        }
    }

    unset($terc, $simc, $cities_r);

    //==============================================================================
    // Print SIMC stats
    //==============================================================================

    if (!$quiet) {
        echo 'SIMC inserted/updated/deleted = '.$simc_insert.'/'.$simc_update.'/'.$simc_delete . PHP_EOL;
        echo '---' . PHP_EOL;
    }

    unset($simc_insert, $simc_update, $simc_delete);

    //==============================================================================
    // Get current ULIC from database
    //==============================================================================

    $str_types = $DB->GetAllByKey('SELECT id, name FROM location_street_types', 'name');

    $tmp_ulic = $DB->GetAll("
	    SELECT s.id, s.ident, s.name, s.name2, s.typeid, c.ident AS city
	    FROM location_streets s
	    JOIN location_cities c ON (s.cityid = c.id)");

    $ulic = array();

    if ($tmp_ulic) {
        foreach ($tmp_ulic as $k => $v) {
            $ulic[$v['ident'] . ':' . $v['city']] = array(
                'id'     => $v['id'],
                'nazwa'  => $v['name'],
                'nazwa2' => $v['name2'],
                'typeid' => $v['typeid']
            );
        }
    }

    unset($tmp_ulic);

    //==============================================================================
    // Read ULIC xml file
    //==============================================================================

    if (!$quiet) {
        echo 'Parsing ULIC.xml' . PHP_EOL;
    }

    if (@$xml->open($teryt_dir . DIRECTORY_SEPARATOR . 'ULIC.xml') === false) {
        fwrite($stderr, "Error: can't open ULIC.xml file." . PHP_EOL);
        die;
    }

    $i = 0;
    $ulic_insert = 0;
    $ulic_update = 0;
    $ulic_delete = 0;

    while ($xml->read()) {
        if ($xml->nodeType != XMLReader::ELEMENT || $xml->name != 'row') {
            continue;
        }

        if (!(++$i % PROGRESS_ROW_COUNT) && !$quiet) {
            echo 'Loaded ' . $i . PHP_EOL;
        }

        $row = parse_teryt_xml_row($xml);

        if (isset($state_list) && !isset($state_list[intval($row['woj'])]) || !isset($row['nazwa_1'])) {
            continue;
        }

        $row['nazwa_1'] = trim($row['nazwa_1']);
        $row['nazwa_2'] = trim($row['nazwa_2']);
        $key    = $row['sym_ul'].':'.$row['sym'];
        $data   = $ulic[$key] ?? null;
        $row['cecha'] = mb_strtolower($row['cecha']);

        if (isset($str_types[$row['cecha']])) {
            $typeid = intval($str_types[$row['cecha']]['id']);
        } else {
             $DB->Execute(
                 'INSERT INTO location_street_types (name) VALUES (?)',
                 array( $row['cecha'] )
             );

            $typeid = $DB->GetLastInsertID('location_street_types');
            $str_types[$row['cecha']] = array(
                'id' => $typeid,
                'name' => $row['cecha'],
            );
        }

        // entry exists
        if ($data) {
            if ($data['nazwa'] != $row['nazwa_1'] || (isset($row['nazwa_2']) && (!isset($data['nazwa2']) || $data['nazwa2'] != $row['nazwa_2'])) || $data['typeid'] != $typeid) {
                $DB->Execute(
                    'UPDATE location_streets
	                          SET cityid = ?, name = ?, name2 = ?, typeid = ?
	                          WHERE id = ?',
                    array($cities[$row['sym']], $row['nazwa_1'], empty($row['nazwa_2']) ? null : $row['nazwa_2'], $typeid, $data['id'])
                );

                ++$ulic_update;
            }

            // mark data as valid
            $ulic[$key]['valid'] = 1;
        } else {
            // add new street
            $DB->Execute(
                'INSERT INTO location_streets (cityid, name, name2, typeid, ident) VALUES (?,?,?,?,?)',
                array($cities[$row['sym']], $row['nazwa_1'], empty($row['nazwa_2']) ? null : $row['nazwa_2'], $typeid, $row['sym_ul'])
            );

            ++$ulic_insert;
        }
    }

    if ($i % PROGRESS_ROW_COUNT && !$quiet) {
        echo 'Loaded ' . $i . PHP_EOL;
    }

    foreach ($ulic as $k => $v) {
        if (empty($v['valid'])) {
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

    unset($ulic, $str_types, $cities, $ulic_insert, $ulic_update, $ulic_delete);

    $xml->close();
    unset($xml);
} // close if ( isset($option['update']) )

//==============================================================================
// Read address point PRG database files
//
// -b, --buildings
//==============================================================================

if (isset($options['buildings'])) {
    if (isset($options['building-base-archived-filename-pattern'])) {
        $building_base_provider['archived_filename_pattern'] = $options['building-base-archived-filename-pattern'];
    }

    $files = getdir($teryt_dir);
    if (empty($files)) {
        fprintf($stderr, "Error: couldn't find any files in '%s' directory!" . PHP_EOL, $teryt_dir);
        die;
    }
    $files = array_filter($files, function ($file) use ($building_base_provider) {
        return preg_match('/' . $building_base_provider['archived_filename_pattern'] . '/', $file);
    });
    $file_count = count($files);
    if ($building_base_provider_type == 'gugik' && $file_count < 4
        || $building_base_provider_type == 'sidusis' && $file_count < 16) {
        fprintf($stderr, "Error: couldn't find any files matching to '%s' regular expression!" . PHP_EOL, $building_base_provider['archived_filename_pattern']);
        die;
    }

    if (isset($state_list)) {
        $state_name_to_ident = $DB->GetAllByKey('SELECT ident, name FROM location_states', 'name');

        foreach ($state_name_to_ident as $k => $v) {
            $state_name_to_ident[mb_strtoupper($k)] = $v['ident'];
        }
    }

    $to_update = array();
    $to_insert = array();

    // create location cache
    $location_cache = new LocationCache(LocationCache::LOAD_FULL);

    if ($building_base_provider_type == 'gugik') {
        if (!$quiet) {
            echo 'Parsing file...' . PHP_EOL;
        }

        $proj4 = new \proj4php\Proj4php();

        $projEPSG2180 = new \proj4php\Proj('EPSG:2180', $proj4);
        $projWGS84 = new \proj4php\Proj('EPSG:4326', $proj4);

        $Shapefile = new \Shapefile\ShapefileReader(
            $teryt_dir . DIRECTORY_SEPARATOR . 'PRG_PunktyAdresowe_POLSKA.shp',
            array(
                \Shapefile\Shapefile::OPTION_DBF_IGNORED_FIELDS => array(
                    'LAYER',
                    'PATH',
                ),
            )
        );

        $totalRecords = $Shapefile->getTotRecords();
        $steps = ceil($totalRecords / 10000);
        $step = 0;
        $i = 0;

        while ($Geometry = $Shapefile->fetchRecord()) {
            if (!$quiet && !($i % 10000)) {
                printf("%.2f%%\r", ($step * 100) / $steps);
            }

            $step_incremented = false;

            if (!($i % 10000) || $i >= $totalRecords) {
                $step++;
                $step_incremented = true;

                if ($to_insert) {
                    if (isset($allowed_building_operations[BUILDING_BASE_OPERATION_ADD])) {
                        $DB->Execute(
                            'INSERT INTO location_buildings
                            (city_id, street_id, building_num, zip, latitude, longitude, updated)
                            VALUES ' . implode(',', $to_insert)
                        );
                    }
                    $to_insert = array();
                }

                if ($to_update) {
                    $DB->Execute('UPDATE location_buildings SET updated = 1 WHERE id in (' . implode(',', $to_update) . ')');
                    $to_update = array();
                }
            }
            $i++;

            if ($Geometry->isDeleted()) {
                continue;
            }

            $address = $Geometry->getDataArray();

            if (isset($state_list) && !isset($state_list[intval(substr($address['TERYT'], 0, 2))])) {
                continue;
            }

            $address['SIMC_NAZWA'] = dbf_to_utf8($address['SIMC_NAZWA']);
            $address['ULIC_NAZWA'] = dbf_to_utf8($address['ULIC_NAZWA']);

            $coords = $Geometry->getArray();
            $pointSrc = new \proj4php\Point($coords['x'], $coords['y'], $projEPSG2180);
            $pointDest = $proj4->transform($projWGS84, $pointSrc);
            [$longitude, $latitude, ] = $pointDest->toArray();
            $coords = compact('longitude', 'latitude');

            $v = array_merge($address, $coords);

            $terc = $v['TERYT'];
            $simc = $v['SIMC_ID'];
            $ulic = $v['ULIC_ID'];

            $v['NUMER'] = preg_replace('/\.$/', '', dbf_to_utf8($v['NUMER']));
            if (!preg_match('#^[0-9a-zA-Z-, /\pL]*$#u', $v['NUMER'])) {
                if (strlen($simc)) {
                    fwrite($stderr, 'Warning: house number contains incorrect characters (TERC: ' . $terc . 'x, SIMC: ' . $simc . ', CITY: ' . $address['SIMC_NAZWA'] . ', ULIC: ' . $ulic . ', STREET: ' . $address['ULIC_NAZWA'] . ', NR: ' . $v['NUMER'] . ')!' . PHP_EOL);
                } else {
                    fwrite($stderr, 'Warning: house number contains incorrect characters (TERC: ' . $terc . 'x, SIMC: ' . $simc . ', CITY: ' . $address['SIMC_NAZWA'] . ', NR: ' . $v['NUMER'] . ')!' . PHP_EOL);
                }
                continue;
            }

            $city = $location_cache->getCityByIdent($terc, $simc);

            if (!$city) {
                if (strlen($simc)) {
                    fwrite($stderr, 'Warning: building was not found in TERYT database (TERC: ' . $terc . 'x, SIMC: ' . $simc . ', CITY: ' . $address['SIMC_NAZWA'] . ', ULIC: ' . $ulic . ', STREET: ' . $address['ULIC_NAZWA'] . ', NR: ' . $v['NUMER'] . ')!' . PHP_EOL);
                } else {
                    fwrite($stderr, 'Warning: building was not found in TERYT database (TERC: ' . $terc . 'x, SIMC: ' . $simc . ', CITY: ' . $address['SIMC_NAZWA'] . ', NR: ' . $v['NUMER'] . ')!' . PHP_EOL);
                }
                continue;
            }

            if ($ulic == '' || $city == '99999') {
                $street = array('id' => '0');
            } else {
                $street = $location_cache->getStreetByIdent($city['id'], $ulic);
                if (empty($street)) {
                    fwrite($stderr, 'Warning: building was not found in TERYT database (TERC: ' . $terc . 'x, SIMC: ' . $simc . ', CITY: ' . $address['SIMC_NAZWA'] . ', ULIC: ' . $ulic . ', STREET: ' . $address['ULIC_NAZWA'] . ', NR: ' . $v['NUMER'] . ')!' . PHP_EOL);
                    continue;
                }
            }
            $building = $location_cache->buildingExists($city['id'], $street['id'], $v['NUMER']);

            if ($building) {
                $fields_to_update = array();

                if ($building['zip'] != $v['PNA']) {
                    $fields_to_update[] = 'zip = ' . ($v['PNA'] ? $DB->Escape($v['PNA']) : 'null');
                }

                if ($building['latitude'] != $v['latitude']) {
                    $fields_to_update[] = 'latitude = ' . ($v['latitude'] ?: 'null');
                }

                if ($building['longitude'] != $v['longitude']) {
                    $fields_to_update[] = 'longitude = ' . ($v['longitude'] ?: 'null');
                }

                if (!empty($fields_to_update)) {
                    $DB->Execute(
                        'UPDATE location_buildings
                        SET updated = 1'
                        . (isset($allowed_building_operations[BUILDING_BASE_OPERATION_UPDATE]) ? ', ' . implode(',', $fields_to_update) : '')
                        . ' WHERE id = ' . $building['id']
                    );
                } else {
                    $to_update[] = $building['id'];
                }
            } else {
                $data = array();
                $data[] = $city['id'];
                $data[] = $street['id'] ?: 'null';
                $data[] = $v['NUMER'] ? $DB->Escape($v['NUMER']) : 'null';
                $data[] = $v['PNA'] ? $DB->Escape($v['PNA']) : 'null';
                $data[] = $v['latitude'] ?: 'null';
                $data[] = $v['longitude'] ?: 'null';
                $data[] = 1;

                $to_insert[] = '(' . implode(',', $data) . ')';
            }

            if (!(($i - 1) % 10000) || ($i - 1) >= $totalRecords) {
                if (!$step_incremented) {
                    $step++;
                }

                if ($to_insert) {
                    if (isset($allowed_building_operations[BUILDING_BASE_OPERATION_ADD])) {
                        $DB->Execute(
                            'INSERT INTO location_buildings
                            (city_id, street_id, building_num, zip, latitude, longitude, updated)
                            VALUES ' . implode(',', $to_insert)
                        );
                    }
                    $to_insert = array();
                }

                if ($to_update) {
                    $DB->Execute('UPDATE location_buildings SET updated = 1 WHERE id in (' . implode(',', $to_update) . ')');
                    $to_update = array();
                }
            }
        }

        echo "\r";
    } else {
        foreach ($files as $file) {
            $state_ident = substr($file, 0, 2);
            if (isset($state_list) && !isset($state_list[intval($state_ident)])) {
                continue;
            }

            $filename = $teryt_dir . DIRECTORY_SEPARATOR . $file;
            $fh = fopen($filename, 'r');
            if ($fh === false) {
                echo 'Cannot open file \'' . $filename . '\'!' . PHP_EOL;
                continue;
            }

            $buildings = $DB->GetAllByKey(
                'SELECT
                    b.city_id,
                    COALESCE(b.street_id, 0) AS street_id,
                    b.building_num,
                    b.longitude,
                    b.latitude,
                    b.zip,
                    b.id,
                    b.extid
                FROM location_buildings b
                JOIN location_cities lc ON lc.id = b.city_id
                JOIN location_boroughs lb ON lb.id = lc.boroughid
                JOIN location_districts ld ON ld.id = lb.districtid
                JOIN location_states ls ON ls.id = ld.stateid
                WHERE ls.ident = ?
                    AND b.extid IS NOT NULL',
                'extid',
                array(
                    $state_ident,
                )
            );
            if (empty($buildings)) {
                $buildings = array();
            }

            $column_names = null;

            $filesize = filesize($filename);
            $steps = ceil($filesize / 1048576);
            $step = 0;
            $i = 0;

            if (!$quiet) {
                echo "\rParsing file '" . $file . "': ";
                printf('%.2f%%    ', ($step * 100) / $steps);
            }

            $fileptr = 0;

            while (!feof($fh)) {
                $res = fseek($fh, $fileptr);
                $contents = fread($fh, 1048576);
                $pos = 0;
                while (true) {
                    $new_pos = strpos($contents, "\n", $pos);
                    if ($new_pos === false) {
                        $fileptr += $pos;
                        break;
                    } else {
                        $new_pos++;
                    }

                    $line = trim(substr($contents, $pos, $new_pos - $pos - 1));
                    $fields = str_getcsv($line);
                    $pos = $new_pos;

                    if (!isset($column_names)) {
                        $column_names = $fields;
                        continue;
                    }

                    $record = array_combine($column_names, $fields);

                    if (!($i % 10000)) {
                        if ($to_insert) {
                            if (isset($allowed_building_operations[BUILDING_BASE_OPERATION_ADD])) {
                                $DB->Execute(
                                    'INSERT INTO location_buildings
                                    (city_id, street_id, building_num, zip, latitude, longitude, updated, extid)
                                    VALUES ' . implode(',', $to_insert)
                                );
                            }
                            $to_insert = array();
                        }

                        if ($to_update) {
                            $DB->Execute('UPDATE location_buildings SET updated = 1 WHERE id in (' . implode(',', $to_update) . ')');
                            $to_update = array();
                        }
                    }
                    $i++;

                    /*
                    array(10) {
                        ["TERC"]=> string(7) "0221011"
                        ["Gmina"]=> string(15) "Boguszów-Gorce"
                        ["SIMC"]=> string(7) "0983824"
                        ["Miejscowość"]=> string(15) "Boguszów-Gorce"
                        ["SYM_UL"]=> string(5) "13923"
                        ["Ulica"]=> string(11) "ul. Nadziei"
                        ["Nr budynku"]=> string(1) "6"
                        ["Szerokość geograficzna"] => string(9) "50.751618"
                        ["Długość geograficzna"]=> string(9) "16.224241"
                        ["gml_id"]=> string(56) "PL.ZIPIN.4463.EMUiA_3e79bdc3-76e9-4c77-aa08-b5b064e70f8b"
                    }
                    */

                    $simc_nazwa = $record['Miejscowość'];
                    $ulic_nazwa = $record['Ulica'];
                    $terc = $record['TERC'];
                    $simc = $record['SIMC'];
                    $ulic = $record['SYM_UL'];
                    if ($ulic == '99999') {
                        $ulic = '';
                    }

                    if (!preg_match('#^[0-9a-zA-Z-, /\pL]*$#u', $record['Nr budynku'])) {
                        if (strlen($simc)) {
                            fwrite($stderr, 'Warning: house number contains incorrect characters (TERC: ' . $terc . ', SIMC: ' . $simc . ', CITY: ' . $simc_nazwa . ', ULIC: ' . $ulic . ', STREET: ' . $ulic_nazwa . ', NR: ' . $record['Nr budynku'] . ')!' . PHP_EOL);
                        } else {
                            fwrite($stderr, 'Warning: house number contains incorrect characters (TERC: ' . $terc . ', SIMC: ' . $simc . ', CITY: ' . $simc_nazwa . ', NR: ' . $record['Nr budynku'] . ')!' . PHP_EOL);
                        }
                        continue;
                    }

                    $city = $location_cache->getCityByIdent2($terc, $simc);

                    if (!$city) {
                        if (strlen($simc)) {
                            fwrite($stderr, 'Warning: building was not found in TERYT database (TERC: ' . $terc . ', SIMC: ' . $simc . ', CITY: ' . $simc_nazwa . ', ULIC: ' . $ulic . ', STREET: ' . $ulic_nazwa . ', NR: ' . $record['Nr budynku'] . ')!' . PHP_EOL);
                        } else {
                            fwrite($stderr, 'Warning: building was not found in TERYT database (TERC: ' . $terc . ', SIMC: ' . $simc . ', CITY: ' . $simc_nazwa . ', NR: ' . $record['Nr budynku'] . ')!' . PHP_EOL);
                        }
                        continue;
                    }

                    if ($ulic == '' || $city == '99999') {
                        $street = array('id' => '0');
                    } else {
                        $street = $location_cache->getStreetByIdent($city['id'], $ulic);
                        if (empty($street)) {
                            fwrite($stderr, 'Warning: building was not found in TERYT database (TERC: ' . $terc . ', SIMC: ' . $simc . ', CITY: ' . $simc_nazwa . ', ULIC: ' . $ulic . ', STREET: ' . $ulic_nazwa . ', NR: ' . $record['Nr budynku'] . ')!' . PHP_EOL);
                            continue;
                        }
                    }

                    if (isset($record['gml_id']) && strlen($record['gml_id']) && isset($buildings[$record['gml_id']])) {
                        $building = $buildings[$record['gml_id']];
                    } else {
                        $building = $location_cache->buildingExists($city['id'], $street['id'], $record['Nr budynku']);
                    }

                    if ($building) {
                        $fields_to_update = array();

                        if (isset($record['gml_id']) && $record['gml_id'] != $building['extid'] || !isset($record['gml_id']) && isset($record['extid']) && strlen($record['extid'])) {
                            $fields_to_update[] = 'extid = ' . (isset($record['gml_id']) ? $DB->Escape($record['gml_id']) : 'null');
                        }

                        if (!isset($building['city_id']) || $city['id'] != $building['city_id']) {
                            $fields_to_update[] = 'city_id = ' . $city['id'];
                        }

                        if (!isset($building['street_id']) || $street['id'] != $building['street_id']) {
                            $fields_to_update[] = 'street_id = ' . ($street['id'] ?: 'null');
                        }

                        if (!isset($building['building_num']) || mb_strtoupper($record['Nr budynku']) != $building['building_num']) {
                            $fields_to_update[] = 'building_num = UPPER(' . $DB->Escape($record['Nr budynku']) . ')';
                        }

                        if (isset($record['PNA']) && $building['zip'] != $record['PNA']) {
                            $fields_to_update[] = 'zip = ' . ($record['PNA'] ? $DB->Escape($record['PNA']) : 'null');
                        }

                        if ($record['Szerokość geograficzna'] != $building['latitude']) {
                            $fields_to_update[] = 'latitude = ' . ($record['Szerokość geograficzna'] ?: 'null');
                        }

                        if ($record['Długość geograficzna'] != $building['longitude']) {
                            $fields_to_update[] = 'longitude = ' . ($record['Długość geograficzna'] ?: 'null');
                        }

                        if (!empty($fields_to_update)) {
                            if (isset($allowed_building_operations[BUILDING_BASE_OPERATION_UPDATE])) {
                                $DB->Execute(
                                    'UPDATE location_buildings
                                    SET updated = 1'
                                    . (isset($allowed_building_operations[BUILDING_BASE_OPERATION_UPDATE]) ? ', ' . implode(',', $fields_to_update) : '')
                                    . ' WHERE id = ' . $building['id']
                                );
                            }
                        } else {
                            $to_update[] = $building['id'];
                        }
                    } else {
                        $data = array();
                        $data[] = $city['id'];
                        $data[] = $street['id'] ?: 'null';
                        $data[] = $record['Nr budynku'] ? $DB->Escape($record['Nr budynku']) : 'null';
                        $data[] = isset($record['PNA']) && $record['PNA'] ? $DB->Escape($record['PNA']) : 'null';
                        $data[] = $record['Szerokość geograficzna'] ?: 'null';
                        $data[] = $record['Długość geograficzna'] ?: 'null';
                        $data[] = 1;
                        $data[] = isset($record['gml_id']) && strlen($record['gml_id']) ? $DB->Escape($record['gml_id']) : 'null';

                        $to_insert[] = '(' . implode(',', $data) . ')';
                    }

                    if (!(($i - 1) % 10000)) {
                        if ($to_insert) {
                            if (isset($allowed_building_operations[BUILDING_BASE_OPERATION_ADD])) {
                                $DB->Execute(
                                    'INSERT INTO location_buildings
                                    (city_id, street_id, building_num, zip, latitude, longitude, updated, extid)
                                    VALUES ' . implode(',', $to_insert)
                                );
                            }
                            $to_insert = array();
                        }

                        if ($to_update) {
                            $DB->Execute('UPDATE location_buildings SET updated = 1 WHERE id in (' . implode(',', $to_update) . ')');
                            $to_update = array();
                        }
                    }
                }

                $step++;

                if (!$quiet) {
                    echo "\rParsing file '" . $file . "': ";
                    printf('%.2f%%    ', ($step * 100) / $steps);
                }
            }

            fclose($fh);

            echo PHP_EOL;
        }
    }

    if (isset($allowed_building_operations[BUILDING_BASE_OPERATION_DELETE])) {
        if (!$quiet) {
            echo 'Removing old buildings...' . PHP_EOL;
        }

        $DB->Execute('DELETE FROM location_buildings WHERE updated = 0');
    }
    $DB->Execute('UPDATE location_buildings SET updated = 0');

    unset(
        $to_insert,
        $to_update,
        $data,
        $location_cache,
        $fields_to_update,
        $i,
        $street,
        $building,
        $simc,
        $city,
        $lines,
        $steps,
        $state_name_to_ident,
        $state_ident
    );
} // close if ( isset($options['buildings']) ) {

//==============================================================================
// Merge TERYT with LMS database.
// Find addresses without city_id and street_id and try match TERYT location.
//
// -m --merge
//==============================================================================

if (isset($options['merge'])) {
    if (!$quiet) {
        echo 'Merging TERYT with LMS database...' . PHP_EOL;
    }
    $updated = 0;

    $addresses = $DB->GetAll("
        (
            SELECT a.id, a.city, a.street, ca.customer_id
            FROM addresses a
            JOIN customer_addresses ca ON ca.address_id = a.id
            WHERE a.city IS NOT NULL
                AND (a.city_id IS NULL OR (a.street IS NOT NULL AND a.street_id IS NULL))
        ) UNION (
            SELECT a.id, a.city, a.street, 0 AS customer_id
            FROM addresses a
            JOIN netdevices nd ON nd.address_id = a.id
            WHERE a.city IS NOT NULL
                AND (a.city_id IS NULL OR (a.street IS NOT NULL AND a.street_id IS NULL))
        ) UNION (
            SELECT a.id, a.city, a.street, 0 AS customer_id
            FROM addresses a
            JOIN netnodes nn ON nn.address_id = a.id
            WHERE a.city IS NOT NULL
                AND (a.city_id IS NULL OR (a.street IS NOT NULL AND a.street_id IS NULL))
        )
    ");

    if (!$addresses) {
        $addresses = array();
    }

    $location_cache = array();

    $cities_with_sections = $LMS->GetCitiesWithSections();

    foreach ($addresses as $a) {
        $city = empty($a['city']) ? '-' : $a['city'];
        $street = empty($a['street']) ? '-' : $a['street'];

        if (!$quiet) {
            printf("City '%s', Street: '%s': ", $city, $street);
        }

        $city = mb_strtolower($city);
        $street = mb_strtolower($street);
        $key = $city . ':' . $street;

        if (isset($location_cache[$key])) {
            $idents = $location_cache[$key];
        } else {
            if (isset($cities_with_sections[$city]) && $city != '-' && $street != '-') {
                $idents = getIdentsWithSubcities($cities_with_sections[$city], $street, $only_unique_city_matches);
            } else {
                $idents = getIdents($city == '-' ? null : $city, $street == '-' ? null : $street, $only_unique_city_matches);
            }
            $location_cache[$key] = $idents;
        }

        if (empty($idents)) {
            if (!$quiet) {
                echo 'not found' . (empty($a['customer_id']) ? '' : ' (customer #' . $a['customer_id'] . ')') . PHP_EOL;
            }
            continue;
        }

        if (!$quiet) {
            echo 'found' . (empty($a['customer_id']) ? '' : ' (customer #' . $a['customer_id'] . ')') . PHP_EOL;
        }

        if (!isset($idents['streetid'])) {
            $idents['streetid'] = null;
        }
        if (!isset($idents['streetname'])) {
            $idents['streetname'] = null;
        }
        $DB->Execute(
            "UPDATE addresses SET state_id = ?, city_id = ?, street_id = ?, street = ? WHERE id = ?",
            array($idents['stateid'], $idents['cityid'], $idents['streetid'], $idents['streetname'], $a['id'])
        );

        $updated++;
    }

    if (!$quiet) {
        echo 'Matched TERYT addresses: ' . $updated . PHP_EOL;
    }
    unset($addresses, $updated, $location_cache);
}

//==============================================================================
// Reverse TERYT identifiers to textual representation with LMS database.
//
// -r --reverse
//==============================================================================

if (isset($options['reverse'])) {
    if (!$quiet) {
        echo 'Reverse TERYT identifiers to textual representation with LMS database...' . PHP_EOL;
    }
    $updated = 0;

    $addresses = $DB->GetAll("
		(
			SELECT a.id, a.city_id, a.street_id
			FROM addresses a
			JOIN customer_addresses ca ON ca.address_id = a.id
			WHERE a.city_id IS NOT NULL
		) UNION (
			SELECT a.id, a.city_id, a.street_id
			FROM addresses a
			JOIN netdevices nd ON nd.address_id = a.id
			WHERE a.city_id IS NOT NULL
		) UNION (
			SELECT a.id, a.city_id, a.street_id
			FROM addresses a
			JOIN netnodes nn ON nn.address_id = a.id
			WHERE a.city_id IS NOT NULL
		)
	");

    if (!$addresses) {
        $addresses = array();
    }

    $location_cache = array();

    $cities_with_sections = $LMS->GetCitiesWithSections();
    $cities_with_sections_by_cityid = array();
    foreach ($cities_with_sections as $city => $city_with_section) {
        $cities_with_sections_by_cityid[$city_with_section['cityid']] = $city_with_section;
    }
    unset($cities_with_sections);

    foreach ($addresses as $a) {
        $city_id = $a['city_id'];
        $street_id = empty($a['street_id']) ? '-' : $a['street_id'];

        if (!$quiet) {
            printf("City ID: '%s', Street ID: '%s' ", $city_id, $street_id);
        }

        $key = $city_id . ':' . $street_id;

        if (isset($location_cache[$key])) {
            $names = $location_cache[$key];
        } else {
            if (isset($cities_with_sections_by_cityid[$city_id]) && $city_id != '-' && $street_id != '-') {
                $names = getNamesWithSubcities($cities_with_sections_by_cityid[$city_id], $street_id);
            } else {
                $names = getNames($city_id, $street_id == '-' ? null : $street_id);
            }
            if (isset($names['streettype'])) {
                $names['street'] = Utils::formatStreetName(array(
                    'type' => $names['streettype'],
                    'name' => $names['street'],
                    'name2' => $names['street2'],
                ));
                unset($names['streettype'], $names['street2']);
            }
            $location_cache[$key] = $names;
        }

        if (!$quiet) {
            printf("=> City '%s', Street: '%s'" . PHP_EOL, $names['city'], empty($names['street']) ? '-' : $names['street']);
        }

        $DB->Execute(
            "UPDATE addresses SET city = ?, street = ? WHERE id = ?",
            array($names['city'], empty($names['street']) ? null : $names['street'], $a['id'])
        );

        $updated++;
    }

    if (!$quiet) {
        echo 'Reversed TERYT identifiers: ' . $updated . PHP_EOL;
    }
    unset($addresses, $updated, $location_cache);
}

//==============================================================================
// Determine explicit node TERYT locations
//
// -e --explicit-node-locations
//==============================================================================

if (isset($options['explicit-node-locations'])) {
    if (!$quiet) {
        echo 'Determining explicit TERYT node locations...' . PHP_EOL;
    }

    $nodes = $DB->GetAll('SELECT id, ownerid FROM nodes
		WHERE ownerid IS NOT NULL AND address_id IS NULL
		ORDER BY id');
    if (!empty($nodes)) {
        foreach ($nodes as $node) {
            $addresses = $LMS->getCustomerAddresses($node['ownerid']);
            if (empty($addresses)) {
                continue;
            }
            $address_id = $LMS->determineDefaultCustomerAddress($addresses);
            if (empty($address_id) || empty($addresses[$address_id]['teryt'])) {
                continue;
            }
            $address = $addresses[$address_id];

            if (!$quiet) {
                printf(
                    'Setting explicit TERYT location address for node: %d (city: %s, street: %s, house: %s, flat: %s)' . PHP_EOL,
                    $node['id'],
                    $address['location_city_name'],
                    $address['location_street_name'],
                    $address['location_house'],
                    $address['location_flat']
                );
            }

            $DB->Execute(
                'UPDATE nodes SET address_id = ? WHERE id = ?',
                array($address['address_id'], $node['id'])
            );
        }
    }
}

//==============================================================================
// Delete downloaded files
//
// -d --delete
//==============================================================================

if (isset($options['delete'])) {
    if (!$quiet) {
        echo 'Deleting downloaded files...' . PHP_EOL;
    }

    if (!empty($building_base_provider['archived_filename_pattern'])) {
        $files = getdir($teryt_dir);
        if (!empty($files)) {
            foreach ($files as $file) {
                if (preg_match('/' . $building_base_provider['archived_filename_pattern'] . '/', $file)) {
                    @unlink($teryt_dir . DIRECTORY_SEPARATOR . $file);
                }
            }
        }
    }

    @unlink($teryt_dir . DIRECTORY_SEPARATOR . $building_base_provider['filename']);
    @unlink($teryt_dir . DIRECTORY_SEPARATOR . 'SIMC.xml');
    @unlink($teryt_dir . DIRECTORY_SEPARATOR . 'ULIC.xml');
    @unlink($teryt_dir . DIRECTORY_SEPARATOR . 'TERC.xml');
}

fclose($stderr);

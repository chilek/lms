<?php

/**
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

use GusApi\GusApi;
use GusApi\RegonConstantsInterface;
use GusApi\SearchType;
use GusApi\Exception\InvalidUserKeyException;
use GusApi\ReportTypes;
use GusApi\ReportTypeMapper;

if (!isset($_GET['searchtype']) || !isset($_GET['searchdata'])) {
    return;
}

if (!in_array($_GET['searchtype'], array(SearchType::NIP,
    SearchType::REGON, SearchType::KRS))) {
    return;
}

$apikey = ConfigHelper::getConfig('phpui.gusapi_key', 'abcde12345abcde12345');
if ($apikey == 'abcde12345abcde12345') {
    $env = 'dev';
} else {
    $env = 'prod';
}

$gus = new GusApi(
    $apikey, // your user key / twój klucz użytkownika
    $env
);

//$mapper = new ReportTypeMapper();

try {
    if (!$gus->login()) {
        throw new Exception(trans('Bad REGON API user key'));
    }

    switch ($_GET['searchtype']) {
        case SearchType::NIP:
            $gusReports = $gus->getByNip(preg_replace('/[^a-z0-9]/i', '', $_GET['searchdata']));
            break;
        case SearchType::REGON:
            $gusReports = $gus->getByRegon($_GET['searchdata']);
            break;
        case SearchType::KRS:
            $gusReports = $gus->getByRegon($_GET['searchdata']);
            break;
        default:
            throw new Exception(trans('Unsupported resource type'));
    }

    if (count($gusReports) > 1) {
        die;
    }

    $gusReport = $gusReports[0];
    $personType = $gusReport->getType();

    if ($personType == \GusApi\SearchReport::TYPE_JURIDICAL_PERSON) {
        $fullReport = $gus->getFullReport(
            $gusReport,
            ReportTypes::REPORT_PUBLIC_LAW
        );

        $report = reset($fullReport);

        $details = array(
            'lastname' => $report['praw_nazwa'],
            'name' => '',
            'rbename' => $report['praw_organRejestrowy_Nazwa'],
            'rbe' => $report['praw_numerWRejestrzeEwidencji'],
            'regon' => array_key_exists('praw_regon9', $report)
                ? $report['praw_regon9']
                : $report['praw_regon14'],
            'ten' => $report['praw_nip'],
            'addresses' => array(),
        );

        $addresses = array();

        $terc = $report['praw_adSiedzWojewodztwo_Symbol']
            . $report['praw_adSiedzPowiat_Symbol']
            . $report['praw_adSiedzGmina_Symbol'];
        $simc = $report['praw_adSiedzMiejscowosc_Symbol'];
        $ulic = $report['praw_adSiedzUlica_Symbol'];
        $location = $LMS->TerytToLocation($terc, $simc, $ulic);

        $addresses[] = array(
            'location_state_name' => mb_strtolower($report['praw_adSiedzWojewodztwo_Nazwa']),
            'location_city_name' => $report['praw_adSiedzMiejscowosc_Nazwa'],
            'location_street_name' => $report['praw_adSiedzUlica_Nazwa'],
            'location_house' => $report['praw_adSiedzNumerNieruchomosci'],
            'location_flat' => $report['praw_adSiedzNumerLokalu'],
            'location_zip' => preg_replace(
                '/^([0-9]{2})([0-9]{3})$/',
                '$1-$2',
                $report['praw_adSiedzKodPocztowy']
            ),
            'location_postoffice' => $report['praw_adSiedzMiejscowoscPoczty_Nazwa']
                == $report['praw_adSiedzMiejscowosc_Nazwa'] ? ''
                    : $report['praw_adSiedzMiejscowoscPoczty_Nazwa'],
            'location_state' => empty($location) ? 0 : $location['location_state'],
            'location_city' => empty($location) ? 0 : $location['location_city'],
            'location_street' => empty($location) ? 0 : $location['location_street'],
        );

        $details['addresses'] = $addresses;
    } elseif ($personType == \GusApi\SearchReport::TYPE_NATURAL_PERSON) {
        $silo = $gusReport->getSilo();

        $siloMapper = array(
            1 => ReportTypes::REPORT_ACTIVITY_PHYSIC_CEIDG,
            2 => ReportTypes::REPORT_ACTIVITY_PHYSIC_AGRO,
            3 => ReportTypes::REPORT_ACTIVITY_PHYSIC_OTHER_PUBLIC,
            4 => ReportTypes::REPORT_ACTIVITY_LOCAL_PHYSIC_WKR_PUBLIC,
        );

        if (!isset($siloMapper[$silo])) {
            die;
        }

        $fullReport = $gus->getFullReport(
            $gusReport,
            $siloMapper[$silo]
        );

        $report = reset($fullReport);

        $details = array(
            'lastname' => $report['fiz_nazwa'],
            'name' => '',
            'rbename' => $report['fizC_RodzajRejestru_Nazwa'],
            'rbe' => $report['fizC_numerwRejestrzeEwidencji'],
            'regon' => array_key_exists('fiz_regon9', $report)
                ? $report['fiz_regon9']
                : $report['fiz_regon14'],
            'addresses' => array(),
        );

        $addresses = array();

        $terc = $report['fiz_adSiedzWojewodztwo_Symbol']
            . $report['fiz_adSiedzPowiat_Symbol']
            . $report['fiz_adSiedzGmina_Symbol'];
        $simc = $report['fiz_adSiedzMiejscowosc_Symbol'];
        $ulic = $report['fiz_adSiedzUlica_Symbol'];
        $location = $LMS->TerytToLocation($terc, $simc, $ulic);

        $addresses[] = array(
            'location_state_name' => mb_strtolower($report['fiz_adSiedzWojewodztwo_Nazwa']),
            'location_city_name' => $report['fiz_adSiedzMiejscowosc_Nazwa'],
            'location_street_name' => $report['fiz_adSiedzUlica_Nazwa'],
            'location_house' => $report['fiz_adSiedzNumerNieruchomosci'],
            'location_flat' => $report['fiz_adSiedzNumerLokalu'],
            'location_zip' => preg_replace(
                '/^([0-9]{2})([0-9]{3})$/',
                '$1-$2',
                $report['fiz_adSiedzKodPocztowy']
            ),
            'location_postoffice' => $report['fiz_adSiedzMiejscowoscPoczty_Nazwa']
                == $report->dane['adSiedzMiejscowosc_Nazwa'] ? ''
                    : $report['fiz_adSiedzMiejscowoscPoczty_Nazwa'],
            'location_state' => empty($location) ? 0 : $location['location_state'],
            'location_city' => empty($location) ? 0 : $location['location_city'],
            'location_street' => empty($location) ? 0 : $location['location_street'],
        );

        $details['addresses'] = $addresses;

        $fullReport = $gus->getFullReport(
            $gusReport,
            \GusApi\ReportTypes::REPORT_ACTIVITY_PHYSIC_PERSON
        );

        $report = reset($fullReport);
        $details['ten'] = $report['fiz_nip'];
    }

    header('Content-Type: application/json');
    die(json_encode($details));
} catch (InvalidUserKeyException $e) {
    header('Content-Type: application/json');
    die(json_encode(array('error' => trans('Bad REGON API user key'))));
} catch (\GusApi\Exception\NotFoundException $e) {
    header('Content-Type: application/json');
    die(json_encode(array('warning' => trans("No data found in REGON database"))));
//      . "For more information read server message below:\n"
//      . '$a', $gus->getResultSearchMessage($sessionId)))));
} catch (Exception $e) {
    header('Content-Type: application/json');
    die(json_encode(array('error' => $e->getMessage())));
}

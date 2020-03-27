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
use GusApi\Exception\InvalidUserKeyException;
use GusApi\ReportTypes;
use GusApi\ReportTypeMapper;

if (!isset($_GET['searchtype']) || !isset($_GET['searchdata'])) {
    return;
}

if (!in_array($_GET['searchtype'], array(RegonConstantsInterface::SEARCH_TYPE_NIP,
    RegonConstantsInterface::SEARCH_TYPE_REGON, RegonConstantsInterface::SEARCH_TYPE_KRS))) {
    return;
}

$apikey = ConfigHelper::getConfig('phpui.gusapi_key', 'abcde12345abcde12345');
if ($apikey == 'abcde12345abcde12345') {
    $adapter = new \GusApi\Adapter\Soap\SoapAdapter(
        RegonConstantsInterface::BASE_WSDL_URL_TEST,
        RegonConstantsInterface::BASE_WSDL_ADDRESS_TEST
    );
} else {
    $adapter = new \GusApi\Adapter\Soap\SoapAdapter(
        RegonConstantsInterface::BASE_WSDL_URL,
        RegonConstantsInterface::BASE_WSDL_ADDRESS // production server / serwer produkcyjny
    );
}

$gus = new GusApi(
    $apikey, // your user key / twój klucz użytkownika
    $adapter
);

$mapper = new ReportTypeMapper();

try {
    $sessionId = $gus->login();

    switch ($_GET['searchtype']) {
        case RegonConstantsInterface::SEARCH_TYPE_NIP:
            $gusReports = $gus->getByNip($sessionId, preg_replace('/[^a-z0-9]/i', '', $_GET['searchdata']));
            break;
        case RegonConstantsInterface::SEARCH_TYPE_REGON:
            $gusReports = $gus->getByRegon($sessionId, $_GET['searchdata']);
            break;
        case RegonConstantsInterface::SEARCH_TYPE_KRS:
            $gusReports = $gus->getByRegon($sessionId, $_GET['searchdata']);
            break;
        default:
            return $result;
    }

    if (count($gusReports) > 1) {
        die;
    }

    $gusReport = $gusReports[0];
    $personType = $gusReport->getType();
    $reportType = $mapper->getReportType($gusReport);

    $fullReport = $gus->getFullReport(
        $sessionId,
        $gusReport,
        $reportType
    );

    if ($personType == \GusApi\SearchReport::TYPE_JURIDICAL_PERSON) {
        $details = array(
            'lastname' => $fullReport->dane->praw_nazwa->__toString(),
            'name' => '',
            'rbename' => $fullReport->dane->praw_organRejestrowy_Nazwa->__toString(),
            'rbe' => $fullReport->dane->praw_numerWrejestrzeEwidencji->__toString(),
            'regon' => property_exists($fullReport->dane, 'praw_regon9')
                ? $fullReport->dane->praw_regon9->__toString()
                : $fullReport->dane->praw_regon14->__toString(),
            'ten' => $fullReport->dane->praw_nip->__toString(),
            'addresses' => array(),
        );

        $addresses = array();

        $terc = $fullReport->dane->praw_adSiedzWojewodztwo_Symbol->__toString()
            . $fullReport->dane->praw_adSiedzPowiat_Symbol->__toString()
            . $fullReport->dane->praw_adSiedzGmina_Symbol->__toString();
        $simc = $fullReport->dane->praw_adSiedzMiejscowosc_Symbol->__toString();
        $ulic = $fullReport->dane->praw_adSiedzUlica_Symbol->__toString();
        $location = $LMS->TerytToLocation($terc, $simc, $ulic);

        $addresses[] = array(
            'location_state_name' => mb_strtolower($fullReport->dane->praw_adSiedzWojewodztwo_Nazwa->__toString()),
            'location_city_name' => $fullReport->dane->praw_adSiedzMiejscowosc_Nazwa->__toString(),
            'location_street_name' => $fullReport->dane->praw_adSiedzUlica_Nazwa->__toString(),
            'location_house' => $fullReport->dane->praw_adSiedzNumerNieruchomosci->__toString(),
            'location_flat' => $fullReport->dane->praw_adSiedzNumerLokalu->__toString(),
            'location_zip' => preg_replace(
                '/^([0-9]{2})([0-9]{3})$/',
                '$1-$2',
                $fullReport->dane->praw_adSiedzKodPocztowy->__toString()
            ),
            'location_postoffice' => $fullReport->dane->praw_adSiedzMiejscowoscPoczty_Nazwa->__toString()
                == $fullReport->dane->praw_adSiedzMiejscowosc_Nazwa->__toString() ? ''
                    : $fullReport->dane->praw_adSiedzMiejscowoscPoczty_Nazwa->__toString(),
            'location_state' => empty($location) ? 0 : $location['location_state'],
            'location_city' => empty($location) ? 0 : $location['location_city'],
            'location_street' => empty($location) ? 0 : $location['location_street'],
        );

        $details['addresses'] = $addresses;
    } elseif ($personType == \GusApi\SearchReport::TYPE_NATURAL_PERSON) {
        $details = array(
            'lastname' => $fullReport->dane->fiz_nazwa->__toString(),
            'name' => '',
            'rbename' => $fullReport->dane->fizC_RodzajRejestru_Nazwa->__toString(),
            'rbe' => $fullReport->dane->fizC_numerwRejestrzeEwidencji->__toString(),
            'regon' => property_exists($fullReport->dane, 'fiz_regon9')
                ? $fullReport->dane->fiz_regon9->__toString()
                : $fullReport->dane->fiz_regon14->__toString(),
            'addresses' => array(),
        );

        $addresses = array();

        $terc = $fullReport->dane->fiz_adSiedzWojewodztwo_Symbol->__toString()
            . $fullReport->dane->fiz_adSiedzPowiat_Symbol->__toString()
            . $fullReport->dane->fiz_adSiedzGmina_Symbol->__toString();
        $simc = $fullReport->dane->fiz_adSiedzMiejscowosc_Symbol->__toString();
        $ulic = $fullReport->dane->fiz_adSiedzUlica_Symbol->__toString();
        $location = $LMS->TerytToLocation($terc, $simc, $ulic);

        $addresses[] = array(
            'location_state_name' => mb_strtolower($fullReport->dane->fiz_adSiedzWojewodztwo_Nazwa->__toString()),
            'location_city_name' => $fullReport->dane->fiz_adSiedzMiejscowosc_Nazwa->__toString(),
            'location_street_name' => $fullReport->dane->fiz_adSiedzUlica_Nazwa->__toString(),
            'location_house' => $fullReport->dane->fiz_adSiedzNumerNieruchomosci->__toString(),
            'location_flat' => $fullReport->dane->fiz_adSiedzNumerLokalu->__toString(),
            'location_zip' => preg_replace(
                '/^([0-9]{2})([0-9]{3})$/',
                '$1-$2',
                $fullReport->dane->fiz_adSiedzKodPocztowy->__toString()
            ),
            'location_postoffice' => $fullReport->dane->fiz_adSiedzMiejscowoscPoczty_Nazwa->__toString()
                == $fullReport->dane->fiz_adSiedzMiejscowosc_Nazwa->__toString() ? ''
                    : $fullReport->dane->fiz_adSiedzMiejscowoscPoczty_Nazwa->__toString(),
            'location_state' => empty($location) ? 0 : $location['location_state'],
            'location_city' => empty($location) ? 0 : $location['location_city'],
            'location_street' => empty($location) ? 0 : $location['location_street'],
        );

        $details['addresses'] = $addresses;

        $fullReport = $gus->getFullReport(
            $sessionId,
            $gusReport,
            \GusApi\ReportTypes::REPORT_ACTIVITY_PHYSIC_PERSON
        );

        $details['ten'] = $fullReport->dane->fiz_nip->__toString();
    }

    $detailsChild = $fullReport->addChild('details');
    foreach ($details as $key => $value) {
        if ($key == 'addresses') {
            $addressesChild = $detailsChild->addChild('addresses');
            foreach ($value as $addressnr => $address) {
                $addressChild = $addressesChild->addChild('address');
                foreach ($address as $addresskey => $addressvalue) {
                    $addressChild->addChild($addresskey, $addressvalue);
                }
            }
        } else {
            $detailsChild->addChild($key, $value);
        }
    }

    header('Content-Type: application/json');
    die(json_encode($fullReport));
} catch (InvalidUserKeyException $e) {
    header('Content-Type: application/json');
    die(json_encode(array('error' => trans('Bad REGON API user key'))));
} catch (\GusApi\Exception\NotFoundException $e) {
    header('Content-Type: application/json');
    die(json_encode(array('warning' => trans("No data found in REGON database"))));
//      . "For more information read server message below:\n"
//      . '$a', $gus->getResultSearchMessage($sessionId)))));
}

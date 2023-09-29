<?php
$latitude = filter_var($_GET['latitude'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$longitude = filter_var($_GET['longitude'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$type = filter_var($_GET['type'], FILTER_SANITIZE_STRING);

//converts URL containging Coordinates in latitude/longitude format to Geoportal BBOX
if ($type == 'geoportal') {
    $geoportalApiUrl = ConfigHelper::getConfig('geoportal.api_url', 'https://mapy.geoportal.gov.pl/imap/Imgp_2.html?composition=default&bbox=');
    $geoportalZoomFactor = ConfigHelper::getConfig('geoportal.zoom_factor', 100);
    $mapPosition = Utils::convertLMStoGeoportal($latitude, $longitude);
    $pos = array(
        'xMin' => number_format($mapPosition[0] - $geoportalZoomFactor, 6, '.', ''),
        'xMax' => number_format($mapPosition[0] + $geoportalZoomFactor, 6, '.', ''),
        'yMin' => number_format($mapPosition[1] - $geoportalZoomFactor, 6, '.', ''),
        'yMax' => number_format($mapPosition[1] + $geoportalZoomFactor, 6, '.', ''),
    );

    header('Location: ' . $geoportalApiUrl . $pos['xMin'] . ',' . $pos['yMin'] . ',' . $pos['xMax'] . ',' . $pos['yMax']);
}

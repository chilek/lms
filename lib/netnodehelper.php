<?php 

/*
 * Obsługa danych modułu ndcluster.
 * Dane te nie są zapisane w bazie.
 *
 */

// Status węzła
$NNstatus = array(trans('existing'),trans('under construction'),trans('planned'));

// Typ obiektu - nie tłumaczę, bo nomenklatura jest wzięta z dokumentu polskiego
$NNtype = array('budynek biurowy','budynek przemysłowy','budynek mieszkalny','obiekt sakralny','maszt','wieża','kontener','szafa uliczna','skrzynka','studnia kablowa','komin');

// Własność węzła - na razie też nie tłumaczę na angielski
$NNownership = array('węzeł własny','węzeł współdzielony z innym podmiotem');



?>

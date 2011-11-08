<?php
require( dirname(__FILE__).'/_shared.php' );

date_default_timezone_set( 'UTC' );

header( 'Content-type: text/plain' );
header( 'Content-Disposition: inline; filename="towers.csv"' );

$writeColumns = array( 'doveid', 'country', 'county', 'diocese', 'dedication', 'place', 'location', 'bells', 'weight', 'weightText', 'note', 'affiliations', 'unringable', 'marker' );

// Open output handle
if( ( $outputHandle = fopen( 'php://output', 'w' ) ) == false ) {
	trigger_error( 'Couldn\'t open php://output' );
}

// Write out columns
fputcsv( $outputHandle, $writeColumns );

// Tower data from dove.txt
if( ( $handle = fopen( __DIR__.'/data/dove.txt', 'r' ) ) == false ) {
	trigger_error( 'Couldn\'t open dove.txt' );
}

// Extract column headings
if( ( $readColumns = fgetcsv( $handle, 0, "\\" ) ) === false ) {
	trigger_error( 'Couldn\'t read dove.txt' );
}

// Read data
while( ( $data = fgetcsv( $handle, 0, "\\" ) ) !== false ) {
	$tower = array();
	
	// Match data to columns
	foreach( $readColumns as $i => $column ) {
		$tower[$column] = $data[$i];
	}
	
	// Tody data
	$rowData = tidyTower( $tower );
	
	// fusionTowers INSERT
	if( isset( $rowData['latitude'], $rowData['longitude'] ) ) {
		$rowData['affiliations'] = $tower['Affiliations'];
		$rowData['location'] = $rowData['latitude'].','.$rowData['longitude'];
		$rowData['marker'] = ( isset( $rowData['unringable'] ) == 1 )? 'measle_white' : (
			( $rowData['bells'] <= 4 )? 'measle_brown' : (
			( $rowData['bells'] == 5 )? 'small_yellow' : (
			( $rowData['bells'] == 6 )? 'measle_turquoise' : (
			( $rowData['bells'] <= 8 )? 'small_green' : (
			( $rowData['bells'] <= 10 )? 'small_blue' : (
			( $rowData['bells'] <= 12 )? 'small_purple' : (
			'small_red' ) ) ) ) ) ) );
		
		// Write row
		$toWrite = array();
		foreach( $writeColumns as $column ) {
			$toWrite[] = isset( $rowData[$column] )? trim( $rowData[$column], "'" ) : null;
		}
		fputcsv( $outputHandle, $toWrite );
	}
}

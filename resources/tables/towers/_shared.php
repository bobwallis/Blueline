<?php
require( dirname(__FILE__).'/../../../vendor/blueline/abbreviations.php' );

function longCounty( $lookup, array $array ) {
	if( empty( $lookup ) ) { return ''; }
	elseif( array_key_exists( $lookup, $array ) ) { return $array[$lookup]; 	}
	elseif( in_array( $lookup, $array ) ) { return $lookup; }
	else {
		trigger_error( 'No full county for: '.$lookup, E_USER_ERROR );
	}
}

function weightText( $weight, $weightApprox ) {
	// Calculate weight text
	$tmp = $weight % 112;
	$cwt = ($weight-$tmp) / 112;
	$tmp2 = $tmp;
	$tmp = $tmp2 % 28;
	$qtr = ($tmp2-$tmp) / 28;
	if( $weightApprox == true && $tmp == 0 && $qtr == 0 ) {
		return $cwt.'cwt';
	}
	else {
		return $cwt.'-'.$qtr.'-'.$tmp;
	}
}

function mysql_escape_mimic( $inp ) {
	if( !empty( $inp ) && is_string( $inp ) ) {
		return str_replace( array( '\\', "\0", "\n", "\r", "'", '"', "\x1a" ), array( '\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z' ), $inp );
	}
	return $inp;
}

// Tidy up data values
function tidyTower( $tower ) {
	global $counties, $welshAreas, $scottishAreas, $irishAreas, $states, $canadianStates, $australianAreas, $newZealandAreas, $southAfricanAreas;

	// Expand shortened county/region names
	if( empty( $tower['Country'] ) ) { $tower['Country'] = 'England'; }
	if( $tower['Country'] == 'Channel Is' ) { $tower['Country'] = 'Channel Isles'; }
	
	if( $tower['County'] == '(none)' ) { $tower['County'] = ''; }
	else {
		switch( $tower['Country'] ) {
			case 'England':
				$tower['County'] = longCounty( $tower['County'], $counties );
				break;
			case 'Wales':
				$tower['County'] = longCounty( $tower['County'], $welshAreas );
				break;
			case 'Scotland':
				$tower['County'] = longCounty( $tower['County'], $scottishAreas );
				break;
			case 'Ireland':
				$tower['County'] = longCounty( $tower['County'], $irishAreas );
				break;
			case 'USA':
				$tower['County'] = longCounty( $tower['County'], $states );
				break;
			case 'Canada':
				$tower['County'] = longCounty( $tower['County'], $canadianStates );
				break;
			case 'Australia':
				$tower['County'] = longCounty( $tower['County'], $australianAreas );
				break;
			case 'New Zealand':
				$tower['County'] = longCounty( $tower['County'], $newZealandAreas );
				break;
			case 'Africa':
				if( $tower['County'] == 'Zimb' ) { $tower['County'] = 'Zimbabwe'; }
				break;
			case 'South Africa':
				$tower['County'] = longCounty( $tower['County'], $southAfricanAreas );
				break;
			case 'Netherlands':
				if( $tower['County'] == 'S Holland' ) { $tower['County'] = 'South Holland'; }
				break;
			case 'Windward Is':
				$tower['Country'] = 'Windward Islands';
				$tower['County'] = $tower['AltName'];
				$tower['AltName'] = '';
				break;
		}
	}
	
	// Sort out dedication abbreviations
	if( !empty( $tower['Dedicn'] ) ) {
		$tower['Dedicn'] = str_replace( 
			array( 'RC ',             'SS ',  'S ',  'SSs ', 'Cath ',      'Cath,',      'P Church',      'Ch ',     ' ch ',     ' K&M',             ' Gt',        'John Bapt',        ' Magd',     'Senara V',          'Mary V',          'BVM',                 'BV',             'Nativity St',    ' of Blessed',     '& Blessed',     'SMV',                'John Ev',             'Mark Ev',             'James Ap',          'Andrew Ap',          'Thomas Ap',          ' A&M',                    ' V&M',                   ' B&M',                   'Margaret Q',         'Edward Conf & K',               'Edward Conf',          'Edward M',          'George M',          'Thomas M',          'Stephen M',          'Laurence M',          'Matthew AEM' ),
			array( 'Roman Catholic ', 'SSs ', 'St ', 'SS ',  'Cathedral ', 'Cathedral,', 'Parish Church', 'Church ', ' church ', ' King and Martyr', ' the Great', 'John the Baptist', ' Magdalen', 'Senara the Virgin', 'Mary the Virgin', 'Blessed Virgin Mary', 'Blessed Virgin', 'Nativity of St', ' of the Blessed', '& the Blessed', 'St Mary the Virgin', 'John the Evangelist', 'Mark the Evangelist', 'James the Apostle', 'Andrew the Apostle', 'Thomas the Apostle', ' the Apostle and Martyr', ' the Virgin and Martyr', ' the Bishop and Martyr', 'Margaret the Queen', 'Edward the Confessor and King', 'Edward the Confessor', 'Edward the Martyr', 'George the Martyr', 'Thomas the Martyr', 'Stephen the Martyr', 'Laurence the Martyr', 'Matthew the Apostle, Evangelist and Martyr' ),
			$tower['Dedicn'] );
		$tower['Dedicn'] = preg_replace( 
			array( '/Cath$/',   '/Ch$/' ),
			array( 'Cathedral', 'Church' ),
			$tower['Dedicn'] );
	}
	if( $tower['Dedicn'] == '(unknown)' ) { $tower['Dedicn'] = 'Unknown'; }
	
	// Tidy up some abbreviated dioceses
	switch( $tower['Diocese'] ) {
		case 'PrivOwnership':
			$tower['Diocese'] = 'Private Ownership';
			break;
		case '(Not Anglican)':
			$tower['Diocese'] = 'Non-Anglican';
			break;
		case 'AnglicanNonUK':
			$tower['Diocese'] = 'Anglican (Non-UK)';
			break;
		case 'ExtraParochial':
			$tower['Diocese'] = 'Extra-Parochial';
			break;
		case '(RC)':
			$tower['Diocese'] = 'Roman Catholic';
			break;
		case '(Ireland)':
			$tower['Diocese'] = 'Ireland';
			break;
		case '(Scotland)':
			$tower['Diocese'] = 'Scotland';
			break;
		case 'ChConsvnTrust':
			$tower['Diocese'] = 'Churches Conservation Trust';
			break;
		case 'LocalAuthority':
			$tower['Diocese'] = 'Local Authority';
			break;
		case 'Trust (nonCCT)':
			$tower['Diocese'] = 'Trust (non-CCT)';
			break;
		case 'St Eds and Ips':
			$tower['Diocese'] = 'St Edmundsbury and Ipswich';
			break;
		case 'RiponAndLeeds':
			$tower['Diocese'] = 'Ripon and Leeds';
			break;
		case 'SouthwellNottm':
			$tower['Diocese'] = 'Southwell and Nottingham';
			break;
		case 'SwanseaBrecon':
			$tower['Diocese'] = 'Swansea and Brecon';
			break;
	}
	
	// And some abbreviated contractors
	switch( $tower['Contractor'] ) {
		case 'Local labour/guild/assn':
			$tower['Contractor'] = 'Local labour';
			break;
		case '(The owner)':
			$tower['Contractor'] = 'The Owner';
			break;
		case 'Merseyside Bell Restn Group':
			$tower['Contractor'] = 'Merseyside Bell Restoration Group';
			break;
	}
	// Check altName isn't unnecessary
	if( $tower['Place'] == $tower['AltName'] ) { $tower['AltName'] = ''; }
	
	// Concat $place2 and $place
	if( !empty( $tower['Place2'] ) ) {
		$tower['Place'] .= ', '.$tower['Place2'];
	}
	$tower['Place'] = str_replace( 'The,', 'The', implode( ', ', array_reverse( explode(  ', ', $tower['Place'] ) ) ) );

	// Escape values for SQL
	$rowData = array();
	$rowData['doveid'] = "'".mysql_escape_mimic( str_replace( ' ', '_', trim( $tower['DoveID'] ) ) )."'";
	$rowData['gridReference'] = "'".mysql_escape_mimic( $tower['NG'] )."'";
	$rowData['latitude'] = floatval( $tower['Lat'] );
	$rowData['longitude'] = floatval( $tower['Long'] );
	$rowData['latitudeSatNav'] = floatval( $tower['SNLat'] );
	$rowData['longitudeSatNav'] = floatval( $tower['SNLong'] );
	$rowData['postcode'] = "'".mysql_escape_mimic( $tower['Postcode'] )."'";
	$rowData['country'] = "'".mysql_escape_mimic( $tower['Country'] )."'";
	$rowData['county'] = "'".mysql_escape_mimic( $tower['County'] )."'";
	$rowData['diocese'] = "'".mysql_escape_mimic( $tower['Diocese'] )."'";
	$rowData['place'] = "'".mysql_escape_mimic( $tower['Place'] )."'";
	$rowData['altName'] = "'".mysql_escape_mimic( $tower['AltName'] )."'";
	$rowData['dedication'] = "'".mysql_escape_mimic( $tower['Dedicn'] )."'";
	$rowData['bells'] = intval( $tower['Bells'] );
	$rowData['weight'] = intval( $tower['Wt'] );
	$rowData['weightApprox'] = empty( $tower['App'] )? 0 : 1;
	$rowData['weightText'] = "'".weightText( $rowData['weight'], $rowData['weightApprox'] )."'";
	$rowData['note'] = "'".mysql_escape_mimic( $tower['Note'] )."'";
	$rowData['hz'] = floatval( $tower['Hz'] );
	$rowData['practiceNight'] = intval( $tower['PDNo'] );
	$rowData['practiceStart'] = "'".mysql_escape_mimic( $tower['PSt'] )."'";
	$rowData['practiceNotes'] = "'".mysql_escape_mimic( $tower['PrXF'] )."'";
	$rowData['groundFloor'] = empty( $tower['GF'] )? 0 : 1;
	$rowData['toilet'] = empty( $tower['Toilet'] )? 0 : 1;
	$rowData['unringable'] = empty( $tower['UR'] )? 0 : 1;
	$rowData['simulator'] = empty( $tower['Simulator'] )? 0 : 1;
	$rowData['overhaulYear'] = intval( $tower['OvhaulYr'] );
	$rowData['contractor'] = "'".mysql_escape_mimic( $tower['Contractor'] )."'";
	$rowData['tuned'] = intval( $tower['TuneYr'] );
	$rowData['extraInfo'] = "'".mysql_escape_mimic( $tower['ExtraInfo'] )."'";
	$rowData['webPage'] = "'".mysql_escape_mimic( $tower['WebPage'] )."'";
	$rowData = array_filter( $rowData, function( $e ) { return ( !empty( $e ) && $e != '\'\'' ); } );

	return $rowData;
}

<?php
namespace Utilities;
require( dirname(dirname(dirname(dirname(__FILE__)))).'/libraries/Helpers/abbreviations.php' );
require( dirname(dirname(dirname(dirname(__FILE__)))).'/libraries/parsecsv.lib.php' );
use \Helpers, \parseCSV;
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

function longCounty( $lookup, array $array ) {
	if( array_key_exists( $lookup, $array ) ) { return $array[$lookup]; 	}
	elseif( in_array( $lookup, $array ) ) { return $lookup; }
	else {
		trigger_error( 'No full county for: '.$tower['County'], E_USER_ERROR );
	}
}

date_default_timezone_set( 'UTC' );

header( 'Content-type: text/plain' );
header( 'Content-Disposition: inline; filename="towers.sql"' );


?>
-- Tower Library
-- Generated on: <?php echo date( 'Y/m/d' ); ?>

-- Generated from CCCBR data from http://dove.cccbr.org.uk

-- Set up tower_oldpks table
DROP TABLE IF EXISTS tower_oldpks;
CREATE TABLE IF NOT EXISTS tower_oldpks (
  oldpk varchar(10) NOT NULL,
  tower_doveId varchar(10) NOT NULL,
  PRIMARY KEY (oldpk),
  UNIQUE KEY (tower_doveId)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Set up towers table
DROP TABLE IF EXISTS towers;
CREATE TABLE IF NOT EXISTS towers (
  doveId varchar(10) NOT NULL, PRIMARY KEY (doveId),
  gridReference varchar(10),
  latitude decimal(8,5), INDEX (latitude),
  longitude decimal(8,5), INDEX (longitude),
  latitudeSatNav decimal(8,5),
  longitudeSatNav decimal(8,5),
  postcode varchar(10),
  country varchar(255) NOT NULL, INDEX (country),
  county varchar(255), INDEX (county),
  diocese varchar(255), INDEX (diocese),
  place varchar(255) NOT NULL,
  altName varchar(255),
  dedication varchar(255),
  bells tinyint NOT NULL, INDEX (bells),
  weight smallint, INDEX (weight),
  weightApprox bit,
  weightText varchar(20),
  note varchar(2),
  hz decimal(5,1),
  practiceNight tinyint, INDEX (practiceNight),
  practiceStart varchar(5),
  practiceNotes text,
  groundFloor bit, INDEX (groundFloor),
  toilet bit, INDEX (toilet),
  unringable bit, INDEX (unringable),
  simulator bit, INDEX (simulator),
  overhaulYear smallint DEFAULT NULL,
  contractor varchar(255) DEFAULT NULL,
  tuned smallint DEFAULT NULL,
  extraInfo text DEFAULT NULL,
  webPage text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Set up a towers fusion table
DROP TABLE IF EXISTS towersFusion;
CREATE TABLE IF NOT EXISTS towersFusion (
  doveId varchar(10) NOT NULL,
  country varchar(255) NOT NULL,
  county varchar(255),
  diocese varchar(255),
  dedication varchar(255) NOT NULL,
  place varchar(255) NOT NULL,
  location varchar(255),
  bells tinyint NOT NULL,
  weight smallint,
  weightText varchar(20),
  note varchar(2),
  affiliations varchar(255),
  practiceNight tinyint,
  groundFloor bit,
  toilet bit,
  unringable bit,
  simulator bit,
  marker varchar(63)
) ENGINE=MyISAM DEFAULT CHARSET=utf8, COMMENT = 'Export into CSV for importing into Google Fusion Tables';

-- Set up associations_towers table
DROP TABLE IF EXISTS associations_towers;
CREATE TABLE IF NOT EXISTS associations_towers (
  association_abbreviation varchar(10) NOT NULL, INDEX (association_abbreviation),
  tower_doveId varchar(10) NOT NULL, INDEX (tower_doveId)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

<?php

// tower_oldpks data from data/newpks.txt
$newpks = new parseCSV();
$newpks->auto( __DIR__.'/data/newpks.txt' );
echo "INSERT INTO tower_oldpks (oldpk,tower_doveId) VALUES\n" .
	implode( ",\n", array_map( function( $row ) {
		return "\t('".sqlite_escape_string( str_replace( ' ', '_', trim( $row['OldID'] ) ) )."','".sqlite_escape_string( str_replace( ' ', '_', trim( $row['NewID'] ) ) ).'\')';
	}, $newpks->data ) ) .
	";\n";
unset( $newpks );


// Other data from data/dove.txt
$dove = new parseCSV();
$dove->auto( __DIR__.'/data/dove.txt' );
foreach( $dove->data as $tower ) {
	// Tidy up data values
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
			array( 'RC ',             'SS ',  'S ',  'SSs ', 'Cath ',      'Cath,',      'P Church',      'Ch ',     ' ch ',     ' K&M',             ' Gt',        'John Bapt',        ' Magd',     'Senara V',          'Mary V',          'BVM',                 'BV',             'Nativity St',    ' of Blessed',     '& Blessed',     'SMV',                'John Ev',             'Mark Ev',             'James Ap',          'Andrew Ap',          'Thomas Ap',          ' A&M',                    ' V&M',                   ' B&M',                    'Margaret Q',         'Edward Conf & K',                'Edward Conf',          'Edward M',          'George M',         'Thomas M',          'Stephen M',          'Laurence M',          'Matthew AEM' ),
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
		case 'ChConsvnTrust':
			$tower['Diocese'] = 'Churches Conservation Trust';
			break;
		case 'St Eds and Ips':
			$tower['Diocese'] = 'St Edmundsbury and Ipswich';
			break;
	}
	
	// And some abbreviated contractors
	switch( $tower['Contractor'] ) {
		case 'Local labour/guild/assn':
			$tower['Contractor'] = 'Local labour';
			break;
		case '(The owner)':
			$tower['Contractor'] = 'Owner';
			break;
		case 'Merseyside Bell Restn Group':
			$tower['Contractor'] = 'Merseyside Bell Restoration Group';
			break;
	}
	// Check altName
	if( $tower['Place'] == $tower['AltName'] ) { $tower['AltName'] = ''; }
	
	// Concat $place2 and $place
	if( !empty( $tower['Place2'] ) ) {
		$tower['Place'] .= ', '.$tower['Place2'];
	}
	$tower['Place'] = str_replace( 'The,', 'The', implode( ', ', array_reverse( explode(  ', ', $tower['Place'] ) ) ) );

	// Escape values for SQL
	$rowData = array();
	$rowData['doveId'] = "'".sqlite_escape_string( str_replace( ' ', '_', trim( $tower['DoveID'] ) ) )."'";
	$rowData['gridReference'] = "'".sqlite_escape_string( $tower['NG'] )."'";
	$rowData['latitude'] = floatval( $tower['Lat'] );
	$rowData['longitude'] = floatval( $tower['Long'] );
	$rowData['latitudeSatNav'] = floatval( $tower['SNLat'] );
	$rowData['longitudeSatNav'] = floatval( $tower['SNLong'] );
	$rowData['postcode'] = "'".sqlite_escape_string( $tower['Postcode'] )."'";
	$rowData['country'] = "'".sqlite_escape_string( $tower['Country'] )."'";
	$rowData['county'] = "'".sqlite_escape_string( $tower['County'] )."'";
	$rowData['diocese'] = "'".sqlite_escape_string( $tower['Diocese'] )."'";
	$rowData['place'] = "'".sqlite_escape_string( $tower['Place'] )."'";
	$rowData['altName'] = "'".sqlite_escape_string( $tower['AltName'] )."'";
	$rowData['dedication'] = "'".sqlite_escape_string( $tower['Dedicn'] )."'";
	$rowData['bells'] = intval( $tower['Bells'] );
	$rowData['weight'] = intval( $tower['Wt'] );
	$rowData['weightApprox'] = empty( $tower['App'] )? 0 : 1;
	$rowData['weightText'] = "'".weightText( $rowData['weight'], $rowData['weightApprox'] )."'";
	$rowData['note'] = "'".sqlite_escape_string( $tower['Note'] )."'";
	$rowData['hz'] = floatval( $tower['Hz'] );
	$rowData['practiceNight'] = intval( $tower['PDNo'] );
	$rowData['practiceStart'] = "'".sqlite_escape_string( $tower['PSt'] )."'";
	$rowData['practiceNotes'] = "'".sqlite_escape_string( $tower['PrXF'] )."'";
	$rowData['groundFloor'] = empty( $tower['GF'] )? 0 : 1;
	$rowData['toilet'] = empty( $tower['Toilet'] )? 0 : 1;
	$rowData['unringable'] = empty( $tower['UR'] )? 0 : 1;
	$rowData['simulator'] = empty( $tower['Simulator'] )? 0 : 1;
	$rowData['overhaulYear'] = intval( $tower['OvhaulYr'] );
	$rowData['contractor'] = "'".sqlite_escape_string( $tower['Contractor'] )."'";
	$rowData['tuned'] = intval( $tower['TuneYr'] );
	$rowData['extraInfo'] = "'".sqlite_escape_string( $tower['ExtraInfo'] )."'";
	$rowData['webPage'] = "'".sqlite_escape_string( $tower['WebPage'] )."'";
	$rowData = array_filter( $rowData, function( $e ) { return ( !empty( $e ) && $e != '\'\'' ); } );
	
	// towers INSERT
 	echo 'INSERT INTO towers ('.implode( ', ', array_keys( $rowData ) ).') VALUES ('.implode( ', ', $rowData ).");\n";
	
	// fusionTowers INSERT
	if( isset( $rowData['latitude'], $rowData['longitude'] ) ) {
		$rowData['location'] = '\''.$rowData['latitude'].','.$rowData['longitude'].'\'';
		$rowData['marker'] = ( isset( $rowData['unringable'] ) == 1 )? "'measle_white'" : (
			( $rowData['bells'] <= 4 )? "'measle_brown'" : (
			( $rowData['bells'] == 5 )? "'small_yellow'" : (
			( $rowData['bells'] == 6 )? "'measle_turquoise'" : (
			( $rowData['bells'] <= 8 )? "'small_green'" : (
			( $rowData['bells'] <= 10 )? "'small_blue'" : (
			( $rowData['bells'] <= 12 )? "'small_purple'" : (
			"'small_red'" ) ) ) ) ) ) );
		unset( $rowData['gridReference'], $rowData['latitude'],$rowData['longitude'], $rowData['latitudeSatNav'], $rowData['longitudeSatNav'], $rowData['postcode'], $rowData['countryCode'], $rowData['altName'], $rowData['weightApprox'], $rowData['hz'], $rowData['practiceStart'], $rowData['practiceNotes'], $rowData['overhaulYear'], $rowData['contractor'], $rowData['tuned'], $rowData['extraInfo'], $rowData['webPage'] );
		echo 'INSERT INTO towersFusion ('.implode( ', ', array_keys( $rowData ) ).') VALUES ('.implode( ', ', $rowData ).");\n";
	}
	
	// Association links
	if( !empty( $tower['Affiliations'] ) ) {
		foreach( explode( ',', $tower['Affiliations'] ) as $link ) {
			echo 'INSERT INTO associations_towers (association_abbreviation, tower_doveId) VALUES (\''.sqlite_escape_string( $link ).'\', '.$rowData['doveId'].');'."\n";
		}
	}
}
?>

OPTIMIZE TABLE towers;
OPTIMIZE TABLE associations_towers;

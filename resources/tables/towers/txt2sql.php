<?php
require( dirname(__FILE__).'/../../../vendor/blueline/abbreviations.php' );
require( dirname(__FILE__).'/../../../vendor/blueline/parsecsv.lib.php' );

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
		trigger_error( 'No full county for: '.$lookup, E_USER_ERROR );
	}
}

date_default_timezone_set( 'UTC' );

header( 'Content-type: text/plain' );
header( 'Content-Disposition: inline; filename="towers.sql"' );

?>
-- Tower Library
-- Generated on: <?php echo date( 'Y/m/d' ); ?>

-- Generated from CCCBR data from http://dove.cccbr.org.uk

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

-- Set up tower_oldpks table
DROP TABLE IF EXISTS `tower_oldpks`;
CREATE TABLE IF NOT EXISTS `tower_oldpks` (
  `oldpk` varchar(10) NOT NULL,
  `tower_doveId` varchar(10) NOT NULL,
  PRIMARY KEY (`oldpk`),
  KEY `tower_doveId` (`tower_doveid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Set up towers table
DROP TABLE IF EXISTS `towers`;
CREATE TABLE IF NOT EXISTS `towers` (
  `doveid` varchar(10) NOT NULL,
  `gridReference` varchar(10) DEFAULT NULL,
  `latitude` decimal(8,5) DEFAULT NULL,
  `longitude` decimal(8,5) DEFAULT NULL,
  `latitudeSatNav` decimal(8,5) DEFAULT NULL,
  `longitudeSatNav` decimal(8,5) DEFAULT NULL,
  `postcode` varchar(10) DEFAULT NULL,
  `country` varchar(255) NOT NULL,
  `county` varchar(255) DEFAULT NULL,
  `diocese` varchar(255) DEFAULT NULL,
  `place` varchar(255) NOT NULL,
  `altName` varchar(255) DEFAULT NULL,
  `dedication` varchar(255) DEFAULT NULL,
  `bells` tinyint(4) NOT NULL,
  `weight` smallint(6) DEFAULT NULL,
  `weightApprox` tinyint(1) DEFAULT NULL,
  `weightText` varchar(20) DEFAULT NULL,
  `note` varchar(2) DEFAULT NULL,
  `hz` decimal(5,1) DEFAULT NULL,
  `practiceNight` tinyint(4) DEFAULT NULL,
  `practiceStart` varchar(5) DEFAULT NULL,
  `practiceNotes` text,
  `groundFloor` tinyint(1) DEFAULT NULL,
  `toilet` tinyint(1) DEFAULT NULL,
  `unringable` tinyint(1) DEFAULT NULL,
  `simulator` tinyint(1) DEFAULT NULL,
  `overhaulYear` smallint(6) DEFAULT NULL,
  `contractor` varchar(255) DEFAULT NULL,
  `tuned` smallint(6) DEFAULT NULL,
  `extraInfo` text,
  `webPage` text,
  PRIMARY KEY (`doveId`),
  KEY `latitude` (`latitude`),
  KEY `longitude` (`longitude`),
  KEY `country` (`country`),
  KEY `county` (`county`),
  KEY `diocese` (`diocese`),
  KEY `bells` (`bells`),
  KEY `weight` (`weight`),
  KEY `practiceNight` (`practiceNight`),
  KEY `groundFloor` (`groundFloor`),
  KEY `toilet` (`toilet`),
  KEY `unringable` (`unringable`),
  KEY `simulator` (`simulator`)
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
DROP TABLE IF EXISTS `associations_towers`;
CREATE TABLE IF NOT EXISTS `associations_towers` (
  `association_abbreviation` varchar(10) NOT NULL COMMENT 'Abbreviation of association',
  `tower_doveid` varchar(10) NOT NULL COMMENT 'Dove ID of an affiliated tower',
  PRIMARY KEY (`association_abbreviation`,`tower_doveid`),
  KEY `association_abbreviation` (`association_abbreviation`),
  KEY `tower_doveid` (`tower_doveid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

<?php

// tower_oldpks data from data/newpks.txt
$newpksFile = new parseCSV();
$newpksFile->auto( __DIR__.'/data/newpks.txt' );

// Extract required data
$oldpks = array_map( function( $row ) { return mysql_escape_mimic( str_replace( ' ', '_', trim( $row['OldID'] ) ) ); }, $newpksFile->data );
$newpks = array_map( function( $row ) { return mysql_escape_mimic( str_replace( ' ', '_', trim( $row['NewID'] ) ) ); }, $newpksFile->data );
unset( $newpksFile );

// Prevent entries appearing as a newPK when they appear as an oldPK themselves
$foundWrongEntry = true;
while( $foundWrongEntry ) {
	$foundWrongEntry = false;
	for( $i = 0, $iLim = count( $oldpks ); $i < $iLim; ++$i ) {
		$newOldPK = array_search( $newpks[$i], $oldpks );
		if( $newOldPK !== false ) {
			$newpks[$i] = $newpks[$newOldPK];
			$foundWrongEntry = true;
		}
	}
}

echo "INSERT INTO `tower_oldpks` (`oldpk`, `tower_doveid`) VALUES\n";
for( $i = 0, $iLim = count( $oldpks ); $i < $iLim; ++$i ) {
	echo (($i==0)?'':",\n")."\t('{$oldpks[$i]}','{$newpks[$i]}')";
}
echo ';';
unset( $oldpks, $newpks, $i, $iLim );

// Other data from data/dove.txt
$doveFile = new parseCSV();
$doveFile->auto( __DIR__.'/data/dove.txt' );
foreach( $doveFile->data as $tower ) {
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
	
	// towers INSERT
 	echo 'INSERT INTO `towers` (`'.implode( '`, `', array_keys( $rowData ) ).'`) VALUES ('.implode( ', ', $rowData ).");\n";
	
	// fusionTowers INSERT
	if( isset( $rowData['latitude'], $rowData['longitude'] ) ) {
		$rowData['affiliations'] = "'".mysql_escape_mimic( $tower['Affiliations'] )."'";
		$rowData['location'] = '\''.$rowData['latitude'].','.$rowData['longitude'].'\'';
		$rowData['marker'] = ( isset( $rowData['unringable'] ) == 1 )? "'measle_white'" : (
			( $rowData['bells'] <= 4 )? "'measle_brown'" : (
			( $rowData['bells'] == 5 )? "'small_yellow'" : (
			( $rowData['bells'] == 6 )? "'measle_turquoise'" : (
			( $rowData['bells'] <= 8 )? "'small_green'" : (
			( $rowData['bells'] <= 10 )? "'small_blue'" : (
			( $rowData['bells'] <= 12 )? "'small_purple'" : (
			"'small_red'" ) ) ) ) ) ) );
		// Remove unwanted data
		unset( $rowData['gridReference'], $rowData['latitude'], $rowData['longitude'], $rowData['latitudeSatNav'], $rowData['longitudeSatNav'], $rowData['postcode'], $rowData['countryCode'], $rowData['altName'], $rowData['weightApprox'], $rowData['hz'], $rowData['practiceStart'], $rowData['practiceNotes'], $rowData['overhaulYear'], $rowData['contractor'], $rowData['tuned'], $rowData['extraInfo'], $rowData['webPage'] );
		echo 'INSERT INTO towersFusion ('.implode( ', ', array_keys( $rowData ) ).') VALUES ('.implode( ', ', $rowData ).");\n";
	}
	
	// associations_towers data
	if( !empty( $tower['Affiliations'] ) ) {
		foreach( explode( ',', $tower['Affiliations'] ) as $link ) {
			echo 'INSERT INTO `associations_towers` (`association_abbreviation`, `tower_doveid`) VALUES (\''.mysql_escape_mimic( $link ).'\', '.$rowData['doveid'].');'."\n";
		}
	}
}

function mysql_escape_mimic( $inp ) {
	if( !empty( $inp ) && is_string( $inp ) ) {
		return str_replace( array( '\\', "\0", "\n", "\r", "'", '"', "\x1a" ), array( '\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z' ), $inp );
	}
	return $inp;
}
?>
-- End

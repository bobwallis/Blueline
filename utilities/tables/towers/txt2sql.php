<?php
namespace ringing;
require( dirname(dirname(dirname(dirname(__FILE__)))).'/vendors/ringing/abbreviations.php' );

date_default_timezone_set( 'UTC' );

header( 'Content-type: text/plain' );
header( 'Content-Disposition: inline; filename="towers.sql"' );

// Initialise variables
$associations_towers = '';

// Header for towers_newpks table
?>
-- Tower Library
-- Generated on: <?php echo date( 'Y/m/d' ); ?>

-- Generated from CCCBR data from http://dove.cccbr.org.uk

-- Set up tower_oldpks table
DROP TABLE IF EXISTS tower_oldpks;
CREATE TABLE IF NOT EXISTS tower_oldpks (
  id varchar(10) NOT NULL,
  tower_id varchar(10) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY (tower_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

<?php
$printedHeader = false; // The INSERT header that is
// Open file
$handle = fopen( 'data/newpks.txt', 'r' );
if( $handle ) {
	fgets( $handle ); // First row contains columns
	while( !feof( $handle ) ) {
		$buffer = fgets( $handle );
		if( !$buffer ) { continue; }
		list(
			$id,
			$tower_id
		) =  explode( '\\', $buffer );
		
		// Make an array of the data
		$rowData = compact(
			'id',
			'tower_id'
		);
		// Escape for SQL
		foreach( array( 'id', 'tower_id' ) as $k ) {
			if( !empty( $rowData[$k] ) ) { $rowData[$k] = "'".sqlite_escape_string( str_replace( ' ', '_', trim( $rowData[$k] ) ) )."'"; }
			else { $rowData[$k] = 'NULL'; }
		}
		// Print statement
		if( !$printedHeader ) {
			echo 'INSERT INTO tower_oldpks ('.implode( ', ', array_keys( $rowData ) ).') VALUES'."\n".'('.implode( ', ', $rowData ).')';
			$printedHeader = true;
		}
		else {
			echo ",\n".'('.implode( ', ', $rowData ).')';
		}
	}
	echo ";\n";
fclose( $handle );
}

// Some initial header information for the towers table
?>

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
  affiliations varchar(255), INDEX (affiliations),
  place varchar(255) NOT NULL,
  altName varchar(255),
  dedication varchar(255),
  URL varchar(255) NOT NULL UNIQUE,
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
  URL varchar(255) NOT NULL,
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
  marker varchar(12)
) ENGINE=MyISAM DEFAULT CHARSET=utf8, COMMENT = 'Export into CSV for importing into Google Fusion Tables';
<?php

// Open file
$handle = fopen( 'data/dove.txt', 'r' );
if( $handle ) {
	fgets( $handle ); // First row contains columns
	while( !feof( $handle ) ) {
		$buffer = fgets( $handle );
		if( !$buffer ) { continue; }
		list(
			$doveId,			
			$gridReference,
			$latitudeSatNav,
			$longitudeSatNav,
			$postcode,
			$towerBase,
			$county,
			$country,
			$countryCode,
			$diocese,
			$place,
			$place2,
			$placeCL,
			$dedication,
			$bells,
			$weight,
			$weightApprox,
			$note,
			$hz,
			$details,
			$groundFloor,
			$toilet,
			$unringable,
			$practiceNight,
			$practiceNightText,
			$practiceStart,
			$practiceNotes,
			$overhaulYear,
			$contractor,
			$tunedYear,
			$extraInfo,
			$webPage,
			$updated,
			$affiliations,
			$altName,
			$simulator,
			$latitude,
			$longitude
		) =  explode( '\\', $buffer );
		
		// Set boolean values
		if( !empty( $weightApprox ) ) { $weightApprox = true; }
		else { $weightApprox = false; }
		if( !empty( $groundFloor ) ) { $groundFloor = true; }
		else { $groundFloor = false; }
		if( !empty( $toilet ) ) { $toilet = true; }
		else { $toilet = false; }
		if( !empty( $unringable ) ) { $unringable = true; }
		else { $unringable = false; }
		if( !empty( $tuned ) ) { $tuned = true; }
		else { $tuned = false; }
		if( strpos( $simulator, 'T' ) !== false ) { $simulator = true; }
		else { $simulator = false; }
		
		// Replace spaces with underscores in doveID
		$doveId = str_replace( ' ', '_', $doveId );
		
		// Expand shortened county/region names
		if( empty( $country ) ) { $country = 'England'; }
		if( $country == 'Windward Is' ) { $country = 'Windward Isles'; }
		elseif( $country == 'Channel Is' ) { $country = 'Channel Isles'; }
		
		if( $county == '(none)' ) { $county = ''; }
		elseif( $country == 'England' ) {
			if( isset( $counties[$county] ) ) { $county = $counties[$county]; }
			else { die( 'No full county for: '.$county ); }
		}
		elseif( $country == 'Wales' ) {
			if( isset( $welshAreas[$county] ) ) { $county = $welshAreas[$county]; }
			else { die( 'No full county for Welsh region: '.$county ); }
		}
		elseif( $country == 'Scotland' ) {
			if( isset( $scottishAreas[$county] ) ) { $county = $scottishAreas[$county]; }
			else { die( 'No full county for Scottish region: '.$county ); }
		}
		elseif( $country == 'Ireland' ) {
			if( isset( $irishAreas[$county] ) ) { $county = $irishAreas[$county]; }
			else { die( 'No full county for Irish region: '.$county ); }
		}
		elseif( $country == 'USA' ) {
			if( isset( $states[$county] ) ) { $county = $states[$county]; }
			else { die( 'No full county for USA region: '.$county ); }
		}
		elseif( $country == 'Canada' ) {
			if( isset( $canadianStates[$county] ) ) { $county = $canadianStates[$county]; }
			else { die( 'No full county for Canadian region: '.$county ); }
		}
		elseif( $country == 'Australia' ) {
			if( isset( $australianAreas[$county] ) ) { $county = $australianAreas[$county]; }
			else { die( 'No full county for Australian region: '.$county ); }
		}
		elseif( $country == 'New Zealand' ) {
			if( isset( $newZealandAreas[$county] ) ) { $county = $newZealandAreas[$county]; }
			else { die( 'No full county for New Zealand region: '.$county ); }
		}
		elseif( $country == 'Africa' ) {
			if( $county == 'Zimb' ) { $county = 'Zimbabwe'; }
		}
		elseif( $country == 'South Africa' ) {
			if( isset( $southAfricanAreas[$county] ) ) { $county = $southAfricanAreas[$county]; }
			else { die( 'No full county for South Africa region: '.$county ); }
		}
		elseif( $country == 'Netherlands' ) {
			if( $county == 'S Holland' ) { $county = 'South Holland'; }
		}
		elseif( $country == 'Windward Isles' ) {
			$country = 'Windward Islands';
			$county = $altName;
			$altName = '';
		}
		
		// Sort out dedication abbreviations
		if( !empty( $dedication ) ) {
			$dedication = str_replace( 
				array( 'RC ',             'SS ',  'S ',  'SSs ', 'Cath ',      'Cath,',      'P Church',      'Ch ',     ' ch ',     ' K&M',             ' Gt',        'John Bapt',        ' Magd',     'Senara V',          'Mary V',          'BVM',                 'BV',             'Nativity St',    ' of Blessed',     '& Blessed',     'SMV',                'John Ev',             'Mark Ev',             'James Ap',          'Andrew Ap',          'Thomas Ap',          ' A&M',                    ' V&M',                   ' B&M',                    'Margaret Q',         'Edward Conf & K',                'Edward Conf',          'Edward M',          'George M',         'Thomas M',          'Stephen M',          'Laurence M',          'Matthew AEM' ),
				array( 'Roman Catholic ', 'SSs ', 'St ', 'SS ',  'Cathedral ', 'Cathedral,', 'Parish Church', 'Church ', ' church ', ' King and Martyr', ' the Great', 'John the Baptist', ' Magdalen', 'Senara the Virgin', 'Mary the Virgin', 'Blessed Virgin Mary', 'Blessed Virgin', 'Nativity of St', ' of the Blessed', '& the Blessed', 'St Mary the Virgin', 'John the Evangelist', 'Mark the Evangelist', 'James the Apostle', 'Andrew the Apostle', 'Thomas the Apostle', ' the Apostle and Martyr', ' the Virgin and Martyr', ' the Bishop and Martyr', 'Margaret the Queen', 'Edward the Confessor and King', 'Edward the Confessor', 'Edward the Martyr', 'George the Martyr', 'Thomas the Martyr', 'Stephen the Martyr', 'Laurence the Martyr', 'Matthew the Apostle, Evangelist and Martyr' ),
				$dedication );
			$dedication = preg_replace( 
				array( '/Cath$/',   '/Ch$/' ),
				array( 'Cathedral', 'Church' ),
				$dedication );
		}
		if( $dedication == '(unknown)' ) {
			$dedication = 'Unknown';
		}
		
		// Tidy up some abbreviated dioceses
		if( $diocese == 'PrivOwnership' ) { $diocese = 'Private Ownership'; }
		elseif( $diocese == '(Not Anglican)' ) { $diocese = 'Non-Anglican'; }
		elseif( $diocese == 'AnglicanNonUK' ) { $diocese = 'Anglican (Non-UK)'; }
		elseif( $diocese == '(RC)' ) { $diocese = 'Roman Catholic'; }
		elseif( $diocese == '(Ireland)' ) { $diocese = 'Ireland'; }
		elseif( $diocese == 'ChConsvnTrust' ) { $diocese = 'Churches Conservation Trust'; }
		elseif( $diocese == 'St Eds and Ips' ) { $diocese = 'St Edmundsbury and Ipswich'; }
		
		// And some abbreviated contractors
		if( $contractor == 'Local labour/guild/assn' ) { $contractor = 'Local labour'; }
		elseif( $contractor == '(The owner)' ) { $contractor = 'Owner'; }
		elseif( $contractor == 'Merseyside Bell Restn Group' ) { $contractor = 'Merseyside Bell Restoration Group'; }
		
		// Check altName
		if( $place == $altName ) { $altName = ''; }
		
		// Build displayString
		$URL_a = array();
		if( !empty( $place2 ) ) { array_push( $URL_a, $place2 ); }
		elseif( !empty( $place ) ) { array_push( $URL_a, $place ); }
		if( !empty( $dedication ) ) { array_push( $URL_a, '('.$dedication.')' ); }
		if( !empty( $county ) ) { array_push( $URL_a, $county ); }
		$URL = str_replace( array( ' ', '&' ), array( '_', 'and' ), implode( '_', $URL_a ) );
		
		// Special cases
		if( $place == 'Drayton' && $bells == 3 ) { $URL .= '_(3)'; }
		if( $place == 'St Pierre du Bois' && $bells == 3 ) { $URL .= '_(3)'; }
		
		// Concat $place2 and $place
		if( !empty( $place2 ) ) {
			$place .= ', '.$place2;
		}
		
		// Calculate weight text
		$tmp = $weight % 112;
		$cwt = ($weight-$tmp) / 112;
		$tmp2 = $tmp;
		$tmp = $tmp2 % 28;
		$qtr = ($tmp2-$tmp) / 28;
		if( $weightApprox == true && $tmp == 0 && $qtr == 0 ) {
			$weightText = $cwt.'cwt';
		}
		else {
			$weightText = $cwt.'-'.$qtr.'-'.$tmp;
		}
		
		// Calculate association links
		if( !empty( $affiliations ) ) {
			$toLink = explode( ',', $affiliations );
			foreach( $toLink as $link ) {
				$associations_towers .= 'INSERT INTO associations_towers (association_abbreviation, tower_doveId) VALUES (\''.sqlite_escape_string( $link ).'\', \''.sqlite_escape_string( $doveId ).'\');'."\n";
			}
		}
		
		// Make an array of the data
		$rowData = compact(
			'doveId',
			
			'gridReference',
			'latitude',
			'longitude',
			'latitudeSatNav',
			'longitudeSatNav',
			'postcode',
			
			'country',
			'county',
			'diocese',
			'affiliations',
			'place',
			'altName',
			'dedication',
			'URL',
			
			'bells',
			'weight',
			'weightApprox',
			'weightText',
			'note',
			'hz',
			
			'practiceNight',
			'practiceStart',
			'practiceNotes',
			
			'groundFloor',
			'toilet',
			'unringable',
			'simulator',
			
			'overhaulYear',
			'contractor',
			'tuned',
			'extraInfo',
			'webPage'
		);
		// Escape for SQL
		// String valued entries
		foreach( array( 'doveId', 'gridReference', 'postcode', 'country', 'county', 'diocese', 'affiliations', 'place', 'altName', 'dedication', 'URL', 'note', 'weightText', 'practiceStart', 'practiceNotes', 'contractor', 'extraInfo', 'webPage' ) as $k ) {
			if( !empty( $rowData[$k] ) ) { $rowData[$k] = "'".sqlite_escape_string( str_replace( '"', '', $rowData[$k] ) )."'"; }
			else { $rowData[$k] = 'NULL'; }
		}
		// Integer valued entries
		foreach( array( 'bells', 'weight', 'practiceNight', 'overhaulYear', 'tuned' ) as $k ) {
			if( !empty( $rowData[$k] ) ) { $rowData[$k] = intval( $rowData[$k] ); }
			else { $rowData[$k] = 'NULL'; }
		}
		// Float valued entries
		foreach( array( 'latitude', 'longitude', 'latitudeSatNav', 'longitudeSatNav', 'hz' ) as $k ) {
			if( !empty( $rowData[$k] ) ) { $rowData[$k] = floatval( $rowData[$k] ); }
			else { $rowData[$k] = 'NULL'; }
		}
		// 'Bit' valued entries
		foreach( array( 'weightApprox', 'groundFloor', 'toilet', 'unringable', 'simulator' ) as $k ) {
			if( $rowData[$k] === true ) { $rowData[$k] = 1; }
			else { $rowData[$k] = 0; }
		}
		
		// Print statements
		echo 'INSERT INTO towers ('.implode( ', ', array_keys( $rowData ) ).') VALUES ('.implode( ', ', $rowData ).");\n";
		
		$rowData['location'] = '\''.$rowData['latitude'].','.$rowData['longitude'].'\'';
		$rowData['place'] ='\''. implode( ', ', array_reverse( explode(  ', ', trim( $rowData['place'], '\'' ) ) ) ).'\'';
		$rowData['marker'] = ( $rowData['unringable'] == 1 )? "'small_red'" : (
			( $rowData['bells'] < 6 )? "'small_yellow'" : (
			( $rowData['bells'] < 8 )? "'small_green'" : (
			( $rowData['bells'] < 10 )? "'small_blue'" : 
			"'small_purple'" ) ) );
		unset(
			$rowData['gridReference'],
			$rowData['latitude'],
			$rowData['longitude'],
			$rowData['latitudeSatNav'],
			$rowData['longitudeSatNav'],
			$rowData['postcode'],
			$rowData['countryCode'],
			$rowData['altName'],
			$rowData['weightApprox'],
			$rowData['hz'],
			$rowData['practiceStart'],
			$rowData['practiceNotes'],
			$rowData['overhaulYear'],
			$rowData['contractor'],
			$rowData['tuned'],
			$rowData['extraInfo'],
			$rowData['webPage']
		);

		echo 'INSERT INTO towersFusion ('.implode( ', ', array_keys( $rowData ) ).') VALUES ('.implode( ', ', $rowData ).");\n";
	}
fclose( $handle );
}

?>

-- Set up associations_towers table
DROP TABLE IF EXISTS associations_towers;
CREATE TABLE IF NOT EXISTS associations_towers (
  association_abbreviation varchar(10) NOT NULL, INDEX (association_abbreviation),
  tower_doveId varchar(10) NOT NULL, INDEX (tower_doveId)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

<?php echo $associations_towers; ?>

OPTIMIZE TABLE towers;
OPTIMIZE TABLE associations_towers;

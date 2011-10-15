<?php
// Converts the CCCBR provided XML files of method data into SQL INSERT statements, one per method
require( dirname(__FILE__).'/../../../src/Blueline/Helpers/LeadHeadCodes.php' );
require( dirname(__FILE__).'/../../../src/Blueline/Helpers/PlaceNotation.php' );
require( dirname(__FILE__).'/../../../src/Blueline/Helpers/Stages.php' );
use \Blueline\Helpers\LeadHeadCodes, \Blueline\Helpers\PlaceNotation, \Blueline\Helpers\Stages;

date_default_timezone_set( 'UTC' );

header( 'Content-type: text/plain' );
header( 'Content-Disposition: inline; filename="methods.sql"' );
// Some initial header information
?>
-- Method Library
-- Generated on: <?php echo date( 'Y/m/d' ); ?>

-- Copyright notice from http://www.methods.org.uk/ :
-- These method collections are the copyright of the Central Council
-- of Church Bell Ringers. You are welcome to make copies of the
-- material for your own use. You may distribute copies to others
-- provided that you do not do so for profit and provided that you
-- include this copyright statement. If you modify the material before
-- distributing it, you must include a clear notice that the material
-- has been modified.

-- Set up table
DROP TABLE IF EXISTS `methods`;
CREATE TABLE IF NOT EXISTS `methods` (
  `stage` smallint(6) NOT NULL,
  `classification` varchar(15) DEFAULT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `nameMetaphone` varchar(255) DEFAULT NULL,
  `notation` varchar(300) DEFAULT NULL,
  `notationExpanded` text,
  `leadHeadCode` varchar(3) DEFAULT NULL,
  `leadHead` varchar(25) DEFAULT NULL,
  `fchGroups` varchar(25) DEFAULT NULL,
  `rwRef` varchar(30) DEFAULT NULL,
  `bnRef` varchar(20) DEFAULT NULL,
  `tdmmRef` smallint(6) DEFAULT NULL,
  `pmmRef` smallint(6) DEFAULT NULL,
  `lengthOfLead` smallint(6) DEFAULT NULL,
  `numberOfHunts` smallint(6) DEFAULT NULL,
  `little` tinyint(1) DEFAULT NULL,
  `differential` tinyint(1) DEFAULT NULL,
  `plain` tinyint(1) DEFAULT NULL,
  `trebleDodging` tinyint(1) DEFAULT NULL,
  `palindromic` tinyint(1) DEFAULT NULL,
  `doubleSym` tinyint(1) DEFAULT NULL,
  `rotational` tinyint(1) DEFAULT NULL,
  `firstTowerbellPeal_date` date DEFAULT NULL,
  `firstTowerbellPeal_location` varchar(255) DEFAULT NULL,
  `firstHandbellPeal_date` date DEFAULT NULL,
  `firstHandbellPeal_location` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`title`),
  KEY `stage` (`stage`),
  KEY `classification` (`classification`),
  KEY `nameMetaphone` (`nameMetaphone`),
  KEY `lengthOfLead` (`lengthOfLead`),
  KEY `numberOfHunts` (`numberOfHunts`),
  KEY `little` (`little`),
  KEY `differential` (`differential`),
  KEY `plain` (`plain`),
  KEY `trebleDodging` (`trebleDodging`),
  KEY `palindromic` (`palindromic`),
  KEY `doubleSym` (`doubleSym`),
  KEY `rotational` (`rotational`),
  KEY `firstTowerbellPeal_date` (`firstTowerbellPeal_date`),
  KEY `firstTowerbellPeal_location` (`firstTowerbellPeal_location`),
  KEY `firstHandbellPeal_date` (`firstHandbellPeal_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

<?php
// Get a list of all .xml files in the ./ directory
$files = array();
if( $handle = opendir( __DIR__.'/data' ) ) {
	while( ( $file = readdir( $handle ) ) !== FALSE ) {
		if( strpos( $file, '.xml' ) ) {
			$files[] = $file;
		}
	}
	closedir( $handle );
}
else { die( 'Cannot open ./data directory' ); }

foreach( $files as $file ) {
	$file = __DIR__.'/data/'.$file;
	
	// Some variables to collect data in
	$methodIndex = 0;
	$methodData = array();
	
	// Set up XML parser
	$parser = xml_parser_create( 'UTF-8' );
	xml_parser_set_option( $parser, XML_OPTION_TARGET_ENCODING, 'UTF-8' ).
	xml_set_element_handler( $parser, __NAMESPACE__.'\startElement', __NAMESPACE__.'\endElement' ); // These functions are called at the start and end of each element
	xml_set_character_data_handler( $parser, __NAMESPACE__.'\charData' ); // This is called for each piece of character data inside elements
	
	// Read the data from the XML file
	$fp = fopen( $file, 'r' ) or die( "Could not open ${file}" );
	$data = fread( $fp, filesize( $file ) ) or die( "Could not read {$file}" );
	
	// Parse the XML
	if( ! ( xml_parse( $parser, $data, feof( $fp ) ) ) ) {
		die( 'Error on line ' . xml_get_current_line_number( $parser ) . ' of ' . $file . ' : ' . xml_error_string( xml_get_error_code( $parser ) ) );
	}
	
	// Unset variables no longer needed (these files can be quite big, so saving memory's a bonus)
	xml_parser_free( $parser );
	fclose( $fp );
	unset( $data );
	
	// Check our title and notation arrays are the same size, a basic check to make sure nothing's gone horribly wrong
	if( count( $methodData['title'] ) != count( $methodData['notation'] ) ) { die( "Amount of method titles and notation don't match in {$file}" ); 	}
	
	// Print a header for each file
	echo "\n\n\n-- {$file}\n";
	
	for( $i = 0; isset( $methodData['title'][$i] ); ++$i ) {
		// Validate, escape, and add quotes to all data.
		// Use the SQLite function, as the MySQL one needs to have an active database connection to work.
		
		// String valued entries
		foreach( array( 'classification', 'title', 'notation', 'leadHeadCode', 'leadHead', 'fchGroups', 'rwRef', 'bnRef', 'firstTowerbellPeal_date', 'firstTowerbellPeal_location', 'firstHandbellPeal_date' ) as $mKey ) {
			$m[$mKey] = ( isset( $methodData[$mKey][$i] ) )? "'".mysql_escape_mimic( $methodData[$mKey][$i] )."'" : 'NULL';
		}
		// Integer valued entries
		foreach( array( 'stage', 'tdmmRef', 'pmmRef', 'lengthOfLead', 'numberOfHunts' ) as $mKey ) {
			$m[$mKey] = ( isset( $methodData[$mKey][$i] ) )? intval( $methodData[$mKey][$i] ) : 'NULL';
		}
		// Bit valued entries
		foreach ( array( 'little', 'differential', 'plain', 'trebleDodging' ) as $mKey ) {
			$m[$mKey] = ( isset( $methodData[$mKey][$i] ) && $methodData[$mKey][$i] === true )? 1 : 0;
		}
		
		// If needed, work out the lead head from the lead head code
		if( $m['leadHead'] == 'NULL' && LeadHeadCodes::fromCode( trim( $m['leadHeadCode'], "'"), $m['stage'] ) !== false ) {  $m['leadHead'] = "'".mysql_escape_mimic( LeadHeadCodes::fromCode( trim( $m['leadHeadCode'], "'" ), $m['stage'] ) )."'"; }
		
		// Parse the place notation from the format given into 'expanded' form
		$m['notationExpanded'] = "'".PlaceNotation::expand( $m['stage'], trim( $m['notation'], "'" ) )."'";
		
		// Work out the name's metaphone string
		$m['nameMetaphone'] = isset( $methodData['name'][$i] )? "'".mysql_escape_mimic( metaphone( $methodData['name'][$i] ) )."'" : 'NULL';
		
		// Read off the method's symmetry
		$symmetryCheck = ( isset( $methodData['symmetry'][$i] ) )? $methodData['symmetry'][$i] : '';
		if( strpos( $symmetryCheck, 'palindromic' ) === FALSE ) { $m['palindromic'] = 0; } else { $m['palindromic'] = '1'; }
		if( strpos( $symmetryCheck, 'double' ) === FALSE ) { $m['doubleSym'] = 0; } else { $m['doubleSym'] = '1'; }
		if( strpos( $symmetryCheck, 'rotational' ) === FALSE ) { $m['rotational'] = 0; } else { $m['rotational'] = '1'; }
		
		// Implode all the collected data ready for printing
		$tableColumns = '('.implode( ', ', array_keys( $m ) ).')';
		$tableData = '('.implode( ', ', $m ).')';
		
		// Print the query
		echo 'INSERT INTO methods '.$tableColumns.' VALUES '.$tableData.";\n";
	}
}




// These functions and variables are used by the XML parser
$pushTo = FALSE;
$methodIndex = 0;
$stillInsideSameTagAsLastTime = FALSE;
$insideMethod = FALSE;
$insideMethodSet = FALSE;
$insideFirstTowerbellPeal = FALSE;
$insideFirstHandbellPeal = FALSE;
$methodSetData = array();
function startElement( $parser, $name, $attributes ) {
	// If the element is a used tag, then set the $pushTo variable to tell the parser to put the next piece of character data into the appropriate array
	global $pushTo, $methodIndex, $stillInsideSameTagAsLastTime, $insideMethod, $insideMethodSet, $insideFirstTowerbellPeal, $insideFirstHandbellPeal, $methodSetData, $methodData;
	
	// React differently depending on what tag we are now in...
	switch( $name ) {
	case 'METHODSET':
		$insideMethodSet = TRUE;
		break;
	case 'METHOD':
		// Begin new method by copying in any (currently) global details, and stop collecting global details
		$insideMethod = TRUE;
		if( $insideMethodSet && isset( $methodSetData[0] ) ) {
			foreach( $methodSetData as $data ) { $methodData[$data[0]][$methodIndex] = $data[1]; }
		}
		break;
	case 'NAME':
		$pushTo = 'name';
		break;
	case 'TITLE':
		$pushTo = 'title';
		break;
	case 'CLASSIFICATION':
		$pushTo = 'classification';
		// and check for attributes
		foreach( $attributes as $key => $value ) {
			if( in_array( strtolower( $key ), array( 'little', 'differential', 'plain', 'trebledodging' ) ) ) {
				if( $insideMethod ) {
					$methodData[strtolower( $key )][$methodIndex] = ( $value == 'true' );
				}
				else if( $insideMethodSet ) {
					$methodSetData[] = array( strtolower( $key ), ( $value == 'true' ) );
				}
			}
		}
		break;
	case 'NOTATION':
		$pushTo = 'notation';
		break;
	case 'LEADHEAD':
		$pushTo = 'leadHead';
		break;
	case 'LEADHEADCODE':
		$pushTo = 'leadHeadCode';
		break;
	case 'FCHGROUPS':
		$pushTo = 'fchGroups';
		break;
	case 'RWREF':
		$pushTo = 'rwRef';
		break;
	case 'BNREF':
		$pushTo = 'bnRef';
		break;
	case 'TDMMREF':
		$pushTo = 'tdmmRef';
		break;
	case 'PMMREF':
		$pushTo = 'pmmRef';
		break;
	case 'SYMMETRY':
		$pushTo = 'symmetry';
		break;
	case 'STAGE':
		$pushTo = 'stage';
		break;
	case 'LENGTHOFLEAD':
		$pushTo = 'lengthOfLead';
		break;
	case 'NUMBEROFHUNTS':
		$pushTo = 'numberOfHunts';
		break;
	case 'FIRSTTOWERBELLPEAL':
		$insideFirstTowerbellPeal = TRUE;
		break;
	case 'FIRSTHANDBELLPEAL':
		$insideFirstHandbellPeal = TRUE;
		break;
	case 'DATE':
		if( $insideFirstTowerbellPeal ) { $pushTo = 'firstTowerbellPeal_date'; }
		elseif( $insideFirstHandbellPeal ) { $pushTo = 'firstHandbellPeal_date'; }
		break;
	case 'LOCATION':
	case 'ROOM':
	case 'BUILDING':
	case 'ADDRESS':
	case 'TOWN':
	case 'COUNTY':
	case 'REGION':
	case 'COUNTRY':
		if( $insideFirstTowerbellPeal ) { $pushTo = 'firstTowerbellPeal_location'; }
		elseif( $insideFirstHandbellPeal ) { /* If this starts happening then the method data has started including handbell peal locations, and the database needs a new column */ trigger_error( 'Handbell peal location detected.' ); }
		break;
	default:
		break;
	}
}
function endElement( $parser, $name ) {
	global $pushTo, $methodIndex, $stillInsideSameTagAsLastTime, $insideMethod, $insideMethodSet, $insideFirstTowerbellPeal, $insideFirstHandbellPeal, $methodSetData, $methodData;
	// Reset everything when we reach the end of an element
	switch( $name ) {
	case 'METHODSET':
		// Clear any global data for this set
		$insideMethodSet = FALSE;
		$methodSetData = array();
		break;
	case 'METHOD':
		$insideMethod = FALSE;
		$methodIndex++;
		break;
	case 'FIRSTTOWERBELLPEAL':
		$insideFirstTowerbellPeal = FALSE;
		break;
	case 'FIRSTHANDBELLPEAL':
		$insideFirstHandbellPeal = FALSE;
		break;
	case 'ROOM':
	case 'BUILDING':
	case 'ADDRESS':
	case 'TOWN':
	case 'COUNTY':
	case 'REGION':
	case 'COUNTRY':
		if( $insideFirstTowerbellPeal ) { $methodData['firstTowerbellPeal_location'][$methodIndex] .= ', '; }
		elseif( $insideFirstHandbellPeal ) { $methodData['firstHandbellPeal_location'][$methodIndex] .= ', '; }
		break;
	case 'LOCATION':
		if( $insideFirstTowerbellPeal ) { $methodData['firstTowerbellPeal_location'][$methodIndex] = trim( $methodData['firstTowerbellPeal_location'][$methodIndex], ', ' ); }
		elseif( $insideFirstHandbellPeal ) { $methodData['firstHandbellPeal_location'][$methodIndex] = trim( $methodData['firstHandbellPeal_location'][$methodIndex], ' ,' ); }
		break;
	}
	$pushTo = $stillInsideSameTagAsLastTime = FALSE;
}
function charData( $parser, $data ) {
	global $pushTo, $methodIndex, $stillInsideSameTagAsLastTime, $insideMethod, $insideMethodSet, $insideFirstTowerbellPeal, $insideFirstHandbellPeal, $methodSetData, $methodData;
	// PHP's XML parser seemingly decides to split character data on $amp; or UTF characters as well as tags.
	// To get around this, make a note of whether we are still inside the same tag as last time, and respond
	// appropriately.
	if( $pushTo ) {
		if( $insideMethodSet && !$insideMethod ) {
			if( $stillInsideSameTagAsLastTime ) {
				$methodSetData[end( array_keys( $methodSetData ) )][1] .= $data;
			}
			else {
				$methodSetData[] = array( $pushTo, $data );
			}
		}
		else {
			if( ! isset( $methodData[$pushTo] ) ) { $methodData[$pushTo] = array(); }
			if( ! isset( $methodData[$pushTo][$methodIndex] ) ) { $methodData[$pushTo][$methodIndex] = ''; }
			$methodData[$pushTo][$methodIndex] .= $data;
		}
		$stillInsideSameTagAsLastTime = TRUE;
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

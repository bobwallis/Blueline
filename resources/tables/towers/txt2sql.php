<?php
require( dirname(__FILE__).'/_shared.php' );

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
if( ( $handle = fopen( __DIR__.'/data/newpks.txt', 'r' ) ) == false ) {
	trigger_error( 'Couldn\'t open newpks.txt' );
}

// Extract column headings
if( ( $columns = fgetcsv( $handle, 25, "\\" ) ) === false ) {
	trigger_error( 'Couldn\'t read newpks.txt' );
}

// Set up data objects
$data = array();
foreach( $columns as $column ) {
	$data[$column] = array();
}

// Read data into object
while( ( $dataCollect = fgetcsv( $handle, 25, "\\" ) ) !== false ) {
	$dataWithHeadings = array();
	foreach( $columns as $i => $column ) {
		$data[$column][] = mysql_escape_mimic( str_replace( ' ', '_', trim( $dataCollect[$i] ) ) );
	}
}
fclose( $handle );

// Prevent entries appearing as a newPK when they appear as an oldPK themselves
$foundWrongEntry = true;
while( $foundWrongEntry ) {
	$foundWrongEntry = false;
	for( $i = 0, $iLim = count( $data['OldID'] ); $i < $iLim; ++$i ) {
		$newOldPK = array_search( $data['NewID'][$i], $data['OldID'] );
		if( $newOldPK !== false ) {
			$data['NewID'][$i] = $data['NewID'][$newOldPK];
			$foundWrongEntry = true;
		}
	}
}

echo "INSERT INTO `tower_oldpks` (`oldpk`, `tower_doveid`) VALUES\n";
for( $i = 0, $iLim = count( $data['OldID'] ); $i < $iLim; ++$i ) {
	echo (($i==0)?'':",\n")."\t('{$data['OldID'][$i]}','{$data['NewID'][$i]}')";
}
echo ';';
unset( $data, $dataWithHeadings, $dataCollect, $columns, $i, $iLim );

// Tower data from dove.txt
if( ( $handle = fopen( __DIR__.'/data/dove.txt', 'r' ) ) == false ) {
	trigger_error( 'Couldn\'t open dove.txt' );
}

// Extract column headings
if( ( $columns = fgetcsv( $handle, 0, "\\" ) ) === false ) {
	trigger_error( 'Couldn\'t read dove.txt' );
}
// Read data
while( ( $data = fgetcsv( $handle, 0, "\\" ) ) !== false ) {
	$tower = array();
	
	// Match data to columns
	foreach( $columns as $i => $column ) {
		$tower[$column] = $data[$i];
	}
	
	// Tody data
	$rowData = tidyTower( $tower );
	
	// towers INSERT
 	echo 'INSERT INTO `towers` (`'.implode( '`, `', array_keys( $rowData ) ).'`) VALUES ('.implode( ', ', $rowData ).");\n";
	
	// associations_towers data
	if( !empty( $tower['Affiliations'] ) ) {
		foreach( explode( ',', $tower['Affiliations'] ) as $link ) {
			echo 'INSERT INTO `associations_towers` (`association_abbreviation`, `tower_doveid`) VALUES (\''.mysql_escape_mimic( $link ).'\', '.$rowData['doveid'].');'."\n";
		}
	}
}
?>
-- End

<?php
// Converts the CCCBR provided HTMLML files of duplicate methods into into SQL INSERT statements, one per method
date_default_timezone_set( 'UTC' );

header( 'Content-type: text/plain' );
header( 'Content-Disposition: inline; filename="methods_duplicates.sql"' );
// Some initial header information
?>
-- Duplicate Names
-- Generated on: <?php echo date( 'Y/m/d' ); ?>
-- Names used for methods that had previously been differently named

-- Set up table
DROP TABLE IF EXISTS `methods_duplicates`;
CREATE TABLE IF NOT EXISTS `methods_duplicates` (
  `actual` varchar(255) NOT NULL DEFAULT '',
  `rung` varchar(255) NOT NULL DEFAULT '',
  `rung_location` varchar(255) DEFAULT NULL,
  `rung_date` date DEFAULT NULL,
  `rung_rwRef` varchar(30) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

<?php

$fp = fopen( __DIR__.'/data/data.html', 'r' ) or die( "Could not open data.html" );
while( $line = fgets( $fp ) ) {
	if( strpos( $line, '<p><b><i>' ) === 0 ) {
		preg_match( '/^<p><b><i>(.*?)<\/i><\/b> .*?(on|at|as) (.*?) on (.*?) (was|(\(.*\)?) was) <b><i>(.*?)<\/i>/', $line, $matches );
		
		if( $matches == null ) {
			die( "Failed to match on: {$line}" );
		}
		
		$data = array(
			'rung' => "'".mysql_escape_mimic( $matches[1] )."'",
			'rung_location' => "'".mysql_escape_mimic( $matches[3] )."'",
			'rung_date' => "'".mysql_escape_mimic( date( 'Y-m-d', strtotime( $matches[4] ) ) )."'",
			'rung_rwRef' => "'".mysql_escape_mimic( trim( $matches[6], '()' ) )."'",
		);

		$rungExplode = explode( ' ', $matches[1] );
		$stage = array_pop( $rungExplode );
		$classification = array_pop( $rungExplode );
		$little = false;
		$differential = false;
		
		$last = array_pop( $rungExplode );
		switch( $last ) {
			case 'Little':
				$little = true;
				break;
			case 'Differential':
				$differential = true;
				break;
		}
		$last = array_pop( $rungExplode );
		if( $last === 'Differential' ) {
			$differential = true;
		}
		$data['actual'] = "'".mysql_escape_mimic( $matches[7].($differential?' Differential':'').($little?' Little':'').' '.$classification.' '.$stage )."'";

		echo 'INSERT INTO methods_duplicates ('.implode( ', ', array_keys( $data ) ).') VALUES ('.implode( ', ', $data ).");\n";
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

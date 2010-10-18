<?php require( 'phpcoord.php' ); ?>
-- Set up postcodes table
DROP TABLE IF EXISTS `postcodes`;
CREATE TABLE IF NOT EXISTS `postcodes` (
  `postcode` varchar(7),
  `gridReference` varchar(8),
  `easting` int(6),
  `northing` int(6),
  `latitude_OSGB36` decimal(8,5),
  `longitude_OSGB36` decimal(8,5),
  `latitude_WGS84` decimal(8,5),
  `longitude_WGS84` decimal(8,5),

  PRIMARY KEY (`postcode`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

<?php
$files = array();
if( $handle = opendir( './data/CSV' ) ) {
	while( ( $file = readdir( $handle ) ) !== FALSE ) {
		if( strpos( $file, '.csv' ) ) {
			$files[] = $file;
		}
	}
	closedir( $handle );
}
else { die( 'Cannot open ./data/CSV directory' ); }

foreach( $files as $file ) {
	$handle = fopen( './data/CSV/'.$file, 'r' );
	if( $handle ) {
		fgets( $handle ); // First row contains columns
		while( !feof( $handle ) ) {
			$buffer = fgets( $handle );
			if( !$buffer ) { continue; }
			$data =  explode( ',', $buffer );
			$postcode = trim( $data[0], '"' );
			$easting = intval( trim( $data[10], '"' ) );
			$northing = intval( trim( $data[11], '"' ) );
			unset( $data, $buffer );
			
			if( $easting == 0 ) { continue; }
			
			$gridRef = new OSRef( $easting, $northing );
			
			$rowData = array(
				'postcode' => '\''.$postcode.'\'',
				'gridReference' => '\''.$gridRef->toSixFigureString().'\'',
				'easting' => $easting,
				'northing' => $northing,
			);
			
			$latLong = $gridRef->toLatLng();
			$rowData['latitude_OSGB36'] = round( $latLong->lat, 5 );
			$rowData['longitude_OSGB36'] = round( $latLong->lng, 5 );
			$latLong->OSGB36ToWGS84();
			$rowData['latitude_WGS84'] = round( $latLong->lat, 5 );
			$rowData['longitude_WGS84'] = round( $latLong->lng, 5 );
			
			echo 'INSERT INTO `postcodes` ('.implode( ', ', array_keys( $rowData ) ).') VALUES ('.implode( ', ', $rowData ).");\n";
		}
	}
}

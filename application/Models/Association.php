<?php
namespace Models;
use \Blueline\Database, \PDO;

class Association extends \Blueline\Model {
	public static function fullList() {
		$sth = Database::$dbh->prepare( '
			SELECT name, abbreviation, link
			FROM associations
			ORDER BY name ASC
		' );
		$sth->execute();
		return ( $associationsData = $sth->fetchAll( PDO::FETCH_ASSOC ) )? $associationsData : array();
	}
	
	public static function view( $abbreviation ) {
		$sth = Database::$dbh->prepare( '
			SELECT name, abbreviation, link
			FROM associations
			WHERE abbreviation LIKE :abbreviation
			LIMIT 1
		' );
		$sth->execute( array( ':abbreviation' => $abbreviation ) );
		if( $associationData = $sth->fetch( PDO::FETCH_ASSOC ) ) {
			$sth = Database::$dbh->prepare( '
				SELECT COUNT(*) as towerCount, MAX(latitude) as lat_max, MIN(latitude) as lat_min, MAX(longitude) as long_max, MIN(longitude) as long_min
				FROM towers
				WHERE affiliations LIKE :abbreviation
			' );
			$sth->execute( array( ':abbreviation' => '%'.$abbreviation.'%' ) );
			return array_merge( $associationData, $sth->fetch( PDO::FETCH_ASSOC ) );
		}
		else {
			return array(
				'name' => 'Not Found'
			);
		}
	}
}

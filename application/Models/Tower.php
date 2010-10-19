<?php
namespace Models;
use \Blueline\Database, \PDO;

class Tower extends \Blueline\Model {
	public static function fullDetailsFromDoveId( $doveId ) {
		$sth = Database::$dbh->prepare( '
			SELECT doveId, gridReference, latitude, longitude, latitudeSatNav, longitudeSatNav, postcode, country, county, diocese, place, altName, dedication, URL, bells, weight, weightApprox, weightText, note, hz, practiceNight, practiceStart, practiceNotes, groundFloor, toilet, unringable, simulator, overhaulYear, contractor, tuned, extraInfo, webPage
			FROM towers
			LEFT OUTER JOIN tower_oldpks ON (tower_doveId = doveId)
			WHERE ((doveId = :doveId) OR (oldpk = :doveId))
			LIMIT 1
		' );
		$sth->execute( array( ':doveId' => $doveId ) );
		if( $towerData = $sth->fetch( PDO::FETCH_ASSOC ) ) {
			$towerData['affiliations'] = self::affiliationsFromDoveId( $doveId );
			$towerData['firstPeals'] = self::firstPealsFromDoveId( $doveId );
			$towerData['nearbyTowers'] = self::nearbyTowersFromLocation( $towerData['latitude'], $towerData['longitude'] );
			return $towerData;
		}
		else {
			return array(
				'doveId' => 'NONE'
			);
		}
	}
	
	public static function firstPealsFromDoveId( $doveId ) {
			$sth = Database::$dbh->prepare( '
				SELECT title, firstTowerbellPeal_date 
				FROM methods
				JOIN methods_towers ON (tower_id = :doveId AND method_title = title)
				ORDER BY firstTowerbellPeal_date DESC
		' );
		$sth->execute( array( ':doveId' => $doveId ) );
		return $sth->fetchAll( PDO::FETCH_ASSOC );
	}
	
	public static function affiliationsFromDoveId( $doveId ) {
		$sth = Database::$dbh->prepare( '
			SELECT abbreviation, name, link
			FROM associations
			JOIN associations_towers ON (association_abbreviation = abbreviation AND tower_doveId = :doveId)
		' );
		$sth->execute( array( ':doveId' => $doveId ) );
		return $sth->fetchAll( PDO::FETCH_ASSOC );
	}
	
	public static function nearbyTowersFromLocation( $latitude, $longitude ) {
		if( empty( $latitude ) || empty( $longitude ) ) { return array(); }
		$sth = Database::$dbh->prepare( '
			SELECT doveId, place, dedication, bells, weightText, ( 6371 * acos( cos( radians(:latitude) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(:longitude) ) + sin( radians(:latitude) ) * sin( radians( latitude ) ) ) ) AS distance
			FROM towers
			WHERE latitude IS NOT NULL
			HAVING distance < 20
			ORDER BY distance ASC
			LIMIT 1,6
		' );
		$sth->execute( array( ':latitude' => $latitude, ':longitude' => $longitude ) );
		return $sth->fetchAll( PDO::FETCH_ASSOC );
	}
}

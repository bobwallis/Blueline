<?php
namespace Models;
use \Blueline\Config, \Blueline\Database, \PDO;

class Association extends \Blueline\Model {

	protected static $_table = 'associations';
	
	protected static $_searchSelect = 'abbreviation, name, link';
	protected static $_searchWhere = false;
	protected static $_searchBindable = false;

	public static function index() {
		$sth = Database::$dbh->prepare( '
			SELECT name, abbreviation, link
			FROM associations
			ORDER BY name ASC
		' );
		$sth->execute();
		return ( $associationsData = $sth->fetchAll( PDO::FETCH_ASSOC ) )? $associationsData : array();
	}
	
	public static function view( $abbreviation ) {
		$associationData = self::getEverythingByAbbreviation( $abbreviation );
		if( !empty( $associationData ) ) {
			$associationData['affiliatedTowers'] = self::getTowersByAbbreviation( $abbreviation );
			return $associationData;
		}
		else {
			return array( 'name' => 'Not Found' );
		}
	}
	
	// get*By* functions
	
	public static function getEverythingByAbbreviation( $abbreviation ) {
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
				JOIN associations_towers ON (association_abbreviation = :abbreviation AND tower_doveId = doveId)
			' );
			$sth->execute( array( ':abbreviation' => $abbreviation ) );
			return array_merge( $associationData, $sth->fetch( PDO::FETCH_ASSOC )?:array() );
		}
		else {
			return array();
		}
	}
	
	public static function getLinkByAbbreviation( $abbreviation ) {
		$sth = Database::$dbh->prepare( '
			SELECT link
			FROM associations
			WHERE abbreviation = :abbreviation
			LIMIT 1
		' );
		$sth->execute( array( ':abbreviation' => $abbreviation ) );
		return ( $associationData = $sth->fetch( PDO::FETCH_ASSOC ) )? $associationData['link'] : '';
	}
	
	public static function getTowersByAbbreviation( $abbreviation ) {
		$sth = Database::$dbh->prepare( '
			SELECT doveId, place, dedication
			FROM towers
			JOIN associations_towers ON (association_abbreviation = :abbreviation AND tower_doveId = doveId)
			ORDER BY place ASC
		' );
		$sth->execute( array( ':abbreviation' => $abbreviation ) );
		return ( $towersData = $sth->fetchAll( PDO::FETCH_ASSOC ) )? $towersData : array();
	}
	
	/**
	 * Gets search suggestions
	 * @return array
	 */
	public static function search_suggestions() {	
		$sth = Database::$dbh->prepare( '
			SELECT name, abbreviation
			FROM '.static::$_table.'
			WHERE '.self::GETtoWhere().'
			LIMIT '.self::GETtoLimit().'
		' );
		$sth->execute( self::GETtoBindable() );
		return ( $searchData = $sth->fetchAll( PDO::FETCH_ASSOC ) )? array(
			'queries' => array_map( function( $a ) { return $a['name']; } , $searchData ),
			'readable' => array(),
			'URLs' => array_map( function( $a ) { return Config::get( 'site.baseURL' ).'/associations/view/'.urlencode( $a['abbreviation'] ); } , $searchData )
		) : array();
	}
	
	// Helper function to assemble search queries
	private static $_conditions = false;
	protected static function GETtoConditions() {
		if( self::$_conditions === false ) {
			$conditions = array();
			// Name
			if( isset( $_GET['q'] ) ) {
				if( strpos( $_GET['q'], '/' ) === 0 && preg_match( '/^\/(.*)\/$/', $_GET['q'], $matches ) ) {
					$conditions['name REGEXP'] = $matches[1];
				}
				else {
					$conditions['name LIKE'] = '%'.self::prepareStringForLike( $_GET['q'] ).'%';
				}
			}
			// Abbreviation
			if( isset( $_GET['abbreviation'] ) ) {
				if( strpos( $_GET['abbreviation'], '/' ) === 0 && preg_match( '/^\/(.*)\/$/', $_GET['abbreviation'], $matches ) ) {
					$conditions['abbreviation REGEXP'] = $matches[1];
				}
				else {
					$conditions['abbreviation LIKE'] = '%'.self::prepareStringForLike( $_GET['abbreviation'] ).'%';
				}
			}
			
			// If an abbreviation search isn't specified, then use the q value to also search by abbreviation
			if( isset( $conditions['name LIKE'] ) && !isset( $_GET['abbreviation'] ) ) {
				$conditions = array(
					'OR' => array(
						'name LIKE' => $conditions['name LIKE'],
						'abbreviation LIKE' => $conditions['name LIKE']
					)
				);
			}
			self::$_conditions = $conditions;
		}
		return self::$_conditions;
	}
}

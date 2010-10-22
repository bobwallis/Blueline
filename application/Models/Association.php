<?php
namespace Models;
use \Blueline\Database, \PDO;

class Association extends \Blueline\Model {

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
	
	public static function search() {		
		$sth = Database::$dbh->prepare( '
			SELECT name, abbreviation, link
			FROM associations
			WHERE '.self::GETtoWhere().'
			LIMIT '.self::GETtoLimit().'
		' );
		$sth->execute( self::GETtoBindable() );
		return ( $associationData = $sth->fetchAll( PDO::FETCH_ASSOC ) )? $associationData : array();
	}
	public static function searchCount() {
		$sth = Database::$dbh->prepare( '
			SELECT COUNT(*) as count
			FROM associations
			WHERE '.self::GETtoWhere().'
		' );
		$sth->execute( self::GETtoBindable() );
		return ( $countData = $sth->fetch( PDO::FETCH_ASSOC ) )? $countData['count'] : 0;
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
				WHERE affiliations LIKE :abbreviation
			' );
			$sth->execute( array( ':abbreviation' => '%'.$abbreviation.'%' ) );
			return array_merge( $associationData, $sth->fetch( PDO::FETCH_ASSOC ) );
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
	
	// Helper function to assemble search queries
	private static $_conditions = false;
	protected static function GETtoConditions() {
		if( self::$_conditions === false ) {
			$conditions = array();
			// Name
			if( isset( $_GET['q'] ) ) {
				if( strpos( $_GET['q'], '/' ) === 0 && preg_match( '/^\/(.*)\/$/', $_GET['q'], $matches ) ) {
					$conditions[':name REGEXP'] = $matches[1];
				}
				else {
					$conditions[':name LIKE'] = isset( $_GET['q'] )? '%'.str_replace( array( ' ', '*', '?' ), array( '%', '%', '_' ), $_GET['q'] ).'%' : '';
				}
			}
			
			self::$_conditions = $conditions;
		}
		return self::$_conditions;
	}
}

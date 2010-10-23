<?php
namespace Models;
use \Blueline\Database, \PDO;

class Method extends \Blueline\Model {

	protected static $_table = 'methods';
	
	protected static $_searchSelect = 'title, stage, classification, notation';
	protected static $_searchWhere = false;
	protected static $_searchBindable = false;
	
	public static function view( $title ) {
		$sth = Database::$dbh->prepare( '
			SELECT title, stage, classification, notation, notationExpanded, leadHeadCode, leadHead, fchGroups, rwRef, bnRef, tdmmRef, pmmRef, lengthOfLead, numberOfHunts, little, differential, plain, trebleDodging, palindromic, doubleSym, rotational, firstTowerbellPeal_date, firstTowerbellPeal_location, firstHandbellPeal_date, firstHandbellPeal_location, calls, ruleOffs, tower_id AS firstTowerbellPeal_location_doveId
			FROM '.static::$_table.'
			LEFT OUTER JOIN method_extras AS me ON (me.method_title = title)
			LEFT OUTER JOIN methods_towers AS mt ON (mt.method_title = title)
			WHERE title LIKE :title
			LIMIT 1
		' );
		$sth->execute( array( ':title' => $title ) );
		return ( $methodData = $sth->fetch( PDO::FETCH_ASSOC ) )? $methodData : array( 'title' => 'Not Found' );
	}
	
	private static $_conditions = false;
	protected static function GETtoConditions() {
		if( self::$_conditions === false ) {
			$conditions = array();
			// Title
			if( isset( $_GET['q'] ) ) {
				$q = $_GET['q'];
				if( strpos( $q, '/' ) === 0 && preg_match( '/^\/(.*)\/$/', $q, $matches ) ) {
					$conditions['title REGEXP'] = $matches[1];
				}
				else {
					// If the search ends in a number then use that to filter by stage
					if( preg_match( '/ (\d{1,2})$/', $q, $matches ) && ($matches[1] > 2) && ($matches[1] < 23) ) {
						$q = substr( $q, 0, -2 );
						$conditions['stage ='] = intval( $matches[1] );
					}
					$conditions['title LIKE'] = '%'.self::prepareStringForLike( $q ).'%';
				}
			}
			self::$_conditions = $conditions;
		}
		return self::$_conditions;
	}
}

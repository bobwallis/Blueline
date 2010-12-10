<?php
namespace Models\DataAccess;

class Methods extends \Blueline\DataAccess {
	protected static $_model = '\Models\Method';
	protected static $_table = 'methods';
	protected static $_fields = array( 'title', 'stage', 'classification', 'notation', 'notationExpanded', 'leadHeadCode', 'leadHead', 'fchGroups', 'rwRef', 'bnRef', 'tdmmRef', 'pmmRef', 'lengthOfLead', 'numberOfHunts', 'little', 'differential', 'plain', 'trebleDodging', 'palindromic', 'doubleSym', 'rotational', 'firstTowerbellPeal_date', 'firstTowerbellPeal_location', 'firstHandbellPeal_date', 'firstHandbellPeal_location' );
	
	private static $_GETConditions = false;
	public static function GETtoConditions() {
		if( self::$_GETConditions === false ) {
			$conditions = array();
			// Title
			if( isset( $_GET['q'] ) ) {
				$q = $_GET['q'];
				if( strpos( $q, '/' ) === 0 && preg_match( '/^\/(.*)\/$/', $q, $matches ) ) {
					$conditions['title REGEXP'] = $matches[1];
				}
				else {
					// If the search ends in a number then use that to filter by stage
					if( preg_match( '/^(.*) (\d{1,2})\s*$/', $q, $matches ) && \Helpers\Stages::toInt( $matches[2] ) != false ) {
						$q = $matches[1];
						$conditions['stage ='] = \Helpers\Stages::toInt( $matches[2] );
					}
					$conditions['title LIKE'] = '%'.self::prepareStringForLike( $q ).'%';
				}
			}
			self::$_GETConditions = $conditions;
		}
		return self::$_GETConditions;
	}
}

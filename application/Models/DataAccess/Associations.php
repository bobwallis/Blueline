<?php
namespace Models\DataAccess;

class Associations extends \Blueline\DataAccess {
	protected static $_model = '\Models\Association';
	protected static $_table = 'associations';
	protected static $_fields = array( 'id', 'abbreviation', 'name', 'link' );
	
	// Helper function to assemble search queries
	private static $_GETConditions = false;
	public static function GETtoConditions() {
		if( self::$_GETConditions === false ) {
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
			self::$_GETConditions = $conditions;
		}
		return self::$_GETConditions;
	}
}

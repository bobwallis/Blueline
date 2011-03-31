<?php
namespace Models\DataAccess;
use Pan\DataAccess;

class Towers extends DataAccess {
	protected static $_model = '\Models\Tower';
	protected static $_table = 'towers';
	protected static $_fields = array( 'doveId', 'gridReference', 'latitude', 'longitude', 'latitudeSatNav', 'longitudeSatNav', 'postcode', 'country', 'county', 'diocese', 'place', 'altName', 'dedication', 'bells', 'weight', 'weightApprox', 'weightText', 'note', 'hz', 'practiceNight', 'practiceStart', 'practiceNotes', 'groundFloor', 'toilet', 'unringable', 'simulator', 'overhaulYear', 'contractor', 'tuned', 'extraInfo', 'webPage' );

	private static $_GETConditions = false;
	public static function GETtoConditions() {
		if( self::$_GETConditions === false ) {
			$conditions = array();
			// Place/Dedication
			if( isset( $_GET['q'] ) ) {
				if( strpos( $_GET['q'], '/' ) === 0 && preg_match( '/^\/(.*)\/$/', $_GET['q'], $matches ) ) {
					$conditions['place REGEXP'] = $matches[1];
				}
				else {
					if( strpos( $_GET['q'], ' ' ) !== false ) {
						$conditions['CONCAT(dedication,\' \',place,\' \',dedication) LIKE'] = '%'.self::prepareStringForLike( $_GET['q'] ).'%';
					}
					else {
						$conditions['place LIKE'] = '%'.$_GET['q'].'%';
					}
				}
			}
			if( isset( $_GET['diocese'] ) ) {
				$conditions['diocese ='] = urldecode( $_GET['diocese'] );
			}
			self::$_GETConditions = $conditions;
		}
		return self::$_GETConditions;
	}
}

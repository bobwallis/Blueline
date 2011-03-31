<?php
namespace Models\DataAccess;
use Pan\DataAccess;

class Methods extends DataAccess {
	protected static $_model = '\Models\Method';
	protected static $_table = 'methods';
	protected static $_fields = array( 'title', 'stage', 'classification', 'notation', 'notationExpanded', 'leadHeadCode', 'leadHead', 'fchGroups', 'rwRef', 'bnRef', 'tdmmRef', 'pmmRef', 'lengthOfLead', 'numberOfHunts', 'little', 'differential', 'plain', 'trebleDodging', 'palindromic', 'doubleSym', 'rotational', 'firstTowerbellPeal_date', 'firstTowerbellPeal_location', 'firstHandbellPeal_date', 'firstHandbellPeal_location' );

	private static $_GETConditions = false;
	public static function GETtoConditions() {
		if( self::$_GETConditions === false ) {
			$conditions = array();
			// Title
			if( isset( $_GET['q'] ) ) {
				$q = trim( preg_replace( '/\s+/', ' ', $_GET['q'] ) );
				if( strpos( $q, '/' ) === 0 && preg_match( '/^\/(.*)\/$/', $q, $matches ) ) {
					$conditions['title REGEXP'] = $matches[1];
				}
				else {
					$qExplode = explode( ' ', $q );
					if( count( $qExplode ) > 1 ) {
						$last = array_pop( $qExplode );
						// If the search ends in a number then use that to filter by stage and remove it from the title search
						if( \Helpers\Stages::toInt( $last ) ) {
							$conditions['stage ='] = \Helpers\Stages::toInt( $last );
							$q = implode( ' ', $qExplode );
							$last = array_pop( $qExplode );
						}
						else {
							$q = implode( ' ', $qExplode ).($last?' '.$last:'');
						}

						// Remove non-name parts of the search to test against nameMetaphone
						if( \Helpers\Classifications::isClass( $last ) ) {
							$conditions['classification ='] = ucwords( strtolower( $last ) );
							$last = array_pop( $qExplode );
						}
						while( 1 ) {
							switch( strtolower( $last ) ) {
								case 'little':
									$conditions['little ='] = 1;
									$last = array_pop( $qExplode );
									break;
								case 'differential':
									$conditions['differential ='] = 1;
									$last = array_pop( $qExplode );
									break;
								default:
									break 2;
							}
						}
						 // This will be used to test against nameMetaphone
						$nameMetaphone = metaphone( implode( ' ', $qExplode ).($last?' '.$last:'') );
					}
					else {
						$nameMetaphone = metaphone( $q );
					}
					if( $nameMetaphone ) {
						$conditions['OR'] = array(
							'title LIKE' => '%'.self::prepareStringForLike( $q ).'%',
							'levenshteinRatio("'.$nameMetaphone.'",nameMetaphone) > 90' => null
						);
					}
					else {
						$conditions['title LIKE'] = '%'.self::prepareStringForLike( $q ).'%';
					}
				}
			}
			self::$_GETConditions = $conditions;
		}
		return self::$_GETConditions;
	}
}

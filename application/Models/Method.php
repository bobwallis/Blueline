<?php
namespace Models;
use \Blueline\Database, \PDO;

class Method extends \Blueline\Model {
	public static function view( $title ) {
		$sth = Database::$dbh->prepare( '
			SELECT title, stage, classification, notation, notationExpanded, leadHeadCode, leadHead, fchGroups, rwRef, bnRef, tdmmRef, pmmRef, lengthOfLead, numberOfHunts, little, differential, plain, trebleDodging, palindromic, doubleSym, rotational, firstTowerbellPeal_date, firstTowerbellPeal_location, firstHandbellPeal_date, firstHandbellPeal_location, calls, ruleOffs, tower_id AS firstTowerbellPeal_location_doveId
			FROM methods
			LEFT OUTER JOIN method_extras AS me ON (me.method_title = title)
			LEFT OUTER JOIN methods_towers AS mt ON (mt.method_title = title)
			WHERE title LIKE :title
			LIMIT 1
		' );
		$sth->bindParam( ':title', $title, PDO::PARAM_STR );
		$sth->execute();
		if( $methodData = $sth->fetch( PDO::FETCH_ASSOC ) ) {
			return $methodData;
		}
		else {
			return array(
				'title' => 'Not Found'
			);
		}
	}
}

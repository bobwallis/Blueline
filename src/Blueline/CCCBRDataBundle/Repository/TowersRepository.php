<?php

namespace Blueline\CCCBRDataBundle\Repository;

class TowersRepository extends SharedRepository {
	public function requestToSearchVariables( $request, $searchable = null ) {
		if( is_null( $searchable ) ) { $searchable = array( 'doveid', 'gridReference', 'latitude', 'longitude', 'postcode', 'country', 'county', 'diocese', 'place', 'dedication', 'bells', 'weight', 'note', 'practiceNight', 'groundFloor', 'toilet', 'unringable', 'simulator', 'overhaulYear', 'contractor', 'tuned' ); }
		return parent::requestToSearchVariables( $request, $searchable );
	}
	
	public function search( $searchVariables, $query = null ) {
		if( is_null( $query ) ) { $query = $this->createQueryBuilder( 't' )->select( 'partial t.{doveid, place, dedication}' ); }
		
		if( isset( $searchVariables['q'] ) ) {
			if( strpos( $searchVariables['q'], ' ' ) !== false ) {
				$query->andWhere( "CONCAT(CONCAT(CONCAT(CONCAT(t.dedication,' '),t.place),' '),t.dedication) LIKE :qLike" );
			}
			else {
				$query->andWhere( 't.place LIKE :qLike' );
			}
			$query->setParameter( 'qLike', $this->prepareStringForLike( $searchVariables['q'] ) );
		}
		
		// String variables
		foreach( array( 'doveid', 'gridReference', 'postcode', 'country', 'county', 'diocese', 'place', 'dedication', 'note', 'contractor' ) as $key ) {
			if( isset(  $searchVariables[$key] ) ) {
				if( strpos( $searchVariables[$key], '/' ) === 0 ) {
					$query->andWhere( 't.'.$key.' REGEXP :'.$key.'Regexp' ) // This doesn't work, which is annoying
						->setParameter( $key.'Regexp', trim( $searchVariables[$key], '/' ) );
				}
				else {
					$query->andWhere( 't.'.$key.' LIKE :'.$key.'Like' )
						->setParameter( $key.'Like', $this->prepareStringForLike( $searchVariables[$key] ) );
				}
			}
		}
		
		return parent::search( $searchVariables, $query );
	}
	
	public function searchCount( $searchVariables ) {
		$query = $this->createQueryBuilder( 't' )->select( 'COUNT(t.place) as num' );
		unset( $searchVariables['offset'] );
		unset( $searchVariables['count'] );
		$result = $this->search( $searchVariables, $query );
		return intval( $result[0]['num'] );
	}
}

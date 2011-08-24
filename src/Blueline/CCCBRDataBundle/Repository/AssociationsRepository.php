<?php

namespace Blueline\CCCBRDataBundle\Repository;

class AssociationsRepository extends SharedRepository {
	public function requestToSearchVariables( $request, $searchable = null ) {
		if( is_null( $searchable ) ) { $searchable = array( 'abbreviation', 'name' ); }
		return parent::requestToSearchVariables( $request, $searchable );
	}
	
	public function search( $searchVariables, $query = null ) {
		if( is_null( $query ) ) { $query = $this->createQueryBuilder( 'a' )->select( 'partial a.{name, abbreviation}' ); }
		
		if( isset( $searchVariables['q'] ) ) {
			$query->andWhere( $query->expr()->orx(
				$query->expr()->like('a.name', ':qLike'),
				$query->expr()->like('a.abbreviation', ':qLike')
				) )
   			->setParameter( 'qLike', $this->prepareStringForLike( $searchVariables['q'] ) );
		}
		
		foreach( array( 'abbreviation', 'name' ) as $key ) {
			if( isset(  $searchVariables[$key] ) ) {
				if( strpos( $searchVariables[$key], '/' ) === 0 ) {
					$query->andWhere( 'a.'.$key.' REGEXP :'.$key.'Regexp' ) // This doesn't work, which is annoying
						->setParameter( $key.'Regexp', trim( $searchVariables[$key], '/' ) );
				}
				else {
					$query->andWhere( 'a.'.$key.' LIKE :'.$key.'Like' )
						->setParameter( $key.'Like', $this->prepareStringForLike( $searchVariables[$key] ) );
				}
			}
		}
		
		return parent::search( $searchVariables, $query );
	}
	
	public function searchCount( $searchVariables ) {
		$query = $this->createQueryBuilder( 'a' )->select( 'COUNT(a.name) as num' );
		unset( $searchVariables['offset'] );
		unset( $searchVariables['count'] );
		$result = $this->search( $searchVariables, $query );
		return intval( $result[0]['num'] );
	}
}

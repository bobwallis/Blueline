<?php

namespace Blueline\CCCBRDataBundle\Repository;

use Doctrine\ORM\EntityRepository;

class SharedRepository extends EntityRepository {

	public function prepareStringForLike( $string ) {
		return str_replace(
			array( '*', '?', ',', '.', ' ', '%%' ),
			array( '%', '_', ' ', ' ', '%', '%' ),
			$string
		);
	}

	public function requestToSearchVariables( $request, $searchable ) {
		$searchVariables = array();
		
		foreach( array_merge( array( 'q' ), $searchable ) as $key ) {
			$value = $request->query->get( $key );
			if( !empty( $value ) ) { $searchVariables[$key] = $value; }
		}
	
		$searchVariables['offset'] = intval( $request->query->get( 'offset' ) );
		$searchVariables['count'] = intval( $request->query->get( 'count' ) );
		if( $searchVariables['offset'] < 0 ) { $searchVariables['offset'] = 0; }
		if( $searchVariables['count'] <= 0 ) { $searchVariables['count'] = 24; }
		
		return $searchVariables;
	}
	
	public function search( $searchVariables, $query = null ) {
		if( is_null( $query ) ) { return array(); }
		
		if( isset( $searchVariables['offset'] ) ) {
			$query->setFirstResult( $searchVariables['offset'] );
		}
		if( isset( $searchVariables['count'] ) ) {
			$query->setMaxResults( $searchVariables['count'] );
		}
	
		return $query->getQuery()->getArrayResult();
	}
}

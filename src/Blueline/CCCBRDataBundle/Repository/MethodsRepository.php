<?php

namespace Blueline\CCCBRDataBundle\Repository;

class MethodsRepository extends SharedRepository {
	public function requestToSearchVariables( $request, $searchable = null ) {
		if( is_null( $searchable ) ) { $searchable = array( 'title', 'stage', 'classification', 'notation', 'leadHeadCode', 'leadHead', 'fchGroups', 'rwRef', 'bnRef', 'tdmmRef', 'pmmRef', 'lengthOfLead', 'numberOfHunts', 'little', 'differential', 'plain', 'trebleDodging', 'palindromic', 'doubleSym', 'rotational', 'firstTowerbellPeal_date', 'firstTowerbellPeal_location', 'firstHandbellPeal_date', 'firstHandbellPeal_location' ); }
		return parent::requestToSearchVariables( $request, $searchable );
	}
	
	public function search( $searchVariables, $query = null ) {
		if( is_null( $query ) ) { $query = $this->createQueryBuilder( 'm' )->select( 'partial m.{title}' ); }
		
		if( isset( $searchVariables['q'] ) ) {
			if( strpos( $searchVariables['q'], '/' ) === 0 ) {
				$query->andWhere( 'm.title REGEXP :qRegexp' )
					->setParameter( 'qRegexp', trim( $searchVariables['q'], '/' ) );
			}
			else {
				$qExplode = explode( ' ', $searchVariables['q'] );
				if( count( $qExplode ) > 1 ) {
					$last = array_pop( $qExplode );
					// If the search ends in a number then use that to filter by stage and remove it from the title search
					$lastStage = \Blueline\Helpers\Stages::toInt( $last );
					if( $lastStage > 0 ) {
						$query->andWhere( 'm.stage = :stageFromQ' )
							->setParameter( 'stageFromQ', $lastStage );
						$searchVariables['q'] = implode( ' ', $qExplode );
						$last = array_pop( $qExplode );
					}
					else {
						$searchVariables['q'] = implode( ' ', $qExplode ).($last?' '.$last:'');
					}

					// Remove non-name parts of the search to test against nameMetaphone
					if( \Blueline\Helpers\Classifications::isClass( $last ) ) {
						$query->andWhere( 'm.classification = :classificationFromQ' )
							->setParameter( 'classificationFromQ', ucwords( strtolower( $last ) ) );
						$last = array_pop( $qExplode );
					}
					while( 1 ) {
						switch( strtolower( $last ) ) {
							case 'little':
								$query->andWhere( 'm.little = :littleFromQ' )
									->setParameter( 'littleFromQ', true );
								$last = array_pop( $qExplode );
								break;
							case 'differential':
								$query->andWhere( 'm.differential = :differentialFromQ' )
									->setParameter( 'differentialFromQ', true );
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
					$nameMetaphone = metaphone( $searchVariables['q'] );
				}
				
				if( empty( $nameMetaphone ) ) {
					$query->andWhere( 'm.title LIKE :qLike' )
						->setParameter( 'qLike', $this->prepareStringForLike( $searchVariables['q'] ) );
				}
				else {
				
				}
				$query->andWhere( $query->expr()->orx( 'm.title LIKE :qLike', 'LEVENSHTEIN_RATIO( :qMetaphone, m.nameMetaphone ) > 90') )
   				->setParameter( 'qLike', $this->prepareStringForLike( $searchVariables['q'] ) )
   				->setParameter( 'qMetaphone', $nameMetaphone );
   		}
		}
		
		// String variables
		foreach( array( 'title', 'classification', 'leadHeadCode', 'leadHead', 'fchGroups', 'rwRef', 'bnRef' ) as $key ) {
			if( isset(  $searchVariables[$key] ) ) {
				if( strpos( $searchVariables[$key], '/' ) === 0 ) {
					$query->andWhere( 'm.'.$key.' REGEXP :'.$key.'Regexp' ) // This doesn't work, which is annoying
						->setParameter( $key.'Regexp', trim( $searchVariables[$key], '/' ) );
				}
				else {
					$query->andWhere( 'm.'.$key.' LIKE :'.$key.'Like' )
						->setParameter( $key.'Like', $this->prepareStringForLike( $searchVariables[$key] ) );
				}
			}
		}
		
		return parent::search( $searchVariables, $query );
	}
	
	public function searchCount( $searchVariables ) {
		$query = $this->createQueryBuilder( 'm' )->select( 'COUNT(m.title) as num' );
		unset( $searchVariables['offset'] );
		unset( $searchVariables['count'] );
		$result = $this->search( $searchVariables, $query );
		return intval( $result[0]['num'] );
	}
}

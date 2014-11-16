<?php
namespace Blueline\AssociationsBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Blueline\BluelineBundle\Helpers\Search;

class AssociationRepository extends EntityRepository
{
    private function createQueryForFindBySearchVariables( $searchVariables, $initialQuery = null )
    {
        $query = ($initialQuery === null)? $this->createQueryBuilder( 'a' )->select( 'a' ) : $initialQuery;

        // Do a more general search if using 'q'
        if( isset(  $searchVariables['q'] ) ) {
            if ( strpos( $searchVariables['q'], '/' ) === 0 && strlen( $searchVariables['q'] ) > 1 ) {
                $query->andWhere( 'REGEXP(a.name, :qRegexp) = TRUE' )
                    ->setParameter( 'qRegexp', trim( $searchVariables['q'], '/' ) );
            } else {
                $query->andWhere( 'LOWER(a.name) LIKE :qLike OR LOWER(a.abbreviation) LIKE :qLike' )
                    ->setParameter( 'qLike', Search::prepareStringForLike( $searchVariables['q'] ) );
            }
        }

        foreach ( array( 'abbreviation', 'name' ) as $key ) {
            if ( isset(  $searchVariables[$key] ) ) {
                if ( strpos( $searchVariables[$key], '/' ) === 0 && strlen( $searchVariables[$key] ) > 1 ) {
                    $query->andWhere( 'REGEXP(a.'.$field.', :'.$key.'Regexp) = TRUE' )
                        ->setParameter( $key.'Regexp', trim( $searchVariables[$key], '/' ) );
                } else {
                    $query->andWhere( 'LOWER(a.'.$field.') LIKE :'.$key.'Like' )
                        ->setParameter( $key.'Like', Search::prepareStringForLike( $searchVariables[$key] ) );
                }
            }
        }

        return $query;
    }

    public function findBySearchVariables( $searchVariables )
    {
        $query = $this->createQueryForFindBySearchVariables( $searchVariables );

        // Offset and count
        if ( isset( $searchVariables['offset'] ) ) {
            $query->setFirstResult( $searchVariables['offset'] );
        }
        if ( isset( $searchVariables['count'] ) ) {
            $query->setMaxResults( $searchVariables['count'] );
        }

        return $query->getQuery()->getResult();
    }

    public function findCountBySearchVariables( $searchVariables )
    {
        $query = $this->createQueryForFindBySearchVariables( $searchVariables, $this->createQueryBuilder( 'a' )->select( 'COUNT(a.name) as num' ) );
        $result = $query->getQuery()->getArrayResult();

        return intval( $result[0]['num'] );
    }
}

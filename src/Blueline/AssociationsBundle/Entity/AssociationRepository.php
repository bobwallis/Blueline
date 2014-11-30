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
                $query->andWhere( 'LOWER(a.name) LIKE :qLike OR LOWER(a.id) LIKE :qLike' )
                    ->setParameter( 'qLike', Search::prepareStringForLike( $searchVariables['q'] ) );
            }
        }

        foreach ( array( 'id', 'name' ) as $key ) {
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

    public function findContainedAssociations($id)
    {
        $bbox = $this->createQueryBuilder( 'a' )
            ->select( 'MAX(t.longitude) as max_lon, MAX(t.latitude) as max_lat, MIN(t.longitude) as min_lon, MIN(t.latitude) as min_lat' )
            ->leftJoin('a.towers', 't')
            ->where( 'a.id = (:id)' )
            ->groupBy('a.id')
            ->setParameter( 'id', $id )
            ->getQuery()
            ->getSingleResult();
        return $this->createQueryBuilder( 'a' )
            ->select( 'a' )
            ->leftJoin('a.towers', 't')
            ->where('a.id != (:id)' )
            ->having('MAX(t.longitude) < (:max_lon) AND MAX(t.latitude) < (:max_lat) AND MIN(t.longitude) > (:min_lon) AND MIN(t.latitude) > (:min_lat)' )
            ->groupBy('a.id')
            ->orderBy('a.name')
            ->setParameter( 'id', $id )
            ->setParameter( 'max_lon', $bbox['max_lon'] )
            ->setParameter( 'max_lat', $bbox['max_lat'] )
            ->setParameter( 'min_lon', $bbox['min_lon'] )
            ->setParameter( 'min_lat', $bbox['min_lat'] )
            ->getQuery()
            ->getArrayResult();
    }
}

<?php
namespace Blueline\TowersBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Blueline\BluelineBundle\Helpers\Search;

class TowerRepository extends EntityRepository
{
    private function createQueryForFindBySearchVariables($searchVariables, $initialQuery = null)
    {
        $query = ($initialQuery === null) ? $this->createQueryBuilder('t')->select('partial t.{id, place, dedication, county, country}') : $initialQuery;

        if (isset($searchVariables['q'])) {
            if (strpos($searchVariables['q'], ' ') !== false) {
                $query->andWhere("CONCAT_WS(' ', LOWER(t.dedication), LOWER(t.place) ,LOWER(t.dedication)) LIKE :qLike");
            } else {
                $query->andWhere('LOWER(t.place) LIKE :qLike');
            }
            $query->setParameter('qLike', Search::prepareStringForLike($searchVariables['q']));
        }

        // String variables
        foreach (array( 'id', 'gridReference', 'postcode', 'country', 'county', 'diocese', 'place', 'dedication', 'note', 'contractor' ) as $key) {
            if (isset($searchVariables[$key])) {
                if (strpos($searchVariables[$key], '/') === 0 && strlen($searchVariables[$key]) > 1) {
                    $query->andWhere('REGEXP(t.'.$key.',:'.$key.'Regexp) = TRUE')
                        ->setParameter($key.'Regexp', trim($searchVariables[$key], '/'));
                } else {
                    $query->andWhere('LOWER(t.'.$key.') LIKE :'.$key.'Like')
                        ->setParameter($key.'Like', Search::prepareStringForLike($searchVariables[$key]));
                }
            }
        }

        return $query;
    }

    public function findBySearchVariables($searchVariables)
    {
        $query = $this->createQueryForFindBySearchVariables($searchVariables);

        // Offset and count
        if (isset($searchVariables['offset'])) {
            $query->setFirstResult($searchVariables['offset']);
        }
        if (isset($searchVariables['count'])) {
            $query->setMaxResults($searchVariables['count']);
        }

        return $query->getQuery()->getResult();
    }

    public function findCountBySearchVariables($searchVariables)
    {
        $query = $this->createQueryForFindBySearchVariables($searchVariables, $this->createQueryBuilder('t')->select('COUNT(t.id) as num'));
        $result = $query->getQuery()->getArrayResult();

        return intval($result[0]['num']);
    }

    public function findNearbyTowers($latitude, $longitude, $count = 8)
    {
        $distance = '( 3959 * acos( cos( radians(:near_lat) ) * cos( radians( t.latitude ) ) * cos( radians( t.longitude ) - radians(:near_long) ) + sin( radians(:near_lat) ) * sin( radians( t.latitude ) ) ) )';

        return array_map(function ($t) { return array_merge($t[0], array( 'distance' => $t['distance'] )); }, array_slice( array_merge(
            $this->createQueryBuilder('t')
                ->select('partial t.{id,place,dedication,latitude,longitude}, 0 as distance')
                  // If the tower is dead on the location then floating point arithmetic
                  // sometimes means that the haversine formula returns >1, so exclude the 
                  // edge case and fetch seperately
                ->orWhere('t.latitude = (:near_lat) AND t.longitude = (:near_long)')
                ->groupBy('t.id')
                ->setParameter('near_lat', $latitude)
                ->setParameter('near_long', $longitude)
                ->getQuery()
                ->getArrayResult(),
            $this->createQueryBuilder('t')
                ->select('partial t.{id,place,dedication,latitude,longitude}, '.$distance.' as distance')
                ->where('t.latitude IS NOT NULL AND t.latitude <> (:near_lat)')
                ->having($distance.' < 20')
                ->groupBy('t.id')
                ->orderBy('distance', 'ASC')
                ->setMaxResults($count)
                ->setParameter('near_lat', $latitude)
                ->setParameter('near_long', $longitude)
                ->getQuery()
                ->getArrayResult()
        ), 0, $count ) );
    }
}

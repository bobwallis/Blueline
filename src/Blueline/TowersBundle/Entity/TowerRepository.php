<?php
namespace Blueline\TowersBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Blueline\BluelineBundle\Helpers\Search;

class TowerRepository extends EntityRepository
{
    public function findNearbyTowers($latitude, $longitude, $count = 8)
    {
        $distance = '( 3959 * acos( cos( radians(:near_lat) ) * cos( radians( t.latitude ) ) * cos( radians( t.longitude ) - radians(:near_long) ) + sin( radians(:near_lat) ) * sin( radians( t.latitude ) ) ) )';

        return array_map(function ($t) { return array_merge($t[0], array( 'distance' => $t['distance'] )); }, array_slice( array_merge(
            $this->createQueryBuilder('t')
                ->select('partial t.{id,place,dedication,latitude,longitude}, 0 as distance')
                  // If the tower is dead on the location then floating point arithmetic
                  // sometimes means that cos( radians(:near_lat) returns >1 and errors,
                  // so exclude the edge case and fetch seperately
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

    public function findOneByIdJoiningBasicAssociationAndPerformanceInformation($id)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT t, partial a.{id,name}, partial p.{id,type,date}, partial m.{title,url} FROM BluelineTowersBundle:Tower t
            LEFT JOIN t.associations a
            LEFT JOIN t.performances p WITH p.type = :performanceType
            LEFT JOIN p.method m
            WHERE t.id = :id
            ORDER BY p.date DESC')
        ->setParameter('id', $id)
        ->setParameter('performanceType', 'firstTowerbellPeal');

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}

<?php
namespace Blueline\AssociationsBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Blueline\BluelineBundle\Helpers\Search;

class AssociationRepository extends EntityRepository
{
    public function findContainedAssociations($id)
    {
        $bbox = $this->findBoundingBox($id);

        return $this->createQueryBuilder('a')
            ->select('a')
            ->leftJoin('a.towers', 't')
            ->where('a.id != (:id)')
            ->having('MAX(t.longitude) < (:max_lon) AND MAX(t.latitude) < (:max_lat) AND MIN(t.longitude) > (:min_lon) AND MIN(t.latitude) > (:min_lat)')
            ->groupBy('a.id')
            ->orderBy('a.name')
            ->setParameter('id', $id)
            ->setParameter('max_lon', $bbox['max_lon'])
            ->setParameter('max_lat', $bbox['max_lat'])
            ->setParameter('min_lon', $bbox['min_lon'])
            ->setParameter('min_lat', $bbox['min_lat'])
            ->getQuery()
            ->getArrayResult();
    }

    public function findBoundingBox($id)
    {
        return $this->createQueryBuilder('a')
            ->select('MAX(t.longitude) as max_lon, MAX(t.latitude) as max_lat, MIN(t.longitude) as min_lon, MIN(t.latitude) as min_lat')
            ->leftJoin('a.towers', 't')
            ->where('a.id = (:id)')
            ->groupBy('a.id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getSingleResult();
    }

    public function findOneByIdJoiningBasicTowerInformation($id)
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT a, partial t.{id,place,dedication}
             FROM BluelineAssociationsBundle:Association a
             LEFT JOIN a.towers t
             WHERE a.id = :id'
        )->setParameter('id', $id);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}

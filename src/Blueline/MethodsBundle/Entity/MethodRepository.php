<?php
namespace Blueline\MethodsBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Blueline\BluelineBundle\Helpers\Search;
use Blueline\MethodsBundle\Helpers\Stages;
use Blueline\MethodsBundle\Helpers\Classifications;

class MethodRepository extends EntityRepository
{
    public function findByURLJoiningPerformancesAndCollections($url)
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT m, p, c FROM BluelineMethodsBundle:Method m
             LEFT JOIN m.performances p
             LEFT JOIN m.collections c
             WHERE m.url = :url'
        )->setParameter('url', $url);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    public function similarMethods($notation, $stage)
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT partial m.{url,title,notation} FROM BluelineMethodsBundle:Method m
            WHERE m.stage = :stage
             AND LEVENSHTEIN_LESS_EQUAL( SUBSTRING(m.notation,0,255), SUBSTRING(:notation,0,255), 2 ) = 1
            ORDER BY m.magic ASC'
        )
        ->setParameter('stage', $stage)
        ->setParameter('notation', $notation);

        try {
            return $query->getArrayResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}

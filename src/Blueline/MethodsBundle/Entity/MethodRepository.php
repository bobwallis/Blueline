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

    public function similarMethodsDifferentOnlyAtTheLeadEnd($url)
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT partial m.{url,title,notation} FROM BluelineMethodsBundle:Method m
             LEFT JOIN m.methodsimilarity2 s
             LEFT JOIN s.method1 m2
             WHERE m2.url = :url AND m.url != :url AND s.onlyDifferentOverLeadEnd = TRUE
            ORDER BY s.similarity ASC'
        )
        ->setParameter('url', $url);

        try {
            return array_map( function($m) {
                $m['leadEndNotation'] =  trim(strrchr($m['notation'], ','), ',');
                unset($m['notation']);
                return $m;
            }, $query->getArrayResult());
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    public function similarMethodsExcludingThoseOnlyDifferentAtTheLeadEnd($url)
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT partial m.{url,title} FROM BluelineMethodsBundle:Method m
             LEFT JOIN m.methodsimilarity2 s
             LEFT JOIN s.method1 m2
             WHERE m2.url = :url AND m.url != :url AND s.onlyDifferentOverLeadEnd IS NULL
            ORDER BY s.similarity ASC'
        )
        ->setMaxResults(8)
        ->setParameter('url', $url);
        
        try {
            return $query->getArrayResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}

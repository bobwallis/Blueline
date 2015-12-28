<?php
namespace Blueline\MethodsBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Blueline\BluelineBundle\Helpers\Search;
use Blueline\MethodsBundle\Helpers\Stages;
use Blueline\MethodsBundle\Helpers\Classifications;

class MethodRepository extends EntityRepository
{
    private function createQueryForFindBySearchVariables($searchVariables, $initialQuery = null)
    {
        $query = ($initialQuery === null) ? $this->createQueryBuilder('m')->select('partial m.{title,url}') : $initialQuery;

        if (isset($searchVariables['q'])) {
            if (strpos($searchVariables['q'], '/') === 0 && strlen($searchVariables['q']) > 1) {
                if (@preg_match($searchVariables['q'].'/', ' ') === false) {
                    throw new BadRequestHttpException('Invalid regular expression');
                }
                $query->andWhere('REGEXP(m.title, :qRegexp) = TRUE')
                    ->setParameter('qRegexp', trim($searchVariables['q'], '/'));
            } else {
                $qExplode = explode(' ', $searchVariables['q']);
                if (count($qExplode) > 1) {
                    $last = array_pop($qExplode);
                    // If the search ends in a number then use that to filter by stage and remove it from the title search
                    $lastStage = Stages::toInt($last);
                    if ($lastStage > 0) {
                        $query->andWhere('m.stage = :stageFromQ')
                            ->setParameter('stageFromQ', $lastStage);
                        $searchVariables['q'] = implode(' ', $qExplode);
                        $last = array_pop($qExplode);
                    } else {
                        $searchVariables['q'] = implode(' ', $qExplode).($last ? ' '.$last : '');
                    }

                    // Remove non-name parts of the search to test against nameMetaphone
                    if (Classifications::isClass($last)) {
                        $query->andWhere('m.classification = :classificationFromQ')
                            ->setParameter('classificationFromQ', ucwords(strtolower($last)));
                        $last = array_pop($qExplode);
                    }
                    while (1) {
                        switch (strtolower($last)) {
                            case 'little':
                                $query->andWhere('m.little = :littleFromQ')
                                    ->setParameter('littleFromQ', true);
                                $last = array_pop($qExplode);
                                break;
                            case 'differential':
                                $query->andWhere('m.differential = :differentialFromQ')
                                    ->setParameter('differentialFromQ', true);
                                $last = array_pop($qExplode);
                                break;
                            default:
                                break 2;
                        }
                    }
                    // This will be used to test against nameMetaphone
                    $nameMetaphone = metaphone(implode(' ', $qExplode).($last ? ' '.$last : ''));
                } else {
                    $nameMetaphone = metaphone($searchVariables['q']);
                }

                if (empty($nameMetaphone)) {
                    $query->andWhere('LOWER(m.title) LIKE :qLike')
                        ->setParameter('qLike', Search::prepareStringForLike($searchVariables['q']));
                } else {
                    $query->andWhere($query->expr()->orx('LOWER(m.title) LIKE :qLike', 'LEVENSHTEIN_RATIO( :qMetaphone, m.nameMetaphone ) > 90'))
                       ->setParameter('qLike', Search::prepareStringForLike($searchVariables['q']))
                       ->setParameter('qMetaphone', $nameMetaphone);
                }
            }
        }

        // String variables
        foreach (array( 'title', 'classification', 'leadHeadCode', 'leadHead', 'fchGroups' ) as $key) {
            if (isset($searchVariables[$key])) {
                if (strpos($searchVariables[$key], '/') === 0 && strlen($searchVariables[$key]) > 1) {
                    $query->andWhere('REGEXP(m.'.$key.', :'.$key.'Regexp) = TRUE')
                        ->setParameter($key.'Regexp', trim($searchVariables[$key], '/'));
                } else {
                    $query->andWhere('LOWER(m.'.$key.') LIKE :'.$key.'Like')
                        ->setParameter($key.'Like', Search::prepareStringForLike($searchVariables[$key]));
                }
            }
        }

        return $query;
    }

    public function findBySearchVariables($searchVariables)
    {
        $query = $this->createQueryForFindBySearchVariables($searchVariables);
        
        // Sort/Order
        $query->orderBy('m.'.(isset($searchVariables['sort'])?$searchVariables['sort']:'magic'), isset($searchVariables['order'])?$searchVariables['order']:'ASC');

        // Offset
        if (isset($searchVariables['offset'])) {
            $query->setFirstResult($searchVariables['offset']);
        }
        // Count
        if (isset($searchVariables['count'])) {
            $query->setMaxResults($searchVariables['count']);
        }

        return $query->getQuery()->getResult();
    }

    public function findCountBySearchVariables($searchVariables)
    {
        $query = $this->createQueryForFindBySearchVariables($searchVariables, $this->createQueryBuilder('m')->select('COUNT(m.title) as num'));
        $result = $query->getQuery()->getArrayResult();

        return intval($result[0]['num']);
    }

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
}

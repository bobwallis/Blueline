<?php

namespace Blueline\Repository;

use Blueline\Entity\Method;
use Doctrine\ORM\EntityRepository;

/**
 * Repository for querying Method entities.
 *
 * Handles complex DQL queries with partial selects, related entity eager-loading,
 * and similarity-based method finding. Used for method detail pages, similarity matching,
 * and related method recommendations.
 */
class MethodRepository extends EntityRepository
{
    /**
     * Find a method by URL with related performances and collection memberships eagerly loaded.
     *
     * Optimises single method detail page queries by loading performances and
     * collection memberships in a single DQL query (avoids N+1 lazy loading).
     *
     * @param string $url The method URL identifier (slug)
     *
     * @return Method|null The method with relationships loaded, or null if not found
     */
    public function findByURLJoiningPerformancesAndCollections($url): ?Method
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT m, p, c FROM Blueline\Entity\Method m
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

    /**
     * Find a method by URL (simple lookup without eager loading).
     *
     * Used for quick lookups when related entities are not needed.
     * Prefer findByURLJoiningPerformancesAndCollections() for detail pages.
     *
     * @param string $url The method URL identifier
     *
     * @return Method|null The method, or null if not found
     */
    public function findByURL($url): ?Method
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT m FROM Blueline\Entity\Method m
             WHERE m.url = :url'
        )->setParameter('url', $url);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Find similar methods that differ only in the lead end (final change of the lead) using the
     * flag set for that purpose.
     *
     * Ordered by similarity score (ascending, i.e., most similar first).
     *
     * @param string $url The reference method URL
     *
     * @return array|null Array of similar methods with lead end notation, or null if none found
     */
    public function similarMethodsDifferentOnlyAtTheLeadEnd($url): ?array
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT partial m.{url,title,notation} FROM Blueline\Entity\Method m
             LEFT JOIN m.methodSimilarity2 s
             LEFT JOIN s.method1 m2
             WHERE m2.url = :url AND m.url != :url AND s.onlyDifferentOverLeadEnd = TRUE
            ORDER BY s.similarity ASC'
        )
        ->setParameter('url', $url);

        try {
            return array_map(function ($m) {
                $m['leadEndNotation'] = trim(strrchr($m['notation'], ','), ',');
                unset($m['notation']);

                return $m;
            }, $query->getArrayResult());
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Find similar methods that differ only in the half-lead (first half of lead) using the
     * flag set for that purpose.
     *
     * Ordered by similarity score (ascending).
     *
     * @param string $url The reference method URL
     *
     * @return array|null Array of similar methods with half-lead notation, or null if none found
     */
    public function similarMethodsDifferentOnlyAtTheHalfLead($url): ?array
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT partial m.{url,title,notation} FROM Blueline\Entity\Method m
             LEFT JOIN m.methodSimilarity2 s
             LEFT JOIN s.method1 m2
             WHERE m2.url = :url AND m.url != :url AND s.onlyDifferentOverHalfLead = TRUE
            ORDER BY s.similarity ASC'
        )
        ->setParameter('url', $url);

        try {
            return array_map(function ($m) {
                preg_match('/([0-9A-Z]*),[0-9A-Z]*$/', $m['notation'], $matches);
                $m['halfLeadNotation'] = $matches[1];
                unset($m['notation']);

                return $m;
            }, $query->getArrayResult());
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Find similar methods that differ only in both half-lead and lead end using the flags set
     * for that purpose.
     *
     * Ordered by similarity score (ascending).
     *
     * @param string $url The reference method URL
     *
     * @return array|null Array of similar methods with both notations, or null if none found
     */
    public function similarMethodsDifferentOnlyAtTheHalfLeadAndLeadEnd($url): ?array
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT partial m.{url,title,notation} FROM Blueline\Entity\Method m
             LEFT JOIN m.methodSimilarity2 s
             LEFT JOIN s.method1 m2
             WHERE m2.url = :url AND m.url != :url AND s.onlyDifferentOverLeadEndAndHalfLead = TRUE
            ORDER BY s.similarity ASC'
        )
        ->setParameter('url', $url);

        try {
            return array_map(function ($m) {
                preg_match('/([0-9A-Z]*),([0-9A-Z]*)$/', $m['notation'], $matches);
                $m['halfLeadNotation'] = $matches[1];
                $m['leadEndNotation'] = $matches[2];
                unset($m['notation']);

                return $m;
            }, $query->getArrayResult());
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Find methods that are significantly similar (differ in non-trivial ways).
     *
     * Excludes methods that differ only at lead end or half-lead (considered trivial variants).
     * Limited to 8 results for UI display. Ordered by similarity score (ascending).
     *
     * @param string $url The reference method URL
     *
     * @return array|null Array of up to 8 similar methods, or null if none found
     */
    public function similarMethodsExcludingThoseOnlyDifferentAtTheLeadEndOrHalfLead($url): ?array
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT partial m.{url,title} FROM Blueline\Entity\Method m
             LEFT JOIN m.methodSimilarity2 s
             LEFT JOIN s.method1 m2
             WHERE m2.url = :url AND m.url != :url AND s.onlyDifferentOverLeadEnd IS NULL AND s.onlyDifferentOverHalfLead IS NULL AND s.onlyDifferentOverLeadEndAndHalfLead IS NULL
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

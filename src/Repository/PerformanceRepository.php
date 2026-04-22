<?php

namespace Blueline\Repository;

use Blueline\Entity\Performance;
use Doctrine\ORM\EntityRepository;

/**
 * Repository for querying Performance entities.
 *
 * Performances represent documented bell-ringing events where a method was rung.
 */
class PerformanceRepository extends EntityRepository
{
    /**
     * Find the current method URL associated with a historical rung URL.
     *
     * Maps legacy rung URLs to current method URLs. Used for redirecting old
     * performance references to current method records.
     *
     * @param string $url The historical rung method URL
     *
     * @return string|null The current method URL identifier, or null if not found
     */
    public function findURLByRungURL($url): ?string
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT p, m FROM Blueline\Entity\Performance p
             LEFT JOIN p.method m
             WHERE p.type = \'renamedMethod\' AND p.rung_url = :url'
        )->setParameter('url', $url);

        try {
            $result = $query->getSingleResult();

            return $result->getMethod()->getUrl();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}

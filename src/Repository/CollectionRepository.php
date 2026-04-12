<?php

namespace Blueline\Repository;

use Doctrine\ORM\EntityRepository;
use Blueline\Entity\Collection;

/**
 * Repository for querying Collection entities.
 *
 * Collections group related methods for browsing and analysis.
 * Provides methods for finding collections by ID and listing all available collections.
 */
class CollectionRepository extends EntityRepository
{
}

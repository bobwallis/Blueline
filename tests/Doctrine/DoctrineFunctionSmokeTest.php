<?php
namespace Blueline\Tests\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DoctrineFunctionSmokeTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
    }

    public function testLevenshteinFunctionReturnsZeroForIdenticalStrings(): void
    {
        $result = $this->entityManager->createQuery(
            'SELECT LEVENSHTEIN(m.title, m.title) AS distance
               FROM Blueline\\Entity\\Method m'
        )
        ->setMaxResults(1)
        ->getSingleScalarResult();

        $this->assertSame('0', (string) $result);
    }

    public function testLevenshteinRatioFunctionReturnsHundredForIdenticalStrings(): void
    {
        $result = $this->entityManager->createQuery(
            'SELECT LEVENSHTEIN_RATIO(m.title, m.title) AS ratio
               FROM Blueline\\Entity\\Method m'
        )
        ->setMaxResults(1)
        ->getSingleScalarResult();

        $this->assertGreaterThanOrEqual(99.9, (float) $result);
    }

    public function testRegexpFunctionMatchesKnownMethodTitlePattern(): void
    {
        $title = $this->entityManager->createQuery(
            'SELECT m.title
               FROM Blueline\\Entity\\Method m'
        )
        ->setMaxResults(1)
        ->getSingleScalarResult();

        $prefix = substr($title, 0, 3);
        $pattern = '^'.preg_quote($prefix, '/').'.*';

        $result = $this->entityManager->createQuery(
            'SELECT COUNT(m.title)
               FROM Blueline\\Entity\\Method m
              WHERE m.title = :title
                AND REGEXP(m.title, :pattern) = TRUE'
        )
        ->setParameter('title', $title)
        ->setParameter('pattern', $pattern)
        ->getSingleScalarResult();

        $this->assertSame('1', (string) $result);
    }
}

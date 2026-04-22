<?php

namespace Blueline\Tests\Helpers;

use Blueline\Helpers\MethodSimilarity;
use PHPUnit\Framework\TestCase;

class MethodSimilarityTest extends TestCase
{
    public function testCalculateReturnsZeroForIdenticalRows(): void
    {
        $rows = ['123456', '214365', '241635'];

        $this->assertSame(0.0, MethodSimilarity::calculate($rows, $rows, 6));
    }

    public function testCalculateAcceptsNotationStringsAndReturnsFiniteDistance(): void
    {
        $distance = MethodSimilarity::calculate(
            'x16x16x16,12',
            'x16x14x12,12',
            6
        );

        $this->assertIsNumeric($distance);
        $this->assertGreaterThanOrEqual(0.0, $distance);
    }

    public function testCalculateHandlesEmptyInputs(): void
    {
        $this->assertSame(2, MethodSimilarity::calculate([], ['123456', '214365'], 6));
        $this->assertSame(3, MethodSimilarity::calculate(['123456', '214365', '241635'], [], 6));
    }

    public function testCalculateReturnsLimitForDissimilarMethodsWithLimit(): void
    {
        // Two very different row sequences — distance should exceed the limit.
        $rows1 = ['123456', '214365', '241635', '426153', '462513', '645231'];
        $rows2 = ['654321', '563412', '536142', '351624', '315264', '132546'];
        $limit = 2;

        $result = MethodSimilarity::calculate($rows1, $rows2, 6, $limit);
        $this->assertEquals($limit, $result);
    }

    public function testCalculateWithLimitMatchesUnlimitedForSimilarMethods(): void
    {
        // Same rows — distance is 0 regardless of limit.
        $rows = ['123456', '214365', '241635'];
        $this->assertSame(0.0, MethodSimilarity::calculate($rows, $rows, 6, 5));
    }
}

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
}

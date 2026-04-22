<?php

namespace Blueline\Tests\Entity;

use Blueline\Entity\Method;
use Blueline\Entity\MethodSimilarity;
use PHPUnit\Framework\TestCase;

class MethodSimilarityTest extends TestCase
{
    private MethodSimilarity $methodSimilarity;

    protected function setUp(): void
    {
        $this->methodSimilarity = new MethodSimilarity();
    }

    public function testDefaultsAreNull()
    {
        $this->assertNull($this->methodSimilarity->getSimilarity());
        $this->assertNull($this->methodSimilarity->getOnlyDifferentOverLeadEnd());
        $this->assertNull($this->methodSimilarity->getOnlyDifferentOverHalfLead());
        $this->assertNull($this->methodSimilarity->getOnlyDifferentOverLeadEndAndHalfLead());
        $this->assertNull($this->methodSimilarity->getMethod1());
        $this->assertNull($this->methodSimilarity->getMethod2());
    }

    public function testSetAndGetSimilarity()
    {
        $this->methodSimilarity->setSimilarity(0.875);

        $this->assertEquals(0.875, $this->methodSimilarity->getSimilarity());
    }

    public function testSetAndGetBooleanFlags()
    {
        $this->methodSimilarity->setOnlyDifferentOverLeadEnd(true);
        $this->methodSimilarity->setOnlyDifferentOverHalfLead(false);
        $this->methodSimilarity->setOnlyDifferentOverLeadEndAndHalfLead(true);

        $this->assertTrue($this->methodSimilarity->getOnlyDifferentOverLeadEnd());
        $this->assertFalse($this->methodSimilarity->getOnlyDifferentOverHalfLead());
        $this->assertTrue($this->methodSimilarity->getOnlyDifferentOverLeadEndAndHalfLead());
    }

    public function testSetAndGetMethods()
    {
        $method1 = new Method(['title' => 'Cambridge Surprise Minor']);
        $method2 = new Method(['title' => 'Yorkshire Surprise Major']);

        $this->methodSimilarity->setMethod1($method1);
        $this->methodSimilarity->setMethod2($method2);

        $this->assertSame($method1, $this->methodSimilarity->getMethod1());
        $this->assertSame($method2, $this->methodSimilarity->getMethod2());
    }
}

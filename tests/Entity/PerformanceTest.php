<?php

namespace Blueline\Tests\Entity;

use Blueline\Entity\Method;
use Blueline\Entity\Performance;
use PHPUnit\Framework\TestCase;

class PerformanceTest extends TestCase
{
    private Performance $performance;

    protected function setUp(): void
    {
        $this->performance = new Performance();
    }

    public function testCreateWithSetAllData()
    {
        $date = new \DateTime('2024-01-02');

        $performance = new Performance([
            'type' => 'Peal',
            'date' => $date,
            'rung_title' => 'Example Touch',
            'rung_url' => 'https://example.test/touch',
        ]);

        $this->assertEquals('Peal', $performance->getType());
        $this->assertEquals('2024-01-02', $performance->getDate()->format('Y-m-d'));
        $this->assertEquals('Example Touch', $performance->getRungTitle());
        $this->assertEquals('https://example.test/touch', $performance->getRungUrl());
    }

    public function testSetAndGetType()
    {
        $this->performance->setType('Quarter Peal');

        $this->assertEquals('Quarter Peal', $this->performance->getType());
    }

    public function testSetAndGetDate()
    {
        $date = new \DateTime('2023-12-25');
        $this->performance->setDate($date);

        $this->assertEquals('2023-12-25', $this->performance->getDate()->format('Y-m-d'));
    }

    public function testSetAndGetMethod()
    {
        $method = new Method(['title' => 'Cambridge Surprise Minor']);
        $this->performance->setMethod($method);

        $this->assertSame($method, $this->performance->getMethod());
    }

    public function testGetLocation()
    {
        $this->performance->setAll([
            'location_room' => 'Tower Chamber',
            'location_town' => 'Cambridge',
            'location_country' => 'UK',
        ]);

        $this->assertEquals('Tower Chamber, Cambridge, UK', $this->performance->getLocation());
    }

    public function testToString()
    {
        $reflection = new \ReflectionClass($this->performance);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($this->performance, 42);

        $this->assertEquals('Performance:42', (string) $this->performance);
    }

    public function testToArray()
    {
        $method = new Method(['title' => 'Yorkshire Surprise Major']);
        $date = new \DateTime('2024-03-10');

        $this->performance->setAll([
            'type' => 'Peal',
            'date' => $date,
            'society' => 'Test Society',
            'reference' => 'Ref-1',
            'method' => $method,
        ]);

        $array = $this->performance->__toArray();

        $this->assertArrayNotHasKey('id', $array);
        $this->assertArrayNotHasKey('method', $array);
        $this->assertEquals('Peal', $array['type']);
        $this->assertEquals('2024-03-10', $array['date']);
        $this->assertEquals('Test Society', $array['society']);
        $this->assertEquals('Ref-1', $array['reference']);
    }
}

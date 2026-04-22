<?php

namespace Blueline\Tests\Entity;

use Blueline\Entity\Collection;
use Blueline\Entity\Method;
use Blueline\Entity\MethodInCollection;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    private Collection $collection;

    protected function setUp(): void
    {
        $this->collection = new Collection();
    }

    /**
     * Test collection can be created with ID.
     */
    public function testCreateWithId()
    {
        $collection = new Collection(['id' => 'test-collection']);
        $this->assertEquals('test-collection', $collection->getId());
    }

    /**
     * Test collection can set and get name.
     */
    public function testSetAndGetName()
    {
        $name = 'Test Collection';
        $this->collection->setName($name);
        $this->assertEquals($name, $this->collection->getName());
    }

    /**
     * Test collection can set and get description.
     */
    public function testSetAndGetDescription()
    {
        $description = 'This is a test collection';
        $this->collection->setDescription($description);
        $this->assertEquals($description, $this->collection->getDescription());
    }

    /**
     * Test setAll with multiple properties.
     */
    public function testSetAll()
    {
        $data = [
            'id' => 'collection-1',
            'name' => 'Royal Methods',
            'description' => 'A collection of Royal methods',
        ];

        $this->collection->setAll($data);

        $this->assertEquals('collection-1', $this->collection->getId());
        $this->assertEquals('Royal Methods', $this->collection->getName());
        $this->assertEquals('A collection of Royal methods', $this->collection->getDescription());
    }

    /**
     * Test methods collection is initialized.
     */
    public function testMethodsInitialized()
    {
        $methods = $this->collection->getMethods();

        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $methods);
        $this->assertCount(0, $methods);
    }

    /**
     * Test adding method to collection.
     */
    public function testAddMethod()
    {
        $method = new Method(['title' => 'Test Method']);
        $collection = new Collection(['id' => 'test-collection']);
        $methodInCollection = new MethodInCollection([
            'method' => $method,
            'collection' => $collection,
            'position' => 1,
        ]);

        $this->collection->addMethod($methodInCollection);

        $methods = $this->collection->getMethods();
        $this->assertCount(1, $methods);
        $this->assertTrue($methods->contains($methodInCollection));
        $this->assertSame($method, $methodInCollection->getMethod());
        $this->assertSame($collection, $methodInCollection->getCollection());
        $this->assertEquals(1, $methodInCollection->getPosition());
    }

    /**
     * Test removing method from collection.
     */
    public function testRemoveMethod()
    {
        $method = new Method(['title' => 'Test Method']);
        $collection = new Collection(['id' => 'test-collection']);
        $methodInCollection = new MethodInCollection([
            'method' => $method,
            'collection' => $collection,
            'position' => 1,
        ]);

        $this->collection->addMethod($methodInCollection);
        $this->assertCount(1, $this->collection->getMethods());

        $this->collection->removeMethod($methodInCollection);
        $this->assertCount(0, $this->collection->getMethods());
    }

    /**
     * Test string representation.
     */
    public function testToString()
    {
        $this->collection->setId('test-id');
        $this->assertEquals('Collection:test-id', (string) $this->collection);
    }

    /**
     * Test toArray filters out internal properties.
     */
    public function testToArray()
    {
        $this->collection->setAll([
            'id' => 'col-1',
            'name' => 'Test Collection',
            'description' => 'Description here',
        ]);

        $array = $this->collection->__toArray();

        $this->assertArrayNotHasKey('id', $array);
        $this->assertArrayNotHasKey('methods', $array);
        $this->assertEquals('Test Collection', $array['name']);
        $this->assertEquals('Description here', $array['description']);
    }
}

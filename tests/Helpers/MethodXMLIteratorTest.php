<?php
namespace Blueline\Tests\Helpers;

use Blueline\Helpers\MethodXMLIterator;
use PHPUnit\Framework\TestCase;

class MethodXMLIteratorTest extends TestCase
{
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        $this->tempFiles = [];
    }

    public function testIteratorParsesValidFixtureAndExposesExpectedKeys(): void
    {
        $file = $this->createTempXmlFile('methods.xml', $this->validXmlFixture());
        $iterator = new MethodXMLIterator($file);

        $this->assertCount(1, $iterator);

        $iterator->rewind();
        $method = $iterator->current();

        $this->assertSame('Test Bob Minor', $method['title']);
        $this->assertSame('Test_Bob_Minor', $method['url']);
        $this->assertSame(6, $method['stage']);
        $this->assertSame('x16x16x16x16x16x12', $method['notationexpanded']);
        $this->assertArrayHasKey('magic', $method);
    }

    public function testIteratorCountsAndIteratesConsistently(): void
    {
        $file = $this->createTempXmlFile('methods.xml', $this->validXmlFixture());
        $iterator = new MethodXMLIterator($file);

        $seen = 0;
        foreach ($iterator as $row) {
            ++$seen;
            $this->assertArrayHasKey('title', $row);
        }

        $this->assertSame(count($iterator), $seen);
    }

    public function testIteratorMarksProvisionalWhenFilenameEndsProvisionalXml(): void
    {
        $file = $this->createTempXmlFile('methods-provisional.xml', $this->validXmlFixture());
        $iterator = new MethodXMLIterator($file);

        $iterator->rewind();
        $method = $iterator->current();

        $this->assertTrue($method['provisional']);
    }

    public function testIteratorGracefullyHandlesInvalidXml(): void
    {
        $file = $this->createTempXmlFile('broken.xml', '<methods><methodSet>');

        set_error_handler(static function (): bool {
            return true;
        }, E_USER_NOTICE);

        try {
            $iterator = new MethodXMLIterator($file);
        } finally {
            restore_error_handler();
        }

        $this->assertCount(0, $iterator);
        $this->assertFalse($iterator->valid());
    }

    private function createTempXmlFile(string $fileName, string $contents): string
    {
        $path = sys_get_temp_dir().'/'.uniqid('blueline-test-', true).'-'.$fileName;
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }

    private function validXmlFixture(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<methods>
  <methodSet>
    <properties>
      <classification>Bob</classification>
    </properties>
    <method>
      <title>Test Bob Minor</title>
      <name>Test</name>
      <stage>6</stage>
      <notation>-16-16-16,12</notation>
      <lengthOfLead>12</lengthOfLead>
      <leadHead>142635</leadHead>
      <numberOfHunts>1</numberOfHunts>
    </method>
  </methodSet>
</methods>
XML;
    }
}

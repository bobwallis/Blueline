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

    public function testIteratorParsesPerformanceSocietyWhenProvided(): void
    {
        $file = $this->createTempXmlFile('methods.xml', $this->validXmlFixtureWithPerformances());
        $iterator = new MethodXMLIterator($file);

        $iterator->rewind();
        $method = $iterator->current();

        $this->assertArrayHasKey('performances', $method);
        $this->assertCount(2, $method['performances']);
        $this->assertSame('Australian & New Zealand Association', $method['performances'][0]['society']);
        $this->assertSame('Salisbury Diocesan Guild', $method['performances'][1]['society']);
    }

    public function testIteratorParsesCccbrId(): void
    {
        $file = $this->createTempXmlFile('methods.xml', $this->xmlFixtureWithNewFields());
        $iterator = new MethodXMLIterator($file);

        $iterator->rewind();
        $method = $iterator->current();

        $this->assertSame('m12345', $method['cccbr_id']);
    }

    public function testIteratorParsesMethodReferencesAsTypedToken(): void
    {
        $file = $this->createTempXmlFile('methods.xml', $this->xmlFixtureWithNewFields());
        $iterator = new MethodXMLIterator($file);

        $iterator->rewind();
        $method = $iterator->current();

        $this->assertSame('rwRef: 2005/235; bnRef: V13', $method['method_references']);
    }

    public function testIteratorParsesExtensionConstruction(): void
    {
        $file = $this->createTempXmlFile('methods.xml', $this->xmlFixtureWithNewFields());
        $iterator = new MethodXMLIterator($file);

        $iterator->rewind();
        $method = $iterator->current();

        $this->assertSame('EP1-4', $method['extensionconstruction']);
    }

    public function testIteratorParsesGenericPerformanceTypes(): void
    {
        $file = $this->createTempXmlFile('methods.xml', $this->xmlFixtureWithNewFields());
        $iterator = new MethodXMLIterator($file);

        $iterator->rewind();
        $method = $iterator->current();

        $this->assertArrayHasKey('performances', $method);
        $this->assertCount(2, $method['performances']);
        $this->assertSame('firstTowerbellPeal', $method['performances'][0]['type']);
        $this->assertSame('firstInclusionInHandbellQuarterPeal', $method['performances'][1]['type']);
        $this->assertSame('2026-01-01', $method['performances'][0]['date']);
    }

    public function testIteratorParsesPerformanceReferencesAsTypedToken(): void
    {
        $file = $this->createTempXmlFile('methods.xml', $this->xmlFixtureWithPerformanceReferences());
        $iterator = new MethodXMLIterator($file);

        $iterator->rewind();
        $method = $iterator->current();

        $this->assertArrayHasKey('performances', $method);
        $this->assertSame('rwRef: 2023/100', $method['performances'][0]['reference']);
    }

    public function testMethodWithNoReferencesHasNoMethodReferencesKey(): void
    {
        $file = $this->createTempXmlFile('methods.xml', $this->validXmlFixture());
        $iterator = new MethodXMLIterator($file);

        $iterator->rewind();
        $method = $iterator->current();

        $this->assertArrayNotHasKey('method_references', $method);
        $this->assertArrayNotHasKey('extensionconstruction', $method);
        $this->assertArrayNotHasKey('cccbr_id', $method);
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

        private function validXmlFixtureWithPerformances(): string
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
            <performances>
                <firstTowerbellPeal>
                    <date>2026-03-22</date>
                    <society>Australian &amp; New Zealand Association</society>
                    <location>
                        <town>Salisbury</town>
                    </location>
                </firstTowerbellPeal>
                <firstHandbellPeal>
                    <date>2026-03-23</date>
                    <society>Salisbury Diocesan Guild</society>
                    <location>
                        <town>Salisbury</town>
                    </location>
                </firstHandbellPeal>
            </performances>
        </method>
    </methodSet>
</methods>
XML;
        }

    private function xmlFixtureWithNewFields(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<methods>
  <methodSet>
    <properties>
      <classification>Bob</classification>
    </properties>
    <method
      id="m12345">
      <title>Test Bob Minor</title>
      <name>Test</name>
      <stage>6</stage>
      <notation>-16-16-16,12</notation>
      <lengthOfLead>12</lengthOfLead>
      <leadHead>142635</leadHead>
      <numberOfHunts>1</numberOfHunts>
      <extensionConstruction>EP1-4</extensionConstruction>
      <performances>
        <firstTowerbellPeal>
          <date>2026-01-01</date>
          <society>Test Society</society>
          <location>
            <town>Testtown</town>
          </location>
        </firstTowerbellPeal>
        <firstInclusionInHandbellQuarterPeal>
          <date>2026-02-01</date>
          <society>Another Society</society>
          <location>
            <town>Othertown</town>
          </location>
        </firstInclusionInHandbellQuarterPeal>
      </performances>
      <references>
        <rwRef>2005/235</rwRef>
        <bnRef>V13</bnRef>
      </references>
    </method>
  </methodSet>
</methods>
XML;
    }

    private function xmlFixtureWithPerformanceReferences(): string
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
      <performances>
        <firstTowerbellPeal>
          <date>2026-01-01</date>
          <society>Test Society</society>
          <location>
            <town>Testtown</town>
          </location>
          <references>
            <rwRef>2023/100</rwRef>
          </references>
        </firstTowerbellPeal>
      </performances>
    </method>
  </methodSet>
</methods>
XML;
    }
}

<?php
namespace Blueline\Tests\Entity;

use Blueline\Entity\Method;
use Blueline\Entity\Performance;
use Blueline\Helpers\PlaceNotation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MethodTest extends TestCase
{
    private const CANONICAL_METHODS = [
        'Cambridge Surprise Minor' => [
            'title' => 'Cambridge Surprise Minor',
            'notation' => 'x36x14x12x36x14x56,12',
            'stage' => 6,
            'classification' => 'Surprise',
            'nameMetaphone' => 'KMRJ',
            'notationExpanded' => 'x36x14x12x36x14x56x14x36x12x14x36x12',
            'leadHeadCode' => 'b',
            'leadHead' => '156342',
            'lengthOfLead' => 24,
            'lengthOfCourse' => 120,
            'numberOfLeads' => 5,
            'numberOfHunts' => 1,
            'plain' => false,
            'trebleDodging' => true,
            'palindromic' => true,
            'doubleSym' => false,
            'rotational' => false,
            'calls' => [
                'Bob' => ['symbol' => '-', 'notation' => '14', 'from' => 0, 'every' => 24, 'cover' => 1],
                'Single' => ['symbol' => 's', 'notation' => '1234', 'from' => 0, 'every' => 24, 'cover' => 1],
            ],
            'ruleOffs' => ['every' => 24, 'from' => 0],
            'callingPositions' => ['from' => 0, 'every' => 24, 'titles' => ['F', 'I', 'W', 'B', 'H']],
            'url' => 'Cambridge_Surprise_Minor',
            'abbreviation' => 'Ca',
            'symmetryText' => 'Palindromic',
            'notationSiril' => '&-3-4-2-3-4-5, +2',
        ],
        'Grandsire Triples' => [
            'title' => 'Grandsire Triples',
            'notation' => '3,1.7.1.7.1.7.1',
            'stage' => 7,
            'classification' => 'Bob',
            'nameMetaphone' => 'KRNTSR',
            'notationExpanded' => '3.1.7.1.7.1.7.1.7.1.7.1.7.1',
            'leadHeadCode' => 'a',
            'leadHead' => '1253746',
            'lengthOfLead' => 14,
            'lengthOfCourse' => 70,
            'numberOfLeads' => 5,
            'numberOfHunts' => 2,
            'plain' => true,
            'trebleDodging' => false,
            'palindromic' => true,
            'doubleSym' => false,
            'rotational' => false,
            'calls' => [
                'Bob' => ['symbol' => '-', 'notation' => '3.1', 'from' => -1, 'every' => 14, 'cover' => 2],
                'Single' => ['symbol' => 's', 'notation' => '3.123', 'from' => -1, 'every' => 14, 'cover' => 2],
            ],
            'ruleOffs' => ['every' => 14, 'from' => 0],
            'callingPositions' => [],
            'url' => 'Grandsire_Triples',
            'abbreviation' => 'Gr',
            'symmetryText' => 'Palindromic',
            'notationSiril' => '+3, &1.7.1.7.1.7.1',
        ],
        'Bristol Surprise Maximus' => [
            'title' => 'Bristol Surprise Maximus',
            'notation' => 'x5Tx14.5Tx5T.36.14x7T.58.16x9T.70.18x18.9Tx18x1T,1T',
            'stage' => 12,
            'classification' => 'Surprise',
            'nameMetaphone' => 'BRSTL',
            'notationExpanded' => 'x5Tx14.5Tx5T.36.14x7T.58.16x9T.70.18x18.9Tx18x1Tx18x9T.18x18.70.9Tx16.58.7Tx14.36.5Tx5T.14x5Tx1T',
            'leadHeadCode' => 'j',
            'leadHead' => '1795E3T20486',
            'lengthOfLead' => 48,
            'lengthOfCourse' => 528,
            'numberOfLeads' => 11,
            'numberOfHunts' => 1,
            'plain' => false,
            'trebleDodging' => true,
            'palindromic' => true,
            'doubleSym' => true,
            'rotational' => true,
            'calls' => [
                'Bob' => ['symbol' => '-', 'notation' => '10', 'every' => 48, 'from' => 0, 'cover' => 1],
                'Single' => ['symbol' => 's', 'notation' => '10ET', 'every' => 48, 'from' => 0, 'cover' => 1],
            ],
            'ruleOffs' => ['every' => 48, 'from' => 0],
            'callingPositions' => [
                'from' => 0,
                'every' => 48,
                'titles' => ['S', 'I', 'E', 'M', 'V', 'F', 'H', 'N', 'B', 'X', 'W'],
            ],
            'url' => 'Bristol_Surprise_Maximus',
            'abbreviation' => 'Br',
            'symmetryText' => 'Palindromic, double and rotational',
            'notationSiril' => '&-5-4.5-5.36.4-7.58.6-9.70.8-8.9-8-T, +T',
        ],
    ];

    public static function canonicalMethodProvider(): array
    {
        return [
            'cambridge' => [self::CANONICAL_METHODS['Cambridge Surprise Minor']],
            'grandsire' => [self::CANONICAL_METHODS['Grandsire Triples']],
            'bristol' => [self::CANONICAL_METHODS['Bristol Surprise Maximus']],
        ];
    }

    // Core behavior and baseline inference tests

    /**
     * Test classification inference from notation
     */
    public function testClassification()
    {
        $tests = array(
            array( 'set' => array( 'title' => 'Cambridge Surprise Minor', 'notation' => '-36-14-12-36-14-56,12'), 'result' => 'Surprise' ),
            array( 'set' => array( 'title' => 'Slapton Slow Bob Doubles', 'notation' => '5.1.5.123.125,125'), 'result' => 'Bob' ),
            array( 'set' => array( 'title' => 'Vincula Surprise Royal', 'notation' => '-50-16-70-18-12-30-14-50-16-70,14'), 'result' => 'Surprise' ),
            array( 'set' => array( 'title' => 'Carfax Delight Minor', 'notation' => '-36-14-12-16-34.1234.56,16'), 'result' => 'Delight' ),
            array( 'set' => array( 'title' => 'St Clement\'s College Bob Triples', 'notation' => '3,1.7.1.7.3.7.3'), 'result' => 'Bob' ),
            array( 'set' => array( 'title' => 'Slink Differential Little Place Maximus', 'notation' => '56.123T,1T'), 'result' => 'Place' ),
            array( 'set' => array( 'title' => 'Kent Treble Bob Maximus', 'notation' => '34-34.1T-12-1T-12-1T-12-1T-12-1T-12-1T,1T'), 'result' => 'Treble Bob' ),
            array( 'set' => array( 'title' => 'Crick Hybrid Major', 'notation' => '-38-14-58-16-12-38.56.14.56-56.1478.56.38-12-16-58-14-38-12'), 'result' => 'Hybrid' ),
            array( 'set' => array( 'title' => 'St Mary Magdalen Alliance Major', 'notation' => '-58-14.58.36.14.38-58,12'), 'result' => 'Alliance' ),
        );
        foreach ($tests as $test) {
            $method = new Method($test['set']);
            $this->assertEquals($test['result'], $method->getClassification(), 'Wrong classification calculated for '.$method->getTitle());
        }
    }

    /**
     * Test stage inference from notation
     */
    public function testStageInference()
    {
        $tests = array(
            array('notation' => '-36-14-12-36-14-56,12', 'expectedStage' => 6), // Major
            array('notation' => '5.1.5.123.125,125', 'expectedStage' => 5), // Doubles
        );

        foreach ($tests as $test) {
            $method = new Method(['notation' => $test['notation']]);
            $this->assertEquals(
                $test['expectedStage'],
                $method->getStage(),
                'Wrong stage inferred for notation: ' . $test['notation']
            );
        }
    }

    /**
     * Test stage text conversion
     */
    public function testStageText()
    {
        $tests = array(
            array('stage' => 3, 'expected' => 'Singles'),
            array('stage' => 4, 'expected' => 'Minimus'),
            array('stage' => 5, 'expected' => 'Doubles'),
            array('stage' => 6, 'expected' => 'Minor'),
            array('stage' => 7, 'expected' => 'Triples'),
            array('stage' => 8, 'expected' => 'Major'),
            array('stage' => 10, 'expected' => 'Royal'),
            array('stage' => 12, 'expected' => 'Maximus'),
        );

        foreach ($tests as $test) {
            $method = new Method(['stage' => $test['stage']]);
            $this->assertEquals($test['expected'], $method->getStageText());
        }
    }

    /**
     * Test setting explicit title
     */
    public function testSetTitle()
    {
        $method = new Method();
        $method->setTitle('Test Method Title');
        $this->assertEquals('Test Method Title', $method->getTitle());
    }

    /**
     * Test abbreviation can be set and retrieved
     */
    public function testSetAbbreviation()
    {
        $method = new Method();
        $method->setAbbreviation('TM');
        $this->assertEquals('TM', $method->getAbbreviation());
    }

    /**
     * Test method properties can be set via setAll
     */
    public function testSetAllMethod()
    {
        $data = [
            'title' => 'Cambridge Surprise Minor',
            'stage' => 6,
            'classification' => 'Surprise',
            'notation' => 'x36x14x12x36x14x56,12',
        ];

        $method = new Method($data);

        $this->assertEquals('Cambridge Surprise Minor', $method->getTitle());
        $this->assertEquals(6, $method->getStage());
        $this->assertEquals('Surprise', $method->getClassification());
        $this->assertEquals('x36x14x12x36x14x56,12', $method->getNotation());
    }

    /**
     * Test string representation
     */
    public function testToString()
    {
        $method = new Method(['title' => 'Cambridge Surprise Minor']);
        $this->assertEquals('Method:Cambridge Surprise Minor', (string)$method);
    }

    /**
     * Test collection relationship initialization
     */
    public function testCollectionsInitialized()
    {
        $method = new Method(['title' => 'Cambridge Surprise Minor']);
        $collections = $method->getCollections();

        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $collections);
        $this->assertCount(0, $collections);
    }

    /**
     * Test performances relationship initialization
     */
    public function testPerformancesInitialized()
    {
        $method = new Method(['title' => 'Cambridge Surprise Minor']);
        $performances = $method->getPerformances();

        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $performances);
        $this->assertCount(0, $performances);
    }

    // Canonical-method inference matrix (runs against all entries in CANONICAL_METHODS)

    #[DataProvider('canonicalMethodProvider')]
    public function testCanonicalInferenceMatchesCapturedValuesForCanonicalMethods(array $expected)
    {
        $method = new Method([
            'title' => $expected['title'],
            'notation' => $expected['notation'],
        ]);

        $this->assertSame($expected['stage'], $method->getStage());
        $this->assertSame($expected['classification'], $method->getClassification());
        $this->assertSame($expected['nameMetaphone'], $method->getNameMetaphone());
        $this->assertSame($expected['notationExpanded'], $method->getNotationExpanded());
        $this->assertSame($expected['leadHeadCode'], $method->getLeadHeadCode());
        $this->assertSame($expected['leadHead'], $method->getLeadHead());
        $this->assertSame($expected['lengthOfLead'], $method->getLengthOfLead());
        $this->assertSame($expected['numberOfLeads'], $method->getNumberOfLeads());
        $this->assertSame($expected['lengthOfCourse'], $method->getLengthOfCourse());
        $this->assertSame($expected['numberOfHunts'], $method->getNumberOfHunts());
        $this->assertSame($expected['plain'], $method->getPlain());
        $this->assertSame($expected['trebleDodging'], $method->getTrebleDodging());
        $this->assertSame($expected['palindromic'], $method->getPalindromic());
        $this->assertSame($expected['doubleSym'], $method->getDoubleSym());
        $this->assertSame($expected['rotational'], $method->getRotational());
        $this->assertEquals($expected['calls'], $method->getCalls());
        $this->assertEquals($expected['ruleOffs'], $method->getRuleOffs());
        $this->assertEquals($expected['callingPositions'], $method->getCallingPositions());
        $this->assertSame($expected['url'], $method->getUrl());
        $this->assertSame($expected['abbreviation'], $method->getAbbreviation());
        $this->assertSame($expected['symmetryText'], $method->getSymmetryText());
    }

    #[DataProvider('canonicalMethodProvider')]
    public function testGeneratedAbbreviationRemovesKnownSuffixesForCanonicalMethods(array $expected)
    {
        $method = new Method([
            'title' => $expected['title'],
            'notation' => $expected['notation'],
        ]);

        $this->assertSame($expected['abbreviation'], $method->getAbbreviation());
    }

    #[DataProvider('canonicalMethodProvider')]
    public function testTitleIsGeneratedWhenUnsetForCanonicalMethods(array $expected)
    {
        $method = new Method([
            'notation' => $expected['notation'],
            'stage' => $expected['stage'],
            'classification' => $expected['classification'],
            'differential' => false,
            'little' => false,
        ]);

        $this->assertSame('Unnamed '.$expected['classification'].' '.$method->getStageText(), $method->getTitle());
    }

    #[DataProvider('canonicalMethodProvider')]
    public function testNotationSirilIsInferredFromNotationForCanonicalMethods(array $expected)
    {
        $method = new Method([
            'title' => $expected['title'],
            'notation' => $expected['notation'],
        ]);

        $this->assertSame($expected['notationSiril'], $method->getNotationSiril());
    }

    #[DataProvider('canonicalMethodProvider')]
    public function testLeadHeadsContainsFullCourseAndEndsAtRoundsForCanonicalMethods(array $expected)
    {
        $method = new Method([
            'title' => $expected['title'],
            'notation' => $expected['notation'],
        ]);

        $leadHeads = $method->getLeadHeads();
        $rounds = PlaceNotation::rounds($expected['stage']);

        $this->assertCount($expected['numberOfLeads'], $leadHeads);
        $this->assertSame($rounds, end($leadHeads));
    }

    #[DataProvider('canonicalMethodProvider')]
    public function testCallsAreEmptyWhenMethodIsDifferentialForCanonicalMethods(array $expected)
    {
        $method = new Method([
            'title' => $expected['title'],
            'notation' => $expected['notation'],
            'differential' => true,
        ]);

        $this->assertSame([], $method->getCalls());
        $this->assertSame([], $method->getCallingPositions());
    }

    #[DataProvider('canonicalMethodProvider')]
    public function testCallingPositionsMatchCanonicalExpectationsForCanonicalMethods(array $expected)
    {
        $method = new Method([
            'title' => $expected['title'],
            'notation' => $expected['notation'],
        ]);

        $this->assertEquals($expected['callingPositions'], $method->getCallingPositions());
    }

    #[DataProvider('canonicalMethodProvider')]
    public function testRuleOffsDefaultToLeadLengthForCanonicalMethods(array $expected)
    {
        $method = new Method([
            'title' => $expected['title'],
            'notation' => $expected['notation'],
        ]);

        $this->assertEquals($expected['ruleOffs'], $method->getRuleOffs());
    }

    #[DataProvider('canonicalMethodProvider')]
    public function testHuntDetailsHaveExpectedStructureForCanonicalMethods(array $expected)
    {
        $method = new Method([
            'title' => $expected['title'],
            'notation' => $expected['notation'],
        ]);

        $details = $method->getHuntDetails();

        $this->assertCount($expected['numberOfHunts'], $details);
        $this->assertGreaterThan(0, count(array_filter($details, fn ($detail) => $detail['principal'])));
        foreach ($details as $detail) {
            $this->assertArrayHasKey('bell', $detail);
            $this->assertArrayHasKey('path', $detail);
            $this->assertArrayHasKey('wellFormedPath', $detail);
            $this->assertArrayHasKey('little', $detail);
            $this->assertArrayHasKey('type', $detail);
            $this->assertArrayHasKey('principal', $detail);
        }
    }

    // Special-case branch coverage

    public function testJumpClassificationOverridesOtherClassification()
    {
        $method = new Method([
            'notation' => '(34)',
            'stage' => 4,
        ]);

        $this->assertTrue($method->getJump());
        $this->assertSame('Jump', $method->getClassification());
    }

    #[DataProvider('canonicalMethodProvider')]
    public function testSymmetryFlagsAndTextMatchCanonicalExpectationsForCanonicalMethods(array $expected)
    {
        $method = new Method([
            'title' => $expected['title'],
            'notation' => $expected['notation'],
        ]);

        $this->assertSame($expected['palindromic'], $method->getPalindromic());
        $this->assertSame($expected['doubleSym'], $method->getDoubleSym());
        $this->assertSame($expected['rotational'], $method->getRotational());
        $this->assertSame($expected['symmetryText'], $method->getSymmetryText());
    }

    #[DataProvider('canonicalMethodProvider')]
    public function testGettersAreStableAcrossRepeatedCallsForCanonicalMethods(array $expected)
    {
        $method = new Method([
            'title' => $expected['title'],
            'notation' => $expected['notation'],
        ]);

        $firstClassification = $method->getClassification();
        $firstCalls = $method->getCalls();
        $firstHuntDetails = $method->getHuntDetails();

        $this->assertSame($firstClassification, $method->getClassification());
        $this->assertSame($firstCalls, $method->getCalls());
        $this->assertSame($firstHuntDetails, $method->getHuntDetails());
    }

    // Relationship filters and serialization behavior

    public function testPerformanceFilteringHelpers()
    {
        $methodForTower = new Method([
            'title' => self::CANONICAL_METHODS['Cambridge Surprise Minor']['title'],
            'notation' => self::CANONICAL_METHODS['Cambridge Surprise Minor']['notation'],
        ]);
        $methodForHand = new Method([
            'title' => self::CANONICAL_METHODS['Bristol Surprise Maximus']['title'],
            'notation' => self::CANONICAL_METHODS['Bristol Surprise Maximus']['notation'],
        ]);

        $renamed = new Performance(['type' => 'renamedMethod']);
        $duplicate = new Performance(['type' => 'duplicateMethod']);
        $firstTowerbell = new Performance(['type' => 'firstTowerbellPeal']);
        $firstHandbell = new Performance(['type' => 'firstHandbellPeal']);

        $methodForTower->addPerformance($firstTowerbell);
        $methodForTower->addPerformance($renamed);
        $methodForTower->addPerformance($duplicate);

        $methodForHand->addPerformance($firstHandbell);

        $this->assertCount(1, $methodForTower->getRenamed());
        $this->assertCount(1, $methodForTower->getDuplicates());
        $this->assertSame($firstTowerbell, $methodForTower->getFirstTowerbellPeal());
        $this->assertSame($firstHandbell, $methodForHand->getFirstHandbellPeal());
    }

    #[DataProvider('canonicalMethodProvider')]
    public function testToArrayIncludesInferredFieldsAndSkipsRelationsForCanonicalMethods(array $expected)
    {
        $method = new Method([
            'title' => $expected['title'],
            'notation' => $expected['notation'],
        ]);

        $array = $method->__toArray();

        $this->assertArrayHasKey('collections', $array);
        $this->assertArrayHasKey('performances', $array);
        $this->assertArrayHasKey('classification', $array);
        $this->assertArrayHasKey('notationExpanded', $array);
        $this->assertArrayHasKey('leadHead', $array);
        $this->assertArrayHasKey('lengthOfLead', $array);
        $this->assertSame($expected['title'], $array['title']);
        $this->assertSame($expected['classification'], $array['classification']);
    }
}

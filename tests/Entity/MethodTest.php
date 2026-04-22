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

    /**
     * Test classification inference from notation across a broad range of method types, stages, and notations, including edge cases and hybrids.
     */
    public function testClassification()
    {
        $tests = array(
            array( 'set' => array( 'title' => 'Innominate Alliance Singles', 'stage' => 3, 'notation' => '3.1.1.3.1'), 'result' => 'Alliance' ),
            array( 'set' => array( 'title' => 'Barton Little Bob Minimus', 'stage' => 4, 'notation' => '-14.34,12'), 'result' => 'Bob' ),
            array( 'set' => array( 'title' => '1215 Delight Minor', 'stage' => 6, 'notation' => '-34-1456-12-16-1234-16,16'), 'result' => 'Delight' ),
            array( 'set' => array( 'title' => 'Because Two', 'stage' => 2, 'notation' => '-,12--12.12-'), 'result' => 'Hybrid' ),
            array( 'set' => array( 'title' => 'Reverse St Remigius Place Singles', 'stage' => 3, 'notation' => '3.1.123,1'), 'result' => 'Place' ),
            array( 'set' => array( 'title' => '12 Victoria Street Surprise Minor', 'stage' => 6, 'notation' => '36-36.14-12-36.14-12.36,12'), 'result' => 'Surprise' ),
            array( 'set' => array( 'title' => '69 Treble Bob Minimus', 'stage' => 4, 'notation' => '34.34.34.14--12.14,14'), 'result' => 'Treble Bob' ),
            array( 'set' => array( 'title' => '20th Birthday Treble Place Singles', 'stage' => 3, 'notation' => '3.123.1.3.3,1.1'), 'result' => 'Treble Place' ),
            array( 'set' => array( 'title' => 'Alderton Alliance Doubles', 'stage' => 5, 'notation' => '345.1.125.123.3.123.5,125'), 'result' => 'Alliance' ),
            array( 'set' => array( 'title' => 'Barbury Alliance Triples', 'stage' => 7, 'notation' => '7.1.5.3.7.5.345.5.7,127'), 'result' => 'Alliance' ),
            array( 'set' => array( 'title' => '70th Birthday Alliance Major', 'stage' => 8, 'notation' => '-38-14-56-16-34-1238-12.78,12'), 'result' => 'Alliance' ),
            array( 'set' => array( 'title' => 'Jan Little Alliance Caters', 'stage' => 9, 'notation' => '1.349.1.5.1.5.1.9.1.3456789.1'), 'result' => 'Alliance' ),
            array( 'set' => array( 'title' => 'Ackhorne Alliance Royal', 'stage' => 10, 'notation' => '34-50.14-1250-16.70.58.16-16.70.16-16.90,12'), 'result' => 'Alliance' ),
            array( 'set' => array( 'title' => 'Broughton-in-Furness Little Alliance Cinques', 'stage' => 11, 'notation' => '5.3.5.1.5.14E.3.5,12E'), 'result' => 'Alliance' ),
            array( 'set' => array( 'title' => '121212 Little Alliance Maximus', 'stage' => 12, 'notation' => '58.149T.58.149T-34,1T'), 'result' => 'Alliance' ),
            array( 'set' => array( 'title' => 'Grandsire Sextuples', 'stage' => 13, 'notation' => '3,1.A.1.A.1.A.1.A.1.A.1.A.1'), 'result' => 'Bob' ),
            array( 'set' => array( 'title' => 'Akenfield Alliance Fourteen', 'stage' => 14, 'notation' => '-5B-14.5B-5B.36.147B.58.169B.70.18.9B.10-10.EB.10-10-10.EB,1B'), 'result' => 'Alliance' ),
            array( 'set' => array( 'title' => 'Grandsire Septuples', 'stage' => 15, 'notation' => '3,1.C.1.C.1.C.1.C.1.C.1.C.1.C.1'), 'result' => 'Bob' ),
            array( 'set' => array( 'title' => 'Callisto Megale Little Alliance Sixteen', 'stage' => 16, 'notation' => '-34.5D.16.7D.18-18-78.16.7D.16.78.16.7D-78-7D.16.78.16.7D.16.78,1D'), 'result' => 'Alliance' ),
            array( 'set' => array( 'title' => 'Little Bob Eighteen', 'stage' => 18, 'notation' => '-1G-14,12'), 'result' => 'Bob' ),
            array( 'set' => array( 'title' => 'Little Bob Twenty', 'stage' => 20, 'notation' => '-1J-14,12'), 'result' => 'Bob' ),
            array( 'set' => array( 'title' => 'Little Bob Twenty-Two', 'stage' => 22, 'notation' => '-1L-14,12'), 'result' => 'Bob' ),
            array( 'set' => array( 'title' => '& Burn Singles', 'stage' => 3, 'notation' => '1.1.1.1.3.123.1.1.1.123.3.123.1.123.1.1.3.1'), 'result' => 'Hybrid' ),
            array( 'set' => array( 'title' => '41st Battalion Singles', 'stage' => 3, 'notation' => '3.1.123.1.3.1.123.1.1.3.1.123.1.123.1.3.1.3'), 'result' => 'Hybrid' ),
            array( 'set' => array( 'title' => 'All the Good Girls Go to Hell Singles', 'stage' => 3, 'notation' => '1.1.1.3.1.1.1.1.3.1.3.1.3.3.123.1.123.3'), 'result' => 'Hybrid' ),
            array( 'set' => array( 'title' => 'St Remigius Place Singles', 'stage' => 3, 'notation' => '3.1.3,123'), 'result' => 'Place' ),
            array( 'set' => array( 'title' => 'Cottesloe Treble Place Singles', 'stage' => 3, 'notation' => '3.3.3.1.123.3,123'), 'result' => 'Treble Place' ),
            array( 'set' => array( 'title' => 'Deltic Treble Place Singles', 'stage' => 3, 'notation' => '3.3.3.123.3.1.123.3.3,123'), 'result' => 'Treble Place' ),
            array( 'set' => array( 'title' => 'Duple Milton Bryan Treble Place Singles', 'stage' => 3, 'notation' => '3.3.3.1.1.1.123.123,1.1'), 'result' => 'Treble Place' ),
            array( 'set' => array( 'title' => 'Bastow Little Bob Minimus', 'stage' => 4, 'notation' => '-12,14'), 'result' => 'Bob' ),
            array( 'set' => array( 'title' => 'Double Bob Minimus', 'stage' => 4, 'notation' => '-14-34,12'), 'result' => 'Bob' ),
            array( 'set' => array( 'title' => 'Dusty Bob Minimus', 'stage' => 4, 'notation' => '-14-14.12.14.34.12'), 'result' => 'Bob' ),
            array( 'set' => array( 'title' => 'Llanarthne Little Bob Minimus', 'stage' => 4, 'notation' => '-14,34'), 'result' => 'Bob' ),
            array( 'set' => array( 'title' => 'Lonk Bob Minimus', 'stage' => 4, 'notation' => '-14-14.12.14-12'), 'result' => 'Bob' ),
            array( 'set' => array( 'title' => 'Plain Bob Minimus', 'stage' => 4, 'notation' => '-14-14,12'), 'result' => 'Bob' ),
            array( 'set' => array( 'title' => 'Canterbury Place Minimus', 'stage' => 4, 'notation' => '-14.12.34,14'), 'result' => 'Place' ),
            array( 'set' => array( 'title' => 'Charollais Place Minimus', 'stage' => 4, 'notation' => '34.14-14.12.14.34.12'), 'result' => 'Place' ),
            array( 'set' => array( 'title' => 'Double Canterbury Place Minimus', 'stage' => 4, 'notation' => '34.14.12.34,12'), 'result' => 'Place' ),
            array( 'set' => array( 'title' => '96 Treble Bob Minimus', 'stage' => 4, 'notation' => '34--14.12.12.12.14,14'), 'result' => 'Treble Bob' ),
            array( 'set' => array( 'title' => 'Absolutely Bolux Quadruple Treble Bob Minimus', 'stage' => 4, 'notation' => '34.34.34.34.34.34.34.34.34.14.12.12.12.12.12.12.12.12.12.14,14'), 'result' => 'Treble Bob' ),
            array( 'set' => array( 'title' => 'Also Bolux Double Treble Bob Minimus', 'stage' => 4, 'notation' => '34.34.34.34.34.14.12.12.12.12.12.14,14'), 'result' => 'Treble Bob' ),
            array( 'set' => array( 'title' => 'Angela Treble Bob Minimus', 'stage' => 4, 'notation' => '--34.14-12.12.14,14'), 'result' => 'Treble Bob' ),
            array( 'set' => array( 'title' => '1815 Delight Minor', 'stage' => 6, 'notation' => '-56-1456-12-16-1234-16,16'), 'result' => 'Delight' ),
            array( 'set' => array( 'title' => '65th Birthday Delight Minor', 'stage' => 6, 'notation' => '-36-1456-56-16-12.34.3456-1234.34.16-56-1456-36-16'), 'result' => 'Delight' ),
            array( 'set' => array( 'title' => 'A Christmas Carol Delight Minor', 'stage' => 6, 'notation' => '-34-14-12-16-34-3456.34-34.16-12-14-34-16'), 'result' => 'Delight' ),
            array( 'set' => array( 'title' => 'A&E Delight Minor', 'stage' => 6, 'notation' => '-56-16-56-36-14-36,16'), 'result' => 'Delight' ),
            array( 'set' => array( 'title' => 'AC/DC Delight Minor', 'stage' => 6, 'notation' => '-36-14-12-16.34-34.36,12'), 'result' => 'Delight' ),
            array( 'set' => array( 'title' => '80th Birthday Surprise Minor', 'stage' => 6, 'notation' => '-56-14-12-36-12-1256,12'), 'result' => 'Surprise' ),
            array( 'set' => array( 'title' => 'A Dangerous Mind Surprise Minor', 'stage' => 6, 'notation' => '-34-1456.56.1256.12.1236-34-56,12'), 'result' => 'Surprise' ),
            array( 'set' => array( 'title' => 'A Random Thought Surprise Minor', 'stage' => 6, 'notation' => '-56-1456.56.1256.12.1236-12-56,12'), 'result' => 'Surprise' ),
        );

        $coveredClassifications = array_values(array_unique(array_map(function ($test) {
            return $test['result'];
        }, $tests)));
        foreach (array('Alliance', 'Bob', 'Delight', 'Hybrid', 'Place', 'Surprise', 'Treble Bob', 'Treble Place') as $requiredClassification) {
            $this->assertContains($requiredClassification, $coveredClassifications, 'Missing coverage for classification: '.$requiredClassification);
        }

        $coveredStages = array_values(array_unique(array_map(function ($test) {
            return $test['set']['stage'];
        }, $tests)));
        foreach (array(2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 18, 20, 22) as $requiredStage) {
            $this->assertContains($requiredStage, $coveredStages, 'Missing coverage for stage: '.$requiredStage);
        }

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
            array('notation' => '-36-14-12-36-14-56,12', 'expectedStage' => 6), // Minor
            array('notation' => '5.1.5.123.125,125', 'expectedStage' => 5), // Doubles
            array('notation' => '34-50.14-1250-16.70.58.16-16.70.16-16.90,12', 'expectedStage' => 10), // Royal
            array('notation' => 'x5Tx14.5Tx5T.36.14x7T.58.16x9T.70.18x18.9Tx18x1T,1T', 'expectedStage' => 12), // Maximus
            array('notation' => '-5B-14.5B-5B.36.147B.58.169B.70.18.9B.10-10.EB.10-10-10.EB,1B', 'expectedStage' => 14), // Fourteen
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
        $this->assertSame($expected['notationSiril'], $method->getNotationSiril());

        $leadHeads = $method->getLeadHeads();
        $this->assertCount($expected['numberOfLeads'], $leadHeads);
        $this->assertSame(PlaceNotation::rounds($expected['stage']), end($leadHeads));
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

    public function testUnnamedTitleUsesClassDescriptorOrdering()
    {
        $method = new Method([
            'stage' => 6,
            'jump' => true,
            'differential' => true,
            'little' => true,
            'classification' => 'Surprise',
        ]);

        $this->assertSame('Unnamed Jump Differential Little Surprise Minor', $method->getTitle());
    }

    public static function classDescriptorProvider(): array
    {
        return [
            'rule-d classification only' => [
                [
                    'classification' => 'Surprise',
                    'stage' => 6,
                ],
                'Surprise',
            ],
            'full descriptor ordering' => [
                [
                    'stage' => 6,
                    'jump' => true,
                    'differential' => true,
                    'little' => true,
                    'classification' => 'Surprise',
                ],
                'Jump Differential Little Surprise',
            ],
            'little excluded without rule-d classification' => [
                [
                    'stage' => 6,
                    'jump' => true,
                    'differential' => true,
                    'little' => true,
                    'classification' => 'Jump',
                ],
                'Jump Differential',
            ],
            'blank when only non-rule-d class applies' => [
                [
                    'stage' => 8,
                    'little' => true,
                    'classification' => 'Hybrid',
                ],
                '',
            ],
        ];
    }

    #[DataProvider('classDescriptorProvider')]
    public function testClassDescriptorExamples(array $set, string $expected): void
    {
        $method = new Method($set);

        $this->assertSame($expected, $method->getClassDescriptor());
    }

    public function testUnnamedTitleExcludesLittleWithoutRuleDClassification()
    {
        $method = new Method([
            'stage' => 6,
            'jump' => true,
            'classification' => 'Jump',
            'differential' => true,
            'little' => true,
        ]);

        $this->assertSame('Unnamed Jump Differential Minor', $method->getTitle());
    }

    public function testUnnamedTitleDoesNotContainDoubleSpacesWhenClassDescriptorIsBlank(): void
    {
        $method = new Method([
            'stage' => 6,
            'classification' => 'Hybrid',
            'little' => true,
        ]);

        $this->assertSame('', $method->getClassDescriptor());
        $this->assertSame('Unnamed Minor', $method->getTitle());
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

        $this->assertArrayNotHasKey('collections', $array);
        $this->assertArrayNotHasKey('performances', $array);
        $this->assertArrayNotHasKey('methodSimilarity1', $array);
        $this->assertArrayNotHasKey('methodSimilarity2', $array);
        $this->assertArrayHasKey('classification', $array);
        $this->assertArrayHasKey('notationExpanded', $array);
        $this->assertArrayHasKey('leadHead', $array);
        $this->assertArrayHasKey('lengthOfLead', $array);
        $this->assertSame($expected['title'], $array['title']);
        $this->assertSame($expected['classification'], $array['classification']);
    }
}

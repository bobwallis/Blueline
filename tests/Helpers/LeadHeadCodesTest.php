<?php
namespace Blueline\Tests\Helpers;

use Blueline\Helpers\LeadHeadCodes;
use Blueline\Helpers\PlaceNotation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LeadHeadCodesTest extends TestCase
{
    #[DataProvider('xmlLeadHeadCodeProvider')]
    public function testToCodeMatchesXmlExamples(string $title, int $stage, string $notation, string $expectedLeadHeadCode, string $expectedLeadHead): void
    {
        $notationExpanded = PlaceNotation::expand($notation, $stage);
        $notationExploded = PlaceNotation::explode($notationExpanded);
        $lead = PlaceNotation::apply(
            PlaceNotation::explodedToPermutations($stage, $notationExploded),
            PlaceNotation::rounds($stage)
        );
        $leadHead = implode('', end($lead));
        $leadEndNotation = $notationExploded[count($notationExploded) - 1];
        $postLeadEndNotation = $notationExploded[0];

        $this->assertSame(
            $expectedLeadHeadCode,
            LeadHeadCodes::toCode($leadHead, $stage, $leadEndNotation, $postLeadEndNotation),
            'Wrong lead head code calculated for '.$title
        );

        $this->assertSame($expectedLeadHead, $leadHead, 'Wrong lead head derived from notation for '.$title);
    }

    #[DataProvider('xmlLeadHeadCodeProvider')]
    public function testFromCodeMatchesXmlExamples(string $title, int $stage, string $notation, string $expectedLeadHeadCode, string $expectedLeadHead): void
    {
        $this->assertSame(
            $expectedLeadHead,
            LeadHeadCodes::fromCode($expectedLeadHeadCode, $stage),
            'Wrong lead head returned from code for '.$title
        );
    }

    public static function xmlLeadHeadCodeProvider(): array
    {
        return [
            'CCCBR_Alliance.xml: Innominate Alliance Singles' => ['Innominate Alliance Singles', 3, '3.1.1.3.1', 'r', '132'],
            'CCCBR_Plain.xml: St Remigius Place Singles' => ['St Remigius Place Singles', 3, '3.1.3,123', 'p', '132'],
            'CCCBR_Plain.xml: Reverse St Remigius Place Singles' => ['Reverse St Remigius Place Singles', 3, '3.1.123,1', 'r', '132'],
            'CCCBR_Alliance.xml: Ermin Little Alliance Minimus' => ['Ermin Little Alliance Minimus', 4, '-34.14', 'm', '1423'],
            'CCCBR_Alliance.xml: Parsley Alliance Minimus' => ['Parsley Alliance Minimus', 4, '-14-12.14-12', 'f', '1423'],
            'CCCBR_Plain.xml: Bastow Little Bob Minimus' => ['Bastow Little Bob Minimus', 4, '-12,14', 'm', '1423'],
            'CCCBR_Plain.xml: Plain Bob Minimus' => ['Plain Bob Minimus', 4, '-14-14,12', 'a', '1342'],
            'CCCBR_Plain.xml: Grandsire Minimus' => ['Grandsire Minimus', 4, '34,14-14-', 'q', '1243'],
            'CCCBR_Plain.xml: Reverse Grandsire Minimus' => ['Reverse Grandsire Minimus', 4, '-,14-14.12', 's', '1243'],
            'CCCBR_Alliance.xml: Platinum Jubilee Alliance Doubles' => ['Platinum Jubilee Alliance Doubles', 5, '5.1.5.1.345.5,125', 'p', '13524'],
            'CCCBR_Alliance.xml: Sunday Morning Alliance Doubles' => ['Sunday Morning Alliance Doubles', 5, '5.1.5.1.5.145,1', 's', '14253'],
            'CCCBR_Plain.xml: Bistow Little Bob Doubles' => ['Bistow Little Bob Doubles', 5, '5.125,1', 's', '14253'],
            'CCCBR_Plain.xml: Stanwell Little Bob Doubles' => ['Stanwell Little Bob Doubles', 5, '3.1.3,125', 'p', '13524'],
            'CCCBR_Plain.xml: Baldrick Little Bob Doubles' => ['Baldrick Little Bob Doubles', 5, '5.1.5.145,125', 'p1', '15432'],
            'CCCBR_Plain.xml: Union Bob Doubles' => ['Union Bob Doubles', 5, '3.1.5.1.345,1', 's', '14253'],
            'CCCBR_Plain.xml: Grandsire Doubles' => ['Grandsire Doubles', 5, '3,1.5.1.5.1', 'a', '12534'],
            'CCCBR_Plain.xml: Reverse Grandsire Doubles' => ['Reverse Grandsire Doubles', 5, '5,1.5.1.5.3', 'g', '12534'],
            'CCCBR_Alliance.xml: Little Dunham Little Alliance Minor' => ['Little Dunham Little Alliance Minor', 6, '-34-16.34,12', 'a', '135264'],
            'CCCBR_Plain.xml: Grandsire Minor' => ['Grandsire Minor', 6, '36,16-16-16-', 'p', '125364'],
            'CCCBR_Surprise.xml: Fryerning Surprise Minor' => ['Fryerning Surprise Minor', 6, '-34-14-12-1236-12-36,12', 'a', '135264'],
            'CCCBR_Surprise.xml: Sedlescombe Surprise Minor' => ['Sedlescombe Surprise Minor', 6, '-34-14-12-1236-14-56,12', 'f', '142635'],
            'CCCBR_Surprise.xml: Westminster Surprise Minor' => ['Westminster Surprise Minor', 6, '-34-14-12-36-12-36,12', 'a', '135264'],
            'CCCBR_Surprise.xml: Annable\'s London Surprise Minor' => ['Annable\'s London Surprise Minor', 6, '-34-14-12-36-14-36,16', 'h', '156342'],
            'CCCBR_Surprise.xml: Lightfoot Surprise Minor' => ['Lightfoot Surprise Minor', 6, '-34-14-12-36.14-14.36,12', 'b', '156342'],
            'CCCBR_Surprise.xml: Ashton-under-Hill Surprise Minor' => ['Ashton-under-Hill Surprise Minor', 6, '-34-14-56-1236-12-16,12', 'b', '156342'],
            'CCCBR_Delight.xml: Old Oxford Delight Minor' => ['Old Oxford Delight Minor', 6, '-34-14-12-16-12-56,12', 'f', '142635'],
            'CCCBR_Delight.xml: Bedford Delight Minor' => ['Bedford Delight Minor', 6, '-34-14-12-16-14-36,12', 'b', '156342'],
            'CCCBR_Delight.xml: Yeah, But, No, But, Yeah, But, Delight Minor' => ['Yeah, But, No, But, Yeah, But, Delight Minor', 6, '-34-14-12-16.12-12.16,16', 'h', '156342'],
            'CCCBR_Delight.xml: Snow White Delight Minor' => ['Snow White Delight Minor', 6, '-34-14-12-16.12-34.16,16', 'm', '142635'],
            'CCCBR_Delight.xml: Taxal Delight Minor' => ['Taxal Delight Minor', 6, '-34-14-12-16.34-14.36,12', 'e', '164523'],
            'CCCBR_Delight.xml: Seven Twenty Delight Minor' => ['Seven Twenty Delight Minor', 6, '-34-14-56-16-14-56,12', 'a', '135264'],
            'CCCBR_Alliance.xml: Blackadder Alliance Triples' => ['Blackadder Alliance Triples', 7, '7.1.7.1.7.347.7.1.7.347,127', 'q1', '1647253'],
            'CCCBR_Plain.xml: Grandsire Triples' => ['Grandsire Triples', 7, '3,1.7.1.7.1.7.1', 'a', '1253746'],
            'CCCBR_Plain.xml: Reverse Grandsire Triples' => ['Reverse Grandsire Triples', 7, '7,1.7.1.7.1.7.5', 'g', '1253746'],
            'CCCBR_Plain.xml: Bistow Little Bob Caters' => ['Bistow Little Bob Caters', 9, '9.129,1', 's', '142638597'],
            'CCCBR_Plain.xml: Grandsire Caters' => ['Grandsire Caters', 9, '3,1.9.1.9.1.9.1.9.1', 'a', '125374968'],
            'CCCBR_Plain.xml: Grandsire Royal' => ['Grandsire Royal', 10, '30,10-10-10-10-10-', 'p', '1253749608'],
            'CCCBR_Alliance.xml: Leda Little Alliance Maximus' => ['Leda Little Alliance Maximus', 12, '-3T.14-12,1T', 'k', '18604T2E3957'],
            'CCCBR_Plain.xml: Plain Bob Sextuples' => ['Plain Bob Sextuples', 13, 'A.1.A.1.A.1.A.1.A.1.A.1.A,12A', 'p', '13527496E8A0T'],
            'CCCBR_Plain.xml: Grandsire Sextuples' => ['Grandsire Sextuples', 13, '3,1.A.1.A.1.A.1.A.1.A.1.A.1', 'a', '12537496E8A0T'],
            'CCCBR_Surprise.xml: Horsleydown Surprise Fourteen' => ['Horsleydown Surprise Fourteen', 14, '-5B-14.5B-5B.36.14-7B.58.16-9B.70.18-EB.9T.10-10.EB-10-1B,12', 'c3', '1ABET907856342'],
            'CCCBR_Surprise.xml: Leonis Surprise Fourteen' => ['Leonis Surprise Fourteen', 14, '-5B-14.5B-12.3B.14-12.5B.16-16.7B.58-18.9B-18-9B-78-9B,1B', 'k3', '1BTA0E89674523'],
            'CCCBR_Plain.xml: Plain Bob Septuples' => ['Plain Bob Septuples', 15, 'C.1.C.1.C.1.C.1.C.1.C.1.C.1.C,12C', 'p', '13527496E8A0CTB'],
            'CCCBR_Plain.xml: Grandsire Septuples' => ['Grandsire Septuples', 15, '3,1.C.1.C.1.C.1.C.1.C.1.C.1.C.1', 'a', '12537496E8A0CTB'],
            'CCCBR_Surprise.xml: Bristol Surprise Sixteen' => ['Bristol Surprise Sixteen', 16, '-5D-14.5D-5D.36.14-7D.58.16-9D.70.18-ED.9T.10-AD.EB.1T-1T.AD-1T-1D,1D', 'j4', '1CDABET907856342'],
            'CCCBR_Plain.xml: Little Bob Eighteen' => ['Little Bob Eighteen', 18, '-1G-14,12', 'e', '1648203T5B7D9GEFAC'],
            'CCCBR_Surprise.xml: York Surprise Eighteen' => ['York Surprise Eighteen', 18, '-3G-14-12-3G.14-14.3G.14-14.3G.14-14.3G.14-14.3G.14-14.3G.14-14.3G.14-14.3G,12', 'a', '13527496E8A0CTFBGD'],
            'CCCBR_Plain.xml: Little Bob Twenty' => ['Little Bob Twenty', 20, '-1J-14,12', 'e', '1648203T5B7D9GEJAHCF'],
            'CCCBR_Surprise.xml: York Surprise Twenty' => ['York Surprise Twenty', 20, '-3J-14-12-3J.14-14.3J.14-14.3J.14-14.3J.14-14.3J.14-14.3J.14-14.3J.14-14.3J.14-14.3J,12', 'a', '13527496E8A0CTFBHDJG'],
            'CCCBR_Plain.xml: Little Bob Twenty-Two' => ['Little Bob Twenty-Two', 22, '-1L-14,12', 'e', '1648203T5B7D9GEJALCKFH'],
        ];
    }
}

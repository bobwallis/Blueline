<?php
namespace Blueline\MethodsBundle\Tests\Entity;

use Blueline\MethodsBundle\Entity\Method;

class MethodTest extends \PHPUnit\Framework\TestCase
{
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
}
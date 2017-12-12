<?php
namespace Blueline\ServicesBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NotationControllerTest extends WebTestCase
{
    public function testNotation()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/services/notation.txt?notation=x1x1x1x1x1x2&stage=6');
        $this->assertTrue($client->getResponse()->isSuccessful(), '/services/notation.txt request unsuccessful');
        $crawler = $client->request('GET', '/services/notation.json?notation=x1x1x1x1x1x2&stage=6');
        $this->assertTrue($client->getResponse()->isSuccessful(), '/services/notation.json request unsuccessful');

        $expansionTests = array(
            array('test' => 'notation=x16x16x16x16x16x12&stage=6',       'result' => "x16x16x16x16x16x12\n&-6-6-6, +2"),
            array('test' => 'notation=x16x16x16x16x16x12',               'result' => "x16x16x16x16x16x12\n&-6-6-6, +2"),
            array('test' => 'notation=x1x1x1x1x1x2&stage=6',             'result' => "x16x16x16x16x16x12\n&-6-6-6, +2"),
            array('test' => 'notation=x1x1x1-2&stage=6',                 'result' => "x16x16x16x16x16x12\n&-6-6-6, +2"),
            array('test' => 'notation=-1-1-1LH2&stage=6',                'result' => "x16x16x16x16x16x12\n&-6-6-6, +2"),
            array('test' => 'notation=-1-1-1 le2&stage=6',               'result' => "x16x16x16x16x16x12\n&-6-6-6, +2"),
            array('test' => 'notation=%26-1-1-1 le2&stage=6',            'result' => "x16x16x16x16x16x12\n&-6-6-6, +2"),
            array('test' => 'notation=-1-1-1,2&stage=6',                 'result' => "x16x16x16x16x16x12\n&-6-6-6, +2"),
            array('test' => 'notation=%26x1x1x1+2&stage=6',              'result' => "x16x16x16x16x16x12\n&-6-6-6, +2"),
            array('test' => 'notation=a %26x1x1x1&stage=6',              'result' => "x16x16x16x16x16x12\n&-6-6-6, +2"),
            array('test' => 'notation=%26x1x1x1 2&stage=6',              'result' => "x16x16x16x16x16x12\n&-6-6-6, +2"),
            array('test' => 'notation=a %26-1-1-1-1-1-1&stage=12',       'result' => "x1Tx1Tx1Tx1Tx1Tx1Tx1Tx1Tx1Tx1Tx1Tx12\n&-T-T-T-T-T-T, +2"),
            array('test' => 'notation=-1L-14,12',                        'result' => "x1Lx14x1Lx12\n&-L-4, +2"),
            array('test' => 'notation=3,1.E.1.E.3.E.3.E.3.E.3&stage=11', 'result' => "3.1.E.1.E.3.E.3.E.3.E.3.E.3.E.3.E.3.E.1.E.1\n+3, &1.E.1.E.3.E.3.E.3.E.3"),
            array('test' => 'notation=3,1.5.1.5.1&stage=5',              'result' => "3.1.5.1.5.1.5.1.5.1\n+3, &1.5.1.5.1"),
            array('test' => 'notation=%2B3,%261.5.1.5.1&stage=5',        'result' => "3.1.5.1.5.1.5.1.5.1\n+3, &1.5.1.5.1"),
        );
        foreach ($expansionTests as $test) {
            $crawler = $client->request('GET', '/services/notation.txt?'.$test['test']);
            $this->assertEquals($test['result'], $client->getResponse()->getContent(), '"'.$test['test'].'" expansion unexpected');
        }
    }
}

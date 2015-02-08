<?php
namespace Blueline\ServicesBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NotationControllerTest extends WebTestCase
{
    public function testNotation()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/services/expandNotation.txt?notation=x1x1x1x1x1x2&stage=6');
        $this->assertTrue($client->getResponse()->isSuccessful(), '/services/expandNotation.txt request unsuccessful');
        $crawler = $client->request('GET', '/services/expandNotation.json?notation=x1x1x1x1x1x2&stage=6');
        $this->assertTrue($client->getResponse()->isSuccessful(), '/services/expandNotation.json request unsuccessful');

        $expansionTests = array(
            array('test' => 'notation=x16x16x16x16x16x12&stage=6', 'result' => 'x16x16x16x16x16x12'),
            array('test' => 'notation=x16x16x16x16x16x12',         'result' => 'x16x16x16x16x16x12'),
            array('test' => 'notation=x1x1x1x1x1x2&stage=6',       'result' => 'x16x16x16x16x16x12'),
            array('test' => 'notation=x1x1x1-2&stage=6',           'result' => 'x16x16x16x16x16x12'),
            array('test' => 'notation=-1-1-1LH2&stage=6',          'result' => 'x16x16x16x16x16x12'),
            array('test' => 'notation=-1-1-1 le2&stage=6',         'result' => 'x16x16x16x16x16x12'),
            array('test' => 'notation=%26-1-1-1 le2&stage=6',      'result' => 'x16x16x16x16x16x12'),
            array('test' => 'notation=-1-1-1,2&stage=6',           'result' => 'x16x16x16x16x16x12'),
            array('test' => 'notation=%26x1x1x1+2&stage=6',        'result' => 'x16x16x16x16x16x12'),
            array('test' => 'notation=a %26x1x1x1&stage=6',        'result' => 'x16x16x16x16x16x12'),
            array('test' => 'notation=%26x1x1x1 2&stage=6',        'result' => 'x16x16x16x16x16x12'),
        );
        foreach ($expansionTests as $test) {
            $crawler = $client->request('GET', '/services/expandNotation.txt?'.$test['test']);
            $this->assertEquals($test['result'], $client->getResponse()->getContent(), '"'.$test['test'].'" expansion unexpected');
        }
    }
}

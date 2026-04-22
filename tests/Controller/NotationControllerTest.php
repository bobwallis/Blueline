<?php

namespace Blueline\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NotationControllerTest extends WebTestCase
{
    public function testNotationRequiresNotationParameter()
    {
        $client = static::createClient();
        $client->request('GET', '/services/notation.txt');

        $this->assertSame(400, $client->getResponse()->getStatusCode());
    }

    public function testNotationJsonReturnsStructuredResponse()
    {
        $client = static::createClient();
        $client->request('GET', '/services/notation.json?notation=x1x1x1x1x1x2&stage=6');

        $this->assertTrue($client->getResponse()->isSuccessful(), '/services/notation.json request unsuccessful');

        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(6, $payload['stage']);
        $this->assertSame('x16x16x16x16x16x12', $payload['expanded']);
        $this->assertSame('&-6-6-6, +2', $payload['siril']);
    }

    public function testNotationGuessesStageWhenMissing()
    {
        $client = static::createClient();
        $client->request('GET', '/services/notation.json?notation=3,1.5.1.5.1');

        $this->assertTrue($client->getResponse()->isSuccessful(), 'notation stage guess request unsuccessful');

        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(5, $payload['stage']);
        $this->assertSame('3.1.5.1.5.1.5.1.5.1', $payload['expanded']);
    }

    public function testNotation()
    {
        $client = static::createClient();
        $client->request('GET', '/services/notation.txt?notation=x1x1x1x1x1x2&stage=6');
        $this->assertTrue($client->getResponse()->isSuccessful(), '/services/notation.txt request unsuccessful');
        $client->request('GET', '/services/notation.json?notation=x1x1x1x1x1x2&stage=6');
        $this->assertTrue($client->getResponse()->isSuccessful(), '/services/notation.json request unsuccessful');

        $expansionTests = [
            ['test' => 'notation=x16x16x16x16x16x12&stage=6',       'result' => "x16x16x16x16x16x12\n&-6-6-6, +2"],
            ['test' => 'notation=x16x16x16x16x16x12',               'result' => "x16x16x16x16x16x12\n&-6-6-6, +2"],
            ['test' => 'notation=x1x1x1x1x1x2&stage=6',             'result' => "x16x16x16x16x16x12\n&-6-6-6, +2"],
            ['test' => 'notation=x1x1x1-2&stage=6',                 'result' => "x16x16x16x16x16x12\n&-6-6-6, +2"],
            ['test' => 'notation=-1-1-1LH2&stage=6',                'result' => "x16x16x16x16x16x12\n&-6-6-6, +2"],
            ['test' => 'notation=-1-1-1 le2&stage=6',               'result' => "x16x16x16x16x16x12\n&-6-6-6, +2"],
            ['test' => 'notation=%26-1-1-1 le2&stage=6',            'result' => "x16x16x16x16x16x12\n&-6-6-6, +2"],
            ['test' => 'notation=-1-1-1,2&stage=6',                 'result' => "x16x16x16x16x16x12\n&-6-6-6, +2"],
            ['test' => 'notation=%26x1x1x1+2&stage=6',              'result' => "x16x16x16x16x16x12\n&-6-6-6, +2"],
            ['test' => 'notation=a %26x1x1x1&stage=6',              'result' => "x16x16x16x16x16x12\n&-6-6-6, +2"],
            ['test' => 'notation=%26x1x1x1 2&stage=6',              'result' => "x16x16x16x16x16x12\n&-6-6-6, +2"],
            ['test' => 'notation=a %26-1-1-1-1-1-1&stage=12',       'result' => "x1Tx1Tx1Tx1Tx1Tx1Tx1Tx1Tx1Tx1Tx1Tx12\n&-T-T-T-T-T-T, +2"],
            ['test' => 'notation=-1L-14,12',                        'result' => "x1Lx14x1Lx12\n&-L-4, +2"],
            ['test' => 'notation=3,1.E.1.E.3.E.3.E.3.E.3&stage=11', 'result' => "3.1.E.1.E.3.E.3.E.3.E.3.E.3.E.3.E.3.E.1.E.1\n+3, &1.E.1.E.3.E.3.E.3.E.3"],
            ['test' => 'notation=3,1.5.1.5.1&stage=5',              'result' => "3.1.5.1.5.1.5.1.5.1\n+3, &1.5.1.5.1"],
            ['test' => 'notation=%2B3,%261.5.1.5.1&stage=5',        'result' => "3.1.5.1.5.1.5.1.5.1\n+3, &1.5.1.5.1"],
        ];
        foreach ($expansionTests as $test) {
            $client->request('GET', '/services/notation.txt?'.$test['test']);
            $this->assertEquals($test['result'], $client->getResponse()->getContent(), '"'.$test['test'].'" expansion unexpected');
        }
    }
}

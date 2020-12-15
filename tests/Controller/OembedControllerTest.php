<?php
namespace Blueline\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OembedControllerTest extends WebTestCase
{
    public function testOembed()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/services/oembed.xml?url=https://rsw.me.uk/blueline/methods/view/Cambridge_Surprise_Minor');
        $this->assertFalse($client->getResponse()->isSuccessful(), '/services/oembed.xml request successful');
        $this->assertTrue($client->getResponse()->getStatusCode() == 501, '/services/oembed.xml request not 501');
        $crawler = $client->request('GET', '/services/oembed.json?url=https://rsw.me.uk/blueline/methods/view/Cambridge_Surprise_Minor');
        $this->assertTrue($client->getResponse()->isSuccessful(), '/services/oembed.json request failed');
    }
}

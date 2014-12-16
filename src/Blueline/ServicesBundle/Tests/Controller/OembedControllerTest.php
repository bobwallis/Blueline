<?php
namespace Blueline\ServicesBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OembedControllerTest extends WebTestCase
{
    public function testOembed()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/services/oembed.xml?url=http://rsw.me.uk/blueline/methods/view/Cambridge_Surprise_Minor');
        $this->assertFalse($client->getResponse()->isSuccessful(), '/services/oembed.xml request successful');
        $this->assertTrue($client->getResponse()->getStatusCode() == 501, '/services/oembed.xml request not 501');
    }
}

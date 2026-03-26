<?php
namespace Blueline\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OembedControllerTest extends WebTestCase
{
    public function testOembedXmlIsNotImplemented()
    {
        $client = static::createClient();
        $client->request('GET', '/services/oembed.xml?url=https://rsw.me.uk/blueline/methods/view/Cambridge_Surprise_Minor');
        $this->assertFalse($client->getResponse()->isSuccessful(), '/services/oembed.xml request successful');
        $this->assertTrue($client->getResponse()->getStatusCode() == 501, '/services/oembed.xml request not 501');
    }

    public function testOembedJsonReturnsExpectedMetadata()
    {
        $client = static::createClient();
        $client->request('GET', '/services/oembed.json?url=https://rsw.me.uk/blueline/methods/view/Cambridge_Surprise_Minor');
        $this->assertTrue($client->getResponse()->isSuccessful(), '/services/oembed.json request failed');

        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('photo', $payload['type']);
        $this->assertSame('1.0', $payload['version']);
        $this->assertSame('Blueline', $payload['provider_name']);
        $this->assertSame('Cambridge Surprise Minor', $payload['title']);
        $this->assertStringContainsString('/methods/view/Cambridge_Surprise_Minor.png?scale=1&style=numbers', $payload['url']);
        $this->assertGreaterThan(0, $payload['width']);
        $this->assertGreaterThan(0, $payload['height']);
    }

    public function testOembedRejectsInvalidUrl()
    {
        $client = static::createClient();
        $client->request('GET', '/services/oembed.json?url=not-a-valid-url');

        $this->assertSame(500, $client->getResponse()->getStatusCode());
    }
}

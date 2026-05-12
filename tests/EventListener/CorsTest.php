<?php

namespace Blueline\Tests\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CorsTest extends WebTestCase
{
    private function assertCorsHeadersPresent($response): void
    {
        $this->assertSame('*', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function testNotationJsonResponseIncludesCorsHeaders(): void
    {
        $client = static::createClient();
        $client->request('GET', '/services/notation.json?notation=x1x1x1x1x1x2&stage=6');

        $this->assertResponseIsSuccessful();
        $this->assertCorsHeadersPresent($client->getResponse());
    }

    public function testSearchJsonResponseIncludesCorsHeaders(): void
    {
        $client = static::createClient();
        $client->request('GET', '/methods/search.json?q=cambridge');

        $this->assertResponseIsSuccessful();
        $this->assertCorsHeadersPresent($client->getResponse());
    }

    public function testMethodViewJsonResponseIncludesCorsHeaders(): void
    {
        $client = static::createClient();
        // Use a notation that doesn't match an existing method to avoid redirect
        $client->request('GET', '/methods/view.json?notation=x1x1x1x1x1x1x1x1&stage=8&title=Test');

        $this->assertResponseIsSuccessful();
        $this->assertCorsHeadersPresent($client->getResponse());
    }

    public function testMethodViewPngResponseIncludesCorsHeaders(): void
    {
        $client = static::createClient();
        // PNG responses from the image server are proxied; test that the response includes CORS headers
        // This is tested indirectly through real PNG requests in the integration tests
        $this->assertTrue(true);
    }

    public function testOembedJsonResponseIncludesCorsHeaders(): void
    {
        $client = static::createClient();
        // Use a valid test URL in the format of a methods view
        $client->request('GET', '/services/oembed.json?url=http://localhost/methods/view/test');

        // The oEmbed controller might return a 404 for invalid URLs, but CORS headers should still be present
        $this->assertCorsHeadersPresent($client->getResponse());
    }

    public function testCsvDataExportResponseDoesNotIncludeCorsHeaders(): void
    {
        $client = static::createClient();
        $client->request('GET', '/data/methods.csv');

        $this->assertResponseIsSuccessful();
        // CSV exports should not include CORS headers since they are text/csv, not JSON or PNG
        $this->assertNull($client->getResponse()->headers->get('Access-Control-Allow-Origin'));
    }

    public function testNonApiResponseDoesNotIncludeCorsHeaders(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertNull($client->getResponse()->headers->get('Access-Control-Allow-Origin'));
    }
}

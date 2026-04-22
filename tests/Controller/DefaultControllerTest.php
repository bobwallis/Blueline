<?php

namespace Blueline\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testPagesRenderExpectedContent()
    {
        $client = static::createClient();
        $pages = array(
            '/' => 'Blueline',
            '/about' => 'methods.notationExpanded',
            '/methods/notation' => 'Place Notation Guide',
        );

        foreach ($pages as $path => $expectedContent) {
            $client->request('GET', $path);
            $this->assertTrue($client->getResponse()->isSuccessful(), $path.' request unsuccessful');
            $this->assertStringContainsString($expectedContent, $client->getResponse()->getContent(), $path.' response missing expected content');
            $this->assertStringContainsString('public', (string) $client->getResponse()->headers->get('Cache-Control'), $path.' missing cache control header');
        }
    }

    public function testRedirects()
    {
        $client = static::createClient();
        foreach (array('services/siril', 'services/siril/about', 'copyright', 'tutorials', 'methods/tutorials') as $html) {
            $client->request('GET', '/'.$html);
            $this->assertSame(301, $client->getResponse()->getStatusCode());
        }
    }

    public function testTextResources()
    {
        $client = static::createClient();
        $client->request('GET', '/robots.txt');
        $this->assertTrue($client->getResponse()->isSuccessful(), '/robots.txt request unsuccessful');
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'text/plain; charset=UTF-8'), '/robots.txt Content-Type header wrong');
        $this->assertStringContainsString('Sitemap:', $client->getResponse()->getContent());

        $client->request('GET', '/humans.txt');
        $this->assertTrue($client->getResponse()->isSuccessful(), '/humans.txt request unsuccessful');
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'text/plain; charset=UTF-8'), '/humans.txt Content-Type header wrong');
    }

    public function testXmlAndJsonResources()
    {
        $client = static::createClient();

        $client->request('GET', '/sitemap.xml');
        $this->assertTrue($client->getResponse()->isSuccessful(), '/sitemap.xml request unsuccessful');
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'text/xml; charset=UTF-8'), '/sitemap.xml Content-Type header wrong');
        $this->assertStringContainsString('<sitemapindex', $client->getResponse()->getContent());

        $client->request('GET', '/sitemap_root.xml');
        $this->assertTrue($client->getResponse()->isSuccessful(), '/sitemap_root.xml request unsuccessful');
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'text/xml; charset=UTF-8'), '/sitemap_root.xml Content-Type header wrong');
        $this->assertStringContainsString('<urlset', $client->getResponse()->getContent());

        $client->request('GET', '/manifest.json');
        $this->assertTrue($client->getResponse()->isSuccessful(), '/manifest.json request unsuccessful');
        $manifest = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Blueline', $manifest['name']);
        $this->assertSame('Blueline', $manifest['short_name']);
    }
}

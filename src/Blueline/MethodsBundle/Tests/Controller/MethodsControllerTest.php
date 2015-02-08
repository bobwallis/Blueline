<?php
namespace Blueline\MethodsBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class MethodsControllerTest extends WebTestCase
{
    public function testMethods()
    {
        // Test welcome page and some other basic requests
        foreach (array('/methods/', '/methods/view/Cambridge_Surprise_Minor') as $page) {
            $client = static::createClient();
            $crawler = $client->request('GET', $page);
            $this->assertTrue($client->getResponse()->isSuccessful(), $page.' request unsuccessful');
        }
        foreach (array('/methods/view/Cambridge_Surprise_Minor.json') as $page) {
            $client = static::createClient();
            $crawler = $client->request('GET', $page);
            $this->assertTrue($client->getResponse()->isSuccessful(), $page.' request unsuccessful');
            $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), $page.' Content-Type header wrong');
        }
        foreach (array('/methods/view/Cambridge_Surprise_Minor.png?scale=1&style=numbers', '/methods/view/Cambridge_Surprise_Minor.png?scale=1&style=line', '/methods/view/Cambridge_Surprise_Minor.png?scale=2&style=grid') as $page) {
            $client = static::createClient();
            $crawler = $client->request('GET', $page);
            $this->assertTrue($client->getResponse()->isSuccessful(), $page.' request unsuccessful');
            $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'image/png'), $page.' Content-Type header wrong');
        }
    }
    public function testMethodsSearch()
    {
        // HTML searches
        foreach (array('/methods/search?q=oxford') as $page) {
            $client = static::createClient();
            $crawler = $client->request('GET', $page);
            $this->assertTrue($client->getResponse()->isSuccessful(), $page.' request unsuccessful');
        }
        // JSON searches
        foreach (array('/methods/search.json?q=oxford') as $page) {
            $client = static::createClient();
            $crawler = $client->request('GET', $page);
            $this->assertTrue($client->getResponse()->isSuccessful(), $page.' request unsuccessful');
            $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), $page.' Content-Type header wrong');
        }
    }
    public function testMethodsRedirects()
    {
        // Test redirects to top page
        foreach (array('/methods', '/methods/view/') as $page) {
            $client = static::createClient();
            $crawler = $client->request('GET', $page);
            $this->assertTrue($client->getResponse()->isRedirect(), $page.' isn\'t a redirect');
        }
    }
    public function testMethodsSitemap()
    {
        foreach (array('/methods/sitemap_1', '/methods/sitemap_2') as $xml) {
            $client = static::createClient();
            $crawler = $client->request('GET', '/'.$xml.'.xml');
            $this->assertTrue($client->getResponse()->isSuccessful(), $xml.'.xml request unsuccessful');
            $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'text/xml; charset=UTF-8'), $xml.'.xml Content-Type header wrong');
        }
    }
}

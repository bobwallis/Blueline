<?php
namespace Blueline\TowersBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class TowersControllerTest extends WebTestCase
{
    public function testTowers()
    {
        // Test welcome page and some other basic requests
        foreach (array('/towers/', '/towers/view/DESBOROUGH') as $page) {
            $client = static::createClient();
            $crawler = $client->request('GET', $page);
            $this->assertTrue($client->getResponse()->isSuccessful(), $page.' request unsuccessful');
        }
        foreach (array('/towers/view/DESBOROUGH.json') as $page) {
            $client = static::createClient();
            $crawler = $client->request('GET', $page);
            $this->assertTrue($client->getResponse()->isSuccessful(), $page.' request unsuccessful');
            $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), $page.' Content-Type header wrong');
        }
    }
    public function testTowersSearch()
    {
        // HTML searches
        foreach (array('/towers/search?q=oxford') as $page) {
            $client = static::createClient();
            $crawler = $client->request('GET', $page);
            $this->assertTrue($client->getResponse()->isSuccessful(), $page.' request unsuccessful');
        }
        // JSON searches
        foreach (array('/towers/search.json?q=oxford') as $page) {
            $client = static::createClient();
            $crawler = $client->request('GET', $page);
            $this->assertTrue($client->getResponse()->isSuccessful(), $page.' request unsuccessful');
            $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), $page.' Content-Type header wrong');
        }
    }
    public function testTowersRedirects()
    {
        // Test redirects to top page
        foreach (array('/towers', '/towers/view', '/towers/view/') as $page) {
            $client = static::createClient();
            $crawler = $client->request('GET', $page);
            $this->assertTrue($client->getResponse()->isRedirect('/towers/'), $page.' doesn\'t redirect to /towers/');
        }
    }
    public function testTowersSitemap()
    {
        foreach (array('/towers/sitemap') as $xml) {
            $client = static::createClient();
            $crawler = $client->request('GET', '/'.$xml.'.xml');
            $this->assertTrue($client->getResponse()->isSuccessful(), $xml.'.xml request unsuccessful');
            $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'text/xml; charset=UTF-8'), $xml.'.xml Content-Type header wrong');
        }
    }
}

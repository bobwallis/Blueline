<?php
namespace Blueline\AssociationsBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AssociationsControllerTest extends WebTestCase
{
    public function testAssociations()
    {
        // Test welcome page and some other basic requests
        foreach (array('/associations/', '/associations/view/OUS') as $page) {
            $client = static::createClient();
            $crawler = $client->request('GET', $page);
            $this->assertTrue($client->getResponse()->isSuccessful(), $page.' request unsuccessful');
        }
        foreach (array('/associations/view/OUS.json') as $page) {
            $client = static::createClient();
            $crawler = $client->request('GET', $page);
            $this->assertTrue($client->getResponse()->isSuccessful(), $page.' request unsuccessful');
            $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), $page.' Content-Type header wrong');
        }
    }
    public function testAssociationsSearch()
    {
        // HTML searches
        foreach (array('/associations/search?q=oxford') as $page) {
            $client = static::createClient();
            $crawler = $client->request('GET', $page);
            $this->assertTrue($client->getResponse()->isSuccessful(), $page.' request unsuccessful');
        }
        // JSON searches
        foreach (array('/associations/search.json?q=oxford') as $page) {
            $client = static::createClient();
            $crawler = $client->request('GET', $page);
            $this->assertTrue($client->getResponse()->isSuccessful(), $page.' request unsuccessful');
            $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), $page.' Content-Type header wrong');
        }
    }
    public function testAssociationsRedirects()
    {
        // Test redirects to top page
        foreach (array('/associations', '/associations/view', '/associations/view/') as $page) {
            $client = static::createClient();
            $crawler = $client->request('GET', $page);
            $this->assertTrue($client->getResponse()->isRedirect('/associations/'), $page.' doesn\'t redirect to /associations/');
        }
    }
    public function testAssociationsSitemap()
    {
        foreach (array('/associations/sitemap') as $xml) {
            $client = static::createClient();
            $crawler = $client->request('GET', '/'.$xml.'.xml');
            $this->assertTrue($client->getResponse()->isSuccessful(), $xml.'.xml request unsuccessful');
            $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'text/xml; charset=UTF-8'), $xml.'.xml Content-Type header wrong');
        }
    }
}

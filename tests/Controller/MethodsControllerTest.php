<?php
namespace Blueline\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class MethodsControllerTest extends WebTestCase
{
    public function testMethods()
    {
        $client = static::createClient();
        // Test welcome page and some other basic requests
        foreach (array('/methods/', '/methods/tutorials', '/methods/view/Cambridge_Surprise_Minor', '/methods/view/Wee_Willie_Winkie_Maximus', '/methods/view?stage=8&notation=x1x1x45x27') as $page) {
            $crawler = $client->request('GET', $page);
            $this->assertTrue($client->getResponse()->isSuccessful(), $page.' request unsuccessful');
        }
        foreach (array('/methods/view/Cambridge_Surprise_Minor.json') as $page) {
            $crawler = $client->request('GET', $page);
            $this->assertTrue($client->getResponse()->isSuccessful(), $page.' request unsuccessful');
            $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), $page.' Content-Type header wrong');
        }
        //foreach (array('/methods/view/Cambridge_Surprise_Minor.png?scale=1&style=numbers', '/methods/view/Cambridge_Surprise_Minor.png?scale=1&style=lines', '/methods/view/Cambridge_Surprise_Minor.png?scale=2&style=grid') as $page) {
        //    $crawler = $client->request('GET', $page);
        //    $this->assertTrue($client->getResponse()->isSuccessful(), $page.' request unsuccessful');
        //    $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'image/png'), $page.' Content-Type header wrong');
        //}
    }
    public function testMethodsSearch()
    {
        $client = static::createClient();
        // HTML searches
        foreach (array('/methods/search?q=oxford') as $page) {
            $crawler = $client->request('GET', $page);
            $this->assertTrue($client->getResponse()->isSuccessful(), $page.' request unsuccessful');
        }
        // JSON searches
        foreach (array('/methods/search.json?q=oxford') as $page) {
            $crawler = $client->request('GET', $page);
            $this->assertTrue($client->getResponse()->isSuccessful(), $page.' request unsuccessful');
            $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), $page.' Content-Type header wrong');
        }
    }
    public function testMethodsRedirects()
    {
        $client = static::createClient();
        // Test redirects to top page
        foreach (array('/methods', '/methods/view/') as $page) {
            $crawler = $client->request('GET', $page);
            $this->assertTrue($client->getResponse()->isRedirect(), $page.' isn\'t a redirect');
        }
    }
    public function testMethodsSitemap()
    {
        $client = static::createClient();
        foreach (array('/methods/sitemap_1', '/methods/sitemap_2') as $xml) {
            $crawler = $client->request('GET', $xml.'.xml');
            $this->assertTrue($client->getResponse()->isSuccessful(), $xml.'.xml request unsuccessful');
            $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'text/xml; charset=UTF-8'), $xml.'.xml Content-Type header wrong');
        }
    }
}

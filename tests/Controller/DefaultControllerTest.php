<?php
namespace Blueline\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    // Pages
    public function testPages()
    {
        $client = static::createClient();
        foreach (array('', 'about', 'methods/notation', 'methods/tutorials') as $html) {
            $crawler = $client->request('GET', '/'.$html);
            $this->assertTrue($client->getResponse()->isSuccessful(), $html.' request unsuccessful');
        }
    }

    // .txt files
    public function testRobots()
    {
        $client = static::createClient();
        foreach (array('robots', 'humans') as $txt) {
            $crawler = $client->request('GET', '/'.$txt.'.txt');
            $this->assertTrue($client->getResponse()->isSuccessful(), $txt.'.txt request unsuccessful');
            $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'text/plain; charset=UTF-8'), $txt.'.txt Content-Type header wrong');
        }
    }

    // XML sitemaps
    public function testSitemaps()
    {
        $client = static::createClient();
        foreach (array('sitemap', 'sitemap_root') as $xml) {
            $crawler = $client->request('GET', '/'.$xml.'.xml');
            $this->assertTrue($client->getResponse()->isSuccessful(), $xml.'.xml request unsuccessful');
            $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'text/xml; charset=UTF-8'), $xml.'.xml Content-Type header wrong');
        }
    }
}

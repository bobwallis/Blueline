<?php
namespace Blueline\BluelineBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    // .txt files
    public function testRobots()
    {
        foreach( array('robots','humans') as $txt ) {
            $client = static::createClient();
            $crawler = $client->request('GET', '/'.$txt.'.txt');
            $this->assertTrue($client->getResponse()->isSuccessful(), $txt.'.txt request unsuccessful');
            $this->assertTrue($client->getResponse()->headers->contains('Content-Type','text/plain; charset=UTF-8'), $txt.'.txt Content-Type header wrong');
        }
    }

    // .manifest
    public function testManifest()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/site.manifest');
        $this->assertTrue($client->getResponse()->isSuccessful(),'site.manifest request unsuccessful');
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type','text/cache-manifest; charset=UTF-8'),'.site.manifest Content-Type header wrong');
    }

    // XML sitemaps/browserconfig
    public function testSitemaps()
    {
        foreach( array('sitemap','sitemap_root','browserconfig') as $xml ) {
            $client = static::createClient();
            $crawler = $client->request('GET', '/'.$xml.'.xml');
            $this->assertTrue($client->getResponse()->isSuccessful(), $xml.'.xml request unsuccessful');
            $this->assertTrue($client->getResponse()->headers->contains('Content-Type','text/xml; charset=UTF-8'), $xml.'.xml Content-Type header wrong');
        }
    }
}

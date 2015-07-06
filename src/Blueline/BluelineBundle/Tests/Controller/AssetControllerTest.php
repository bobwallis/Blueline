<?php
namespace Blueline\BluelineBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AssetControllerTest extends WebTestCase
{
    // favicon
    public function testFavicon()
    {
        foreach (array('ico', 'png', 'bmp', 'svg') as $format) {
            $client = static::createClient();
            $crawler = $client->request('GET', '/favicon.'.$format);
            $this->assertTrue($client->getResponse()->isSuccessful(), 'favicon.'.$format.' request unsuccessful');
        }
    }

    // iOS stuff
    public function testiOS()
    {
        // Icons
        foreach (array('57x57', '72x72', '76x76', '114x114', '120x120', '144x144', '152x152', '196x196', '768x768') as $size) {
            $client = static::createClient();
            $crawler = $client->request('GET', '/apple-touch-icon-'.$size.'.png');
            $this->assertTrue($client->getResponse()->isSuccessful(), 'apple-touch-icon-'.$size.'.png request unsuccessful');
        }
        // Startup images
        foreach (array('1', '2') as $ratio) {
            foreach (array('640x1096', '640x920', '320x460', '1536x2008', '1496x2048', '768x1004', '748x1024') as $size) {
                $client = static::createClient();
                $crawler = $client->request('GET', '/apple-startup-image-'.$ratio.'-'.$size.'.png');
                $this->assertTrue($client->getResponse()->isSuccessful(), 'apple-startup-image-'.$ratio.'-'.$size.'.png request unsuccessful');
            }
        }
    }

    // images
    public function testImages()
    {
        foreach (array('png', 'svg') as $format) {
            foreach (array('database', 'external', 'search', 'select', 'welcome_associations', 'welcome_methods', 'welcome_towers') as $image) {
                $client = static::createClient();
                $crawler = $client->request('GET', '/images/'.$image.'.'.$format);
                $this->assertTrue($client->getResponse()->isSuccessful(), $image.'.'.$format.' request unsuccessful');
            }
        }
        $client = static::createClient();
        $crawler = $client->request('GET', '/images/loading.gif');
        $this->assertTrue($client->getResponse()->isSuccessful(), 'loading.gif request unsuccessful');
    }

    // font
    public function testFont()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/fonts/Blueline.woff');
        $this->assertTrue($client->getResponse()->isSuccessful(), 'Blueline.woff request unsuccessful');
    }
}

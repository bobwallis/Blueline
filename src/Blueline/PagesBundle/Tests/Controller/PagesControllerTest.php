<?php
namespace Blueline\BluelineBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PagesControllerTest extends WebTestCase
{
    public function testPages()
    {
        foreach( array('/','/about') as $page ) {
            $client = static::createClient();
            $crawler = $client->request('GET', $page);
            $this->assertTrue($client->getResponse()->isSuccessful(), $page.' request unsuccessful');
        }
    }
}

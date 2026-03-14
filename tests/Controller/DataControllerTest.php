<?php
namespace Blueline\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DataControllerTest extends WebTestCase
{
    // Pages
    public function testPages()
    {
        $client = static::createClient();
        foreach (array('collections' ,'methods', 'methods_collections', 'methods_similar', 'performances') as $table) {
            $crawler = $client->request('GET', '/data/'.$table.'.csv');
            $this->assertTrue($client->getResponse()->isSuccessful(), '/data/'.$table.'.csv request unsuccessful');
        }
    }
}

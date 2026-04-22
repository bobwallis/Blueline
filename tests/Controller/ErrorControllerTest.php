<?php

namespace Blueline\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ErrorControllerTest extends WebTestCase
{
    public function testDeletedRoutesReturnGone()
    {
        $client = static::createClient();

        foreach (['/towers', '/towers/anything/here', '/associations', '/associations/anything/here'] as $path) {
            $client->request('GET', $path);
            $this->assertSame(410, $client->getResponse()->getStatusCode(), $path.' should return 410 Gone');
        }
    }
}

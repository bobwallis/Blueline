<?php

namespace Blueline\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataControllerTest extends WebTestCase
{
    public function testCsvExportsHaveExpectedHeaders()
    {
        $client = static::createClient();
        $expectedHeaders = [
            'collections' => ['id', 'name', 'description'],
            'methods' => ['title', 'stage', 'notation'],
            'methods_collections' => ['id', 'position', 'method_title'],
            'methods_similar' => ['method1_title', 'method2_title', 'stage'],
            'performances' => ['method_title', 'date'],
        ];

        foreach ($expectedHeaders as $table => $expectedColumns) {
            $client->request('GET', '/data/'.$table.'.csv');
            $this->assertTrue($client->getResponse()->isSuccessful(), '/data/'.$table.'.csv request unsuccessful');
            $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'text/csv; charset=UTF-8') || $client->getResponse()->headers->contains('Content-Type', 'text/csv'), '/data/'.$table.'.csv Content-Type header wrong');
            foreach ($expectedColumns as $expectedColumn) {
                $this->assertNotSame('', $expectedColumn, '/data/'.$table.'.csv expected column should not be empty');
            }
            $this->assertInstanceOf(StreamedResponse::class, $client->getResponse(), '/data/'.$table.'.csv should use a streamed response');
        }
    }

    public function testUnknownCsvExportRouteReturnsNotFound()
    {
        $client = static::createClient();
        $client->request('GET', '/data/not_a_table.csv');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }
}

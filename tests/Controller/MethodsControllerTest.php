<?php
namespace Blueline\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MethodsControllerTest extends WebTestCase
{
    public function testWelcomePageShowsSearchAndCustomMethodForm()
    {
        $client = static::createClient();
        $client->request('GET', '/methods/');

        $this->assertTrue($client->getResponse()->isSuccessful(), '/methods/ request unsuccessful');
        $this->assertStringContainsString('Custom Method', $client->getResponse()->getContent());
        $this->assertStringContainsString('Search methods', $client->getResponse()->getContent());
    }

    public function testMethodViewHtmlAndJsonResponsesContainExpectedData()
    {
        $client = static::createClient();
        $client->request('GET', '/methods/view/Cambridge_Surprise_Minor');
        $this->assertTrue($client->getResponse()->isSuccessful(), '/methods/view/Cambridge_Surprise_Minor request unsuccessful');
        $this->assertStringContainsString('Cambridge Surprise Minor', $client->getResponse()->getContent());
        $this->assertStringContainsString('Place', $client->getResponse()->getContent());
        $this->assertStringContainsString('section class="method" data-cccbr-id="', $client->getResponse()->getContent());

        $client->request('GET', '/methods/view/Cambridge_Surprise_Minor.json');
        $this->assertTrue($client->getResponse()->isSuccessful(), '/methods/view/Cambridge_Surprise_Minor.json request unsuccessful');
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), '/methods/view/Cambridge_Surprise_Minor.json Content-Type header wrong');

        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Cambridge Surprise Minor', $payload[0]['title']);
        $this->assertSame('Cambridge_Surprise_Minor', $payload[0]['url']);
        $this->assertSame(6, $payload[0]['stage']);
    }

    public function testMethodViewRedirectsCanonicalAndEmptyUrls()
    {
        $client = static::createClient();

        $client->request('GET', '/methods');
        $this->assertTrue($client->getResponse()->isRedirect(), '/methods is not a redirect');

        $client->request('GET', '/methods/view/');
        $this->assertTrue($client->getResponse()->isRedirect(), '/methods/view/ is not a redirect');
        $this->assertStringContainsString('/methods/', (string) $client->getResponse()->headers->get('Location'));

        $client->request('GET', '/methods/view/CambridgeSurpriseMinor');
        $this->assertSame(301, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('/methods/view/Cambridge_Surprise_Minor', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testMethodViewReturnsNotFoundForUnknownMethod()
    {
        $client = static::createClient();
        $client->request('GET', '/methods/view/Definitely_Not_A_Method');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }

    public function testMethodsSearchHtmlAndJsonResponsesContainResults()
    {
        $client = static::createClient();

        $client->request('GET', '/methods/search?q=oxford');
        $this->assertTrue($client->getResponse()->isSuccessful(), '/methods/search?q=oxford request unsuccessful');
        $this->assertStringContainsString('Oxford', $client->getResponse()->getContent());

        $client->request('GET', '/methods/search.json?q=oxford');
        $this->assertTrue($client->getResponse()->isSuccessful(), '/methods/search.json?q=oxford request unsuccessful');
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'), '/methods/search.json?q=oxford Content-Type header wrong');

        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('oxford', $payload['query']['q']);
        $this->assertGreaterThan(0, $payload['count']);
        $this->assertNotEmpty($payload['results']);
        $this->assertTrue((bool) array_filter($payload['results'], function ($method) {
            return stripos($method['title'], 'Oxford') !== false;
        }), 'Search results should include an Oxford method');

        $this->assertStringContainsString('cccbrId', $payload['query']['fields']);
    }

    public function testMethodsSearchJsonReturnsOnlyRequestedFields()
    {
        $client = static::createClient();

        $client->request('GET', '/methods/search.json?q=oxford&fields=title,url');
        $this->assertTrue($client->getResponse()->isSuccessful(), '/methods/search.json?q=oxford&fields=title,url request unsuccessful');

        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('title,url', $payload['query']['fields']);
        $this->assertNotEmpty($payload['results']);

        $firstResult = $payload['results'][0];
        $this->assertSame(array('title', 'url'), array_keys($firstResult));
        $this->assertArrayNotHasKey('collections', $firstResult);
        $this->assertArrayNotHasKey('performances', $firstResult);
        $this->assertArrayNotHasKey('methodSimilarity1', $firstResult);
        $this->assertArrayNotHasKey('methodSimilarity2', $firstResult);
    }

    public function testMethodsSearchJsonUnknownFieldsAreIgnored()
    {
        $client = static::createClient();

        $client->request('GET', '/methods/search.json?q=oxford&fields=title,notAField');
        $this->assertTrue($client->getResponse()->isSuccessful(), '/methods/search.json?q=oxford&fields=title,notAField request unsuccessful');

        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('title', $payload['query']['fields']);
        $this->assertNotEmpty($payload['results']);
        $this->assertSame(array('title'), array_keys($payload['results'][0]));
    }

    public function testMethodsSearchRejectsInvalidRegularExpressions()
    {
        $client = static::createClient();
        $client->request('GET', '/methods/search?q=/(/');

        $this->assertSame(400, $client->getResponse()->getStatusCode());
    }

    public function testMethodPngRequestsAreValidatedAndNormalised()
    {
        $client = static::createClient();

        $client->request('GET', '/methods/view/Cambridge_Surprise_Minor.png');
        $this->assertSame(301, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('scale=1', (string) $client->getResponse()->headers->get('Location'));
        $this->assertStringContainsString('style=numbers', (string) $client->getResponse()->headers->get('Location'));

        $client->request('GET', '/methods/view/Cambridge_Surprise_Minor.png?scale=5&style=numbers');
        $this->assertSame(401, $client->getResponse()->getStatusCode());

        $client->request('GET', '/methods/view/Cambridge_Surprise_Minor.png?scale=1&style=invalid');
        $this->assertSame(401, $client->getResponse()->getStatusCode());

        $client->request('GET', '/methods/view/Cambridge_Surprise_Minor.png?scale=1&style=numbers');
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('scale=1&style=numbers', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testCustomMethodViewHtmlAndJsonResponsesContainExpectedData()
    {
        $client = static::createClient();

        $client->request('GET', '/methods/view?stage=8&notation=x1x1x45x27');
        $this->assertTrue($client->getResponse()->isSuccessful(), '/methods/view?stage=8&notation=x1x1x45x27 request unsuccessful');
        $this->assertStringContainsString('Place', $client->getResponse()->getContent());

        $client->request('GET', '/methods/view.json', array('stage' => 8, 'notation' => 'x1x1x45x27'));
        $this->assertTrue($client->getResponse()->isSuccessful(), '/methods/view.json?stage=8&notation=x1x1x45x27 request unsuccessful');

        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(8, $payload[0]['stage']);
        $this->assertSame('x1x1x45x27', $payload[0]['notation']);
    }

    public function testCustomMethodViewRequiresNotation()
    {
        $client = static::createClient();
        $client->request('GET', '/methods/view');

        $this->assertSame(400, $client->getResponse()->getStatusCode());
    }

    public function testCustomMethodViewRedirectsToExistingMethodWhenNotationMatches()
    {
        $client = static::createClient();
        $client->request('GET', '/methods/view/Cambridge_Surprise_Minor.json');
        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $client->request('GET', '/methods/view', array(
            'stage' => $payload[0]['stage'],
            'notation' => $payload[0]['notation'],
        ));

        $this->assertSame(301, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('/methods/view/Cambridge_Surprise_Minor', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testCustomMethodPngRequestsAreValidatedAndNormalised()
    {
        $client = static::createClient();

        $client->request('GET', '/methods/view.png', array('stage' => 8, 'notation' => 'x1x1x45x27'));
        $this->assertSame(301, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('scale=1', (string) $client->getResponse()->headers->get('Location'));
        $this->assertStringContainsString('style=numbers', (string) $client->getResponse()->headers->get('Location'));

        $client->request('GET', '/methods/view.png', array('stage' => 8, 'notation' => 'x1x1x45x27', 'scale' => 1, 'style' => 'numbers'));
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('scale=1&style=numbers', (string) $client->getResponse()->headers->get('Location'));

        $client->request('GET', '/methods/view.png', array('stage' => 8, 'notation' => 'x1x1x45x27', 'scale' => 5, 'style' => 'numbers'));
        $this->assertSame(401, $client->getResponse()->getStatusCode());

        $client->request('GET', '/methods/view.png', array('stage' => 8, 'notation' => 'x1x1x45x27', 'scale' => 1, 'style' => 'invalid'));
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testMethodsSitemapReturnsXml()
    {
        $client = static::createClient();
        foreach (array('/methods/sitemap_1', '/methods/sitemap_2') as $xml) {
            $client->request('GET', $xml.'.xml');
            $this->assertTrue($client->getResponse()->isSuccessful(), $xml.'.xml request unsuccessful');
            $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'text/xml; charset=UTF-8'), $xml.'.xml Content-Type header wrong');
            $this->assertStringContainsString('<urlset', $client->getResponse()->getContent());
        }
    }
}

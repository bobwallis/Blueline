<?php

namespace Blueline\Tests\EventListener;

use Blueline\EventListener\ResponseListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ResponseListenerTest extends TestCase
{
    public function testMinifiesHtmlResponsesOnMainRequests(): void
    {
        $response = new Response("<div>\n    <span>Test</span>\n</div>");
        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');

        $event = new ResponseEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('/'),
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        (new ResponseListener())->onKernelResponse($event);

        $this->assertSame('<div><span>Test</span></div>', $response->getContent());
    }

    public function testLeavesNonHtmlResponsesUnchanged(): void
    {
        $response = new Response("{\n    \"ok\": true\n}");
        $response->headers->set('Content-Type', 'application/json');

        $event = new ResponseEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('/api'),
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        (new ResponseListener())->onKernelResponse($event);

        $this->assertSame("{\n    \"ok\": true\n}", $response->getContent());
    }

    public function testLeavesSubRequestResponsesUnchanged(): void
    {
        $response = new Response("<div>\n    <span>Test</span>\n</div>");
        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');

        $event = new ResponseEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('/fragment'),
            HttpKernelInterface::SUB_REQUEST,
            $response,
        );

        (new ResponseListener())->onKernelResponse($event);

        $this->assertSame("<div>\n    <span>Test</span>\n</div>", $response->getContent());
    }
}

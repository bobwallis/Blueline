<?php

namespace Blueline\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Symfony event listener for kernel.response events.
 *
 * Minifies HTML responses by removing whitespace between tags.
 * Operates only on main requests with text/html content type.
 *
 * Equivalent to Twig's spaceless filter applied globally.
 */
#[AsEventListener(event: 'kernel.response', method: 'onKernelResponse', priority: -1)]
class ResponseListener
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();

        // Add Cache-Control headers for error responses to prevent caching of error pages, but allow short CDN caching for 404/410 errors
        if ($response->isClientError() || $response->isServerError()) {
            if (in_array($response->getStatusCode(), [404, 410], true)) {
                $response->headers->set('Cache-Control', 'public, s-maxage=60, max-age=0');
            } else {
                $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            }
        }

        // Strip whitespace between HTML tags (same as Twig's spaceless filter)
        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));
        if (!str_contains($contentType, 'text/html') && !str_contains($contentType, 'application/xhtml+xml')) {
            return;
        }
        $content = $response->getContent();
        if (false !== $content) {
            $minifiedContent = preg_replace('/>\s+</', '><', $content);
            if (null !== $minifiedContent) {
                $response->setContent($minifiedContent);
            }
        }
    }
}

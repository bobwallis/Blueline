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
        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));
        if (!str_contains($contentType, 'text/html') && !str_contains($contentType, 'application/xhtml+xml')) {
            return;
        }

        // Strip whitespace between HTML tags (same as Twig's spaceless filter)
        $content = $response->getContent();
        if ($content !== false) {
            $minifiedContent = preg_replace('/>\s+</', '><', $content);
            if ($minifiedContent !== null) {
                $response->setContent($minifiedContent);
            }
        }
    }
}

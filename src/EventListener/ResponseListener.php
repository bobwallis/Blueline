<?php
namespace Blueline\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Symfony event listener for kernel.response events.
 *
 * Minifies HTML responses by removing whitespace between tags.
 * Operates only on main requests with text/html content type.
 *
 * Equivalent to Twig's spaceless filter applied globally.
 */
#[AsEventListener(event: 'kernel.response')]
class ResponseListener
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $contentType = $response->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'text/html')) {
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

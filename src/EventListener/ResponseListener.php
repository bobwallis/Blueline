<?php
namespace Blueline\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Symfony event listener for kernel.response events.
 *
 * Minifies HTML responses in production by removing whitespace between tags.
 * Operates only on main requests with text/html content type.
 * Disabled in development for easier debugging.
 *
 * Equivalent to Twig's spaceless filter applied globally.
 */
#[AsEventListener(event: 'kernel.response')]
class ResponseListener
{
    public function __construct(
        private string $kernelEnvironment,
    ) {}

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Only minify HTML responses in production
        if ($this->kernelEnvironment !== 'prod') {
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
            $response->setContent(preg_replace('/>\s+</', '><', $content));
        }
    }
}

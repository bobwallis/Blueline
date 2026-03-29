<?php
namespace Blueline\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

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

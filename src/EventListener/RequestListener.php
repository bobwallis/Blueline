<?php

namespace Blueline\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Symfony event listener for kernel.request events.
 *
 * Populates request attributes with global application parameters:
 * - endpoint: Application base URL for links and oEmbed generation
 * - database_update: Timestamp of last database update.
 *                    Set to current time in non-prod environments
 *                    to disable caching during development.
 */
#[AsEventListener(event: 'kernel.request')]
class RequestListener
{
    public function __construct(
        #[Autowire('%env(DEFAULT_URI)%')]
        string $defaultUri,
        #[Autowire('%env(DATABASE_UPDATE)%')]
        private readonly string $databaseUpdate,
        #[Autowire('%kernel.environment%')]
        private readonly string $kernelEnvironment,
    ) {
        $this->defaultUri = rtrim($defaultUri, '/');
    }

    private readonly string $defaultUri;

    public function onKernelRequest(RequestEvent $event)
    {
        // Set global parameters that can be used in @Cache annotations and other places
        $event->getRequest()->attributes->set('endpoint', $this->defaultUri);
        if ('prod' == $this->kernelEnvironment) {
            $event->getRequest()->attributes->set('database_update', new \DateTime('@'.$this->databaseUpdate));
        } else {
            $event->getRequest()->attributes->set('database_update', new \DateTime());
        }
    }
}

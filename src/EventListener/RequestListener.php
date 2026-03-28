<?php
namespace Blueline\EventListener;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'kernel.request')]
class RequestListener
{
    public function __construct(
        private ParameterBagInterface $params,
        private string $kernelEnvironment,
    ) {}

    public function onKernelRequest(RequestEvent $event)
    {
        // Add some missing mime-types
        $event->getRequest()->setFormat('jsonp', 'application/javascript');
        $event->getRequest()->setFormat('manifest', 'text/cache-manifest');
        $event->getRequest()->setFormat('ico', 'image/x-icon');
        $event->getRequest()->setFormat('gif', 'image/gif');
        $event->getRequest()->setFormat('png', 'image/png');
        $event->getRequest()->setFormat('jpg', 'image/jpg');
        $event->getRequest()->setFormat('svg', 'image/svg+xml');
        $event->getRequest()->setFormat('pdf', 'application/pdf');
        $event->getRequest()->setFormat('woff', 'application/font-woff');
        $event->getRequest()->setFormat('woff2', 'application/font-woff2');

        // Set global parameters that can be used in @Cache annotations and other places
        $event->getRequest()->attributes->set('endpoint', $this->params->get('blueline.endpoint'));
        if ($this->kernelEnvironment == 'prod') {
            $event->getRequest()->attributes->set('asset_update', new \DateTime('@'.$this->params->get('blueline.asset_update')));
            $event->getRequest()->attributes->set('database_update', new \DateTime('@'.$this->params->get('blueline.database_update')));
        } else {
            $event->getRequest()->attributes->set('asset_update', new \DateTime());
            $event->getRequest()->attributes->set('database_update', new \DateTime());
        }
    }
}

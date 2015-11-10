<?php
namespace Blueline\BluelineBundle\EventListener;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListener
{

    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        // Add some missing mime-types
        $event->getRequest()->setFormat('atom', 'application/atom+xml');
        $event->getRequest()->setFormat('jsonp', 'application/javascript');
        $event->getRequest()->setFormat('manifest', 'text/cache-manifest');
        $event->getRequest()->setFormat('ico', 'image/x-icon');
        $event->getRequest()->setFormat('gif', 'image/gif');
        $event->getRequest()->setFormat('png', 'image/png');
        $event->getRequest()->setFormat('jpg', 'image/jpg');
        $event->getRequest()->setFormat('bmp', 'image/bmp');
        $event->getRequest()->setFormat('svg', 'image/svg+xml');
        $event->getRequest()->setFormat('woff', 'application/font-woff');
        $event->getRequest()->setFormat('woff2', 'application/font-woff2');

        // Set global parameters that can be used in @Cache annotations
        if ($this->container->getParameter('kernel.environment') == 'prod') {
            $event->getRequest()->attributes->set('asset_update', new \DateTime('@'.$this->container->getParameter('asset_update')));
            $event->getRequest()->attributes->set('database_update', new \DateTime('@'.$this->container->getParameter('database_update')));
        }
        else {
            $event->getRequest()->attributes->set('asset_update', new \DateTime());
            $event->getRequest()->attributes->set('database_update', new \DateTime());
        }
    }
}

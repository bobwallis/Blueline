<?php
namespace Blueline\BluelineBundle;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListener
{
    public function onKernelRequest( GetResponseEvent $event )
    {
        // Add some missing mime-types
        $event->getRequest()->setFormat( 'atom', 'application/atom+xml' );
        $event->getRequest()->setFormat( 'jsonp', 'application/javascript' );
        $event->getRequest()->setFormat( 'manifest', 'text/cache-manifest' );
        $event->getRequest()->setFormat( 'ico', 'image/x-icon' );
        $event->getRequest()->setFormat( 'gif', 'image/gif' );
        $event->getRequest()->setFormat( 'png', 'image/png' );
        $event->getRequest()->setFormat( 'jpg', 'image/jpg' );
        $event->getRequest()->setFormat( 'bmp', 'image/bmp' );
        $event->getRequest()->setFormat( 'svg', 'image/svg+xml' );
        $event->getRequest()->setFormat( 'woff', 'application/font-woff' );
    }
}

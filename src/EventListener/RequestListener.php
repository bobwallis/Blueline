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
        // Set global parameters that can be used in @Cache annotations and other places
        $event->getRequest()->attributes->set('endpoint', $this->params->get('blueline.endpoint'));
        if ($this->kernelEnvironment == 'prod') {
            $event->getRequest()->attributes->set('database_update', new \DateTime('@'.$this->params->get('blueline.database_update')));
        } else {
            $event->getRequest()->attributes->set('database_update', new \DateTime());
        }
    }
}

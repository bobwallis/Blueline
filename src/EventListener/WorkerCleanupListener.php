<?php
namespace Blueline\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

/**
 * Symfony event listener for kernel.terminate events.
 *
 * Clears Doctrine's identity map between requests for long-running workers
 * (for example FrankenPHP worker mode), preventing request-to-request entity
 * state reuse.
 */
#[AsEventListener(event: 'kernel.terminate', method: 'onKernelTerminate')]
class WorkerCleanupListener
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function onKernelTerminate(TerminateEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->entityManager->clear();
    }
}

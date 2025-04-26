<?php

declare(strict_types=1);

namespace Knp\DoctrineBehaviors\EventSubscriber;

use Psr\Log\LogLevel;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\PreUpdateEventArgs;
use Knp\DoctrineBehaviors\Contract\Entity\LoggableInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
final class LoggableEventSubscriber
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function postPersist(LifecycleEventArgs $lifecycleEventArgs): void
    {
        $entity = $lifecycleEventArgs->getObject();
        if (! $entity instanceof LoggableInterface) {
            return;
        }

        $createLogMessage = $entity->getCreateLogMessage();
        $this->logger->log(LogLevel::INFO, $createLogMessage);

        $this->logChangeSet($lifecycleEventArgs);
    }

    public function postUpdate(LifecycleEventArgs $lifecycleEventArgs): void
    {
        $entity = $lifecycleEventArgs->getObject();
        if (! $entity instanceof LoggableInterface) {
            return;
        }

        $this->logChangeSet($lifecycleEventArgs);
    }

    public function preRemove(LifecycleEventArgs $lifecycleEventArgs): void
    {
        $entity = $lifecycleEventArgs->getObject();

        if ($entity instanceof LoggableInterface) {
            $this->logger->log(LogLevel::INFO, $entity->getRemoveLogMessage());
        }
    }

    /**
     * Logs entity changeset
     */
    private function logChangeSet(PreUpdateEventArgs $lifecycleEventArgs): void
    {
        $entity = $lifecycleEventArgs->getObject();

        $changeSet = $lifecycleEventArgs->getEntityChangeSet();

        $message = $entity->getUpdateLogMessage($changeSet);

        if ($message === '') {
            return;
        }

        $this->logger->log(LogLevel::INFO, $message);
    }
}

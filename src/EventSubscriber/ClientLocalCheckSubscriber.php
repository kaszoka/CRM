<?php

namespace App\EventSubscriber;

use App\Entity\Client;
use App\Service\PostalCodeDistanceService;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ClientLocalCheckSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PostalCodeDistanceService $distanceService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => ['onClientSave', 10],
            BeforeEntityUpdatedEvent::class => ['onClientSave', 10],
        ];
    }

    public function onClientSave(BeforeEntityPersistedEvent|BeforeEntityUpdatedEvent $event): void
    {
        $entity = $event->getEntityInstance();
        if (!$entity instanceof Client) {
            return;
        }

        $isLocal = $this->distanceService->isLocal($entity->getPostalCode());
        $entity->setIsLocalClient($isLocal);
    }
}


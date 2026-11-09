<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Client;
use App\Entity\Suggestion;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class SuggestionFormSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            AfterEntityPersistedEvent::class => ['onEntitySaved'],
            AfterEntityUpdatedEvent::class => ['onEntitySaved'],
        ];
    }

    public function onEntitySaved(AfterEntityPersistedEvent|AfterEntityUpdatedEvent $event): void
    {
        $entity = $event->getEntityInstance();

        if (!$entity instanceof Client) {
            return;
        }

        $title = trim((string) $entity->getNewSuggestionTitle());
        $text  = trim((string) $entity->getNewSuggestionText());

        if ($text === '' && $title === '') {
            $this->clearDraftTags($entity);
            return;
        }

        $suggestion = new Suggestion();
        $suggestion->setTitle($title !== '' ? $title : 'Sugestia');
        $suggestion->setContent($text);
        $suggestion->setClient($entity);
        $suggestion->setCreatedBy($this->security->getUser());

        foreach ($entity->getSuggestionDraftTags() as $tag) {
            $suggestion->addTag($tag);
        }

        $this->entityManager->persist($suggestion);

        $this->clearDraftTags($entity);

        $entity->setNewSuggestionTitle(null);
        $entity->setNewSuggestionText(null);

        $this->entityManager->flush();
    }

    private function clearDraftTags(Client $client): void
    {
        foreach ($client->getSuggestionDraftTags()->toArray() as $tag) {
            $client->removeSuggestionDraftTag($tag);
        }
    }
}


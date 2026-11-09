<?php

namespace App\EventSubscriber;

use App\Entity\ActivityLog;
use App\Entity\Client;
use App\Entity\Industry;
use App\Entity\Task;
use App\Entity\User;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityDeletedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EasyAdminActivitySubscriber implements EventSubscriberInterface
{
    private array $originalData = [];

    public function __construct(
        private ActivityLogger $logger,
        private EntityManagerInterface $em,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityUpdatedEvent::class => 'onBeforeUpdate',
            AfterEntityPersistedEvent::class => 'onAfterCreate',
            AfterEntityUpdatedEvent::class => 'onAfterUpdate',
            AfterEntityDeletedEvent::class => 'onAfterDelete',
        ];
    }

    public function onBeforeUpdate(BeforeEntityUpdatedEvent $event): void
    {
        $entity = $event->getEntityInstance();
        if (!$this->isTrackedEntity($entity)) return;

        $uow = $this->em->getUnitOfWork();
        $uow->computeChangeSets();
        $original = $uow->getOriginalEntityData($entity);

        if ($entity instanceof Client) {
            $conn = $this->em->getConnection();
            $indRows = $conn->fetchAllAssociative(
                'SELECT i.name FROM industry i INNER JOIN client_industry ci ON ci.industry_id = i.id WHERE ci.client_id = ?',
                [$entity->getId()]
            );
            $original['industries'] = implode(', ', array_column($indRows, 'name')) ?: '(brak)';

            $assRows = $conn->fetchAllAssociative(
                'SELECT a.name FROM assortment a INNER JOIN client_assortment ca ON ca.assortment_id = a.id WHERE ca.client_id = ?',
                [$entity->getId()]
            );
            $original['assortments'] = implode(', ', array_column($assRows, 'name')) ?: '(brak)';
        }

        $this->originalData[spl_object_id($entity)] = $original;
    }

    public function onAfterCreate(AfterEntityPersistedEvent $event): void
    {
        $entity = $event->getEntityInstance();
        if (!$this->isTrackedEntity($entity)) return;

        $this->logger->log(
            action: ActivityLog::ACTION_CREATE,
            entityType: ActivityLogger::getEntityType($entity),
            entityId: $entity->getId(),
            entityName: ActivityLogger::getEntityName($entity),
            description: 'Dodano: ' . ActivityLogger::getEntityName($entity),
        );
    }

    public function onAfterUpdate(AfterEntityUpdatedEvent $event): void
    {
        $entity = $event->getEntityInstance();
        if (!$this->isTrackedEntity($entity)) return;

        $objectId = spl_object_id($entity);
        $original = $this->originalData[$objectId] ?? [];
        unset($this->originalData[$objectId]);

        $changes = $this->computeChanges($entity, $original);

        if (empty($changes)) return;

        $descriptions = [];
        $fieldLabels = $this->getFieldLabels($entity);
        foreach ($changes as $field => $change) {
            $label = $fieldLabels[$field] ?? $field;
            $descriptions[] = $label . ': ' . $change['old'] . ' → ' . $change['new'];
        }

        $this->logger->log(
            action: ActivityLog::ACTION_UPDATE,
            entityType: ActivityLogger::getEntityType($entity),
            entityId: $entity->getId(),
            entityName: ActivityLogger::getEntityName($entity),
            changes: $changes,
            description: implode(', ', $descriptions),
        );
    }

    public function onAfterDelete(AfterEntityDeletedEvent $event): void
    {
        $entity = $event->getEntityInstance();
        if (!$this->isTrackedEntity($entity)) return;

        $this->logger->log(
            action: ActivityLog::ACTION_DELETE,
            entityType: ActivityLogger::getEntityType($entity),
            entityId: $entity->getId(),
            entityName: ActivityLogger::getEntityName($entity),
            description: 'Usunięto: ' . ActivityLogger::getEntityName($entity),
        );
    }

    private function isTrackedEntity(object $entity): bool
    {
        return $entity instanceof Client
            || $entity instanceof Task
            || $entity instanceof User;
    }

    private function computeChanges(object $entity, array $original): array
    {
        $changes = [];
        $trackedFields = $this->getTrackedFields($entity);

        foreach ($trackedFields as $field => $getter) {
            $newValue = $entity->$getter();
            $oldValue = $original[$field] ?? null;

            $newFormatted = ActivityLogger::formatValue($newValue);
            $oldFormatted = ActivityLogger::formatValue($oldValue);

            if ($newFormatted !== $oldFormatted) {
                $changes[$field] = [
                    'old' => $oldFormatted,
                    'new' => $newFormatted,
                ];
            }
        }

        return $changes;
    }

    private function getTrackedFields(object $entity): array
    {
        if ($entity instanceof Client) {
            return [
                'nip' => 'getNip',
                'companyName' => 'getCompanyName',
                'phone' => 'getPhone',
                'email' => 'getEmail',
                'street' => 'getStreet',
                'city' => 'getCity',
                'postalCode' => 'getPostalCode',
                'hasBusinessAccount' => 'isHasBusinessAccount',
                'hasPurchased' => 'isHasPurchased',
                'industries' => 'getIndustriesString',
                'assortments' => 'getAssortmentsString',
                'contactMethods' => 'getContactMethodsString',
                'source' => 'getSourcesString',
                'isLocalClient' => 'isLocalClient',
                'turnover' => 'getTurnover',
                'notes' => 'getNotes',
            ];
        }

        if ($entity instanceof Task) {
            return [
                'title' => 'getTitle',
                'description' => 'getDescription',
                'priority' => 'getPriority',
                'status' => 'getStatus',
                'assignedTo' => 'getAssignedTo',
                'client' => 'getClient',
                'dueDate' => 'getDueDate',
            ];
        }

        return [];
    }

    private function getFieldLabels(object $entity): array
    {
        if ($entity instanceof Client) return ActivityLogger::getClientFieldLabels();
        if ($entity instanceof Task) return ActivityLogger::getTaskFieldLabels();
        return [];
    }
}


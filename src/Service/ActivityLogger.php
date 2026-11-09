<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\Client;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ActivityLogger
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
    ) {
    }

    public function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?string $entityName = null,
        ?array $changes = null,
        ?string $description = null,
    ): void {
        $log = new ActivityLog();
        $log->setAction($action);
        $log->setEntityType($entityType);
        $log->setEntityId($entityId);
        $log->setEntityName($entityName);
        $log->setUser($this->security->getUser());
        $log->setChanges($changes);
        $log->setDescription($description);

        $this->em->persist($log);
        $this->em->flush();
    }

    public static function getClientFieldLabels(): array
    {
        return [
            'nip' => 'NIP',
            'companyName' => 'Nazwa firmy',
            'phone' => 'Telefon',
            'email' => 'E-mail',
            'street' => 'Ulica',
            'city' => 'Miasto',
            'postalCode' => 'Kod pocztowy',
            'hasBusinessAccount' => 'Konto biznesowe',
            'hasPurchased' => 'Kupił',
            'industries' => 'Branże',
            'assortments' => 'Asortyment',
            'contactMethods' => 'Forma kontaktu',
            'source' => 'Źródło',
            'isLocalClient' => 'Klient lokalny',
            'turnover' => 'Obroty',
            'notes' => 'Uwagi',
        ];
    }

    public static function getTaskFieldLabels(): array
    {
        return [
            'title' => 'Tytuł',
            'description' => 'Opis',
            'priority' => 'Priorytet',
            'status' => 'Status',
            'assignedTo' => 'Przypisano do',
            'client' => 'Klient',
            'dueDate' => 'Termin',
        ];
    }

    public static function getEntityName(object $entity): string
    {
        if ($entity instanceof Client) {
            return $entity->getCompanyName() ?? 'Klient #' . $entity->getId();
        }
        if ($entity instanceof Task) {
            return $entity->getTitle() ?? 'Zadanie #' . $entity->getId();
        }
        if ($entity instanceof User) {
            return $entity->getFirstName() . ' ' . $entity->getLastName();
        }
        return (string) $entity;
    }

    public static function getEntityType(object $entity): string
    {
        if ($entity instanceof Client) return 'Client';
        if ($entity instanceof Task) return 'Task';
        if ($entity instanceof User) return 'User';
        return (new \ReflectionClass($entity))->getShortName();
    }

    public static function formatValue(mixed $value): string
    {
        if ($value === null) return '(brak)';
        if (is_bool($value)) return $value ? 'Tak' : 'Nie';
        if ($value instanceof \DateTimeInterface) return $value->format('d.m.Y H:i');
        if ($value instanceof Client) return $value->getCompanyName() ?? '(klient)';
        if ($value instanceof User) return $value->getFirstName() . ' ' . $value->getLastName();
        if ($value instanceof \Doctrine\Common\Collections\Collection) {
            $items = $value->map(fn($item) => method_exists($item, '__toString') ? (string) $item : '?')->toArray();
            return $items ? implode(', ', $items) : '(brak)';
        }
        if (is_object($value) && method_exists($value, '__toString')) return (string) $value;
        if (is_array($value)) return json_encode($value);
        return (string) $value;
    }
}


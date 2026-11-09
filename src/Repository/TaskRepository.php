<?php

namespace App\Repository;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function findByAssignedUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.assignedTo = :user')
            ->setParameter('user', $user)
            ->orderBy('t.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingTasks(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.status != :completed')
            ->setParameter('completed', Task::STATUS_COMPLETED)
            ->orderBy('FIELD(t.priority, :high, :medium, :low)', 'ASC')
            ->setParameter('high', Task::PRIORITY_HIGH)
            ->setParameter('medium', Task::PRIORITY_MEDIUM)
            ->setParameter('low', Task::PRIORITY_LOW)
            ->getQuery()
            ->getResult();
    }

    public function findOverdueTasks(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.dueDate < :today')
            ->andWhere('t.status != :completed')
            ->setParameter('today', new \DateTime('today'))
            ->setParameter('completed', Task::STATUS_COMPLETED)
            ->orderBy('t.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}


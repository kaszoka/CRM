<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    public function findByIndustry(int $industryId): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.industries', 'i')
            ->andWhere('i.id = :industryId')
            ->setParameter('industryId', $industryId)
            ->orderBy('c.companyName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findLocalClients(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isLocalClient = :isLocal')
            ->setParameter('isLocal', true)
            ->orderBy('c.companyName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findClientsWhoPurchased(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.hasPurchased = :purchased')
            ->setParameter('purchased', true)
            ->orderBy('c.companyName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}


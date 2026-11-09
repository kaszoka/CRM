<?php

namespace App\Repository;

use App\Entity\Industry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class IndustryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Industry::class);
    }

    public function findOrCreate(string $name): Industry
    {
        $industry = $this->findOneBy(['name' => $name]);

        if (!$industry) {
            $industry = new Industry();
            $industry->setName($name);
            $this->getEntityManager()->persist($industry);
        }

        return $industry;
    }
}


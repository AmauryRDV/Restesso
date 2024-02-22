<?php

namespace App\Repository;

use App\Entity\Coffee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Coffee>
 *
 * @method Coffee|null find($id, $lockMode = null, $lockVersion = null)
 * @method Coffee|null findOneBy(array $criteria, array $orderBy = null)
 * @method Coffee[]    findAll()
 * @method Coffee[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CoffeeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Coffee::class);
    }

    public function findActive(int $id): ?Coffee
    {
        return $this->createQueryBuilder('coffee')
            ->andWhere('coffee.id = :id')
            ->andWhere("coffee.status='active'")
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('coffee')
            ->andWhere("coffee.status='active'")
            ->getQuery()
            ->getResult()
        ;
    }
}

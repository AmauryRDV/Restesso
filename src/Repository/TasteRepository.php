<?php

namespace App\Repository;

use App\Entity\Taste;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Taste>
 *
 * @method Taste|null find($id, $lockMode = null, $lockVersion = null)
 * @method Taste|null findOneBy(array $criteria, array $orderBy = null)
 * @method Taste[]    findAll()
 * @method Taste[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TasteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Taste::class);
    }

    public function findActive(int $id): ?Taste
    {
        return $this->createQueryBuilder('taste')
            ->andWhere('taste.id = :id')
            ->andWhere("taste.status='active'")
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('taste')
            ->andWhere("taste.status='active'")
            ->getQuery()
            ->getResult()
        ;
    }
}

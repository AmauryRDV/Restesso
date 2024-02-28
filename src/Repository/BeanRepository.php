<?php

namespace App\Repository;

use App\Entity\Bean;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bean>
 *
 * @method Bean|null find($id, $lockMode = null, $lockVersion = null)
 * @method Bean|null findOneBy(array $criteria, array $orderBy = null)
 * @method Bean[]    findAll()
 * @method Bean[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BeanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bean::class);
    }

    public function findActive(int $id): ?Bean
    {
        return $this->createQueryBuilder('bean')
            ->andWhere('bean.id = :id')
            ->andWhere("bean.status='active'")
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('bean')
            ->andWhere("bean.status='active'")
            ->getQuery()
            ->getResult()
        ;
    }
}

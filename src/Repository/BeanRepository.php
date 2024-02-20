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

//    /**
//     * @return Bean[] Returns an array of Bean objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Bean
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

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

//    /**
//     * @return Taste[] Returns an array of Taste objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Taste
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

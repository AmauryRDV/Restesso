<?php

namespace App\Repository;

use App\Entity\LoadedFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoadedFile>
 *
 * @method LoadedFile|null find($id, $lockMode = null, $lockVersion = null)
 * @method LoadedFile|null findOneBy(array $criteria, array $orderBy = null)
 * @method LoadedFile[]    findAll()
 * @method LoadedFile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoadedFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoadedFile::class);
    }

//    /**
//     * @return LoadedFile[] Returns an array of LoadedFile objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?LoadedFile
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

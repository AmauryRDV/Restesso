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

    public function findActive(int $id): ?LoadedFile
    {
        return $this->createQueryBuilder('loadedFile')
            ->andWhere('loadedFile.id = :id')
            ->andWhere("loadedFile.status='active'")
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('loadedFile')
            ->andWhere("loadedFile.status='active'")
            ->getQuery()
            ->getResult()
        ;
    }
}

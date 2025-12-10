<?php

namespace App\Repository;

use App\Doctrine\Enum\SortOrderTypeEnum;
use App\Entity\Bank;
use App\Entity\Student;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

final class BankRepository extends ServiceEntityRepository
{
    public const string ALIAS = 'b';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bank::class);
    }

    public function getAllSortedByNameQB(): QueryBuilder
    {
        return $this->createQueryBuilder('b')->orderBy('b.name', SortOrderTypeEnum::ASC);
    }

    public function getStudentRelatedItemsQB(?Student $student = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('b');
        if ($student instanceof Student && !is_null($student->getId())) {
            // $student is not null
            $qb
                ->where('b.parent = :parent')
                ->setParameter('parent', $student->getParent())
            ;
        } else {
            // $student is null
            $qb->where('b.id < 0');
        }

        return $qb;
    }

    public function getStudentRelatedItemsQ(?Student $student = null): Query
    {
        return $this->getStudentRelatedItemsQB($student)->getQuery();
    }

    public function getStudentRelatedItems(?Student $student = null): array
    {
        return $this->getStudentRelatedItemsQ($student)->getResult();
    }
}

<?php

namespace App\Repository;

use App\Doctrine\Enum\SortOrderTypeEnum;
use App\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

final class PersonRepository extends ServiceEntityRepository
{
    public const string ALIAS = 'p';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Person::class);
    }

    public function getEnabledSortedBySurnameQB(): QueryBuilder
    {
        return $this->createQueryBuilder(self::ALIAS)
            ->where(sprintf('%s.enabled = :enabled', self::ALIAS))
            ->setParameter('enabled', true)
            ->orderBy(sprintf('%s.surname', self::ALIAS), SortOrderTypeEnum::ASC)
            ->addOrderBy(sprintf('%s.name', self::ALIAS), SortOrderTypeEnum::ASC);
    }

    public function getEnabledSortedBySurnameQ(): Query
    {
        return $this->getEnabledSortedBySurnameQB()->getQuery();
    }

    public function getEnabledSortedBySurname(): array
    {
        return $this->getEnabledSortedBySurnameQ()->getResult();
    }
}

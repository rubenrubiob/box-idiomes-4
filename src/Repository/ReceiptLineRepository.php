<?php

namespace App\Repository;

use App\Entity\ReceiptLine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ReceiptLineRepository extends ServiceEntityRepository
{
    public const string ALIAS = 'rl';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReceiptLine::class);
    }
}

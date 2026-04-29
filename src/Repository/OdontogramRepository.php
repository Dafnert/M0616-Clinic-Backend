<?php

namespace App\Repository;

use App\Entity\Odontogram;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Odontogram>
 */
class OdontogramRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Odontogram::class);
    }

    /**
     * Returns all odontogram records for a given patient.
     *
     * @return Odontogram[]
     */
    public function findByPatientId(int $patientId): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.patient = :patientId')
            ->setParameter('patientId', $patientId)
            ->orderBy('o.toothNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds the odontogram record for a specific tooth of a patient.
     */
    public function findOneByPatientAndTooth(int $patientId, int $toothNumber): ?Odontogram
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.patient = :patientId')
            ->andWhere('o.toothNumber = :toothNumber')
            ->setParameter('patientId', $patientId)
            ->setParameter('toothNumber', $toothNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
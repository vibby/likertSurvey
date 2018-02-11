<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Respondent;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class RespondentRepository extends EntityRepository
{
    public function getQueryKeyList(array $keys)
    {
        return $this->createQueryBuilder('r')
            ->where('r.key IN (:keys)')
            ->setParameter(':keys', $keys)
            ->getQuery();
    }

    public function getQueryManagerUnderCollaboratorsCount($count = 5)
    {
        return $this->createQueryBuilder('r')
            ->select('r')
            ->leftJoin('r.subordinates', 's')
            ->having('count(s.id) < :count')
            ->setParameter(':count', $count)
            ->where('r.feedbackTeam = 1')
            ->groupBy('r.id')
            ->getQuery();
    }

    public function getQueryAll()
    {
        return $this->createQueryBuilder('r')
            ->getQuery();
    }

    public function getQueryUnconnectedSinceXDays($fromDate, $maxCount = 3)
    {
        $qb = $this->createQueryBuilder('r');
        $qb = $this->addExportWhere($qb, $fromDate, $maxCount);

        return $qb->getQuery();
    }

    private function addExportWhere(QueryBuilder $qb, $fromDate, $maxCount)
    {
        return $qb->where(<<<DQL
            r.revivedCount <= :revived_count
            and
            (
                r.lastConnectionDate < :date
                or
                (
                    r.lastConnectionDate = :null
                    and
                    r.createdDate < :date
                )
            )
DQL
        )
        ->setParameter(':date', $fromDate)
        ->setParameter(':null', null)
        ->setParameter(':revived_count', $maxCount);

        return $qb;
    }

    public function updateIncrementExport($fromDate, $maxCount = 3)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update(Respondent::class, 'r');
        $qb->set('r.revivedCount', 'r.revivedCount+1');
        $qb = $this->addExportWhere($qb, $fromDate, $maxCount);

        return $qb->getQuery()->execute();
    }
}

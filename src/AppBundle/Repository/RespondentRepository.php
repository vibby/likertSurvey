<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

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

    public function getQueryUnconnectedSinceXDays($days)
    {
        $from = new \DateTime();
        $from->sub(new \DateInterval(sprintf('P%sD', $days)));

        return $this->createQueryBuilder('r')
            ->where('r.lastConnected < :date')
            ->setParameter(':date', $from)
            ->getQuery();
    }
}

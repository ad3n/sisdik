<?php

namespace Langgas\SisdikBundle\Repository;

use Doctrine\ORM\EntityRepository;

class SekolahRepository extends EntityRepository
{
    public function findAll()
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('sekolah')
            ->from($this->getEntityName(), 'sekolah')
            ->getQuery()
            ->useQueryCache(true)
            ->getResult()
        ;
    }
}

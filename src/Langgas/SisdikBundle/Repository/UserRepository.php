<?php

namespace Langgas\SisdikBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Entity\Sekolah;

class UserRepository extends EntityRepository
{
    public function findByRole(Sekolah $sekolah, $role)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb
            ->select('user')
            ->from($this->_entityName, 'user')
            ->where('user.sekolah = :sekolah')
            ->andWhere('user.roles LIKE :roles')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('roles', '%"' . $role . '"%')
        ;

        return $qb->getQuery()->getResult();
    }
}

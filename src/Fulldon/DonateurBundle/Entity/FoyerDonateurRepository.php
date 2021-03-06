<?php

namespace Fulldon\DonateurBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * FoyerDonateurRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class FoyerDonateurRepository extends \Doctrine\ORM\EntityRepository {

    public function getFoyerByEmail($email) {
        $qb = $this->createQueryBuilder('p')
                ->select('p.email')
                ->where('p.email = :email')
                ->setParameter('email', $email);
        return $qb->getQuery()->getResult();
    }

}

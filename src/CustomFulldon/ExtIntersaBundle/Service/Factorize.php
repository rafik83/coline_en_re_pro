<?php
namespace CustomFulldon\ExtIntersaBundle\Service;
/*
 * This file is part of the fulldon project
 *
 * (c) SAMI BOUSSACSOU <boussacsou@intersa.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAware;




class Factorize extends ContainerAware {
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }
    public function checkNote($q) {
        return (isset($q) && !empty($q) && is_numeric($q) && $q <=10 && $q >=0) ;
    }
}
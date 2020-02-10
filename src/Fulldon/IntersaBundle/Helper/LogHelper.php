<?php
/*
 * This file is part of the fulldon project
 *
 * (c) SAMI BOUSSACSOU <boussacsou@intersa.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Fulldon\IntersaBundle\Helper;
use Doctrine\ORM\EntityManager;
class LogHelper
{
    const ROLE_ADMIN = 'ROLE_INTERSA';

    public function __construct(EntityManager $em)
    {
        $this->em = $em;

    }

    function getRole($current_user)
    {

        $role = null;
        $roles = $current_user->getRoles();
        $arr = array_map(function ($elem) {
            return $elem->getRole();
        }, $roles);

        if (in_array(self::ROLE_ADMIN . '_N1', $arr) ||
            in_array(self::ROLE_ADMIN . '_N2', $arr) ||
            in_array(self::ROLE_ADMIN . '_N3', $arr)
        ) {
            $role = 'ROLE_INTERSA';
        } else {
            $role =  'ROLE_ASSOC';
        }

        return $role;
    }

    public function getAddMsgLog($obj, $quoi)
    {

        $msg = 'Création d\'un nouveau ' . $quoi . '[' . $obj->getId() . ']';

        if ($obj instanceof \Fulldon\DonateurBundle\Entity\Pnd) {
            $msg = $msg . ' [Motif : ' . $obj->getMotif()->getName() . ']';
        }

        return $msg;
    }

    public function getModMsgLog($obj, $quoi) {
        $msg = $quoi.'['.$obj->getId().'] : Les éléments suivants : ';
        $uow = $this->em->getUnitOfWork();
        $uow->computeChangeSets();
        $attr = array();
        $changeset = $uow->getEntityChangeSet($obj);
        foreach($changeset as $key => $changes) {
            if($changes[0] != $changes[1]) {
                foreach(array(
                            '\Fulldon\DonateurBundle\Entity\MotifAbo',
                            '\Fulldon\DonateurBundle\Entity\MotifRejetPrelevement',
                            '\Fulldon\DonateurBundle\Entity\MotifDisableDon',
                            '\Fulldon\DonateurBundle\Entity\MotifPnd',
                        ) as $class ) {
                    if($changes[1] instanceof $class ) {
                        $changes[1] = $changes[1]->getName();
                    }
                    if($changes[0] instanceof $class ) {
                        $changes[0] = $changes[0]->getName();
                    }
                }
                //$msg = $changes[1]->getNom();
                if(!is_object($changes[0]) && !is_object($changes[1]))
                    $attr[]= $key.'([A:'.$changes[0].'];[N:'.$changes[1].'])';
                else
                    $attr[]= $key;
            }
        }
        $msg .=implode(',', $attr).' ont été modifiés';
        return $msg;
    }

}
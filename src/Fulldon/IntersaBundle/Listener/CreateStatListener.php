<?php
/*
 * This file is part of the fulldon project
 *
 * (c) SAMI BOUSSACSOU <boussacsou@intersa.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fulldon\IntersaBundle\Listener;

use Symfony\Component\EventDispatcher\Event;
use Fulldon\IntersaBundle\Event\HistoryStatEvent;
use Fulldon\IntersaBundle\Entity\Stat;
use Doctrine\ORM\EntityManager;

class CreateStatListener {
    protected $em;
    function __construct(EntityManager $em)
    {
//        die('here');
        $this->em = $em;
    }
    public function addStat(HistoryStatEvent $event) {

//        die('here');
        $stat = new Stat();

        $stat->setUser($event->getUser());
        $stat->setTypeStat($event->getType());
        $this->em->persist($stat);
        $this->em->flush();
    }
}
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
use Fulldon\IntersaBundle\Event\HistoryLogEvent;
use Fulldon\IntersaBundle\Entity\Log;
use Doctrine\ORM\EntityManager;

class CreateLogListener {
    protected $em;
    function __construct(EntityManager $em)
    {
//        die('here');
        $this->em = $em;
    }
    public function addLog(HistoryLogEvent $event) {

//        die('here');
    $log = new Log();
        $log->setDonateur($event->getDonateur());
        if(is_object($event->getUser())) {
            $log->setUser($event->getUser());
        }
        $log->setDon($event->getDon());
        $log->setRole($event->getRole());
        $log->setTypeLog($event->getType());
        $log->setDescAction($event->getMsg());
        $this->em->persist($log);
        $this->em->flush();
    }
}
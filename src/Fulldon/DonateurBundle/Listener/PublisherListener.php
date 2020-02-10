<?php

/*
 * This file is part of the fulldon project
 *
 * (c) SAMI BOUSSACSOU <boussacsou@intersa.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fulldon\DonateurBundle\Listener;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class PublisherListener {

    public function onCoreController(FilterControllerEvent $event) {


//        var_dump(HttpKernelInterface::MASTER_REQUEST);
//          die('icicicicicii');
        if (HttpKernelInterface::MASTER_REQUEST == $event->getRequestType()) {


//            var_dump($event);
//            die('$event');
            $_controller = $event->getController();


            if (isset($_controller[0])) {
                $controller = $_controller[0];

                if (method_exists($controller, 'preExecute')) {
                    $controller->preExecute(); // la methode n existe pas pour showaction
//                    var_dump($controller->preExecute());
//                    die('method_exists');
                }
            }
//            die('end if');
        }
    }

}

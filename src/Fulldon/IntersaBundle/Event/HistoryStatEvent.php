<?php
/*
 * This file is part of the fulldon project
 *
 * (c) SAMI BOUSSACSOU <boussacsou@intersa.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 namespace Fulldon\IntersaBundle\Event;

 use Symfony\Component\EventDispatcher\Event;
 use Fulldon\DonateurBundle\Entity\Donateur;
 use Fulldon\SecurityBundle\Entity\User;
 use Fulldon\IntersaBundle\Entity\TypeLog;

 class HistoryStatEvent extends Event {
     protected $user;
     protected $type;


     public static function constr1( User $user, $type) {
//         die('here');
         $instance = new self();
         $instance->user = $user;
         $instance->type = $type;
         return $instance;
     }
     public static function constr2($type) {
//         die('here');
         $instance = new self();
         $instance->type = $type;
         return $instance;
     }

     public function setUser( User $user ) {
         $this->user = $user;
     }

     public function setType( $type) {
         $this->type = $type;

     }

     public function getUser() {
         return $this->user;
     }

     public function getType() {
         return $this->type;
     }


 }
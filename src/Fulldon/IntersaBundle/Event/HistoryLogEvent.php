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
 use Fulldon\DonateurBundle\Entity\Don;
 use Fulldon\SecurityBundle\Entity\User;
 use Fulldon\IntersaBundle\Entity\TypeLog;

 class HistoryLogEvent extends Event {
     protected $user;
     protected $donateur;
     protected $type;
     protected $msg;
     protected $role;
     protected $don;


     public static function constr1( User $user, Donateur $donateur, TypeLog $type, $msg) {
//         die('here');
         $instance = new self();
         $instance->user = $user;
         $instance->donateur = $donateur;
         $instance->type = $type;
         $instance->msg = $msg;
         return $instance;
     }
     public static function constr2( User $user, TypeLog $type, $msg) {
//         die('here');
         $instance = new self();
         $instance->user = $user;
         $instance->type = $type;
         $instance->msg = $msg;
         return $instance;
     }

     public static function constr3( Donateur $donateur, TypeLog $type, $msg) {
//         die('here');
         $instance = new self();
         $instance->type = $type;
         $instance->msg = $msg;
         $instance->donateur = $donateur;
         return $instance;
     }
     public static function mainConstr($user,  $donateur,  $type, $msg, $role,  $don) {
//         die('here');
         $instance = new self();
         $instance->user = $user;
         $instance->donateur = $donateur;
         $instance->type = $type;
         $instance->msg = $msg;
         $instance->don = $don;
         $instance->role = $role;
         return $instance;
     }



     public function setUser(  $user ) {
         $this->user = $user;

     }
     public function setDonateur( $donateur) {
         $this->donateur = $donateur;

     }
     public function setTypeLog( $type) {
         $this->type = $type;

     }
     public function setMsg($msg) {
         $this->msg = $msg;
     }
     public function getUser() {
         return $this->user;

     }
     public function getDonateur() {
         return $this->donateur;
     }
     public function getType() {
         return $this->type;
     }
     public function getMsg() {
         return $this->msg;
     }
     public function getRole() {
         return $this->role;
     }
     public function setRole($role) {
         $this->role = $role;
     }
     public function setDon($don) {
         $this->don = $don;

     }
     public function getDon() {
         return $this->don;
     }
 }
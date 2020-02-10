<?php

/*
 * This file is part of the fulldon project
 *
 * (c) SAMI BOUSSACSOU <boussacsou@intersa.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fulldon\DonateurBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\DonRepository")
 * @ORM\Table(name="don", indexes={@ORM\Index(name="search_don_idx", columns={"id", "created_at", "montant", "mode_id", "lot", "cause_id", "ispa"}),
 * @ORM\Index(name="montant_don_idx", columns={"montant"}),
 * @ORM\Index(name="mode_don_idx", columns={"mode_id"}),
 * @ORM\Index(name="date_don_idx", columns={"created_at"}),
 * @ORM\Index(name="cause_id_don_idx", columns={"cause_id"}),
 * @ORM\Index(name="ispa_don_idx", columns={"ispa"}),
 * @ORM\Index(name="stat_one_don_idx", columns={"removed","created_at"}),
 * @ORM\Index(name="stat_two_don_idx", columns={"abo_id","ispa","removed"}),
 * @ORM\Index(name="stat_four_don_idx", columns={"type_id"}),
 * @ORM\Index(name="stat_mix_don_idx", columns={"type_id", "mode_id"})
 *
 * })
 */
class Don {

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="ModePaiement")
     * @ORM\JoinColumn(nullable=true, name="mode_id" )
     */
    protected $modePaiement;

    /**
     * @ORM\OneToMany(targetEntity="Rf", mappedBy="don" )
     */
    protected $rfs; //Array collection
    /**
     * @ORM\Column(name="montant", type="decimal", precision=10, scale=2)
     */
    protected $montant;

    /**
     * @ORM\Column(name="sequence", type="string", length=255, nullable=true)
     */
    protected $sequence;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;

    /**
     * @ORM\Column(name="date_fiscale", type="date", nullable=true)
     */
    protected $date_fiscale;

    /**
     * @ORM\Column(name="lot", type="string", length=255, nullable=true)
     */
    protected $lot;

    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\IntersaBundle\Entity\TypeDon")
     * @ORM\JoinColumn( name="type_id");
     */
    protected $type;

    /**
     * @ORM\OneToOne(targetEntity="Abonnement", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true,name="abo_id");
     */
    protected $abonnement;

    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\SecurityBundle\Entity\User")
     */
    protected $user;

    /**
     * @ORM\Column(name="isconfirmed", type="boolean" )
     */
    protected $isConfirmed;

    /**
     * @ORM\Column(name="removed", type="boolean" )
     */
    protected $removed;

    /**
     * @ORM\Column(name="removed_at", type="datetime", nullable=true)
     */
    protected $removedAt;

    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\MotifDisableDon")
     * @ORM\JoinColumn( name="motif_disable_don_id");
     */
    protected $motif;

    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\IntersaBundle\Entity\Eclat", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, name="eclat_id" )
     */
    protected $eclat;
    // END Removing informations
    /**
     * @ORM\ManyToOne(targetEntity="Transaction", cascade={"persist"})
     */
    protected $transaction;

    /**
     * @ORM\ManyToOne(targetEntity="Cause")
     * @ORM\JoinColumn(nullable=true, name="cause_id" )
     */
    protected $cause;

    /**
     * @ORM\Column(name="ispa", type="boolean" )
     */
    protected $ispa;

    /**
     * @ORM\Column(name="event_option",type="string", length=255, nullable=true)
     */
    protected $eventOption;

    /**
     * @ORM\Column(name="cust_prodon", type="integer", nullable=true)
     */
    protected $custProdon;

    /**
     * @ORM\ManyToMany(targetEntity="Event", inversedBy="dons")
     *
     */
    private $events;

    /**
     * @ORM\Column(name="order_ogone_id", type="string", length=255, nullable=true)
     */
    protected $OrderOgoneId;

    

    //est ce que c'est un don régulier ou pas
    public function __construct() {
        $this->createdAt = new \Datetime();
        $this->date_fiscale = $this->createdAt;
        $this->isConfirmed = false;
        $this->removed = false;
        $this->rfs = new ArrayCollection();
        $this->events = new ArrayCollection();
        
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set sequence
     *
     * @param string $sequence
     * @return Don
     */
    public function setSequence($sequence) {
        $this->sequence = $sequence;

        return $this;
    }

    /**
     * Get sequence
     *
     * @return string 
     */
    public function getSequence() {
        return $this->sequence;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Don
     */
    public function setCreatedAt($createdAt) {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt() {
        return $this->createdAt;
    }

    /**
     * Set lot
     *
     * @param string $lot
     * @return Don
     */
    public function setLot($lot) {
        $this->lot = $lot;

        return $this;
    }

    /**
     * Get lot
     *
     * @return string 
     */
    public function getLot() {
        return $this->lot;
    }

    /**
     * Set isConfirmed
     *
     * @param boolean $isConfirmed
     * @return Don
     */
    public function setIsConfirmed($isConfirmed) {
        $this->isConfirmed = $isConfirmed;

        return $this;
    }

    /**
     * Get isConfirmed
     *
     * @return boolean 
     */
    public function getIsConfirmed() {
        return $this->isConfirmed;
    }

    /**
     * Set removed
     *
     * @param boolean $removed
     * @return Don
     */
    public function setRemoved($removed) {
        $this->removed = $removed;

        return $this;
    }

    /**
     * Get removed
     *
     * @return boolean 
     */
    public function getRemoved() {
        return $this->removed;
    }

    /**
     * Set modePaiement
     *
     * @param \Fulldon\DonateurBundle\Entity\ModePaiement $modePaiement
     * @return Don
     */
    public function setModePaiement(\Fulldon\DonateurBundle\Entity\ModePaiement $modePaiement) {
        $this->modePaiement = $modePaiement;

        return $this;
    }

    /**
     * Get modePaiement
     *
     * @return \Fulldon\DonateurBundle\Entity\ModePaiement 
     */
    public function getModePaiement() {
        return $this->modePaiement;
    }

    /**
     * Set abonnement
     *
     * @param \Fulldon\DonateurBundle\Entity\Abonnement $abonnement
     * @return Don
     */
    public function setAbonnement(\Fulldon\DonateurBundle\Entity\Abonnement $abonnement = null) {
        $this->abonnement = $abonnement;

        return $this;
    }

    /**
     * Get abonnement
     *
     * @return \Fulldon\DonateurBundle\Entity\Abonnement 
     */
    public function getAbonnement() {
        return $this->abonnement;
    }

    

    /**
     * Set transaction
     *
     * @param \Fulldon\DonateurBundle\Entity\Transaction $transaction
     * @return Don
     */
    public function setTransaction(\Fulldon\DonateurBundle\Entity\Transaction $transaction = null) {
        $this->transaction = $transaction;

        return $this;
    }

    /**
     * Get transaction
     *
     * @return \Fulldon\DonateurBundle\Entity\Transaction 
     */
    public function getTransaction() {
        return $this->transaction;
    }

    /**
     * Set cause
     *
     * @param \Fulldon\DonateurBundle\Entity\Cause $cause
     * @return Don
     */
    public function setCause(\Fulldon\DonateurBundle\Entity\Cause $cause) {
        $this->cause = $cause;

        return $this;
    }

    /**
     * Get cause
     *
     * @return \Fulldon\DonateurBundle\Entity\Cause 
     */
    public function getCause() {
        return $this->cause;
    }

    /**
     * Set montant
     *
     * @param string $montant
     * @return Don
     */
    public function setMontant($montant) {
        $this->montant = $montant;

        return $this;
    }

    /**
     * Get montant
     *
     * @return string 
     */
    public function getMontant() {
        return $this->montant;
    }

    /**
     * Set ispa
     *
     * @param boolean $ispa
     * @return Don
     */
    public function setIspa($ispa) {
        $this->ispa = $ispa;

        return $this;
    }

    /**
     * Get ispa
     *
     * @return boolean 
     */
    public function getIspa() {
        return $this->ispa;
    }

    /**
     * Set motif
     *
     * @param \Fulldon\DonateurBundle\Entity\MotifDisableDon $motif
     * @return Don
     */
    public function setMotif(\Fulldon\DonateurBundle\Entity\MotifDisableDon $motif = null) {
        $this->motif = $motif;

        return $this;
    }

    /**
     * Get motif
     *
     * @return \Fulldon\DonateurBundle\Entity\MotifDisableDon 
     */
    public function getMotif() {
        return $this->motif;
    }

    /**
     * Set removedAt
     *
     * @param \DateTime $removedAt
     * @return Don
     */
    public function setRemovedAt($removedAt) {
        $this->removedAt = $removedAt;

        return $this;
    }

    /**
     * Get removedAt
     *
     * @return \DateTime 
     */
    public function getRemovedAt() {
        return $this->removedAt;
    }

    /**
     * Set date_fiscale
     *
     * @param \DateTime $dateFiscale
     * @return Don
     */
    public function setDateFiscale($dateFiscale) {
        $this->date_fiscale = $dateFiscale;

        return $this;
    }

    /**
     * Get date_fiscale
     *
     * @return \DateTime 
     */
    public function getDateFiscale() {
        return $this->date_fiscale;
    }

    /**
     * Set eclat
     *
     * @param \Fulldon\IntersaBundle\Entity\Eclat $eclat
     * @return Don
     */
    public function setEclat(\Fulldon\IntersaBundle\Entity\Eclat $eclat = null) {
        $this->eclat = $eclat;

        return $this;
    }

    /**
     * Get eclat
     *
     * @return \Fulldon\IntersaBundle\Entity\Eclat 
     */
    public function getEclat() {
        return $this->eclat;
    }

    /**
     * Add rfs
     *
     * @param \Fulldon\DonateurBundle\Entity\Rf $rfs
     * @return Don
     */
    public function addRf(\Fulldon\DonateurBundle\Entity\Rf $rfs) {
        $this->rfs[] = $rfs;

        return $this;
    }

    /**
     * Remove rfs
     *
     * @param \Fulldon\DonateurBundle\Entity\Rf $rfs
     */
    public function removeRf(\Fulldon\DonateurBundle\Entity\Rf $rfs) {
        $this->rfs->removeElement($rfs);
    }

    /**
     * Get rfs
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRfs() {
        return $this->rfs;
    }

    public function getRfByName($name) {
        foreach ($this->rfs as $rf) {
            if ($rf->getNom() == $name) {
                return $rf;
            }
        }
        return null;
    }

    /**
     * Set type
     *
     * @param \Fulldon\IntersaBundle\Entity\TypeDon $type
     * @return Don
     */
    public function setType(\Fulldon\IntersaBundle\Entity\TypeDon $type = null) {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return \Fulldon\IntersaBundle\Entity\TypeDon 
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Set custProdon
     *
     * @param integer $custProdon
     * @return Don
     */
    public function setCustProdon($custProdon) {
        $this->custProdon = $custProdon;

        return $this;
    }

    /**
     * Get custProdon
     *
     * @return integer 
     */
    public function getCustProdon() {
        return $this->custProdon;
    }

    /**
     * Add events
     *
     * @param \Fulldon\DonateurBundle\Entity\Event $events
     * @return Don
     */
    public function addEvent(\Fulldon\DonateurBundle\Entity\Event $events) {
        $this->events[] = $events;

        return $this;
    }

    /**
     * Remove events
     *
     * @param \Fulldon\DonateurBundle\Entity\Event $events
     */
    public function removeEvent(\Fulldon\DonateurBundle\Entity\Event $events) {
        $this->events->removeElement($events);
    }

    /**
     * Get events
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getEvents() {
        return $this->events;
    }

    /**
     * Set eventOption
     *
     * @param string $eventOption
     * @return Don
     */
    public function setEventOption($eventOption) {
        $this->eventOption = $eventOption;

        return $this;
    }

    /**
     * Get eventOption
     *
     * @return string 
     */
    public function getEventOption() {
        return $this->eventOption;
    }

    /**
     * Set orderOgoneId
     *
     * @param string $orderOgoneId
     *
     * @return Don
     */
    public function setOrderOgoneId($orderOgoneId) {
        $this->OrderOgoneId = $orderOgoneId;

        return $this;
    }

    /**
     * Get orderOgoneId
     *
     * @return string
     */
    public function getOrderOgoneId() {
        return $this->OrderOgoneId;
    }


    /**
     * Set user
     *
     * @param \Fulldon\SecurityBundle\Entity\User $user
     *
     * @return Don
     */
    public function setUser(\Fulldon\SecurityBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Fulldon\SecurityBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }
}

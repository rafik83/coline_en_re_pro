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
use Symfony\Component\Security\Core\Role\RoleInterface;
use Doctrine\Common\Collections\ArrayCollection;
/**
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\PrelevementRepository")
 * @ORM\Table(name="prelevement", indexes={@ORM\Index(name="id_prelevement_idx", columns={"id"}),
 * @ORM\Index(name="rf_prelevement_idx", columns={"date_prelevement", "rejet"}),
 * @ORM\Index(name="stat_prelevement_idx", columns={"abo_id", "rejet","date_prelevement"}),
 * })
 */
class Prelevement {
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\Abonnement")
     * @ORM\JoinColumn( name="abo_id");
     */
    private $abo;
    /**
     * @ORM\Column(name="montant", type="decimal", length=255)
     */
    private $montant;
    /**
     * @ORM\Column(name="date", type="date" )
     */
    private $date;
    /**
     * @ORM\Column(name="date_prelevement", type="date" )
     */
    private $datePrelevement;
    /**
     * @ORM\Column(name="rejet", type="boolean" )
     */
    private $rejet;
    /**
     * @ORM\Column(name="rejected_at", type="datetime", nullable=true)
     */
    protected $rejectedAt;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\MotifRejetPrelevement")
     * @ORM\JoinColumn( name="motif_rejet_id", nullable=true);
     */
    protected $motif;
    /**
     * @ORM\Column(name="rum", type="string", nullable=false)
     */
    protected $rum;

    public function __construct()
    {
        $this->date = new \Datetime();
        $this->rejet = false;
    }
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set montant
     *
     * @param string $montant
     * @return Prelevement
     */
    public function setMontant($montant)
    {
        $this->montant = $montant;

        return $this;
    }

    /**
     * Get montant
     *
     * @return string 
     */
    public function getMontant()
    {
        return $this->montant;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return Prelevement
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set rejet
     *
     * @param boolean $rejet
     * @return Prelevement
     */
    public function setRejet($rejet)
    {
        $this->rejet = $rejet;

        return $this;
    }

    /**
     * Get rejet
     *
     * @return boolean 
     */
    public function getRejet()
    {
        return $this->rejet;
    }



    /**
     * Set abo
     *
     * @param \Fulldon\DonateurBundle\Entity\Abonnement $abo
     * @return Prelevement
     */
    public function setAbo(\Fulldon\DonateurBundle\Entity\Abonnement $abo = null)
    {
        $this->abo = $abo;

        return $this;
    }

    /**
     * Get abo
     *
     * @return \Fulldon\DonateurBundle\Entity\Abonnement 
     */
    public function getAbo()
    {
        return $this->abo;
    }

    /**
     * Set datePrelevement
     *
     * @param \DateTime $datePrelevement
     * @return Prelevement
     */
    public function setDatePrelevement($datePrelevement)
    {
        $this->datePrelevement = $datePrelevement;

        return $this;
    }

    /**
     * Get datePrelevement
     *
     * @return \DateTime 
     */
    public function getDatePrelevement()
    {
        return $this->datePrelevement;
    }

    /**
     * Set motif
     *
     * @param \Fulldon\DonateurBundle\Entity\MotifRejetPrelevement $motif
     * @return Prelevement
     */
    public function setMotif(\Fulldon\DonateurBundle\Entity\MotifRejetPrelevement $motif = null)
    {
        $this->motif = $motif;

        return $this;
    }

    /**
     * Get motif
     *
     * @return \Fulldon\DonateurBundle\Entity\MotifRejetPrelevement 
     */
    public function getMotif()
    {
        return $this->motif;
    }

    /**
     * Set rejectedAt
     *
     * @param \DateTime $rejectedAt
     * @return Prelevement
     */
    public function setRejectedAt($rejectedAt)
    {
        $this->rejectedAt = $rejectedAt;

        return $this;
    }

    /**
     * Get rejectedAt
     *
     * @return \DateTime 
     */
    public function getRejectedAt()
    {
        return $this->rejectedAt;
    }

    /**
     * Set rum
     *
     * @param string $rum
     * @return Prelevement
     */
    public function setRum($rum)
    {
        $this->rum = $rum;

        return $this;
    }

    /**
     * Get rum
     *
     * @return string 
     */
    public function getRum()
    {
        return $this->rum;
    }
}

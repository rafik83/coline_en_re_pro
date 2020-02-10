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
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\HdesAbonnementRepository")
 * @ORM\Table(name="historique_desactivation_abonnement")
 */
class HdesAbonnement
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="actif", type="boolean", nullable=true)
     */
    private $actif;
    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\MotifAbo")
     * @ORM\JoinColumn( name="motif_abo_id", nullable=true);
     */
    protected $motif;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\Abonnement")
     * @ORM\JoinColumn( name="abo_id", nullable=true);
     */
    protected $abo;

    public function __construct()
    {
        $this->createdAt = new \Datetime();
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
     * Set actif
     *
     * @param boolean $actif
     * @return HdesAbonnement
     */
    public function setActif($actif)
    {
        $this->actif = $actif;

        return $this;
    }

    /**
     * Get actif
     *
     * @return boolean 
     */
    public function getActif()
    {
        return $this->actif;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return HdesAbonnement
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set motif
     *
     * @param \Fulldon\DonateurBundle\Entity\MotifAbo $motif
     * @return HdesAbonnement
     */
    public function setMotif(\Fulldon\DonateurBundle\Entity\MotifAbo $motif = null)
    {
        $this->motif = $motif;

        return $this;
    }

    /**
     * Get motif
     *
     * @return \Fulldon\DonateurBundle\Entity\MotifAbo 
     */
    public function getMotif()
    {
        return $this->motif;
    }

    /**
     * Set abo
     *
     * @param \Fulldon\DonateurBundle\Entity\Abonnement $abo
     * @return HdesAbonnement
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
}

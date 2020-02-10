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
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\ProspectionRepository")
 * @ORM\Table(name="prospection")
 */
class Prospection
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\Donateur", cascade={"remove"})
     * @ORM\JoinColumn( name="donateur_id", nullable=true, onDelete="CASCADE");
     */
    protected $donateur;
    /**
     * @ORM\ManyToOne(targetEntity="Cause")
     * @ORM\JoinColumn(nullable=true, name="cause_id" )
     */
    protected $cause;
    /**
     * @ORM\ManyToMany(targetEntity="ReceptionMode", inversedBy="prospections")
     * @ORM\JoinColumn(nullable=false, name="reception_id");
     */
    private $receptions;
    /**
     * @ORM\Column(name="retour",type="boolean")
     */
    protected $retour;



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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Prospection
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
     * Set retour
     *
     * @param boolean $retour
     * @return Prospection
     */
    public function setRetour($retour)
    {
        $this->retour = $retour;

        return $this;
    }

    /**
     * Get retour
     *
     * @return boolean 
     */
    public function getRetour()
    {
        return $this->retour;
    }

    /**
     * Set donateur
     *
     * @param \Fulldon\DonateurBundle\Entity\Donateur $donateur
     * @return Prospection
     */
    public function setDonateur(\Fulldon\DonateurBundle\Entity\Donateur $donateur = null)
    {
        $this->donateur = $donateur;

        return $this;
    }

    /**
     * Get donateur
     *
     * @return \Fulldon\DonateurBundle\Entity\Donateur 
     */
    public function getDonateur()
    {
        return $this->donateur;
    }

    /**
     * Set cause
     *
     * @param \Fulldon\DonateurBundle\Entity\Cause $cause
     * @return Prospection
     */
    public function setCause(\Fulldon\DonateurBundle\Entity\Cause $cause = null)
    {
        $this->cause = $cause;

        return $this;
    }

    /**
     * Get cause
     *
     * @return \Fulldon\DonateurBundle\Entity\Cause 
     */
    public function getCause()
    {
        return $this->cause;
    }


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->receptions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add receptions
     *
     * @param \Fulldon\DonateurBundle\Entity\ReceptionMode $receptions
     * @return Prospection
     */
    public function addReception(\Fulldon\DonateurBundle\Entity\ReceptionMode $receptions)
    {
        $this->receptions[] = $receptions;

        return $this;
    }

    /**
     * Remove receptions
     *
     * @param \Fulldon\DonateurBundle\Entity\ReceptionMode $receptions
     */
    public function removeReception(\Fulldon\DonateurBundle\Entity\ReceptionMode $receptions)
    {
        $this->receptions->removeElement($receptions);
    }

    /**
     * Get receptions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getReceptions()
    {
        return $this->receptions;
    }
}

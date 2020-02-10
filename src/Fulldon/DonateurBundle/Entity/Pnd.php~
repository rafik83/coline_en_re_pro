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
 * @ORM\Table(name="pnd")
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\PndRepository")
 */
class Pnd
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="motif_desc", type="text", nullable=true)
     */
    private $motifDesc;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\Donateur")
     * @ORM\JoinColumn( name="donateur_id");
     */
    protected $donateur;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\MotifPnd")
     * @ORM\JoinColumn( name="motif_id");
     */
    protected $motif;
    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected $createdAt;
    /**
     * @ORM\Column(name="removed", type="boolean" )
     */
    protected $removed;

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
     * Set motifDesc
     *
     * @param string $motifDesc
     * @return Pnd
     */
    public function setMotifDesc($motifDesc)
    {
        $this->motifDesc = $motifDesc;

        return $this;
    }

    /**
     * Get motifDesc
     *
     * @return string 
     */
    public function getMotifDesc()
    {
        return $this->motifDesc;
    }

    /**
     * Set donateur
     *
     * @param \Fulldon\DonateurBundle\Entity\Donateur $donateur
     * @return Pnd
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
     * Set motif
     *
     * @param \Fulldon\DonateurBundle\Entity\MotifPnd $motif
     * @return Pnd
     */
    public function setMotif(\Fulldon\DonateurBundle\Entity\MotifPnd $motif = null)
    {
        $this->motif = $motif;

        return $this;
    }

    /**
     * Get motif
     *
     * @return \Fulldon\DonateurBundle\Entity\MotifPnd 
     */
    public function getMotif()
    {
        return $this->motif;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Pnd
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
     * Set removed
     *
     * @param boolean $removed
     * @return Pnd
     */
    public function setRemoved($removed)
    {
        $this->removed = $removed;

        return $this;
    }

    /**
     * Get removed
     *
     * @return boolean 
     */
    public function getRemoved()
    {
        return $this->removed;
    }
}

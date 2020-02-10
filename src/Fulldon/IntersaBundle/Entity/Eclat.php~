<?php
/*
 * This file is part of the fulldon project
 *
 * (c) SAMI BOUSSACSOU <boussacsou@intersa.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fulldon\IntersaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Table(name="eclat")
 * @ORM\Entity(repositoryClass="Fulldon\IntersaBundle\Entity\EclatRepository")
 */
class Eclat
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;
    /**
     * @ORM\OneToOne(targetEntity="Fulldon\DonateurBundle\Entity\Rf", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, name="rf_id" )
     */
    protected $rfEclat;

    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\Donateur")
     * @ORM\JoinColumn( name="donateur_id");
     */
    protected $donateur;
    
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Eclat
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
     * Set rfEclat
     *
     * @param \Fulldon\DonateurBundle\Entity\Rf $rfEclat
     * @return Eclat
     */
    public function setRfEclat(\Fulldon\DonateurBundle\Entity\Rf $rfEclat = null)
    {
        $this->rfEclat = $rfEclat;

        return $this;
    }

    /**
     * Get rfEclat
     *
     * @return \Fulldon\DonateurBundle\Entity\Rf 
     */
    public function getRfEclat()
    {
        return $this->rfEclat;
    }

    /**
     * Set donateur
     *
     * @param \Fulldon\DonateurBundle\Entity\Donateur $donateur
     * @return Eclat
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
}

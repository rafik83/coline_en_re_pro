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
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="courrier_traitement")
 * @ORM\Entity(repositoryClass="Fulldon\IntersaBundle\Entity\CourrierTraitementRepository")
 */
class CourrierTraitement
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="lot", type="string", length=255)
     */
    private $lot;
    /**
     * @ORM\Column(name="sequence", type="integer", length=5)
     */
    private $sequence;
    /**
     * @ORM\ManyToMany(targetEntity="Fulldon\IntersaBundle\Entity\TypeTraitementCourrier" , cascade={"persist"})
     */
    protected $typeTraitements;
    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\Donateur")
     * @ORM\JoinColumn( name="donateur_id");
     */
    protected $donateur;
    /**
     * @ORM\Column(name="done", type="boolean")
     */
    protected $done;



    public function __construct()
    {
        $this->createdAt = new \Datetime();
        $this->typeTraitements = new ArrayCollection();

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
     * Set lot
     *
     * @param string $lot
     * @return CourrierTraitement
     */
    public function setLot($lot)
    {
        $this->lot = $lot;

        return $this;
    }

    /**
     * Get lot
     *
     * @return string 
     */
    public function getLot()
    {
        return $this->lot;
    }

    /**
     * Set sequence
     *
     * @param integer $sequence
     * @return CourrierTraitement
     */
    public function setSequence($sequence)
    {
        $this->sequence = $sequence;

        return $this;
    }

    /**
     * Get sequence
     *
     * @return integer 
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return CourrierTraitement
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
     * Set done
     *
     * @param boolean $done
     * @return CourrierTraitement
     */
    public function setDone($done)
    {
        $this->done = $done;

        return $this;
    }

    /**
     * Get done
     *
     * @return boolean 
     */
    public function getDone()
    {
        return $this->done;
    }

    /**
     * Add typeTraitements
     *
     * @param \Fulldon\IntersaBundle\Entity\TypeTraitementCourrier $typeTraitements
     * @return CourrierTraitement
     */
    public function addTypeTraitement(\Fulldon\IntersaBundle\Entity\TypeTraitementCourrier $typeTraitements)
    {
        $this->typeTraitements[] = $typeTraitements;

        return $this;
    }

    /**
     * Remove typeTraitements
     *
     * @param \Fulldon\IntersaBundle\Entity\TypeTraitementCourrier $typeTraitements
     */
    public function removeTypeTraitement(\Fulldon\IntersaBundle\Entity\TypeTraitementCourrier $typeTraitements)
    {
        $this->typeTraitements->removeElement($typeTraitements);
    }

    /**
     * Get typeTraitements
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTypeTraitements()
    {
        return $this->typeTraitements;
    }

    /**
     * Set donateur
     *
     * @param \Fulldon\DonateurBundle\Entity\Donateur $donateur
     * @return CourrierTraitement
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

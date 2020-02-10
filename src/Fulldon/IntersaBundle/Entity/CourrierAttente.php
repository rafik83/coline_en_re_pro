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
 * @ORM\Table(name="courrier_attente")
 * @ORM\Entity(repositoryClass="Fulldon\IntersaBundle\Entity\CourrierAttenteRepository")
 */
class CourrierAttente
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\IntersaBundle\Entity\TypeTraitementCourrier")
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
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\Don")
     * @ORM\JoinColumn( name="don_id", nullable=true);
     */
    protected $don;
    /**
     * @ORM\Column(name="done_at", type="datetime", nullable=true)
     */
    protected $doneAt;
    /**
     * @ORM\Column(name="done", type="boolean",options={"default":true})
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return CourrierAttente
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
     * Set doneAt
     *
     * @param \DateTime $doneAt
     * @return CourrierAttente
     */
    public function setDoneAt($doneAt)
    {
        $this->doneAt = $doneAt;

        return $this;
    }

    /**
     * Get doneAt
     *
     * @return \DateTime 
     */
    public function getDoneAt()
    {
        return $this->doneAt;
    }

    /**
     * Set done
     *
     * @param boolean $done
     * @return CourrierAttente
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
     * Set typeTraitements
     *
     * @param \Fulldon\IntersaBundle\Entity\TypeTraitementCourrier $typeTraitements
     * @return CourrierAttente
     */
    public function setTypeTraitements(\Fulldon\IntersaBundle\Entity\TypeTraitementCourrier $typeTraitements = null)
    {
        $this->typeTraitements = $typeTraitements;

        return $this;
    }

    /**
     * Get typeTraitements
     *
     * @return \Fulldon\IntersaBundle\Entity\TypeTraitementCourrier 
     */
    public function getTypeTraitements()
    {
        return $this->typeTraitements;
    }

    /**
     * Set donateur
     *
     * @param \Fulldon\DonateurBundle\Entity\Donateur $donateur
     * @return CourrierAttente
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
     * Set don
     *
     * @param \Fulldon\DonateurBundle\Entity\Don $don
     * @return CourrierAttente
     */
    public function setDon(\Fulldon\DonateurBundle\Entity\Don $don = null)
    {
        $this->don = $don;

        return $this;
    }

    /**
     * Get don
     *
     * @return \Fulldon\DonateurBundle\Entity\Donateur 
     */
    public function getDon()
    {
        return $this->don;
    }
}

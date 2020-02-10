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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\EventRepository")
 * @ORM\Table(name="event",indexes={@ORM\Index(name="id_event_idx", columns={"id"}),
 * })
 *
 */
class Event
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected  $id;
    /**
     * @ORM\Column(name="titre", type="string", length=255)
     */
    protected $titre;
    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;
    /**
     * @ORM\Column(name="prix_adh", type="decimal", precision=10, scale=2)
     */
    protected $prixAdh;
    /**
     * @ORM\Column(name="prix_nonadh", type="decimal", precision=10, scale=2)
     */
    protected $prixNonAdh;
    /**
     * @ORM\Column(name="date_event", type="date", length=255)
     */
    protected $dateEvent;
    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;
    /**
     * @ORM\Column("actif", type="boolean")
     */
    protected $actif;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\Cause")
     * @ORM\JoinColumn( name="code_activite", nullable=true);
     */
    protected $cause;

    /**
     * @ORM\ManyToMany(targetEntity="Don", mappedBy="events")
     *
     */
    private $dons;


    public function __construct()
    {
        $this->createdAt = new \Datetime();
        $this->dons = new ArrayCollection();
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
     * Set titre
     *
     * @param string $titre
     * @return Event
     */
    public function setTitre($titre)
    {
        $this->titre = $titre;

        return $this;
    }

    /**
     * Get titre
     *
     * @return string 
     */
    public function getTitre()
    {
        return $this->titre;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Event
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set prixAdh
     *
     * @param string $prixAdh
     * @return Event
     */
    public function setPrixAdh($prixAdh)
    {
        $this->prixAdh = $prixAdh;

        return $this;
    }

    /**
     * Get prixAdh
     *
     * @return string 
     */
    public function getPrixAdh()
    {
        return $this->prixAdh;
    }

    /**
     * Set prixNonAdh
     *
     * @param string $prixNonAdh
     * @return Event
     */
    public function setPrixNonAdh($prixNonAdh)
    {
        $this->prixNonAdh = $prixNonAdh;

        return $this;
    }

    /**
     * Get prixNonAdh
     *
     * @return string 
     */
    public function getPrixNonAdh()
    {
        return $this->prixNonAdh;
    }

    /**
     * Set dateEvent
     *
     * @param \DateTime $dateEvent
     * @return Event
     */
    public function setDateEvent($dateEvent)
    {
        $this->dateEvent = $dateEvent;

        return $this;
    }

    /**
     * Get dateEvent
     *
     * @return \DateTime 
     */
    public function getDateEvent()
    {
        return $this->dateEvent;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Event
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
     * Set actif
     *
     * @param boolean $actif
     * @return Event
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
     * Set cause
     *
     * @param \Fulldon\DonateurBundle\Entity\Cause $cause
     * @return Event
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
     * Add dons
     *
     * @param \Fulldon\DonateurBundle\Entity\Don $dons
     * @return Event
     */
    public function addDon(\Fulldon\DonateurBundle\Entity\Don $dons)
    {
        $this->dons[] = $dons;

        return $this;
    }

    /**
     * Remove dons
     *
     * @param \Fulldon\DonateurBundle\Entity\Don $dons
     */
    public function removeDon(\Fulldon\DonateurBundle\Entity\Don $dons)
    {
        $this->dons->removeElement($dons);
    }

    /**
     * Get dons
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDons()
    {
        return $this->dons;
    }
}

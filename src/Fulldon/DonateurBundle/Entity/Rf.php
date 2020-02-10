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

/**
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\RfRepository")
 * @ORM\Table(name="rf")
 */
class Rf
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\Column(name="nom", type="string", length=255)
     */
    private $nom;
    /**
     * @ORM\Column(name="nom_duplicata", type="string", length=255, nullable=true)
     */
    private $nomDuplicata;
    /**
     * @ORM\Column(name="sended", type="boolean")
     */
    private $sended;

    /**
     * @ORM\Column(name="createAt", type="date")
     */
    private $createdAt;
    /**
     * @ORM\ManyToOne(targetEntity="Don", inversedBy="rfs")
     * @ORM\JoinColumn(name="don_id", referencedColumnName="id")
     */
    protected $don;

    public function __construct()
    {
        $this->createdAt = new \Datetime();
        $this->sended = false;
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
     * Set nom
     *
     * @param string $nom
     * @return Rf
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom
     *
     * @return string 
     */
    public function getNom()
    {
        return $this->nom;
    }


    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Rf
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
     * Set sended
     *
     * @param boolean $sended
     * @return Rf
     */
    public function setSended($sended)
    {
        $this->sended = $sended;

        return $this;
    }

    /**
     * Get sended
     *
     * @return boolean 
     */
    public function getSended()
    {
        return $this->sended;
    }

    /**
     * Set don
     *
     * @param \Fulldon\DonateurBundle\Entity\Don $don
     * @return Rf
     */
    public function setDon(\Fulldon\DonateurBundle\Entity\Don $don = null)
    {
        $this->don = $don;

        return $this;
    }

    /**
     * Get don
     *
     * @return \Fulldon\DonateurBundle\Entity\Don 
     */
    public function getDon()
    {
        return $this->don;
    }


    /**
     * Set nomDuplicata
     *
     * @param string $nomDuplicata
     * @return Rf
     */
    public function setNomDuplicata($nomDuplicata)
    {
        $this->nomDuplicata = $nomDuplicata;

        return $this;
    }

    /**
     * Get nomDuplicata
     *
     * @return string 
     */
    public function getNomDuplicata()
    {
        return $this->nomDuplicata;
    }
}

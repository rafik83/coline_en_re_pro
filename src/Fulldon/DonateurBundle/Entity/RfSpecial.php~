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
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\RfSpecialRepository")
 * @ORM\Table(name="rf_special")
 */
class RfSpecial
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
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $url;
    /**
     * @ORM\Column(name="url", type="datetime")
     */
    private $createdAt;
    /**
     * @ORM\Column(name="duplicata", type="boolean" )
     */
    private $duplicata;


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
     * @return RfSpecial
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
     * Set url
     *
     * @param \DateTime $url
     * @return RfSpecial
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return \DateTime 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return RfSpecial
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
     * Set duplicata
     *
     * @param boolean $duplicata
     * @return RfSpecial
     */
    public function setDuplicata($duplicata)
    {
        $this->duplicata = $duplicata;

        return $this;
    }

    /**
     * Get duplicata
     *
     * @return boolean 
     */
    public function getDuplicata()
    {
        return $this->duplicata;
    }
}

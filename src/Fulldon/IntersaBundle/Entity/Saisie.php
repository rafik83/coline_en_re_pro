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
use Symfony\Component\Security\Core\Role\RoleInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Table(name="saisie",indexes={@index(name="search_saisie_index", columns={"lot"})})
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Fulldon\IntersaBundle\Entity\SaisieRepository")
 * @UniqueEntity(fields={"lot"}, message="Le nom login doit être unique")
 */
class Saisie
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
     * @ORM\Column(name="type", type="string", length=255)
     */
    private $type;
    /**
     * @ORM\Column(name="sequence", type="integer", length=5)
     */
    private $sequence;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\SecurityBundle\Entity\User")
     * @ORM\JoinColumn( name="user_id");
     */
    protected $user;
    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;
    /**
     * @ORM\Column(name="done", type="boolean" )
     */
    protected $done;
    /**
     * @ORM\Column(name="rf_done", type="boolean")
     */
    protected $rfDone;
    // pour la vérification des lots
    /**
     * @ORM\Column(name="verif_done", type="boolean")
     */
    protected $verifDone;

    //est ce que c'est un don régulier ou pas
    public function __construct()
    {
        $this->createdAt = new \Datetime();
        $this->rfDone = false;
        $this->verifDone =false;
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
     * @return Saisie
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
     * @param \number $sequence
     * @return Saisie
     */
    public function setSequence( $sequence)
    {
        $this->sequence = $sequence;

        return $this;
    }

    /**
     * Get sequence
     *
     * @return \number 
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Saisie
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
     * Set user
     *
     * @param \Fulldon\SecurityBundle\Entity\User $user
     * @return Saisie
     */
    public function setUser(\Fulldon\SecurityBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Fulldon\SecurityBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set done
     *
     * @param boolean $done
     * @return Saisie
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
     * Set type
     *
     * @param string $type
     * @return Saisie
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set rfDone
     *
     * @param boolean $rfDone
     * @return Saisie
     */
    public function setRfDone($rfDone)
    {
        $this->rfDone = $rfDone;

        return $this;
    }

    /**
     * Get rfDone
     *
     * @return boolean 
     */
    public function getRfDone()
    {
        return $this->rfDone;
    }

    /**
     * Set verifDone
     *
     * @param boolean $verifDone
     * @return Saisie
     */
    public function setVerifDone($verifDone)
    {
        $this->verifDone = $verifDone;

        return $this;
    }

    /**
     * Get verifDone
     *
     * @return boolean 
     */
    public function getVerifDone()
    {
        return $this->verifDone;
    }
}

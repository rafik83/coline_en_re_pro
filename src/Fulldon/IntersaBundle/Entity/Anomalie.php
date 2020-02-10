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
 * @ORM\Table(name="anomalie",indexes={@index(name="search_saisie_index", columns={"lot"})})
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Fulldon\IntersaBundle\Entity\AnomalieRepository")
 */
class Anomalie
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
     * @ORM\ManyToOne(targetEntity="Fulldon\SecurityBundle\Entity\User")
     * @ORM\JoinColumn( name="user_id");
     */
    protected $user;
    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;
    /**
     * @ORM\Column(name="commentaire", type="text")
     */
    protected $commentaire;
    /**
     * @ORM\Column(name="corriger", type="boolean")
     */
    protected $corriger;
    /**
     * @ORM\Column(name="type", type="string", length=255)
     */
    protected $type;
    // type de saisie CB OU CS ...

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
     * Set lot
     *
     * @param string $lot
     * @return Anomalie
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
     * @return Anomalie
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
     * @return Anomalie
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
     * Set commentaire
     *
     * @param string $commentaire
     * @return Anomalie
     */
    public function setCommentaire($commentaire)
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    /**
     * Get commentaire
     *
     * @return string 
     */
    public function getCommentaire()
    {
        return $this->commentaire;
    }

    /**
     * Set corriger
     *
     * @param boolean $corriger
     * @return Anomalie
     */
    public function setCorriger($corriger)
    {
        $this->corriger = $corriger;

        return $this;
    }

    /**
     * Get corriger
     *
     * @return boolean 
     */
    public function getCorriger()
    {
        return $this->corriger;
    }

    /**
     * Set user
     *
     * @param \Fulldon\SecurityBundle\Entity\User $user
     * @return Anomalie
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
     * Set type
     *
     * @param string $type
     * @return Anomalie
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
}

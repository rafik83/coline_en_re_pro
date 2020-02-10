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
 * @ORM\Table(name="log")
 * @ORM\Entity(repositoryClass="Fulldon\IntersaBundle\Entity\LogRepository")
 */
class Log
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\Donateur", cascade={"remove"})
     * @ORM\JoinColumn( name="donateur_id", nullable=true, onDelete="CASCADE");
     */
    protected $donateur;

    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\Don", cascade={"remove"})
     * @ORM\JoinColumn( name="don_id", nullable=true, onDelete="CASCADE");
     */
    protected $don;

    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\SecurityBundle\Entity\User")
     * @ORM\JoinColumn( name="user_id", nullable=true);
     */
    protected $user;
    /**
     * @ORM\Column(name="role", length=255,type="string", nullable=true)
     */
    protected $role;

    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\IntersaBundle\Entity\TypeLog")
     * @ORM\JoinColumn( name="typelog_id");
     */
    protected $typeLog;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;
    /**
     * @ORM\Column(name="desc_action",type="text")
     */
    protected $descAction;




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
     * @return Log
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
     * Set donateur
     *
     * @param \Fulldon\SecurityBundle\Entity\Donateur $donateur
     * @return Log
     */
    public function setDonateur(\Fulldon\DonateurBundle\Entity\Donateur $donateur = null)
    {
        $this->donateur = $donateur;

        return $this;
    }

    /**
     * Get donateur
     *
     * @return \Fulldon\SecurityBundle\Entity\Donateur 
     */
    public function getDonateur()
    {
        return $this->donateur;
    }

    /**
     * Set user
     *
     * @param \Fulldon\SecurityBundle\Entity\User $user
     * @return Log
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
     * Set typeLog
     *
     * @param \Fulldon\IntersaBundle\Entity\TypeLog $typeLog
     * @return Log
     */
    public function setTypeLog(\Fulldon\IntersaBundle\Entity\TypeLog $typeLog = null)
    {
        $this->typeLog = $typeLog;

        return $this;
    }

    /**
     * Get typeLog
     *
     * @return \Fulldon\IntersaBundle\Entity\TypeLog 
     */
    public function getTypeLog()
    {
        return $this->typeLog;
    }

    /**
     * Set descAction
     *
     * @param string $descAction
     * @return Log
     */
    public function setDescAction($descAction)
    {
        $this->descAction = $descAction;

        return $this;
    }

    /**
     * Get descAction
     *
     * @return string 
     */
    public function getDescAction()
    {
        return $this->descAction;
    }

    /**
     * Set role
     *
     * @param string $role
     *
     * @return Log
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set don
     *
     * @param \Fulldon\DonateurBundle\Entity\Don $don
     *
     * @return Log
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
}

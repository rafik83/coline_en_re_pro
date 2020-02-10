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
 * @ORM\Table(name="temps_saisie")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Fulldon\IntersaBundle\Entity\TempsSaisieRepository")
 */
class TimeSaisie
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\IntersaBundle\Entity\Saisie")
     * @ORM\JoinColumn( name="saisie_id");
     */
    protected $saisie;
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
     * @ORM\Column(name="temps", type="decimal", scale=2, length=5 )
     */
    protected $temps;


    //est ce que c'est un don régulier ou pas
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
     * Set sequence
     *
     * @param integer $sequence
     * @return TimeSaisie
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
     * @return TimeSaisie
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
     * Set temps
     *
     * @param string $temps
     * @return TimeSaisie
     */
    public function setTemps($temps)
    {
        $this->temps = $temps;

        return $this;
    }

    /**
     * Get temps
     *
     * @return string 
     */
    public function getTemps()
    {
        return $this->temps;
    }

    /**
     * Set saisie
     *
     * @param \Fulldon\IntersaBundle\Entity\Saisie $saisie
     * @return TimeSaisie
     */
    public function setSaisie(\Fulldon\IntersaBundle\Entity\Saisie $saisie = null)
    {
        $this->saisie = $saisie;

        return $this;
    }

    /**
     * Get saisie
     *
     * @return \Fulldon\IntersaBundle\Entity\Saisie 
     */
    public function getSaisie()
    {
        return $this->saisie;
    }

    /**
     * Set user
     *
     * @param \Fulldon\SecurityBundle\Entity\User $user
     * @return TimeSaisie
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
}

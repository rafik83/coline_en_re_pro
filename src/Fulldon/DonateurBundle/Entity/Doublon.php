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
use Symfony\Component\Security\Core\Role\RoleInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Table(name="doublon",indexes={@index(name="doublon_index", columns={"id"})})
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\DoublonRepository")
 */
class Doublon
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="donateur1", type="integer", length=255)
     */
    private $donateur1;
    /**
     * @ORM\Column(name="donateur2", type="integer", length=5)
     */
    private $donateur2;
    /**
     * @ORM\Column(name="pourcentage", type="integer", length=5)
     */
    private $pourcentage;
    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;
    /**
     * @ORM\Column(name="done", type="boolean" )
     */
    protected $done;

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
     * Set donateur1
     *
     * @param integer $donateur1
     * @return Doublon
     */
    public function setDonateur1($donateur1)
    {
        $this->donateur1 = $donateur1;

        return $this;
    }

    /**
     * Get donateur1
     *
     * @return integer 
     */
    public function getDonateur1()
    {
        return $this->donateur1;
    }

    /**
     * Set donateur2
     *
     * @param integer $donateur2
     * @return Doublon
     */
    public function setDonateur2($donateur2)
    {
        $this->donateur2 = $donateur2;

        return $this;
    }

    /**
     * Get donateur2
     *
     * @return integer 
     */
    public function getDonateur2()
    {
        return $this->donateur2;
    }

    /**
     * Set pourcentage
     *
     * @param integer $pourcentage
     * @return Doublon
     */
    public function setPourcentage($pourcentage)
    {
        $this->pourcentage = $pourcentage;

        return $this;
    }

    /**
     * Get pourcentage
     *
     * @return integer 
     */
    public function getPourcentage()
    {
        return $this->pourcentage;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Doublon
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
     * @return Doublon
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
}

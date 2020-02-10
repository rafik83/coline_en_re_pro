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
 * @ORM\Table(name="account")
 * @ORM\Entity(repositoryClass="Fulldon\IntersaBundle\Entity\AccountRepository")
 */
class Account
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="secret_code", type="string", length=255)
     */
    private $secretCode;
    /**
     * @ORM\Column(name="used", type="boolean")
     */
    private $used;
    /**
     * @ORM\Column(name="level", type="integer")
     */
    private $level;
    /**
     * @ORM\Column(name="sub_domain", type="string", length=255, unique=true)
     *
     */
    private $subDomain;

    /**
     * @ORM\Column(name="custom_domain", type="string", length=255, unique=true)
     *
     */
    private $customDomain;
    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;

    /**
     * @ORM\Column(name="adresse_fact", type="string", length=255, unique=true)
     *
     */
    private $adresseFct;

    /**
     * @ORM\Column(name="zipcode", type="string", length=255, unique=true)
     *
     */
    private $zipcode;

    /**
     * @ORM\Column(name="ville", type="string", length=255, unique=true)
     *
     */
    private $ville;

    /**
     * @ORM\Column(name="pays", type="string", length=255, unique=true)
     *
     */
    private $pays;

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
     * Set secretCode
     *
     * @param string $secretCode
     * @return Account
     */
    public function setSecretCode($secretCode)
    {
        $this->secretCode = $secretCode;

        return $this;
    }

    /**
     * Get secretCode
     *
     * @return string 
     */
    public function getSecretCode()
    {
        return $this->secretCode;
    }

    /**
     * Set used
     *
     * @param boolean $used
     * @return Account
     */
    public function setUsed($used)
    {
        $this->used = $used;

        return $this;
    }

    /**
     * Get used
     *
     * @return boolean 
     */
    public function getUsed()
    {
        return $this->used;
    }

    /**
     * Set level
     *
     * @param integer $level
     * @return Account
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level
     *
     * @return integer 
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set subDomain
     *
     * @param string $subDomain
     * @return Account
     */
    public function setSubDomain($subDomain)
    {
        $this->subDomain = $subDomain;

        return $this;
    }

    /**
     * Get subDomain
     *
     * @return string 
     */
    public function getSubDomain()
    {
        return $this->subDomain;
    }

    /**
     * Set customDomain
     *
     * @param string $customDomain
     * @return Account
     */
    public function setCustomDomain($customDomain)
    {
        $this->customDomain = $customDomain;

        return $this;
    }

    /**
     * Get customDomain
     *
     * @return string 
     */
    public function getCustomDomain()
    {
        return $this->customDomain;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Account
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
     * Set adresseFct
     *
     * @param string $adresseFct
     * @return Account
     */
    public function setAdresseFct($adresseFct)
    {
        $this->adresseFct = $adresseFct;

        return $this;
    }

    /**
     * Get adresseFct
     *
     * @return string 
     */
    public function getAdresseFct()
    {
        return $this->adresseFct;
    }

    /**
     * Set zipcode
     *
     * @param string $zipcode
     * @return Account
     */
    public function setZipcode($zipcode)
    {
        $this->zipcode = $zipcode;

        return $this;
    }

    /**
     * Get zipcode
     *
     * @return string 
     */
    public function getZipcode()
    {
        return $this->zipcode;
    }

    /**
     * Set ville
     *
     * @param string $ville
     * @return Account
     */
    public function setVille($ville)
    {
        $this->ville = $ville;

        return $this;
    }

    /**
     * Get ville
     *
     * @return string 
     */
    public function getVille()
    {
        return $this->ville;
    }

    /**
     * Set pays
     *
     * @param string $pays
     * @return Account
     */
    public function setPays($pays)
    {
        $this->pays = $pays;

        return $this;
    }

    /**
     * Get pays
     *
     * @return string 
     */
    public function getPays()
    {
        return $this->pays;
    }
}

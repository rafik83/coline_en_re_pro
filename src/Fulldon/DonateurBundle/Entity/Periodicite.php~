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

/**
 * @ORM\Table(name="periodicite")
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\PeriodiciteRepository")
 */
class Periodicite
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=100)
     */
    private $name;
    /**
     * @ORM\Column(name="code", type="decimal", length=3)
     */
    private $code;
    /**
     * @ORM\Column(name="visible_do", type="boolean")
     */
    private $visibleOnDonateur;

    public function __contruct() {

        $this->visibleOnDonateur = true;

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
     * Set name
     *
     * @param string $name
     * @return Periodicite
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return Periodicite
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }



    /**
     * Set visibleOnDonateur
     *
     * @param boolean $visibleOnDonateur
     * @return Periodicite
     */
    public function setVisibleOnDonateur($visibleOnDonateur)
    {
        $this->visibleOnDonateur = $visibleOnDonateur;

        return $this;
    }

    /**
     * Get visibleOnDonateur
     *
     * @return boolean 
     */
    public function getVisibleOnDonateur()
    {
        return $this->visibleOnDonateur;
    }
}

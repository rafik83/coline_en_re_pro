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
 * @ORM\Table(name="cat_donateur")
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\CategoryDonateurRepository")
 */
class CategoryDonateur
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
     * @ORM\Column(name="code", type="string", length=255)
     */
    protected $code;
    /**
     * @ORM\ManyToMany(targetEntity="Donateur", mappedBy="categories")
     */
    private $donateurs;
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
     * @return CategoryDonateur
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
     * @return CategoryDonateur
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
     * Constructor
     */
    public function __construct()
    {
        $this->donateurs = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add donateur
     *
     * @param \Fulldon\DonateurBundle\Entity\Donateur $donateur
     *
     * @return CategoryDonateur
     */
    public function addDonateur(\Fulldon\DonateurBundle\Entity\Donateur $donateur)
    {
        $this->donateurs[] = $donateur;

        return $this;
    }

    /**
     * Remove donateur
     *
     * @param \Fulldon\DonateurBundle\Entity\Donateur $donateur
     */
    public function removeDonateur(\Fulldon\DonateurBundle\Entity\Donateur $donateur)
    {
        $this->donateurs->removeElement($donateur);
    }

    /**
     * Get donateurs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDonateurs()
    {
        return $this->donateurs;
    }
}

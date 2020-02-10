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

/**
 * @ORM\Table(name="reception_mode")
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\ReceptionModeRepository")
 */
class ReceptionMode
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
     * @ORM\ManyToMany(targetEntity="Prospection", mappedBy="receptions")
     */
    private $prospections;
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
     * @return ReceptionMode
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
     * @return ReceptionMode
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
        $this->prospections = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add prospections
     *
     * @param \Fulldon\DonateurBundle\Entity\Prospection $prospections
     * @return ReceptionMode
     */
    public function addProspection(\Fulldon\DonateurBundle\Entity\Prospection $prospections)
    {
        $this->prospections[] = $prospections;

        return $this;
    }

    /**
     * Remove prospections
     *
     * @param \Fulldon\DonateurBundle\Entity\Prospection $prospections
     */
    public function removeProspection(\Fulldon\DonateurBundle\Entity\Prospection $prospections)
    {
        $this->prospections->removeElement($prospections);
    }

    /**
     * Get prospections
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getProspections()
    {
        return $this->prospections;
    }
}

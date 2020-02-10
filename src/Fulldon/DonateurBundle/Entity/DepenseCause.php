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
 * @ORM\Table(name="depenses_cause")
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\DepenseCauseRepository")
 *
 */
class DepenseCause
{

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\Column(name="objet_depense", type="text", nullable=true)
     */
    private $objetDepense;
    /**
     * @ORM\Column(name="montant", type="decimal", precision=10, scale=2)
     */
    protected $montant;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\Cause")
     * @ORM\JoinColumn( name="id_cause", nullable=true);
     */
    protected $cause;
    /**
     * @ORM\Column(name="date_depense", type="date", nullable=true)
     */
    protected $dateDepense;


    public function __construct()
    {
        $this->dateDepense = new \Datetime();

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
     * Set objetDepense
     *
     * @param string $objetDepense
     * @return DepenseCause
     */
    public function setObjetDepense($objetDepense)
    {
        $this->objetDepense = $objetDepense;

        return $this;
    }

    /**
     * Get objetDepense
     *
     * @return string
     */
    public function getObjetDepense()
    {
        return $this->objetDepense;
    }

    /**
     * Set montant
     *
     * @param string $montant
     * @return DepenseCause
     */
    public function setMontant($montant)
    {
        $this->montant = $montant;

        return $this;
    }

    /**
     * Get montant
     *
     * @return string
     */
    public function getMontant()
    {
        return $this->montant;
    }

    /**
     * Set dateDepense
     *
     * @param \DateTime $dateDepense
     * @return DepenseCause
     */
    public function setDateDepense($dateDepense)
    {
        $this->dateDepense = $dateDepense;

        return $this;
    }

    /**
     * Get dateDepense
     *
     * @return \DateTime
     */
    public function getDateDepense()
    {
        return $this->dateDepense;
    }

    /**
     * Set cause
     *
     * @param \Fulldon\DonateurBundle\Entity\Cause $cause
     * @return DepenseCause
     */
    public function setCause(\Fulldon\DonateurBundle\Entity\Cause $cause = null)
    {
        $this->cause = $cause;

        return $this;
    }

    /**
     * Get cause
     *
     * @return \Fulldon\DonateurBundle\Entity\Cause
     */
    public function getCause()
    {
        return $this->cause;
    }
}

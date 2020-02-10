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
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\ModePaiementRepository")
 * @ORM\Table(name="mode_paiement")
 */
class ModePaiement
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\Column(name="libelle", type="string", length=255)
     */
    private $libelle;
    /**
     * @ORM\Column(name="code_solution", type="string", length=255)
     */
    private $codeSolution;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\IntersaBundle\Entity\TypeDon")
     * @ORM\JoinColumn( name="type_id",nullable=true);
     */
    protected $type;

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
     * Set codeSolution
     *
     * @param string $codeSolution
     * @return ModePaiement
     */
    public function setCodeSolution($codeSolution)
    {
        $this->codeSolution = $codeSolution;

        return $this;
    }

    /**
     * Get codeSolution
     *
     * @return string 
     */
    public function getCodeSolution()
    {
        return $this->codeSolution;
    }

    /**
     * Set libelle
     *
     * @param string $libelle
     * @return ModePaiement
     */
    public function setLibelle($libelle)
    {
        $this->libelle = $libelle;

        return $this;
    }

    /**
     * Get libelle
     *
     * @return string 
     */
    public function getLibelle()
    {
        return $this->libelle;
    }

    /**
     * Set type
     *
     * @param \Fulldon\IntersaBundle\Entity\TypeDon $type
     * @return ModePaiement
     */
    public function setType(\Fulldon\IntersaBundle\Entity\TypeDon $type = null)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return \Fulldon\IntersaBundle\Entity\TypeDon 
     */
    public function getType()
    {
        return $this->type;
    }
}

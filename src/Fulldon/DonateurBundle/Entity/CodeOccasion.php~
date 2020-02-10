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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\CodeOccasionRepository")
 * @ORM\Table(name="code_occasion")
 * @UniqueEntity(fields={"code"}, message="Le code occasion doit être unique")
 *
 */
class CodeOccasion
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected  $id;
    /**
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;
    /**
     * @ORM\Column(name="code", type="string", length=255)
     */
    protected $code;

    /**
     * @ORM\Column(name="cible", type="string", length=255)
     */
    protected $cible;

    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\CodeCompagne")
     * @ORM\JoinColumn( name="code_compagne", nullable=true);
     */
    protected $codeCompagne;


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
     * @return CodeOccasion
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
     * @return CodeOccasion
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
     * Set codeCompagne
     *
     * @param \Fulldon\DonateurBundle\Entity\CodeCompagne $codeCompagne
     * @return CodeOccasion
     */
    public function setCodeCompagne(\Fulldon\DonateurBundle\Entity\CodeCompagne $codeCompagne = null)
    {
        $this->codeCompagne = $codeCompagne;

        return $this;
    }

    /**
     * Get codeCompagne
     *
     * @return \Fulldon\DonateurBundle\Entity\CodeCompagne
     */
    public function getCodeCompagne()
    {
        return $this->codeCompagne;
    }

    /**
     * Set cible
     *
     * @param string $cible
     * @return CodeOccasion
     */
    public function setCible($cible)
    {
        $this->cible = $cible;

        return $this;
    }

    /**
     * Get cible
     *
     * @return string 
     */
    public function getCible()
    {
        return $this->cible;
    }
}

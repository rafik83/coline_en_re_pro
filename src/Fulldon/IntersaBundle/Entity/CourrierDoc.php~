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
 * @ORM\Table(name="courrier_doc")
 * @ORM\Entity(repositoryClass="Fulldon\IntersaBundle\Entity\CourrierDocRepository")
 */
class CourrierDoc
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="nom", type="string", length=255)
     */
    private $nom;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\IntersaBundle\Entity\CourrierTraitement")
     * @ORM\JoinColumn( name="courrier_traitement_id");
     */
    protected $courrierTraitement;

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
     * Set nom
     *
     * @param string $nom
     * @return CourrierDoc
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom
     *
     * @return string 
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set courrierTraitement
     *
     * @param \Fulldon\IntersaBundle\Entity\CourrierTraitement $courrierTraitement
     * @return CourrierDoc
     */
    public function setCourrierTraitement(\Fulldon\IntersaBundle\Entity\CourrierTraitement $courrierTraitement = null)
    {
        $this->courrierTraitement = $courrierTraitement;

        return $this;
    }

    /**
     * Get courrierTraitement
     *
     * @return \Fulldon\IntersaBundle\Entity\CourrierTraitement 
     */
    public function getCourrierTraitement()
    {
        return $this->courrierTraitement;
    }
}

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
 * @ORM\Table(name="ville", indexes={@ORM\Index(name="search_ville_idx", columns={"id"}),
 * @ORM\Index(name="search_id_pays_idx", columns={"id_pays"})
 * })
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\VilleRepository")
 *
 */
class Ville
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
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\Pays")
     * @ORM\JoinColumn( name="id_pays", nullable=true);
     */
    protected $pays;


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
     * @return Ville
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
     * Set pays
     *
     * @param \Fulldon\DonateurBundle\Entity\Pays $pays
     * @return Ville
     */
    public function setPays(\Fulldon\DonateurBundle\Entity\Pays $pays = null)
    {
        $this->pays = $pays;

        return $this;
    }

    /**
     * Get pays
     *
     * @return \Fulldon\DonateurBundle\Entity\Pays 
     */
    public function getPays()
    {
        return $this->pays;
    }
}

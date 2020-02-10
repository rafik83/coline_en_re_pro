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
 * @ORM\Table(name="solde")
 * @ORM\Entity(repositoryClass="Fulldon\IntersaBundle\Entity\SoldeRepository")
 */
class Solde
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="solde_sms", type="string", length=255)
     */
    private $soldeSms;
    /**
     * @ORM\Column(name="solde_email", type="string", length=255)
     */
    private $soldeEmail;


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
     * Set soldeSms
     *
     * @param string $soldeSms
     * @return Solde
     */
    public function setSoldeSms($soldeSms)
    {
        $this->soldeSms = $soldeSms;

        return $this;
    }

    /**
     * Get soldeSms
     *
     * @return string 
     */
    public function getSoldeSms()
    {
        return $this->soldeSms;
    }

    /**
     * Set soldeEmail
     *
     * @param string $soldeEmail
     * @return Solde
     */
    public function setSoldeEmail($soldeEmail)
    {
        $this->soldeEmail = $soldeEmail;

        return $this;
    }

    /**
     * Get soldeEmail
     *
     * @return string 
     */
    public function getSoldeEmail()
    {
        return $this->soldeEmail;
    }
}

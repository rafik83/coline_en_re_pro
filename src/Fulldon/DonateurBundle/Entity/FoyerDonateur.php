<?php

namespace Fulldon\DonateurBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

//use Symfony\Component\Security\Core\Role\RoleInterface;
//use Doctrine\Common\Collections\ArrayCollection;

/**
 * FoyerDonateur
 *
 * @ORM\Table(name="foyer_donateur")
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\FoyerDonateurRepository")
 */
class FoyerDonateur {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return FoyerDonateur
     */
    public function setEmail($email) {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }

    public function __toString() {
        return $this->email;
    }

}

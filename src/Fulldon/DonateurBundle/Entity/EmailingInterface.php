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
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\EmailingInterfaceRepository")
 * @ORM\Table(name="emailing_interface")
 */
class EmailingInterface
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    /**
     * @ORM\Column(name="id_unique", type="string", nullable=false)
     */
    protected $uniqueId;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\Donateur", cascade={"remove"})
     * @ORM\JoinColumn( name="donateur_id", nullable=true, onDelete="CASCADE");
     */
    protected $donateur;
    /**
     * @ORM\Column(name="content", type="text", nullable=false)
     */
    protected $content;


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
     * Set uniqueId
     *
     * @param string $uniqueId
     * @return EmailingInterface
     */
    public function setUniqueId($uniqueId)
    {
        $this->uniqueId = $uniqueId;

        return $this;
    }

    /**
     * Get uniqueId
     *
     * @return string 
     */
    public function getUniqueId()
    {
        return $this->uniqueId;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return EmailingInterface
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set donateur
     *
     * @param \Fulldon\DonateurBundle\Entity\Donateur $donateur
     * @return EmailingInterface
     */
    public function setDonateur(\Fulldon\DonateurBundle\Entity\Donateur $donateur = null)
    {
        $this->donateur = $donateur;

        return $this;
    }

    /**
     * Get donateur
     *
     * @return \Fulldon\DonateurBundle\Entity\Donateur 
     */
    public function getDonateur()
    {
        return $this->donateur;
    }
}

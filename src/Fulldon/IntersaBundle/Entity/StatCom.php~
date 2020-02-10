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
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Table(name="stat_com")
 * @ORM\Entity(repositoryClass="Fulldon\IntersaBundle\Entity\StatComRepository")
 */
class StatCom
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;
    /**
     * @ORM\Column(name="nb_sms",type="integer")
     */
    protected $nbSms;
    /**
     * @ORM\Column(name="nb_email",type="integer")
     */
    protected $nbEmail;
    /**
     * @ORM\Column(name="tag",type="string", length=255)
     */
    protected $tag;


    public function __construct()
    {
        $this->createdAt = new \Datetime();
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return StatCom
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set nbSms
     *
     * @param integer $nbSms
     * @return StatCom
     */
    public function setNbSms($nbSms)
    {
        $this->nbSms = $nbSms;

        return $this;
    }

    /**
     * Get nbSms
     *
     * @return integer 
     */
    public function getNbSms()
    {
        return $this->nbSms;
    }

    /**
     * Set nbEmail
     *
     * @param integer $nbEmail
     * @return StatCom
     */
    public function setNbEmail($nbEmail)
    {
        $this->nbEmail = $nbEmail;

        return $this;
    }

    /**
     * Get nbEmail
     *
     * @return integer 
     */
    public function getNbEmail()
    {
        return $this->nbEmail;
    }

    /**
     * Set tag
     *
     * @param string $tag
     * @return StatCom
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Get tag
     *
     * @return string 
     */
    public function getTag()
    {
        return $this->tag;
    }
}

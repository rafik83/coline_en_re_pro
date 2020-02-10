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
 * @ORM\Table(name="upload_prospection")
 * @ORM\Entity(repositoryClass="Fulldon\IntersaBundle\Entity\LogRepository")
 */
class UploadProspection
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\CodeOccasion")
     * @ORM\JoinColumn( name="occasion_id");
     */
    protected $occasion;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;
    /**
     * @ORM\Column(name="fileName",type="string")
     */
    protected $fileName;


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
     * @return UploadProspection
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
     * Set fileName
     *
     * @param string $fileName
     * @return UploadProspection
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Get fileName
     *
     * @return string 
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Set occasion
     *
     * @param \Fulldon\DonateurBundle\Entity\CodeOccasion $occasion
     * @return UploadProspection
     */
    public function setOccasion(\Fulldon\DonateurBundle\Entity\CodeOccasion $occasion = null)
    {
        $this->occasion = $occasion;

        return $this;
    }

    /**
     * Get occasion
     *
     * @return \Fulldon\DonateurBundle\Entity\CodeOccasion 
     */
    public function getOccasion()
    {
        return $this->occasion;
    }
}

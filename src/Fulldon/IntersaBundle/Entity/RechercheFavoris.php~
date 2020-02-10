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
 * @ORM\Table(name="recherche_favoris")
 * @ORM\Entity(repositoryClass="Fulldon\IntersaBundle\Entity\RechercheFavorisRepository")
 * @ORM\HasLifecycleCallbacks
 */
class RechercheFavoris
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\Column(type="string")
     */
    private $title;

    /**
     * @ORM\Column(name="description",type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(name="url",type="text", nullable=true)
     */
    private $url;

    /**
     * @ORM\Column(name="section",type="string", length=255, nullable=false)
     */
    private $section;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;
    
    /**
     * @ORM\ManyToMany(targetEntity="Fulldon\IntersaBundle\Entity\CustomCronTask", mappedBy="recherches")
     * @ORM\JoinTable(name="customcrontask_recherchefavoris")
     */
    protected $customTasks;

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
     * Set title
     *
     * @param string $title
     *
     * @return RechercheFavoris
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return RechercheFavoris
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set url
     *
     * @param string $url
     *
     * @return RechercheFavoris
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set section
     *
     * @param string $section
     *
     * @return RechercheFavoris
     */
    public function setSection($section)
    {
        $this->section = $section;

        return $this;
    }

    /**
     * Get section
     *
     * @return string
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return RechercheFavoris
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
     * Add customTask
     *
     * @param \Fulldon\IntersaBundle\Entity\CustomCronTask $customTask
     *
     * @return RechercheFavoris
     */
    public function addCustomTask(\Fulldon\IntersaBundle\Entity\CustomCronTask $customTask)
    {
        $this->customTasks[] = $customTask;

        return $this;
    }

    /**
     * Remove customTask
     *
     * @param \Fulldon\IntersaBundle\Entity\CustomCronTask $customTask
     */
    public function removeCustomTask(\Fulldon\IntersaBundle\Entity\CustomCronTask $customTask)
    {
        $this->customTasks->removeElement($customTask);
    }

    /**
     * Get customTasks
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCustomTasks()
    {
        return $this->customTasks;
    }
}

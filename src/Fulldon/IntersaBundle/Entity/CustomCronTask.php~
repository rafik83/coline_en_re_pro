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
 * @ORM\Entity
 * @ORM\Table(name="custom_cron_task")
 * @ORM\HasLifecycleCallbacks
 */
class CustomCronTask
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastrun;

    /**
     * @ORM\Column(name="action",type="string", length=255, nullable=true)
     */
    private $action;

    /**
     * @ORM\Column(name="done", type="boolean", nullable=true)
     */
    private $done;
    
    /**
     * @ORM\ManyToMany(targetEntity="Fulldon\IntersaBundle\Entity\RechercheFavoris",inversedBy="customTasks")
     * @ORM\JoinTable(name="customcrontask_recherchefavoris")
     */
    protected $recherches;
    
    /**
     * @ORM\Column(name="progress", type="boolean", nullable=true)
     */
    private $progress;

    public function __construct()
    {
        $this->done = false;
        $this->lastrun = new \Datetime();
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
     * Set lastrun
     *
     * @param \DateTime $lastrun
     *
     * @return CustomCronTask
     */
    public function setLastrun($lastrun)
    {
        $this->lastrun = $lastrun;

        return $this;
    }

    /**
     * Get lastrun
     *
     * @return \DateTime
     */
    public function getLastrun()
    {
        return $this->lastrun;
    }

    /**
     * Set action
     *
     * @param string $action
     *
     * @return CustomCronTask
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set done
     *
     * @param boolean $done
     *
     * @return CustomCronTask
     */
    public function setDone($done)
    {
        $this->done = $done;

        return $this;
    }

    /**
     * Get done
     *
     * @return boolean
     */
    public function getDone()
    {
        return $this->done;
    }

    /**
     * Set progress
     *
     * @param boolean $progress
     *
     * @return CustomCronTask
     */
    public function setProgress($progress)
    {
        $this->progress = $progress;

        return $this;
    }

    /**
     * Get progress
     *
     * @return boolean
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * Add recherch
     *
     * @param \Fulldon\IntersaBundle\Entity\RechercheFavoris $recherch
     *
     * @return CustomCronTask
     */
    public function addRecherch(\Fulldon\IntersaBundle\Entity\RechercheFavoris $recherch)
    {
        $this->recherches[] = $recherch;

        return $this;
    }

    /**
     * Remove recherch
     *
     * @param \Fulldon\IntersaBundle\Entity\RechercheFavoris $recherch
     */
    public function removeRecherch(\Fulldon\IntersaBundle\Entity\RechercheFavoris $recherch)
    {
        $this->recherches->removeElement($recherch);
    }

    /**
     * Get recherches
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRecherches()
    {
        return $this->recherches;
    }
}

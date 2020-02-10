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
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="email_cron_task")
 */
class MarketingCronTask
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\Column(type="string")
     */
    private $title;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastrun;

    /**
     * @ORM\Column(name="objet",type="string", length=255, nullable=true)
     */
    private $objet;

    /**
     * @ORM\Column(name="file",type="string", length=255, nullable=false)
     */
    private $file;

    /**
     * @ORM\Column(name="columns",type="text", nullable=true)
     */
    private $columns;

    /**
     * @ORM\Column(name="email_content",type="text", nullable=true)
     */
    private $emailContent;

    /**
     * @ORM\Column(name="sms_content",type="text", nullable=true)
     */
    private $smsContent;

    /**
     * @ORM\Column(name="done", type="boolean", nullable=true)
     */
    private $done;

    /**
     * @ORM\Column(name="progress", type="boolean", nullable=true)
     */
    private $progress;

    /**
     * @ORM\Column(name="is_sms", type="boolean", nullable=true)
     */
    private $isSms;
    /**
     * @ORM\Column(name="is_email", type="boolean", nullable=true)
     */
    private $isEmail;
    /**
     * @ORM\Column(name="total_donateur", type="integer", nullable=true)
     */
    private $totalDonateur;

    /**
     * @ORM\Column(name="test_mode", type="boolean", nullable=true)
     */
    private $testMode;

    /**
     * @ORM\OneToOne(targetEntity="StatCom", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true,name="stat_id");
     */
    protected $stats;
    // private use
    private $appFolder;
    private $path;


    public function __construct()
    {
        $this->done = false;
        $this->lastrun = new \Datetime();
    }
    public function getFullFilePath() {
        return null === $this->file ? null : $this->getUploadRootDir().$this->file;
    }
    public function getUploadRootDir() {
        return $this->getTmpUploadRootDir().$this->getId()."/";
    }
    public function getTmpUploadRootDir() {
        //return __DIR__ .'/../../../../web/marketing /';
        return '/'.$this->getAppFolder().'/MARKETING/';
    }
    /**
     * @ORM\PrePersist
     */
    public function uploadFile() {
        if(null === $this->file || !is_object($this->file)) {
            return ;
        }

        $filename = sha1(uniqid(mt_rand(), true));
        $this->path = $filename.'.csv';

        if(!$this->id) {
            $this->file->move($this->getTmpUploadRootDir(), $this->path);
        } else {
            $this->file->move($this->getUploadRootDir(), $this->path);
        }
        $this->setFile($this->path);
    }

    /**
     * @ORM\PostPersist
     */
    public function moveFile()
    {
        if(null === $this->file) {
            return;
        }
        if(!is_dir($this->getUploadRootDir())){
            mkdir($this->getUploadRootDir());
        }
        copy($this->getTmpUploadRootDir().$this->file, $this->getFullFilePath());
        unlink($this->getTmpUploadRootDir().$this->file);
    }

    /**
     * @ORM\PreRemove()
     */
    public function removeFile()
    {
        unlink($this->getFullFilePath());
        rmdir($this->getUploadRootDir());
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
     * @return MarketingCronTask
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
     * Set lastrun
     *
     * @param \DateTime $lastrun
     * @return MarketingCronTask
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
     * Set objet
     *
     * @param string $objet
     * @return MarketingCronTask
     */
    public function setObjet($objet)
    {
        $this->objet = $objet;

        return $this;
    }

    /**
     * Get objet
     *
     * @return string 
     */
    public function getObjet()
    {
        return $this->objet;
    }

    /**
     * Set file
     *
     * @param string $file
     * @return MarketingCronTask
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Get file
     *
     * @return string 
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set emailContent
     *
     * @param string $emailContent
     * @return MarketingCronTask
     */
    public function setEmailContent($emailContent)
    {
        $this->emailContent = $emailContent;

        return $this;
    }

    /**
     * Get emailContent
     *
     * @return string 
     */
    public function getEmailContent()
    {
        return $this->emailContent;
    }

    /**
     * Set smsContent
     *
     * @param string $smsContent
     * @return MarketingCronTask
     */
    public function setSmsContent($smsContent)
    {
        $this->smsContent = $smsContent;

        return $this;
    }

    /**
     * Get smsContent
     *
     * @return string 
     */
    public function getSmsContent()
    {
        return $this->smsContent;
    }

    /**
     * Set done
     *
     * @param boolean $done
     * @return MarketingCronTask
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
     * Set totalDonateur
     *
     * @param boolean $totalDonateur
     * @return MarketingCronTask
     */
    public function setTotalDonateur($totalDonateur)
    {
        $this->totalDonateur = $totalDonateur;

        return $this;
    }

    /**
     * Get totalDonateur
     *
     * @return boolean 
     */
    public function getTotalDonateur()
    {
        return $this->totalDonateur;
    }

    /**
     * Set isSms
     *
     * @param boolean $isSms
     * @return MarketingCronTask
     */
    public function setIsSms($isSms)
    {
        $this->isSms = $isSms;

        return $this;
    }

    /**
     * Get isSms
     *
     * @return boolean 
     */
    public function getIsSms()
    {
        return $this->isSms;
    }

    /**
     * Set isEmail
     *
     * @param boolean $isEmail
     * @return MarketingCronTask
     */
    public function setIsEmail($isEmail)
    {
        $this->isEmail = $isEmail;

        return $this;
    }

    /**
     * Get isEmail
     *
     * @return boolean 
     */
    public function getIsEmail()
    {
        return $this->isEmail;
    }

    public function getAppFolder()
    {
        return $this->appFolder;
    }
    public function setAppFolder($appFolder)
    {
        $this->appFolder = $appFolder;
    }


    public function setStats(\Fulldon\IntersaBundle\Entity\StatCom $stats = null)
    {
        $this->stats = $stats;

        return $this;
    }


    public function getStats()
    {
        return $this->stats;
    }

    /**
     * Set columns
     *
     * @param string $columns
     * @return MarketingCronTask
     */
    public function setColumns($columns)
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * Get columns
     *
     * @return string 
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Set progress
     *
     * @param boolean $progress
     * @return MarketingCronTask
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
     * Set testMode
     *
     * @param boolean $testMode
     * @return MarketingCronTask
     */
    public function setTestMode($testMode)
    {
        $this->testMode = $testMode;

        return $this;
    }

    /**
     * Get testMode
     *
     * @return boolean 
     */
    public function getTestMode()
    {
        return $this->testMode;
    }
}

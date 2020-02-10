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
 * @ORM\Table(name="conf_avance")
 * @ORM\Entity(repositoryClass="Fulldon\IntersaBundle\Entity\ConfAvanceRepository")
 */
class ConfAvance
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="seuil_rf", type="string", length=255, nullable=true)
     */
    private $seuilRf;
    /**
     * @ORM\Column(name="seuil_pnd", type="string", length=255, nullable=true)
     */
    private $seuilPnd;
    /**
     * @ORM\Column(name="assoc_iban", type="string", length=255, nullable=true)
     */
    private $assocIban;
    /**
     * @ORM\Column(name="assoc_bic", type="string", length=255, nullable=true)
     */
    private $assocBic;
    /**
     * @ORM\Column(name="assoc_sepa", type="string", length=255, nullable=true)
     */
    private $assocSepa;
    /**
     * @ORM\Column(name="assoc_banque_name", type="string", length=255, nullable=true)
     */
    private $assocBanqueName;
    /**
     * @ORM\Column(name="jour_prelevement", type="string", length=255, nullable=true)
     */
    private $jourPrelevement;
    /**
     * @ORM\Column(name="twitter_id", type="string", length=255, nullable=true)
     */
    private $twitterId;
    /**
     * @ORM\Column(name="facebook_id",type="string", length=255, nullable=true)
     */
    private $facebookId;
    /**
     * @ORM\Column(name="google_analytics", type="string", length=255, nullable=true)
     */
    private $googleAnalytics;
    /**
     * @ORM\Column(name="max_per_page", type="string", length=255, nullable=true)
     */
    private $maxPerPage;
    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;
    /**
     * @ORM\Column(name="generate_rf", type="boolean", nullable=true)
     */
    protected $generateRf;
    /**
     * @ORM\Column(name="send_stats", type="boolean", nullable=true)
     */
    protected $sendStats;

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
     * Set seuilRf
     *
     * @param string $seuilRf
     * @return ConfAvance
     */
    public function setSeuilRf($seuilRf)
    {
        $this->seuilRf = $seuilRf;

        return $this;
    }

    /**
     * Get seuilRf
     *
     * @return string 
     */
    public function getSeuilRf()
    {
        return $this->seuilRf;
    }

    /**
     * Set seuilPnd
     *
     * @param string $seuilPnd
     * @return ConfAvance
     */
    public function setSeuilPnd($seuilPnd)
    {
        $this->seuilPnd = $seuilPnd;

        return $this;
    }

    /**
     * Get seuilPnd
     *
     * @return string 
     */
    public function getSeuilPnd()
    {
        return $this->seuilPnd;
    }

    /**
     * Set assocIban
     *
     * @param string $assocIban
     * @return ConfAvance
     */
    public function setAssocIban($assocIban)
    {
        $this->assocIban = $assocIban;

        return $this;
    }

    /**
     * Get assocIban
     *
     * @return string 
     */
    public function getAssocIban()
    {
        return $this->assocIban;
    }

    /**
     * Set assocBic
     *
     * @param string $assocBic
     * @return ConfAvance
     */
    public function setAssocBic($assocBic)
    {
        $this->assocBic = $assocBic;

        return $this;
    }

    /**
     * Get assocBic
     *
     * @return string 
     */
    public function getAssocBic()
    {
        return $this->assocBic;
    }

    /**
     * Set assocSepa
     *
     * @param string $assocSepa
     * @return ConfAvance
     */
    public function setAssocSepa($assocSepa)
    {
        $this->assocSepa = $assocSepa;

        return $this;
    }

    /**
     * Get assocSepa
     *
     * @return string 
     */
    public function getAssocSepa()
    {
        return $this->assocSepa;
    }

    /**
     * Set assocBanqueName
     *
     * @param string $assocBanqueName
     * @return ConfAvance
     */
    public function setAssocBanqueName($assocBanqueName)
    {
        $this->assocBanqueName = $assocBanqueName;

        return $this;
    }

    /**
     * Get assocBanqueName
     *
     * @return string 
     */
    public function getAssocBanqueName()
    {
        return $this->assocBanqueName;
    }

    /**
     * Set jourPrelevement
     *
     * @param string $jourPrelevement
     * @return ConfAvance
     */
    public function setJourPrelevement($jourPrelevement)
    {
        $this->jourPrelevement = $jourPrelevement;

        return $this;
    }

    /**
     * Get jourPrelevement
     *
     * @return string 
     */
    public function getJourPrelevement()
    {
        return $this->jourPrelevement;
    }

    /**
     * Set twitterId
     *
     * @param string $twitterId
     * @return ConfAvance
     */
    public function setTwitterId($twitterId)
    {
        $this->twitterId = $twitterId;

        return $this;
    }

    /**
     * Get twitterId
     *
     * @return string 
     */
    public function getTwitterId()
    {
        return $this->twitterId;
    }

    /**
     * Set facebookId
     *
     * @param string $facebookId
     * @return ConfAvance
     */
    public function setFacebookId($facebookId)
    {
        $this->facebookId = $facebookId;

        return $this;
    }

    /**
     * Get facebookId
     *
     * @return string 
     */
    public function getFacebookId()
    {
        return $this->facebookId;
    }

    /**
     * Set googleAnalytics
     *
     * @param string $googleAnalytics
     * @return ConfAvance
     */
    public function setGoogleAnalytics($googleAnalytics)
    {
        $this->googleAnalytics = $googleAnalytics;

        return $this;
    }

    /**
     * Get googleAnalytics
     *
     * @return string 
     */
    public function getGoogleAnalytics()
    {
        return $this->googleAnalytics;
    }

    /**
     * Set maxPerPage
     *
     * @param string $maxPerPage
     * @return ConfAvance
     */
    public function setMaxPerPage($maxPerPage)
    {
        $this->maxPerPage = $maxPerPage;

        return $this;
    }

    /**
     * Get maxPerPage
     *
     * @return string 
     */
    public function getMaxPerPage()
    {
        return $this->maxPerPage;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return ConfAvance
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
     * Set generateRf
     *
     * @param boolean $generateRf
     * @return ConfAvance
     */
    public function setGenerateRf($generateRf)
    {
        $this->generateRf = $generateRf;

        return $this;
    }

    /**
     * Get generateRf
     *
     * @return boolean 
     */
    public function getGenerateRf()
    {
        return $this->generateRf;
    }

    /**
     * Set sendStats
     *
     * @param boolean $sendStats
     * @return ConfAvance
     */
    public function setSendStats($sendStats)
    {
        $this->sendStats = $sendStats;

        return $this;
    }

    /**
     * Get sendStats
     *
     * @return boolean 
     */
    public function getSendStats()
    {
        return $this->sendStats;
    }
}

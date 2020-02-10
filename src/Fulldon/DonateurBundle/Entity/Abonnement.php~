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
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\AbonnementRepository")
 * @ORM\Table(name="abonnement",indexes={@ORM\Index(name="id_abo_idx", columns={"id"}),
 * @ORM\Index(name="datenext_abo_idx", columns={"date_next_pa"}),
 * @ORM\Index(name="datefirst_abo_idx", columns={"date_first_pa"}),
 * @ORM\Index(name="stat_one_abo_idx", columns={"actif","disabled_at"}),
 * @ORM\Index(name="stat_two_abo_idx", columns={"id","motif_abo_id"}),
 *
 * })
 */
class Abonnement
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="actif", type="boolean", nullable=true)
     */
    private $actif;
    /**
     * @ORM\Column(name="disabled_at", type="datetime", nullable=true)
     */
    protected $disabledAt;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\MotifAbo")
     * @ORM\JoinColumn( name="motif_abo_id", nullable=true);
     */
    protected $motif;
    /**
     * @ORM\Column(name="date_first_pa", type="date", nullable=true)
     */
    private $date_first_pa;
    /**
     * @ORM\Column(name="date_next_pa", type="date", nullable=true)
     */
    private $date_next_pa;
    /**
     * @ORM\Column(name="date_fin_pa", type="date", nullable=true)
     */
    private $date_fin_pa;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\Periodicite")
     * @ORM\JoinColumn( name="periodicite_id", nullable=true);
     */
    private $periodicite;
    /**
     * @ORM\Column(name="iban", type="string", length=100, nullable=true)
     */
    private $iban;
    /**
     * @ORM\Column(name="bic", type="string", length=100, nullable=true)
     */
    private $bic;
    /**
     * @ORM\Column(name="nom_banque", type="string", length=255, nullable=true)
     */
    protected $nomBanque;
    /**
     * @ORM\Column(name="rum_indice", type="integer", options={"default":1})
     */
    protected $rumIndice;
    /**
     * @ORM\Column(name="rum", type="string", length=255, nullable=true)
     */
    protected $rum;
    /**
     * @ORM\Column(name="prelevement_first", type="boolean", options={"default":true} )
     */
    private $preFirst = true;
    /**
     * @ORM\Column(name="signedFile", type="string", length=255, nullable=true)
     */
    protected $signedFile;
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
     * Set actif
     *
     * @param boolean $actif
     * @return Abonnement
     */
    public function setActif($actif)
    {
        $this->actif = $actif;

        return $this;
    }

    /**
     * Get actif
     *
     * @return boolean
     */
    public function getActif()
    {
        return $this->actif;
    }

    /**
     * Set motif_arret
     *
     * @param string $motifArret
     * @return Abonnement
     */
    public function setMotifArret($motifArret)
    {
        $this->motif_arret = $motifArret;

        return $this;
    }

    /**
     * Get motif_arret
     *
     * @return string
     */
    public function getMotifArret()
    {
        return $this->motif_arret;
    }

    /**
     * Set date_first_pa
     *
     * @param \DateTime $dateFirstPa
     * @return Abonnement
     */
    public function setDateFirstPa($dateFirstPa)
    {
        $this->date_first_pa = $dateFirstPa;

        return $this;
    }

    /**
     * Get date_first_pa
     *
     * @return \DateTime
     */
    public function getDateFirstPa()
    {
        return $this->date_first_pa;
    }

    /**
     * Set periodicite
     *
     * @param \Fulldon\DonateurBundle\Entity\Periodicite $periodicite
     * @return Abonnement
     */
    public function setPeriodicite(\Fulldon\DonateurBundle\Entity\Periodicite $periodicite = null)
    {
        $this->periodicite = $periodicite;

        return $this;
    }

    /**
     * Get periodicite
     *
     * @return \Fulldon\DonateurBundle\Entity\Periodicite
     */
    public function getPeriodicite()
    {
        return $this->periodicite;
    }

    /**
     * Set date_next_pa
     *
     * @param \DateTime $dateNextPa
     * @return Abonnement
     */
    public function setDateNextPa($dateNextPa)
    {
        $this->date_next_pa = $dateNextPa;

        return $this;
    }

    /**
     * Get date_next_pa
     *
     * @return \DateTime
     */
    public function getDateNextPa()
    {
        return $this->date_next_pa;
    }

    /**
     * Set motif
     *
     * @param \Fulldon\DonateurBundle\Entity\MotifAbo $motif
     * @return Abonnement
     */
    public function setMotif(\Fulldon\DonateurBundle\Entity\MotifAbo $motif = null)
    {
        $this->motif = $motif;

        return $this;
    }

    /**
     * Get motif
     *
     * @return \Fulldon\DonateurBundle\Entity\MotifAbo
     */
    public function getMotif()
    {
        return $this->motif;
    }

    /**
     * Set disabledAt
     *
     * @param \DateTime $disabledAt
     * @return Abonnement
     */
    public function setDisabledAt($disabledAt)
    {
        $this->disabledAt = $disabledAt;

        return $this;
    }

    /**
     * Get disabledAt
     *
     * @return \DateTime
     */
    public function getDisabledAt()
    {
        return $this->disabledAt;
    }

    /**
     * Set iban
     *
     * @param string $iban
     * @return Abonnement
     */
    public function setIban($iban)
    {
        $this->iban = $iban;

        return $this;
    }

    /**
     * Get iban
     *
     * @return string
     */
    public function getIban()
    {
        return $this->iban;
    }

    /**
     * Set bic
     *
     * @param string $bic
     * @return Abonnement
     */
    public function setBic($bic)
    {
        $this->bic = $bic;

        return $this;
    }

    /**
     * Get bic
     *
     * @return string
     */
    public function getBic()
    {
        return $this->bic;
    }

    /**
     * Set nomBanque
     *
     * @param string $nomBanque
     * @return Abonnement
     */
    public function setNomBanque($nomBanque)
    {
        $this->nomBanque = $nomBanque;

        return $this;
    }

    /**
     * Get nomBanque
     *
     * @return string
     */
    public function getNomBanque()
    {
        return $this->nomBanque;
    }

    /**
     * Set preFirst
     *
     * @param boolean $preFirst
     * @return Abonnement
     */
    public function setPreFirst($preFirst)
    {
        $this->preFirst = $preFirst;

        return $this;
    }

    /**
     * Get preFirst
     *
     * @return boolean
     */
    public function getPreFirst()
    {
        return $this->preFirst;
    }



    /**
     * Set rumIndice
     *
     * @param integer $rumIndice
     * @return Abonnement
     */
    public function setRumIndice($rumIndice)
    {
        $this->rumIndice = $rumIndice;

        return $this;
    }

    /**
     * Get rumIndice
     *
     * @return integer
     */
    public function getRumIndice()
    {
        return $this->rumIndice;
    }

    /**
     * Set rum
     *
     * @param string $rum
     * @return Abonnement
     */
    public function setRum($rum)
    {
        $this->rum = $rum;

        return $this;
    }

    /**
     * Get rum
     *
     * @return string
     */
    public function getRum()
    {
        return $this->rum;
    }

    /**
     * Set date_fin_pa
     *
     * @param \DateTime $dateFinPa
     * @return Abonnement
     */
    public function setDateFinPa($dateFinPa)
    {
        $this->date_fin_pa = $dateFinPa;

        return $this;
    }

    /**
     * Get date_fin_pa
     *
     * @return \DateTime
     */
    public function getDateFinPa()
    {
        return $this->date_fin_pa;
    }

    /**
     * Set signedFile
     *
     * @param string $signedFile
     * @return Abonnement
     */
    public function setSignedFile($signedFile)
    {
        $this->signedFile = $signedFile;

        return $this;
    }

    /**
     * Get signedFile
     *
     * @return string 
     */
    public function getSignedFile()
    {
        return $this->signedFile;
    }
}

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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\CauseRepository")
 * @ORM\Table(name="cause",indexes={@ORM\Index(name="id_cause_idx", columns={"id"}),
 * @ORM\Index(name="stat_cause_entity_idx", columns={"entity_assoc"}),
 * @ORM\Index(name="espace_donateur_cause", columns={"visible_do", "actif", "libelle"}),
 * @ORM\Index(name="espace_donateur_cause_en", columns={"visible_do", "actif", "libelle_en"})
 * })
 * @UniqueEntity(fields={"code"}, message="Le code de la cause doit être unique")
 *
 */
class Cause
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected  $id;
    /**
     * @ORM\Column(name="libelle", type="string", length=255)
     */
    protected $libelle;
    /**
     * @ORM\Column(name="libelle_en", type="string", length=255, nullable=true)
     */
    protected $libelleEn;
    /**
     * @ORM\Column(name="code", type="string", length=255)
     */
    protected $code;

    /**
     * @ORM\Column(name="cible", type="string", length=255, nullable=true)
     */
    protected $cible;
    
    /**
     * @ORM\Column("rf" , type="boolean")
     */
    protected $rf;

    /**
     * @ORM\Column("actif", type="boolean")
     */
    protected $actif;
    /**
     * @ORM\Column(name="visible_do", type="boolean")
     */
    private $visibleOnDonateur;

    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\CodeAnalytique")
     * @ORM\JoinColumn( name="code_analytique", nullable=true);
     */
    protected $codeAnalytique;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\CodeOccasion")
     * @ORM\JoinColumn( name="code_occasion", nullable=true);
     */
    protected $codeOccasion;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\IntersaBundle\Entity\Entity")
     * @ORM\JoinColumn( name="entity_assoc", nullable=true);
     */
    protected $entity;
    /**
     * @ORM\Column(name="budget_previsionnel", type="decimal", precision=10, scale=2, nullable=true)
     */
    protected $budgetPrevisionnel;
    /**
     * @ORM\Column(name="date_debut_projet", type="date", nullable=true)
     */
    protected $dateDebutProjet;
    /**
     * @ORM\Column(name="date_fin_projet", type="date", nullable=true)
     */
    protected $dateFinProjet;
    /**
     * @ORM\Column(name="detail_projet", type="text", nullable=true)
     */
    private $detailProjet;

    // Custom fields for coline en ré
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\Donateur")
     * @ORM\JoinColumn( name="artiste", nullable=true);
     */
    protected $artiste;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\Donateur")
     * @ORM\JoinColumn( name="hote", nullable=true);
     */
    protected $hote;

    /**
     * @ORM\Column(name="date_concert", type="date", length=255, nullable=true)
     */
    protected $dateConcert;
    /**
     * @ORM\Column(name="lieu_concert", type="string", length=255, nullable=true)
     */
    protected $lieuConcert;




    public function __contruct() {

    $this->rf = true;
    $this->visibleOnDonateur = true;

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
     * Set libelle
     *
     * @param string $libelle
     * @return Cause
     */
    public function setLibelle($libelle)
    {
        $this->libelle = $libelle;

        return $this;
    }

    /**
     * Get libelle
     *
     * @return string 
     */
    public function getLibelle()
    {
        return $this->libelle;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return Cause
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set rf
     *
     * @param boolean $rf
     * @return Cause
     */
    public function setRf($rf)
    {
        $this->rf = $rf;

        return $this;
    }

    /**
     * Get rf
     *
     * @return boolean 
     */
    public function getRf()
    {
        return $this->rf;
    }





    /**
     * Set visibleOnDonateur
     *
     * @param boolean $visibleOnDonateur
     * @return Cause
     */
    public function setVisibleOnDonateur($visibleOnDonateur)
    {
        $this->visibleOnDonateur = $visibleOnDonateur;

        return $this;
    }

    /**
     * Get visibleOnDonateur
     *
     * @return boolean 
     */
    public function getVisibleOnDonateur()
    {
        return $this->visibleOnDonateur;
    }

    /**
     * Set actif
     *
     * @param boolean $actif
     * @return Cause
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
     * Set codeAnalytique
     *
     * @param \Fulldon\DonateurBundle\Entity\CodeAnalytique $codeAnalytique
     * @return Cause
     */
    public function setCodeAnalytique(\Fulldon\DonateurBundle\Entity\CodeAnalytique $codeAnalytique = null)
    {
        $this->codeAnalytique = $codeAnalytique;

        return $this;
    }

    /**
     * Get codeAnalytique
     *
     * @return \Fulldon\DonateurBundle\Entity\CodeAnalytique 
     */
    public function getCodeAnalytique()
    {
        return $this->codeAnalytique;
    }

    /**
     * Set codeOccasion
     *
     * @param \Fulldon\DonateurBundle\Entity\CodeOccasion $codeOccasion
     * @return Cause
     */
    public function setCodeOccasion(\Fulldon\DonateurBundle\Entity\CodeOccasion $codeOccasion = null)
    {
        $this->codeOccasion = $codeOccasion;

        return $this;
    }

    /**
     * Get codeOccasion
     *
     * @return \Fulldon\DonateurBundle\Entity\CodeOccasion 
     */
    public function getCodeOccasion()
    {
        return $this->codeOccasion;
    }

    /**
     * Set entity
     *
     * @param \Fulldon\IntersaBundle\Entity\Entity $entity
     * @return Cause
     */
    public function setEntity(\Fulldon\IntersaBundle\Entity\Entity $entity = null)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get entity
     *
     * @return \Fulldon\IntersaBundle\Entity\Entity 
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set cible
     *
     * @param string $cible
     * @return Cause
     */
    public function setCible($cible)
    {
        $this->cible = $cible;

        return $this;
    }

    /**
     * Get cible
     *
     * @return string 
     */
    public function getCible()
    {
        return $this->cible;
    }

    /**
     * Set libelleEn
     *
     * @param string $libelleEn
     * @return Cause
     */
    public function setLibelleEn($libelleEn)
    {
        $this->libelleEn = $libelleEn;

        return $this;
    }

    /**
     * Get libelleEn
     *
     * @return string 
     */
    public function getLibelleEn()
    {
        return $this->libelleEn;
    }



    /**
     * Set dateDebutProjet
     *
     * @param \DateTime $dateDebutProjet
     * @return Cause
     */
    public function setDateDebutProjet($dateDebutProjet)
    {
        $this->dateDebutProjet = $dateDebutProjet;

        return $this;
    }

    /**
     * Get dateDebutProjet
     *
     * @return \DateTime 
     */
    public function getDateDebutProjet()
    {
        return $this->dateDebutProjet;
    }

    /**
     * Set dateFinProjet
     *
     * @param \DateTime $dateFinProjet
     * @return Cause
     */
    public function setDateFinProjet($dateFinProjet)
    {
        $this->dateFinProjet = $dateFinProjet;

        return $this;
    }

    /**
     * Get dateFinProjet
     *
     * @return \DateTime 
     */
    public function getDateFinProjet()
    {
        return $this->dateFinProjet;
    }

    /**
     * Set detailProjet
     *
     * @param string $detailProjet
     * @return Cause
     */
    public function setDetailProjet($detailProjet)
    {
        $this->detailProjet = $detailProjet;

        return $this;
    }

    /**
     * Get detailProjet
     *
     * @return string 
     */
    public function getDetailProjet()
    {
        return $this->detailProjet;
    }

    /**
     * Set budgetPrevisionnel
     *
     * @param string $budgetPrevisionnel
     * @return Cause
     */
    public function setBudgetPrevisionnel($budgetPrevisionnel)
    {
        $this->budgetPrevisionnel = $budgetPrevisionnel;

        return $this;
    }

    /**
     * Get budgetPrevisionnel
     *
     * @return string 
     */
    public function getBudgetPrevisionnel()
    {
        return $this->budgetPrevisionnel;
    }

    /**
     * Set artiste
     *
     * @param \Fulldon\DonateurBundle\Entity\Donateur $artiste
     *
     * @return Cause
     */
    public function setArtiste(\Fulldon\DonateurBundle\Entity\Donateur $artiste = null)
    {
        $this->artiste = $artiste;

        return $this;
    }

    /**
     * Get artiste
     *
     * @return \Fulldon\DonateurBundle\Entity\Donateur
     */
    public function getArtiste()
    {
        return $this->artiste;
    }

    /**
     * Set hote
     *
     * @param \Fulldon\DonateurBundle\Entity\Donateur $hote
     *
     * @return Cause
     */
    public function setHote(\Fulldon\DonateurBundle\Entity\Donateur $hote = null)
    {
        $this->hote = $hote;

        return $this;
    }

    /**
     * Get hote
     *
     * @return \Fulldon\DonateurBundle\Entity\Donateur
     */
    public function getHote()
    {
        return $this->hote;
    }

    /**
     * Set dateConcert
     *
     * @param \DateTime $dateConcert
     *
     * @return Cause
     */
    public function setDateConcert($dateConcert)
    {
        $this->dateConcert = $dateConcert;

        return $this;
    }

    /**
     * Get dateConcert
     *
     * @return \DateTime
     */
    public function getDateConcert()
    {
        return $this->dateConcert;
    }

    /**
     * Set lieuConcert
     *
     * @param string $lieuConcert
     *
     * @return Cause
     */
    public function setLieuConcert($lieuConcert)
    {
        $this->lieuConcert = $lieuConcert;

        return $this;
    }

    /**
     * Get lieuConcert
     *
     * @return string
     */
    public function getLieuConcert()
    {
        return $this->lieuConcert;
    }
}

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
use Fulldon\IntersaBundle\Event\HistoryLogEvent;
use Fulldon\IntersaBundle\Listener;
use Fulldon\IntersaBundle\Event\LogVar;
use Fulldon\IntersaBundle\Entity\Log;

/**
 * @ORM\Entity(repositoryClass="Fulldon\DonateurBundle\Entity\DonateurRepository")
 * @ORM\Table(name="donateur", indexes={
 * @ORM\Index(name="search_donateur_idx", columns={"id", "nom", "prenom", "email", "pays_id", "ville_id", "id_foyer"}),
 * @ORM\Index(name="stat_donateur_idx", columns={"id", "email", "telephone_mobile", "telephone_fixe", "removed"}),
 * @ORM\Index(name="id_donateur_idx", columns={"id"}),
 * @ORM\Index(name="nom_donateur_idx", columns={"nom"}),
 * @ORM\Index(name="prenom_donateur_idx", columns={"prenom"}),
 * @ORM\Index(name="email_donateur_idx", columns={"email"}),
 * @ORM\Index(name="pays_donateur_idx", columns={"pays_id"}),
 * @ORM\Index(name="ville_donateur_idx", columns={"ville_id"}),
 * @ORM\Index(name="foyer_donateur_idx", columns={"id_foyer"}),
 * @ORM\Index(name="stat_rem_donateur_idx", columns={"removed"}),
 * @ORM\Index(name="stat_donateur_idx", columns={"motif_donateur_id","removed"})
 * })
 * @ORM\HasLifecycleCallbacks
 *
 */
class Donateur {

    //CONST _LOG_TYPE_INFO_DONATEUR_ = 1;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="nom", type="string", length=255, nullable=true)
     */
    private $nom;

    /**
     * @ORM\Column(name="prenom", type="string", length=255, nullable=true)
     */
    private $prenom;

    /**
     * @ORM\Column(name="civilite", type="string", length=255, nullable=true)
     */
    private $civilite;

    /**
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(name="datenaissance", type="date",nullable=true)
     */
    private $dateNaissance;

    /**
     * @ORM\Column(name="iso_pays", type="string", length=255, nullable=true)
     */
    private $isopays;

    /**
     * @ORM\Column(name="iso_ville", type="string", length=255, nullable=true)
     */
    private $isoville;

    /**
     * @ORM\ManyToOne(targetEntity="Pays")
     * @ORM\JoinColumn(nullable=true, name="pays_id" )
     */
    private $pays;

    /**
     * @ORM\ManyToOne(targetEntity="Ville")
     * @ORM\JoinColumn(nullable=true, name="ville_id"  )
     */
    private $ville;

    /**
     * @ORM\Column(name="adresse1", type="text", nullable=true)
     */
    private $adresse1;

    /**
     * @ORM\Column(name="adresse2", type="text",nullable=true)
     */
    private $adresse2;

    /**
     * @ORM\Column(name="adresse3", type="text",nullable=true)
     */
    private $adresse3;

    /**
     * @ORM\Column(name="adresse4", type="text",nullable=true)
     */
    private $adresse4;

    /**
     * @ORM\Column(name="adresse5", type="text",  nullable=true)
     */
    private $adresse5 = null;

    /**
     * @ORM\Column(name="telephone_mobile", type="text",  nullable=true)
     */
    private $telephoneMobile;

    /**
     * @ORM\Column(name="telephone_fixe", type="text",  nullable=true)
     */
    private $telephoneFixe;

    /**
     * @ORM\ManyToMany(targetEntity="ReceptionMode" , cascade={"persist"})
     */
    private $receptionMode;

    /**
     * @ORM\ManyToMany(targetEntity="CategoryDonateur", inversedBy="donateurs")
     * @ORM\JoinColumn(nullable=true);
     */
    private $categories;

    /**
     * @ORM\Column(name="zipcode", type="string", length=7, nullable=true)
     */
    private $zipcode;

    /**
     * @ORM\Column(name="allow_rf", type="boolean" )
     */
    private $allowRf;

    /**
     * @ORM\OneToOne(targetEntity="Fulldon\SecurityBundle\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id",nullable=true);
     */
    private $user;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(name="removed", type="boolean" )
     */
    private $removed;

    /**
     * @ORM\Column(name="removed_at", type="datetime", nullable=true)
     */
    protected $removedAt;

    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\MotifDisableDonateur")
     * @ORM\JoinColumn( name="motif_donateur_id", nullable=true);
     */
    protected $motif;

    /**
     * @ORM\Column(name="ref_donateur", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $refDonateur;

    /**
     * @ORM\ManyToOne(targetEntity="SituationFamiliale")
     * @ORM\JoinColumn(nullable=true, name="situationfamiliale_id" )
     */
    private $situationFamiliale;

    /**
     * @ORM\ManyToOne(targetEntity="SituationProfessionnelle")
     * @ORM\JoinColumn(nullable=true, name="situationpro_id" )
     */
    private $situationProfessionnelle;

    /**
     * @ORM\Column(name="enfants", type="string", length=7, nullable=true)
     */
    private $enfants;

    /**
     * @ORM\Column(name="fourchette_min", type="string", length=7, nullable=true)
     */
    private $fourchetteMin;

    /**
     * @ORM\Column(name="fourchette_max", type="string", length=7, nullable=true)
     */
    private $fourchetteMax;

    /**
     * @ORM\ManyToOne(targetEntity="StatutDonateur")
     * @ORM\JoinColumn(nullable=true, name="statut_donateur" )
     */
    private $statutDonateur;

    /**
     * @ORM\Column(name="commentaire", type="text", nullable=true)
     */
    private $commentaire;

    /**
     * @ORM\Column(name="nom_entreprise", type="string", nullable=true)
     */
    private $nomEntreprise;
    
     
   /**
     *@ORM\ManyToOne(targetEntity="FoyerDonateur")
     * @ORM\JoinColumn(nullable=true, name="id_foyer")
     */
    private $foyer;
    
    
//    public function __construct() {
//        $this->createdAt = new \Datetime();
//        $this->receptionMode = new ArrayCollection();
//        $this->categories = new ArrayCollection();
//       // $this->foyer = new ArrayCollection();
//    }

    
//    public function __toString() {
//        return (string) $this->getFoyer();
//    }
    /**
     * Constructor
     */
    public function __construct()
    {
         $this->createdAt = new \Datetime();
        $this->receptionMode = new \Doctrine\Common\Collections\ArrayCollection();
        $this->categories = new \Doctrine\Common\Collections\ArrayCollection();
        $this->foyer = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set nom
     *
     * @param string $nom
     *
     * @return Donateur
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set prenom
     *
     * @param string $prenom
     *
     * @return Donateur
     */
    public function setPrenom($prenom)
    {
        $this->prenom = $prenom;

        return $this;
    }

    /**
     * Get prenom
     *
     * @return string
     */
    public function getPrenom()
    {
        return $this->prenom;
    }

    /**
     * Set civilite
     *
     * @param string $civilite
     *
     * @return Donateur
     */
    public function setCivilite($civilite)
    {
        $this->civilite = $civilite;

        return $this;
    }

    /**
     * Get civilite
     *
     * @return string
     */
    public function getCivilite()
    {
        return $this->civilite;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Donateur
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set dateNaissance
     *
     * @param \DateTime $dateNaissance
     *
     * @return Donateur
     */
    public function setDateNaissance($dateNaissance)
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    /**
     * Get dateNaissance
     *
     * @return \DateTime
     */
    public function getDateNaissance()
    {
        return $this->dateNaissance;
    }

    /**
     * Set isopays
     *
     * @param string $isopays
     *
     * @return Donateur
     */
    public function setIsopays($isopays)
    {
        $this->isopays = $isopays;

        return $this;
    }

    /**
     * Get isopays
     *
     * @return string
     */
    public function getIsopays()
    {
        return $this->isopays;
    }

    /**
     * Set isoville
     *
     * @param string $isoville
     *
     * @return Donateur
     */
    public function setIsoville($isoville)
    {
        $this->isoville = $isoville;

        return $this;
    }

    /**
     * Get isoville
     *
     * @return string
     */
    public function getIsoville()
    {
        return $this->isoville;
    }

    /**
     * Set adresse1
     *
     * @param string $adresse1
     *
     * @return Donateur
     */
    public function setAdresse1($adresse1)
    {
        $this->adresse1 = $adresse1;

        return $this;
    }

    /**
     * Get adresse1
     *
     * @return string
     */
    public function getAdresse1()
    {
        return $this->adresse1;
    }

    /**
     * Set adresse2
     *
     * @param string $adresse2
     *
     * @return Donateur
     */
    public function setAdresse2($adresse2)
    {
        $this->adresse2 = $adresse2;

        return $this;
    }

    /**
     * Get adresse2
     *
     * @return string
     */
    public function getAdresse2()
    {
        return $this->adresse2;
    }

    /**
     * Set adresse3
     *
     * @param string $adresse3
     *
     * @return Donateur
     */
    public function setAdresse3($adresse3)
    {
        $this->adresse3 = $adresse3;

        return $this;
    }

    /**
     * Get adresse3
     *
     * @return string
     */
    public function getAdresse3()
    {
        return $this->adresse3;
    }

    /**
     * Set adresse4
     *
     * @param string $adresse4
     *
     * @return Donateur
     */
    public function setAdresse4($adresse4)
    {
        $this->adresse4 = $adresse4;

        return $this;
    }

    /**
     * Get adresse4
     *
     * @return string
     */
    public function getAdresse4()
    {
        return $this->adresse4;
    }

    /**
     * Set adresse5
     *
     * @param string $adresse5
     *
     * @return Donateur
     */
    public function setAdresse5($adresse5)
    {
        $this->adresse5 = $adresse5;

        return $this;
    }

    /**
     * Get adresse5
     *
     * @return string
     */
    public function getAdresse5()
    {
        return $this->adresse5;
    }

    /**
     * Set telephoneMobile
     *
     * @param string $telephoneMobile
     *
     * @return Donateur
     */
    public function setTelephoneMobile($telephoneMobile)
    {
        $this->telephoneMobile = $telephoneMobile;

        return $this;
    }

    /**
     * Get telephoneMobile
     *
     * @return string
     */
    public function getTelephoneMobile()
    {
        return $this->telephoneMobile;
    }

    /**
     * Set telephoneFixe
     *
     * @param string $telephoneFixe
     *
     * @return Donateur
     */
    public function setTelephoneFixe($telephoneFixe)
    {
        $this->telephoneFixe = $telephoneFixe;

        return $this;
    }

    /**
     * Get telephoneFixe
     *
     * @return string
     */
    public function getTelephoneFixe()
    {
        return $this->telephoneFixe;
    }

    /**
     * Set zipcode
     *
     * @param string $zipcode
     *
     * @return Donateur
     */
    public function setZipcode($zipcode)
    {
        $this->zipcode = $zipcode;

        return $this;
    }

    /**
     * Get zipcode
     *
     * @return string
     */
    public function getZipcode()
    {
        return $this->zipcode;
    }

    /**
     * Set allowRf
     *
     * @param boolean $allowRf
     *
     * @return Donateur
     */
    public function setAllowRf($allowRf)
    {
        $this->allowRf = $allowRf;

        return $this;
    }

    /**
     * Get allowRf
     *
     * @return boolean
     */
    public function getAllowRf()
    {
        return $this->allowRf;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Donateur
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
     * Set removed
     *
     * @param boolean $removed
     *
     * @return Donateur
     */
    public function setRemoved($removed)
    {
        $this->removed = $removed;

        return $this;
    }

    /**
     * Get removed
     *
     * @return boolean
     */
    public function getRemoved()
    {
        return $this->removed;
    }

    /**
     * Set removedAt
     *
     * @param \DateTime $removedAt
     *
     * @return Donateur
     */
    public function setRemovedAt($removedAt)
    {
        $this->removedAt = $removedAt;

        return $this;
    }

    /**
     * Get removedAt
     *
     * @return \DateTime
     */
    public function getRemovedAt()
    {
        return $this->removedAt;
    }

    /**
     * Set refDonateur
     *
     * @param integer $refDonateur
     *
     * @return Donateur
     */
    public function setRefDonateur($refDonateur)
    {
        $this->refDonateur = $refDonateur;

        return $this;
    }

    /**
     * Get refDonateur
     *
     * @return integer
     */
    public function getRefDonateur()
    {
        return $this->refDonateur;
    }

    /**
     * Set enfants
     *
     * @param string $enfants
     *
     * @return Donateur
     */
    public function setEnfants($enfants)
    {
        $this->enfants = $enfants;

        return $this;
    }

    /**
     * Get enfants
     *
     * @return string
     */
    public function getEnfants()
    {
        return $this->enfants;
    }

    /**
     * Set fourchetteMin
     *
     * @param string $fourchetteMin
     *
     * @return Donateur
     */
    public function setFourchetteMin($fourchetteMin)
    {
        $this->fourchetteMin = $fourchetteMin;

        return $this;
    }

    /**
     * Get fourchetteMin
     *
     * @return string
     */
    public function getFourchetteMin()
    {
        return $this->fourchetteMin;
    }

    /**
     * Set fourchetteMax
     *
     * @param string $fourchetteMax
     *
     * @return Donateur
     */
    public function setFourchetteMax($fourchetteMax)
    {
        $this->fourchetteMax = $fourchetteMax;

        return $this;
    }

    /**
     * Get fourchetteMax
     *
     * @return string
     */
    public function getFourchetteMax()
    {
        return $this->fourchetteMax;
    }

    /**
     * Set commentaire
     *
     * @param string $commentaire
     *
     * @return Donateur
     */
    public function setCommentaire($commentaire)
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    /**
     * Get commentaire
     *
     * @return string
     */
    public function getCommentaire()
    {
        return $this->commentaire;
    }

    /**
     * Set nomEntreprise
     *
     * @param string $nomEntreprise
     *
     * @return Donateur
     */
    public function setNomEntreprise($nomEntreprise)
    {
        $this->nomEntreprise = $nomEntreprise;

        return $this;
    }

    /**
     * Get nomEntreprise
     *
     * @return string
     */
    public function getNomEntreprise()
    {
        return $this->nomEntreprise;
    }

    /**
     * Set pays
     *
     * @param \Fulldon\DonateurBundle\Entity\Pays $pays
     *
     * @return Donateur
     */
    public function setPays(\Fulldon\DonateurBundle\Entity\Pays $pays = null)
    {
        $this->pays = $pays;

        return $this;
    }

    /**
     * Get pays
     *
     * @return \Fulldon\DonateurBundle\Entity\Pays
     */
    public function getPays()
    {
        return $this->pays;
    }

    /**
     * Set ville
     *
     * @param \Fulldon\DonateurBundle\Entity\Ville $ville
     *
     * @return Donateur
     */
    public function setVille(\Fulldon\DonateurBundle\Entity\Ville $ville = null)
    {
        $this->ville = $ville;

        return $this;
    }

    /**
     * Get ville
     *
     * @return \Fulldon\DonateurBundle\Entity\Ville
     */
    public function getVille()
    {
        return $this->ville;
    }

    /**
     * Add receptionMode
     *
     * @param \Fulldon\DonateurBundle\Entity\ReceptionMode $receptionMode
     *
     * @return Donateur
     */
    public function addReceptionMode(\Fulldon\DonateurBundle\Entity\ReceptionMode $receptionMode)
    {
        $this->receptionMode[] = $receptionMode;

        return $this;
    }

    /**
     * Remove receptionMode
     *
     * @param \Fulldon\DonateurBundle\Entity\ReceptionMode $receptionMode
     */
    public function removeReceptionMode(\Fulldon\DonateurBundle\Entity\ReceptionMode $receptionMode)
    {
        $this->receptionMode->removeElement($receptionMode);
    }

    /**
     * Get receptionMode
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReceptionMode()
    {
        return $this->receptionMode;
    }

    /**
     * Add category
     *
     * @param \Fulldon\DonateurBundle\Entity\CategoryDonateur $category
     *
     * @return Donateur
     */
    public function addCategory(\Fulldon\DonateurBundle\Entity\CategoryDonateur $category)
    {
        $this->categories[] = $category;

        return $this;
    }

    /**
     * Remove category
     *
     * @param \Fulldon\DonateurBundle\Entity\CategoryDonateur $category
     */
    public function removeCategory(\Fulldon\DonateurBundle\Entity\CategoryDonateur $category)
    {
        $this->categories->removeElement($category);
    }

    /**
     * Get categories
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Set user
     *
     * @param \Fulldon\SecurityBundle\Entity\User $user
     *
     * @return Donateur
     */
    public function setUser(\Fulldon\SecurityBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Fulldon\SecurityBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set motif
     *
     * @param \Fulldon\DonateurBundle\Entity\MotifDisableDonateur $motif
     *
     * @return Donateur
     */
    public function setMotif(\Fulldon\DonateurBundle\Entity\MotifDisableDonateur $motif = null)
    {
        $this->motif = $motif;

        return $this;
    }

    /**
     * Get motif
     *
     * @return \Fulldon\DonateurBundle\Entity\MotifDisableDonateur
     */
    public function getMotif()
    {
        return $this->motif;
    }

    /**
     * Set situationFamiliale
     *
     * @param \Fulldon\DonateurBundle\Entity\SituationFamiliale $situationFamiliale
     *
     * @return Donateur
     */
    public function setSituationFamiliale(\Fulldon\DonateurBundle\Entity\SituationFamiliale $situationFamiliale = null)
    {
        $this->situationFamiliale = $situationFamiliale;

        return $this;
    }

    /**
     * Get situationFamiliale
     *
     * @return \Fulldon\DonateurBundle\Entity\SituationFamiliale
     */
    public function getSituationFamiliale()
    {
        return $this->situationFamiliale;
    }

    /**
     * Set situationProfessionnelle
     *
     * @param \Fulldon\DonateurBundle\Entity\SituationProfessionnelle $situationProfessionnelle
     *
     * @return Donateur
     */
    public function setSituationProfessionnelle(\Fulldon\DonateurBundle\Entity\SituationProfessionnelle $situationProfessionnelle = null)
    {
        $this->situationProfessionnelle = $situationProfessionnelle;

        return $this;
    }

    /**
     * Get situationProfessionnelle
     *
     * @return \Fulldon\DonateurBundle\Entity\SituationProfessionnelle
     */
    public function getSituationProfessionnelle()
    {
        return $this->situationProfessionnelle;
    }

    /**
     * Set statutDonateur
     *
     * @param \Fulldon\DonateurBundle\Entity\StatutDonateur $statutDonateur
     *
     * @return Donateur
     */
    public function setStatutDonateur(\Fulldon\DonateurBundle\Entity\StatutDonateur $statutDonateur = null)
    {
        $this->statutDonateur = $statutDonateur;

        return $this;
    }

    /**
     * Get statutDonateur
     *
     * @return \Fulldon\DonateurBundle\Entity\StatutDonateur
     */
    public function getStatutDonateur()
    {
        return $this->statutDonateur;
    }

    /**
     * Set foyer
     *
     * @param \Fulldon\DonateurBundle\Entity\FoyerDonateur $foyer
     *
     * @return Donateur
     */
    public function setFoyer(\Fulldon\DonateurBundle\Entity\FoyerDonateur $foyer = null)
    {
        $this->foyer = $foyer;

        return $this;
    }

    /**
     * Get foyer
     *
     * @return \Fulldon\DonateurBundle\Entity\FoyerDonateur
     */
    public function getFoyer()
    {
        return $this->foyer;
    }
}

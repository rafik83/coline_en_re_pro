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

/**
 * @ORM\Table(name="personnalisation")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Fulldon\IntersaBundle\Entity\PersonnalisationRepository")
 */
class Personnalisation
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="couleur", type="string", length=100, nullable=true)
     */
    private $couleur;
    /**
     * @ORM\Column(name="logo", type="string", length=255,nullable=true)
     */
    private $logo;
    /**
     * @ORM\Column(name="header_page_don", type="string", length=255, nullable=true)
     */
    private $headerPage;
    /**
     * @ORM\Column(name="fond_page", type="string", length=255, nullable=true)
     */
    private $fondPage;
    /**
     * @ORM\Column(name="presentation", type="text", nullable=true)
     */
    private $presentation;
    /**
     * @ORM\Column(name="presentation_en", type="text", nullable=true)
     */
    private $presentationEn;
    /**
     * @ORM\Column(name="adresse_assoc", type="text", nullable=true)
     */
    private $adresseAssoc;
    /**
     * @ORM\Column(name="telephone", type="string", length=15, nullable=true)
     */
    private $telephone;
    /**
     * @ORM\Column(name="rf_message", type="text", nullable=true)
     */
    private $rfMessage;
    /**
     * @ORM\Column(name="rf_message_en", type="text", nullable=true)
     */
    private $rfMessageEn;
    /**
     * @ORM\Column(name="amounts_p", type="string", length=255, nullable=true)
     */
    private $montantP;
    /**
     * @ORM\Column(name="amounts_r", type="string", length=255, nullable=true)
     */
    private $montantR;
    /**
     * @ORM\Column(name="amounts_ar", type="string", length=255, nullable=true)
     */
    private $montantAR;
    /**
     * @ORM\OneToOne(targetEntity="Fulldon\IntersaBundle\Entity\Persod", cascade={"persist"})
     * @ORM\JoinColumn(name="perso_page_don",nullable=true);
     */
    private $Persod;
    /**
     * @ORM\OneToOne(targetEntity="Fulldon\IntersaBundle\Entity\Persoa", cascade={"persist"})
     * @ORM\JoinColumn(name="perso_page_adherent",nullable=true);
     */
    private $Persoa;

    /**
     * @ORM\Column(name="couleur_adh", type="string", length=100, nullable=true)
     */
    private $couleurAdh;

    public function getFullImagePath() {
        return null === $this->logo ? null : $this->getUploadRootDir().$this->logo;
    }
    public function getFullHeaderPagePath() {
        return null === $this->headerPage ? null : $this->getUploadRootDir().$this->headerPage;
    }
    public function getFullFondPagePath() {
        return null === $this->fondPage ? null : $this->getUploadRootDir().$this->fondPage;
    }
    public function getUploadRootDir() {
        return $this->getTmpUploadRootDir().$this->getId()."/";
    }
    public function getTmpUploadRootDir() {
        return __DIR__ .'/../../../../web/upload/';
    }
    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function uploadImage() {
        if(null === $this->logo || !is_object($this->logo)) {
            return ;
        }

        if(!$this->id) {
            $this->logo->move($this->getTmpUploadRootDir(), $this->logo->getClientOriginalName());
        } else {
            $this->logo->move($this->getUploadRootDir(), $this->logo->getClientOriginalName());
        }
        $this->setLogo($this->logo->getClientOriginalName());
    }
    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function uploadFondPage() {
        if(null === $this->fondPage || !is_object($this->fondPage)) {
            return ;
        }

        if(!$this->id) {
            $this->fondPage->move($this->getTmpUploadRootDir(), $this->fondPage->getClientOriginalName());
        } else {
            $this->fondPage->move($this->getUploadRootDir(), $this->fondPage->getClientOriginalName());
        }
        $this->setFondPage($this->fondPage->getClientOriginalName());
    }
    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function uploadHeaderPage() {
        if(null === $this->headerPage || !is_object($this->headerPage)) {
            return ;
        }

        if(!$this->id) {
            $this->headerPage->move($this->getTmpUploadRootDir(), $this->headerPage->getClientOriginalName());
        } else {
            $this->headerPage->move($this->getUploadRootDir(), $this->headerPage->getClientOriginalName());
        }

        $this->setHeaderPage($this->headerPage->getClientOriginalName());
    }
    /**
     * @ORM\PostPersist
     */
    public function moveImage()
    {
        if(null === $this->logo) {
            return;
        }
        if(!is_dir($this->getUploadRootDir())){
            mkdir($this->getUploadRootDir());
        }
        copy($this->getTmpUploadRootDir().$this->logo, $this->getFullImagePath());
        unlink($this->getTmpUploadRootDir().$this->logo);
    }
    /**
     * @ORM\PostPersist
     */
    public function moveFondPage()
    {
        if(null === $this->fondPage) {
            return;
        }
        if(!is_dir($this->getUploadRootDir())){
            mkdir($this->getUploadRootDir());
        }
        copy($this->getTmpUploadRootDir().$this->fondPage, $this->getFullFondPagePath());
        unlink($this->getTmpUploadRootDir().$this->fondPage);
    }
    /**
     * @ORM\PostPersist
     */
    public function moveHeaderPage()
    {
        if(null === $this->headerPage) {
            return;
        }
        if(!is_dir($this->getUploadRootDir())){
            mkdir($this->getUploadRootDir());
        }
        copy($this->getTmpUploadRootDir().$this->headerPage, $this->getFullHeaderPagePath());
        unlink($this->getTmpUploadRootDir().$this->headerPage);
    }
    /**
     * @ORM\PreRemove()
     */
    public function removeImage()
    {
        unlink($this->getFullImagePath());
        rmdir($this->getUploadRootDir());
    }
    /**
     * @ORM\PreRemove()
     */
    public function removeFondPage()
    {
        unlink($this->getFullFondPagePath());
        rmdir($this->getUploadRootDir());
    }
    /**
     * @ORM\PreRemove()
     */
    public function removeHeaderPage()
    {
        unlink($this->getFullHeaderPagePath());
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
     * Set couleur
     *
     * @param string $couleur
     * @return Personnalisation
     */
    public function setCouleur($couleur)
    {
        $this->couleur = $couleur;

        return $this;
    }

    /**
     * Get couleur
     *
     * @return string 
     */
    public function getCouleur()
    {
        return $this->couleur;
    }

    /**
     * Set logo
     *
     * @param \file $logo
     * @return Personnalisation
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo
     *
     * @return \file 
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Set presentation
     *
     * @param string $presentation
     * @return Personnalisation
     */
    public function setPresentation($presentation)
    {
        $this->presentation = $presentation;

        return $this;
    }

    /**
     * Get presentation
     *
     * @return string 
     */
    public function getPresentation()
    {
        return $this->presentation;
    }


    /**
     * Set presentationEn
     *
     * @param string $presentationEn
     * @return Personnalisation
     */
    public function setPresentationEn($presentationEn)
    {
        $this->presentationEn = $presentationEn;

        return $this;
    }

    /**
     * Get presentationEn
     *
     * @return string 
     */
    public function getPresentationEn()
    {
        return $this->presentationEn;
    }

    /**
     * Set adresseAssoc
     *
     * @param string $adresseAssoc
     * @return Personnalisation
     */
    public function setAdresseAssoc($adresseAssoc)
    {
        $this->adresseAssoc = $adresseAssoc;

        return $this;
    }

    /**
     * Get adresseAssoc
     *
     * @return string 
     */
    public function getAdresseAssoc()
    {
        return $this->adresseAssoc;
    }

    /**
     * Set telephone
     *
     * @param string $telephone
     * @return Personnalisation
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * Get telephone
     *
     * @return string 
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * Set rfMessage
     *
     * @param string $rfMessage
     * @return Personnalisation
     */
    public function setRfMessage($rfMessage)
    {
        $this->rfMessage = $rfMessage;

        return $this;
    }

    /**
     * Get rfMessage
     *
     * @return string 
     */
    public function getRfMessage()
    {
        return $this->rfMessage;
    }

    /**
     * Set rfMessageEn
     *
     * @param string $rfMessageEn
     * @return Personnalisation
     */
    public function setRfMessageEn($rfMessageEn)
    {
        $this->rfMessageEn = $rfMessageEn;

        return $this;
    }

    /**
     * Get rfMessageEn
     *
     * @return string 
     */
    public function getRfMessageEn()
    {
        return $this->rfMessageEn;
    }

    /**
     * Set headerPage
     *
     * @param string $headerPage
     * @return Personnalisation
     */
    public function setHeaderPage($headerPage)
    {
        $this->headerPage = $headerPage;

        return $this;
    }

    /**
     * Get headerPage
     *
     * @return string 
     */
    public function getHeaderPage()
    {
        return $this->headerPage;
    }

    /**
     * Set fondPage
     *
     * @param string $fondPage
     * @return Personnalisation
     */
    public function setFondPage($fondPage)
    {
        $this->fondPage = $fondPage;

        return $this;
    }

    /**
     * Get fondPage
     *
     * @return string 
     */
    public function getFondPage()
    {
        return $this->fondPage;
    }

    /**
     * Set montantP
     *
     * @param string $montantP
     *
     * @return Personnalisation
     */
    public function setMontantP($montantP)
    {
        $this->montantP = $montantP;

        return $this;
    }

    /**
     * Get montantP
     *
     * @return string
     */
    public function getMontantP()
    {
        return $this->montantP;
    }

    /**
     * Set montantR
     *
     * @param string $montantR
     *
     * @return Personnalisation
     */
    public function setMontantR($montantR)
    {
        $this->montantR = $montantR;

        return $this;
    }

    /**
     * Get montantR
     *
     * @return string
     */
    public function getMontantR()
    {
        return $this->montantR;
    }

    /**
     * Set dc2title1
     *
     * @param string $dc2title1
     *
     * @return Personnalisation
     */
    public function setDc2title1($dc2title1)
    {
        $this->dc2title1 = $dc2title1;

        return $this;
    }

    /**
     * Get dc2title1
     *
     * @return string
     */
    public function getDc2title1()
    {
        return $this->dc2title1;
    }

    /**
     * Set dc3block1
     *
     * @param string $dc3block1
     *
     * @return Personnalisation
     */
    public function setDc3block1($dc3block1)
    {
        $this->dc3block1 = $dc3block1;

        return $this;
    }

    /**
     * Get dc3block1
     *
     * @return string
     */
    public function getDc3block1()
    {
        return $this->dc3block1;
    }

    /**
     * Set dc1footer
     *
     * @param string $dc1footer
     *
     * @return Personnalisation
     */
    public function setDc1footer($dc1footer)
    {
        $this->dc1footer = $dc1footer;

        return $this;
    }

    /**
     * Get dc1footer
     *
     * @return string
     */
    public function getDc1footer()
    {
        return $this->dc1footer;
    }

    /**
     * Set dc2footer
     *
     * @param string $dc2footer
     *
     * @return Personnalisation
     */
    public function setDc2footer($dc2footer)
    {
        $this->dc2footer = $dc2footer;

        return $this;
    }

    /**
     * Get dc2footer
     *
     * @return string
     */
    public function getDc2footer()
    {
        return $this->dc2footer;
    }

    /**
     * Set dc1footertitle
     *
     * @param string $dc1footertitle
     *
     * @return Personnalisation
     */
    public function setDc1footertitle($dc1footertitle)
    {
        $this->dc1footertitle = $dc1footertitle;

        return $this;
    }

    /**
     * Get dc1footertitle
     *
     * @return string
     */
    public function getDc1footertitle()
    {
        return $this->dc1footertitle;
    }

    /**
     * Set dc2footertitle
     *
     * @param string $dc2footertitle
     *
     * @return Personnalisation
     */
    public function setDc2footertitle($dc2footertitle)
    {
        $this->dc2footertitle = $dc2footertitle;

        return $this;
    }

    /**
     * Get dc2footertitle
     *
     * @return string
     */
    public function getDc2footertitle()
    {
        return $this->dc2footertitle;
    }

    /**
     * Set ac2title1
     *
     * @param string $ac2title1
     *
     * @return Personnalisation
     */
    public function setAc2title1($ac2title1)
    {
        $this->ac2title1 = $ac2title1;

        return $this;
    }

    /**
     * Get ac2title1
     *
     * @return string
     */
    public function getAc2title1()
    {
        return $this->ac2title1;
    }

    /**
     * Set ac2block1
     *
     * @param string $ac2block1
     *
     * @return Personnalisation
     */
    public function setAc2block1($ac2block1)
    {
        $this->ac2block1 = $ac2block1;

        return $this;
    }

    /**
     * Get ac2block1
     *
     * @return string
     */
    public function getAc2block1()
    {
        return $this->ac2block1;
    }

    /**
     * Set ac2block2
     *
     * @param string $ac2block2
     *
     * @return Personnalisation
     */
    public function setAc2block2($ac2block2)
    {
        $this->ac2block2 = $ac2block2;

        return $this;
    }

    /**
     * Get ac2block2
     *
     * @return string
     */
    public function getAc2block2()
    {
        return $this->ac2block2;
    }

    /**
     * Set ac3block1
     *
     * @param string $ac3block1
     *
     * @return Personnalisation
     */
    public function setAc3block1($ac3block1)
    {
        $this->ac3block1 = $ac3block1;

        return $this;
    }

    /**
     * Get ac3block1
     *
     * @return string
     */
    public function getAc3block1()
    {
        return $this->ac3block1;
    }

    /**
     * Set ac1footer
     *
     * @param string $ac1footer
     *
     * @return Personnalisation
     */
    public function setAc1footer($ac1footer)
    {
        $this->ac1footer = $ac1footer;

        return $this;
    }

    /**
     * Get ac1footer
     *
     * @return string
     */
    public function getAc1footer()
    {
        return $this->ac1footer;
    }

    /**
     * Set ac2footer
     *
     * @param string $ac2footer
     *
     * @return Personnalisation
     */
    public function setAc2footer($ac2footer)
    {
        $this->ac2footer = $ac2footer;

        return $this;
    }

    /**
     * Get ac2footer
     *
     * @return string
     */
    public function getAc2footer()
    {
        return $this->ac2footer;
    }

    /**
     * Set ac1footertitle
     *
     * @param string $ac1footertitle
     *
     * @return Personnalisation
     */
    public function setAc1footertitle($ac1footertitle)
    {
        $this->ac1footertitle = $ac1footertitle;

        return $this;
    }

    /**
     * Get ac1footertitle
     *
     * @return string
     */
    public function getAc1footertitle()
    {
        return $this->ac1footertitle;
    }

    /**
     * Set ac2footertitle
     *
     * @param string $ac2footertitle
     *
     * @return Personnalisation
     */
    public function setAc2footertitle($ac2footertitle)
    {
        $this->ac2footertitle = $ac2footertitle;

        return $this;
    }

    /**
     * Get ac2footertitle
     *
     * @return string
     */
    public function getAc2footertitle()
    {
        return $this->ac2footertitle;
    }

    /**
     * Set dc2title1En
     *
     * @param string $dc2title1En
     *
     * @return Personnalisation
     */
    public function setDc2title1En($dc2title1En)
    {
        $this->dc2title1En = $dc2title1En;

        return $this;
    }

    /**
     * Get dc2title1En
     *
     * @return string
     */
    public function getDc2title1En()
    {
        return $this->dc2title1En;
    }

    /**
     * Set dc3block1En
     *
     * @param string $dc3block1En
     *
     * @return Personnalisation
     */
    public function setDc3block1En($dc3block1En)
    {
        $this->dc3block1En = $dc3block1En;

        return $this;
    }

    /**
     * Get dc3block1En
     *
     * @return string
     */
    public function getDc3block1En()
    {
        return $this->dc3block1En;
    }

    /**
     * Set dc1footerEn
     *
     * @param string $dc1footerEn
     *
     * @return Personnalisation
     */
    public function setDc1footerEn($dc1footerEn)
    {
        $this->dc1footerEn = $dc1footerEn;

        return $this;
    }

    /**
     * Get dc1footerEn
     *
     * @return string
     */
    public function getDc1footerEn()
    {
        return $this->dc1footerEn;
    }

    /**
     * Set dc2footerEn
     *
     * @param string $dc2footerEn
     *
     * @return Personnalisation
     */
    public function setDc2footerEn($dc2footerEn)
    {
        $this->dc2footerEn = $dc2footerEn;

        return $this;
    }

    /**
     * Get dc2footerEn
     *
     * @return string
     */
    public function getDc2footerEn()
    {
        return $this->dc2footerEn;
    }

    /**
     * Set dc1footertitleEn
     *
     * @param string $dc1footertitleEn
     *
     * @return Personnalisation
     */
    public function setDc1footertitleEn($dc1footertitleEn)
    {
        $this->dc1footertitleEn = $dc1footertitleEn;

        return $this;
    }

    /**
     * Get dc1footertitleEn
     *
     * @return string
     */
    public function getDc1footertitleEn()
    {
        return $this->dc1footertitleEn;
    }

    /**
     * Set dc2footertitleEn
     *
     * @param string $dc2footertitleEn
     *
     * @return Personnalisation
     */
    public function setDc2footertitleEn($dc2footertitleEn)
    {
        $this->dc2footertitleEn = $dc2footertitleEn;

        return $this;
    }

    /**
     * Get dc2footertitleEn
     *
     * @return string
     */
    public function getDc2footertitleEn()
    {
        return $this->dc2footertitleEn;
    }

    /**
     * Set ac2title1En
     *
     * @param string $ac2title1En
     *
     * @return Personnalisation
     */
    public function setAc2title1En($ac2title1En)
    {
        $this->ac2title1En = $ac2title1En;

        return $this;
    }

    /**
     * Get ac2title1En
     *
     * @return string
     */
    public function getAc2title1En()
    {
        return $this->ac2title1En;
    }

    /**
     * Set ac2block1En
     *
     * @param string $ac2block1En
     *
     * @return Personnalisation
     */
    public function setAc2block1En($ac2block1En)
    {
        $this->ac2block1En = $ac2block1En;

        return $this;
    }

    /**
     * Get ac2block1En
     *
     * @return string
     */
    public function getAc2block1En()
    {
        return $this->ac2block1En;
    }

    /**
     * Set ac2block2En
     *
     * @param string $ac2block2En
     *
     * @return Personnalisation
     */
    public function setAc2block2En($ac2block2En)
    {
        $this->ac2block2En = $ac2block2En;

        return $this;
    }

    /**
     * Get ac2block2En
     *
     * @return string
     */
    public function getAc2block2En()
    {
        return $this->ac2block2En;
    }

    /**
     * Set ac3block1En
     *
     * @param string $ac3block1En
     *
     * @return Personnalisation
     */
    public function setAc3block1En($ac3block1En)
    {
        $this->ac3block1En = $ac3block1En;

        return $this;
    }

    /**
     * Get ac3block1En
     *
     * @return string
     */
    public function getAc3block1En()
    {
        return $this->ac3block1En;
    }

    /**
     * Set ac1footerEn
     *
     * @param string $ac1footerEn
     *
     * @return Personnalisation
     */
    public function setAc1footerEn($ac1footerEn)
    {
        $this->ac1footerEn = $ac1footerEn;

        return $this;
    }

    /**
     * Get ac1footerEn
     *
     * @return string
     */
    public function getAc1footerEn()
    {
        return $this->ac1footerEn;
    }

    /**
     * Set ac2footerEn
     *
     * @param string $ac2footerEn
     *
     * @return Personnalisation
     */
    public function setAc2footerEn($ac2footerEn)
    {
        $this->ac2footerEn = $ac2footerEn;

        return $this;
    }

    /**
     * Get ac2footerEn
     *
     * @return string
     */
    public function getAc2footerEn()
    {
        return $this->ac2footerEn;
    }

    /**
     * Set ac1footertitleEn
     *
     * @param string $ac1footertitleEn
     *
     * @return Personnalisation
     */
    public function setAc1footertitleEn($ac1footertitleEn)
    {
        $this->ac1footertitleEn = $ac1footertitleEn;

        return $this;
    }

    /**
     * Get ac1footertitleEn
     *
     * @return string
     */
    public function getAc1footertitleEn()
    {
        return $this->ac1footertitleEn;
    }

    /**
     * Set ac2footertitleEn
     *
     * @param string $ac2footertitleEn
     *
     * @return Personnalisation
     */
    public function setAc2footertitleEn($ac2footertitleEn)
    {
        $this->ac2footertitleEn = $ac2footertitleEn;

        return $this;
    }

    /**
     * Get ac2footertitleEn
     *
     * @return string
     */
    public function getAc2footertitleEn()
    {
        return $this->ac2footertitleEn;
    }

    /**
     * Set dc3secure
     *
     * @param string $dc3secure
     *
     * @return Personnalisation
     */
    public function setDc3secure($dc3secure)
    {
        $this->dc3secure = $dc3secure;

        return $this;
    }

    /**
     * Get dc3secure
     *
     * @return string
     */
    public function getDc3secure()
    {
        return $this->dc3secure;
    }

    /**
     * Set dc3secureEn
     *
     * @param string $dc3secureEn
     *
     * @return Personnalisation
     */
    public function setDc3secureEn($dc3secureEn)
    {
        $this->dc3secureEn = $dc3secureEn;

        return $this;
    }

    /**
     * Get dc3secureEn
     *
     * @return string
     */
    public function getDc3secureEn()
    {
        return $this->dc3secureEn;
    }

    /**
     * Set montantAR
     *
     * @param string $montantAR
     *
     * @return Personnalisation
     */
    public function setMontantAR($montantAR)
    {
        $this->montantAR = $montantAR;

        return $this;
    }

    /**
     * Get montantAR
     *
     * @return string
     */
    public function getMontantAR()
    {
        return $this->montantAR;
    }

    /**
     * Set couleurAdh
     *
     * @param string $couleurAdh
     *
     * @return Personnalisation
     */
    public function setCouleurAdh($couleurAdh)
    {
        $this->couleurAdh = $couleurAdh;

        return $this;
    }

    /**
     * Get couleurAdh
     *
     * @return string
     */
    public function getCouleurAdh()
    {
        return $this->couleurAdh;
    }

    /**
     * Set persod
     *
     * @param \Fulldon\IntersaBundle\Entity\Persod $persod
     *
     * @return Personnalisation
     */
    public function setPersod(\Fulldon\IntersaBundle\Entity\Persod $persod = null)
    {
        $this->Persod = $persod;

        return $this;
    }

    /**
     * Get persod
     *
     * @return \Fulldon\IntersaBundle\Entity\Persod
     */
    public function getPersod()
    {
        return $this->Persod;
    }

    /**
     * Set persoa
     *
     * @param \Fulldon\IntersaBundle\Entity\Persoa $persoa
     *
     * @return Personnalisation
     */
    public function setPersoa(\Fulldon\IntersaBundle\Entity\Persoa $persoa = null)
    {
        $this->Persoa = $persoa;

        return $this;
    }

    /**
     * Get persoa
     *
     * @return \Fulldon\IntersaBundle\Entity\Persoa
     */
    public function getPersoa()
    {
        return $this->Persoa;
    }
}

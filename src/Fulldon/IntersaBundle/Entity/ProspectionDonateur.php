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
use Fulldon\IntersaBundle\Listener;


/**
 * @ORM\Entity(repositoryClass="Fulldon\IntersaBundle\Entity\ProspectionDonateurRepository")
 * @ORM\Table(name="prospection_donateur")
 * @UniqueEntity(fields={"datamatrix"}, message="La DATAMATRIX deverait être unique")
 */
class ProspectionDonateur
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\Column(name="nom", type="string", length=255)
     */
    private $nom;
    /**
     * @ORM\Column(name="prenom", type="string", length=255)
     */
    private $prenom;
    /**
     * @ORM\Column(name="ville", type="text",nullable=true)
     */
    private $ville;
    /**
     * @ORM\Column(name="adresse1", type="text")
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
     * @ORM\Column(name="zipcode", type="string", length=7)
     */
    private $zipcode;
    /**
     * @ORM\Column(name="datamatrix", type="string", length=50)
      */
    private $datamatrix;
    /**
     * @ORM\ManyToOne(targetEntity="Fulldon\DonateurBundle\Entity\Cause")
     * @ORM\JoinColumn(nullable=true, name="cause_id" )
     */
    protected $cause;
    /**
     * @ORM\Column(name="filename", type="string", length=200)
     */
    private $filename;
    /**
     * @ORM\Column(name="line", type="integer", length=7)
     */
    private $line;



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
     * @return ProspectionDonateur
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
     * @return ProspectionDonateur
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
     * Set adresse1
     *
     * @param string $adresse1
     * @return ProspectionDonateur
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
     * @return ProspectionDonateur
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
     * @return ProspectionDonateur
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
     * @return ProspectionDonateur
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
     * Set zipcode
     *
     * @param string $zipcode
     * @return ProspectionDonateur
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
     * Set datamatrix
     *
     * @param string $datamatrix
     * @return ProspectionDonateur
     */
    public function setDatamatrix($datamatrix)
    {
        $this->datamatrix = $datamatrix;

        return $this;
    }

    /**
     * Get datamatrix
     *
     * @return string 
     */
    public function getDatamatrix()
    {
        return $this->datamatrix;
    }

    /**
     * Set ville
     *
     * @param \Fulldon\DonateurBundle\Entity\Ville $ville
     * @return ProspectionDonateur
     */
    public function setVille(\Fulldon\DonateurBundle\Entity\Ville $ville)
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
     * Set cause
     *
     * @param \Fulldon\DonateurBundle\Entity\Cause $cause
     * @return ProspectionDonateur
     */
    public function setCause(\Fulldon\DonateurBundle\Entity\Cause $cause = null)
    {
        $this->cause = $cause;

        return $this;
    }

    /**
     * Get cause
     *
     * @return \Fulldon\DonateurBundle\Entity\Cause 
     */
    public function getCause()
    {
        return $this->cause;
    }

    /**
     * Set filename
     *
     * @param string $filename
     * @return ProspectionDonateur
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string 
     */
    public function getFilename()
    {
        return $this->filename;
    }



    /**
     * Set line
     *
     * @param integer $line
     * @return ProspectionDonateur
     */
    public function setLine($line)
    {
        $this->line = $line;

        return $this;
    }

    /**
     * Get line
     *
     * @return integer 
     */
    public function getLine()
    {
        return $this->line;
    }
}

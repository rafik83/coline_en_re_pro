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
 * @ORM\Table(name="bill_report")})
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Fulldon\IntersaBundle\Entity\BillReportRepository")
 */
class BillReport
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="nb_new_donateur", type="integer", nullable=true)
     */
    private $nbNewDonateur;
    /**
     * @ORM\Column(name="nb_don_ponctuel", type="integer", nullable=true)
     */
    private $nbDonPonc;
    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;
    /**
     * @ORM\Column(name="nb_don_prelevement", type="text", nullable=true)
     */
    protected $nbDonPa;
    /**
     * @ORM\Column(name="nb_cs", type="boolean", nullable=true)
     */
    protected $nbCs;
    /**
     * @ORM\Column(name="nb_bc", type="boolean", nullable=true)
     */
    protected $nb_bc;
    /**
     * @ORM\Column(name="nb_espece", type="integer", nullable=true)
     */
    private $nbEspece;

    /**
     * @ORM\Column(name="maj_donateur", type="integer", nullable=true)
     */
    private $nbMajDonateur;

    /**
     * @ORM\Column(name="maj_don", type="integer", nullable=true)
     */
    private $nbMajDon;
    /**
     * @ORM\Column(name="nb_topage", type="integer", nullable=true)
     */
    private $nbTopage;
    /**
     * @ORM\Column(name="nb_email", type="integer", nullable=true)
     */
    private $nbEmail;
    /**
     * @ORM\Column(name="nb_duplicata", type="integer", nullable=true)
     */
    private $nbDuplicata;
    /**
     * @ORM\Column(name="nb_rf", type="integer", nullable=true)
     */
    private $nbRf;

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
     * Set nbNewDonateur
     *
     * @param integer $nbNewDonateur
     *
     * @return BillReport
     */
    public function setNbNewDonateur($nbNewDonateur)
    {
        $this->nbNewDonateur = $nbNewDonateur;

        return $this;
    }

    /**
     * Get nbNewDonateur
     *
     * @return integer
     */
    public function getNbNewDonateur()
    {
        return $this->nbNewDonateur;
    }

    /**
     * Set nbDonPonc
     *
     * @param integer $nbDonPonc
     *
     * @return BillReport
     */
    public function setNbDonPonc($nbDonPonc)
    {
        $this->nbDonPonc = $nbDonPonc;

        return $this;
    }

    /**
     * Get nbDonPonc
     *
     * @return integer
     */
    public function getNbDonPonc()
    {
        return $this->nbDonPonc;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return BillReport
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
     * Set nbDonPa
     *
     * @param string $nbDonPa
     *
     * @return BillReport
     */
    public function setNbDonPa($nbDonPa)
    {
        $this->nbDonPa = $nbDonPa;

        return $this;
    }

    /**
     * Get nbDonPa
     *
     * @return string
     */
    public function getNbDonPa()
    {
        return $this->nbDonPa;
    }

    /**
     * Set nbCs
     *
     * @param boolean $nbCs
     *
     * @return BillReport
     */
    public function setNbCs($nbCs)
    {
        $this->nbCs = $nbCs;

        return $this;
    }

    /**
     * Get nbCs
     *
     * @return boolean
     */
    public function getNbCs()
    {
        return $this->nbCs;
    }

    /**
     * Set nbBc
     *
     * @param boolean $nbBc
     *
     * @return BillReport
     */
    public function setNbBc($nbBc)
    {
        $this->nb_bc = $nbBc;

        return $this;
    }

    /**
     * Get nbBc
     *
     * @return boolean
     */
    public function getNbBc()
    {
        return $this->nb_bc;
    }

    /**
     * Set nbEspece
     *
     * @param integer $nbEspece
     *
     * @return BillReport
     */
    public function setNbEspece($nbEspece)
    {
        $this->nbEspece = $nbEspece;

        return $this;
    }

    /**
     * Get nbEspece
     *
     * @return integer
     */
    public function getNbEspece()
    {
        return $this->nbEspece;
    }

    /**
     * Set nbMajDonateur
     *
     * @param integer $nbMajDonateur
     *
     * @return BillReport
     */
    public function setNbMajDonateur($nbMajDonateur)
    {
        $this->nbMajDonateur = $nbMajDonateur;

        return $this;
    }

    /**
     * Get nbMajDonateur
     *
     * @return integer
     */
    public function getNbMajDonateur()
    {
        return $this->nbMajDonateur;
    }

    /**
     * Set nbMajDon
     *
     * @param integer $nbMajDon
     *
     * @return BillReport
     */
    public function setNbMajDon($nbMajDon)
    {
        $this->nbMajDon = $nbMajDon;

        return $this;
    }

    /**
     * Get nbMajDon
     *
     * @return integer
     */
    public function getNbMajDon()
    {
        return $this->nbMajDon;
    }

    /**
     * Set nbTopage
     *
     * @param integer $nbTopage
     *
     * @return BillReport
     */
    public function setNbTopage($nbTopage)
    {
        $this->nbTopage = $nbTopage;

        return $this;
    }

    /**
     * Get nbTopage
     *
     * @return integer
     */
    public function getNbTopage()
    {
        return $this->nbTopage;
    }

    /**
     * Set nbEmail
     *
     * @param integer $nbEmail
     *
     * @return BillReport
     */
    public function setNbEmail($nbEmail)
    {
        $this->nbEmail = $nbEmail;

        return $this;
    }

    /**
     * Get nbEmail
     *
     * @return integer
     */
    public function getNbEmail()
    {
        return $this->nbEmail;
    }

    /**
     * Set nbDuplicata
     *
     * @param integer $nbDuplicata
     *
     * @return BillReport
     */
    public function setNbDuplicata($nbDuplicata)
    {
        $this->nbDuplicata = $nbDuplicata;

        return $this;
    }

    /**
     * Get nbDuplicata
     *
     * @return integer
     */
    public function getNbDuplicata()
    {
        return $this->nbDuplicata;
    }

    /**
     * Set nbRf
     *
     * @param integer $nbRf
     *
     * @return BillReport
     */
    public function setNbRf($nbRf)
    {
        $this->nbRf = $nbRf;

        return $this;
    }

    /**
     * Get nbRf
     *
     * @return integer
     */
    public function getNbRf()
    {
        return $this->nbRf;
    }
}

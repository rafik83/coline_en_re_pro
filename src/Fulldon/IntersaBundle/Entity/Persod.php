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
 * @ORM\Table(name="persod")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Fulldon\IntersaBundle\Entity\PersodRepository")
 */
class Persod
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;


    /********************* Frensh version ************************/
    // Fields for the donation page
    //d for donateur
    // c for column
    // the first number is refering to the position of the column in the donation page.
    /**
     * @ORM\Column(name="dc2title1" , type="string", length=255, nullable=true)
     */
    private $dc2title1;

    /**
     * @ORM\Column(name="dc3block1" , type="text", nullable=true)
     */
    private $dc3block1;
    /**
     * @ORM\Column(name="dc1footer" , type="text", nullable=true)
     */
    private $dc1footer;
    /**
     * @ORM\Column(name="dc2footer" , type="text", nullable=true)
     */
    private $dc2footer;

    /**
     * @ORM\Column(name="dc1footertitle", type="string", length=255, nullable=true)
     */
    private $dc1footertitle;
    /**
     * @ORM\Column(name="dc2footertitle",type="string", length=255, nullable=true)
     */
    private $dc2footertitle;
    /**
     * @ORM\Column(name="dc3secure", type="text", nullable=true)
     */
    private $dc3secure;


    /*********************  English version ************************/
    // Fields for the donation page
    //d for donateur
    // c for column
    // the first number is refering to the position of the column in the donation page.
    /**
     * @ORM\Column(name="dc2title1_en" , type="string", length=255, nullable=true)
     */
    private $dc2title1En;

    /**
     * @ORM\Column(name="dc3block1_en" , type="text", nullable=true)
     */
    private $dc3block1En;
    /**
     * @ORM\Column(name="dc1footer_en" , type="text", nullable=true)
     */
    private $dc1footerEn;
    /**
     * @ORM\Column(name="dc2footer_en" , type="text", nullable=true)
     */
    private $dc2footerEn;

    /**
     * @ORM\Column(name="dc1footertitle_en", type="string", length=255, nullable=true)
     */
    private $dc1footertitleEn;
    /**
     * @ORM\Column(name="dc2footertitle_en",type="string", length=255, nullable=true)
     */
    private $dc2footertitleEn;

    /**
     * @ORM\Column(name="dc3secure_en", type="text", nullable=true)
     */
    private $dc3secureEn;




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
     * Set dc2title1
     *
     * @param string $dc2title1
     *
     * @return Persod
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
     * @return Persod
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
     * @return Persod
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
     * @return Persod
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
     * @return Persod
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
     * @return Persod
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
     * Set dc3secure
     *
     * @param string $dc3secure
     *
     * @return Persod
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
     * Set dc2title1En
     *
     * @param string $dc2title1En
     *
     * @return Persod
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
     * @return Persod
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
     * @return Persod
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
     * @return Persod
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
     * @return Persod
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
     * @return Persod
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
     * Set dc3secureEn
     *
     * @param string $dc3secureEn
     *
     * @return Persod
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
}

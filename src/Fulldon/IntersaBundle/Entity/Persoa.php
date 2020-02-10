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
 * @ORM\Table(name="persoa")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Fulldon\IntersaBundle\Entity\PersoaRepository")
 */
class Persoa
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    // Fields for the adherent page
    // a is for Adherent
    // c is for column
    // the first number is for the position of the column in the adherent page.
    // the second number is for the position of the block in the column.

    /**
     * @ORM\Column(name="ac2title1", type="string", length=255, nullable=true)
     */
    private $ac2title1;
    /**
     * @ORM\Column(name="ac2block1", type="text", nullable=true)
     */
    private $ac2block1;
    /**
     * @ORM\Column(name="ac2block2", type="text", nullable=true)
     */
    private $ac2block2;
    /**
     * @ORM\Column(name="ac3block2", type="text", nullable=true)
     */
    private $ac3block1;
    /**
     * @ORM\Column(name="ac1footer", type="text", nullable=true)
     */
    private $ac1footer;
    /**
     * @ORM\Column(name="ac2footer", type="text", nullable=true)
     */
    private $ac2footer;
    /**
     * @ORM\Column(name="ac1footertitle", type="string", length=255, nullable=true)
     */
    private $ac1footertitle;
    /**
     * @ORM\Column(name="ac2footertitle", type="string", length=255, nullable=true)
     */
    private $ac2footertitle;


    // Fields for the adherent page
    // a is for Adherent
    // c is for column
    // the first number is for the position of the column in the adherent page.
    // the second number is for the position of the block in the column.

    /**
     * @ORM\Column(name="ac2title1_en", type="string", length=255, nullable=true)
     */
    private $ac2title1En;
    /**
     * @ORM\Column(name="ac2block1_en", type="text", nullable=true)
     */
    private $ac2block1En;
    /**
     * @ORM\Column(name="ac2block2_en", type="text", nullable=true)
     */
    private $ac2block2En;
    /**
     * @ORM\Column(name="ac3block2_en", type="text", nullable=true)
     */
    private $ac3block1En;
    /**
     * @ORM\Column(name="ac1footer_en", type="text", nullable=true)
     */
    private $ac1footerEn;
    /**
     * @ORM\Column(name="ac2footer_en", type="text", nullable=true)
     */
    private $ac2footerEn;
    /**
     * @ORM\Column(name="ac1footertitle_en", type="string", length=255, nullable=true)
     */
    private $ac1footertitleEn;
    /**
     * @ORM\Column(name="ac2footertitle_en", type="string", length=255, nullable=true)
     */
    private $ac2footertitleEn;



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
     * Set ac2title1
     *
     * @param string $ac2title1
     *
     * @return Persoa
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
     * @return Persoa
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
     * @return Persoa
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
     * @return Persoa
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
     * @return Persoa
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
     * @return Persoa
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
     * @return Persoa
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
     * @return Persoa
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
     * Set ac2title1En
     *
     * @param string $ac2title1En
     *
     * @return Persoa
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
     * @return Persoa
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
     * @return Persoa
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
     * @return Persoa
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
     * @return Persoa
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
     * @return Persoa
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
     * @return Persoa
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
     * @return Persoa
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
}
